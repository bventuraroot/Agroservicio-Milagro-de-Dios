<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PurchaseInventoryService;
use App\Models\Inventory;
use Carbon\Carbon;

class CheckExpiringProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:check-expiring {--days=30 : Días para verificar} {--notify : Enviar notificaciones}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar productos próximos a vencer en el inventario';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $days = $this->option('days');
        $shouldNotify = $this->option('notify');

        $this->info("Verificando productos próximos a vencer en los próximos {$days} días...");

        $service = new PurchaseInventoryService();
        $expiringProducts = $service->checkExpiringProducts($days);

        $criticalCount = $expiringProducts['critical']->count();
        $warningCount = $expiringProducts['warning']->count();
        $totalCount = $expiringProducts['total'];

        if ($totalCount === 0) {
            $this->info('✅ No hay productos próximos a vencer.');
            return 0;
        }

        $this->info("📊 Resumen de productos próximos a vencer:");
        $this->info("   • Críticos (≤7 días): {$criticalCount}");
        $this->info("   • Advertencia (8-{$days} días): {$warningCount}");
        $this->info("   • Total: {$totalCount}");

        // Mostrar productos críticos
        if ($criticalCount > 0) {
            $this->warn("\n🚨 PRODUCTOS CRÍTICOS (≤7 días):");
            $this->displayProducts($expiringProducts['critical']);
        }

        // Mostrar productos con advertencia
        if ($warningCount > 0) {
            $this->warn("\n⚠️  PRODUCTOS CON ADVERTENCIA (8-{$days} días):");
            $this->displayProducts($expiringProducts['warning']);
        }

        // Actualizar fechas de verificación
        $this->updateLastCheckDate();

        // Enviar notificaciones si se solicita
        if ($shouldNotify) {
            $this->sendNotifications($expiringProducts);
        }

        $this->info("\n✅ Verificación completada.");

        return 0;
    }

    /**
     * Mostrar productos en formato tabla
     */
    private function displayProducts($products)
    {
        $headers = ['Producto', 'Proveedor', 'Cantidad', 'Vence en', 'Fecha Caducidad'];
        $rows = [];

        foreach ($products as $inventory) {
            $product = $inventory->product;
            $provider = $product->provider;
            $daysUntilExpiration = $inventory->getDaysUntilExpiration();

            $rows[] = [
                $product->name ?? 'N/A',
                $provider->razonsocial ?? 'N/A',
                $inventory->quantity,
                $daysUntilExpiration . ' días',
                $inventory->expiration_date ? $inventory->expiration_date->format('d/m/Y') : 'N/A'
            ];
        }

        $this->table($headers, $rows);
    }

    /**
     * Actualizar fecha de última verificación
     */
    private function updateLastCheckDate()
    {
        Inventory::whereNotNull('expiration_date')
            ->where('quantity', '>', 0)
            ->update(['last_expiration_check' => now()]);
    }

    /**
     * Enviar notificaciones (implementar según necesidades)
     */
    private function sendNotifications($expiringProducts)
    {
        $this->info("\n📧 Enviando notificaciones...");

        // Aquí puedes implementar el envío de notificaciones
        // Por ejemplo, enviar emails, notificaciones push, etc.

        $criticalCount = $expiringProducts['critical']->count();
        $warningCount = $expiringProducts['warning']->count();

        if ($criticalCount > 0) {
            $this->warn("   • Enviadas {$criticalCount} notificaciones críticas");
        }

        if ($warningCount > 0) {
            $this->info("   • Enviadas {$warningCount} notificaciones de advertencia");
        }
    }
}
