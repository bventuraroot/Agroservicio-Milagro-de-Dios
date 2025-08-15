<?php

namespace App\Http\Controllers;

use App\Services\AIService;
use App\Models\AIConversation;
use App\Models\AIChatSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AIChatPageController extends Controller
{
    private $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Mostrar la página principal del chat de IA
     */
    public function index()
    {
        // Obtener configuraciones del usuario
        $settings = AIChatSetting::where('user_id', auth()->id())->first();
        if (!$settings) {
            $settings = AIChatSetting::create([
                'user_id' => auth()->id(),
                ...AIChatSetting::getDefaultSettings()
            ]);
        }

        // Obtener conversaciones recientes
        $conversations = AIConversation::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('ai-chat.index', compact('settings', 'conversations'));
    }

    /**
     * Enviar mensaje y obtener respuesta
     */
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
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

            // Guardar la conversación
            $conversation = AIConversation::create([
                'user_id' => auth()->id(),
                'query' => $request->message,
                'response' => $response,
                'context' => $request->context ?? [],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'success' => true,
                'response' => $response,
                'conversation_id' => $conversation->id,
                'timestamp' => now()
            ]);

        } catch (\Exception $e) {
            Log::error('Error en AIChatPageController::sendMessage', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la consulta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener historial de conversaciones
     */
    public function getHistory(Request $request)
    {
        $limit = $request->get('limit', 50);
        $offset = $request->get('offset', 0);

        $conversations = AIConversation::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'conversations' => $conversations,
            'has_more' => $conversations->count() === $limit
        ]);
    }

    /**
     * Obtener una conversación específica
     */
    public function getConversation($id)
    {
        $conversation = AIConversation::where('user_id', auth()->id())
            ->where('id', $id)
            ->first();

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversación no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'conversation' => $conversation
        ]);
    }

    /**
     * Eliminar una conversación
     */
    public function deleteConversation($id)
    {
        $conversation = AIConversation::where('user_id', auth()->id())
            ->where('id', $id)
            ->first();

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversación no encontrada'
            ], 404);
        }

        $conversation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conversación eliminada correctamente'
        ]);
    }

    /**
     * Limpiar todo el historial
     */
    public function clearHistory()
    {
        AIConversation::where('user_id', auth()->id())->delete();

        return response()->json([
            'success' => true,
            'message' => 'Historial limpiado correctamente'
        ]);
    }

    /**
     * Actualizar configuraciones del chat
     */
    public function updateSettings(Request $request)
    {
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
                'background_color', 'text_color', 'accent_color',
                'font_size', 'save_conversations', 'show_timestamps'
            ])
        );

        return response()->json([
            'success' => true,
            'settings' => $settings,
            'message' => 'Configuración actualizada correctamente'
        ]);
    }
}
