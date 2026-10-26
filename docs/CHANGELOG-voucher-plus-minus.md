# Fitur +/- Voucher — Case 1 & Case 2

## Cara pakai
Timpa 5 file ini ke project Laravel kamu di path yang sama, lalu jalankan
`php artisan test --filter=PromoTest` (butuh PHP terpasang — sandbox saya
tidak punya PHP jadi saya cuma bisa cek sintaks dengan esbuild/keseimbangan
kurung, belum benar-benar dieksekusi):

- `app/Services/PromoService.php`
- `app/Services/LoyaltyService.php`
- `app/Http/Controllers/Apps/TransactionController.php`
- `resources/js/Pages/Dashboard/Transactions/Index.jsx`
- `tests/Feature/Pricing/PromoTest.php` (3 test baru)

## Apa yang berubah

**PromoService.php**
- `buildVoucherUnitPool()` — pecah keranjang jadi "unit" per pcs.
- `applyStandardVoucherToUnits()` — terapkan SATU kemenangan voucher
  standard_discount (persen/nominal) yang `applies_to_qty`-nya diisi, ke 1
  barang yang belum kepakai voucher lain. Dipanggil sekali per klik "+".
- `serializeManualPromo()` sekarang juga melaporkan `allocated_cart_ids`
  (barang mana yang kena voucher ini).

**LoyaltyService.php (`previewCheckout`)**
- Voucher TIDAK lagi di-dedupe by id — array `vouchers` boleh berisi id yang
  sama berkali-kali (1 entri = 1 kemenangan).
- Tiap kemenangan standard_discount+applies_to_qty dialokasikan lewat unit
  pool di atas → ini yang benerin:
  - **Case 1** (2 kode beda menang bareng): sekarang saling koordinasi,
    tidak dua-duanya numpuk ke barang termurah.
  - **Case 2** (kode sama menang 2x): sekarang beneran terhitung 2x ke 2
    barang berbeda, bukan cuma 1x.
- Ada pengecekan kuota-dalam-transaksi-yang-sama: kalau sisa kuota kode itu
  cuma 1, klik "+" 2x tetap cuma kehitung 1x (tidak menembus `max_uses`).
- Summary sekarang punya `voucher_discount_by_cart_id` untuk breakdown per
  barang.

**TransactionController.php**
- `previewPricing()` & `store()`: perbaikan `whereIn('id', $ids)->get()` yang
  sebelumnya diam-diam dedupe id berulang.
- `used_count` sekarang naik sebanyak jumlah kemenangan yang BENERAN
  tervalidasi (bukan cuma +1 per kode unik) — jadi kode yang menang 2x akan
  motong kuota 2x juga.
- `transaction_details.discount_total` sekarang menggabungkan diskon promo
  otomatis + voucher manual per baris keranjang (pakai
  `voucher_discount_by_cart_id`), jadi struk/riwayat transaksi transparan
  soal barang mana kena voucher mana.

**Index.jsx (kasir)**
- Kartu voucher sekarang punya tombol `− [angka] +` (bukan cuma
  checklist/toggle). Klik "+" = tambah 1 kemenangan, "−" = kurangi.
- Ngetik kode yang sama 2x di kolom "Punya Kode Promo?" sekarang dihitung
  sebagai kemenangan baru, bukan diabaikan.
- Voucher personal (sekali pakai) tetap dibatasi maksimal 1x.

**tests/Feature/Pricing/PromoTest.php** — 3 test baru:
1. `test_two_different_voucher_codes_coordinate_across_separate_units` — Case 1
2. `test_same_voucher_code_won_twice_applies_to_two_different_units` — Case 2
3. `test_voucher_cannot_be_won_more_times_than_its_remaining_quota` — guard kuota

## Update: basis "Diskon Manual" (Rp/%) dibetulkan

**Konteks:** yang ada di gambar screenshot kamu ("Diskon Manual" dengan toggle
Rp/%) itu **diskon level TRANSAKSI/TOTAL**, bukan per item — cuma ada 1
kontrol ini di kasir sekarang, belum ada versi "pilih 1 barang, kasih diskon
cuma ke barang itu".

**Bug yang ketemu & sudah dibenerin:** mode "%" di kontrol ini menghitung
persennya dari subtotal SETELAH promo otomatis doang — padahal urutan
seharusnya (dan yang dipakai backend buat nge-clamp) adalah:

```
subtotal (setelah promo otomatis)
  → dikurangi voucher manual
  → dikurangi redeem poin loyalty
  → SISANYA baru jadi basis diskon manual (Rp atau %)
```

