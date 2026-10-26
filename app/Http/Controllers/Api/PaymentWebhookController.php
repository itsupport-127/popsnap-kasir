<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use App\Models\Transaction;
use App\Services\Payments\DokuGateway;
use App\Services\Payments\FlipGateway;
use App\Services\StockMutationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private DokuGateway $dokuGateway,
        private FlipGateway $flipGateway,
        private StockMutationService $stockMutationService,
    ) {}

    // =========================================================================
    // MIDTRANS (tidak berubah)
    // =========================================================================

    public function midtrans(Request $request)
    {
        try {
            $paymentSetting = PaymentSetting::first();
            if (! $paymentSetting || ! $paymentSetting->midtrans_enabled) {
                return response()->json(['status' => 'error', 'message' => 'Midtrans not configured'], 400);
            }

            $orderId    = $request->input('order_id');
            $statusCode = $request->input('status_code');
            $grossAmount = $request->input('gross_amount');
            $serverKey  = $paymentSetting->resolvedSecret('midtrans_server_key');

            $signatureKey      = $request->input('signature_key');
            $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

            if ($signatureKey !== $expectedSignature) {
                Log::warning('Midtrans Webhook: Invalid signature', ['order_id' => $orderId]);
                return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 403);
            }

            $transaction = Transaction::where('invoice', $orderId)->first();
            if (! $transaction) {
                return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
            }

            $newStatus = $this->mapMidtransStatus(
                $request->input('transaction_status'),
                $request->input('fraud_status')
            );

            $this->applyPaymentStatusUpdate($transaction, $newStatus, $request->input('transaction_id'));

            Log::info('Midtrans Webhook: Transaction updated', ['order_id' => $orderId, 'status' => $newStatus]);
            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Midtrans Webhook Error', ['message' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // XENDIT (tidak berubah)
    // =========================================================================

    public function xendit(Request $request)
    {
        try {
            $paymentSetting = PaymentSetting::first();
            if (! $paymentSetting || ! $paymentSetting->xendit_enabled) {
                return response()->json(['status' => 'error', 'message' => 'Xendit not configured'], 400);
            }

            $callbackToken  = $request->header('X-CALLBACK-TOKEN');
            $expectedToken  = $paymentSetting->resolvedSecret('xendit_callback_token');

            if (blank($expectedToken) || ! hash_equals($expectedToken, (string) $callbackToken)) {
                Log::warning('Xendit Webhook: Invalid callback token');
                return response()->json(['status' => 'error', 'message' => 'Invalid callback token'], 403);
            }

            $externalId = $request->input('external_id');
            $transaction = Transaction::where('invoice', $externalId)->first();
            if (! $transaction) {
                return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
            }

            $newStatus = $this->mapXenditStatus($request->input('status'));
            $this->applyPaymentStatusUpdate($transaction, $newStatus, $request->input('id'));

            Log::info('Xendit Webhook: Transaction updated', ['external_id' => $externalId, 'status' => $newStatus]);
            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Xendit Webhook Error', ['message' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // DOKU — BARU
    // =========================================================================

    public function doku(Request $request)
    {
        try {
            $paymentSetting = PaymentSetting::first();
            if (! $paymentSetting || ! $paymentSetting->doku_enabled) {
                return response()->json(['status' => 'error', 'message' => 'DOKU not configured'], 400);
            }

            $secretKey = $paymentSetting->resolvedSecret('doku_secret_key');
            $headers   = array_map(fn($h) => $h[0], $request->headers->all());
            $body      = $request->getContent();

            if (! $this->dokuGateway->verifyWebhook($headers, $body, $secretKey)) {
                Log::warning('DOKU Webhook: Invalid signature');
                return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 403);
            }

            $invoiceNumber = $request->input('order.invoice_number');
            $transaction   = Transaction::where('invoice', $invoiceNumber)->first();
            if (! $transaction) {
                return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
            }

            $acquirementStatus = $request->input('transaction.status', 'PENDING');
            $newStatus         = $this->dokuGateway->mapStatus($acquirementStatus);

            $this->applyPaymentStatusUpdate($transaction, $newStatus, $request->input('transaction.original_request_id'));

            Log::info('DOKU Webhook: Transaction updated', ['invoice' => $invoiceNumber, 'status' => $newStatus]);
            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('DOKU Webhook Error', ['message' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // FLIP — BARU
    // =========================================================================

    public function flip(Request $request)
    {
        try {
            $paymentSetting = PaymentSetting::first();
            if (! $paymentSetting || ! $paymentSetting->flip_enabled) {
                return response()->json(['status' => 'error', 'message' => 'Flip not configured'], 400);
            }

            $validationToken  = $paymentSetting->resolvedSecret('flip_validation_token');
            $receivedToken    = $request->header('X-CALLBACK-TOKEN', '');

            if (! $this->flipGateway->verifyWebhook($receivedToken, $validationToken)) {
                Log::warning('Flip Webhook: Invalid token');
                return response()->json(['status' => 'error', 'message' => 'Invalid token'], 403);
            }

            // Flip kirim data sebagai form-encoded dalam field 'data'
            $data = json_decode($request->input('data', '{}'), true);

            $billTitle   = $data['title'] ?? '';
            // Kita pakai external_id atau title untuk mapping ke invoice
            $externalId  = $data['external_id'] ?? $billTitle;
            $invoiceNum  = str_replace('Transaksi #', '', $externalId);

            $transaction = Transaction::where('invoice', $invoiceNum)->first();
            if (! $transaction) {
                return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
            }

            $newStatus = $this->flipGateway->mapStatus($data['status'] ?? 'PENDING');
            $this->applyPaymentStatusUpdate($transaction, $newStatus, $data['id'] ?? null);

            Log::info('Flip Webhook: Transaction updated', ['invoice' => $invoiceNum, 'status' => $newStatus]);
            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Flip Webhook Error', ['message' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * applyPaymentStatusUpdate — satu pintu buat semua webhook update status
     * pembayaran. Kalau status baru 'failed' (gagal/expired) dan sebelumnya
     * BUKAN 'failed'/'paid', stok yang udah kepotong pas checkout otomatis
     * dikembalikan. Dibungkus DB::transaction biar atomic, dan pengecekan
     * status sebelumnya mencegah stok balik dobel kalau webhook yang sama
     * kekirim berkali-kali (retry dari payment gateway).
     */
    private function applyPaymentStatusUpdate(Transaction $transaction, string $newStatus, ?string $paymentReference): void
    {
        DB::transaction(function () use ($transaction, $newStatus, $paymentReference) {
            $transaction = Transaction::where('id', $transaction->id)->lockForUpdate()->first();
            $previousStatus = $transaction->payment_status;

            $transaction->update([
                'payment_status'    => $newStatus,
                'payment_reference' => $paymentReference ?: $transaction->payment_reference,
            ]);

            if (
                in_array($newStatus, ['failed', 'expired'], true)
                && ! in_array($previousStatus, ['failed', 'expired', 'paid'], true)
            ) {
                $this->stockMutationService->restoreStockForTransaction($transaction);

                Log::info('Stock restored for failed/expired payment', [
                    'transaction_id' => $transaction->id,
                    'invoice'         => $transaction->invoice,
                ]);
            }
        });
    }

    private function mapMidtransStatus(string $transactionStatus, ?string $fraudStatus = null): string
    {
        if ($fraudStatus === 'challenge' || $fraudStatus === 'deny') return 'failed';
        return match ($transactionStatus) {
            'capture', 'settlement' => 'paid',
            'pending'               => 'pending',
            'deny', 'cancel', 'expire' => 'failed',
            default                 => 'pending',
        };
    }

    private function mapXenditStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'PAID', 'SETTLED' => 'paid',
            'PENDING'         => 'pending',
            'EXPIRED', 'FAILED' => 'failed',
            default           => 'pending',
        };
    }
}