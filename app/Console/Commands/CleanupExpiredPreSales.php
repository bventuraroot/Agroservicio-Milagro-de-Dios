<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\Salesdetail;
use Carbon\Carbon;

class CleanupExpiredPreSales extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'presales:cleanup {--hours=4 : Horas después de las cuales expira una sesión}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar sesiones de pre-ventas expiradas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = $this->option('hours');
        $expirationTime = Carbon::now()->subHours($hours);

        $this->info("Buscando sesiones de pre-ventas expiradas (más de {$hours} horas)...");

        // Buscar sesiones expiradas
        $expiredSessions = Sale::where('typesale', '2') // Borrador
                              ->where('created_at', '<', $expirationTime)
                              ->get();

        if ($expiredSessions->isEmpty()) {
            $this->info('No se encontraron sesiones expiradas.');
            return 0;
        }

        $this->info("Se encontraron {$expiredSessions->count()} sesiones expiradas.");

        $cleanedCount = 0;
        $bar = $this->output->createProgressBar($expiredSessions->count());

        foreach ($expiredSessions as $session) {
            try {
                // Restaurar stock de todos los productos
                $details = Salesdetail::where('sale_id', $session->id)->get();

                foreach ($details as $detail) {
                    $product = $detail->product;
                    if ($product) {
                        $product->stock += $detail->amountp;
                        $product->save();
                    }
                }

                // Eliminar detalles de la sesión
                Salesdetail::where('sale_id', $session->id)->delete();

                // Eliminar la sesión
                $session->delete();

                $cleanedCount++;
                $bar->advance();

            } catch (\Exception $e) {
                $this->error("Error al limpiar sesión #{$session->id}: " . $e->getMessage());
            }
        }

        $bar->finish();
        $this->newLine();

        $this->info("Se limpiaron {$cleanedCount} sesiones expiradas exitosamente.");
        $this->info("Stock restaurado para todos los productos.");

        return 0;
    }
}
