# PRD — Ekosistem POPSNAP (Photobox + Kasir + Galeri + Remote)

Dokumen alur dan kebutuhan produk, ditulis dari pembacaan kode per 25 Nov 2026 (kasir `b3ec695`, photobox `e94b8d3`,
galeri `034ed16`, remote `66d9219`). Bila ada beda dengan kode, **kode yang benar**; perbarui dokumen ini.
Bagian yang saya tidak yakin diberi tanda **[verifikasi]**.

## 1. Ringkasan produk

POPSNAP menjalankan usaha photobooth: tamu berfoto di booth, membayar, mendapat hasil cetak dan tautan unduhan digital.
Pemilik/CS mengelola penjualan, shift kasir, laporan, dan booth dari jauh.

| Komponen | Peran | Teknologi | Tempat jalan |
|---|---|---|---|
| **Photobox (booth)** | Layar tamu: pilih template, bayar, jepret, edit, cetak, QR unduhan. Admin booth lokal. | Electron + Express (Node ≥ 22.5, `node:sqlite`) + React/Vite | PC booth (Windows `.exe`; Linux `.deb`/`.tar.gz`) |
| **Kasir** | Penjualan toko (POS), shift kasir, stok, promo, pelanggan/loyalti, laporan; menerima riwayat transaksi dari booth. | Laravel 12 + Inertia/React | Server web |
| **Galeri** | Halaman unduhan tamu `/s/<kode>`; file di S3-kompatibel (R2/B2/S3); admin galeri, unggah manual. | Node/Express, Docker | VPS |
| **Remote** | Memantau dan mengatur banyak booth dari jauh (status, setelan, kamera). | Node/Express, Docker | VPS |
| *(terpisah)* **popsnap-paygate** | dslrBooth/payment-gate. **Tidak digabung** ke app ini. | — | — |

## 2. Peran pengguna

- **Tamu**: memakai layar booth, memindai QR unduhan.
- **Operator/CS booth**: mengurus booth di lokasi, unggah manual galeri, cetak ulang manual.
- **Kasir**: membuka/menutup shift, membuat transaksi toko, menjual voucher sesi.
- **Pemilik/Admin**: laporan, promo, studio/booth, pengguna dan hak akses (Spatie), force-close shift.
- **IT**: pasang/update booth (Windows/Linux), Remote, galeri.

## 3. Peta sistem dan aliran data

```
Tamu ─▶ [Photobox booth] ──(riwayat transaksi, heartbeat; Bearer token studio)──▶ [Kasir]
             │  │                                                                    ▲  │
             │  └──(unggah file langsung ke S3 lewat URL bertanda tangan)──▶ [Galeri]│  │ status sesi
             │                                         ▲  token studio diperiksa ke Kasir │ (/api/v1/status)
             └──(agen booth ↔ perintah/hasil JSON)──▶ [Remote]                       └──┘ unggah manual ditarik
```

- Token studio dibuat di Kasir (menu Transaksi Photobox › Studio) dan dipakai booth untuk sinkron ke Kasir **dan** autentikasi ke Galeri.
- Galeri tidak menyimpan file di VPS; file langsung ke cloud S3 lewat URL bertanda tangan (banyak bucket, berprioritas, file dihapus otomatis setelah N hari).
- Remote hanya membawa JSON kecil (perintah ≤ 512 KB, hasil ≤ 2 MB) — bukan file besar.

## 4. Alur utama

### 4.1 Sesi photobox (booth)

1. **Start** → pilih langkah menurut **alur yang dikonfigurasi** (`general.flow.steps`): `disclaimer`, `template`, `payment`, `consent`, `reminder`
   dapat diurutkan bebas. **Capture → Editor → Result** selalu tetap di akhir.
2. **Pembayaran** (`mode`): `voucher` (bayar di kasir/offline) · `qris` (gateway DOKU) · `both` · `auto` (QRIS saat online, voucher saat offline) ·
   `none` (tanpa bayar di booth; tetap tercatat di Kasir sebagai metode "free", Rp 0).
3. **Bayar dulu** (opsi): hanya aktif bila semua template yang tampil punya harga **sama dan > 0** (harga tetap per sesi, `flatPriceInfo()`).
   Bila tidak terpenuhi, alur kembali ke urutan biasa (pembayaran setelah template).
4. **Voucher**: kode bebas karakter (huruf, angka, spasi, `-`, simbol), maks 40 karakter; divalidasi ke Kasir/cache offline.
5. **Jepret** (webcam, atau DSLR lewat SDK Canon/Nikon di Windows / gPhoto2 di Linux) → **Editor** (filter, stiker) → **Result**:
   cetak, QR unduh, **Add Print**.
6. Booth mengunggah hasil (final, GIF, video/Live, klip, mentah) ke Galeri; QR menunjuk `/s/<kode>`.
7. Riwayat transaksi dikirim ke Kasir (idempoten per `order_no`; status `paid` tidak bisa mundur).

