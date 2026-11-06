@extends('layout')
@section('title', $product->name.' — Rewear')
@section('content')
<section class="product-page">
    <a class="back" href="{{ route('home') }}#collection">← Kembali ke koleksi</a>
    <div class="product-detail-grid">
        <div class="product-detail-photo">
            <img src="{{ $product->photo_url }}" alt="{{ $product->name }}">
        </div>
        <div class="product-details">
            <span class="eyebrow">PRELOVED / SATU BARANG, CERITA BARU</span>
            <h1>{{ $product->name }}</h1>
            <p class="product-detail-price">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
            <dl class="product-specs">
                <div><dt>Brand</dt><dd>{{ $product->brand }}</dd></div>
                <div><dt>Ukuran</dt><dd>{{ $product->size }}</dd></div>
                <div><dt>Kondisi</dt><dd>{{ $product->condition }}</dd></div>
                <div><dt>Ketersediaan</dt><dd>1 barang tersedia</dd></div>
            </dl>
            <section class="product-description-section">
                <h2>Deskripsi & ukuran aktual</h2>
                <p class="product-description">{{ $product->description }}</p>
            </section>
            <div class="product-purchase">
                <a class="button full" href="{{ route('checkout', $product) }}">Beli sekarang ↗</a>
                @if($product->fashn_image)
                    <a class="secondary full" href="{{ route('fashn', ['product' => $product->id]) }}">Coba pakaian ini di AI Fitting ↗</a>
                @endif
                <p class="fine-print">Ongkir flat Rp {{ number_format(config('shop.shipping'), 0, ',', '.') }} · Pembayaran melalui transfer bank.</p>
            </div>
        </div>
    </div>
    @if($products->isNotEmpty())
        <section class="related-products">
            <div class="section-heading"><div><span class="eyebrow">MASIH ADA CERITA LAIN</span><h2>Temuan lain <em>untukmu.</em></h2></div><a class="text-link" href="{{ route('home') }}#collection">Lihat koleksi ↗</a></div>
            <div class="product-grid">
                @foreach($products as $item)
                    @include('partials.product-card', ['product' => $item])
                @endforeach
            </div>
        </section>
    @endif
</section>
@endsection
