<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin - Bestellingen | PrintMijnPDF</title>
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            color: #1a1a2e;
            line-height: 1.6;
        }
        .header {
            background: linear-gradient(135deg, #e63946 0%, #d62839 100%);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { font-size: 1.5rem; font-weight: 700; }
        .header-right { display: flex; align-items: center; gap: 1.5rem; }
        .header a { color: white; text-decoration: none; opacity: 0.9; }
        .header a:hover { opacity: 1; }
        .header .user-info { font-size: 14px; opacity: 0.9; }
        .header .logout-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }
        .header .logout-btn:hover { background: rgba(255,255,255,0.3); }
        .header .logout-btn svg { width: 16px; height: 16px; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .stat-card .label { font-size: 13px; color: #6c757d; margin-bottom: 4px; }
        .stat-card .value { font-size: 28px; font-weight: 700; color: #1a1a2e; }
        .stat-card.highlight .value { color: #e63946; }
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-header h2 { font-size: 1.1rem; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem 0.75rem; text-align: left; white-space: nowrap; }
        th {
            background: #f8f9fa;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
        }
        tr { border-bottom: 1px solid #e9ecef; }
        tr:hover { background: #f8f9fa; }
        tr:last-child { border-bottom: none; }
        .order-number {
            font-weight: 600;
            color: #e63946;
            font-family: monospace;
            font-size: 13px;
        }
        .customer-name { font-weight: 500; }
        .customer-email { font-size: 13px; color: #6c757d; }
        td.customer { white-space: normal; min-width: 200px; max-width: 240px; overflow-wrap: anywhere; }
        .file-info { font-size: 13px; white-space: normal; }
        .file-name { 
            max-width: 200px; 
            overflow: hidden; 
            text-overflow: ellipsis; 
            white-space: nowrap;
            display: block;
        }
        .file-meta { color: #6c757d; font-size: 12px; }
        .price { font-weight: 600; color: #1a1a2e; }
        .status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status.paid { background: #d4edda; color: #155724; }
        .status.processing { background: #fff3cd; color: #856404; }
        .status.shipped { background: #cce5ff; color: #004085; }
        .status.delivered { background: #e2e3e5; color: #383d41; }
        .status.cancelled { background: #f8d7da; color: #721c24; }
        .status.pending { background: #ffeeba; color: #856404; }
        .checkbox-cell { width: 40px; text-align: center; overflow: visible; text-overflow: clip; }
        .checkbox-cell input[type="checkbox"] {
            width: 18px; height: 18px; cursor: pointer; accent-color: #e63946;
        }
        .bulk-bar {
            display: none;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 1.5rem;
            background: #fff3cd;
            border-bottom: 1px solid #ffc107;
            font-size: 14px;
        }
        .bulk-bar.active { display: flex; }
        .bulk-bar .count { font-weight: 600; }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover { background: #c82333; }
        .btn-danger-outline {
            background: transparent;
            color: #dc3545;
            border: 1px solid #dc3545;
        }
        .btn-danger-outline:hover { background: #dc3545; color: white; }
        /* Acties in rijen van drie, zodat de tabel ook op een 15-inch scherm past */
        .actions-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            width: 250px;
        }
        .actions-grid form { display: contents; }
        /* Vangnet: past de tabel toch niet, dan horizontaal scrollen in plaats van wegvallen */
        .table-wrap { overflow-x: auto; }
        .tabs { display: flex; gap: 4px; }
        .tabs a {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #495057;
            text-decoration: none;
        }
        .tabs a:hover { background: #f8f9fa; }
        .tabs a.active { background: #e63946; color: white; }
        .btn-archive {
            background: #495057;
            color: white;
        }
        .btn-archive:hover { background: #343a40; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border: none;
            border-radius: 8px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-primary {
            background: #e63946;
            color: white;
        }
        .btn-primary:hover { background: #d62839; }
        .btn-secondary {
            background: #f8f9fa;
            color: #495057;
            border: 1px solid #dee2e6;
        }
        .btn-secondary:hover { background: #e9ecef; }
        .btn-success {
            background: #40c057;
            color: white;
        }
        .btn-success:hover { background: #37a34a; }
        .btn svg { width: 16px; height: 16px; }
        .date { font-size: 13px; color: #6c757d; }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 1.5rem;
        }
        .pagination a, .pagination span {
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 14px;
            text-decoration: none;
        }
        .pagination a { background: #f8f9fa; color: #495057; }
        .pagination a:hover { background: #e9ecef; }
        .pagination .current { background: #e63946; color: white; }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }
        .empty-state svg { width: 64px; height: 64px; margin-bottom: 1rem; opacity: 0.5; }
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 14px;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .modal h3 { font-size: 1.25rem; margin-bottom: 1rem; }
        .modal .form-group { margin-bottom: 1rem; }
        .modal label { display: block; font-size: 14px; font-weight: 500; margin-bottom: 6px; }
        .modal input, .modal select {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-family: inherit;
            font-size: 15px;
        }
        .modal input:focus, .modal select:focus {
            outline: none;
            border-color: #e63946;
        }
        .modal [hidden] { display: none !important; }
        .modal.wide { max-width: 520px; max-height: 92vh; overflow-y: auto; }
        .modal .choice { display: flex; gap: 8px; }
        .modal .choice label {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            padding: 10px 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
        }
        .modal .choice input { width: auto; }
        .modal .choice label:has(input:checked) { border-color: #e63946; }
        .postnl-section { border-bottom: 1px solid #e9ecef; padding-bottom: 1rem; margin-bottom: 1rem; }
        .postnl-section .btn, .label-tools .btn { font-size: 14px; padding: 10px 14px; }
        .sandbox-badge { font-size: 11px; background: #fff3cd; color: #856404; border-radius: 4px; padding: 2px 6px; vertical-align: middle; }
        .label-frame { width: 100%; height: max(320px, 48vh); border: 1px solid #dee2e6; border-radius: 8px; margin: 0.75rem 0; }
        .label-tools { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .label-tools a { font-size: 13px; color: #495057; }
        .modal-actions { display: flex; gap: 10px; margin-top: 1.5rem; }
        .modal-actions .btn { flex: 1; justify-content: center; }
        @media (max-width: 1024px) {
            .container { padding: 1rem; }
            th, td { padding: 0.75rem; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 Bestellingen</h1>
        <div class="header-right">
            <span class="user-info">Ingelogd als: {{ session('admin_username', 'Admin') }}</span>
            <a href="/">← Site bekijken</a>
            <form action="{{ route('admin.logout') }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="logout-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Uitloggen
                </button>
            </form>
        </div>
    </div>

    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        <div class="stats">
            <div class="stat-card highlight">
                <div class="label">Totaal bestellingen</div>
                <div class="value">{{ $activeCount + $archivedCount }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Betaald (wacht op print)</div>
                <div class="value">{{ \App\Models\Order::where('status', 'paid')->count() }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Verzonden</div>
                <div class="value">{{ \App\Models\Order::where('status', 'shipped')->count() }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Omzet totaal</div>
                <div class="value">€ {{ number_format(\App\Models\Order::where('status', '!=', 'cancelled')->sum('price_total') / 100, 2, ',', '.') }}</div>
            </div>
        </div>

        <!-- Maandoverzicht facturen -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h2>Maandoverzicht facturen</h2>
                <form method="GET" action="{{ route('admin.invoices.monthly') }}" target="_blank" style="display: flex; gap: 10px; align-items: center;">
                    <select name="month" style="padding: 6px 10px; border: 2px solid #e9ecef; border-radius: 8px; font-family: inherit; font-size: 14px;">
                        @foreach(['januari','februari','maart','april','mei','juni','juli','augustus','september','oktober','november','december'] as $i => $maand)
                            <option value="{{ $i + 1 }}" {{ (int)now()->month === $i + 1 ? 'selected' : '' }}>{{ ucfirst($maand) }}</option>
                        @endforeach
                    </select>
                    <select name="year" style="padding: 6px 10px; border: 2px solid #e9ecef; border-radius: 8px; font-family: inherit; font-size: 14px;">
                        @for($y = now()->year; $y >= 2024; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                    <button type="submit" class="btn btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                        Overzicht genereren
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>{{ $archive ? 'Archief' : 'Bestellingen' }}</h2>
                <nav class="tabs">
                    <a href="{{ route('admin.orders') }}" class="{{ $archive ? '' : 'active' }}">Actief ({{ $activeCount }})</a>
                    <a href="{{ route('admin.orders', ['archief' => 1]) }}" class="{{ $archive ? 'active' : '' }}">Archief ({{ $archivedCount }})</a>
                </nav>
            </div>

            @if($orders->count() > 0)
                <div class="bulk-bar" id="bulkBar">
                    <span><span class="count" id="selectedCount">0</span> geselecteerd</span>
                    @if($archive)
                        <button type="button" class="btn btn-secondary" onclick="confirmBulkUnarchive()">Terugzetten</button>
                    @else
                        <button type="button" class="btn btn-archive" onclick="confirmBulkArchive()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                                <polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/>
                            </svg>
                            Archiveren
                        </button>
                    @endif
                    <button type="button" class="btn btn-danger" onclick="confirmBulkDelete()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                            <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                        Verwijderen
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="clearSelection()">Annuleren</button>
                </div>
                {{-- Bewust leeg: de checkboxes horen er via form="bulkForm" bij. Een
                     formulier om de tabel heen zou de formulieren in de rijen opslokken.
                     De knop in de balk kiest de actie (verwijderen, archiveren, terugzetten). --}}
                <form id="bulkForm" method="POST" action="{{ route('admin.orders.bulk-delete') }}">
                    @csrf
                </form>
                <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th class="checkbox-cell"><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
                            <th>Bestelnr.</th>
                            <th>Klant</th>
                            <th>Bestand</th>
                            <th>Totaal</th>
                            <th>Status</th>
                            <th>Datum</th>
                            <th>Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            <tr>
                                <td class="checkbox-cell">
                                    <input type="checkbox" form="bulkForm" name="order_numbers[]" value="{{ $order->order_number }}" class="order-checkbox" onchange="updateBulkBar()">
                                </td>
                                <td>
                                    <span class="order-number">{{ $order->order_number }}</span>
                                </td>
                                <td class="customer">
                                    <div class="customer-name">{{ $order->customer_name }}</div>
                                    <div class="customer-email">{{ $order->customer_email }}</div>
                                    @if($order->utm_source || $order->gclid || $order->fbclid)
                                    <div class="customer-email" style="color: #6366f1;">{{ $order->attribution_summary }}</div>
                                    @endif
                                </td>
                                <td class="file-info">
                                    <span class="file-name" title="{{ $order->pdf_original_name }}">{{ $order->pdf_original_name }}</span>
                                    <div class="file-meta">
                                        {{ $order->format_label }} • {{ $order->bleed_label }} • {{ $order->pages_label }} pag. • {{ $order->quantity ?? 1 }}× •
                                        @if($order->binding_type === 'booklet')
                                            Geniet
                                        @else
                                            Losbladig {{ $order->print_side === 'double' ? '(2-z)' : '(1-z)' }}
                                        @endif
                                        • 
                                        @if($order->delivery_type === 'pickup')
                                            <span style="color: #0369a1;">🏪 Afhalen</span>
                                        @else
                                            📦 Verzenden
                                        @endif
                                    </div>
                                </td>
                                <td class="price">€ {{ number_format($order->price_total / 100, 2, ',', '.') }}</td>
                                <td>
                                    <span class="status {{ $order->status }}">
                                        @switch($order->status)
                                            @case('pending') In afwachting @break
                                            @case('paid') Betaald @break
                                            @case('processing') In productie @break
                                            @case('shipped') Verzonden @break
                                            @case('delivered') Afgeleverd @break
                                            @case('cancelled') Geannuleerd @break
                                            @default {{ $order->status }}
                                        @endswitch
                                    </span>
                                </td>
                                <td class="date">
                                    {{ $order->created_at->format('d-m-Y') }}<br>
                                    <small>{{ $order->created_at->format('H:i') }}</small>
                                </td>
                                <td>
                                    <div class="actions-grid">
                                    @if($order->pdf_path)
                                        <a href="/admin/orders/{{ $order->order_number }}/pdf" class="btn btn-secondary" title="Download originele PDF">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                                <polyline points="7 10 12 15 17 10"/>
                                                <line x1="12" y1="15" x2="12" y2="3"/>
                                            </svg>
                                            PDF
                                        </a>
                                    @endif
                                    @if($order->pdf_path && $order->status !== 'pending' && $order->status !== 'cancelled')
                                        <a href="/admin/orders/{{ $order->order_number }}/imposed" class="btn btn-primary" title="Download inslag PDF (drukklaar)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                                <line x1="12" y1="3" x2="12" y2="21"/>
                                            </svg>
                                            Inslag
                                        </a>
                                    @endif
                                    <a href="/admin/orders/{{ $order->order_number }}/pakbon" class="btn btn-secondary" target="_blank" title="Pakbon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14 2 14 8 20 8"/>
                                            <line x1="16" y1="13" x2="8" y2="13"/>
                                            <line x1="16" y1="17" x2="8" y2="17"/>
                                            <polyline points="10 9 9 9 8 9"/>
                                        </svg>
                                        Pakbon
                                    </a>
                                    @if($order->isPaid())
                                        @php $invoice = \App\Models\Invoice::where('order_id', $order->id)->first(); @endphp
                                        @if($invoice)
                                            <a href="/admin/orders/{{ $order->order_number }}/invoice" class="btn btn-secondary" target="_blank" title="Download factuur {{ $invoice->invoice_number }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                    <polyline points="14 2 14 8 20 8"/>
                                                    <line x1="9" y1="15" x2="15" y2="15"/>
                                                </svg>
                                                Factuur
                                            </a>
                                            <button type="button" class="btn btn-secondary" onclick="resendInvoice('{{ $order->order_number }}', '{{ $invoice->invoice_number }}', '{{ $order->customer_email }}')" title="Factuur nogmaals mailen naar {{ $order->customer_email }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <line x1="22" y1="2" x2="11" y2="13"/>
                                                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                                                </svg>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-secondary" onclick="createInvoice('{{ $order->order_number }}')" title="Factuur aanmaken voor deze bestelling" style="border: 1px dashed #6c757d;">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                    <polyline points="14 2 14 8 20 8"/>
                                                    <line x1="12" y1="11" x2="12" y2="17"/>
                                                    <line x1="9" y1="14" x2="15" y2="14"/>
                                                </svg>
                                                + Factuur
                                            </button>
                                        @endif
                                    @endif
                                    @if($order->status === 'paid' && !$order->archived_at)
                                        @if($order->delivery_type === 'pickup')
                                            <form method="POST" action="/admin/orders/{{ $order->order_number }}/ship" onsubmit="return confirm({{ \Illuminate\Support\Js::from($order->customer_name . ' krijgt een afhaalmail op ' . $order->customer_email . '. Doorgaan?') }})">
                                                @csrf
                                                <input type="hidden" name="status" value="shipped">
                                                <input type="hidden" name="pickup" value="1">
                                                <button type="submit" class="btn btn-success" title="Markeer als klaar voor afhalen">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                                        <polyline points="9 22 9 12 15 12 15 22"/>
                                                    </svg>
                                                    Klaar voor afhalen
                                                </button>
                                            </form>
                                        @else
                                            @php $postnl = $postnlEnabled && \App\Services\PostNLService::supports($order); @endphp
                                            <button type="button" class="btn btn-success" data-ship="{{ $order->order_number }}" onclick="openShipModal({{ \Illuminate\Support\Js::from([
                                                'orderNumber' => $order->order_number,
                                                'postnl' => $postnl,
                                                'product' => $order->postnl_product ?? \App\Services\PostNLService::defaultProduct($order),
                                                'barcode' => $order->postnl_barcode,
                                                'labelUrl' => $postnl && $order->postnl_label_path ? route('admin.order.label', $order->order_number) : null,
                                            ]) }})" title="{{ $postnl ? 'Label maken en verzenden' : 'Markeer als verzonden' }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="1" y="3" width="15" height="13"/>
                                                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                                                    <circle cx="5.5" cy="18.5" r="2.5"/>
                                                    <circle cx="18.5" cy="18.5" r="2.5"/>
                                                </svg>
                                                Verzenden
                                            </button>
                                        @endif
                                    @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>

                <form id="singleDeleteForm" method="POST" style="display:none;">
                    @csrf
                    @method('DELETE')
                </form>

                <form id="createInvoiceForm" method="POST" style="display:none;">
                    @csrf
                </form>

                <form id="resendInvoiceForm" method="POST" style="display:none;">
                    @csrf
                </form>

                @if($orders->hasPages())
                    <div class="pagination">
                        @if($orders->onFirstPage())
                            <span>← Vorige</span>
                        @else
                            <a href="{{ $orders->previousPageUrl() }}">← Vorige</a>
                        @endif
                        
                        @foreach($orders->getUrlRange(1, $orders->lastPage()) as $page => $url)
                            @if($page == $orders->currentPage())
                                <span class="current">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}">{{ $page }}</a>
                            @endif
                        @endforeach
                        
                        @if($orders->hasMorePages())
                            <a href="{{ $orders->nextPageUrl() }}">Volgende →</a>
                        @else
                            <span>Volgende →</span>
                        @endif
                    </div>
                @endif
            @else
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    @if($archive)
                        <h3>Het archief is leeg</h3>
                        <p>Gearchiveerde bestellingen verschijnen hier, zonder PDF.</p>
                    @else
                        <h3>Geen actieve bestellingen</h3>
                        <p>Zodra er bestellingen binnenkomen, verschijnen ze hier.</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Verzend Modal -->
    <div class="modal-overlay" id="shipModal">
        <div class="modal" id="shipModalBox">
            <h3>📦 Bestelling verzenden <span id="shipOrderNumber" style="color:#6c757d;font-weight:400;font-size:0.9rem;"></span></h3>

            {{-- Label via PostNL; buitenland of een PostNL-storing: code hieronder handmatig invullen --}}
            <div class="postnl-section" id="postnlSection" hidden>
                <div class="form-group">
                    <label>Verzendvorm
                        @if(config('postnl.sandbox'))<span class="sandbox-badge" title="Testlabels: niet gebruiken voor echte zendingen">SANDBOX</span>@endif
                    </label>
                    <div class="choice">
                        @foreach(config('postnl.products') as $key => $product)
                            <label><input type="radio" name="postnl_product" value="{{ $key }}"> {{ $product['label'] }}</label>
                        @endforeach
                    </div>
                </div>
                <button type="button" class="btn btn-primary" id="labelButton" onclick="createLabel()">Label maken via PostNL</button>
                <div class="alert alert-error" id="labelError" style="margin:0.75rem 0 0;" hidden></div>

                <div id="labelPreview" hidden>
                    <iframe class="label-frame" id="labelFrame" title="PostNL-label"></iframe>
                    <div class="label-tools">
                        <button type="button" class="btn btn-primary" onclick="printLabel()">🖨️ Printen</button>
                        <a id="labelOpen" href="#" target="_blank">Openen in nieuw tabblad</a>
                        <button type="button" class="btn btn-secondary" style="margin-left:auto;" onclick="createLabel(true)">Nieuw label maken</button>
                    </div>
                </div>
            </div>

            <form id="shipForm" method="POST">
                @csrf
                <input type="hidden" name="status" value="shipped">

                <div class="form-group">
                    <label for="track_trace" id="trackTraceLabel">Track & Trace code (optioneel)</label>
                    <input type="text" id="track_trace" name="track_trace" placeholder="3S...">
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeShipModal()">Annuleren</button>
                    <button type="submit" class="btn btn-success" id="shipSubmit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <span>Verzenden</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let shipOrder = null;

        function openShipModal(order) {
            shipOrder = order;
            document.getElementById('shipForm').action = '/admin/orders/' + order.orderNumber + '/ship';
            document.getElementById('shipOrderNumber').textContent = order.orderNumber;
            document.getElementById('postnlSection').hidden = !order.postnl;
            document.getElementById('labelError').hidden = true;
            document.querySelectorAll('input[name="postnl_product"]').forEach(r => r.checked = r.value === order.product);

            if (order.postnl && order.labelUrl) {
                showLabel(order.labelUrl, order.barcode);
            } else {
                document.getElementById('labelPreview').hidden = true;
                document.getElementById('labelButton').hidden = false;
                document.getElementById('shipModalBox').classList.remove('wide');
                setShipSubmit(order.postnl ? 'Zonder label verzenden' : 'Verzenden');
            }
            document.getElementById('shipModal').classList.add('active');
        }

        function showLabel(url, barcode) {
            const src = url + '?t=' + Date.now();
            document.getElementById('labelFrame').src = src;
            document.getElementById('labelOpen').href = src;
            document.getElementById('labelPreview').hidden = false;
            document.getElementById('labelButton').hidden = true;
            document.getElementById('shipModalBox').classList.add('wide');
            document.getElementById('track_trace').value = barcode || '';
            setShipSubmit('Bevestig verzending & mail klant');
        }

        function setShipSubmit(text) {
            document.querySelector('#shipSubmit span').textContent = text;
        }

        async function createLabel(replace = false) {
            if (replace && !confirm('Een nieuw label maken? Gebruik daarna alleen het nieuwe label; het oude vervalt.')) return;

            const button = document.getElementById('labelButton');
            const error = document.getElementById('labelError');
            const product = document.querySelector('input[name="postnl_product"]:checked')?.value;
            button.disabled = true;
            button.textContent = 'Label wordt gemaakt…';
            error.hidden = true;

            try {
                const response = await fetch('/admin/orders/' + shipOrder.orderNumber + '/label', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ product: product, replace: replace }),
                });
                const data = await response.json().catch(() => ({}));

                if (response.status === 409 && data.has_label) {
                    return createLabel(true);
                }
                if (!response.ok) {
                    throw new Error(data.error || data.message || 'Label maken mislukt (HTTP ' + response.status + ').');
                }
                shipOrder.labelUrl = data.label_url;
                shipOrder.barcode = data.barcode;
                shipOrder.product = product;
                showLabel(data.label_url, data.barcode);
            } catch (e) {
                error.textContent = e.message + ' Je kunt de Track & Trace-code ook handmatig invullen.';
                error.hidden = false;
            } finally {
                button.disabled = false;
                button.textContent = 'Label maken via PostNL';
            }
        }

        function printLabel() {
            const frame = document.getElementById('labelFrame');
            try {
                frame.contentWindow.focus();
                frame.contentWindow.print();
            } catch (e) {
                window.open(frame.src, '_blank');
            }
        }

        function closeShipModal() {
            document.getElementById('shipModal').classList.remove('active');
            document.getElementById('track_trace').value = '';
            document.getElementById('labelFrame').removeAttribute('src');
            // Een nieuw gemaakt label is pas na herladen in de knop van de rij bekend
            if (shipOrder && shipOrder.labelUrl) {
                const order = Object.assign({}, shipOrder);
                const button = document.querySelector('button[data-ship="' + CSS.escape(order.orderNumber) + '"]');
                if (button) button.onclick = () => openShipModal(Object.assign({}, order));
            }
        }
        
        document.getElementById('shipModal').addEventListener('click', function(e) {
            if (e.target === this) closeShipModal();
        });

        // Invoice creation
        function createInvoice(orderNumber) {
            const form = document.getElementById('createInvoiceForm');
            form.action = '/admin/orders/' + orderNumber + '/invoice';
            form.submit();
        }

        // Resend invoice email
        function resendInvoice(orderNumber, invoiceNumber, email) {
            if (confirm('Factuur ' + invoiceNumber + ' nogmaals versturen naar ' + email + '?')) {
                const form = document.getElementById('resendInvoiceForm');
                form.action = '/admin/orders/' + orderNumber + '/invoice/resend';
                form.submit();
            }
        }

        // Delete functionality
        function confirmDelete(orderNumber) {
            if (confirm('Weet je zeker dat je bestelling ' + orderNumber + ' wilt verwijderen?')) {
                const form = document.getElementById('singleDeleteForm');
                form.action = '/admin/orders/' + orderNumber;
                form.submit();
            }
        }

        // Bulk selection
        function toggleAll(source) {
            document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = source.checked);
            updateBulkBar();
        }

        function updateBulkBar() {
            const checked = document.querySelectorAll('.order-checkbox:checked');
            const bar = document.getElementById('bulkBar');
            const count = document.getElementById('selectedCount');
            const selectAll = document.getElementById('selectAll');
            const allBoxes = document.querySelectorAll('.order-checkbox');

            count.textContent = checked.length;
            bar.classList.toggle('active', checked.length > 0);
            selectAll.checked = allBoxes.length > 0 && checked.length === allBoxes.length;
            selectAll.indeterminate = checked.length > 0 && checked.length < allBoxes.length;
        }

        function clearSelection() {
            document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAll').checked = false;
            updateBulkBar();
        }

        function submitBulk(action, question) {
            const checked = document.querySelectorAll('.order-checkbox:checked');
            if (checked.length === 0) return;
            if (confirm(question.replace('{n}', checked.length))) {
                const form = document.getElementById('bulkForm');
                form.action = action;
                form.submit();
            }
        }

        function confirmBulkDelete() {
            submitBulk(@json(route('admin.orders.bulk-delete')), 'Weet je zeker dat je {n} bestelling(en) wilt verwijderen? Dit kan niet ongedaan worden gemaakt.');
        }

        function confirmBulkArchive() {
            submitBulk(@json(route('admin.orders.bulk-archive')), '{n} bestelling(en) archiveren? De ordergegevens en facturen blijven bewaard, de PDF\'s worden verwijderd.');
        }

        function confirmBulkUnarchive() {
            submitBulk(@json(route('admin.orders.bulk-unarchive')), '{n} bestelling(en) terugzetten uit het archief? De PDF komt niet terug.');
        }
    </script>
</body>
</html>
