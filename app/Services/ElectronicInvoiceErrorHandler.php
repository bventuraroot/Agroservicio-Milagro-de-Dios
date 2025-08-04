<?php

namespace App\Services;

use App\Models\Dte;
use App\Models\Contingencia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Exception;

class ElectronicInvoiceErrorHandler
{
    // Tipos de errores conocidos
    const ERROR_CONNECTION = 'connection_error';
    const ERROR_AUTHENTICATION = 'auth_error';
    const ERROR_VALIDATION = 'validation_error';
    const ERROR_TIMEOUT = 'timeout_error';
    const ERROR_SERVER = 'server_error';
    const ERROR_FORMAT = 'format_error';
    const ERROR_UNKNOWN = 'unknown_error';

    // Niveles de severidad
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    /**
     * Maneja errores de documentos electrónicos
     */
    public function handleDocumentError(Dte $dte, array $errorData, Exception $exception = null): void
    {
        $errorType = $this->classifyError($errorData, $exception);
        $severity = $this->determineSeverity($errorType, $dte);

        // Log del error
        $this->logError($dte, $errorData, $errorType, $severity, $exception);

        // Actualizar el documento según el tipo de error
        $this->updateDocumentStatus($dte, $errorType, $errorData);

        // Determinar acción a tomar
        $action = $this->determineAction($dte, $errorType, $severity);
        $this->executeAction($dte, $action, $errorData);

        // Incrementar contadores de errores
        $this->incrementErrorCounters($dte->company_id, $errorType);

        // Verificar si necesita alertas
        $this->checkAlertThresholds($dte->company_id, $errorType);
    }

    /**
     * Clasifica el tipo de error basado en la respuesta
     */
    private function classifyError(array $errorData, Exception $exception = null): string
    {
        // Errores de conexión
        if ($exception && (
            strpos($exception->getMessage(), 'Connection') !== false ||
            strpos($exception->getMessage(), 'timeout') !== false ||
            strpos($exception->getMessage(), 'cURL') !== false
        )) {
            return self::ERROR_CONNECTION;
        }

        // Errores de autenticación
        if (isset($errorData['status']) && in_array($errorData['status'], [401, 403])) {
            return self::ERROR_AUTHENTICATION;
        }

        // Errores del servidor de Hacienda
        if (isset($errorData['status']) && $errorData['status'] >= 500) {
            return self::ERROR_SERVER;
        }

        // Errores de validación (códigos específicos de MH)
        if (isset($errorData['codigoMsg']) && $this->isValidationError($errorData['codigoMsg'])) {
            return self::ERROR_VALIDATION;
        }

        // Errores de formato
        if (isset($errorData['descripcionMsg']) && (
            strpos(strtolower($errorData['descripcionMsg']), 'formato') !== false ||
            strpos(strtolower($errorData['descripcionMsg']), 'schema') !== false ||
            strpos(strtolower($errorData['descripcionMsg']), 'json') !== false
        )) {
            return self::ERROR_FORMAT;
        }

        // Timeout específico
        if (isset($errorData['descripcionMsg']) &&
            strpos(strtolower($errorData['descripcionMsg']), 'timeout') !== false) {
            return self::ERROR_TIMEOUT;
        }

        return self::ERROR_UNKNOWN;
    }

    /**
     * Determina la severidad del error
     */
    private function determineSeverity(string $errorType, Dte $dte): string
    {
        // Errores críticos para documentos urgentes
        if ($dte->es_urgente) {
            return self::SEVERITY_CRITICAL;
        }

        return match($errorType) {
            self::ERROR_CONNECTION, self::ERROR_SERVER => self::SEVERITY_HIGH,
            self::ERROR_AUTHENTICATION => self::SEVERITY_CRITICAL,
            self::ERROR_VALIDATION, self::ERROR_FORMAT => self::SEVERITY_MEDIUM,
            self::ERROR_TIMEOUT => self::SEVERITY_MEDIUM,
            default => self::SEVERITY_LOW
        };
    }

