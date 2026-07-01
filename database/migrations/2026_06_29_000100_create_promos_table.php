<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promos', function (Blueprint $table) {
            $table->id();

            // Identitas
            $table->string('name');
            $table->string('code')->nullable()->unique(); // wajib hanya jika activation_mode = manual
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(0); // urutan penerapan untuk promo otomatis

            // Cara aktivasi — pengganti konsep "Pricing Rule vs Voucher"
            // auto   = berlaku sendiri begitu syarat keranjang terpenuhi
            // manual = kasir harus klik tombol / pilih kode dulu
            $table->string('activation_mode', 10)->default('auto');

            // Jenis diskon — sama untuk auto maupun manual
            // standard_discount | qty_break | bundle_price | buy_x_get_y
            $table->string('discount_kind', 30)->default('standard_discount');
            $table->string('discount_type', 20)->nullable(); // percentage | fixed_amount | fixed_price (untuk standard_discount)
            $table->decimal('discount_value', 15, 2)->nullable();

            // Target produk/kategori (berlaku untuk discount_kind apa saja kecuali bundle_price)
            $table->string('target_type', 20)->default('all'); // all | product | category
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();

            // Scope pelanggan
            $table->string('customer_scope', 20)->default('all'); // all | no_customer | registered | loyalty_member
            $table->json('eligible_loyalty_tiers')->nullable();

            // Untuk manual: assign ke 1 pelanggan tertentu (personal voucher),
            // atau null = berlaku umum (sesuai customer_scope di atas)
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            // Kuota pemakaian (khusus manual). null = tidak terbatas.
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            // Status pakai untuk voucher personal sekali pakai
            $table->boolean('is_used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();

            // Minimum belanja agar promo berlaku
            $table->unsignedInteger('minimum_order')->default(0);

            // Buy X Get Y
            $table->unsignedInteger('buy_qty')->nullable();
            $table->unsignedInteger('get_qty')->nullable();
            $table->foreignId('get_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedTinyInteger('get_discount_percent')->default(100); // 100 = gratis penuh

            // Preview/simulasi di admin (dipakai standard_discount/qty_break)
            $table->unsignedInteger('preview_quantity_multiplier')->default(1);

            // Periode berlaku
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'activation_mode', 'priority']);
            $table->index(['discount_kind', 'is_active']);
            $table->index(['target_type', 'product_id', 'category_id']);
            $table->index(['customer_scope', 'starts_at', 'ends_at']);
            $table->index(['customer_id']);
        });

        // Tingkatan diskon grosil bertingkat — dipakai oleh discount_kind = qty_break
        Schema::create('promo_qty_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_id')->constrained('promos')->cascadeOnDelete();
            $table->unsignedInteger('min_qty');
            $table->string('discount_type', 20); // percentage | fixed_amount | fixed_price
            $table->decimal('discount_value', 15, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['promo_id', 'min_qty']);
        });

        // Produk dalam satu paket bundle — dipakai oleh discount_kind = bundle_price
        Schema::create('promo_bundle_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_id')->constrained('promos')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['promo_id', 'sort_order']);
        });

        // Produk Buy/Get untuk discount_kind = buy_x_get_y (jika menyasar produk tertentu, bukan "semua produk")
        Schema::create('promo_buy_get_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_id')->constrained('promos')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('role', 10); // buy | get
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['promo_id', 'role', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_buy_get_items');
        Schema::dropIfExists('promo_bundle_items');
        Schema::dropIfExists('promo_qty_breaks');
        Schema::dropIfExists('promos');
    }
};
