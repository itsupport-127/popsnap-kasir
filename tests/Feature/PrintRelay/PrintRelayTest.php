<?php

namespace Tests\Feature\PrintRelay;

use App\Events\PrintJobRequested;
use App\Models\PrintJob;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PrintRelayTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::firstOrCreate(['name' => 'transactions-access', 'guard_name' => 'web']);
        $this->cashier = User::factory()->create(['name' => 'Kasir Satu']);
        $this->cashier->givePermissionTo('transactions-access');
    }

    private function transaction(): Transaction
    {
        return Transaction::create([
            'cashier_id' => $this->cashier->id, 'cashier_shift_id' => null, 'customer_id' => null,
            'invoice' => 'TRX-'.Str::upper(Str::random(8)), 'cash' => 30000, 'change' => 0, 'discount' => 0,
            'shipping_cost' => 0, 'grand_total' => 30000, 'payment_method' => 'cash', 'payment_status' => 'paid',
            'payment_reference' => null, 'payment_url' => null, 'bank_account_id' => null,
        ]);
    }

    private function job(array $attrs = []): PrintJob
    {
        return PrintJob::create(array_merge(['invoice' => 'TRX-X', 'size' => '58', 'requested_by' => 'HP', 'status' => PrintJob::QUEUED], $attrs));
    }

    private function backdate(PrintJob $job, Carbon $at): void
    {
        DB::table('print_jobs')->where('id', $job->id)->update(['created_at' => $at, 'updated_at' => $at]);
    }

    public function test_request_creates_a_queued_job_and_broadcasts_it_with_the_job_id(): void
    {
        Event::fake([PrintJobRequested::class]);
        $trx = $this->transaction();

        $res = $this->actingAs($this->cashier)->postJson(route('print-relay.request', $trx->invoice), ['size' => '80']);

        $res->assertOk();
        $job = PrintJob::firstOrFail();
        $this->assertSame($trx->invoice, $job->invoice);
        $this->assertSame('80', $job->size);
        $this->assertSame(PrintJob::QUEUED, $job->status);
        $this->assertSame('Kasir Satu', $job->requested_by);
        $res->assertJsonPath('job_id', $job->id);
        Event::assertDispatched(PrintJobRequested::class, fn ($e) => $e->jobId === $job->id && $e->invoice === $trx->invoice && $e->size === '80');
    }

    public function test_request_for_an_unknown_invoice_creates_nothing(): void
    {
        Event::fake([PrintJobRequested::class]);

        $this->actingAs($this->cashier)->postJson(route('print-relay.request', 'TRX-TIDAKADA'))->assertNotFound();

        $this->assertSame(0, PrintJob::count());
        Event::assertNotDispatched(PrintJobRequested::class);
    }

    public function test_only_one_pc_can_claim_a_job(): void
    {
        $job = $this->job();

        $first = $this->actingAs($this->cashier)->postJson(route('print-relay.claim', $job), ['device' => 'PC Kasir 1']);
        $first->assertOk()->assertJsonPath('claimed', true)->assertJsonPath('invoice', 'TRX-X')->assertJsonStructure(['printer_name']);

        $second = $this->actingAs($this->cashier)->postJson(route('print-relay.claim', $job), ['device' => 'PC Kasir 2']);
        $second->assertStatus(409)->assertJsonPath('claimed', false);

        $job->refresh();
        $this->assertSame(PrintJob::PRINTING, $job->status);
        $this->assertSame('Kasir Satu · PC Kasir 1', $job->printed_by); // yang menang tercatat, bukan yang kalah
    }

    public function test_a_stale_job_cannot_be_claimed(): void
    {
        $job = $this->job();
        $this->backdate($job, now()->subMinutes(PrintJob::PENDING_WINDOW_MINUTES + 1));

        $this->actingAs($this->cashier)->postJson(route('print-relay.claim', $job))->assertStatus(409);
        $this->assertSame(PrintJob::QUEUED, $job->fresh()->status);
    }

    public function test_finish_records_the_outcome_only_for_a_job_being_printed(): void
    {
        $ok = $this->job(['status' => PrintJob::PRINTING, 'claimed_at' => now()]);
        $this->actingAs($this->cashier)->postJson(route('print-relay.finish', $ok), ['status' => 'printed'])->assertOk();
        $this->assertSame(PrintJob::PRINTED, $ok->fresh()->status);
        $this->assertNotNull($ok->fresh()->finished_at);

        $bad = $this->job(['status' => PrintJob::PRINTING, 'claimed_at' => now()]);
        $this->actingAs($this->cashier)->postJson(route('print-relay.finish', $bad), ['status' => 'failed', 'error' => 'Printer POS-58 tidak ditemukan'])->assertOk();
        $this->assertSame(PrintJob::FAILED, $bad->fresh()->status);
        $this->assertSame('Printer POS-58 tidak ditemukan', $bad->fresh()->error);

        // pekerjaan yang masih antre tidak bisa "diselesaikan" tanpa diklaim dulu
        $queued = $this->job();
        $this->actingAs($this->cashier)->postJson(route('print-relay.finish', $queued), ['status' => 'printed'])->assertOk();
        $this->assertSame(PrintJob::QUEUED, $queued->fresh()->status);
    }

    public function test_pending_lists_only_recent_unclaimed_jobs(): void
    {
        $recent = $this->job(['invoice' => 'TRX-BARU']);
        $this->job(['invoice' => 'TRX-DIKLAIM', 'status' => PrintJob::PRINTING]);
        $old = $this->job(['invoice' => 'TRX-BASI']);
        $this->backdate($old, now()->subMinutes(PrintJob::PENDING_WINDOW_MINUTES + 2));

        $res = $this->actingAs($this->cashier)->getJson(route('print-relay.pending'))->assertOk();

        $this->assertSame([$recent->id], collect($res->json('jobs'))->pluck('id')->all());
    }

    public function test_history_page_shows_only_todays_jobs_and_clears_older_ones(): void
    {
        $today = $this->job(['invoice' => 'TRX-HARIINI']);
        $yesterday = $this->job(['invoice' => 'TRX-KEMARIN']);
        $this->backdate($yesterday, now()->subDay()->setTime(14, 0));

        $this->actingAs($this->cashier)->get(route('print-relay.listener'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/PrintListener')
                ->has('jobs', 1)
                ->where('jobs.0.invoice', 'TRX-HARIINI'));

        $this->assertNull(PrintJob::find($yesterday->id), 'riwayat kemarin harus terhapus (reset harian)');
        $this->assertNotNull(PrintJob::find($today->id));
    }

    public function test_a_job_stuck_in_printing_is_marked_failed(): void
    {
        $stuck = $this->job(['status' => PrintJob::PRINTING, 'claimed_at' => now()->subMinutes(PrintJob::STUCK_AFTER_MINUTES + 1)]);
        $fresh = $this->job(['status' => PrintJob::PRINTING, 'claimed_at' => now()->subSeconds(20)]);

        $this->actingAs($this->cashier)->getJson(route('print-relay.jobs'))->assertOk();

        $this->assertSame(PrintJob::FAILED, $stuck->fresh()->status);
        $this->assertSame(PrintJob::PRINTING, $fresh->fresh()->status);
    }

    public function test_users_without_transactions_access_are_refused(): void
    {
        $outsider = User::factory()->create();
        $job = $this->job();

        $this->actingAs($outsider)->postJson(route('print-relay.claim', $job))->assertForbidden();
        $this->actingAs($outsider)->getJson(route('print-relay.pending'))->assertForbidden();
        $this->actingAs($outsider)->get(route('print-relay.listener'))->assertForbidden();
        $this->assertSame(PrintJob::QUEUED, $job->fresh()->status);
    }
}
