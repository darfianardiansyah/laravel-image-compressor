<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageConversionTest extends TestCase
{
    public function test_user_can_convert_image_to_webp(): void
    {
        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('Ekstensi GD tidak mendukung WebP.');
        }

        Storage::fake('public');

        $response = $this->post(route('conversions.convert'), [
            'image' => UploadedFile::fake()->image('photo.png', 1200, 800),
            'format' => 'webp',
            'quality' => 75,
            'max_width' => '800',
        ]);

        $response
            ->assertRedirect(route('conversions.index'))
            ->assertSessionHas('status', 'Gambar berhasil dikonversi.')
            ->assertSessionHas('result');

        $result = session('result');

        Storage::disk('public')->assertExists($result['converted_path']);
        $this->assertSame('WEBP', $result['converted_format']);
        $this->assertLessThanOrEqual(800, $result['converted_width']);
    }

    public function test_user_cannot_convert_unsupported_file(): void
    {
        Storage::fake('public');

        $response = $this->from(route('conversions.index'))->post(route('conversions.convert'), [
            'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            'format' => 'webp',
            'quality' => 75,
            'max_width' => '1600',
        ]);

        $response
            ->assertRedirect(route('conversions.index'))
            ->assertSessionHasErrors('image');
    }

    public function test_result_page_can_render_conversion_summary(): void
    {
        $response = $this->withSession([
            'result' => [
                'original_name' => 'photo.png',
                'original_format' => 'PNG',
                'original_size' => 2048,
                'original_width' => 1200,
                'original_height' => 800,
                'converted_format' => 'WEBP',
                'converted_size' => 512,
                'converted_width' => 800,
                'converted_height' => 533,
                'saving_percent' => 75.0,
                'download_url' => route('conversions.download', ['filename' => '11111111-1111-1111-1111-111111111111.webp']),
                'converted_download_name' => 'photo.webp',
            ],
        ])->get(route('conversions.index'));

        $response
            ->assertOk()
            ->assertSee('Penghematan 75.00%')
            ->assertSee('photo.png')
            ->assertSee('Download Hasil');
    }
}
