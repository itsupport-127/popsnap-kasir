# Cashier Shifts

Kembali ke indeks dokumentasi: `docs/README.md`

## Tujuan

Mengontrol sesi kerja kasir agar transaksi kasir memiliki konteks shift yang jelas, termasuk cash opening, cash closing, dan summary operasional.

## Fitur Saat Ini

- open shift
- list shift
- detail shift
- close shift
- force close untuk user berwenang
- ringkasan expected cash, actual cash, selisih, transaksi, dan sales return
- pencatatan pengeluaran (cash yang diambil dari laci, mis. selisih kembalian atau belanja galon) saat closing, dinetkan ke perhitungan selisih kas
- email laporan tutup kasir (otomatis ditawarkan setelah closing, atau dikirim/dikirim ulang manual kapan saja dari halaman shift/histori shift) ke daftar penerima tetap

## Halaman dan Route

- `dashboard/cashier-shifts`
- `cashier-shifts.store`
- `cashier-shifts.show`
- `cashier-shifts.close`
- `cashier-shifts.send-report-email`

## Permission

- `cashier-shifts-access`
- `cashier-shifts-open`
- `cashier-shifts-close` (juga dipakai untuk kirim/kirim ulang email laporan)
- `cashier-shifts-force-close`

## Alur User

1. kasir membuka shift dengan opening cash
2. route transaksi tertentu mewajibkan shift aktif
3. selama shift aktif, transaksi dan sales return tercatat dalam konteks shift
4. saat penutupan, kasir mengisi actual cash, catatan closing, dan opsional pengeluaran + catatan pengeluaran
5. sistem menghitung selisih dan summary shift (selisih sudah memperhitungkan pengeluaran)
6. setelah shift ditutup, sistem menawarkan pengiriman email laporan tutup kasir; jika ditunda, email bisa dikirim manual kapan saja dari halaman detail shift atau histori shift

## Integrasi Data

- `cashier_shifts` (termasuk `expense_total`, `expense_notes`, `report_email_sent_at`, `report_email_sent_to`, `report_email_sent_by`)
- transaksi cash / non-cash
- sales returns
- middleware `active_shift`
- `config/cashier_shift_report.php` (daftar penerima email, override via `CASHIER_SHIFT_REPORT_RECIPIENTS`)

## Efek Bisnis Penting

- tanpa shift aktif, operasi inti transaksi diblokir
- close shift dipakai untuk rekonsiliasi cash operasional
- pengeluaran mengurangi ekspektasi kas sebelum selisih dihitung, supaya selisih closing tidak salah dibaca sebagai kekurangan kas
- email laporan tutup kasir memerlukan mailer terkonfigurasi (`MAIL_MAILER` bukan `log`) agar benar-benar terkirim, bukan hanya tercatat di log

## Batasan Saat Ini

- shift tidak dimaksudkan sebagai workflow approval
- force close dibatasi untuk user tertentu
- sistem hanya mencatat pembayaran cash dan qris; kolom Debit/Kredit pada email laporan selalu ditampilkan "-" karena metode tersebut belum ada di sistem

## File Sentral

- `app/Http/Controllers/Apps/CashierShiftController.php`
- `app/Services/CashierShiftService.php`
- `app/Mail/CashierShiftClosingReportMail.php`
- `resources/views/emails/cashier-shifts/closing-report.blade.php`
- `app/Http/Middleware/EnsureActiveCashierShift.php`
