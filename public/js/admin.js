for (const form of document.querySelectorAll('.product-delete-form')) {
  form.addEventListener('submit', event => {
    const name = form.dataset.productName;
    if (!window.confirm(`Hapus produk "${name}"? Produk akan hilang dari katalog dan AI Fitting. Riwayat pesanan tetap tersimpan.`)) {
      event.preventDefault();
    }
  });
}
