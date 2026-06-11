<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Image Compress Converter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --ink: #1f2937;
            --muted: #64748b;
            --line: #d9e2ec;
            --surface: #f7fafc;
            --accent: #0f766e;
            --accent-dark: #115e59;
        }

        body {
            background: var(--surface);
            color: var(--ink);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .app-shell {
            max-width: 1040px;
        }

        .navbar {
            border-bottom: 1px solid var(--line);
        }

        .panel {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 8px;
        }

        .btn-primary {
            --bs-btn-bg: var(--accent);
            --bs-btn-border-color: var(--accent);
            --bs-btn-hover-bg: var(--accent-dark);
            --bs-btn-hover-border-color: var(--accent-dark);
        }

        .preview-image {
            max-height: 320px;
            object-fit: contain;
            background: #eef2f7;
            border: 1px dashed #bcccdc;
            border-radius: 8px;
        }

        .metric {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: .65rem 0;
            border-bottom: 1px solid #edf2f7;
        }

        .metric:last-child {
            border-bottom: 0;
        }

        .metric span:first-child {
            color: var(--muted);
        }
    </style>
</head>
<body>
    <nav class="navbar bg-white">
        <div class="container app-shell py-2">
            <span class="navbar-brand mb-0 h1">Image Compress Converter</span>
        </div>
    </nav>

    <main class="container app-shell py-4 py-lg-5">
        <div class="mb-4">
            <h1 class="h2 fw-semibold mb-2">Image Compress Converter</h1>
            <p class="text-secondary mb-0">Konversi gambar JPG, PNG, atau WebP ke AVIF/WebP agar ukuran lebih kecil dan tetap berkualitas.</p>
        </div>

        @if (session('status'))
            <div class="alert alert-success" role="alert">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Gambar gagal dikonversi.</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-4">
            <section class="col-lg-7">
                <form class="panel p-4" method="post" action="{{ route('conversions.convert') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label for="image" class="form-label fw-medium">Pilih Gambar</label>
                        <input class="form-control" type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" required>
                        <div class="form-text">Format JPG, PNG, atau WebP. Maksimal 10 MB.</div>
                    </div>

                    <img id="preview" class="preview-image w-100 d-none mb-3" alt="Preview gambar sebelum konversi">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="format" class="form-label fw-medium">Format Output</label>
                            <select class="form-select" id="format" name="format">
                                <option value="webp" @selected(old('format', 'webp') === 'webp')>WebP</option>
                                <option value="avif" @selected(old('format') === 'avif') @disabled(! $supportsAvif)>AVIF</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="quality" class="form-label fw-medium">Kualitas <span id="qualityValue">{{ old('quality', 75) }}</span></label>
                            <input class="form-range" type="range" min="10" max="100" id="quality" name="quality" value="{{ old('quality', 75) }}">
                        </div>

                        <div class="col-md-4">
                            <label for="max_width" class="form-label fw-medium">Maksimal Lebar</label>
                            <select class="form-select" id="max_width" name="max_width">
                                @foreach ([800, 1200, 1600, 1920] as $width)
                                    <option value="{{ $width }}" @selected(old('max_width', '1600') == $width)>{{ $width }} px</option>
                                @endforeach
                                <option value="original" @selected(old('max_width') === 'original')>Original</option>
                            </select>
                        </div>
                    </div>

                    @unless ($supportsAvif)
                        <div class="alert alert-warning mt-3 mb-0" role="alert">
                            AVIF belum didukung server ini. Gunakan WebP untuk konversi.
                        </div>
                    @else
                        <p class="text-secondary small mt-3 mb-0">AVIF menghasilkan ukuran lebih kecil, namun prosesnya bisa lebih lama dan bergantung pada dukungan server.</p>
                    @endunless

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <button class="btn btn-primary" type="submit">Convert Sekarang</button>
                        <a class="btn btn-outline-secondary" href="{{ route('conversions.index') }}">Convert Gambar Lain</a>
                    </div>
                </form>
            </section>

            <aside class="col-lg-5">
                <div class="panel p-4 h-100">
                    <h2 class="h5 fw-semibold mb-3">Hasil Konversi</h2>

                    @if ($result)
                        <div class="d-inline-flex align-items-center rounded-pill bg-success-subtle text-success-emphasis px-3 py-2 mb-3 fw-medium">
                            Penghematan {{ number_format($result['saving_percent'], 2) }}%
                        </div>

                        <div class="metric"><span>File asli</span><strong>{{ $result['original_name'] }}</strong></div>
                        <div class="metric"><span>Format asli</span><strong>{{ $result['original_format'] }}</strong></div>
                        <div class="metric"><span>Ukuran asli</span><strong>{{ \Illuminate\Support\Number::fileSize($result['original_size']) }}</strong></div>
                        <div class="metric"><span>Dimensi asli</span><strong>{{ $result['original_width'] }} x {{ $result['original_height'] }} px</strong></div>
                        <div class="metric"><span>Format hasil</span><strong>{{ $result['converted_format'] }}</strong></div>
                        <div class="metric"><span>Ukuran hasil</span><strong>{{ \Illuminate\Support\Number::fileSize($result['converted_size']) }}</strong></div>
                        <div class="metric"><span>Dimensi hasil</span><strong>{{ $result['converted_width'] }} x {{ $result['converted_height'] }} px</strong></div>

                        <a class="btn btn-primary w-100 mt-4" href="{{ $result['download_url'] }}?name={{ urlencode($result['converted_download_name']) }}">Download Hasil</a>
                    @else
                        <p class="text-secondary mb-0">Hasil akan tampil setelah gambar berhasil dikonversi.</p>
                    @endif
                </div>
            </aside>
        </div>
    </main>

    <script>
        const imageInput = document.getElementById('image');
        const preview = document.getElementById('preview');
        const formatInput = document.getElementById('format');
        const qualityInput = document.getElementById('quality');
        const qualityValue = document.getElementById('qualityValue');

        imageInput.addEventListener('change', () => {
            const file = imageInput.files[0];

            if (!file) {
                preview.classList.add('d-none');
                preview.removeAttribute('src');
                return;
            }

            preview.src = URL.createObjectURL(file);
            preview.classList.remove('d-none');
        });

        qualityInput.addEventListener('input', () => {
            qualityValue.textContent = qualityInput.value;
        });

        formatInput.addEventListener('change', () => {
            if (formatInput.value === 'avif' && qualityInput.value === '75') {
                qualityInput.value = 55;
            }

            if (formatInput.value === 'webp' && qualityInput.value === '55') {
                qualityInput.value = 75;
            }

            qualityValue.textContent = qualityInput.value;
        });
    </script>
</body>
</html>
