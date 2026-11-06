# Rewear — Laravel preloved & AI Fitting

Klik produk di katalog untuk membuka foto, harga, brand, ukuran, kondisi, deskripsi lengkap, dan tombol beli. Foto generatif FASHN-VTON tersedia melalui navbar **AI Fitting** atau tombol pada detail produk yang memiliki foto FASHN. Lihat [FASHN_SETUP.md](FASHN_SETUP.md) untuk layanan GPU dan foto produk.

## Jalankan pada komputer ini

PHP portabel ada di `.runtime/php/php.exe`, Composer di `.runtime/composer.phar`. Keduanya hanya untuk pengembangan lokal dan diabaikan Git. Database SQLite, dependensi, dan koleksi ilustrasi disiapkan pada pengerjaan awal.

```powershell
.\start.ps1
```

Perintah ini menyalakan website, FASHN-VTON, antrean gambar, dan pembersihan foto dalam satu terminal. Biarkan terminal terbuka; Ctrl+C menghentikan layanan yang dimulainya. Buka http://127.0.0.1:8000 dan pilih **AI Fitting** untuk foto generatif setelah indikator **AI siap** muncul. Untuk website saja tanpa memuat FASHN, gunakan `.\start.ps1 -WebOnly`.

Kamera realtime dengan overlay pakaian sudah dihapus. Tombol kamera di AI Fitting hanya mengambil foto diam untuk diproses FASHN; pengguna juga dapat mengunggah foto.

## Instalasi baru

Gunakan PHP 8.4 dengan ekstensi mbstring, openssl, fileinfo, pdo_sqlite, sqlite3, curl, zip, dan gd, serta Composer. Lockfile yang disertakan dibuat pada PHP 8.4. Node hanya diperlukan untuk regenerasi ilustrasi; tampilan tidak memerlukan build Vite.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item database/database.sqlite -ItemType File
php artisan migrate --seed
php artisan storage:link
php artisan shop:admin admin@example.com
php artisan serve
```

Pada Windows, `storage:link` mungkin memerlukan Developer Mode atau terminal administrator. Document root produksi harus menunjuk ke `public`, bukan root proyek.

## Admin & pembayaran

Tidak ada akun/kata sandi admin bawaan. Buat akun melalui terminal; kata sandi ditanyakan secara tersembunyi:

```powershell
.runtime/php/php.exe artisan shop:admin admin@example.com
```

Masuk di `/admin/login`. Admin dapat menambah produk beserta foto katalog dan detail pakaian, melihat pesanan serta bukti transfer privat, mengonfirmasi, menolak, atau membatalkan pesanan. Konfirmasi pembayaran adalah tindakan manual; tidak ada integrasi payment gateway atau QRIS otomatis.

Tombol **Hapus** tersedia pada setiap produk di inventaris, disertai konfirmasi nama produk. Produk yang dihapus tidak lagi muncul di inventaris, katalog, atau pilihan AI Fitting. Data dan foto produk tetap disimpan untuk riwayat pesanan dan proses fitting yang sudah masuk antrean. Pembatalan pesanan lama tidak menerbitkan kembali produk yang sudah dihapus. Formulir foto FASHN hanya muncul untuk produk yang belum memiliki foto FASHN.

Isi `.env` dengan rekening asli toko:

```dotenv
SHOP_BANK_NAME="Nama bank"
SHOP_BANK_ACCOUNT="Nomor rekening"
SHOP_BANK_HOLDER="Nama pemilik"
```

Tanpa rekening, checkout diblokir. Biaya pengiriman flat Rp20.000 dapat diubah di `config/shop.php`. Harga pesanan dihitung di server; produk stok tunggal dikunci dalam transaksi. Pesanan menunggu transfer tidak kedaluwarsa otomatis: admin harus membatalkan pesanan yang ditinggalkan agar barang tersedia kembali. Tautan pesanan memakai token UUID dan bertindak sebagai akses privat pembeli; belum ada akun pembeli atau notifikasi email. Jangan bagikan tautan tersebut.

## Detail produk & AI Fitting

Katalog mengarah ke halaman detail /products/{id}. Foto besar, brand, ukuran, kondisi, harga, deskripsi lengkap, ketersediaan, dan ongkir ditampilkan sebelum checkout. Deskripsi panjang tetap membungkus dan mempertahankan baris baru. Tautan lama /try-on/{id} dialihkan ke detail produk.

Admin cukup mengunggah foto katalog JPG/PNG/WebP, maksimal 5 MB dan 4096 piksel. Lengkapi deskripsi dengan kondisi, bahan, dan ukuran aktual. Unggahan PNG transparan untuk overlay tidak lagi diperlukan; data aset lama tetap disimpan.

Untuk AI Fitting, tambahkan foto atasan melalui bagian **Foto produk untuk FASHN** di admin. Produk yang memiliki foto FASHN menampilkan tombol untuk mencoba pakaian itu, dengan pilihan produk otomatis terisi. FASHN menghasilkan foto baru berdasarkan foto pengguna dan pakaian, bukan video realtime. Hasilnya tidak menjamin ukuran fisik atau seluruh detail pakaian; lihat [FASHN_SETUP.md](FASHN_SETUP.md) untuk batasan dan privasi.

Setelah memperbarui proyek lama, jalankan perintah **.runtime/php/php.exe artisan migrate** agar produk baru dapat dibuat tanpa aset overlay. Tidak perlu menghapus atau mengisi ulang produk.

## Verifikasi

```powershell
.runtime/php/php.exe artisan test
```

Periksa katalog → detail produk → checkout, termasuk deskripsi panjang di layar ponsel. Untuk AI Fitting, uji unggah foto, pemilihan pakaian, hasil, dan penghapusan foto. Pengambilan foto melalui kamera memerlukan HTTPS atau localhost.

Sebelum produksi, ganti koleksi demo, isi rekening asli, buat akun admin sendiri, set APP_DEBUG=false dan APP_URL HTTPS, aktifkan cookie secure, siapkan backup database dan penyimpanan, serta atur batas upload web server. Proyek Laravel ini belum dipublikasikan; hosting Sites berbasis Workers tidak menjalankan PHP.
