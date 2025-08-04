<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;

trait ImageOptimizer
{
    /**
     * Optimizar y guardar imagen
     */
    public function optimizeAndStoreImage(UploadedFile $file, string $disk = 'public', array $options = [])
    {
        $defaultOptions = [
            'maxWidth' => 1920,
            'maxHeight' => 1080,
            'quality' => 80,
            'format' => 'webp', // webp, jpg, png
            'generateThumbnail' => true,
            'thumbnailSize' => 300,
            'path' => 'images',
            'filename' => null
        ];

        $options = array_merge($defaultOptions, $options);

        // Generar nombre único si no se proporciona
        if (!$options['filename']) {
            $options['filename'] = time() . '_' . Str::random(10);
        }

        try {
            // Crear manager de imágenes con Intervention 3.x
            $manager = new ImageManager(new Driver());

            // Crear imagen
            $image = $manager->read($file);

            // Redimensionar si es necesario
            $image = $this->resizeImage($image, $options['maxWidth'], $options['maxHeight']);

            // Convertir formato si es necesario
            $image = $this->convertFormat($image, $options['format']);

            // Guardar imagen principal
            $mainPath = $options['path'] . '/' . $options['filename'] . '.' . $options['format'];
            $mainImageData = $image->encode($options['format'], $options['quality']);
            Storage::disk($disk)->put($mainPath, $mainImageData);

            $result = [
                'main' => $mainPath,
                'thumbnail' => null
            ];

            // Generar thumbnail si se solicita
            if ($options['generateThumbnail']) {
                $thumbnail = $this->createThumbnail($image, $options['thumbnailSize']);
                $thumbnailPath = $options['path'] . '/thumbnails/' . $options['filename'] . '.' . $options['format'];
                $thumbnailData = $thumbnail->encode($options['format'], $options['quality']);
                Storage::disk($disk)->put($thumbnailPath, $thumbnailData);
                $result['thumbnail'] = $thumbnailPath;
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Error optimizando imagen: ' . $e->getMessage());
            throw new \Exception('Error al procesar la imagen: ' . $e->getMessage());
        }
    }

    /**
     * Redimensionar imagen manteniendo proporción
     */
    private function resizeImage($image, $maxWidth, $maxHeight)
    {
        $width = $image->width();
        $height = $image->height();

        // Solo redimensionar si es necesario
        if ($width > $maxWidth || $height > $maxHeight) {
            $image->resize($maxWidth, $maxHeight);
        }

        return $image;
    }

    /**
     * Convertir formato de imagen
     */
    private function convertFormat($image, $format)
    {
        switch ($format) {
            case 'webp':
                return $image->encode('webp');
            case 'jpg':
            case 'jpeg':
                return $image->encode('jpg');
            case 'png':
                return $image->encode('png');
            default:
                return $image;
        }
    }

    /**
     * Crear thumbnail
     */
    private function createThumbnail($image, $size)
    {
        return $image->resize($size, $size);
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
     * Eliminar imagen y thumbnail
     */
    public function deleteImage(string $path, string $disk = 'public')
    {
        try {
            // Eliminar imagen principal
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }

            // Eliminar thumbnail si existe
            $thumbnailPath = $this->getThumbnailPath($path);
            if (Storage::disk($disk)->exists($thumbnailPath)) {
                Storage::disk($disk)->delete($thumbnailPath);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Error eliminando imagen: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener ruta del thumbnail
     */
    private function getThumbnailPath(string $mainPath)
    {
        $pathInfo = pathinfo($mainPath);
        return $pathInfo['dirname'] . '/thumbnails/' . $pathInfo['basename'];
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

    /**
     * Generar URL de thumbnail con fallback
     */
    public function getThumbnailUrl(string $path, string $disk = 'public', string $fallback = null)
    {
        $thumbnailPath = $this->getThumbnailPath($path);

        if (Storage::disk($disk)->exists($thumbnailPath)) {
            return Storage::disk($disk)->url($thumbnailPath);
        }

        // Si no existe thumbnail, usar imagen principal
        return $this->getImageUrl($path, $disk, $fallback);
    }
}
