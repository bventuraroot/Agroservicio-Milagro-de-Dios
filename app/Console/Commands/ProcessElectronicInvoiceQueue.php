<?php

namespace App\Console\Commands;

use App\Models\Dte;
use App\Services\DocumentRetryService;
use App\Services\ContingencyManager;
use App\Services\ElectronicInvoiceErrorHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessElectronicInvoiceQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:process-queue
                            {--retries : Solo procesar reintentos}
                            {--contingencies : Solo procesar contingencias}
                            {--all : Procesar todo (reintentos + contingencias)}
                            {--limit=50 : Límite de documentos a procesar}
                            {--company= : ID de empresa específica}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa la cola de documentos electrónicos pendientes, reintentos y contingencias';

    private DocumentRetryService $retryService;
    private ContingencyManager $contingencyManager;
    private ElectronicInvoiceErrorHandler $errorHandler;

    /**
     * Create a new command instance.
     */
    public function __construct(
        DocumentRetryService $retryService,
        ContingencyManager $contingencyManager,
        ElectronicInvoiceErrorHandler $errorHandler
    ) {
        parent::__construct();
        $this->retryService = $retryService;
        $this->contingencyManager = $contingencyManager;
        $this->errorHandler = $errorHandler;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Iniciando procesamiento de cola de facturación electrónica');

        $startTime = microtime(true);
        $companyId = $this->option('company');

        try {
            $results = [];

            // Determinar qué procesar
            if ($this->option('retries') || $this->option('all')) {
                $results['retries'] = $this->processRetries($companyId);
            }

            if ($this->option('contingencies') || $this->option('all')) {
                $results['contingencies'] = $this->processContingencies($companyId);
            }

            // Si no se especifica nada, procesar todo por defecto
            if (!$this->option('retries') && !$this->option('contingencies') && !$this->option('all')) {
                $results['retries'] = $this->processRetries($companyId);
                $results['contingencies'] = $this->processContingencies($companyId);
            }

            $this->displayResults($results, microtime(true) - $startTime);

            return self::SUCCESS;

        } catch (Exception $e) {
            $this->error("❌ Error procesando cola: {$e->getMessage()}");
            Log::error('Error en comando ProcessElectronicInvoiceQueue', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Procesa reintentos de documentos
     */
    private function processRetries(?string $companyId): array
    {
        $this->info('📄 Procesando reintentos de documentos...');

        if ($companyId) {
            $documentsToRetry = Dte::paraReintento()
                ->where('company_id', $companyId)
                ->limit($this->option('limit'))
                ->get();

            $results = [
                'processed' => 0,
                'successful' => 0,
                'failed' => 0,
                'skipped' => 0,
                'errors' => 0
            ];

            foreach ($documentsToRetry as $dte) {
                $result = $this->retryService->retryDocument($dte);
                $results['processed']++;
                $results[$result['status']]++;
            }
        } else {
            $results = $this->retryService->processRetries();
        }

        $this->line("   ✅ Exitosos: {$results['successful']}");
        $this->line("   ❌ Fallidos: {$results['failed']}");
        $this->line("   ⏭️  Omitidos: {$results['skipped']}");

        if ($results['errors'] > 0) {
            $this->warn("   ⚠️  Errores: {$results['errors']}");
        }

        return $results;
    }

    /**
     * Procesa contingencias automáticas
     */
    private function processContingencies(?string $companyId): array
    {
        $this->info('🆘 Procesando contingencias automáticas...');

        $results = $this->contingencyManager->processAutomaticContingencies();

        if ($companyId) {
            // Filtrar resultados por empresa si se especifica
            $this->info("   🏢 Procesando solo empresa ID: {$companyId}");
        }

        $this->line("   📋 Documentos procesados: {$results['processed']}");
        $this->line("   🆕 Contingencias creadas: {$results['contingencies_created']}");
        $this->line("   📄 Documentos asignados: {$results['documents_assigned']}");

        if ($results['errors'] > 0) {
            $this->warn("   ⚠️  Errores: {$results['errors']}");
        }

        // Procesar contingencias vencidas
        $expiredResults = $this->contingencyManager->processExpiredContingencies();
        if ($expiredResults['processed'] > 0) {
            $this->line("   🕐 Contingencias vencidas procesadas: {$expiredResults['processed']}");
        }

        // Enviar alertas de contingencias próximas a vencer
        $alertResults = $this->contingencyManager->sendExpirationAlerts();
        if ($alertResults['alerts_sent'] > 0) {
            $this->line("   📧 Alertas enviadas: {$alertResults['alerts_sent']}");
        }

        return array_merge($results, [
            'expired_processed' => $expiredResults['processed'],
            'alerts_sent' => $alertResults['alerts_sent']
        ]);
    }

    /**
     * Muestra los resultados del procesamiento
     */
    private function displayResults(array $results, float $executionTime): void
    {
        $this->newLine();
        $this->info('📊 Resumen de Procesamiento:');
        $this->table(
            ['Operación', 'Resultado'],
            $this->formatResultsTable($results)
        );

        $this->info("⏱️  Tiempo de ejecución: " . round($executionTime, 2) . " segundos");

        // Mostrar estado de salud general
        $healthStatus = $this->contingencyManager->getHealthStatus();
        $this->displayHealthStatus($healthStatus);
    }

    /**
     * Formatea los resultados para la tabla
     */
    private function formatResultsTable(array $results): array
    {
        $table = [];

        if (isset($results['retries'])) {
            $retry = $results['retries'];
            $table[] = ['Reintentos Procesados', $retry['processed']];
            $table[] = ['Reintentos Exitosos', $retry['successful']];
            $table[] = ['Reintentos Fallidos', $retry['failed']];
        }

        if (isset($results['contingencies'])) {
            $contingency = $results['contingencies'];
            $table[] = ['Contingencias Creadas', $contingency['contingencies_created']];
            $table[] = ['Documentos en Contingencia', $contingency['documents_assigned']];

            if (isset($contingency['expired_processed'])) {
                $table[] = ['Contingencias Vencidas', $contingency['expired_processed']];
            }

            if (isset($contingency['alerts_sent'])) {
                $table[] = ['Alertas Enviadas', $contingency['alerts_sent']];
            }
        }

        return $table;
    }

    /**
     * Muestra el estado de salud del sistema
     */
    private function displayHealthStatus(array $health): void
    {
        $this->newLine();
        $this->info('🏥 Estado de Salud del Sistema:');

        $statusEmoji = match($health['status']) {
            'healthy' => '💚',
            'attention' => '💛',
            'warning' => '🧡',
            'critical' => '❤️',
            default => '⚪'
        };

        $this->line("   Estado general: {$statusEmoji} " . ucfirst($health['status']));
        $this->line("   Contingencias pendientes: {$health['contingencias_pendientes']}");
        $this->line("   Contingencias vencidas: {$health['contingencias_vencidas']}");
        $this->line("   Documentos sin contingencia: {$health['documentos_sin_contingencia']}");
        $this->line("   Documentos urgentes: {$health['documentos_urgentes']}");
        $this->line("   Último procesamiento: {$health['ultimo_procesamiento']}");

        if ($health['status'] === 'critical') {
            $this->error('⚠️  El sistema requiere atención inmediata!');
        } elseif ($health['status'] === 'warning') {
            $this->warn('⚠️  El sistema requiere atención pronto.');
        }
    }
}
