<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Milon\Barcode\DNS1D;

class BarcodeController extends Controller
{
    public function generate($code)
    {
        try {
            $dns1d = new DNS1D();
            $tipo = detectarTipoBarcode($code);
            $barcode = $dns1d->getBarcodeHTML($code, $tipo);

            if (empty($barcode)) {
                return response()->json(['error' => 'No se pudo generar el código de barras'], 500);
            }

            return response()->json([
                'html' => $barcode
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al generar el código de barras: ' . $e->getMessage()
            ], 500);
        }
    }
}

function detectarTipoBarcode($code) {
    $length = strlen($code);

    if (preg_match('/^[0-9]+$/', $code)) {
        if ($length == 13) {
            return 'EAN13';
        } elseif ($length == 8) {
            return 'EAN8';
        } elseif ($length == 12) {
            return 'UPCA';
        } elseif ($length == 7) {
            return 'UPCE';
        }
    }
    // Si contiene letras o símbolos, probablemente sea Code 39
    if (preg_match('/^[A-Z0-9\\-\\. \\$\\/\\+%]+$/i', $code)) {
        return 'C39';
    }
    // Por defecto, usa Code 128 (muy flexible)
    return 'C128';
}