    /**
     * Determina la acción a tomar
     */
    private function determineAction(Dte $dte, string $errorType, string $severity): string
    {
        // Si ya se han hecho muchos intentos
        if ($dte->nSends >= 3) {
            return 'create_contingency';
        }

        // Errores que requieren intervención manual inmediata
        if (in_array($errorType, [self::ERROR_AUTHENTICATION, self::ERROR_FORMAT]) ||
            $severity === self::SEVERITY_CRITICAL) {
            return 'manual_review';
        }

        // Errores temporales que se pueden reintentar
        if (in_array($errorType, [self::ERROR_CONNECTION, self::ERROR_TIMEOUT, self::ERROR_SERVER])) {
            return 'schedule_retry';
        }

        // Errores de validación que necesitan revisión
        if ($errorType === self::ERROR_VALIDATION) {
            return 'validation_review';
        }

        return 'schedule_retry';
    }

    /**
     * Ejecuta la acción determinada
     */
    private function executeAction(Dte $dte, string $action, array $errorData): void
    {
        switch ($action) {
            case 'create_contingency':
                $this->createAutomaticContingency($dte);
                break;

            case 'manual_review':
                $dte->marcarEnRevision('Requiere revisión manual: ' . ($errorData['descripcionMsg'] ?? 'Error crítico'));
                $this->notifyManualReview($dte, $errorData);
                break;

            case 'schedule_retry':
                $this->scheduleRetry($dte);
                break;

            case 'validation_review':
                $dte->marcarEnRevision('Error de validación: ' . ($errorData['descripcionMsg'] ?? 'Datos inválidos'));
                break;
        }
    }

