<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Exception;

class DatabaseQueryService
{
    /**
     * Obtener estadísticas generales del sistema
     */
    public function getSystemStats()
    {
        try {
            $stats = [];
            
            // Verificar qué tablas existen
            $tables = Schema::getAllTables();
            $tableNames = $tables ? array_column($tables, 'name') : [];
            
            // Estadísticas de usuarios
            if (in_array('users', $tableNames)) {
                $stats['users'] = [
                    'total' => DB::table('users')->count(),
                    'activos' => DB::table('users')->where('status', 'active')->count(),
                ];
            }
            
            // Estadísticas de productos
            if (in_array('products', $tableNames)) {
                $stats['products'] = [
                    'total' => DB::table('products')->count(),
                    'activos' => DB::table('products')->where('state', 1)->count(),
                    'inactivos' => DB::table('products')->where('state', 0)->count(),
                ];
            }
            
            // Estadísticas de ventas
            if (in_array('sales', $tableNames)) {
                $stats['sales'] = [
                    'total' => DB::table('sales')->count(),
                    'hoy' => DB::table('sales')->whereDate('created_at', today())->count(),
                    'mes' => DB::table('sales')->whereMonth('created_at', now()->month)->count(),
                ];
            }
            
            // Estadísticas de cotizaciones
            if (in_array('quotations', $tableNames)) {
                $stats['quotations'] = [
                    'total' => DB::table('quotations')->count(),
                    'pendientes' => DB::table('quotations')->where('status', 'pending')->count(),
                    'aprobadas' => DB::table('quotations')->where('status', 'approved')->count(),
                ];
            }
            
            return $stats;
            
        } catch (Exception $e) {
            Log::error('Error obteniendo estadísticas del sistema', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Buscar productos por nombre o descripción
     */
    public function searchProducts($query, $limit = 10)
    {
        try {
            if (!Schema::hasTable('products')) {
                return [];
            }
            
            return DB::table('products')
                ->where('name', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->select('id', 'name', 'description', 'price', 'state', 'category')
                ->limit($limit)
                ->get()
                ->toArray();
                
        } catch (Exception $e) {
            Log::error('Error buscando productos', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Obtener ventas recientes
     */
    public function getRecentSales($limit = 5)
    {
        try {
            if (!Schema::hasTable('sales')) {
                return [];
            }
            
            return DB::table('sales')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->toArray();
                
        } catch (Exception $e) {
            Log::error('Error obteniendo ventas recientes', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Obtener productos inactivos
     */
    public function getInactiveProducts($limit = 10)
    {
        try {
            if (!Schema::hasTable('products')) {
                return [];
            }
            
            return DB::table('products')
                ->where('state', 0)
                ->select('id', 'name', 'price', 'category')
                ->orderBy('name', 'asc')
                ->get()
                ->toArray();
                
        } catch (Exception $e) {
            Log::error('Error obteniendo productos inactivos', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Obtener cotizaciones pendientes
     */
    public function getPendingQuotations($limit = 10)
    {
        try {
            if (!Schema::hasTable('quotations')) {
                return [];
            }
            
            return DB::table('quotations')
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->toArray();
                
        } catch (Exception $e) {
            Log::error('Error obteniendo cotizaciones pendientes', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Obtener información de un usuario específico
     */
    public function getUserInfo($userId)
    {
        try {
            if (!Schema::hasTable('users')) {
                return null;
            }
            
            return DB::table('users')
                ->where('id', $userId)
                ->select('id', 'name', 'email', 'created_at')
                ->first();
                
        } catch (Exception $e) {
            Log::error('Error obteniendo información del usuario', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Ejecutar consulta personalizada de forma segura
     */
    public function executeSafeQuery($table, $conditions = [], $select = ['*'], $limit = 50)
    {
        try {
            // Validar que la tabla existe
            if (!Schema::hasTable($table)) {
                return [];
            }
            
            // Lista de tablas permitidas para consultas
            $allowedTables = ['users', 'products', 'sales', 'quotations', 'categories', 'providers'];
            
            if (!in_array($table, $allowedTables)) {
                Log::warning('Intento de consulta en tabla no permitida', ['table' => $table]);
                return [];
            }
            
            $query = DB::table($table)->select($select);
            
            // Aplicar condiciones de forma segura
            foreach ($conditions as $condition) {
                if (isset($condition['column']) && isset($condition['operator']) && isset($condition['value'])) {
                    $query->where($condition['column'], $condition['operator'], $condition['value']);
                }
            }
            
            return $query->limit($limit)->get()->toArray();
            
        } catch (Exception $e) {
            Log::error('Error ejecutando consulta segura', [
                'table' => $table,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
