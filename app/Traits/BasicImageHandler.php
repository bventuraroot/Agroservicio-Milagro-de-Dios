<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait BasicImageHandler
{
    /**
     * Guardar imagen de forma básica y confiable
     */
    public function storeImage(UploadedFile $file, string $disk = 'public', array $options = [])
    {
        $defaultOptions = [
            'path' => 'images',
            'filename' => null,
            'maxSize' => 5 * 1024 * 1024 // 5MB
        ];

        $options = array_merge($defaultOptions, $options);

        try {
            // Validar archivo
            $this->validateBasicImage($file, $options['maxSize']);

            // Generar nombre único
            $filename = $options['filename'] ?: time() . '_' . Str::random(10);
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $fullFilename = $filename . '.' . $extension;

            // Ruta completa
            $filePath = $options['path'] . '/' . $fullFilename;

            // Guardar archivo
            Storage::disk($disk)->put($filePath, file_get_contents($file));

            return [
                'main' => $filePath,
                'filename' => $fullFilename,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ];

        } catch (\Exception $e) {
            Log::error('Error guardando imagen: ' . $e->getMessage());
            throw new \Exception('Error al guardar la imagen: ' . $e->getMessage());
        }
    }

    /**
     * Validación básica de imagen
     */
    public function validateBasicImage(UploadedFile $file, int $maxSize = 5242880)
    {
        // Validar que sea un archivo
        if (!$file->isValid()) {
            throw new \Exception('El archivo no es válido');
        }

        // Validar tamaño
        if ($file->getSize() > $maxSize) {
            $maxSizeMB = round($maxSize / 1024 / 1024, 1);
            throw new \Exception("El archivo es muy grande. Máximo {$maxSizeMB}MB");
        }

        // Validar tipo MIME
        $allowedMimes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp'
        ];

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('Tipo de archivo no permitido. Solo se permiten imágenes.');
        }

        // Validar extensión
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions)) {
            throw new \Exception('Extensión de archivo no permitida.');
        }

        return true;
    }

    /**
     * Eliminar imagen
     */
    public function deleteImage(string $path, string $disk = 'public')
    {
        try {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error('Error eliminando imagen: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener URL de imagen
     */
    public function getImageUrl(string $path, string $disk = 'public', string $fallback = null)
    {
        try {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->url($path);
            }
        } catch (\Exception $e) {
            Log::error('Error obteniendo URL de imagen: ' . $e->getMessage());
        }

        return $fallback ?: asset('images/placeholder.jpg');
    }

    /**
     * Verificar si existe la imagen
     */
    public function imageExists(string $path, string $disk = 'public')
    {
        try {
            return Storage::disk($disk)->exists($path);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtener información de la imagen
     */
    public function getImageInfo(string $path, string $disk = 'public')
    {
        try {
            if (Storage::disk($disk)->exists($path)) {
                $size = Storage::disk($disk)->size($path);
                $lastModified = Storage::disk($disk)->lastModified($path);

                return [
                    'exists' => true,
                    'size' => $size,
                    'size_formatted' => $this->formatBytes($size),
                    'last_modified' => $lastModified,
                    'last_modified_formatted' => date('Y-m-d H:i:s', $lastModified)
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error obteniendo información de imagen: ' . $e->getMessage());
        }

        return ['exists' => false];
    }

    /**
     * Formatear bytes a formato legible
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
