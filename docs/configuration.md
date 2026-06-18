# Configuration

Kembali ke indeks dokumentasi: `docs/README.md`

## Tujuan

Dokumen ini merangkum konfigurasi yang perlu diperhatikan setelah aplikasi berhasil dijalankan.

## Environment Penting

Perhatikan minimal:

- `APP_URL`
- konfigurasi database
- kredensial Midtrans
- kredensial Xendit
- `XENDIT_CALLBACK_TOKEN`

## APP_URL

`APP_URL` penting untuk:

- share invoice publik
- generate webhook URL
- integrasi payment gateway

Jika masih `localhost`, webhook eksternal tidak akan bisa menjangkau aplikasi.

## Payment Gateway

Konfigurasi dilakukan dari:

- `dashboard/settings/payments`

Gateway yang saat ini didukung:

- cash
- bank transfer
- Midtrans
- Xendit

Validasi penting:

- Midtrans aktif butuh `server key` dan `client key`
- Xendit aktif butuh `secret key`
- Xendit juga butuh `callback token`
- default gateway harus mengarah ke gateway yang aktif

## Webhook

Endpoint:

- Midtrans: `/api/webhooks/midtrans`
- Xendit: `/api/webhooks/xendit`

Checklist:

1. set `APP_URL` ke URL publik
2. copy webhook URL dari halaman payment settings
3. daftarkan di dashboard provider
4. pastikan token callback Xendit cocok

## Bank Accounts

Konfigurasi dilakukan dari:

- `dashboard/settings/bank-accounts`

Dipakai untuk:

- transfer manual saat checkout
- pencatatan pembayaran piutang
- pencatatan pembayaran hutang supplier

## Profil Toko

Konfigurasi dilakukan dari:

- `dashboard/settings/store`

Data ini dipakai pada dokumen dan tampilan publik:

- nama toko
- logo
- alamat
- kontak
- kota

## Target Penjualan

Konfigurasi dilakukan dari:

- `dashboard/settings/target`

Dipakai oleh dashboard untuk menampilkan progress target bulanan.

## Catatan Dependency Eksternal

- wilayah customer memakai `laravolt/indonesia`
- PDF memakai DomPDF
- barcode dokumen dibuat dinamis
- payment gateway butuh konektivitas publik untuk webhook
