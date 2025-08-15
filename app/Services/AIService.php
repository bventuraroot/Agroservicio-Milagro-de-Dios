<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\DatabaseQueryService;

class AIService
{
    private $apiKey;
    private $model;
    private $baseUrl = 'https://generativelanguage.googleapis.com/v1/models/';
    private $dbService;

    public function __construct()
    {
        $this->apiKey = config('ai.google.api_key');
        $this->model = config('ai.google.model');
        $this->dbService = new DatabaseQueryService();
    }

                public function generateResponse($prompt, $context = [])
    {
                // Verificar si estamos en modo demo o si no hay API key
        if (config('ai.google.demo_mode', false) || !$this->apiKey) {
            return $this->generateDemoResponse($prompt);
        }

        // Verificar si el modelo es válido
        if ($this->model === 'gemini-pro') {
            return $this->generateDemoResponse($prompt) . "\n\n💡 **Nota**: El modelo gemini-pro no está disponible en la versión v1 de la API. Usando respuestas demo.";
        }

        try {
            // Crear caché key basado en el prompt
            $cacheKey = 'ai_response_' . md5($prompt . json_encode($context));

            // Verificar si ya existe en caché
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $url = $this->baseUrl . $this->model . ':generateContent?key=' . $this->apiKey;

            $data = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $this->buildPrompt($prompt, $context)
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => config('ai.google.max_tokens'),
                    'temperature' => 0.7
                ]
            ];

            $response = Http::timeout(30)->post($url, $data);

                        if ($response->successful()) {
                $result = $response->json();
                $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'No se pudo generar respuesta';

                // Guardar en caché por 1 hora
                Cache::put($cacheKey, $text, 3600);

                // Log de la consulta
                $this->logQuery($prompt, $text);

                return $text;
            }

            $errorResponse = $response->json();
            $statusCode = $response->status();

            Log::error('Error en Gemini API', [
                'response' => $errorResponse,
                'status' => $statusCode
            ]);

