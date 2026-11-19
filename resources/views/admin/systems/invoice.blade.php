@php
    $invoiceNo = 'INV-'.str_pad((string) $system->id, 4, '0', STR_PAD_LEFT).'-'.now()->format('Ymd');
    $currency = $system->price_currency ?: 'USD';
    $hasPrice = $system->price !== null;
    $paid = $hasPrice && $system->isFullyPaid();
    $balance = $system->balanceDue();
    $paidAmount = (float) $system->paid_amount;
    $isPartial = $hasPrice && ! $paid && $paidAmount > 0;
    $statusLabel = ! $hasPrice ? 'Pricing pending' : ($paid ? 'Paid in full' : ($isPartial ? 'Partially paid' : 'Balance due'));
    $statusTone = ! $hasPrice ? 'pending' : ($paid ? 'paid' : ($isPartial ? 'partial' : 'due'));
    $itemCount = max(1, $costs->count());
    $settledStages = $milestones->filter(fn ($milestone) => $milestone->isPaid())->count();
    $hasClientDetails = $system->client_name || $system->client_phone || $system->client_email || $system->client_telegram || $system->client_address;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoiceNo }} — {{ $system->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            background: #eef1f7;
            color: #0f172a;
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            padding: 36px 16px 116px;
            -webkit-font-smoothing: antialiased;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .invoice-sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 18px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 32px 64px -32px rgba(15, 23, 42, .35), 0 0 0 1px rgba(15, 23, 42, .04);
        }

        .inv-hero {
            position: relative;
            overflow: hidden;
            padding: 28px 32px 32px;
            background: linear-gradient(135deg, #0b0f24 0%, #161a3d 52%, #241a4e 100%);
            color: #ffffff;
        }
        .inv-ring {
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, .07);
        }
        .inv-ring-1 { width: 270px; height: 270px; top: -160px; right: 30%; }
        .inv-ring-2 { width: 190px; height: 190px; bottom: -130px; left: 16%; }
        .inv-hero-inner {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .inv-brand { display: flex; align-items: center; }
        .inv-brand-mark {
            width: 44px;
            height: 44px;
            flex: 0 0 44px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: .03em;
            color: #ffffff;
            background: linear-gradient(140deg, #6366f1, #8b5cf6 55%, #a855f7);
            border: 1px solid rgba(255, 255, 255, .22);
        }
        .inv-brand-text strong {
            display: block;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: -.01em;
        }
        .inv-brand-text small {
            display: block;
            margin-top: 3px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .52);
        }
        .inv-heading { text-align: right; }
        .inv-doc {
            font-size: 32px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: .22em;
            text-transform: uppercase;
        }
        .inv-meta {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 11px;
            font-size: 13px;
            color: rgba(255, 255, 255, .75);
        }
        .inv-meta-label {
            margin-right: 5px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .55);
        }
        .inv-meta-dot {
            width: 4px;
            height: 4px;
            margin: 0 9px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .4);
        }
        .inv-status {
            display: inline-flex;
            align-items: center;
            margin-top: 13px;
            padding: 6px 14px;
            border: 1px solid transparent;
            border-radius: 99px;
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
        }
        .inv-status-dot {
            width: 5px;
            height: 5px;
            margin-right: 6px;
            border-radius: 50%;
            background: currentColor;
        }
        .inv-status-paid { background: rgba(16, 185, 129, .15); border-color: rgba(52, 211, 153, .35); color: #6ee7b7; }
        .inv-status-partial { background: rgba(245, 158, 11, .16); border-color: rgba(251, 191, 36, .35); color: #fcd34d; }
        .inv-status-due { background: rgba(248, 113, 113, .14); border-color: rgba(252, 165, 165, .35); color: #fca5a5; }
        .inv-status-pending { background: rgba(148, 163, 184, .14); border-color: rgba(203, 213, 225, .3); color: #cbd5e1; }
        .inv-hero-accent {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 4px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6 55%, #a855f7);
        }

        .inv-body {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            padding: 28px 32px 24px;
        }

        .inv-parties { display: flex; align-items: stretch; }
        .inv-parties > section { flex: 1 1 0; min-width: 0; }
        .inv-parties > section + section { margin-left: 16px; }
        .inv-card {
            height: 100%;
            padding: 18px 20px;
            border: 1px solid #e6eaf2;
            border-radius: 14px;
            background: #ffffff;
        }
        .inv-card-head { display: flex; align-items: center; margin-bottom: 12px; }
        .inv-icon {
            width: 32px;
            height: 32px;
            flex: 0 0 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            border-radius: 8px;
            background: #eef2ff;
            color: #4f46e5;
            font-size: 14px;
        }
        .inv-card-label {
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: #475569;
        }
        .inv-client-name, .inv-project-name {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -.01em;
            color: #0f172a;
            overflow-wrap: anywhere;
        }
        .inv-client-empty { font-size: 14px; font-weight: 500; color: #64748b; }
        .inv-contact { list-style: none; margin: 9px 0 0; padding: 0; font-size: 13.5px; color: #334155; }
        .inv-contact li { display: flex; align-items: flex-start; margin-top: 6px; line-height: 1.5; }
        .inv-contact li i { width: 18px; flex: 0 0 18px; margin-top: 2px; font-size: 13px; color: #64748b; }
        .inv-contact li span { min-width: 0; overflow-wrap: anywhere; }
        .inv-tag-row { margin-top: 8px; }
        .inv-tag {
            display: inline-block;
            margin: 3px 6px 0 0;
            padding: 4px 10px;
            border: 1px solid #e2e8f0;
            border-radius: 99px;
            background: #f1f5f9;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: #334155;
        }
        .inv-desc { margin: 12px 0 0; font-size: 13.5px; line-height: 1.6; color: #475569; }
        .inv-terms { margin-top: 12px; padding-top: 11px; border-top: 1px dashed #e2e8f0; }
        .inv-terms p { margin: 4px 0 0; font-size: 13px; line-height: 1.55; color: #334155; white-space: pre-line; }

        .inv-section { margin-top: 28px; }
        .inv-section-head {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 10px;
        }
        .inv-section-head h2 {
            margin: 0;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: #475569;
        }
        .inv-section-meta { font-size: 12.5px; font-weight: 600; color: #64748b; }

        .inv-table { width: 100%; border-collapse: collapse; }
        .inv-table th {
            padding: 0 12px 10px;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #64748b;
        }
        .inv-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #f1f4f9;
            vertical-align: top;
            font-size: 14px;
            color: #1e293b;
        }
        .inv-table tbody tr:last-child td { border-bottom: 2px solid #e2e8f0; }
        .inv-index { width: 42px; font-size: 13px; font-weight: 700; color: #94a3b8; }
        .inv-item-title { font-size: 15px; font-weight: 700; color: #0f172a; }
        .inv-item-sub { margin-top: 4px; font-size: 13px; line-height: 1.5; color: #64748b; }
        .inv-amount { font-size: 15px; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .inv-right { text-align: right; }
        .inv-muted { color: #64748b; }

        .inv-table-stages td { padding: 10px 12px; font-size: 12px; }
        .inv-stage-name { font-weight: 600; color: #1e293b; }
        .inv-chip {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border: 1px solid transparent;
            border-radius: 99px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .07em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .inv-chip-dot { width: 4px; height: 4px; margin-right: 5px; border-radius: 50%; background: currentColor; }
        .inv-chip-paid { background: #ecfdf5; border-color: #bbf7d0; color: #047857; }
        .inv-chip-wait { background: #f8fafc; border-color: #e2e8f0; color: #64748b; }
        .inv-stage-date { display: block; margin-top: 4px; font-size: 10px; font-weight: 500; color: #94a3b8; }

        .inv-summary { display: flex; justify-content: flex-end; margin-top: 18px; }
        .inv-summary-card {
            width: 350px;
            max-width: 100%;
            padding: 16px 18px 18px;
            border: 1px solid #e6eaf2;
            border-radius: 14px;
            background: #f8fafc;
        }
        .inv-sum-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 6px 0;
            font-size: 14px;
            color: #475569;
        }
        .inv-sum-row strong { font-size: 15px; font-weight: 700; color: #1e293b; font-variant-numeric: tabular-nums; }
        .inv-sum-row strong.inv-green { color: #059669; }
        .inv-sum-divider { height: 1px; margin: 10px 0 12px; background: #e2e8f0; }
        .inv-balance {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 16px;
            border: 1px solid transparent;
            border-radius: 10px;
        }
        .inv-balance span {
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
        }
        .inv-balance strong {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -.01em;
            font-variant-numeric: tabular-nums;
        }
        .inv-balance-paid { background: #ecfdf5; border-color: #bbf7d0; color: #047857; }
        .inv-balance-due, .inv-balance-partial {
            background: linear-gradient(135deg, #111633, #1d1a46);
            border-color: rgba(255, 255, 255, .08);
            color: #ffffff;
        }
        .inv-balance-due span, .inv-balance-partial span { color: rgba(255, 255, 255, .78); }
        .inv-balance-pending { background: #f1f5f9; border-color: #e2e8f0; color: #334155; }
        .inv-sum-note { margin-top: 10px; font-size: 11px; font-weight: 500; letter-spacing: .05em; text-align: center; color: #64748b; }

        .inv-note {
            display: flex;
            align-items: flex-start;
            margin-top: 14px;
            padding: 14px 16px;
            border: 1px solid #e6eaf2;
            border-radius: 12px;
            background: #f8fafc;
        }
        .inv-note-indigo { background: #f5f3ff; border-color: #e7e2ff; }
        .inv-note-icon {
            width: 32px;
            height: 32px;
            flex: 0 0 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            border: 1px solid #e6eaf2;
            border-radius: 8px;
            background: #ffffff;
            color: #6366f1;
            font-size: 13px;
        }
        .inv-note-title {
            margin-bottom: 4px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .13em;
            text-transform: uppercase;
            color: #475569;
        }
        .inv-note-body { font-size: 13.5px; line-height: 1.6; color: #334155; }
        .inv-pre { white-space: pre-line; }

        .inv-footer { margin-top: auto; padding-top: 26px; }
        .inv-footer-inner {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 16px;
            border-top: 2px solid #e2e8f0;
        }
        .inv-thanks { font-size: 15px; font-weight: 700; color: #1e293b; }
        .inv-footer-meta { margin-top: 5px; font-size: 12px; color: #475569; }
        .inv-footer-ref { font-size: 11.5px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: #475569; }

        .inv-actions {
            position: fixed;
            left: 50%;
            bottom: 22px;
            z-index: 50;
            transform: translateX(-50%);
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
            max-width: calc(100% - 24px);
            padding: 8px;
            border: 1px solid #dfe3ec;
            border-radius: 14px;
            background: rgba(255, 255, 255, .94);
            box-shadow: 0 18px 40px -18px rgba(15, 23, 42, .38);
            backdrop-filter: blur(12px);
        }
        .inv-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 9px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            background: #ffffff;
            color: #334155;
            font-family: inherit;
            font-size: 12.5px;
            font-weight: 600;
            line-height: 1.2;
            text-decoration: none;
            cursor: pointer;
            transition: background .15s ease, color .15s ease, box-shadow .15s ease;
        }
        .inv-btn:hover { background: #f8fafc; color: #0f172a; }
        .inv-btn i { font-size: 13px; }
        .inv-btn:disabled { opacity: .7; cursor: progress; }
        .inv-btn-primary {
            border-color: transparent;
            background: linear-gradient(135deg, #6366f1, #8b5cf6 60%, #a855f7);
            color: #ffffff;
            box-shadow: 0 10px 20px -10px rgba(99, 102, 241, .7);
        }
        .inv-btn-primary:hover {
            background: linear-gradient(135deg, #6366f1, #8b5cf6 60%, #a855f7);
            color: #ffffff;
            filter: brightness(1.05);
        }
        .inv-spinner {
            width: 12px;
            height: 12px;
            display: inline-block;
            border: 2px solid rgba(15, 23, 42, .18);
            border-top-color: #0f172a;
            border-radius: 50%;
            animation: invSpin .7s linear infinite;
        }
        @keyframes invSpin { to { transform: rotate(360deg); } }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        @media print {
            html, body {
                width: 100% !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            .invoice-sheet {
                width: 100% !important;
                max-width: 210mm !important;
                min-height: auto !important;
                height: auto !important;
                margin: 0 auto !important;
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                display: block !important;
            }
            .inv-hero {
                padding: 30px 32px 34px !important;
                background: #0b0f24 !important;
                background: linear-gradient(135deg, #0b0f24 0%, #161a3d 52%, #241a4e 100%) !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            .inv-hero-accent {
                background: #6366f1 !important;
                background: linear-gradient(90deg, #6366f1, #8b5cf6 55%, #a855f7) !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .inv-hero-inner {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: center !important;
            }
            .inv-brand {
                display: flex !important;
                align-items: center !important;
            }
            .inv-heading {
                text-align: right !important;
                margin-top: 0 !important;
            }
            .inv-doc {
                font-size: 32px !important;
                color: #ffffff !important;
            }
            .inv-meta {
                display: flex !important;
                justify-content: flex-end !important;
                color: rgba(255, 255, 255, .8) !important;
            }
            .inv-status {
                border: 1px solid rgba(255, 255, 255, .25) !important;
            }
            .inv-status-pending {
                background: #1e293b !important;
                color: #f1f5f9 !important;
            }
            .inv-status-paid {
                background: #064e3b !important;
                color: #a7f3d0 !important;
            }
            .inv-status-due {
                background: #7f1d1d !important;
                color: #fecaca !important;
            }
            .inv-parties {
                display: flex !important;
                flex-direction: row !important;
                align-items: stretch !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }
            .inv-parties > section {
                flex: 1 1 0 !important;
                min-width: 0 !important;
                margin-top: 0 !important;
            }
            .inv-parties > section + section {
                margin-left: 16px !important;
                margin-top: 0 !important;
            }
            .inv-card {
                background: #ffffff !important;
                border: 1px solid #cbd5e1 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .inv-icon {
                background: #eef2ff !important;
                color: #4f46e5 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .inv-summary {
                display: flex !important;
                justify-content: flex-end !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }
            .inv-summary-card {
                width: 350px !important;
                background: #f8fafc !important;
                border: 1px solid #cbd5e1 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .inv-balance {
                background: #0f172a !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .inv-balance span {
                color: rgba(255, 255, 255, .85) !important;
            }
            .inv-balance strong {
                color: #ffffff !important;
            }
            .inv-balance-paid {
                background: #ecfdf5 !important;
                color: #047857 !important;
            }
            .inv-balance-paid span, .inv-balance-paid strong {
                color: #047857 !important;
            }
            .inv-balance-pending {
                background: #1e293b !important;
                color: #ffffff !important;
            }
            .inv-balance-pending span, .inv-balance-pending strong {
                color: #ffffff !important;
            }
            .inv-section {
                break-inside: auto !important;
                page-break-inside: auto !important;
            }
            .inv-section-head {
                break-after: avoid !important;
                page-break-after: avoid !important;
            }
            .inv-table tr {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }
            .inv-note {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
                background: #f8fafc !important;
                border: 1px solid #cbd5e1 !important;
            }
            .inv-body {
                padding: 24px 32px 20px !important;
            }
            .inv-footer {
                margin-top: 30px !important;
                padding-top: 16px !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }
            .inv-footer-inner {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: flex-end !important;
            }
            .inv-footer-ref {
                margin-top: 0 !important;
            }
            .no-print {
                display: none !important;
            }
        }

        @media (max-width: 760px) {
            body { padding: 0 0 118px; background: #ffffff; }
            .invoice-sheet { width: 100%; min-height: 0; border-radius: 0; box-shadow: none; }
            .inv-hero { padding: 22px 20px 26px; }
            .inv-hero-inner { flex-direction: column; }
            .inv-heading { margin-top: 20px; text-align: left; }
            .inv-meta { justify-content: flex-start; }
            .inv-doc { font-size: 24px; }
            .inv-status { margin-top: 11px; }
            .inv-body { padding: 22px 18px 24px; }
            .inv-parties { flex-direction: column; }
            .inv-parties > section + section { margin-left: 0; margin-top: 12px; }
            .inv-summary { justify-content: stretch; }
            .inv-summary-card { width: 100%; }
            .inv-hide-sm { display: none; }
            .inv-footer-inner { flex-direction: column; align-items: flex-start; }
            .inv-footer-ref { margin-top: 8px; }
            .inv-actions {
                left: 12px;
                right: 12px;
                bottom: 12px;
                transform: none;
                max-width: none;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 7px;
                padding: 7px;
                border-radius: 13px;
            }
            .inv-btn { width: 100%; padding: 10px 8px; font-size: 12px; }
        }
    </style>
</head>
<body>
<div class="invoice-sheet">
    <header class="inv-hero">
        <span class="inv-ring inv-ring-1" aria-hidden="true"></span>
        <span class="inv-ring inv-ring-2" aria-hidden="true"></span>
        <div class="inv-hero-inner">
            <div class="inv-brand">
                <x-brand-logo variant="mark" :height="76" alt="FS Tech Systems" />
                <span class="inv-brand-text" style="margin-left: 18px;">
                    <strong style="font-size: 26px; font-weight: 800; letter-spacing: -0.02em; line-height: 1.1;">FS Tech Systems</strong>
                    <small style="color: rgba(255,255,255,0.6); font-size: 8px; letter-spacing: 0.12em; text-transform: uppercase; font-weight: 600; display: block; margin-top: 4px;">Software Engineering &amp; System Architecture</small>
                </span>
            </div>
            <div class="inv-heading">
                <div class="inv-doc">Invoice</div>
                <div class="inv-meta">
                    <span><span class="inv-meta-label">No.</span> {{ $invoiceNo }}</span>
                    <span class="inv-meta-dot"></span>
                    <span><span class="inv-meta-label">Issued</span> {{ now()->format('M j, Y') }}</span>
                </div>
                <span class="inv-status inv-status-{{ $statusTone }}">
                    <span class="inv-status-dot"></span>{{ $statusLabel }}
                </span>
            </div>
        </div>
        <span class="inv-hero-accent" aria-hidden="true"></span>
    </header>

    <main class="inv-body">
        <div class="inv-parties">
            <section class="inv-card">
                <div class="inv-card-head">
                    <span class="inv-icon"><i class="bi bi-person-badge"></i></span>
                    <span class="inv-card-label">Billed to</span>
                </div>
                @if($hasClientDetails)
                    <div class="inv-client-name">{{ $system->client_name ?: 'Client' }}</div>
                    <ul class="inv-contact">
                        @if($system->client_phone)
                            <li><i class="bi bi-telephone"></i><span>{{ $system->client_phone }}</span></li>
                        @endif
                        @if($system->client_email)
                            <li><i class="bi bi-envelope"></i><span>{{ $system->client_email }}</span></li>
                        @endif
                        @if($system->client_telegram)
                            <li><i class="bi bi-telegram"></i><span>{{ $system->client_telegram }}</span></li>
                        @endif
                        @if($system->client_address)
                            <li><i class="bi bi-geo-alt"></i><span class="inv-pre">{{ $system->client_address }}</span></li>
                        @endif
                    </ul>
                @else
                    <div class="inv-client-empty">Client details not recorded</div>
                @endif
            </section>

            <section class="inv-card">
                <div class="inv-card-head">
                    <span class="inv-icon"><i class="bi bi-layers"></i></span>
                    <span class="inv-card-label">Project</span>
                </div>
                <div class="inv-project-name">{{ $system->name }}</div>
                <div class="inv-tag-row">
                    <span class="inv-tag">{{ $system->stackLabel() }}</span>
                </div>
                @if($system->description)
                    <p class="inv-desc">{{ \Illuminate\Support\Str::limit($system->description, 170) }}</p>
                @endif
                @if($system->payment_notes)
                    <div class="inv-terms">
                        <span class="inv-card-label">Payment terms</span>
                        <p>{{ $system->payment_notes }}</p>
                    </div>
                @endif
            </section>
        </div>

        <section class="inv-section">
            <div class="inv-section-head">
                <h2>Line items</h2>
                <span class="inv-section-meta">{{ $itemCount }} {{ \Illuminate\Support\Str::plural('item', $itemCount) }}</span>
            </div>
            <table class="inv-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th class="inv-right">Amount</th>
                </tr>
                </thead>
                <tbody>
                @forelse($costs as $cost)
                    <tr>
                        <td class="inv-index">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</td>
                        <td>
                            <div class="inv-item-title">{{ $cost->categoryLabel() }}</div>
                            @if($cost->title && $cost->title !== $cost->categoryLabel())
                                <div class="inv-item-sub">{{ $cost->title }}</div>
                            @endif
                        </td>
                        <td class="inv-right inv-amount">{{ \App\Models\System::formatMoney((float) $cost->amount, $cost->currency ?: $currency) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="inv-index">01</td>
                        <td>
                            <div class="inv-item-title">System build &amp; development</div>
                            @if($system->description && strlen($system->description) <= 80)
                                <div class="inv-item-sub">{{ $system->description }}</div>
                            @endif
                        </td>
                        <td class="inv-right inv-amount">{{ $system->formatPrice() }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </section>

        <div class="inv-summary">
            <div class="inv-summary-card">
                <div class="inv-sum-row">
                    <span>Total</span>
                    <strong>{{ $system->formatPrice() }}</strong>
                </div>
                <div class="inv-sum-row">
                    <span>Amount paid</span>
                    <strong class="{{ $paidAmount > 0 ? 'inv-green' : '' }}">{{ $paidAmount > 0 ? '− ' : '' }}{{ \App\Models\System::formatMoney($paidAmount, $currency) }}</strong>
                </div>
                <div class="inv-sum-divider"></div>
                <div class="inv-balance inv-balance-{{ $statusTone }}">
                    <span>{{ $paid ? 'Balance' : 'Balance due' }}</span>
                    <strong>{{ $paid ? \App\Models\System::formatMoney(0, $currency) : ($balance === null ? '—' : \App\Models\System::formatMoney($balance, $currency)) }}</strong>
                </div>
                <div class="inv-sum-note">All totals in {{ $currency }}</div>
            </div>
        </div>

        @if($milestones->isNotEmpty())
            <section class="inv-section">
                <div class="inv-section-head">
                    <h2>Payment stages</h2>
                    <span class="inv-section-meta">{{ $settledStages }} of {{ $milestones->count() }} settled</span>
                </div>
                <table class="inv-table inv-table-stages">
                    <thead>
                    <tr>
                        <th>Stage</th>
                        <th class="inv-right inv-hide-sm">Share</th>
                        <th class="inv-right">Amount</th>
                        <th class="inv-right">Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($milestones as $milestone)
                        <tr>
                            <td><span class="inv-stage-name">{{ $milestone->title }}</span></td>
                            <td class="inv-right inv-hide-sm inv-muted">{{ rtrim(rtrim(number_format((float) $milestone->percentage, 2), '0'), '.') }}%</td>
                            <td class="inv-right inv-amount">{{ $milestone->formatAmount() }}</td>
                            <td class="inv-right">
                                @if($milestone->isPaid())
                                    <span class="inv-chip inv-chip-paid"><span class="inv-chip-dot"></span>Paid</span>
                                    @if($milestone->paid_at)
                                        <span class="inv-stage-date">{{ $milestone->paid_at->format('M j, Y') }}</span>
                                    @endif
                                @else
                                    <span class="inv-chip inv-chip-wait"><span class="inv-chip-dot"></span>Pending</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </section>
        @endif

        @if($system->maintenance_amount !== null && $system->maintenance_amount > 0)
            <div class="inv-note inv-note-indigo">
                <span class="inv-note-icon"><i class="bi bi-arrow-repeat"></i></span>
                <div>
                    <div class="inv-note-title">Maintenance</div>
                    <div class="inv-note-body">
                        {{ \App\Models\System::formatMoney((float) $system->maintenance_amount, $currency) }}
                        @if($system->maintenance_cycle)
                            {{ strtolower($system->maintenanceCycleLabel()) }}
                        @endif
                        @if($system->maintenance_starts_at)
                            — starting {{ $system->maintenance_starts_at->format('M j, Y') }}
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if($system->notes)
            <div class="inv-note">
                <span class="inv-note-icon"><i class="bi bi-journal-text"></i></span>
                <div>
                    <div class="inv-note-title">Notes</div>
                    <div class="inv-note-body inv-pre">{{ $system->notes }}</div>
                </div>
            </div>
        @endif

        <footer class="inv-footer">
            <div class="inv-footer-inner" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px;">
                <div>
                    <div class="inv-thanks">{{ $paid ? 'Thank you — this invoice is settled in full.' : 'Thank you for your business.' }}</div>
                    <div class="inv-footer-meta" style="margin-top: 6px; line-height: 1.6;">
                        <strong>FS Tech Systems</strong> · Professional Digital Solutions<br>
                        <span><i class="bi bi-telephone-fill" style="margin-right: 4px;"></i> 0967444964</span>
                        <span style="margin: 0 8px;">·</span>
                        <span><i class="bi bi-envelope-fill" style="margin-right: 4px;"></i> mathfarors@gmail.com</span>
                        <span style="margin: 0 8px;">·</span>
                        <span><i class="bi bi-telegram" style="margin-right: 4px;"></i> t.me/farors07</span>
                        <span style="margin: 0 8px;">·</span>
                        <span><i class="bi bi-facebook" style="margin-right: 4px;"></i> FS Tech Systems</span>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div class="inv-footer-ref">{{ $invoiceNo }}</div>
                    <div style="font-size: 10px; color: #94a3b8; margin-top: 4px;">Generated {{ now()->format('M j, Y') }}</div>
                </div>
            </div>
        </footer>
    </main>
</div>

<div class="inv-actions no-print" role="toolbar" aria-label="Invoice actions">
    <a href="{{ route('systems.show', [$system, 'tab' => 'finance']) }}" class="inv-btn"><i class="bi bi-arrow-left"></i>Back</a>
    <button type="button" class="inv-btn" id="saveImageBtn" onclick="fstSaveInvoiceImage(this)"><i class="bi bi-image"></i>Save image</button>
    <button type="button" class="inv-btn inv-btn-primary" onclick="window.print()"><i class="bi bi-printer"></i>Print / Save as PDF</button>
</div>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
    async function fstSaveInvoiceImage(btn) {
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="inv-spinner"></span>Rendering...';

        try {
            if (document.fonts && document.fonts.ready) {
                await document.fonts.ready;
            }
            await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));

            const sheet = document.querySelector('.invoice-sheet');
            const canvas = await html2canvas(sheet, {
                scale: 2,
                backgroundColor: '#ffffff',
                useCORS: true,
                windowWidth: sheet.scrollWidth,
            });

            const link = document.createElement('a');
            link.download = 'invoice-{{ $system->slug }}-{{ now()->format('Ymd') }}.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        } catch (error) {
            alert('Could not create the image. Please try printing or saving as PDF instead.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    }
</script>

@if($autoPrint)
<script>window.addEventListener('load', () => window.print());</script>
@endif
</body>
</html>
