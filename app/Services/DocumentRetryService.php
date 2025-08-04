<?php

namespace App\Services;

use App\Models\Dte;
use App\Models\Sale;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Exception;

class DocumentRetryService
{
    private ElectronicInvoiceErrorHandler $errorHandler;
    private SaleController $saleController;

    public function __construct(
        ElectronicInvoiceErrorHandler $errorHandler,
        SaleController $saleController
    ) {
        $this->errorHandler = $errorHandler;
        $this->saleController = $saleController;
    }

    /**
     * Procesa todos los documentos que necesitan reintento
     */
    public function processRetries(): array
    {
        $results = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => 0
        ];

        $documentsToRetry = $this->getDocumentsForRetry();

        Log::info("Iniciando procesamiento de reintentos", [
            'total_documents' => $documentsToRetry->count()
        ]);

        foreach ($documentsToRetry as $dte) {
            try {
                $result = $this->retryDocument($dte);
                $results['processed']++;

                switch ($result['status']) {
                    case 'success':
                        $results['successful']++;
                        break;
                    case 'failed':
                        $results['failed']++;
                        break;
                    case 'skipped':
                        $results['skipped']++;
                        break;
                }

            } catch (Exception $e) {
                $results['errors']++;
                Log::error("Error en reintento de documento", [
                    'dte_id' => $dte->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Actualizar cache con último procesamiento
        Cache::put('retry_last_run', now()->toDateTimeString(), 3600 * 24);

        Log::info("Procesamiento de reintentos completado", $results);

        return $results;
    }

    /**
     * Obtiene documentos que necesitan reintento
     */
    private function getDocumentsForRetry(): Collection
    {
        return Dte::paraReintento()
            ->with(['sale', 'company'])
            ->orderBy('updated_at')
            ->limit(100) // Procesar máximo 100 por lote
            ->get();
    }

    /**
     * Reintenta el envío de un documento específico
     */
    public function retryDocument(Dte $dte): array
    {
        $result = [
            'status' => 'skipped',
            'message' => '',
            'dte_id' => $dte->id
        ];

        // Verificar si el documento puede ser reintentado
        if (!$this->canRetryDocument($dte)) {
            $result['message'] = 'Documento no puede ser reintentado';
            return $result;
        }

        // Verificar límite de reintentos
        if ($dte->nSends >= 3) {
            $result['message'] = 'Límite de reintentos alcanzado';
            $this->handleMaxRetriesReached($dte);
            return $result;
        }

        Log::info("Reintentando documento", [
            'dte_id' => $dte->id,
            'attempt' => $dte->nSends + 1,
            'company_id' => $dte->company_id
        ]);

        try {
            // Preparar datos del documento
            $documentData = $this->prepareDocumentForRetry($dte);

            // Realizar el envío
            $response = $this->sendDocumentToHacienda($documentData);

            // Procesar respuesta
            if ($this->isSuccessfulResponse($response)) {
                $this->handleSuccessfulRetry($dte, $response);
                $result['status'] = 'success';
                $result['message'] = 'Documento enviado exitosamente';
            } else {
                $this->handleFailedRetry($dte, $response);
                $result['status'] = 'failed';
                $result['message'] = 'Envío falló: ' . ($response['descripcionMsg'] ?? 'Error desconocido');
            }

        } catch (Exception $e) {
            $this->handleRetryException($dte, $e);
            $result['status'] = 'failed';
            $result['message'] = 'Error en reintento: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Verifica si un documento puede ser reintentado
     */
    private function canRetryDocument(Dte $dte): bool
    {
        // Verificar estado
        if (!$dte->puedeReintentar()) {
            return false;
        }

        // Verificar si el documento aún existe
        if (!$dte->sale) {
            Log::warning("Documento DTE sin venta asociada", ['dte_id' => $dte->id]);
            return false;
        }

        // Verificar si ya está en contingencia
        if ($dte->idContingencia) {
            return false;
        }

        // Verificar cooldown period
        $cooldownMinutes = $this->calculateCooldownPeriod($dte->nSends);
        if ($dte->updated_at->addMinutes($cooldownMinutes) > now()) {
            return false;
        }

        return true;
    }

    /**
     * Calcula el período de cooldown basado en el número de intentos
     */
    private function calculateCooldownPeriod(int $attempts): int
    {
        // Backoff exponencial: 30min, 60min, 120min
        return min(30 * pow(2, $attempts), 120);
    }

    /**
     * Prepara los datos del documento para reintento
     */
    private function prepareDocumentForRetry(Dte $dte): array
    {
        // Reconstruir los datos del comprobante desde el JSON almacenado
        $jsonData = $dte->json_decoded;

        if (!$jsonData) {
            throw new Exception("No se encontraron datos JSON para el documento {$dte->id}");
        }

        return [
            'dte' => $dte,
            'sale' => $dte->sale,
            'company_id' => $dte->company_id,
            'document_data' => $jsonData,
            'transaction_code' => $dte->codTransaction
        ];
    }

    /**
     * Envía el documento a Hacienda
     */
    private function sendDocumentToHacienda(array $documentData): array
    {
        $dte = $documentData['dte'];

        // Incrementar contador de envíos antes del intento
        $dte->incrementarIntentos();

        try {
            // Usar el método existente del SaleController
            $response = $this->saleController->Enviar_Hacienda(
                $documentData['document_data'],
                $documentData['transaction_code']
            );

            return is_array($response) ? $response : json_decode($response, true);

        } catch (Exception $e) {
            Log::error("Error enviando documento a Hacienda", [
                'dte_id' => $dte->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Verifica si la respuesta es exitosa
     */
    private function isSuccessfulResponse(array $response): bool
    {
        return isset($response['codEstado']) && $response['codEstado'] === '02';
    }

    /**
     * Maneja un reintento exitoso
     */
    private function handleSuccessfulRetry(Dte $dte, array $response): void
    {
        $dte->marcarComoEnviado($response);

        Log::info("Reintento exitoso", [
            'dte_id' => $dte->id,
            'attempt' => $dte->nSends,
            'codigo_generacion' => $response['codigoGeneracion'] ?? null
        ]);

        // Limpiar caché de errores para esta empresa
        $this->clearErrorCounters($dte->company_id);
    }

    /**
     * Maneja un reintento fallido
     */
    private function handleFailedRetry(Dte $dte, array $response): void
    {
        // Usar el error handler para procesar el fallo
        $this->errorHandler->handleDocumentError($dte, $response);

        Log::warning("Reintento fallido", [
            'dte_id' => $dte->id,
            'attempt' => $dte->nSends,
            'error_code' => $response['codigoMsg'] ?? null,
            'error_message' => $response['descripcionMsg'] ?? null
        ]);
    }

    /**
     * Maneja una excepción durante el reintento
     */
    private function handleRetryException(Dte $dte, Exception $e): void
    {
        $errorData = [
            'descripcionMsg' => $e->getMessage(),
            'codigoMsg' => 'RETRY_ERROR',
            'clasificaMsg' => 'ERROR',
            'observacionesMsg' => 'Error durante reintento automático'
        ];

        $this->errorHandler->handleDocumentError($dte, $errorData, $e);
    }

    /**
     * Maneja documentos que alcanzaron el límite de reintentos
     */
    private function handleMaxRetriesReached(Dte $dte): void
    {
        Log::warning("Límite de reintentos alcanzado", [
            'dte_id' => $dte->id,
            'company_id' => $dte->company_id,
            'total_attempts' => $dte->nSends
        ]);

        // El error handler se encargará de crear la contingencia
        $errorData = [
            'descripcionMsg' => 'Límite de reintentos automáticos alcanzado',
            'codigoMsg' => 'MAX_RETRIES',
            'clasificaMsg' => 'ERROR'
        ];

        $this->errorHandler->handleDocumentError($dte, $errorData);
    }

    /**
     * Limpia contadores de errores para una empresa
     */
    private function clearErrorCounters(int $companyId): void
    {
        $cachePattern = "error_count:{$companyId}:*";

        // En un sistema real, esto requeriría un comando Redis SCAN
        // Por simplicidad, limpiamos los tipos conocidos
        $errorTypes = [
            'connection_error',
            'auth_error',
            'validation_error',
            'timeout_error',
            'server_error',
            'format_error'
        ];

        foreach ($errorTypes as $type) {
            $cacheKey = "error_count:{$companyId}:{$type}:" . now()->format('Y-m-d-H');
            Cache::forget($cacheKey);
        }
    }

    /**
     * Reintenta documentos específicos por IDs
     */
    public function retrySpecificDocuments(array $dteIds): array
    {
        $results = [
            'requested' => count($dteIds),
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'not_found' => 0
        ];

        foreach ($dteIds as $dteId) {
            $dte = Dte::find($dteId);

            if (!$dte) {
                $results['not_found']++;
                continue;
            }

            try {
                $result = $this->retryDocument($dte);
                $results['processed']++;

                if ($result['status'] === 'success') {
                    $results['successful']++;
                } else {
                    $results['failed']++;
                }

            } catch (Exception $e) {
                $results['failed']++;
                Log::error("Error en reintento específico", [
                    'dte_id' => $dteId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Obtiene estadísticas de reintentos
     */
    public function getRetryStats(int $days = 7): array
    {
        $dateFrom = now()->subDays($days);

        $stats = [
            'total_retries' => 0,
            'successful_retries' => 0,
            'failed_retries' => 0,
            'documents_needing_retry' => 0,
            'avg_attempts_per_document' => 0,
            'success_rate' => 0,
            'last_run' => Cache::get('retry_last_run', 'Nunca')
        ];

        // Documentos con reintentos en el período
        $retriedDocuments = Dte::where('nSends', '>', 1)
            ->where('updated_at', '>=', $dateFrom)
            ->get();

        $stats['total_retries'] = $retriedDocuments->sum('nSends') - $retriedDocuments->count();
        $stats['successful_retries'] = $retriedDocuments->where('codEstado', Dte::ESTADO_ENVIADO)->count();
        $stats['failed_retries'] = $retriedDocuments->where('codEstado', '!=', Dte::ESTADO_ENVIADO)->count();

        // Documentos que actualmente necesitan reintento
        $stats['documents_needing_retry'] = Dte::paraReintento()->count();

        if ($retriedDocuments->count() > 0) {
            $stats['avg_attempts_per_document'] = round(
                $retriedDocuments->avg('nSends'), 2
            );

            $stats['success_rate'] = round(
                ($stats['successful_retries'] / $retriedDocuments->count()) * 100, 2
            );
        }

        return $stats;
    }

    /**
     * Programa reintentos para documentos específicos
     */
    public function scheduleRetries(array $dteIds, int $delayMinutes = 0): array
    {
        $results = [
            'scheduled' => 0,
            'errors' => 0,
            'already_scheduled' => 0
        ];

        foreach ($dteIds as $dteId) {
            try {
                $dte = Dte::find($dteId);

                if (!$dte) {
                    $results['errors']++;
                    continue;
                }

                if ($dte->isEnCola()) {
                    $results['already_scheduled']++;
                    continue;
                }

                $dte->update([
                    'codEstado' => Dte::ESTADO_EN_COLA,
                    'Estado' => 'Programado para reintento',
                    'updated_at' => now()->addMinutes($delayMinutes)
                ]);

                $results['scheduled']++;

                Log::info("Documento programado para reintento", [
                    'dte_id' => $dte->id,
                    'delay_minutes' => $delayMinutes
                ]);

            } catch (Exception $e) {
                $results['errors']++;
                Log::error("Error programando reintento", [
                    'dte_id' => $dteId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Obtiene documentos con problemas persistentes
     */
    public function getProblematicDocuments(int $minAttempts = 2): Collection
    {
        return Dte::where('nSends', '>=', $minAttempts)
            ->fallidos()
            ->with(['sale', 'company'])
            ->orderBy('nSends', 'desc')
            ->orderBy('updated_at')
            ->get();
    }

    /**
     * Analiza patrones de fallas para mejorar el sistema
     */
    public function analyzeFailurePatterns(int $days = 30): array
    {
        $dateFrom = now()->subDays($days);

        $failedDocuments = Dte::fallidos()
            ->where('updated_at', '>=', $dateFrom)
            ->get();

        $patterns = [
            'total_failed' => $failedDocuments->count(),
            'by_company' => [],
            'by_document_type' => [],
            'by_error_type' => [],
            'by_attempts' => [],
            'time_distribution' => []
        ];

        foreach ($failedDocuments as $dte) {
            // Por empresa
            $companyId = $dte->company_id;
            $patterns['by_company'][$companyId] = ($patterns['by_company'][$companyId] ?? 0) + 1;

            // Por tipo de documento
            $docType = $dte->tipoDte;
            $patterns['by_document_type'][$docType] = ($patterns['by_document_type'][$docType] ?? 0) + 1;

            // Por número de intentos
            $attempts = $dte->nSends;
            $patterns['by_attempts'][$attempts] = ($patterns['by_attempts'][$attempts] ?? 0) + 1;

            // Por hora del día
            $hour = $dte->updated_at->format('H');
            $patterns['time_distribution'][$hour] = ($patterns['time_distribution'][$hour] ?? 0) + 1;
        }

        return $patterns;
    }
}
