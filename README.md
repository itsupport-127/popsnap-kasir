# Popsnap POS

Sistem kasir berbasis Laravel 12 + Inertia 2 + React untuk operasional toko foto **Popsnap Studio** — mencakup transaksi penjualan, customer management, inventory audit, sales return, cashier shift, receivables, payables, payment gateway (QRIS, Midtrans, Xendit, DOKU, Flip), dan observability operasional.

## Daftar Isi

- [Tentang Aplikasi](#tentang-aplikasi)
- [What's New](#whats-new)
- [Fitur Utama](#fitur-utama)
- [Teknologi Inti](#teknologi-inti)
- [Quick Start](#quick-start)
- [Konfigurasi Awal](#konfigurasi-awal)
- [Default Login](#default-login)
- [Dokumentasi Detail](#dokumentasi-detail)
- [Peta Modul Utama](#peta-modul-utama)
- [Testing](#testing)
- [Troubleshooting Umum](#troubleshooting-umum)

## Tentang Aplikasi

Repo ini adalah sistem POS yang dibangun khusus untuk operasional **Popsnap Studio**, bisnis photobox di Yogyakarta. Modul yang tersedia:

- POS & checkout multi-metode pembayaran (Cash, Transfer Bank, QRIS Statis, DOKU QRIS, Flip.id, Midtrans, Xendit)
- Customer management + loyalty points + wilayah Indonesia
- Receivables / piutang pelanggan
- Payables / hutang supplier
- Stock opname + stock mutation
- Sales return
- Cashier shift
- Audit log
- Laporan dan dokumen cetak

## What's New

- **QRIS Statis** — upload gambar QR dari bank/e-wallet, kasir tampilkan ke pelanggan, konfirmasi manual
- **DOKU QRIS Dinamis** — QR code otomatis per transaksi via DOKU payment gateway
- **Flip.id** — payment link otomatis via Flip.id
- Modal konfirmasi pembayaran QRIS sebelum transaksi final disubmit
- Hutang & piutang untuk pelanggan dan supplier
- Sales return terhubung ke stok, profit, dan receivable
- Stock opname dan stock mutation untuk audit inventory
- Cashier shift dengan kontrol shift aktif pada operasi transaksi
- Audit log untuk jejak perubahan operasional
- Payment workflow: cash, transfer bank, QRIS, DOKU, Flip, Midtrans, Xendit, dan pay later

## Fitur Utama

### POS & Transaksi

- Pencarian produk via barcode / keyword
- Cart multi-item dengan hold / resume transaksi
- Checkout multi-metode pembayaran
- Modal QRIS — tampilkan QR sebelum konfirmasi transaksi
- Share invoice publik dan dokumen print

### Payment Gateway

| Metode        | Konfirmasi         | Biaya  |
| ------------- | ------------------ | ------ |
| Cash          | Otomatis           | -      |
| Transfer Bank | Manual             | -      |
| QRIS Statis   | Manual kasir       | Gratis |
| DOKU QRIS     | Otomatis (webhook) | 0.7%   |
| Flip.id       | Otomatis (webhook) | 0.7%   |
| Midtrans      | Otomatis (webhook) | 0.7–1% |
| Xendit        | Otomatis (webhook) | 0.7–1% |

### Customer & Loyalitas

- CRUD customer dengan alamat wilayah Indonesia
- Loyalty points & tier (regular, silver, gold, platinum)
- Customer voucher & history transaksi

### Inventory & Operasional

- Product management (kategori, barcode, stok)
- Stock opname & stock mutation
- Sales return
- Cashier shift
- Low stock notification

### Keuangan

- Receivables / piutang pelanggan
- Payables / hutang supplier
- Supplier management
- Payment gateway settings
- Bank account management

### Admin & Observability

- Users, roles, permissions (RBAC granular)
- Audit logs
- Sales report & profit report
- Dokumen PDF operasional

## Teknologi Inti

- [Laravel 12](https://laravel.com)
- [Inertia.js 2](https://inertiajs.com) + [React](https://react.dev)
- [Tailwind CSS v4](https://tailwindcss.com)
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)
- [Laravolt Indonesia](https://github.com/laravolt/indonesia)
- [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf)
- [picqer/php-barcode-generator](https://github.com/picqer/php-barcode-generator)
- Payment: QRIS Statis, DOKU, Flip.id, Midtrans, Xendit

## Quick Start

```bash
git clone https://github.com/itsupport-127/Popsnap-Kasir.git
cd Popsnap-Kasir
cp .env.example .env

composer install
npm install

php artisan key:generate
php artisan migrate --seed
php artisan storage:link

npm run dev
php artisan serve
```

Untuk akses online menggunakan Cloudflare Tunnel (gratis):

```bash
cloudflared tunnel login
cloudflared tunnel create popsnap
cloudflared tunnel run popsnap
```

## Konfigurasi Awal

Setelah login pertama kali, lakukan konfigurasi berikut:

1. Lengkapi profil toko di **Settings → Toko**
2. Atur payment gateway di **Settings → Payment**
    - Upload gambar QRIS toko jika pakai QRIS Statis
    - Isi Client ID & Secret Key jika pakai DOKU/Flip/Midtrans/Xendit
3. Tambah rekening bank aktif jika pakai transfer manual
4. Isi target penjualan bulanan di **Settings → Target**
5. Pastikan `APP_URL` valid jika memakai webhook payment gateway

### Konfigurasi .env penting

```env
APP_URL=https://domain-kamu.com   # wajib untuk webhook payment
DB_CONNECTION=mysql
DB_DATABASE=point_of_sales
DB_USERNAME=root
DB_PASSWORD=
```

## Default Login

| Role                | Email               | Password   |
| ------------------- | ------------------- | ---------- |
| Admin / Super Admin | `admin@gmail.com`   | `password` |
| Kasir               | `cashier@gmail.com` | `password` |

> Seeder akan membuat roles, permissions, user default, sample data produk Popsnap, customer, supplier, transaksi, receivable, dan payable secara otomatis.

## Dokumentasi Detail

- Getting started: `docs/getting-started.md`
- Gambaran arsitektur: `docs/architecture-overview.md`
- Konfigurasi aplikasi: `docs/configuration.md`
- POS & transaksi: `docs/features/pos-transactions.md`
- Customer & wilayah: `docs/features/customers-regions.md`
- Receivables: `docs/features/receivables.md`
- Payables & suppliers: `docs/features/payables-suppliers.md`
- Inventory & stock: `docs/features/inventory-stock.md`
- Sales returns: `docs/features/sales-returns.md`
- Cashier shifts: `docs/features/cashier-shifts.md`
- Audit logs: `docs/features/audit-logs.md`
- Settings & payments: `docs/features/settings-payments.md`
- RBAC, users, roles: `docs/features/rbac-users-roles.md`
- Reports & documents: `docs/features/reports-documents.md`

## Peta Modul Utama

```
dashboard/transactions          — POS, cart, hold/resume, checkout, print
dashboard/transactions/history  — histori transaksi & entry point sales return
dashboard/customers             — customer management dan data wilayah
dashboard/receivables           — piutang pelanggan
dashboard/suppliers             — supplier management
dashboard/payables              — hutang supplier
dashboard/stock-opnames         — stock opname
dashboard/stock-mutations       — histori mutasi stok
dashboard/sales-returns         — retur penjualan
dashboard/cashier-shifts        — buka/tutup shift kasir
dashboard/audit-logs            — observability aktivitas user
dashboard/settings/store        — profil toko
dashboard/settings/payments     — payment gateway (QRIS, DOKU, Flip, Midtrans, Xendit)
dashboard/settings/bank-accounts — rekening bank transfer
```

## Testing

```bash
php artisan test
php artisan test --filter=StockOpnameTest
php artisan test --filter=SalesReturnTest
php artisan test --filter=AuditLogTest
```

## Troubleshooting Umum

### Permission baru tidak terbaca setelah seeding

```bash
php artisan db:seed
```

Logout lalu login ulang. Pastikan seeder permission/role sudah reset cache.

### Gambar QRIS tidak tampil di modal transaksi

Pastikan storage link sudah dibuat dan gambar sudah diupload di **Settings → Payment**:

```bash
php artisan storage:link
```

### Webhook payment gateway tidak terpanggil

- Pastikan `APP_URL` di `.env` bukan `localhost`
- Gunakan Cloudflare Tunnel atau ngrok untuk expose ke internet
- Daftarkan webhook URL di dashboard masing-masing gateway:
    - Midtrans: `https://domain.kamu/api/webhooks/midtrans`
    - Xendit: `https://domain.kamu/api/webhooks/xendit`
    - DOKU: `https://domain.kamu/api/webhooks/doku`
    - Flip: `https://domain.kamu/api/webhooks/flip`

### Route error karena tabel belum ada

```bash
php artisan migrate
```

### Reset database dari awal

```bash
php artisan migrate:fresh --seed
```
