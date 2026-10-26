# Changelog — Popsnap Kasir (gabungan semua sesi)

## ⚠️ PENTING — baca dulu

Semua file di zip ini dari lokasi **ASLI** yang beneran dipakai Laravel
(`app/`, bukan `app/app/`). Kalau project kamu masih punya folder
`app/app/` (duplikat lengkap tapi mati, nggak pernah ke-load Laravel
karena composer.json cuma baca `App\ -> app/`) — **hapus folder itu**,
aman. Saya sudah hapus di salinan kerja saya sendiri.

## Cara pasang

1. Copy semua file di zip ini ke project kamu (timpa yang lama), sesuai
   path relatifnya.
2. Hapus folder `app/app/` di project kamu kalau masih ada.
3. `php artisan migrate` (2 migration baru: `allow_zero_price`,
   `usage_limit_mode` — `applies_to_qty` & `payment_method_restriction`
   sudah ada duluan).
4. Test alur promo + diskon di kasir sebelum dipakai transaksi asli.

---

## Ringkasan fitur/perbaikan

### Perhitungan diskon
- **Bug utama**: voucher (Panjat Promo) dulu motong SELURUH subtotal
  keranjang, bukan cuma barang yang menang. Sekarang di-scope ke
  target_type (produk/kategori/semua).
- **Successive stacking**: 2+ voucher numpuk di barang yang sama sekarang
  dihitung berturut-turut dari SISA barang itu (bukan dari harga awal
  tiap kali) — mencegah kombinasi diskon besar (mis. 81%+45%) dianggap
  >100% dan bikin barang jadi Rp0 nggak sengaja.
- **Toggle `allow_zero_price`**: default barang nggak boleh sampai Rp0
  (nyisa minimal Rp1), bisa diizinkan per-promo kalau memang sengaja.
- **`applies_to_qty`** ("1 kode = N paket"): 3 lapis bug (nggak ada di
  fillable, nggak divalidasi, nggak di whitelist controller) — semua
  sudah dibenerin.
- **Diskon manual PER ITEM (baru)**: kasir bisa kasih diskon Rp/% ke 1
  barang spesifik di keranjang (tombol ikon tag di tiap baris), terpisah
  dari "Diskon Manual" yang motong total transaksi. Urutan hitung
  lengkap: promo otomatis → voucher → diskon manual per item → redeem
  poin → diskon manual transaksi.
- Basis "Diskon Manual" transaksi (mode Rp/%) dibenerin — dulu salah
  basis kalau ada voucher aktif.

### Struk & riwayat
- `transaction_details.discount_total` & `promo_id` sekarang mencatat
  voucher manual juga (dulu cuma promo otomatis) — dan sekarang juga
  mencatat diskon manual per item.
- Nama promo/voucher per item ikut tersimpan & tampil di struk (semua
  ukuran: 80mm, 58mm — sebelumnya 58mm beda & lebih minim dari 80mm,
  sudah disamakan) dan di modal detail riwayat transaksi.
- Riwayat transaksi (`History.jsx`): kolom Total sekarang nunjukin info
  diskon di bawahnya.
- **Riwayat pelanggan** (`Customers/Show.jsx`): section "Transaksi
  Terakhir" (sudah ada sebelumnya, limit 5) sekarang nunjukin rincian
  diskon & nama voucher per transaksi, limit dinaikkan ke 10.

### Promo — fitur baru
- **Halaman detail pemakaian** (`Promos/Usage.jsx`): daftar semua
  transaksi yang pakai kode tertentu, per transaksi (invoice, tanggal,
  pelanggan, kasir, barang, potongan, total, link struk).
- **Tombol Duplikat promo**: kloning promo + qty breaks/bundle/buy-get
  items, kode baru di-generate, statistik direset, hasilnya nonaktif
  dulu (draft).
- **Validasi "1x per pelanggan"** (2 mode): "Per Transaksi" (default,
  boleh berkali-kali) vs "1x per Pelanggan" (begitu pernah dipakai
  pelanggan itu, kode hilang dari daftar & ditolak kalau dipaksa).

---

## Diketahui, belum diputuskan/dikerjakan

- **Celah dual-payment**: voucher "hanya cashless" masih lolos kalau
  bayarnya split cash+QRIS (dianggap cashless sepenuhnya). Belum
  diperbaiki — tunggu keputusan kamu apakah itu boleh atau harus
  ditolak juga.

---

## Catatan jujur

Semua perubahan **belum pernah dijalankan** di PHP beneran (sandbox
kerja saya nggak ada PHP terpasang) — cuma dicek manual: balance kurung
`{}`/`()`, dan baca ulang tiap fungsi satu-satu. Tolong jalankan
`php artisan migrate` + test menyeluruh (idealnya di staging dulu)
sebelum dipakai transaksi asli.
