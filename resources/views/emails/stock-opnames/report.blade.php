@php
    $selisih = fn ($item) => -(int) ($item->difference ?? 0);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Stock Opname {{ $storeLabel }} {{ $dateLabel }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Arial, Helvetica, sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">
                    <tr>
                        <td style="background-color:#0f172a;padding:20px 28px;">
                            <p style="margin:0;color:#94a3b8;font-size:12px;letter-spacing:.08em;text-transform:uppercase;">Laporan Stock Opname</p>
                            <h1 style="margin:4px 0 0;color:#ffffff;font-size:18px;">STOCK OPNAME {{ mb_strtoupper($storeLabel) }} {{ mb_strtoupper($dateLabel) }}</h1>
                            @if($stockOpname->code)
                                <p style="margin:4px 0 0;color:#94a3b8;font-size:12px;">{{ $stockOpname->code }}</p>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 28px 4px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;">
                                <tr>
                                    <td style="padding:4px 0;color:#64748b;width:33%;">Total Item</td>
                                    <td style="padding:4px 0;text-align:right;font-weight:600;width:33%;">{{ $summary['total_items'] }}</td>
                                    <td style="padding:4px 0;color:#64748b;width:34%;text-align:right;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0;color:#64748b;">Item Beda</td>
                                    <td style="padding:4px 0;text-align:right;font-weight:600;color:{{ $summary['different_items'] > 0 ? '#b45309' : '#15803d' }};">{{ $summary['different_items'] }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0;color:#64748b;">Total Selisih</td>
                                    <td style="padding:4px 0;text-align:right;font-weight:700;color:{{ $summary['total_selisih'] != 0 ? '#b45309' : '#15803d' }};">{{ $summary['total_selisih'] > 0 ? '+'.$summary['total_selisih'] : $summary['total_selisih'] }}</td>
                                    <td></td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:12px;border-collapse:collapse;">
                                <thead>
                                    <tr style="background-color:#f8fafc;">
                                        <th align="left" style="padding:8px 6px;color:#64748b;border-bottom:1px solid #e2e8f0;">Produk</th>
                                        <th align="right" style="padding:8px 6px;color:#64748b;border-bottom:1px solid #e2e8f0;">Stock Awal</th>
                                        <th align="right" style="padding:8px 6px;color:#64748b;border-bottom:1px solid #e2e8f0;">Stock Akhir</th>
                                        <th align="right" style="padding:8px 6px;color:#64748b;border-bottom:1px solid #e2e8f0;">Stock di Toko</th>
                                        <th align="right" style="padding:8px 6px;color:#64748b;border-bottom:1px solid #e2e8f0;">Selisih</th>
                                        <th align="left" style="padding:8px 6px;color:#64748b;border-bottom:1px solid #e2e8f0;">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($stockOpname->items as $item)
                                        <tr>
                                            <td style="padding:6px;border-bottom:1px solid #f1f5f9;">{{ $item->product->title ?? '-' }}</td>
                                            <td align="right" style="padding:6px;border-bottom:1px solid #f1f5f9;color:#94a3b8;">{{ $item->opening_stock ?? '-' }}</td>
                                            <td align="right" style="padding:6px;border-bottom:1px solid #f1f5f9;">{{ $item->system_stock }}</td>
                                            <td align="right" style="padding:6px;border-bottom:1px solid #f1f5f9;">{{ $item->physical_stock ?? '-' }}</td>
                                            <td align="right" style="padding:6px;border-bottom:1px solid #f1f5f9;font-weight:600;color:{{ $selisih($item) != 0 ? '#b45309' : '#15803d' }};">
                                                @if($item->physical_stock === null)
                                                    -
                                                @else
                                                    {{ $selisih($item) > 0 ? '+'.$selisih($item) : $selisih($item) }}
                                                @endif
                                            </td>
                                            <td style="padding:6px;border-bottom:1px solid #f1f5f9;color:#64748b;">{{ $item->adjustment_reason ?: '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:8px 28px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0;padding-top:14px;">
                                <tr>
                                    <td style="padding:2px 0;">Dibuat oleh</td>
                                    <td style="padding:2px 0;text-align:right;">{{ $stockOpname->creator?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px 0;">Difinalisasi</td>
                                    <td style="padding:2px 0;text-align:right;">{{ optional($stockOpname->finalized_at)->format('d/m/Y H:i') ?? '-' }}</td>
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