**Add Print**: pilih jumlah (1–9) → bila harga > 0 muncul QRIS (kedaluwarsa 120 detik, cek status tiap 2 detik) → setelah lunas, cetak otomatis;
harga 0 → langsung cetak. Tercatat di Kasir sebagai `addprint`. Teks status kecil "Mencetak… / Dikirim ke printer ✓" (artinya terkirim, bukan kertas sudah keluar).

### 4.2 Shift kasir dan laporan

1. Kasir membuka shift (modal awal, catatan, **toko dan/atau studio photobox yang dipegang** — kosong = semua studio). Satu kasir hanya satu shift aktif.
2. Transaksi toko dan retur selalu ke shift aktif **milik pembuatnya**. Daftar transaksi/retur non-superadmin hanya milik sendiri.
3. **Transaksi photobox** tidak punya kasir; dikaitkan ke shift lewat **waktu bayar + studio** (aturan di §5.2).
4. Tutup shift: hitung kas yang diharapkan vs aktual, selisih, total penjualan toko dan photobox; angka dibekukan saat tutup.
   Force-close oleh superadmin/hak `cashier-shifts-force-close` (butuh konfirmasi password).
5. **Email laporan tutup kasir**: ringkasan toko, photobox (hanya `amount > 0`; sesi voucher Rp 0 **tidak** masuk), rekap item, pengeluaran, studio.

### 4.3 Galeri

- Tamu scan QR → `/s/<kode>`: hasil template (atas) dan foto mentah + klip (bawah); unduh satu per satu, ZIP, atau keduanya (pengaturan admin).
  "Unduh semua" memakai retry dan daftar cadangan untuk file yang belum terunduh.
- **Unggah otomatis (booth)**: dikerjakan booth, dicoba ulang di latar bila internet putus.
- **Unggah manual (CS)**: admin galeri → Unggah manual (pilih folder hasil). Dipakai bila booth gagal mengunggah. Bila folder membawa
  `popsnap-share.json`/nomor transaksi yang sudah ada, ikut link yang sama; bila booth menyusul, booth mengambil alih sesi (file `ok` dilewati, tidak dobel).
- Admin galeri: Link pelanggan (filter **Sumber**: booth/manual; dialog Atur link memuat daftar file + sumber), bucket penyimpanan, studio, pengguna dan hak.
- Kasir menandai "Belum terupload" / "Sudah terupload" per sesi lewat `/api/v1/status` galeri (tidak membedakan sumber).

### 4.4 Remote

- Daftar booth, status online, setelan yang boleh diubah dari jauh (daftar sah: `spec.js` = salinan `backend/remoteSpec.js`).
- **Kamera**: Pengaturan › Kamera › **Profil Live & Foto** (ISO/Tv/Av/WB terpisah bila "Pisahkan setelan Live View dan Foto" nyala; jadwal siang/malam
  menimpa profil dasar dalam periodenya). Tab Alat hanya "Cek kamera". Kode kamera berbeda Windows vs Linux → baca ulang dari kamera.

## 5. Aturan bisnis penting

### 5.1 Harga/voucher
- Voucher sesi dijual di Kasir; booth menyimpan daftar yang boleh dipakai (cache offline). Voucher sesi bernilai Rp 0 muncul di sesi photobox tetapi **tidak** di rekap uang.
- Pembayaran booth ke gateway (DOKU) dan pembayaran toko di Kasir (Xendit/Midtrans/QRIS statis/bank) adalah jalur terpisah.

### 5.2 Kepemilikan transaksi photobox oleh shift (kasir)
Tiap transaksi `paid`, asli (bukan TEST), `paid_at` dalam jendela shift, punya tepat satu pemilik:
1. studio dikelompokkan ke **toko** (`photobox_stores`; studio boleh tanpa toko). Shift punya salah satu cakupan: studio tertentu (rank 0), satu toko penuh termasuk studio yang ditambah kemudian (rank 1), atau semua studio (rank 2; semua shift lama);
2. cakupan yang lebih spesifik menang: studio tertentu > toko > semua studio;
3. antar shift setingkat: dibuka paling awal menang (seri: id terkecil);
4. shift lupa-tutup tidak mengklaim melewati 48 jam (`MAX_SHIFT_HOURS`).
**Konsekuensi:** studio/toko tanpa shift yang memegangnya (dan tanpa shift "semua studio") → penjualannya tidak masuk laporan shift mana pun.

**Hak lihat riwayat photobox:** admin (superadmin, role `admin`, atau izin `cashier-shifts-force-close`; izin `photobox-manage` TIDAK cukup) melihat semua toko dan studio (filter Toko/Studio); kasir melihat transaksi dari semua shift miliknya (aktif dan yang sudah ditutup) untuk toko/studio dan rentang waktu masing-masing shift, dan tidak bisa mengubah/menghapus transaksi di luar cakupan.

