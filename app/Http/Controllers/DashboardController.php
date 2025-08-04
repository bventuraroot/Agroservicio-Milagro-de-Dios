<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Sale;
use App\Models\Salesdetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function home()
    {
        $user = auth()->user();

        // Estadísticas básicas
        $tclientes = Client::count();
        $tproviders = Provider::count();
        $tproducts = Product::count();
        $tsales = Sale::count();

        // Datos para gráficos de ventas - Mejorados para manejar datos vacíos
        $ventasUltimoAno = $this->getVentasUltimoAno();
        $ventasUltimoMes = $this->getVentasUltimoMes();
        $ventasUltimaSemana = $this->getVentasUltimaSemana();
        $productosMasVendidos = $this->getProductosMasVendidos();
        $ventasPorMes = $this->getVentasPorMes();
        $ventasPorDia = $this->getVentasPorDia();

        // Cálculo de totales - Mejorado para manejar valores NULL
        $totalVentas = $this->getTotalVentas();
        $totalVentasMes = $this->getTotalVentasMes();
        $totalVentasSemana = $this->getTotalVentasSemana();

        // Cálculo de porcentajes de crecimiento
        $crecimientoVentas = $this->calcularCrecimientoVentas();
        $crecimientoProductos = $this->calcularCrecimientoProductos();

        // Datos adicionales para mejor organización
        $estadisticasGenerales = [
            'clientes' => $tclientes,
            'proveedores' => $tproviders,
            'productos' => $tproducts,
            'ventas' => $tsales
        ];

        return view('dashboard', compact(
            'tclientes',
            'tproviders',
            'tproducts',
            'tsales',
            'ventasUltimoAno',
            'ventasUltimoMes',
            'ventasUltimaSemana',
            'productosMasVendidos',
            'ventasPorMes',
            'ventasPorDia',
            'totalVentas',
            'totalVentasMes',
            'totalVentasSemana',
            'crecimientoVentas',
            'crecimientoProductos',
            'estadisticasGenerales'
        ));
    }

    private function getTotalVentas()
    {
        $total = \DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->sum('salesdetails.pricesale');
        return $total ?: 0;
    }

    private function getTotalVentasMes()
    {
        $total = \DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->whereMonth('sales.date', Carbon::now()->month)
            ->sum('salesdetails.pricesale');
        return $total ?: 0;
    }

    private function getTotalVentasSemana()
    {
        $total = \DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->whereBetween('sales.date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->sum('salesdetails.pricesale');
        return $total ?: 0;
    }

    private function getVentasUltimoAno()
    {
        $ventas = \DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->whereYear('sales.date', Carbon::now()->year)
            ->selectRaw('MONTH(sales.date) as mes, SUM(salesdetails.pricesale) as total')
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        $meses = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
        ];

        $resultado = [];
        foreach ($meses as $numero => $nombre) {
            $ventaMes = $ventas->where('mes', $numero)->first();
            $resultado[$nombre] = $ventaMes ? (float)$ventaMes->total : 0;
        }
        return array_values($resultado); // Para gráficos tipo array
    }

    private function getVentasUltimoMes()
    {
        $ventas = \DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->whereMonth('sales.date', Carbon::now()->month)
            ->selectRaw('DAY(sales.date) as dia, SUM(salesdetails.pricesale) as total')
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();

        $diasEnMes = Carbon::now()->daysInMonth;
        $resultado = [];
        for ($i = 1; $i <= $diasEnMes; $i++) {
            $ventaDia = $ventas->where('dia', $i)->first();
            $resultado[] = $ventaDia ? (float)$ventaDia->total : 0;
        }
        return $resultado;
    }

    private function getVentasUltimaSemana()
    {
        $ventas = \DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->whereBetween('sales.date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->selectRaw('DAY(sales.date) as dia, SUM(salesdetails.pricesale) as total')
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();

        $resultado = [];
        for ($i = 0; $i < 7; $i++) {
            $dia = Carbon::now()->startOfWeek()->copy()->addDays($i)->day;
            $ventaDia = $ventas->where('dia', $dia)->first();
            $resultado[] = $ventaDia ? (float)$ventaDia->total : 0;
        }
        return $resultado;
    }

    private function getProductosMasVendidos()
    {
        $productos = Salesdetail::join('products', 'salesdetails.product_id', '=', 'products.id')
            ->selectRaw('products.name, SUM(salesdetails.amountp) as cantidad_vendida')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('cantidad_vendida')
            ->limit(5)
            ->get();

        // Si no hay productos vendidos, retornar colección vacía
        return $productos->isEmpty() ? collect() : $productos;
    }

    private function getVentasPorMes()
    {
        $ventas = \DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->whereYear('sales.date', Carbon::now()->year)
            ->selectRaw('MONTH(sales.date) as mes, SUM(salesdetails.pricesale) as total')
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        $meses = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
        ];

        $resultado = [];
        foreach ($meses as $numero => $nombre) {
            $ventaMes = $ventas->where('mes', $numero)->first();
            $resultado[$nombre] = $ventaMes ? (float)$ventaMes->total : 0;
        }
        return $resultado;
    }

    private function getVentasPorDia()
    {
        $ventas = \DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->whereMonth('sales.date', Carbon::now()->month)
            ->selectRaw('DAY(sales.date) as dia, SUM(salesdetails.pricesale) as total')
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();

        $diasEnMes = Carbon::now()->daysInMonth;
        $resultado = [];
        for ($i = 1; $i <= $diasEnMes; $i++) {
            $ventaDia = $ventas->where('dia', $i)->first();
            $resultado[$i] = $ventaDia ? (float)$ventaDia->total : 0;
        }
        return $resultado;
    }

    private function calcularCrecimientoVentas()
    {
        $ventasMesActual = \DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->whereMonth('sales.date', Carbon::now()->month)
            ->sum('salesdetails.pricesale') ?: 0;

        $ventasMesAnterior = \DB::table('sales')
            ->join('salesdetails', 'sales.id', '=', 'salesdetails.sale_id')
            ->where('sales.state', 1)
            ->whereMonth('sales.date', Carbon::now()->subMonth()->month)
            ->sum('salesdetails.pricesale') ?: 0;

        if ($ventasMesAnterior > 0) {
            return round((($ventasMesActual - $ventasMesAnterior) / $ventasMesAnterior) * 100, 1);
        }
        return 0;
    }

    private function calcularCrecimientoProductos()
    {
        $productosMesActual = Product::whereMonth('created_at', Carbon::now()->month)->count();
        $productosMesAnterior = Product::whereMonth('created_at', Carbon::now()->subMonth()->month)->count();

        if ($productosMesAnterior > 0) {
            return round((($productosMesActual - $productosMesAnterior) / $productosMesAnterior) * 100, 1);
        }

        return 0;
    }
}
