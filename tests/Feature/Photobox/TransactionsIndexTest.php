<?php
namespace Tests\Feature\Photobox;
use App\Http\Controllers\Apps\PhotoboxTransactionController;
use App\Models\PhotoboxStudio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
class TransactionsIndexTest extends TestCase
{
    public function test_index_page_builds_with_and_without_gallery_rows(): void
    {
        foreach (['2026_10_22_000100_create_photobox_tables','2026_10_26_000100_add_soft_delete_and_edit_to_photobox_transactions','2026_10_27_000100_add_gallery_url_to_photobox_transactions','2026_11_03_000100_add_sync_report_to_photobox_studios','2026_11_09_000200_add_is_test_to_photobox_transactions_table'] as $m) {
            $this->artisan('migrate', ['--path' => "database/migrations/$m.php", '--force' => true]);
        }
        Schema::create('customers', fn ($t) => [$t->id(), $t->string('name')->nullable(), $t->string('no_telp')->nullable(), $t->timestamps()]);
        Schema::create('transactions', fn ($t) => [$t->id(), $t->string('invoice')->nullable(), $t->unsignedBigInteger('customer_id')->nullable(), $t->timestamps()]);
        config(['services.popsnap_gallery.url' => 'https://foto.example.com']);
        $s = new PhotoboxStudio(['name' => 'A', 'is_active' => true, 'token_hash' => bin2hex(random_bytes(32))]); $s->save(); $tok = $s->issueToken();
        $rows = [];
        foreach ([1,2,3] as $i) $rows[] = ['order_no' => "PB-A-261115-000$i", 'template_id' => 't', 'template_name' => 'T', 'category' => 'P', 'mode' => 'photo', 'method' => 'qris', 'amount' => 35000, 'status' => 'paid', 'paid_at' => now()->toIso8601String(), 'created_at' => now()->toIso8601String(), 'session_id' => "PB-A-261115-000$i", 'gallery_url' => $i < 3 ? "https://foto.example.com/s/7PJVYRAEMZ$i" : null];
        $this->withToken($tok)->postJson('/api/photobox/transactions', ['transactions' => $rows])->assertOk();
        Http::fake(['*' => Http::response(['sessions' => [], 'orders' => []], 200)]);
        $req = Request::create('/dashboard/photobox-transactions', 'GET', ['period' => 'week', 'search' => '']);
        $resp = app(PhotoboxTransactionController::class)->index($req);
        $this->assertNotNull($resp);
        $req = Request::create('/dashboard/photobox-transactions', 'GET', ['period' => 'week', 'search' => '7PJV YRAE']);
        $this->assertNotNull(app(PhotoboxTransactionController::class)->index($req));
    }
}
