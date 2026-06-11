<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConvertImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'format' => ['required', 'in:webp,avif'],
            'quality' => ['required', 'integer', 'min:10', 'max:100'],
            'max_width' => ['nullable', 'in:800,1200,1600,1920,original'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.required' => 'Pilih gambar terlebih dahulu.',
            'image.image' => 'File wajib berupa gambar valid.',
            'image.mimes' => 'Format gambar harus JPG, JPEG, PNG, atau WebP.',
            'image.max' => 'Ukuran gambar maksimal 10 MB.',
            'format.in' => 'Format hasil harus WebP atau AVIF.',
            'quality.min' => 'Kualitas minimal 10.',
            'quality.max' => 'Kualitas maksimal 100.',
            'max_width.in' => 'Pilihan maksimal lebar gambar tidak valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'format' => $this->input('format', 'webp'),
            'quality' => $this->input('quality', $this->input('format') === 'avif' ? 55 : 75),
            'max_width' => $this->input('max_width', '1600'),
        ]);
    }
}
