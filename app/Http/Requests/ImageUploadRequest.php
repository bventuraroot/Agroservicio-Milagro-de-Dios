<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImageUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,png,jpg,webp,gif',
                'max:5120', // 5MB
                'dimensions:min_width=100,min_height=100,max_width=5000,max_height=5000'
            ]
        ];

        // Si es para actualizar, hacer la imagen opcional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['image'] = array_merge($rules['image'], ['sometimes']);
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'image.required' => 'Debe seleccionar una imagen.',
            'image.file' => 'El archivo debe ser válido.',
            'image.image' => 'El archivo debe ser una imagen.',
            'image.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg, webp o gif.',
            'image.max' => 'La imagen no puede ser mayor a 5MB.',
            'image.dimensions' => 'La imagen debe tener dimensiones entre 100x100 y 5000x5000 píxeles.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'image' => 'imagen',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->hasFile('image')) {
                $file = $this->file('image');

                // Validación adicional de seguridad
                $this->validateImageSecurity($file, $validator);
            }
        });
    }

    /**
     * Validaciones adicionales de seguridad
     */
    private function validateImageSecurity($file, $validator)
    {
        // Verificar que realmente es una imagen válida
        $imageInfo = getimagesize($file->getPathname());
        if (!$imageInfo) {
            $validator->errors()->add('image', 'El archivo no es una imagen válida.');
            return;
        }

        // Verificar proporción de aspecto (opcional)
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $ratio = $width / $height;

        // Si la imagen es muy ancha o muy alta, podría ser sospechosa
        if ($ratio > 10 || $ratio < 0.1) {
            $validator->errors()->add('image', 'Las dimensiones de la imagen no son válidas.');
        }

        // Verificar que el archivo no esté corrupto
        if (!$this->isValidImageFile($file)) {
            $validator->errors()->add('image', 'La imagen está corrupta o no es válida.');
        }
    }

    /**
     * Verificar si el archivo es una imagen válida
     */
    private function isValidImageFile($file)
    {
        try {
            $image = imagecreatefromstring(file_get_contents($file->getPathname()));
            if ($image !== false) {
                imagedestroy($image);
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }
}
