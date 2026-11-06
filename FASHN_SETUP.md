# FASHN-VTON lokal pada Rewear

Menu **AI Fitting** tersedia di navbar, URL `/ai-fitting`. Ini menghasilkan foto dari foto pengguna + foto atasan, dengan model FASHN VTON 1.5 lokal. Tidak memakai API berbayar.

## Menjalankan pada laptop ini

Satu terminal untuk website, AI, antrean, dan penghapusan foto kedaluwarsa:

```powershell
cd C:\Users\VICTUS\Documents\tryon_preloved
.\start.ps1
```

Biarkan terminal terbuka. Buka http://127.0.0.1:8000/ai-fitting dan tunggu indikator **AI siap**. Startup memuat model sekali; beberapa menit pada startup awal mungkin diperlukan. Ctrl+C menghentikan website, AI, dan proses worker/scheduler yang dimulai skrip itu. Jika layanan lama masih aktif, hentikan lewat terminal lamanya sebelum menjalankan skrip gabungan.

Untuk menjalankan website saja tanpa memuat AI, gunakan `.\start.ps1 -WebOnly`. Layanan AI juga tetap dapat dijalankan terpisah dengan `.\start-fashn.ps1`. Log website tersimpan di `.runtime/web.log` dan `.runtime/web-error.log`; proses pemuatan AI ditampilkan langsung di terminal.

Jika unduhan bobot belum lengkap atau terputus, jalankan:

```powershell
$env:HF_HOME="$PWD/.runtime/huggingface"
$env:HF_HUB_DISABLE_XET='1'
.runtime/fashn-env/Scripts/python.exe -u .runtime/fashn-vton/scripts/download_weights.py --weights-dir .runtime/fashn-weights
```

## Menyiapkan produk

Masuk ke `/admin`. Bagian **Foto produk untuk FASHN** hanya menampilkan produk yang belum memiliki foto FASHN. Setelah foto disimpan, formulir produk tersebut menghilang; jika semua produk sudah memiliki foto, bagian ini disembunyikan. Pilih `Pakaian dibentangkan / tanpa model` atau `Pakaian dikenakan model` sesuai gambar. Format JPG/PNG/WebP, 256–4096 piksel, maksimal 5 MB. PNG transparan ditaruh di atas background putih saat diproses. Jangan memakai ilustrasi SVG demo untuk menilai kualitas hasil.

Produk yang aktif dan memiliki foto FASHN muncul di AI Fitting. Model saat ini dikonfigurasi untuk **atasan**; dukungan kategori lain membutuhkan penambahan kategori produk dan kontrol model. Foto FASHN dapat berbeda dari foto katalog. Aset PNG overlay realtime tidak lagi digunakan atau wajib diunggah. Klik produk di katalog membuka detail produk; tombol AI Fitting pada detail tersedia bila foto FASHN sudah diunggah.

## Alur pengguna

Pilih atasan → unggah foto atau gunakan kamera → centang persetujuan → hasilkan foto → lihat sebelum/sesudah → unduh atau hapus. Foto diproses pada layanan toko, tidak dikirim ke API FASHN. Koneksi internet diperlukan untuk instalasi dan pengunduhan bobot pertama.

Satu sesi hanya dapat memiliki satu proses aktif; kapasitas antrean global lima. Proses dibatasi 10 menit, worker 11 menit, retry_after 12 menit. Tidak ada retry inferensi otomatis. Antrean memakai koneksi database `fashn` tersendiri, tidak terpengaruh `QUEUE_CONNECTION=sync`.

Foto dan hasil disimpan di `storage/app/private/tryon`, diakses lewat route Laravel yang memeriksa identitas sesi. UUID saja tidak memberi akses. Masa akses 24 jam; `fashn:prune` menghapus file kedaluwarsa setiap menit saat scheduler aktif. Saat layanan dimatikan, penghapusan fisik dilanjutkan setelah scheduler dinyalakan lagi. Pengguna dapat menghapus foto/hasil setelah status selesai atau gagal. Menghapus cookie sesi menghilangkan akses ke hasil lama.

## Profil laptop 8 GB

`ai/runtime.py` memuat model utama dalam BF16 pada GPU, dan DWPose serta human parser pada CPU. Checkpoint dimuat di CPU lalu dipindah ke GPU untuk mengurangi lonjakan VRAM. Satu gambar per proses, 20 sampling steps. Kualitas dan waktu proses bergantung foto, perangkat, dan memori kosong. Hasil AI tidak menjamin ukuran fisik atau detail produk selalu tepat.

## Instalasi ulang

Jalankan `./scripts/setup-fashn.ps1` untuk menyiapkan runtime, dependensi, dan bobot di dalam proyek. Unduhan berukuran beberapa GB dan dapat memerlukan waktu lama.

Source upstream dipin di `ai/UPSTREAM.md`. Python 3.11, PyTorch CUDA 2.6.0 + torchvision 0.21.0 digunakan pada pengerjaan ini. Versi paket lainnya tercatat di `ai/installed-requirements.txt`. Instal PyTorch dari index CUDA resmi dahulu; lalu dependensi dan source FASHN pada commit yang tercantum. Semua runtime, model dan cache ditempatkan dalam `.runtime` yang diabaikan Git.

```powershell
.runtime/php/php.exe scripts/init-fashn.php
.runtime/php/php.exe artisan migrate
```

`scripts/init-fashn.php` membuat token internal secara acak tanpa menampilkannya. Token disimpan di `.runtime/fashn-service.key`, dibaca Laravel dan Python. Service hanya bind ke `127.0.0.1:8001`. Jangan membuka port AI langsung ke internet. Untuk deployment di mesin terpisah, konfigurasi jaringan privat, otentikasi, penyimpanan, HTTPS, serta pengelolaan proses perlu disesuaikan.

## Pemeriksaan & troubleshooting

Verifikasi 5 November 2026 pada RTX 4060 Laptop 8 GB: contoh resmi berhasil diolah menjadi `.runtime/fashn-smoke.png`. Pemuatan model 10,3 detik, inferensi termasuk preprocessing 52,5 detik, puncak alokasi PyTorch 2,72 GiB (pemakaian total GPU teramati sekitar 3,6 GB). Angka ini satu pengujian, bukan jaminan kecepatan semua foto. Laravel `/ai-fitting/health` mengembalikan `ready: true`. Sebanyak 14 tes Laravel dan 3 tes Python lulus. Template Blade berhasil dikompilasi. Pemeriksaan visual browser otomatis belum selesai karena sesi browser timeout.

```powershell
.runtime/php/php.exe artisan test
.runtime/fashn-env/Scripts/python.exe -m pytest ai/test_server.py -q
node --check public/js/fashn.js
```

Tes Python memalsukan keluaran model untuk memeriksa protokol, validasi gambar dan penguncian GPU. Tes Laravel memalsukan respons HTTP untuk memeriksa penyimpanan dan privasi. Keduanya tidak menggantikan uji inferensi nyata.

Jika status AI belum siap, lihat terminal AI. Jika antrean tidak berjalan, lihat `.runtime/fashn-worker-error.log`. Jika VRAM penuh, tutup aplikasi GPU lain dan restart AI. Foto yang gagal dapat dikirim ulang. Jika worker terputus, proses berjalan ditandai gagal setelah 20 menit; antrean yang tidak diambil worker kedaluwarsa setelah 1 jam. Tombol kamera memerlukan HTTPS atau localhost.

Referensi: https://github.com/fashn-AI/fashn-vton-1.5. Lisensi source utama Apache-2.0; pertahankan lisensi upstream dan periksa lisensi komponen/model pendukung saat mendistribusikan.
