@php
$invoiceNo = 'INV-'.str_pad((string) $system->id, 4, '0', STR_PAD_LEFT).'-'.now()->format('Ymd');
$currency = $system->price_currency;
$paid = $system->price === null ? false : $system->isFullyPaid();

$money = function ($amount) use ($currency) {
    $formatted = number_format((float) $amount, $currency === 'KHR' ? 0 : 2);

    return $currency === 'KHR' ? 'KHR '.$formatted : '$'.$formatted;
};

$payMoney = function ($amount, $cur) {
    $formatted = number_format((float) $amount, $cur === 'KHR' ? 0 : 2);

    return $cur === 'KHR' ? 'KHR '.$formatted : '$'.$formatted;
};
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; line-height: 1.3; }
        .accent { color: #4f46e5; }
        .muted { color: #6b7280; }
        .label { font-size: 7.5px; letter-spacing: 1px; text-transform: uppercase; color: #6b7280; font-weight: bold; }
        .title { font-size: 20px; font-weight: bold; letter-spacing: 4px; }
        .badge { font-size: 8px; font-weight: bold; padding: 2px 8px; border-radius: 8px; }
        .badge-paid { background-color: #dcfce7; color: #166534; }
        .badge-due { background-color: #fef9c3; color: #854d0e; }
        .bar { height: 3px; background-color: #4f46e5; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; page-break-inside: avoid; }
        th { text-align: left; border-bottom: 2px solid #1f2937; padding: 4px 4px; font-size: 8px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; }
        td { padding: 4px 4px; border-bottom: 1px solid #e5e7eb; vertical-align: top; font-size: 9.5px; }
        .noborder td, .noborder th { border-bottom: none; }
        .right { text-align: right; }
        .box { border: 1px solid #e5e7eb; padding: 6px 8px; }
        .totals td { border-bottom: none; padding: 2px 4px; font-size: 9.5px; }
        .balance { background-color: #f3f4f6; border: 1px solid #e5e7eb; padding: 6px 8px; font-size: 14px; font-weight: bold; }
        .note { background-color: #f8f9fb; border: 1px solid #e5e7eb; padding: 5px 8px; margin-top: 6px; font-size: 8.5px; page-break-inside: avoid; }
    </style>
</head>
<body>
    {{-- Header --}}
    <table class="noborder" style="margin-bottom:2px">
        <tr>
            <td class="noborder" style="width:60%; vertical-align:middle">
                <x-brand-logo variant="banner" :pdf="true" width="265px" alt="FS Tech Systems" />
            </td>
            <td class="noborder right" style="width:50%">
                <span class="title" style="font-size:22px; letter-spacing:4px">INVOICE</span><br>
                <span class="muted" style="font-size:8.5px">No. {{ $invoiceNo }}</span><br>
                <span class="muted" style="font-size:8.5px">Date: {{ now()->format('M j, Y') }}</span><br>
                @if($system->price !== null)
                    <span class="badge {{ $paid ? 'badge-paid' : 'badge-due' }}" style="font-size:8.5px">{{ $paid ? 'PAID IN FULL' : 'BALANCE DUE' }}</span>
                @endif
            </td>
        </tr>
    </table>
    <div class="bar"></div>

    {{-- Billed to / Project --}}
    <table class="noborder" style="margin-bottom:10px">
        <tr>
            <td class="noborder" style="width:50%; border:1px solid #e5e7eb; padding:8px 10px">
                <span class="label">Billed To</span><br>
                <strong>{{ $system->client_name ?: '—' }}</strong><br>
                @if($system->client_phone)Phone: {{ $system->client_phone }}<br>@endif
                @if($system->client_telegram)Telegram: {{ $system->client_telegram }}<br>@endif
                @if($system->client_email)Email: {{ $system->client_email }}<br>@endif
                @if($system->client_address)Address: {{ $system->client_address }}<br>@endif
            </td>
            <td class="noborder" style="width:6px; border:none"></td>
            <td class="noborder" style="width:50%; border:1px solid #e5e7eb; padding:8px 10px">
                <span class="label">Project</span><br>
                <strong>{{ $system->name }}</strong><br>
                @if($system->description)<span class="muted">{{ \Illuminate\Support\Str::limit($system->description, 150) }}</span><br>@endif
                @if($system->payment_notes)
                    <span class="label">Payment Terms</span><br>{{ $system->payment_notes }}
                @endif
            </td>
        </tr>
    </table>

    {{-- Items --}}
    <table>
        <thead>
        <tr>
            <th>Description</th>
            <th class="right" style="width:26%">Amount</th>
        </tr>
        </thead>
        <tbody>
            @forelse($costs as $cost)
                <tr>
                    <td>
                        <strong>{{ $cost->categoryLabel() }}</strong>
                        @if($cost->title && $cost->title !== $cost->categoryLabel())<br><span class="muted">{{ $cost->title }}</span>@endif
                    </td>
                    <td class="right"><strong>{{ $payMoney($cost->amount, $cost->currency) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td>
                        <strong>System build &amp; development — {{ $system->name }}</strong>
                        @if($system->description && strlen($system->description) <= 80)<br><span class="muted">{{ $system->description }}</span>@endif
                    </td>
                    <td class="right"><strong>{{ $system->price === null ? '—' : $money($system->price) }}</strong></td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Settlement Row: Payment Schedule (Left) & Totals (Right) --}}
    <table class="noborder" style="width:100%; margin-top:8px; page-break-inside:avoid">
        <tr>
            @if($milestones->isNotEmpty())
                <td class="noborder" style="width:58%; vertical-align:top; padding-right:12px">
                    <div class="box" style="padding:6px 8px">
                        <span class="label" style="display:block; margin-bottom:3px">Payment Schedule</span>
                        <table style="width:100%">
                            <thead>
                            <tr>
                                <th>Phase</th>
                                <th class="right" style="width:14%">%</th>
                                <th class="right" style="width:26%">Amount</th>
                                <th class="right" style="width:20%">Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($milestones as $milestone)
                                <tr>
                                    <td>{{ $milestone->title }}</td>
                                    <td class="right muted">{{ rtrim(rtrim(number_format((float) $milestone->percentage, 2), '0'), '.') }}%</td>
                                    <td class="right"><strong>{{ $milestone->formatAmount() }}</strong></td>
                                    <td class="right" style="{{ $milestone->isPaid() ? 'color:#166534; font-weight:bold' : 'color:#6b7280' }}">
                                        {{ $milestone->isPaid() ? 'Paid' : 'Pending' }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </td>
            @else
                <td class="noborder" style="width:55%"></td>
            @endif

            <td class="noborder" style="width:{{ $milestones->isNotEmpty() ? '42%' : '45%' }}; vertical-align:top">
                <table class="noborder totals" style="width:100%">
                    <tr>
                        <td class="muted noborder">Total price</td>
                        <td class="right noborder"><strong>{{ $system->price === null ? '—' : $money($system->price) }}</strong></td>
                    </tr>
                    <tr>
                        <td class="muted noborder">Amount paid</td>
                        <td class="right noborder" style="color:#166534">- {{ $money($system->paid_amount) }}</td>
                    </tr>
                </table>
                <table class="noborder" style="width:100%; margin-top:3px">
                    <tr>
                        <td class="noborder balance">
                            <span>{{ $paid ? 'Balance' : 'Balance due' }}</span>
                            <span class="right" style="float:right {{ $paid ? '; color:#166534' : '' }}">{{ $paid ? $money(0) : ($system->price === null ? '—' : $money($system->balanceDue())) }}</span>
                        </td>
                    </tr>
                </table>
                <div class="muted right" style="font-size:7.5px; margin-top:3px; text-align:center">All totals in {{ $currency }}</div>
            </td>
        </tr>
    </table>

    {{-- Centered KHQR Underneath Payment Stages & Totals --}}
    <table class="noborder" style="width:100%; margin-top:6px; page-break-inside:avoid">
        <tr>
            <td class="noborder" style="text-align:center; vertical-align:middle">
                <div class="box" style="display:inline-block; padding:5px 14px; text-align:center">
                    <div style="background-color:#e11d48; color:#ffffff; font-size:8px; font-weight:bold; letter-spacing:1px; padding:2px 6px; border-radius:3px; display:inline-block; margin-bottom:3px">KHQR</div>
                    <img src="{{ str_replace('\\', '/', public_path('images/khqr-payment.jpg')) }}" style="height:76px; width:auto; max-width:100%; object-fit:contain; display:block; margin:0 auto">
                    <strong style="font-size:9px; display:block; margin-top:3px; color:#111827">FARORS MATH</strong>
                    <span class="muted" style="font-size:7.5px; display:block">Scan to Pay</span>
                </div>
            </td>
        </tr>
    </table>

    {{-- Maintenance --}}
    @if($system->maintenance_amount !== null && $system->maintenance_amount > 0)
        <div class="note">
            <strong>Maintenance:</strong>
            {{ $money($system->maintenance_amount) }}
            @if($system->maintenance_cycle)
                {{ strtolower($system->maintenanceCycleLabel()) }}
                @if($system->maintenance_starts_at) — starting {{ $system->maintenance_starts_at->format('M j, Y') }}@endif
            @endif
        </div>
    @endif

    {{-- Notes --}}
    @if($system->notes)
        <div class="note">
            <strong>Notes:</strong><br>
            {!! nl2br(e($system->notes)) !!}
        </div>
    @endif

    {{-- Provider & Brand Contact Info --}}
    <table class="noborder" style="margin-top:14px; padding-top:8px; border-top:1px solid #e5e7eb; width:100%; page-break-inside:avoid">
        <tr>
            <td class="noborder" style="width:60%">
                <strong style="color:#4f46e5; font-size:11px">FS Tech Systems</strong><br>
                <span class="muted" style="font-size:9px">Professional Software & Digital Solutions</span><br>
                <span style="font-size:9px; color:#4b5563">
                    Phone: 0967444964 &nbsp;|&nbsp; Email: mathfarors@gmail.com<br>
                    Telegram: https://t.me/farors07 &nbsp;|&nbsp; Facebook: FS Tech Systems
                </span>
            </td>
            <td class="noborder right" style="width:40%; vertical-align:bottom">
                <span class="muted" style="font-size:8px">Generated on {{ now()->format('M j, Y') }}</span><br>
                <strong style="font-size:9px">{{ $invoiceNo }}</strong>
            </td>
        </tr>
    </table>

</body>
</html>
