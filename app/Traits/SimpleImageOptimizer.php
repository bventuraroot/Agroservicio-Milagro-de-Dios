<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;

trait SimpleImageOptimizer
{
    /**
     * Optimizar y guardar imagen de forma simple
     */
    public function optimizeAndStoreImage(UploadedFile $file, string $disk = 'public', array $options = [])
    {
        $defaultOptions = [
            'maxWidth' => 800,
            'maxHeight' => 600,
            'quality' => 80,
            'format' => 'jpg',
            'path' => 'images',
            'filename' => null
        ];

        $options = array_merge($defaultOptions, $options);

        // Generar nombre único si no se proporciona
        if (!$options['filename']) {
            $options['filename'] = time() . '_' . Str::random(10);
        }

        try {
            // Crear manager de imágenes
            $manager = new ImageManager(new Driver());

            // Leer la imagen
            $image = $manager->read($file);

            // Redimensionar si es necesario
            $width = $image->width();
            $height = $image->height();

            if ($width > $options['maxWidth'] || $height > $options['maxHeight']) {
                $image->resize($options['maxWidth'], $options['maxHeight']);
            }

                        // Guardar imagen
            $filePath = $options['path'] . '/' . $options['filename'] . '.' . $options['format'];

            // Codificar imagen según el formato
            switch ($options['format']) {
                case 'jpg':
                case 'jpeg':
                    $imageData = $image->toJpeg($options['quality']);
                    break;
                case 'png':
                    $imageData = $image->toPng();
                    break;
                case 'webp':
                    $imageData = $image->toWebp($options['quality']);
                    break;
                default:
                    $imageData = $image->toJpeg($options['quality']);
            }

            Storage::disk($disk)->put($filePath, $imageData);

            return [
                'main' => $filePath,
                'filename' => $options['filename'] . '.' . $options['format']
            ];

        } catch (\Exception $e) {
            Log::error('Error optimizando imagen: ' . $e->getMessage());

            // Fallback: guardar imagen original
            $originalName = time() . '_' . $file->getClientOriginalName();
            $filePath = $options['path'] . '/' . $originalName;
            Storage::disk($disk)->put($filePath, file_get_contents($file));

            return [
                'main' => $filePath,
                'filename' => $originalName
            ];
        }
    }

    /**
     * Validar archivo de imagen
     */
    public function validateImage(UploadedFile $file, array $rules = [])
    {
        $defaultRules = [
            'max_size' => 5 * 1024 * 1024, // 5MB
            'allowed_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            'min_width' => 100,
            'min_height' => 100,
            'max_width' => 5000,
            'max_height' => 5000
        ];

        $rules = array_merge($defaultRules, $rules);

        // Validar tamaño
        if ($file->getSize() > $rules['max_size']) {
            throw new \Exception('El archivo es muy grande. Máximo ' . ($rules['max_size'] / 1024 / 1024) . 'MB');
        }

        // Validar tipo
        if (!in_array($file->getMimeType(), $rules['allowed_types'])) {
            throw new \Exception('Tipo de archivo no permitido');
        }

        // Validar dimensiones
        $imageInfo = getimagesize($file->getPathname());
        if ($imageInfo) {
            $width = $imageInfo[0];
            $height = $imageInfo[1];

            if ($width < $rules['min_width'] || $height < $rules['min_height']) {
                throw new \Exception("La imagen es muy pequeña. Mínimo {$rules['min_width']}x{$rules['min_height']}px");
            }

            if ($width > $rules['max_width'] || $height > $rules['max_height']) {
                throw new \Exception("La imagen es muy grande. Máximo {$rules['max_width']}x{$rules['max_height']}px");
            }
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
            }
            return true;
        } catch (\Exception $e) {
            Log::error('Error eliminando imagen: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generar URL de imagen con fallback
     */
    public function getImageUrl(string $path, string $disk = 'public', string $fallback = null)
    {
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->url($path);
        }

        return $fallback ?: asset('images/placeholder.jpg');
    }
}
