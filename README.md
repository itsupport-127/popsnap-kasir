# Bug: Detail Item salah label diskon per barang

## Yang dilaporkan
Transaksi TRX-8994TMAXET pakai 2 voucher sekaligus: PANJAT8100 (potongan Rp8.100) dan PANJAT1700 (potongan Rp1.700), total potongan Rp9.800 — angka totalnya BENAR. Tapi di modal "Detail Item", KEDUA barang (1990 SnapBox & Vintage SnapBox) sama-sama nampilin:
- Label "Panjat Promo Agustus - Potongan 1700" (padahal salah satu barang mestinya juga kena kontribusi PANJAT8100)
- "Diskon -Rp4.900" per barang
- DAN di ringkasan bawah ada 2 baris yang sama-sama nunjukin -Rp9.800: "Diskon Promo" dan "Voucher (...)"

## Root cause (3 bug, saling terkait)

**1. Angka Rp4.900 per barang itu SEBENARNYA BENAR** — bukan bug. Kedua voucher (Total Transaksi scope) menyebar proporsional ke 2 barang yang harganya sama (Rp35.000 masing-masing), jadi Rp9.800 dibagi rata Rp4.900/barang. Ini emang didesain begitu.

**2. Bug label**: kode di `LoyaltyService.php` yang mencatat "voucher mana yang nempel ke barang mana" cuma nyimpen 1 nama per barang — jadi kalau 2 voucher SAMA-SAMA nyumbang ke barang yang sama (kasus di atas), nama voucher yang diproses BELAKANGAN (PANJAT1700) menimpa nama yang diproses duluan (PANJAT8100). Barang itu kelihatannya cuma kena 1 kode padahal 2. **Sudah diperbaiki** — sekarang nama voucher digabung (bukan ditimpa) kalau lebih dari 1 kode nyumbang ke barang yang sama.

**3. Bug dobel-hitung di ringkasan**: kolom database `voucher_discount` dan `item_manual_discount` di `transaction_details` itu SELALU 0 — bukan karena datanya salah, tapi karena `TransactionController::store()` nggak pernah nulis nilai ke situ (cuma nulis ke kolom gabungan `discount_total`). Akibatnya, komponen "Detail Item" & struk yang coba misahin "diskon promo" vs "diskon voucher" pakai kolom yang isinya 0 terus, jadi salah nganggep SEMUA potongan itu "Diskon Promo" — padahal aslinya 100% dari voucher. Makanya "Diskon Promo" dan "Voucher" nampilin angka yang sama. **Sudah diperbaiki** — kolom itu sekarang beneran keisi sesuai porsinya.

## File yang diubah
- `app/Services/LoyaltyService.php` — nama voucher per barang digabung (bukan ditimpa).
- `app/Http/Controllers/Apps/TransactionController.php` — kolom `voucher_discount` & `item_manual_discount` sekarang beneran ditulis, dan label nama voucher gabungan di-join dengan koma.
- `resources/js/Components/Dashboard/TransactionItemsModal.jsx` — "Diskon Promo" sekarang dihitung dari porsi promo OTOMATIS murni (bukan gabungan), jadi nggak dobel sama baris "Voucher". Ditambah baris "Diskon Per Item" juga.

## PENTING sebelum deploy
- **Transaksi LAMA** (yang sudah kejadian sebelum fix ini, termasuk TRX-8994TMAXET) — kolom `voucher_discount`/`item_manual_discount`-nya akan TETAP 0 di database (fix ini cuma berlaku ke transaksi BARU setelah dideploy). Kalau mau riwayat lama juga tampil benar, perlu skrip backfill terpisah — kasih tau kalau mau saya buatkan.
- Belum saya jalankan test — cek dulu di staging sebelum dipakai transaksi asli beneran.
