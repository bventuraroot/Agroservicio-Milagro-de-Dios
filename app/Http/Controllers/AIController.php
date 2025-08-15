<?php

namespace App\Http\Controllers;

use App\Services\AIService;
use App\Models\AIConversation;
use App\Models\AIChatSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AIController extends Controller
{
    private $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

        public function chat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
            'context' => 'sometimes|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $response = $this->aiService->generateResponse(
                $request->message,
                $request->context ?? []
            );

            // Guardar la conversación si el usuario está autenticado
            if (auth()->check()) {
                $settings = AIChatSetting::where('user_id', auth()->id())->first();

                if (!$settings || $settings->save_conversations) {
                    AIConversation::create([
                        'user_id' => auth()->id(),
                        'query' => $request->message,
                        'response' => $response,
                        'context' => $request->context ?? [],
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'response' => $response,
                'timestamp' => now()
            ]);

        } catch (\Exception $e) {
            Log::error('Error en AIController::chat', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la consulta: ' . $e->getMessage()
            ], 500);
        }
    }

    public function analyze(Request $request)
    {
        // Endpoint para análisis específicos
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:ventas,inventario,reportes',
            'data' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de análisis inválido'
            ], 400);
        }

        $prompt = $this->buildAnalysisPrompt($request->type, $request->data);

        $response = $this->aiService->generateResponse($prompt, $request->data);

        return response()->json([
            'success' => true,
            'analysis' => $response,
            'type' => $request->type
        ]);
    }

    private function buildAnalysisPrompt($type, $data)
    {
        $prompts = [
            'ventas' => 'Analiza los datos de ventas proporcionados y genera insights sobre tendencias, productos más vendidos y recomendaciones.',
            'inventario' => 'Analiza el estado del inventario y proporciona recomendaciones sobre reabastecimiento y optimización.',
            'reportes' => 'Genera un reporte detallado basado en los datos proporcionados.'
        ];

        return $prompts[$type] ?? 'Analiza los datos proporcionados.';
    }

    public function getSettings()
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        $settings = AIChatSetting::where('user_id', auth()->id())->first();

        if (!$settings) {
            $settings = AIChatSetting::create([
                'user_id' => auth()->id(),
                ...AIChatSetting::getDefaultSettings()
            ]);
        }

        return response()->json([
            'success' => true,
            'settings' => $settings
        ]);
    }

    public function updateSettings(Request $request)
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'background_color' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'text_color' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'font_size' => 'sometimes|string|regex:/^\d+px$/',
            'save_conversations' => 'sometimes|boolean',
            'show_timestamps' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 400);
        }

        $settings = AIChatSetting::updateOrCreate(
            ['user_id' => auth()->id()],
            $request->only([
                'background_color',
                'text_color',
                'accent_color',
                'font_size',
                'save_conversations',
                'show_timestamps'
            ])
        );

        return response()->json([
            'success' => true,
            'settings' => $settings
        ]);
    }

    public function getConversations()
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        $conversations = AIConversation::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'conversations' => $conversations
        ]);
    }
}
