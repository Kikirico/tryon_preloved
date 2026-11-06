@extends('layout')
@section('title', 'AI Fitting — Rewear × FASHN')
@section('content')
<section class="studio fitting">
    <span class="eyebrow">REWEAR × FASHN VTON 1.5</span>
    <div class="section-heading"><h1>Bayangkan gaya <em>barumu.</em></h1><span id="ai-health" class="pill" role="status">Memeriksa AI…</span></div>
    <p>Ambil foto, pilih atasan, dan lihat foto dirimu mengenakannya. Hasil AI adalah pratinjau visual dan bukan jaminan ukuran.</p>
    <div class="fitting-grid">
        <form id="fitting-form" class="panel" data-latest="{{ $latest?->id }}">@csrf
            <h2>01 / Pilih pakaian</h2>
            <label for="fitting-product">Koleksi untuk AI fitting</label>
            <select name="product_id" id="fitting-product" required>
                <option value="">Pilih atasan…</option>
                @foreach($products as $product)<option value="{{ $product->id }}" data-image="{{ $product->photo_url }}" @selected((string)request('product') === (string)$product->id)>{{ $product->name }} · {{ $product->size }}</option>@endforeach
            </select>
            <img id="fitting-garment" class="selected-image" hidden alt="Pakaian pilihan">
            @if($products->isEmpty())<p class="notice">Belum ada foto pakaian untuk FASHN. Admin dapat menambahkan foto FASHN pada produk melalui dashboard.</p>@endif
            <h2>02 / Foto dirimu</h2>
            <p class="fine-print">Menghadap depan, bahu sampai pinggul terlihat, tangan sedikit menjauh dari tubuh. Maksimal 5 MB, JPG/PNG/WebP, ukuran 256–4096 piksel.</p>
            <label>Unggah foto<input id="fitting-file" type="file" accept="image/jpeg,image/png,image/webp"></label>
            <div class="actions"><button class="secondary" id="fitting-camera" type="button">Gunakan kamera</button><button class="secondary" id="fitting-capture" type="button" hidden>Ambil foto</button><button class="secondary" id="fitting-stop" type="button" hidden>Matikan kamera</button></div>
            <video id="fitting-video" autoplay playsinline muted hidden></video>
            <img id="fitting-preview" hidden alt="Foto yang akan diproses">
            <label class="consent"><input type="checkbox" name="consent" value="1" required> Saya setuju foto diproses oleh layanan AI milik toko. Foto dan hasil disimpan privat maksimal 24 jam dan dapat dihapus setelah proses selesai.</label>
            <button id="fitting-submit" class="button full" aria-describedby="fitting-submit-help" disabled>Hasilkan foto try-on ↗</button>
            <p id="fitting-submit-help" class="fine-print" role="status">Menunggu layanan AI siap sebelum foto dapat diproses.</p>
        </form>
        <div class="panel fitting-result"><span class="eyebrow">YOUR NEXT LOOK</span><h2>Gaya baru, versi kamu.</h2>
            <p id="fitting-status" role="status" aria-live="polite">Hasil akan muncul di sini. Pilih pakaian dan foto untuk memulai.</p>
            <div id="fitting-comparison" hidden>
                <div class="comparison-grid">
                    <figure>
                        <button class="fitting-photo" type="button" aria-haspopup="dialog" aria-label="Perbesar foto sebelum try-on" data-title="Sebelum try-on">
                            <img id="fitting-before" alt="Foto sebelum try-on">
                        </button>
                        <figcaption>Sebelum</figcaption>
                    </figure>
                    <figure>
                        <button class="fitting-photo" type="button" aria-haspopup="dialog" aria-label="Perbesar foto hasil AI try-on" data-title="Hasil AI try-on">
                            <img id="fitting-after" alt="Foto hasil AI try-on">
                        </button>
                        <figcaption>Hasil AI</figcaption>
                    </figure>
                </div>
            </div>
            <div class="actions"><a id="fitting-download" class="button" hidden download="rewear-tryon.png">Unduh hasil ↓</a><button id="fitting-delete" class="secondary" type="button" hidden>Hapus foto & hasil</button></div>
            <p class="fine-print">Proses pertama bisa lebih lama. Satu foto diproses setiap giliran; tetap di halaman ini atau kembali dengan browser yang sama untuk melihat status.</p>
        </div>
    </div>
    <dialog id="fitting-photo-dialog" class="fitting-photo-dialog" aria-labelledby="fitting-photo-title">
        <div class="fitting-photo-view">
            <div class="fitting-photo-bar">
                <h2 id="fitting-photo-title">Pratinjau foto</h2>
                <form method="dialog"><button class="secondary" type="submit" autofocus>Tutup</button></form>
            </div>
            <img id="fitting-photo-large" alt="">
        </div>
    </dialog>
</section>
@endsection
@push('scripts')<script type="module" src="{{ asset('js/fashn.js') }}"></script>@endpush
