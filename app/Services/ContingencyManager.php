<?php

namespace App\Services;

use App\Models\Dte;
use App\Models\Contingencia;
use App\Models\Sale;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Exception;

class ContingencyManager
{
    private ElectronicInvoiceErrorHandler $errorHandler;

    public function __construct(ElectronicInvoiceErrorHandler $errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }

    /**
     * Identifica documentos que necesitan contingencia automática
     */
    public function identifyDocumentsNeedingContingency(): Collection
    {
        return Dte::necesitanContingencia()
            ->with(['sale', 'company'])
            ->orderBy('company_id')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Procesa todas las contingencias automáticas pendientes
     */
    public function processAutomaticContingencies(): array
    {
        $results = [
            'processed' => 0,
            'errors' => 0,
            'contingencies_created' => 0,
            'documents_assigned' => 0
        ];

        $documentsByCompany = $this->identifyDocumentsNeedingContingency()
            ->groupBy('company_id');

        foreach ($documentsByCompany as $companyId => $documents) {
            try {
                $result = $this->processCompanyContingency($companyId, $documents);

                $results['processed'] += $result['processed'];
                $results['documents_assigned'] += $result['documents_assigned'];

                if ($result['contingency_created']) {
                    $results['contingencies_created']++;
                }

            } catch (Exception $e) {
                $results['errors']++;
                Log::error("Error procesando contingencias para empresa {$companyId}", [
                    'error' => $e->getMessage(),
                    'documents_count' => $documents->count()
                ]);
            }
        }

        return $results;
    }

    /**
     * Procesa contingencias para una empresa específica
     */
    private function processCompanyContingency(int $companyId, Collection $documents): array
    {
        $result = [
            'processed' => 0,
            'documents_assigned' => 0,
            'contingency_created' => false
        ];

        // Buscar contingencia activa existente
        $contingenciaActiva = Contingencia::buscarActivaPorEmpresa($companyId);

        if (!$contingenciaActiva) {
            // Crear nueva contingencia
            $contingenciaActiva = $this->createSmartContingency($companyId, $documents);
            $result['contingency_created'] = true;
        }

        // Asignar documentos a la contingencia
        foreach ($documents as $dte) {
            try {
                $dte->update(['idContingencia' => $contingenciaActiva->id]);
                $result['documents_assigned']++;
                $result['processed']++;

                Log::info("Documento asignado a contingencia automática", [
                    'dte_id' => $dte->id,
                    'contingencia_id' => $contingenciaActiva->id,
                    'company_id' => $companyId
                ]);

            } catch (Exception $e) {
                Log::error("Error asignando documento a contingencia", [
                    'dte_id' => $dte->id,
                    'contingencia_id' => $contingenciaActiva->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $result;
    }

    /**
     * Crea una contingencia inteligente basada en el análisis de errores
     */
    private function createSmartContingency(int $companyId, Collection $documents): Contingencia
    {
        $analysis = $this->analyzeDocumentErrors($documents);

        $tipoContingencia = $this->determineBestContingencyType($analysis);
        $motivo = $this->generateContingencyReason($analysis, $documents->count());

        return Contingencia::crearAutomatica($companyId, $tipoContingencia, $motivo);
    }

    /**
     * Analiza los errores de los documentos para determinar el tipo de contingencia
     */
    private function analyzeDocumentErrors(Collection $documents): array
    {
        $analysis = [
            'connection_errors' => 0,
            'server_errors' => 0,
            'auth_errors' => 0,
            'validation_errors' => 0,
            'timeout_errors' => 0,
            'unknown_errors' => 0,
            'total_documents' => $documents->count(),
            'error_pattern' => null,
            'time_span' => null
        ];

        $firstError = $documents->first();
        $lastError = $documents->last();

        if ($firstError && $lastError) {
            $analysis['time_span'] = $firstError->created_at->diffInMinutes($lastError->created_at);
        }

        foreach ($documents as $dte) {
            $errorType = $this->classifyDocumentError($dte);

            switch ($errorType) {
                case 'connection':
                    $analysis['connection_errors']++;
                    break;
                case 'server':
                    $analysis['server_errors']++;
                    break;
                case 'auth':
                    $analysis['auth_errors']++;
                    break;
                case 'validation':
                    $analysis['validation_errors']++;
                    break;
                case 'timeout':
                    $analysis['timeout_errors']++;
                    break;
                default:
                    $analysis['unknown_errors']++;
                    break;
            }
        }

        // Determinar patrón dominante
        $errorCounts = [
            'connection' => $analysis['connection_errors'],
            'server' => $analysis['server_errors'],
            'auth' => $analysis['auth_errors'],
            'validation' => $analysis['validation_errors'],
            'timeout' => $analysis['timeout_errors']
        ];

        $analysis['error_pattern'] = array_search(max($errorCounts), $errorCounts);

        return $analysis;
    }

    /**
     * Clasifica el error de un documento específico
     */
    private function classifyDocumentError(Dte $dte): string
    {
        $description = strtolower($dte->descriptionMessage ?? '');
        $details = strtolower($dte->detailsMessage ?? '');
        $combined = $description . ' ' . $details;

        if (strpos($combined, 'connection') !== false ||
            strpos($combined, 'conexi') !== false ||
            strpos($combined, 'curl') !== false) {
            return 'connection';
        }

        if (strpos($combined, 'server') !== false ||
            strpos($combined, 'servidor') !== false ||
            strpos($combined, '5') === 0) {
            return 'server';
        }

        if (strpos($combined, 'auth') !== false ||
            strpos($combined, 'token') !== false ||
            strpos($combined, 'credencial') !== false) {
            return 'auth';
        }

        if (strpos($combined, 'validaci') !== false ||
            strpos($combined, 'formato') !== false ||
            strpos($combined, 'schema') !== false) {
            return 'validation';
        }

        if (strpos($combined, 'timeout') !== false ||
            strpos($combined, 'tiempo') !== false) {
            return 'timeout';
        }

        return 'unknown';
    }

    /**
     * Determina el mejor tipo de contingencia basado en el análisis
     */
    private function determineBestContingencyType(array $analysis): int
    {
        $pattern = $analysis['error_pattern'];
        $totalDocuments = $analysis['total_documents'];

        // Si hay muchos errores de conexión/servidor, es problema del MH
        if (($analysis['connection_errors'] + $analysis['server_errors']) / $totalDocuments > 0.7) {
            return Contingencia::TIPO_NO_DISPONIBILIDAD_MH;
        }

        // Si hay problemas de internet/timeout
        if ($analysis['timeout_errors'] / $totalDocuments > 0.5) {
            return Contingencia::TIPO_FALLA_INTERNET;
        }

        // Si los errores se concentraron en poco tiempo, probablemente es MH
        if (isset($analysis['time_span']) && $analysis['time_span'] < 60) {
            return Contingencia::TIPO_NO_DISPONIBILIDAD_MH;
        }

        // Por defecto, asumir problema del MH
        return Contingencia::TIPO_NO_DISPONIBILIDAD_MH;
    }

    /**
     * Genera una razón detallada para la contingencia
     */
    private function generateContingencyReason(array $analysis, int $documentCount): string
    {
        $pattern = $analysis['error_pattern'];
        $timeSpan = $analysis['time_span'] ?? 0;

        $reason = "Contingencia automática generada por fallas múltiples. ";
        $reason .= "Documentos afectados: {$documentCount}. ";

        if ($timeSpan > 0) {
            $hours = round($timeSpan / 60, 1);
            $reason .= "Período de errores: {$hours} horas. ";
        }

        $reason .= "Patrón de errores detectado: ";

        switch ($pattern) {
            case 'connection':
                $reason .= "Problemas de conectividad ({$analysis['connection_errors']} documentos). ";
                break;
            case 'server':
                $reason .= "Errores del servidor de Hacienda ({$analysis['server_errors']} documentos). ";
                break;
            case 'timeout':
                $reason .= "Timeouts de conexión ({$analysis['timeout_errors']} documentos). ";
                break;
            case 'auth':
                $reason .= "Problemas de autenticación ({$analysis['auth_errors']} documentos). ";
                break;
            case 'validation':
                $reason .= "Errores de validación ({$analysis['validation_errors']} documentos). ";
                break;
            default:
                $reason .= "Errores diversos. ";
                break;
        }

        $reason .= "Sistema automático activado para evitar pérdida de documentos.";

        return $reason;
    }

    /**
     * Obtiene contingencias que están próximas a vencer
     */
    public function getExpiringContingencies(int $days = 1): Collection
    {
        return Contingencia::proximasAVencer($days)
            ->with(['empresa', 'documentos'])
            ->get();
    }

    /**
     * Obtiene contingencias vencidas que no fueron enviadas
     */
    public function getExpiredContingencies(): Collection
    {
        return Contingencia::vencidas()
            ->with(['empresa', 'documentos'])
            ->get();
    }

    /**
     * Envía alertas para contingencias próximas a vencer
     */
    public function sendExpirationAlerts(): array
    {
        $results = [
            'alerts_sent' => 0,
            'errors' => 0
        ];

        $expiringContingencies = $this->getExpiringContingencies();

        foreach ($expiringContingencies as $contingencia) {
            try {
                $this->sendContingencyAlert($contingencia, 'expiring');
                $results['alerts_sent']++;
            } catch (Exception $e) {
                $results['errors']++;
                Log::error("Error enviando alerta de contingencia", [
                    'contingencia_id' => $contingencia->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Procesa contingencias vencidas
     */
    public function processExpiredContingencies(): array
    {
        $results = [
            'processed' => 0,
            'errors' => 0
        ];

        $expiredContingencies = $this->getExpiredContingencies();

        foreach ($expiredContingencies as $contingencia) {
            try {
                $this->handleExpiredContingency($contingencia);
                $results['processed']++;
            } catch (Exception $e) {
                $results['errors']++;
                Log::error("Error procesando contingencia vencida", [
                    'contingencia_id' => $contingencia->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Maneja una contingencia vencida
     */
    private function handleExpiredContingency(Contingencia $contingencia): void
    {
        $contingencia->marcarEnRevision('Contingencia vencida sin procesar');

        // Notificar sobre la contingencia vencida
        $this->sendContingencyAlert($contingencia, 'expired');

        // Si hay documentos asignados, crear nueva contingencia
        if ($contingencia->contador_documentos > 0) {
            $this->extendOrCreateNewContingency($contingencia);
        }
    }

    /**
     * Extiende una contingencia o crea una nueva
     */
    private function extendOrCreateNewContingency(Contingencia $contingenciaVencida): void
    {
        $nuevaContingencia = Contingencia::crearAutomatica(
            $contingenciaVencida->idEmpresa,
            $contingenciaVencida->tipoContingencia,
            "Extensión automática de contingencia vencida ID: {$contingenciaVencida->id}"
        );

        // Transferir documentos no procesados
        $documentosNoEnviados = Dte::where('idContingencia', $contingenciaVencida->id)
            ->fallidos()
            ->get();

        foreach ($documentosNoEnviados as $dte) {
            $dte->update(['idContingencia' => $nuevaContingencia->id]);
        }

        Log::info("Contingencia extendida automáticamente", [
            'contingencia_vencida' => $contingenciaVencida->id,
            'nueva_contingencia' => $nuevaContingencia->id,
            'documentos_transferidos' => $documentosNoEnviados->count()
        ]);
    }

    /**
     * Envía alertas de contingencias
     */
    private function sendContingencyAlert(Contingencia $contingencia, string $type): void
    {
        $alertData = [
            'contingencia_id' => $contingencia->id,
            'empresa_id' => $contingencia->idEmpresa,
            'tipo' => $type,
            'documentos_count' => $contingencia->contador_documentos,
            'fecha_vencimiento' => $contingencia->fFin,
            'dias_restantes' => $contingencia->dias_restantes
        ];

        $message = match($type) {
            'expiring' => "Contingencia próxima a vencer en {$contingencia->dias_restantes} días",
            'expired' => "Contingencia vencida sin procesar",
            default => "Alerta de contingencia"
        };

        Log::warning($message, $alertData);

        // Aquí se pueden agregar notificaciones adicionales
        // Como emails, SMS, Slack, etc.
    }

    /**
     * Obtiene estadísticas de contingencias
     */
    public function getContingencyStats(int $companyId = null, int $days = 30): array
    {
        $query = Contingencia::query();

        if ($companyId) {
            $query->where('idEmpresa', $companyId);
        }

        $query->where('created_at', '>=', now()->subDays($days));

        $contingencias = $query->get();

        return [
            'total' => $contingencias->count(),
            'activas' => $contingencias->where('codEstado', Contingencia::ESTADO_EN_COLA)->count(),
            'enviadas' => $contingencias->where('codEstado', Contingencia::ESTADO_ENVIADO)->count(),
            'rechazadas' => $contingencias->where('codEstado', Contingencia::ESTADO_RECHAZADO)->count(),
            'vencidas' => $contingencias->filter(fn($c) => $c->isVencida())->count(),
            'documentos_total' => $contingencias->sum('contador_documentos'),
            'promedio_documentos' => $contingencias->count() > 0 ?
                round($contingencias->sum('contador_documentos') / $contingencias->count(), 2) : 0
        ];
    }

    /**
     * Verifica el estado de salud del sistema de contingencias
     */
    public function getHealthStatus(): array
    {
        return [
            'contingencias_pendientes' => Contingencia::enCola()->count(),
            'contingencias_vencidas' => Contingencia::vencidas()->count(),
            'documentos_sin_contingencia' => Dte::necesitanContingencia()->count(),
            'documentos_urgentes' => Dte::fallidos()
                ->where('updated_at', '<=', now()->subHours(4))
                ->count(),
            'ultimo_procesamiento' => cache('contingency_last_run', 'Nunca'),
            'status' => $this->determineOverallHealth()
        ];
    }

    /**
     * Determina el estado general de salud del sistema
     */
    private function determineOverallHealth(): string
    {
        $urgentDocs = Dte::fallidos()
            ->where('updated_at', '<=', now()->subHours(4))
            ->count();

        $expiredContingencies = Contingencia::vencidas()->count();
        $docsNeedingContingency = Dte::necesitanContingencia()->count();

        if ($urgentDocs > 50 || $expiredContingencies > 5) {
            return 'critical';
        }

        if ($urgentDocs > 20 || $docsNeedingContingency > 10) {
            return 'warning';
        }

        if ($urgentDocs > 5 || $docsNeedingContingency > 0) {
            return 'attention';
        }

        return 'healthy';
    }
}