            // Manejar errores específicos
            if ($statusCode === 503) {
                return 'El servicio de IA está temporalmente sobrecargado. Por favor, intenta de nuevo en unos minutos.';
            } elseif ($statusCode === 429) {
                $message = 'Has excedido la cuota gratuita de Google AI. ';
                if (strpos($errorResponse['error']['message'] ?? '', 'quota') !== false) {
                    $message .= 'Para continuar usando el chat, necesitas:';
                    $message .= "\n\n• **Actualizar tu plan** en Google AI Studio";
                    $message .= "\n• **O esperar** hasta mañana para que se reinicie la cuota gratuita";
                    $message .= "\n\n💡 **Alternativa**: Puedes usar el chat en modo demo sin IA mientras tanto.";
                } else {
                    $message .= 'Por favor, espera un momento antes de intentar de nuevo.';
                }
                return $message;
            } elseif ($statusCode === 401) {
                return 'Error de autenticación con la API de IA. Verifica la configuración.';
            } elseif ($statusCode === 400) {
                return 'La consulta no pudo ser procesada. Por favor, reformula tu pregunta.';
            } else {
                return 'Lo siento, no pude procesar tu consulta en este momento. Error: ' . ($errorResponse['error']['message'] ?? 'Desconocido');
            }

        } catch (\Exception $e) {
            Log::error('Error en AIService', [
                'message' => $e->getMessage(),
                'prompt' => $prompt
            ]);

            return 'Ocurrió un error al procesar tu consulta.';
        }
    }

        private function buildPrompt($prompt, $context = [])
    {
        // Obtener datos de la base de datos
        $dbData = $this->getDatabaseContext($prompt);

        $basePrompt = "Eres un asistente inteligente para un sistema de gestión empresarial llamado 'Agroservicio Milagro de Dios'.
        Tu función es ayudar con consultas sobre ventas, inventario, facturación y reportes.

        INSTRUCCIONES DE FORMATO:
        - Responde de manera clara y útil en español
        - Usa párrafos cortos para facilitar la lectura
        - Separa las ideas con saltos de línea
        - Usa viñetas (•) para listas
        - Usa negritas (**texto**) para destacar información importante
        - Usa cursivas (*texto*) para términos técnicos
        - Agrega espacios entre secciones para mejor legibilidad
        - Si das pasos, numéralos (1., 2., 3.)
        - Si das recomendaciones, usa 💡 para destacarlas
        - Si algo no puedes hacer di que te comuniques con el administrador del sistema que es Brian Ventura mi whatsapp es +50373199274 para que te ayude.

        DATOS DEL SISTEMA (información real de la base de datos):
        " . json_encode($dbData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "

        Contexto adicional: " . json_encode($context) . "

        Consulta del usuario: " . $prompt;

        return $basePrompt;
    }

    private function logQuery($prompt, $response)
    {
        Log::channel('ai')->info('Consulta procesada', [
            'prompt' => $prompt,
            'response' => $response,
            'user_id' => auth()->id(),
            'timestamp' => now()
        ]);
    }

    /**
     * Obtener contexto de la base de datos basado en la consulta
     */
    private function getDatabaseContext($prompt)
    {
        $context = [];
        $promptLower = strtolower($prompt);

        // Estadísticas generales del sistema
        $context['estadisticas_generales'] = $this->dbService->getSystemStats();

        // Si la consulta menciona productos
        if (strpos($promptLower, 'producto') !== false || strpos($promptLower, 'inventario') !== false) {
            $context['productos_inactivos'] = $this->dbService->getInactiveProducts(5);

            // Si menciona buscar algo específico
            if (preg_match('/(buscar|encontrar|producto|item)\s+([a-zA-Z0-9\s]+)/', $prompt, $matches)) {
                $searchTerm = trim($matches[2]);
                if (strlen($searchTerm) > 2) {
                    $context['productos_busqueda'] = $this->dbService->searchProducts($searchTerm, 5);
                }
            }
        }

        // Si la consulta menciona ventas
        if (strpos($promptLower, 'venta') !== false || strpos($promptLower, 'ventas') !== false) {
            $context['ventas_recientes'] = $this->dbService->getRecentSales(5);
        }

        // Si la consulta menciona cotizaciones
        if (strpos($promptLower, 'cotizacion') !== false || strpos($promptLower, 'cotización') !== false) {
            $context['cotizaciones_pendientes'] = $this->dbService->getPendingQuotations(5);
        }

        // Si la consulta menciona usuarios
        if (strpos($promptLower, 'usuario') !== false || strpos($promptLower, 'user') !== false) {
            if (auth()->check()) {
                $context['usuario_actual'] = $this->dbService->getUserInfo(auth()->id());
            }
        }

        return $context;
    }

    private function generateDemoResponse($prompt)
    {
        // Obtener datos de la base de datos
        $dbData = $this->getDatabaseContext($prompt);
        $promptLower = strtolower($prompt);

        // Respuestas con datos reales de la base de datos
        if (strpos($promptLower, 'hola') !== false) {
            $stats = $dbData['estadisticas_generales'] ?? [];
            $response = '¡Hola! Soy el asistente IA de **Agroservicio Milagro de Dios**.';

            if (!empty($stats)) {
                $response .= "\n\n📊 **Estadísticas del sistema**:";
                if (isset($stats['products'])) {
                    $response .= "\n• **Productos**: " . $stats['products']['total'] . " total";
                    $response .= "\n• **Activos**: " . $stats['products']['activos'] . " productos";
                    $response .= "\n• **Inactivos**: " . $stats['products']['inactivos'] . " productos";
                }
                if (isset($stats['sales'])) {
                    $response .= "\n• **Ventas hoy**: " . $stats['sales']['hoy'] . " ventas";
                }
                if (isset($stats['quotations'])) {
                    $response .= "\n• **Cotizaciones pendientes**: " . $stats['quotations']['pendientes'] . " cotizaciones";
                }
            }

            return $response;
        }

        if (strpos($promptLower, 'inventario') !== false || strpos($promptLower, 'producto') !== false) {
            $inactiveProducts = $dbData['productos_inactivos'] ?? [];
            $response = "📦 **Estado del inventario**:";

            if (!empty($inactiveProducts)) {
                $response .= "\n\n⚠️ **Productos inactivos**:";
                foreach (array_slice($inactiveProducts, 0, 3) as $product) {
                    $response .= "\n• **" . $product->name . "**: $" . $product->price . " (" . $product->category . ")";
                }
                if (count($inactiveProducts) > 3) {
                    $response .= "\n• Y " . (count($inactiveProducts) - 3) . " productos más...";
                }
            } else {
                $response .= "\n\n✅ **Todos los productos están activos**";
            }

            return $response;
        }

        if (strpos($promptLower, 'venta') !== false) {
            $recentSales = $dbData['ventas_recientes'] ?? [];
            $response = "💰 **Información de ventas**:";

            if (!empty($recentSales)) {
                $response .= "\n\n📈 **Ventas recientes**:";
                foreach (array_slice($recentSales, 0, 3) as $sale) {
                    $response .= "\n• Venta #" . $sale->id . " - " . date('d/m/Y', strtotime($sale->created_at));
                }
            }

            return $response;
        }

        if (strpos($promptLower, 'cotizacion') !== false || strpos($promptLower, 'cotización') !== false) {
            $pendingQuotations = $dbData['cotizaciones_pendientes'] ?? [];
            $response = "📋 **Estado de cotizaciones**:";

            if (!empty($pendingQuotations)) {
                $response .= "\n\n⏳ **Cotizaciones pendientes**: " . count($pendingQuotations);
                foreach (array_slice($pendingQuotations, 0, 3) as $quotation) {
                    $response .= "\n• Cotización #" . $quotation->id . " - " . date('d/m/Y', strtotime($quotation->created_at));
                }
            } else {
                $response .= "\n\n✅ **No hay cotizaciones pendientes**";
            }

            return $response;
        }

        // Respuestas por defecto
        $responses = [
            'producto' => 'Para consultas sobre **productos**, puedes acceder al módulo de inventario. Allí encontrarás el stock actual, precios y descripciones.',
            'reporte' => 'Los **reportes** están disponibles en el módulo de reportes. Puedes generar reportes de ventas, inventario y otros datos importantes del negocio.',
            'ayuda' => '💡 **Consejos útiles**:\n\n• Usa el menú lateral para navegar entre módulos\n• Los reportes se pueden exportar en PDF\n• Puedes personalizar el chat en la configuración\n• El historial guarda tus conversaciones anteriores'
        ];

        foreach ($responses as $keyword => $response) {
            if (strpos($promptLower, $keyword) !== false) {
                return $response;
            }
        }

        return 'Entiendo tu consulta sobre "' . $prompt . '". En modo demo puedo darte información general sobre el sistema Agroservicio Milagro de Dios. Para respuestas más específicas, necesitarías activar la IA completa.';
    }
}