### 5.3 Data pelanggan
Pelanggan, poin loyalti, kredit, voucher, segmen = milik **toko**, bukan per kasir/studio. Transaksi mencatat kasirnya, sehingga laporan dapat difilter tanpa memecah data.

### 5.4 Keamanan dan data
- Booth ↔ Kasir: `Authorization: Bearer <token studio>` (token di-hash di DB Kasir; tampil sekali).
- Admin galeri: login + hak per area (`link.view`, `link.edit`, `upload`, `storage.view`, …).
- Linux: data booth di `/var/lib/popsnap` **tidak pernah dihapus** oleh pemasang; pemasangan membuat cadangan DB; downgrade ditolak kecuali dipaksa.
- Rahasia (kunci gateway, S3, API) tidak dikirim ke Remote.

## 6. Keterbatasan dan celah yang diketahui

| # | Keterbatasan | Dampak | Usulan |
|---|---|---|---|
| 1 | Add Print: gagal cetak **setelah bayar** tanpa tombol cetak ulang | CS harus mencetak manual | Tombol "Cetak ulang" + log |
| 2 | Add Print: layar ditutup di tengah pembayaran → pengecekan status berhenti **[verifikasi: ada rekonsiliasi backend?]** | Uang masuk tanpa cetakan | Cek pembayaran tertinggal |
| 3 | Kunci sesi unggahan GIF/klip/Live di memori | Restart backend → unggahan sesi aktif ditolak (kasus PB-1990-…-0008) | Simpan di DB + log penolakan |
| 4 | Studio tanpa shift pemegang (kasir) | Penjualan tak masuk laporan | Peringatan di dashboard / satu shift "semua studio" |
| 5 | Cakupan toko/studio shift tidak bisa diubah setelah dibuka | Salah pilih → tutup & buka ulang | Edit cakupan pada shift terbuka (hak khusus) |
| 6 | Kamera DSLR di Linux belum terbukti (Canon 1100D via gPhoto2; Nikon belum ada bridge) | Linux = webcam saja sampai diuji | Uji di booth + bridge Nikon |
| 7 | Dua aplikasi kamera tidak bisa jalan bersamaan (mis. CamCo/PM2 vs POPSNAP) | Kamera "busy" | Skrip ganti aplikasi, satu autostart |
| 8 | Import/export template via Remote belum ada | Salin template antar booth manual | Endpoint unggah besar + logika zip di backend |
| 9 | Kamera tidak pernah diputus saat idle | Panas/boros daya | Mode siaga/tidur bertingkat (saklar default mati) |
| 10 | Penyebab "Unduh semua hanya sebagian" belum terkonfirmasi | Keluhan tamu | Kumpulkan jumlah file, perangkat, log |

## 7. Kebutuhan non-fungsional

- **Ketahanan offline**: booth tetap bisa jalan tanpa internet (voucher cache, antrean sinkron, unggahan dicoba ulang); sinkron idempoten.
- **Kinerja**: layar tamu responsif; live view kamera ber-jeda henti (~2 menit) agar tidak siklus hidup-mati cepat.
- **Keandalan data**: transaksi photobox tidak boleh dobel (`order_no` unik); angka shift tertutup dibekukan.
- **Operasional**: update tanpa kehilangan data booth; log dan `popsnap-doctor` untuk diagnosis di Linux.
- **Kompatibilitas**: Windows (exe, SDK Canon/Nikon, digiCamControl, dslrBooth bukan bagian repo ini); Linux (Ubuntu/Debian, Chrome `.deb`, bukan Chromium snap).

## 8. Konfigurasi/deploy singkat

| Komponen | Konfigurasi utama | Deploy |
|---|---|---|
| Kasir | `.env` (DB, gateway Xendit/Midtrans, `POPSNAP_GALLERY_URL`, `POPSNAP_GALLERY_KEY`), `php artisan migrate`, `npm run build` | server web biasa |
| Galeri | `.env` (`PUBLIC_URL`, `ADMIN_PASSWORD`, `KASIR_URL`), bucket di admin | `docker compose up -d --build` di VPS (port 127.0.0.1:8088 + reverse proxy) |
| Remote | `docker-compose.yml`, domain (mis. DuckDNS) | `docker compose up -d --build` |
| Booth Windows | `build-exe.bat` / `start-popsnap.bat` | exe Electron |
| Booth Linux | `.deb` (`sudo apt install ./popsnap_X_amd64.deb`), `popsnap-doctor`, kiosk Chrome | `systemd` layanan `popsnap` + autostart kiosk |

## 9. Peta jalan yang diusulkan (belum disetujui)

1. Tutup celah Add Print (#1, #2) dan unggahan sesi (#3).
2. Uji Linux dengan kamera asli; bridge Nikon gPhoto2.
3. Peringatan "studio tanpa shift" di dashboard Kasir; edit studio pada shift terbuka.
4. Import/export template + setelan tambahan di Remote.
5. Kamera idle bertingkat.
