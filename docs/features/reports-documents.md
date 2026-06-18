# Reports & Documents

Kembali ke indeks dokumentasi: `docs/README.md`

## Tujuan

Menyediakan visibilitas operasional melalui laporan dan dokumen siap cetak / share.

## Fitur Saat Ini

- laporan penjualan
- laporan profit
- invoice transaksi publik dan internal
- receipt thermal 58mm / 80mm
- shipping label
- PDF receivable
- PDF payable

## Halaman dan Route

- `dashboard/reports/sales`
- `dashboard/reports/profits`
- `pdf.transactions.invoice`
- `pdf.transactions.receipt`
- `pdf.transactions.shipping`
- `pdf.receivables.show`
- `pdf.payables.show`

## Permission

- `reports-access`
- `profits-access`
- akses dokumen mengikuti modul asal seperti `transactions-access`, `receivables-access`, dan `payables-access`

## Alur User

1. user membuka laporan sales atau profit
2. user memfilter data
3. user membuka dokumen transaksi atau finansial terkait
4. dokumen bisa dipakai untuk print/share

## Integrasi Data

- `transactions`
- `transaction_details`
- `profits`
- `receivables`
- `payables`
- `settings` untuk identitas toko

## Efek Bisnis Penting

- laporan sales dan profit bergantung pada kualitas data transaksi
- sales return dan inventory correction dapat memengaruhi pembacaan operasional pada laporan terkait

## Batasan Saat Ini

- laporan lebih fokus pada kebutuhan operasional dasar
- tidak semua analitik lanjutan owner tersedia

## File Sentral

- `app/Http/Controllers/Reports/SalesReportController.php`
- `app/Http/Controllers/Reports/ProfitReportController.php`
- `app/Http/Controllers/DocumentController.php`