    /**
     * Crea una contingencia automática
     */
    private function createAutomaticContingency(Dte $dte): void
    {
        try {
            // Verificar si ya existe una contingencia activa para la empresa
            $contingenciaActiva = Contingencia::buscarActivaPorEmpresa($dte->company_id);

            if (!$contingenciaActiva) {
                // Crear nueva contingencia
                $motivo = "Fallas múltiples en el envío de documentos electrónicos. " .
                         "Documento que desencadenó: {$dte->id_doc}";

                $contingencia = Contingencia::crearAutomatica(
                    $dte->company_id,
                    Contingencia::TIPO_NO_DISPONIBILIDAD_MH,
                    $motivo
                );

                Log::info("Contingencia automática creada", [
                    'contingencia_id' => $contingencia->id,
                    'empresa_id' => $dte->company_id,
                    'dte_id' => $dte->id
                ]);
            } else {
                $contingencia = $contingenciaActiva;
            }

            // Asignar el documento a la contingencia
            $dte->update(['idContingencia' => $contingencia->id]);

        } catch (Exception $e) {
            Log::error("Error creando contingencia automática", [
                'dte_id' => $dte->id,
                'empresa_id' => $dte->company_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Programa un reintento
     */
    private function scheduleRetry(Dte $dte): void
    {
        $delay = $this->calculateRetryDelay($dte->nSends);

        // Marcar el documento para reintento
        $dte->update([
            'codEstado' => Dte::ESTADO_EN_COLA,
            'Estado' => 'Programado para reintento',
            'updated_at' => now()->addMinutes($delay)
        ]);

        Log::info("Documento programado para reintento", [
            'dte_id' => $dte->id,
            'intento' => $dte->nSends + 1,
            'delay_minutes' => $delay
        ]);
    }

    /**
     * Calcula el delay para reintentos con backoff exponencial
     */
    private function calculateRetryDelay(int $attempt): int
    {
        return min(30 * pow(2, $attempt), 1440); // Máximo 24 horas
    }

    /**
     * Actualiza el estado del documento
     */
    private function updateDocumentStatus(Dte $dte, string $errorType, array $errorData): void
    {
        $dte->marcarComoRechazado($errorData);

        // Agregar información adicional del tipo de error
        $dte->update([
            'detailsMessage' => ($dte->detailsMessage ?? '') .
                               "\n[{$errorType}] " . now()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log detallado del error
     */
    private function logError(Dte $dte, array $errorData, string $errorType, string $severity, Exception $exception = null): void
    {
        $logData = [
            'dte_id' => $dte->id,
            'company_id' => $dte->company_id,
            'document_type' => $dte->tipoDte,
            'error_type' => $errorType,
            'severity' => $severity,
            'attempt' => $dte->nSends + 1,
            'error_data' => $errorData,
            'exception' => $exception ? $exception->getMessage() : null,
            'stack_trace' => $exception ? $exception->getTraceAsString() : null
        ];

        $level = match($severity) {
            self::SEVERITY_CRITICAL => 'critical',
            self::SEVERITY_HIGH => 'error',
            self::SEVERITY_MEDIUM => 'warning',
            default => 'info'
        };

        Log::channel('electronic_invoice')->{$level}(
            "Error en documento electrónico: {$errorType}",
            $logData
        );
    }

    /**
     * Incrementa contadores de errores para análisis
     */
    private function incrementErrorCounters(int $companyId, string $errorType): void
    {
        $cacheKey = "error_count:{$companyId}:{$errorType}:" . now()->format('Y-m-d-H');
        Cache::increment($cacheKey, 1);
        Cache::expire($cacheKey, 3600 * 24 * 7); // 7 días
    }

    /**
     * Verifica umbrales de alerta
     */
    private function checkAlertThresholds(int $companyId, string $errorType): void
    {
        $hourlyCount = Cache::get("error_count:{$companyId}:{$errorType}:" . now()->format('Y-m-d-H'), 0);

        // Umbrales por tipo de error
        $thresholds = [
            self::ERROR_CONNECTION => 5,
            self::ERROR_AUTHENTICATION => 1,
            self::ERROR_VALIDATION => 3,
            self::ERROR_SERVER => 3,
        ];

        $threshold = $thresholds[$errorType] ?? 10;

        if ($hourlyCount >= $threshold) {
            $this->triggerAlert($companyId, $errorType, $hourlyCount);
        }
    }

    /**
     * Dispara alertas cuando se superan umbrales
     */
    private function triggerAlert(int $companyId, string $errorType, int $count): void
    {
        // Evitar spam de alertas
        $alertKey = "alert_sent:{$companyId}:{$errorType}:" . now()->format('Y-m-d-H');

        if (Cache::has($alertKey)) {
            return;
        }

        Log::alert("Umbral de errores superado", [
            'company_id' => $companyId,
            'error_type' => $errorType,
            'count' => $count,
            'hour' => now()->format('Y-m-d H:00')
        ]);

        // Marcar que ya se envió la alerta
        Cache::put($alertKey, true, 3600);

        // Aquí se pueden agregar notificaciones adicionales
        // Como emails, Slack, etc.
    }

    /**
     * Notifica cuando un documento necesita revisión manual
     */
    private function notifyManualReview(Dte $dte, array $errorData): void
    {
        Log::warning("Documento requiere revisión manual", [
            'dte_id' => $dte->id,
            'company_id' => $dte->company_id,
            'error_data' => $errorData
        ]);

        // Aquí se pueden agregar notificaciones específicas
        // para documentos que requieren intervención manual
    }

    /**
     * Verifica si un código de error es de validación
     */
    private function isValidationError(string $codigoMsg): bool
    {
        $validationCodes = [
            '50', '51', '52', '53', '54', '55', // Códigos de validación comunes
            '60', '61', '62', '63', '64', '65'  // Códigos de formato
        ];

        return in_array($codigoMsg, $validationCodes);
    }

    /**
     * Obtiene estadísticas de errores para una empresa
     */
    public function getErrorStats(int $companyId, int $days = 7): array
    {
        $stats = [
            'total_errors' => 0,
            'by_type' => [],
            'by_day' => [],
            'critical_count' => 0,
            'avg_daily' => 0
        ];

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i);
            $dayKey = $date->format('Y-m-d');

            $dailyErrors = Dte::where('company_id', $companyId)
                ->whereDate('updated_at', $date)
                ->fallidos()
                ->count();

            $stats['by_day'][$dayKey] = $dailyErrors;
            $stats['total_errors'] += $dailyErrors;
        }

        $stats['avg_daily'] = round($stats['total_errors'] / $days, 2);

        return $stats;
    }

    /**
     * Obtiene documentos que necesitan atención urgente
     */
    public function getUrgentDocuments(int $companyId = null): array
    {
        $query = Dte::fallidos()
            ->with(['sale', 'company'])
            ->where('updated_at', '<=', now()->subHours(2));

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->orderBy('updated_at')
            ->limit(50)
            ->get()
            ->toArray();
    }
}
