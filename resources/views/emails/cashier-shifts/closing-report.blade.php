@php
    $rp = fn ($num) => 'Rp '.number_format((float) ($num ?? 0), 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Tutup Kasir {{ $storeLabel }} {{ $dateLabel }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Arial, Helvetica, sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">
                    <tr>
                        <td style="background-color:#0f172a;padding:20px 28px;">
                            <p style="margin:0;color:#94a3b8;font-size:12px;letter-spacing:.08em;text-transform:uppercase;">Laporan Tutup Kasir</p>
                            <h1 style="margin:4px 0 0;color:#ffffff;font-size:18px;">TUTUP KASIR {{ mb_strtoupper($storeLabel) }} {{ mb_strtoupper($dateLabel) }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 28px 8px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                                <tr>
                                    <td style="padding:6px 0;color:#64748b;width:40%;">Cash</td>
                                    <td style="padding:6px 0;text-align:right;font-weight:600;">{{ $rp($summary['cash']) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;color:#64748b;">Qris</td>
                                    <td style="padding:6px 0;text-align:right;font-weight:600;">{{ $rp($summary['qris']) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0 6px;color:#0f172a;border-top:1px solid #e2e8f0;font-weight:700;">Total Penjualan</td>
                                    <td style="padding:10px 0 6px;text-align:right;border-top:1px solid #e2e8f0;font-weight:700;">{{ $rp($summary['total']) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;color:#64748b;">Pengeluaran</td>
                                    <td style="padding:6px 0;text-align:right;font-weight:600;color:#b45309;">{{ $rp($summary['expense']) }}</td>
                                </tr>
                            </table>

                            <div style="margin-top:16px;padding:14px 16px;background-color:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;">
                                <p style="margin:0 0 4px;font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">Note</p>
                                <p style="margin:0;font-size:14px;color:#1e293b;white-space:pre-line;">{{ $summary['note'] ?: '-' }}</p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0;padding-top:14px;">
                                <tr>
                                    <td style="padding:2px 0;">Kasir</td>
                                    <td style="padding:2px 0;text-align:right;">{{ $shift->user?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px 0;">Ditutup</td>
                                    <td style="padding:2px 0;text-align:right;">{{ optional($shift->closed_at)->format('d/m/Y H:i') ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px 0;">Shift #</td>
                                    <td style="padding:2px 0;text-align:right;">{{ $shift->id }}</td>
                                </tr>
                            </table>
                            <p style="margin:16px 0 0;font-size:11px;color:#cbd5e1;">Email ini dikirim otomatis oleh sistem Popsnap Kasir.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
