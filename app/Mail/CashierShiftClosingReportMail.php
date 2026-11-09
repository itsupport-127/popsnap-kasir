<?php

namespace App\Mail;

use App\Models\CashierShift;
use App\Services\CashierShiftService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CashierShiftClosingReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Nama bulan Bahasa Indonesia, tidak bergantung pada locale server supaya
     * format tanggal di judul & isi email selalu konsisten "11 JULI 2026".
     */
    private const MONTHS_ID = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public array $summary;

    public string $dateLabel;

    public function __construct(public CashierShift $cashierShift)
    {
        $referenceDate = $this->cashierShift->closed_at ?? now();
        $this->dateLabel = sprintf(
            '%d %s %d',
            $referenceDate->day,
            self::MONTHS_ID[(int) $referenceDate->month],
            $referenceDate->year
        );

        $this->summary = $this->buildSummary();
    }

    public function envelope(): Envelope
    {
        $storeLabel = config('cashier_shift_report.store_label', 'POPSNAP');

        return new Envelope(
            subject: mb_strtoupper("Tutup Kasir {$storeLabel} {$this->dateLabel}"),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cashier-shifts.closing-report',
            with: [
                'shift' => $this->cashierShift,
                'summary' => $this->summary,
                'dateLabel' => $this->dateLabel,
                'storeLabel' => config('cashier_shift_report.store_label', 'POPSNAP'),
            ],
        );
    }

    private function buildSummary(): array
    {
        $shift = $this->cashierShift;

        $service = app(CashierShiftService::class);

        $cash = (int) $shift->cash_sales_total;
        $qris = (int) $shift->non_cash_sales_total;
        $storeTotal = $cash + $qris;
        // Penjualan booth Photobox (lunas, bukan transaksi uji) selama shift ikut masuk laporan.
        $photobox = $service->photoboxTotalsFor($shift);
        $photoboxTotal = $photobox['photobox_sales_total'];
        $total = $storeTotal + $photoboxTotal;
        $expense = (int) $shift->expense_total;

        return [
            'cash' => $cash,
            'qris' => $qris,
            'photobox' => $photoboxTotal,
            'photobox_count' => $photobox['photobox_transactions_count'],
            'photobox_recap' => $service->calculatePhotoboxRecap($shift),
            'store_total' => $storeTotal,
            'total' => $total,
            'expense' => $expense,
            'note' => $shift->expense_notes,
            // Rekap item terjual (per kategori) supaya penerima laporan langsung tahu paket apa saja yang laku.
            'items' => $service->calculateItemRecap($shift),
        ];
    }
}
