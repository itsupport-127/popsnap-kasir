<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerCredit;
use App\Models\Promo;
use App\Models\LoyaltyPointHistory;
use App\Models\Payable;
use App\Models\PayablePayment;
use App\Models\Product;
use App\Models\Profit;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\StockMutation;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        Cart::truncate();
        LoyaltyPointHistory::truncate();
        Promo::truncate();
        CustomerCredit::truncate();
        SalesReturnItem::truncate();
        SalesReturn::truncate();
        CashierShift::truncate();
        StockMutation::truncate();
        ReceivablePayment::truncate();
        PayablePayment::truncate();
        Receivable::truncate();
        Payable::truncate();
        TransactionDetail::truncate();
        Profit::truncate();
        Transaction::truncate();
        Product::truncate();
        Category::truncate();
        Customer::truncate();
        Supplier::truncate();

        Schema::enableForeignKeyConstraints();

        Storage::disk('public')->makeDirectory('products');
        Storage::disk('public')->makeDirectory('category');

        $this->command->info('Seeding customers...');
        $customers = $this->seedCustomers();

        $this->command->info('Seeding suppliers...');
        $suppliers = $this->seedSuppliers();

        $this->command->info('Seeding categories...');
        $categories = $this->seedCategories();

        $this->command->info('Seeding products...');
        $products = $this->seedProducts($categories);

        $this->command->info('Seeding transactions...');
        $this->seedTransactions($customers, $products);

        $this->command->info('Seeding receivables...');
        $this->seedReceivables($customers);

        $this->command->info('Seeding loyalty vouchers...');
        $this->seedCustomerVouchers($customers);

        $this->command->info('Seeding payables...');
        $this->seedPayables($suppliers);

        $this->command->info('Sample data seeding completed!');
    }

    // -------------------------------------------------------------------------
    // CUSTOMERS — sesuai data Popsnap
    // -------------------------------------------------------------------------

    private function seedCustomers(): Collection
    {
        $customers = collect([
            ['name' => 'Andi Nugraha',    'no_telp' => '6281211111111', 'address' => 'Jl. Melati No. 21, Bandung',    'is_loyalty_member' => true,  'member_code' => 'MEM-ANDI001', 'loyalty_tier' => 'gold',     'loyalty_points' => 180, 'loyalty_total_spent' => 1800000, 'loyalty_transaction_count' => 12, 'loyalty_member_since' => now()->subMonths(8)],
            ['name' => 'Bunga Maharani',  'no_telp' => '6281312345678', 'address' => 'Jl. Mawar No. 5, Jakarta',      'is_loyalty_member' => true,  'member_code' => 'MEM-BUNGA01', 'loyalty_tier' => 'silver',   'loyalty_points' => 60,  'loyalty_total_spent' => 780000,  'loyalty_transaction_count' => 6,  'loyalty_member_since' => now()->subMonths(4)],
            ['name' => 'Cici Amelia',     'no_telp' => '6281512340000', 'address' => 'Jl. Anggrek No. 17, Surabaya',  'is_loyalty_member' => false],
            ['name' => 'Davin Pradipta',  'no_telp' => '6285612349911', 'address' => 'Jl. Kenanga No. 2, Yogyakarta', 'is_loyalty_member' => false],
            ['name' => 'Eko Saputra',     'no_telp' => '6287712348822', 'address' => 'Jl. Cemara No. 45, Semarang',   'is_loyalty_member' => true,  'member_code' => 'MEM-EKO0001', 'loyalty_tier' => 'platinum', 'loyalty_points' => 420, 'loyalty_total_spent' => 3600000, 'loyalty_transaction_count' => 21, 'loyalty_member_since' => now()->subYear()],
            ['name' => 'Fitri Lestari',   'no_telp' => '6282213345566', 'address' => 'Jl. Sakura No. 7, Medan',       'is_loyalty_member' => false],
            ['name' => 'Gina Putri',      'no_telp' => '6281399887766', 'address' => 'Jl. Dahlia No. 12, Malang',     'is_loyalty_member' => false],
            ['name' => 'Hendra Wijaya',   'no_telp' => '6285544332211', 'address' => 'Jl. Flamboyan No. 8, Denpasar', 'is_loyalty_member' => false],
        ]);

        return $customers
            ->map(fn ($c) => Customer::create(array_merge(['loyalty_tier' => 'regular', 'loyalty_points' => 0, 'loyalty_total_spent' => 0, 'loyalty_transaction_count' => 0], $c)))
            ->keyBy('name');
    }

    private function seedCustomerVouchers(Collection $customers): void
    {
        $blueprints = [
            ['customer' => 'Andi Nugraha',   'code' => 'VCR-ANDI10',  'name' => 'Voucher Loyal Gold',     'discount_type' => 'fixed_amount', 'discount_value' => 10000, 'minimum_order' => 75000],
            ['customer' => 'Bunga Maharani', 'code' => 'VCR-BUNGA5',  'name' => 'Voucher Repeat Order',   'discount_type' => 'percentage',   'discount_value' => 5,     'minimum_order' => 50000],
            ['customer' => 'Eko Saputra',    'code' => 'VCR-EKO25',   'name' => 'Voucher Platinum',       'discount_type' => 'fixed_amount', 'discount_value' => 25000, 'minimum_order' => 150000],
        ];

        foreach ($blueprints as $blueprint) {
            $customer = $customers->get($blueprint['customer']);
            if (! $customer) continue;

            Promo::create([
                'customer_id'    => $customer->id,
                'activation_mode' => Promo::ACTIVATION_MANUAL,
                'discount_kind'  => Promo::KIND_STANDARD_DISCOUNT,
                'code'           => $blueprint['code'],
                'name'           => $blueprint['name'],
                'discount_type'  => $blueprint['discount_type'],
                'discount_value' => $blueprint['discount_value'],
                'minimum_order'  => $blueprint['minimum_order'],
                'target_type'    => Promo::TARGET_ALL,
                'customer_scope' => Promo::SCOPE_ALL,
                'is_active'      => true,
                'starts_at'      => now()->subDays(7),
                'ends_at'        => now()->addDays(30),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // SUPPLIERS
    // -------------------------------------------------------------------------

    private function seedSuppliers(): Collection
    {
        $suppliers = collect([
            ['name' => 'PT Mitra Foto Indonesia',  'phone' => '0215551001', 'email' => 'sales@mitrafoto.test',    'address' => 'Jl. Industri Foto No. 10, Jakarta'],
            ['name' => 'CV Kertas Prima Jaya',     'phone' => '0225551002', 'email' => 'order@kertasprima.test',  'address' => 'Jl. Soekarno Hatta No. 88, Bandung'],
            ['name' => 'PT Bahan Cetak Nusantara', 'phone' => '0315551003', 'email' => 'hello@bahancetak.test',   'address' => 'Jl. Raya Darmo No. 21, Surabaya'],
            ['name' => 'UD Tinta & Supply',        'phone' => '0245551004', 'email' => 'admin@tintasupply.test',  'address' => 'Jl. Pandanaran No. 45, Semarang'],
        ]);

        return $suppliers
            ->map(fn ($s) => Supplier::create($s))
            ->keyBy('name');
    }

    // -------------------------------------------------------------------------
    // CATEGORIES — Popsnap PhotoBox
    // -------------------------------------------------------------------------

    private function seedCategories(): Collection
    {
        $categories = collect([
            ['name' => 'PhotoBox',         'description' => 'Temukan berbagai pilihan photobox menarik untuk menyimpan dan menampilkan momen spesial Anda dengan gaya yang unik dan personal.'],
            ['name' => 'Paket PhotoBox',   'description' => 'Paket PhotoBox lengkap dengan berbagai pilihan tema dan aksesori.'],
            ['name' => 'Cetakan Tambahan', 'description' => 'Layanan cetakan tambahan untuk hasil foto berkualitas tinggi.'],
            ['name' => 'Aksesoris Photo',  'description' => 'Berbagai aksesoris foto untuk melengkapi pengalaman berfoto Anda.'],
            ['name' => 'Card & Package',   'description' => 'Paket kartu dan bundel produk pilihan.'],
            ['name' => 'Print Spesial',    'description' => 'Layanan cetak spesial dengan berbagai pilihan media dan format.'],
            ['name' => 'Frame & Display',  'description' => 'Bingkai dan media display untuk kenangan berharga Anda.'],
        ]);

        return $categories->map(function ($category) {
            return Category::create([
                'name'        => $category['name'],
                'description' => $category['description'],
                'image'       => 'default.jpg',
            ]);
        })->keyBy('name');
    }

    // -------------------------------------------------------------------------
    // PRODUCTS — Popsnap PhotoBox (sesuai SQL)
    // -------------------------------------------------------------------------

    private function seedProducts(Collection $categories): Collection
    {
        $products = collect([
            ['category' => 'PhotoBox',         'barcode' => 'PHB-0001', 'sku' => 'PHB-0001', 'title' => 'Fisheye Box',          'description' => 'Fisheye Box - Produk PhotoBox dengan desain kreatif untuk menyimpan dan menampilkan momen spesial Anda.',           'buy_price' => 18000,  'sell_price' => 30000,  'stock' => 50],
            ['category' => 'PhotoBox',         'barcode' => 'PHB-0002', 'sku' => 'PHB-0002', 'title' => 'Vintage Room',         'description' => 'Vintage Room - Produk PhotoBox dengan desain kreatif untuk menyimpan dan menampilkan momen spesial Anda.',          'buy_price' => 21000,  'sell_price' => 35000,  'stock' => 48],
            ['category' => 'PhotoBox',         'barcode' => 'PHB-0003', 'sku' => 'PHB-0003', 'title' => "Emma's Room",          'description' => "Emma's Room - Produk PhotoBox dengan desain kreatif untuk menyimpan dan menampilkan momen spesial Anda.",           'buy_price' => 21000,  'sell_price' => 35000,  'stock' => 46],
            ['category' => 'PhotoBox',         'barcode' => 'PHB-0004', 'sku' => 'PHB-0004', 'title' => 'CCTV SnapBox',         'description' => 'CCTV SnapBox - Produk PhotoBox dengan desain kreatif untuk menyimpan dan menampilkan momen spesial Anda.',          'buy_price' => 21000,  'sell_price' => 35000,  'stock' => 49],
            ['category' => 'Paket PhotoBox',   'barcode' => 'PHB-0005', 'sku' => 'PHB-0005', 'title' => 'Celebration Package',  'description' => 'Celebration Package - Paket PhotoBox lengkap untuk merayakan momen spesial Anda.',                                   'buy_price' => 99000,  'sell_price' => 165000, 'stock' => 50],
            ['category' => 'Cetakan Tambahan', 'barcode' => 'PHB-0006', 'sku' => 'PHB-0006', 'title' => 'Add Print',            'description' => 'Add Print - Layanan cetakan tambahan untuk hasil foto berkualitas tinggi.',                                          'buy_price' => 3000,   'sell_price' => 5000,   'stock' => 50],
            ['category' => 'Aksesoris Photo',  'barcode' => 'PHB-0007', 'sku' => 'PHB-0007', 'title' => 'Keychain',             'description' => 'Keychain - Aksesoris foto berupa gantungan kunci dengan foto kenangan Anda.',                                        'buy_price' => 9000,   'sell_price' => 15000,  'stock' => 50],
            ['category' => 'Card & Package',   'barcode' => 'PHB-0008', 'sku' => 'PHB-0008', 'title' => 'Card Package',         'description' => 'Card Package - Paket kartu foto eksklusif untuk berbagai momen.',                                                   'buy_price' => 18000,  'sell_price' => 30000,  'stock' => 50],
            ['category' => 'Print Spesial',    'barcode' => 'PHB-0009', 'sku' => 'PHB-0009', 'title' => 'Newspaper Print',      'description' => 'Newspaper Print - Cetak foto dengan tema koran untuk kenangan unik.',                                               'buy_price' => 6000,   'sell_price' => 10000,  'stock' => 50],
            ['category' => 'Frame & Display',  'barcode' => 'PHB-0010', 'sku' => 'PHB-0010', 'title' => 'Frame 4R',             'description' => 'Frame 4R - Bingkai foto ukuran 4R untuk memajang kenangan berharga Anda.',                                          'buy_price' => 15000,  'sell_price' => 25000,  'stock' => 50],
        ]);

        return $products->map(function ($product) use ($categories) {
            $category = $categories->get($product['category']);

            return Product::create([
                'category_id' => $category?->id,
                'image'       => 'products/default.jpg',
                'barcode'     => $product['barcode'],
                'sku'         => $product['sku'],
                'title'       => $product['title'],
                'description' => $product['description'],
                'buy_price'   => $product['buy_price'],
                'sell_price'  => $product['sell_price'],
                'stock'       => $product['stock'],
            ]);
        })->keyBy('barcode');
    }

    // -------------------------------------------------------------------------
    // TRANSACTIONS — sesuai produk Popsnap
    // -------------------------------------------------------------------------

    private function seedTransactions(Collection $customers, Collection $products): void
    {
        $cashier = User::where('email', 'cashier@gmail.com')->first() ?? User::first();
        if (! $cashier) return;

        $blueprints = [
            [
                'customer' => 'Andi Nugraha',
                'discount' => 5000,
                'cash'     => 100000,
                'items'    => [['barcode' => 'PHB-0001', 'qty' => 2], ['barcode' => 'PHB-0006', 'qty' => 3]],
            ],
            [
                'customer' => 'Bunga Maharani',
                'discount' => 0,
                'cash'     => 200000,
                'items'    => [['barcode' => 'PHB-0002', 'qty' => 1], ['barcode' => 'PHB-0007', 'qty' => 2]],
            ],
            [
                'customer' => 'Cici Amelia',
                'discount' => 10000,
                'cash'     => 250000,
                'items'    => [['barcode' => 'PHB-0005', 'qty' => 1]],
            ],
            [
                'customer' => 'Davin Pradipta',
                'discount' => 0,
                'cash'     => 100000,
                'items'    => [['barcode' => 'PHB-0003', 'qty' => 1], ['barcode' => 'PHB-0009', 'qty' => 2]],
            ],
            [
                'customer' => 'Eko Saputra',
                'discount' => 15000,
                'cash'     => 500000,
                'items'    => [['barcode' => 'PHB-0005', 'qty' => 2], ['barcode' => 'PHB-0010', 'qty' => 1]],
            ],
            [
                'customer' => null,
                'discount' => 0,
                'cash'     => 50000,
                'items'    => [['barcode' => 'PHB-0004', 'qty' => 1], ['barcode' => 'PHB-0006', 'qty' => 2]],
            ],
        ];

        foreach ($blueprints as $blueprint) {
            $customer = $blueprint['customer'] ? $customers->get($blueprint['customer']) : null;

            $items = collect($blueprint['items'])->map(function ($item) use ($products) {
                $product = $products->get($item['barcode']);
                if (! $product) return null;
                return [
                    'product'    => $product,
                    'qty'        => $item['qty'],
                    'line_total' => $product->sell_price * $item['qty'],
                    'profit'     => ($product->sell_price - $product->buy_price) * $item['qty'],
                ];
            })->filter();

            if ($items->isEmpty()) continue;

            $discount   = max(0, $blueprint['discount']);
            $gross      = $items->sum('line_total');
            $grandTotal = max(0, $gross - $discount);
            $cashPaid   = max($grandTotal, $blueprint['cash']);
            $change     = $cashPaid - $grandTotal;

            $transaction = Transaction::create([
                'cashier_id'  => $cashier->id,
                'customer_id' => $customer?->id,
                'invoice'     => 'TRX-' . Str::upper(Str::random(8)),
                'cash'        => $cashPaid,
                'change'      => $change,
                'discount'    => $discount,
                'grand_total' => $grandTotal,
            ]);

            foreach ($items as $item) {
                $transaction->details()->create([
                    'product_id' => $item['product']->id,
                    'qty'        => $item['qty'],
                    'price'      => $item['line_total'],
                ]);

                $transaction->profits()->create(['total' => $item['profit']]);

                $item['product']->decrement('stock', $item['qty']);
            }
        }
    }

    // -------------------------------------------------------------------------
    // RECEIVABLES
    // -------------------------------------------------------------------------

    private function seedReceivables(Collection $customers): void
    {
        $cashier = User::where('email', 'cashier@gmail.com')->first() ?? User::first();

        $sourceTransactions = Transaction::with('customer')->whereNotNull('customer_id')->take(3)->get();

        foreach ($sourceTransactions as $index => $transaction) {
            $paid = match ($index) {
                0       => (float) ($transaction->grand_total * 0.4),
                1       => (float) ($transaction->grand_total * 0.7),
                default => 0,
            };

            $status = $paid <= 0 ? 'unpaid' : ($paid >= $transaction->grand_total ? 'paid' : 'partial');

            $receivable = Receivable::create([
                'customer_id'    => $transaction->customer_id,
                'transaction_id' => $transaction->id,
                'invoice'        => 'RCV-' . $transaction->invoice,
                'total'          => $transaction->grand_total,
                'paid'           => $paid,
                'due_date'       => now()->addDays(($index + 1) * 7)->toDateString(),
                'status'         => $status,
                'note'           => 'Piutang dari transaksi ' . $transaction->invoice,
            ]);

            if ($paid > 0) {
                ReceivablePayment::create([
                    'receivable_id' => $receivable->id,
                    'paid_at'       => now()->subDays(2 + $index)->toDateString(),
                    'amount'        => $paid,
                    'method'        => 'cash',
                    'user_id'       => $cashier?->id,
                    'note'          => 'Pembayaran awal piutang',
                ]);
            }

            $transaction->update([
                'payment_method' => 'credit',
                'payment_status' => $status === 'paid' ? 'paid' : 'unpaid',
                'cash'           => (int) $paid,
                'change'         => 0,
            ]);
        }

        // Manual receivables
        $manuals = [
            ['customer' => 'Gina Putri',   'invoice' => 'RCV-MANUAL-001', 'total' => 185000, 'paid' => 50000, 'due_date' => now()->addDays(10)->toDateString(), 'status' => 'partial', 'note' => 'Piutang manual grosir bulanan'],
            ['customer' => 'Hendra Wijaya','invoice' => 'RCV-MANUAL-002', 'total' => 275000, 'paid' => 0,     'due_date' => now()->subDays(3)->toDateString(),  'status' => 'overdue', 'note' => 'Piutang manual jatuh tempo'],
        ];

        foreach ($manuals as $item) {
            $customer = $customers->get($item['customer']);
            if (! $customer) continue;

            $receivable = Receivable::create([
                'customer_id' => $customer->id,
                'invoice'     => $item['invoice'],
                'total'       => $item['total'],
                'paid'        => $item['paid'],
                'due_date'    => $item['due_date'],
                'status'      => $item['status'],
                'note'        => $item['note'],
            ]);

            if ($item['paid'] > 0) {
                ReceivablePayment::create([
                    'receivable_id' => $receivable->id,
                    'paid_at'       => now()->subDays(1)->toDateString(),
                    'amount'        => $item['paid'],
                    'method'        => 'bank_transfer',
                    'user_id'       => $cashier?->id,
                    'note'          => 'Pembayaran sebagian',
                ]);
            }
        }
    }

    // -------------------------------------------------------------------------
    // PAYABLES
    // -------------------------------------------------------------------------

    private function seedPayables(Collection $suppliers): void
    {
        $cashier = User::where('email', 'cashier@gmail.com')->first() ?? User::first();

        $blueprints = [
            ['supplier' => 'PT Mitra Foto Indonesia',  'document_number' => 'PYB-0001', 'total' => 450000, 'paid' => 150000, 'due_date' => now()->addDays(14)->toDateString(), 'status' => 'partial', 'note' => 'Pengadaan stok kertas foto'],
            ['supplier' => 'CV Kertas Prima Jaya',     'document_number' => 'PYB-0002', 'total' => 720000, 'paid' => 0,      'due_date' => now()->addDays(21)->toDateString(), 'status' => 'unpaid',  'note' => 'Pengadaan bahan cetak'],
            ['supplier' => 'PT Bahan Cetak Nusantara', 'document_number' => 'PYB-0003', 'total' => 390000, 'paid' => 390000, 'due_date' => now()->subDays(2)->toDateString(),  'status' => 'paid',    'note' => 'Pembelian tinta & cartridge'],
            ['supplier' => 'UD Tinta & Supply',        'document_number' => 'PYB-0004', 'total' => 510000, 'paid' => 100000, 'due_date' => now()->subDays(5)->toDateString(),  'status' => 'overdue', 'note' => 'Pengadaan supply campuran jatuh tempo'],
        ];

        foreach ($blueprints as $item) {
            $supplier = $suppliers->get($item['supplier']);
            if (! $supplier) continue;

            $payable = Payable::create([
                'supplier_id'     => $supplier->id,
                'document_number' => $item['document_number'],
                'total'           => $item['total'],
                'paid'            => $item['paid'],
                'due_date'        => $item['due_date'],
                'status'          => $item['status'],
                'note'            => $item['note'],
            ]);

            if ($item['paid'] > 0) {
                PayablePayment::create([
                    'payable_id' => $payable->id,
                    'paid_at'    => now()->subDays(3)->toDateString(),
                    'amount'     => $item['paid'],
                    'method'     => 'bank_transfer',
                    'user_id'    => $cashier?->id,
                    'note'       => 'Pembayaran hutang supplier',
                ]);
            }
        }
    }
}