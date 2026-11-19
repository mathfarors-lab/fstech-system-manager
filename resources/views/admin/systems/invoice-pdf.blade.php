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
        @page { margin: 46px 48px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        .accent { color: #4f46e5; }
        .muted { color: #6b7280; }
        .label { font-size: 8px; letter-spacing: 1px; text-transform: uppercase; color: #6b7280; font-weight: bold; }
        .title { font-size: 24px; font-weight: bold; letter-spacing: 6px; }
        .badge { font-size: 9px; font-weight: bold; padding: 3px 10px; border-radius: 10px; }
        .badge-paid { background-color: #dcfce7; color: #166534; }
        .badge-due { background-color: #fef9c3; color: #854d0e; }
        .bar { height: 4px; background-color: #4f46e5; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; border-bottom: 2px solid #1f2937; padding: 6px 5px; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; }
        td { padding: 7px 5px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .noborder td, .noborder th { border-bottom: none; }
        .right { text-align: right; }
        .box { border: 1px solid #e5e7eb; padding: 10px 12px; }
        .totals td { border-bottom: none; padding: 3px 5px; }
        .balance { background-color: #f3f4f6; border: 1px solid #e5e7eb; padding: 10px 12px; font-size: 17px; font-weight: bold; }
        .note { background-color: #f8f9fb; border: 1px solid #e5e7eb; padding: 9px 12px; margin-top: 12px; }
    </style>
</head>
<body>
    {{-- Header --}}
    <table class="noborder" style="margin-bottom:4px">
        <tr>
            <td class="noborder" style="width:60%; vertical-align:middle">
                <x-brand-logo variant="banner" :pdf="true" width="270px" alt="FS Tech Systems" />
            </td>
            <td class="noborder right" style="width:50%">
                <span class="title">INVOICE</span><br>
                <span class="muted">No. {{ $invoiceNo }}</span><br>
                <span class="muted">Date: {{ now()->format('M j, Y') }}</span><br>
                @if($system->price !== null)
                    <span class="badge {{ $paid ? 'badge-paid' : 'badge-due' }}">{{ $paid ? 'PAID IN FULL' : 'BALANCE DUE' }}</span>
                @endif
            </td>
        </tr>
    </table>
    <div class="bar"></div>

    {{-- Billed to / Project --}}
    <table class="noborder" style="margin-bottom:20px">
        <tr>
            <td class="noborder" style="width:50%; border:1px solid #e5e7eb; padding:10px 12px">
                <span class="label">Billed To</span><br>
                <strong>{{ $system->client_name ?: '—' }}</strong><br>
                @if($system->client_phone)Phone: {{ $system->client_phone }}<br>@endif
                @if($system->client_telegram)Telegram: {{ $system->client_telegram }}<br>@endif
                @if($system->client_email)Email: {{ $system->client_email }}<br>@endif
                @if($system->client_address)Address: {{ $system->client_address }}<br>@endif
            </td>
            <td class="noborder" style="width:6px; border:none"></td>
            <td class="noborder" style="width:50%; border:1px solid #e5e7eb; padding:10px 12px">
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

    {{-- Totals --}}
    <table class="noborder totals" style="width:50%; margin-left:50%; margin-top:10px">
        <tr>
            <td class="muted noborder">Total price</td>
            <td class="right noborder"><strong>{{ $system->price === null ? '—' : $money($system->price) }}</strong></td>
        </tr>
        <tr>
            <td class="muted noborder">Amount paid</td>
            <td class="right noborder" style="color:#166534">- {{ $money($system->paid_amount) }}</td>
        </tr>
    </table>
    <table class="noborder" style="width:50%; margin-left:50%; margin-top:4px">
        <tr>
            <td class="noborder balance">
                <span>{{ $paid ? 'Balance' : 'Balance due' }}</span>
                <span class="right" style="float:right {{ $paid ? '; color:#166534' : '' }}">{{ $paid ? $money(0) : ($system->price === null ? '—' : $money($system->balanceDue())) }}</span>
            </td>
        </tr>
    </table>

    {{-- Payment schedule --}}
    @if($milestones->isNotEmpty())
        <div class="label" style="margin-top:18px">Payment Schedule</div>
        <table style="margin-top:6px">
            <thead>
            <tr>
                <th>Phase</th>
                <th class="right" style="width:12%">%</th>
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
                        {{ $milestone->isPaid() ? 'Paid'.($milestone->paid_at ? ' · '.$milestone->paid_at->format('M j, Y') : '') : 'Pending' }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

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
    <table class="noborder" style="margin-top:24px; padding-top:12px; border-top:1px solid #e5e7eb; width:100%">
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
