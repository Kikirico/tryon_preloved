@extends('layout')
@section('content')
<section class="hero">
    <div class="hero-copy">
        <span class="eyebrow"><i></i> PRELOVED MEETS A LITTLE TECHNOLOGY</span>
        <h1>Gaya baru.<br>Cerita <em>kedua.</em></h1>
        <p>Temukan pakaian pilihan yang layak dicintai lagi.<br>Kenali detailnya, bayangkan gaya barumu dengan AI Fitting.</p>
        <div class="actions"><a class="button" href="#collection">Jelajahi koleksi <span>↗</span></a><a class="text-link" href="{{ route('fashn') }}">◎ Buka AI Fitting</a></div>
        <div class="hero-note"><span>✧</span> Gaya lebih personal. Belanja lebih sadar.</div>
    </div>
    <div class="hero-art">
        <div class="orbit"></div><span class="vertical-note">THE SECOND CHAPTER COLLECTION</span>
        <img src="{{ asset('demo/sage.svg') }}" alt="Ilustrasi kemeja sage dari koleksi demo">
        <div class="art-label"><small>YOUR NEXT FAVORITE</small><strong>Already loved.<br>Ready for you.</strong></div>
        <div class="floating-tag">◎ <div>AI Fitting<small>Foto baru, gaya pilihanmu.</small></div><span>↗</span></div>
        <span class="round-stamp">LESS WASTE<br>MORE STYLE<br>✳</span>
    </div>
</section>
<div class="benefits"><span>✧ Kurasi dengan hati</span><span>◎ Coba gaya lewat AI Fitting</span><span>↺ Satu barang, cerita baru</span><span>♡ Pilihan yang lebih sadar</span></div>
<section id="collection" class="collection">
    <div class="section-heading"><div><span class="eyebrow">THE GOOD FINDS</span><h2>Temukan yang <em>kamu banget.</em></h2></div><span>{{ $products->count() }} pilihan tersedia</span></div>
    <div class="collection-tools"><span class="pill">Atasan & outerwear</span><form method="get"><label class="sr-only" for="search">Cari koleksi</label><input id="search" name="q" value="{{ $search }}" placeholder="Cari nama pakaian…"><button aria-label="Cari produk">⌕</button></form></div>
    <div class="product-grid">
        @forelse($products as $product)
            @include('partials.product-card')
        @empty
            <div class="empty">Belum ada produk yang cocok. <a href="/">Lihat semua koleksi</a></div>
        @endforelse
    </div>
</section>
<section id="how" class="how">
    <div>
        <span class="eyebrow">YOUR NEXT FAVORITE, MADE SIMPLE.</span>
        <h2>Kenali detailnya.<br>Temukan <em>gayamu.</em></h2>
        <p>Lihat foto dan informasi lengkap setiap produk. Ingin membayangkan tampilannya di tubuhmu? Coba foto dengan AI Fitting sebelum membeli.</p>
        <small>AI Fitting menghasilkan pratinjau foto. Periksa ukuran aktual dan kondisi pada detail produk sebelum memesan.</small>
    </div>
    <ol>
        <li><span>01</span><div><h3>Pilih & lihat detail</h3><p>Klik produk untuk melihat foto, harga, ukuran, kondisi, dan deskripsi lengkap.</p></div></li>
        <li><span>02</span><div><h3>Bayangkan lewat AI Fitting</h3><p>Pilih pakaian yang tersedia di AI Fitting, unggah fotomu, lalu tunggu hasilnya.</p></div></li>
        <li><span>03</span><div><h3>Jadikan milikmu</h3><p>Klik Beli sekarang di detail produk, isi alamat, dan lanjutkan pembayaran.</p></div></li>
    </ol>
</section>
@endsection
