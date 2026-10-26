# Integrasi Photobox (Transaksi Photobox)

Kembali ke indeks dokumentasi: `docs/README.md`

## Tujuan
Menerima riwayat transaksi dari app Photobox (booth) tiap studio. Nomor transaksi **dibuat di booth** (`PB-<KODE STUDIO>-<YYMMDD>-<URUT>`, mis. `PB-EMMA-261022-0007`) dan dipakai apa adanya di kasir, jadi sama di kedua app.

## Halaman & permission
- `Transaksi Photobox` (`/dashboard/photobox-transactions`) — permission `photobox-access`: filter studio/periode/metode/status, ringkasan pendapatan & produk (sesi) terjual per studio dan per template.
- Kelola studio & token — permission `photobox-manage`.
- Menu ini ada di grup **Sales**, tepat di bawah Riwayat Transaksi. Riwayat Transaksi = detail toko; Transaksi Photobox = detail studio.
- Dashboard utama **menggabungkan** toko + photobox (butuh `photobox-access`; tanpa izin itu dashboard hanya toko): Penjualan, Transaksi, Target Bulan Ini, Tren Pendapatan, dan Metode Pembayaran (QRIS booth masuk ke QRIS). Kartu "Pendapatan per Sumber" merinci kasir toko + tiap studio dengan tautan Detail ke menu masing-masing.
- Profit tetap hanya toko (photobox belum punya data modal). Produk terlaris/pelanggan terbaik tetap data toko.
- "Transaksi" photobox = sesi yang membayar di booth (nominal > 0). Sesi voucher (nominal 0) tidak ikut, karena sudah tercatat sebagai transaksi kasir saat voucher dibeli.

## Cara menyambungkan satu studio
1. Jalankan `php artisan migrate` (membuat tabel `photobox_studios`, `photobox_transactions` dan permission).
2. Menu **Transaksi Photobox** > Studio > tambah studio. **Token tampil sekali** (`pbx_...`), salin.
3. Di app booth: Admin > Pembayaran > *Kasir online*: isi alamat kasir, token, kode studio (unik per studio), aktifkan, lalu **Tes koneksi**.

## API (auth: `Authorization: Bearer <token studio>`)
- `GET  /api/photobox/ping` → `{ok, studio}`
- `POST /api/photobox/transactions` — body `{transactions: [ ... maks 100 ]}`; tiap item: `order_no, method (qris|voucher|free), amount, status (pending|paid|failed|expired|refunded), template_id, template_name, category, mode, voucher_code, paid_at, created_at, session_id, meta`.
  - Idempoten per `order_no`; status `paid` tidak bisa turun; nomor yang sudah dipakai studio lain ditolak.

## Aturan hitung
- Hanya status `paid` yang dihitung. Pendapatan = jumlah `amount`.
- QRIS di booth: `amount` = harga. Voucher yang dibayar di kasir: `amount` 0 (pendapatan sudah ada di transaksi kasir), tetap dihitung sebagai sesi/produk terjual. Di booth ada opsi "Hitung voucher sebagai pendapatan photobox" untuk kasus voucher tanpa transaksi kasir.
- `meta.kasir_reference` berisi nomor transaksi kasir (`reference` voucher) kalau voucher berasal dari kasir.

## Uji
`php vendor/bin/phpunit tests/Feature/Photobox` (migrasi lama proyek khusus MySQL, jadi tes ini hanya menjalankan migrasi Photobox).
