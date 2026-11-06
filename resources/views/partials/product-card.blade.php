<article class="product-card">
    <a class="product-image" href="{{ route('product.show', $product) }}">
        <span class="product-badge">{{ $product->condition }}</span>
        <img loading="lazy" src="{{ $product->photo_url }}" alt="{{ $product->name }}">
        <span class="detail-badge">Lihat detail produk <b>↗</b></span>
    </a>
    <div class="product-meta"><span>{{ $product->brand }} · {{ $product->size }}</span><span>1 of 1</span></div>
    <h3><a href="{{ route('product.show', $product) }}">{{ $product->name }}</a></h3>
    <div class="product-price">Rp {{ number_format($product->price, 0, ',', '.') }} <a href="{{ route('product.show', $product) }}" aria-label="Lihat detail {{ $product->name }}">↗</a></div>
</article>
