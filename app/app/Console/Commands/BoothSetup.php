<?php

namespace App\Console\Commands;

use App\Models\BoothOutlet;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;

class BoothSetup extends Command
{
    protected $signature = 'booth:setup {--price=35000 : Harga sesi default}';
    protected $description = 'Buat outlet default dan produk POS "Sesi Photobox".';

    public function handle(): int
    {
        BoothOutlet::firstOrCreate([], ['name' => 'Outlet Utama']);
        $category = Category::first();
        if (! $category) {
            $this->error('Belum ada kategori produk. Buat satu kategori dulu di POS.');

            return self::FAILURE;
        }
        $product = Product::firstOrCreate(['sku' => config('booth.product_sku', 'SESI-PHOTOBOX')], [
            'image' => '', 'barcode' => config('booth.product_sku', 'SESI-PHOTOBOX'), 'title' => 'Sesi Photobox', 'description' => 'Penjualan sesi photobox (otomatis dari booth)',
            'buy_price' => 0, 'sell_price' => (int) $this->option('price'), 'category_id' => $category->id, 'stock' => 999999, 'is_sellable' => true,
        ]);
        $this->info("Produk siap: {$product->title} (id {$product->id}).");

        return self::SUCCESS;
    }
}
