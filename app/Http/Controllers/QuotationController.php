<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\Product;
use App\Models\Client;
use App\Models\Company;
use App\Mail\EnviarCorreo;
use App\Mail\QuotationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class QuotationController extends Controller
{
    /**
     * Mostrar listado de cotizaciones
     */
    public function index()
    {
        return view('quotations.index');
    }

    /**
     * Mostrar formulario para crear nueva cotización
     */
    public function create()
    {
        $user = Auth::user();

        // Obtener empresas disponibles para el usuario
        $companies = Company::join('permission_company', 'companies.id', '=', 'permission_company.company_id')
            ->where('permission_company.user_id', $user->id)
            ->where('permission_company.state', 1)
            ->select('companies.id', 'companies.name as company_name')
            ->get();

        // Obtener clientes
        $clients = Client::all()->sortBy('razonsocial');

                // Obtener productos
        $products = Product::where('state', 1)->orderBy('name')->get();

        return view('quotations.create', compact('companies', 'clients', 'products'));
    }

    /**
     * Mostrar formulario para editar cotización
     */
    public function edit($id)
    {
        $quotation = Quotation::with(['details.product'])->findOrFail($id);

        // Solo permitir editar cotizaciones pendientes
        if ($quotation->status !== Quotation::STATUS_PENDING) {
            return redirect()->route('cotizaciones.index')
                ->with('error', 'Solo se pueden editar cotizaciones pendientes');
        }

        $user = Auth::user();

        $companies = Company::join('permission_company', 'companies.id', '=', 'permission_company.company_id')
            ->where('permission_company.user_id', $user->id)
            ->where('permission_company.state', 1)
            ->select('companies.id', 'companies.name as company_name')
            ->get();

        $clients = Client::all()->sortBy('razonsocial');
        $products = Product::where('state', 1)->orderBy('name')->get();

        return view('quotations.edit', compact('quotation', 'companies', 'clients', 'products'));
    }

    /**
     * Actualizar cotización existente
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'quote_date' => 'required|date',
            'valid_until' => 'required|date|after:quote_date',
            'currency' => 'required|string|max:3',
            'payment_terms' => 'nullable|string|max:255',
            'delivery_time' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
            'products.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            $quotation = Quotation::findOrFail($id);

            // Verificar que solo se puedan editar cotizaciones pendientes
            if ($quotation->status !== Quotation::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden editar cotizaciones pendientes'
                ], 400);
            }

            // Actualizar datos principales de la cotización
            $quotation->update([
                'client_id' => $request->client_id,
                'quote_date' => $request->quote_date,
                'valid_until' => $request->valid_until,
                'currency' => $request->currency,
                'payment_terms' => $request->payment_terms,
                'delivery_time' => $request->delivery_time,
                'notes' => $request->notes,
                'terms_conditions' => $request->terms_conditions,
            ]);

            // Eliminar detalles existentes
            $quotation->details()->delete();

            // Crear nuevos detalles
            foreach ($request->products as $productData) {
                QuotationDetail::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'unit_price' => $productData['unit_price'],
                    'discount_percentage' => $productData['discount_percentage'] ?? 0,
                ]);
            }

            // Recalcular totales
            $quotation->calculateTotals();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cotización actualizada exitosamente',
                'quotation_id' => $quotation->id
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Almacenar nueva cotización
     */
    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'client_id' => 'required|exists:clients,id',
            'quote_date' => 'required|date',
            'valid_until' => 'required|date|after:quote_date',
            'payment_terms' => 'nullable|string|max:255',
            'delivery_time' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
            'products.*.discount_percentage' => 'nullable|numeric|min:0|max:100'
        ]);

        DB::beginTransaction();
        try {
            // Generar número de cotización
            $quoteNumber = Quotation::generateQuoteNumber($request->company_id);

            // Crear cotización
            $quotation = Quotation::create([
                'quote_number' => $quoteNumber,
                'company_id' => $request->company_id,
                'client_id' => $request->client_id,
                'user_id' => Auth::id(),
                'quote_date' => $request->quote_date,
                'valid_until' => $request->valid_until,
                'payment_terms' => $request->payment_terms,
                'delivery_time' => $request->delivery_time,
                'notes' => $request->notes,
                'terms_conditions' => $request->terms_conditions,
                'currency' => $request->currency ?? 'USD',
                'status' => Quotation::STATUS_PENDING
            ]);

            // Crear detalles de cotización
            foreach ($request->products as $productData) {
                QuotationDetail::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'unit_price' => $productData['unit_price'],
                    'discount_percentage' => $productData['discount_percentage'] ?? 0,
                    'tax_rate' => 13.00, // IVA estándar
                    'description' => $productData['description'] ?? null
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cotización creada exitosamente',
                'quotation_id' => $quotation->id,
                'quote_number' => $quotation->quote_number
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar cotización específica
     */
    public function show($id)
    {
        $quotation = Quotation::with(['client', 'company', 'user', 'details.product'])
            ->findOrFail($id);

        return view('quotations.show', compact('quotation'));
    }

    /**
     * Eliminar cotización
     */
    public function destroy($id)
    {
        try {
            $decodedId = base64_decode($id);
            $quotation = Quotation::findOrFail($decodedId);

            // Solo permitir eliminar cotizaciones pendientes o rechazadas
            if (!in_array($quotation->status, [Quotation::STATUS_PENDING, Quotation::STATUS_REJECTED])) {
                return response()->json([
                    'res' => 0,
                    'message' => 'Solo se pueden eliminar cotizaciones pendientes o rechazadas'
                ]);
            }

            $quotation->delete();

            return response()->json([
                'res' => 1,
                'message' => 'Cotización eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'res' => 0,
                'message' => 'Error al eliminar la cotización: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Cambiar estado de la cotización
     */
    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected,expired'
        ]);

        $quotation = Quotation::findOrFail($id);
        $quotation->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado exitosamente'
        ]);
    }

    /**
     * Generar PDF de la cotización
     */
            public function generatePDF($id)
    {
        try {
            $quotation = Quotation::with(['client', 'company', 'user', 'details.product'])
                ->findOrFail($id);

            $pdf = app('dompdf.wrapper');
            $pdf->set_option('isHtml5ParserEnabled', true);
            $pdf->set_option('isRemoteEnabled', true);

            $pdf->loadView('pdf.quotation', compact('quotation'));
            $pdf->setPaper('Letter', 'portrait');

            return $pdf->stream("Cotizacion_{$quotation->quote_number}.pdf");

        } catch (\Exception $e) {
            \Log::error('Error generando PDF: ' . $e->getMessage());

            return response()->json([
                'error' => 'Error generando PDF: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Descargar PDF de la cotización
     */
    public function downloadPDF($id)
    {
        $quotation = Quotation::with(['client', 'company', 'user', 'details.product'])
            ->findOrFail($id);

        $pdf = app('dompdf.wrapper');
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_option('isRemoteEnabled', true);

        $pdf->loadView('pdf.quotation', compact('quotation'));
        $pdf->setPaper('Letter', 'portrait');

        return $pdf->download("Cotizacion_{$quotation->quote_number}.pdf");
    }

    /**
     * Enviar cotización por correo
     */
    public function sendEmail(Request $request, $id)
    {
        $request->validate([
            'email' => 'required|email',
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string'
        ]);

        try {
            $quotation = Quotation::with(['client', 'company', 'user', 'details.product'])
                ->findOrFail($id);

            // Generar PDF
            $pdf = app('dompdf.wrapper');
            $pdf->set_option('isHtml5ParserEnabled', true);
            $pdf->set_option('isRemoteEnabled', true);
            $pdf->loadView('pdf.quotation', compact('quotation'));
            $pdf->setPaper('Letter', 'portrait');

            // Preparar datos para el correo
            $emailData = [
                'nombre' => $quotation->client->razonsocial,
                'quote_number' => $quotation->quote_number,
                'company_name' => $quotation->company->name,
                'custom_message' => $request->message,
                'quotation' => $quotation
            ];

            $subject = $request->subject ?: "Cotización #{$quotation->quote_number} - {$quotation->company->name}";

            // Crear y enviar correo
            $mail = new QuotationMail($emailData, $subject);
            $mail->attachData(
                $pdf->output(),
                "Cotizacion_{$quotation->quote_number}.pdf",
                ['mime' => 'application/pdf']
            );

            Mail::to($request->email)->send($mail);

            return response()->json([
                'success' => true,
                'message' => 'Cotización enviada por correo exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el correo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener cotizaciones en formato JSON (para DataTables)
     */
    public function getQuotations()
    {
        $user = Auth::user();

        $quotations = Quotation::with(['client', 'company', 'user'])
            ->whereHas('company', function($query) use ($user) {
                $query->join('permission_company', 'companies.id', '=', 'permission_company.company_id')
                      ->where('permission_company.user_id', $user->id)
                      ->where('permission_company.state', 1);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Formatear datos para DataTables
        $data = [];
        foreach ($quotations as $quotation) {
            $data[] = [
                'id' => $quotation->id,
                'quote_number' => $quotation->quote_number,
                'client' => [
                    'razonsocial' => $quotation->client->razonsocial,
                    'email' => $quotation->client->email
                ],
                'quote_date' => $quotation->quote_date,
                'valid_until' => $quotation->valid_until,
                'total_amount' => $quotation->total_amount,
                'currency' => $quotation->currency,
                'status' => $quotation->status,
                'is_expired' => $quotation->isExpired()
            ];
        }

        return response()->json(['data' => $data]);
    }

    /**
     * Obtener cotización específica en JSON
     */
    public function getQuotation($id)
    {
        $quotation = Quotation::with(['client', 'company', 'user', 'details.product'])
            ->findOrFail(base64_decode($id));

        return response()->json($quotation);
    }
}
