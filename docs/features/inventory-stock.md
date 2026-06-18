# Inventory & Stock

Kembali ke indeks dokumentasi: `docs/README.md`

## Tujuan

Menjaga akurasi stok melalui master produk, stock opname, dan histori mutasi stok.

## Fitur Saat Ini

- CRUD produk
- initial stock saat create product
- stock tidak bisa diubah langsung dari edit product
- stock opname draft → finalized
- stock mutation list
- low stock notification

## Halaman dan Route

- `dashboard/products`
- `dashboard/stock-opnames`
- `dashboard/stock-mutations`

## Permission

- `products-access`, `products-create`, `products-edit`, `products-delete`
- `stock-opnames-access`, `stock-opnames-create`, `stock-opnames-finalize`
- `stock-mutations-access`

## Alur User

1. produk dibuat dengan initial stock
2. initial stock menghasilkan stock mutation awal
3. stock opname dibuat sebagai draft
4. produk ditambahkan ke sesi opname
5. stok fisik diisi per item
6. finalize mengubah stok produk dan membuat stock mutation adjustment

## Integrasi Data

- `products`
- `stock_opnames`
- `stock_opname_items`
- `stock_mutations`
- `product_notification_reads`

## Efek Bisnis Penting

- edit product tidak lagi menjadi jalur mutasi stok
- sales return dan stock opname dapat menambah stok kembali
- histori mutasi adalah audit trail inventory utama

## Batasan Saat Ini

- belum multi warehouse
- mutasi stok belum mencakup semua sumber operasional secara penuh

## File Sentral

- `app/Http/Controllers/Apps/ProductController.php`
- `app/Http/Controllers/Apps/StockOpnameController.php`
- `app/Http/Controllers/Apps/StockMutationController.php`
- `app/Services/StockMutationService.php`
