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

                            @php
                                $recap = $summary['items'] ?? ['groups' => [], 'totals' => ['net_qty' => 0, 'net_revenue' => 0, 'returned' => 0]];
                                $diff = (int) ($summary['total'] ?? 0) - (int) ($recap['totals']['net_revenue'] ?? 0);
                            @endphp
                            <div style="margin-top:16px;">
                                <p style="margin:0 0 6px;font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">Item Terjual</p>
                                @if (empty($recap['groups']))
                                    <p style="margin:0;font-size:14px;color:#94a3b8;">Belum ada item terjual pada shift ini.</p>
                                @else
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;border:1px solid #e2e8f0;border-radius:10px;">
                                        <tr>
                                            <td style="padding:6px 12px;font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;">Item</td>
                                            <td style="padding:6px 8px;font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;text-align:right;">Jumlah</td>
                                            <td style="padding:6px 12px;font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;text-align:right;">Nominal</td>
                                        </tr>
                                        @foreach ($recap['groups'] as $group)
                                            <tr>
                                                <td style="padding:8px 12px;background-color:#f1f5f9;font-weight:700;color:#0f172a;">{{ $group['category'] }}</td>
                                                <td style="padding:8px 8px;background-color:#f1f5f9;text-align:right;font-weight:700;color:#0f172a;">{{ $group['net_qty'] }}</td>
                                                <td style="padding:8px 12px;background-color:#f1f5f9;text-align:right;font-weight:700;color:#0f172a;white-space:nowrap;">{{ $rp($group['net_revenue']) }}</td>
                                            </tr>
                                            @foreach ($group['items'] as $item)
                                                <tr>
                                                    <td style="padding:6px 12px;color:#334155;border-top:1px solid #f1f5f9;">
                                                        {{ $item['title'] }}
                                                        @if ($item['returned'] > 0)
                                                            <span style="color:#b45309;font-size:12px;">(retur {{ $item['returned'] }})</span>
                                                        @endif
                                                    </td>
                                                    <td style="padding:6px 8px;text-align:right;font-weight:600;color:#0f172a;border-top:1px solid #f1f5f9;white-space:nowrap;">{{ $item['net_qty'] }}</td>
                                                    <td style="padding:6px 12px;text-align:right;color:#475569;border-top:1px solid #f1f5f9;white-space:nowrap;">{{ $rp($item['net_revenue']) }}</td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                        <tr>
                                            <td style="padding:10px 12px;font-weight:700;color:#0f172a;border-top:1px solid #e2e8f0;">Total item terjual</td>
                                            <td style="padding:10px 8px;text-align:right;font-weight:700;color:#0f172a;border-top:1px solid #e2e8f0;">{{ $recap['totals']['net_qty'] }}</td>
                                            <td style="padding:10px 12px;text-align:right;font-weight:700;color:#0f172a;border-top:1px solid #e2e8f0;white-space:nowrap;">{{ $rp($recap['totals']['net_revenue']) }}</td>
                                        </tr>
                                        @if ($diff !== 0)
                                            <tr>
                                                <td colspan="2" style="padding:6px 12px 10px;font-size:12px;color:#94a3b8;">Selisih dengan Total Penjualan<br>(ongkir, retur, atau transaksi belum lunas)</td>
                                                <td style="padding:6px 12px 10px;text-align:right;font-size:12px;color:#94a3b8;white-space:nowrap;vertical-align:top;">{{ $diff < 0 ? '-' : '' }}{{ $rp(abs($diff)) }}</td>
                                            </tr>
                                        @endif
                                    </table>
                                @endif
                            </div>

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
