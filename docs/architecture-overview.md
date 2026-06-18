# Architecture Overview

Kembali ke indeks dokumentasi: `docs/README.md`

## Stack

- backend: Laravel
- transport web: Inertia.js
- frontend dashboard: React
- styling: Tailwind CSS
- authorization: Spatie Laravel Permission

## Struktur Area Penting

- `routes/web.php` — surface route dashboard dan permission middleware
- `app/Http/Controllers/Apps` — controller modul dashboard
- `resources/js/Pages/Dashboard` — halaman Inertia per modul
- `app/Http/Middleware/HandleInertiaRequests.php` — shared props global seperti auth, permissions, notifications, store profile
- `database/seeders` — permission, role, user, sample data
- `app/Services` — service logic lintas controller seperti stock mutation, cashier shift, audit log

## Alur Request Umum

1. route dashboard diproteksi `auth` dan permission middleware
2. controller menyiapkan data
3. Inertia merender page React di `resources/js/Pages/Dashboard`
4. permission user dishare ke frontend lewat middleware Inertia

## Middleware Penting

- `permission` — proteksi akses route berbasis Spatie
- `active_shift` — mewajibkan kasir punya shift aktif untuk aksi transaksi tertentu

`active_shift` dipakai pada:

- search product
- add/update/destroy cart
- hold/resume/clear hold
- store transaksi

## Pola Integrasi Modul

- transaksi menjadi pusat banyak relasi: detail, profit, receivable, sales return
- product menjadi pusat inventory: stock opname item, stock mutation, sales return item
- audit log dipakai untuk mencatat perubahan penting lintas modul

## Pola Dokumentasi Fitur

Setiap modul fitur di `docs/features/` menjelaskan:

- tujuan modul
- route / halaman utama
- permission
- alur user
- integrasi data
- batasan implementasi saat ini
