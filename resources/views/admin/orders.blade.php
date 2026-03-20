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
        th, td { padding: 1rem 1.25rem; text-align: left; }
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
        .file-info { font-size: 13px; }
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
        .actions { display: flex; gap: 8px; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border: none;
            border-radius: 8px;
            font-family: inherit;
            font-size: 13px;
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

        <div class="card">
            <div class="card-header">
                <h2>Alle bestellingen</h2>
            </div>

            @if($orders->count() > 0)
                <table>
                    <thead>
                        <tr>
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
                                <td>
                                    <span class="order-number">{{ $order->order_number }}</span>
                                </td>
                                <td>
                                    <div class="customer-name">{{ $order->customer_name }}</div>
                                    <div class="customer-email">{{ $order->customer_email }}</div>
                                </td>
                                <td class="file-info">
                                    <span class="file-name" title="{{ $order->pdf_original_name }}">{{ $order->pdf_original_name }}</span>
                                    <div class="file-meta">{{ $order->format }} • {{ $order->page_count }} pagina's</div>
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
                                        <a href="/admin/orders/{{ $order->order_number }}/pdf" class="btn btn-secondary" title="Download PDF">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                                <polyline points="7 10 12 15 17 10"/>
                                                <line x1="12" y1="15" x2="12" y2="3"/>
                                            </svg>
                                            PDF
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
                                    @if($order->status === 'paid')
                                        <button class="btn btn-success" onclick="openShipModal('{{ $order->order_number }}')" title="Markeer als verzonden">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="1" y="3" width="15" height="13"/>
                                                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                                                <circle cx="5.5" cy="18.5" r="2.5"/>
                                                <circle cx="18.5" cy="18.5" r="2.5"/>
                                            </svg>
                                            Verzenden
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

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
    </script>
</body>
</html>
