<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin - Bestellingen | PrintMijnPDF</title>
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
        .actions { display: flex; gap: 6px; white-space: nowrap; }
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
        .modal-actions { display: flex; gap: 10px; margin-top: 1.5rem; }
        .modal-actions .btn { flex: 1; justify-content: center; }
        @media (max-width: 1024px) {
            .container { padding: 1rem; }
            th, td { padding: 0.75rem; }
            .actions { flex-direction: column; }
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

        <div class="stats">
            <div class="stat-card highlight">
                <div class="label">Totaal bestellingen</div>
                <div class="value">{{ $orders->total() }}</div>
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
                <h2>Alle bestellingen</h2>
            </div>

            @if($orders->count() > 0)
                <div class="bulk-bar" id="bulkBar">
                    <span><span class="count" id="selectedCount">0</span> geselecteerd</span>
                    <button type="button" class="btn btn-danger" onclick="confirmBulkDelete()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                            <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                        Verwijderen
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="clearSelection()">Annuleren</button>
                </div>
                <form id="bulkDeleteForm" method="POST" action="{{ route('admin.orders.bulk-delete') }}">
                    @csrf
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
                                    <input type="checkbox" name="order_numbers[]" value="{{ $order->order_number }}" class="order-checkbox" onchange="updateBulkBar()">
                                </td>
                                <td>
                                    <span class="order-number">{{ $order->order_number }}</span>
                                </td>
                                <td>
                                    <div class="customer-name">{{ $order->customer_name }}</div>
                                    <div class="customer-email">{{ $order->customer_email }}</div>
                                </td>
                                <td class="file-info">
                                    <span class="file-name" title="{{ $order->pdf_original_name }}">{{ $order->pdf_original_name }}</span>
                                    <div class="file-meta">
                                        {{ $order->format }} • {{ $order->page_count }} pag. • {{ $order->quantity ?? 1 }}× •
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
                                <td class="actions">
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
                                    @if($order->binding_type === 'booklet' && $order->status !== 'pending' && $order->status !== 'cancelled')
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
                                    @if($order->status === 'paid')
                                        @if($order->delivery_type === 'pickup')
                                            <form method="POST" action="/admin/orders/{{ $order->order_number }}/ship" style="display:inline;">
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
                                            <button type="button" class="btn btn-success" onclick="openShipModal('{{ $order->order_number }}')" title="Markeer als verzonden">
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
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                </form>

                <form id="singleDeleteForm" method="POST" style="display:none;">
                    @csrf
                    @method('DELETE')
                </form>

                <form id="createInvoiceForm" method="POST" style="display:none;">
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
                    <h3>Nog geen bestellingen</h3>
                    <p>Zodra er bestellingen binnenkomen, verschijnen ze hier.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Verzend Modal -->
    <div class="modal-overlay" id="shipModal">
        <div class="modal">
            <h3>📦 Bestelling verzenden</h3>
            <form id="shipForm" method="POST">
                @csrf
                <input type="hidden" name="status" value="shipped">
                
                <div class="form-group">
                    <label for="track_trace">Track & Trace code (optioneel)</label>
                    <input type="text" id="track_trace" name="track_trace" placeholder="3S...">
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeShipModal()">Annuleren</button>
                    <button type="submit" class="btn btn-success">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Verzenden
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openShipModal(orderNumber) {
            document.getElementById('shipForm').action = '/admin/orders/' + orderNumber + '/ship';
            document.getElementById('shipModal').classList.add('active');
        }
        
        function closeShipModal() {
            document.getElementById('shipModal').classList.remove('active');
            document.getElementById('track_trace').value = '';
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

        function confirmBulkDelete() {
            const checked = document.querySelectorAll('.order-checkbox:checked');
            if (checked.length === 0) return;
            if (confirm('Weet je zeker dat je ' + checked.length + ' bestelling(en) wilt verwijderen?')) {
                document.getElementById('bulkDeleteForm').submit();
            }
        }
    </script>
</body>
</html>
