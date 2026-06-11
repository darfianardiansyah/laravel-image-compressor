<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConvertImageRequest;
use App\Services\ImageConversionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ImageConversionController extends Controller
{
    public function __construct(private readonly ImageConversionService $imageConversionService) {}

    public function index(): View
    {
        return view('conversions.index', [
            'supportsAvif' => $this->imageConversionService->supportsAvif(),
            'result' => session('result'),
        ]);
    }

    public function convert(ConvertImageRequest $request): RedirectResponse
    {
        $maxWidth = $request->input('max_width') === 'original'
            ? null
            : (int) $request->input('max_width', 1600);

        try {
            $result = $this->imageConversionService->convert(
                $request->file('image'),
                $request->string('format')->toString(),
                $request->integer('quality'),
                $maxWidth,
            );

            return redirect()
                ->route('conversions.index')
                ->with('status', 'Gambar berhasil dikonversi.')
                ->with('result', $result);
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->except('image'))
                ->withErrors(['image' => $exception->getMessage()]);
        }
    }

    public function download(string $filename): StreamedResponse|RedirectResponse
    {
        abort_unless(preg_match('/^[a-f0-9-]+\.(webp|avif)$/', $filename) === 1, 404);

        $path = 'conversions/'.$filename;

        if (! Storage::disk('public')->exists($path)) {
            return redirect()
                ->route('conversions.index')
                ->withErrors(['download' => 'File download tidak ditemukan atau sudah dibersihkan.']);
        }

        $downloadName = request('name', $filename);

        return Storage::disk('public')->download($path, basename($downloadName));
    }
}
