@if($productsWithoutFashn->isNotEmpty())
<section class="panel fashn-admin"><h2>Foto produk untuk FASHN</h2><p>Produk berikut belum memiliki foto FASHN. Tambahkan foto atasan asli yang dibentangkan atau dikenakan model. Setelah disimpan, produk otomatis keluar dari daftar ini.</p>
@foreach($productsWithoutFashn as $product)
<form method="post" action="/admin/products/{{ $product->id }}/fashn" enctype="multipart/form-data" class="order-card">@csrf
<h3>{{ $product->name }}</h3><p>Belum ada foto FASHN.</p>
<label>Foto atasan<input type="file" name="fashn_image" accept="image/jpeg,image/png,image/webp" required></label>
<label>Jenis foto<select name="fashn_photo_type"><option value="flat-lay" @selected($product->fashn_photo_type === 'flat-lay')>Pakaian dibentangkan / tanpa model</option><option value="model" @selected($product->fashn_photo_type === 'model')>Pakaian dikenakan model</option></select></label>
<small>JPG, PNG, WebP · maks. 5 MB · 256–4096 piksel. Gunakan atasan tampak depan.</small><button class="button full">Simpan foto FASHN</button>
</form>@endforeach</section>
@endif
