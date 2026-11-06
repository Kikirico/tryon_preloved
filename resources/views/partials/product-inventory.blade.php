<section class="admin-inventory">
    <h2>Inventaris produk</h2>
    <div class="inventory">
        @forelse($products as $product)
            <div class="inventory-row">
                <img src="{{ $product->photo_url }}" alt="" loading="lazy">
                <div class="inventory-info">
                    <strong>{{ $product->name }}</strong>
                    <span>{{ $product->brand }} · {{ $product->size }} · Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                    <small>{{ $product->fashn_image ? 'Foto FASHN tersedia' : 'Belum ada foto FASHN' }}</small>
                </div>
                <span class="pill">{{ $product->active ? 'Tersedia' : 'Dipesan / terjual' }}</span>
                <form method="post" action="{{ route('admin.products.destroy', $product) }}" class="product-delete-form" data-product-name="{{ $product->name }}">
                    @csrf
                    @method('DELETE')
                    <button class="secondary danger-button" type="submit" aria-label="Hapus produk {{ $product->name }}">Hapus</button>
                </form>
            </div>
        @empty
            <p class="empty">Belum ada produk. Tambahkan produk melalui formulir di atas.</p>
        @endforelse
    </div>
</section>