Sebelumnya, kalau customer sudah pakai voucher/poin gede, mode "%" tetap
menghitung dari subtotal yang lebih besar (sebelum potongan itu) — hasilnya
angka diskon manual bisa lebih besar dari yang seharusnya jadi tanggungan
customer, meskipun tetap di-clamp di backend supaya nggak minus. Sekarang
frontend pakai basis yang PERSIS sama dengan backend (`afterLoyalty`):
`subtotal_after_promo − voucher_discount_total − loyalty_discount_total`.

## Ringkasan status alur diskon (jawaban pertanyaan "tinggal apa yg belum")

| Bagian | Status |
|---|---|
| Diskon per-item OTOMATIS (dari promo aktif) | ✅ sudah, dan sudah benar (per item, bukan whole-cart) |
| Diskon per-item dari VOUCHER (kode manual, termasuk menang 2x/kode beda) | ✅ sudah (fitur +/- yang baru dibuat) |
| Diskon TOTAL manual (Rp/%) — yang di screenshot | ✅ sudah ada, basis %-nya baru saja dibetulkan |
| **Diskon per-item MANUAL** (kasir pilih 1 barang spesifik, kasih Rp/%) | ✅ **baru dibangun** — lihat "Update 3" di bawah |
| Urutan hitung item→voucher→manual-item→poin→manual-total | ✅ konsisten frontend & backend |
| Kuota harian promo | ❌ belum (opsi terpisah, nunggu keputusan) |
| 5 fitur planning awal lain (dropdown kode, duplikat promo, dst) | ❌ belum |

## Update 3: Diskon Manual PER ITEM (fitur baru)

Sekarang kasir bisa pilih 1 barang spesifik di keranjang dan kasih diskon
Rp atau % cuma ke barang itu — independen dari promo otomatis, voucher,
maupun diskon manual TOTAL.

**Urutan hitung lengkap sekarang** (ini yang paling penting buat dipahami):

```
1. Harga barang setelah PROMO OTOMATIS (per item, dari promo aktif)
2. Dikurangi VOUCHER manual (per item, termasuk +/- kode sama/beda)
3. Dikurangi DISKON MANUAL PER ITEM (kasir pilih barang, Rp/% baru ini)
   → jumlahkan semua baris jadi 1 subtotal
4. Dikurangi REDEEM POIN loyalty
5. Dikurangi DISKON MANUAL TOTAL (Rp/% di screenshot awal kamu)
6. + Ongkir = Grand Total
```

**Backend:**
- `PromoService::calculateItemManualDiscounts()` — method baru. Basis
  hitungnya SISA harga barang setelah promo otomatis + voucher (bukan harga
  asli), supaya kalau barang itu sudah dapat voucher, diskon manual per-item
  dihitung dari yang beneran masih tersisa.
- `LoyaltyService::previewCheckout()` menerima opsi baru `item_discounts`
  (array `{cart_id, mode, value}`), menyisipkannya di urutan yang benar (poin
  3 di atas), dan mengekspos `item_manual_discount_total` +
  `item_manual_discount_by_cart_id` di summary.
- `TransactionController`: endpoint `previewPricing()` & `store()` menerima
  `item_discounts`; `transaction_details.discount_total` sekarang gabungan
  promo + voucher + manual-per-item.

**Frontend (kasir):**
- Tiap baris keranjang punya tombol ikon tag — klik untuk buka editor Rp/%
  inline khusus barang itu (mirip kontrol Diskon Manual total, tapi versi
  mini per baris), termasuk info "maks Rp..." dan tombol hapus.
- Harga per baris & badge "Diskon" di tiap item sudah mencerminkan
  voucher + diskon manual item digabung.
- Ringkasan struk punya baris baru "Diskon Per Item".
- Basis `manualDiscountBase` (buat diskon manual TOTAL) sudah ikut
  dikurangi `item_manual_discount_total`, supaya diskon TOTAL tetap
  dihitung dari sisa yang BENAR (bukan dobel-hitung).

**Test baru:** `test_manual_item_discount_percent_computes_from_remaining_after_voucher`,
`test_manual_item_discount_nominal_is_clamped_to_remaining_amount`.

## Perbaikan minor
Waktu menambahkan test-test baru di atas, saya sempat salah edit dan tanpa
sengaja menghapus baris deklarasi fungsi `createUserWithPermissions()` di
`PromoTest.php` (function-nya masih ada isinya, cuma signature-nya hilang).
Sudah saya perbaiki dan cek ulang balance kurung + nama semua fungsi di
ketiga file PHP dan file JSX supaya kejadian serupa nggak kelewat lagi.

## Yang masih belum disentuh
- Kuota harian promo ("maks N per hari").
- 5 fitur planning awal lain (dropdown kode di kasir, tombol duplikat promo, dst).


