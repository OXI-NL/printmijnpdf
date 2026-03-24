<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bestelling bevestigd - PrintMijnPDF.nl</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            color: #1a1a2e;
            line-height: 1.6;
        }
        .container { max-width: 540px; margin: 0 auto; padding: 2.5rem 1.5rem; }
        .logo { display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 2rem; }
        .logo-icon {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, #e63946 0%, #d62839 100%);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(230, 57, 70, 0.3);
        }
        .logo-icon svg { width: 28px; height: 28px; color: white; }
        .logo-text { font-size: 26px; font-weight: 700; color: #1a1a2e; }
        .logo-text span { color: #e63946; }
        .card { background: white; border-radius: 20px; padding: 2rem; box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06); text-align: center; }
        .success-icon {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, #40c057 0%, #2f9e44 100%);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 8px 24px rgba(64, 192, 87, 0.3);
        }
        .success-icon svg { width: 40px; height: 40px; color: white; }
        .card h1 { font-size: 24px; font-weight: 600; margin-bottom: 0.5rem; }
        .card .subtitle { font-size: 16px; color: #6c757d; margin-bottom: 2rem; }
        .order-number { background: #f8f9fa; border-radius: 10px; padding: 1rem; margin-bottom: 1.5rem; }
        .order-number-label { font-size: 12px; color: #6c757d; text-transform: uppercase; letter-spacing: 1px; }
        .order-number-value { font-size: 20px; font-weight: 700; color: #e63946; font-family: monospace; }
        .details { text-align: left; background: #f8f9fa; border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem; }
        .details-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; }
        .details-row:not(:last-child) { border-bottom: 1px solid #e9ecef; }
        .details-label { color: #6c757d; }
        .details-value { font-weight: 500; }
        .next-steps { background: #fff5f5; border: 1px solid #fecaca; border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem; text-align: left; }
        .next-steps h3 { font-size: 14px; font-weight: 600; margin-bottom: 0.75rem; color: #e63946; }
        .next-steps ol { margin: 0; padding-left: 1.25rem; font-size: 14px; color: #6c757d; }
        .next-steps li { margin-bottom: 0.5rem; }
        .footer { text-align: center; margin-top: 1.5rem; font-size: 13px; color: #adb5bd; }
        .status-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: #d3f9d8; color: #2f9e44;
            padding: 6px 12px; border-radius: 20px;
            font-size: 13px; font-weight: 500;
        }
        .status-badge.pending { background: #fff3cd; color: #856404; }
        .status-badge.processing { background: #e7e5ff; color: #6c63ff; }
        .status-badge.shipped { background: #d3f9d8; color: #2f9e44; }
        .status-badge.cancelled { background: #fecaca; color: #dc2626; }
        .error-icon {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 8px 24px rgba(239, 68, 68, 0.3);
        }
        .error-icon svg { width: 40px; height: 40px; color: white; }
        .warning-icon {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.3);
        }
        .warning-icon svg { width: 40px; height: 40px; color: white; }
        .retry-btn {
            display: inline-block;
            background: linear-gradient(135deg, #e63946 0%, #d62839 100%);
            color: white;
            padding: 14px 28px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            margin-top: 1rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .retry-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(230, 57, 70, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="12" y1="18" x2="12" y2="12"></line>
                    <line x1="9" y1="15" x2="15" y2="15"></line>
                </svg>
            </div>
            <div class="logo-text">Print<span>Mijn</span>PDF</div>
        </div>

        <div class="card">
            @if($order->isPaid())
            {{-- BETAALD --}}
            <div class="success-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <h1>Bedankt voor je bestelling!</h1>
            <p class="subtitle">We hebben je betaling ontvangen en<br>gaan direct aan de slag.</p>
            
            @elseif($order->status === 'cancelled')
            {{-- MISLUKT --}}
            <div class="error-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </div>
            <h1>Betaling niet gelukt</h1>
            <p class="subtitle">Je betaling is geannuleerd of mislukt. Je bestelling is niet verwerkt.</p>
            
            @else
            {{-- PENDING --}}
            <div class="warning-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <h1>Wachten op betaling</h1>
            <p class="subtitle">We wachten nog op de bevestiging van je betaling.</p>
            @endif

            <div class="order-number">
                <div class="order-number-label">Bestelnummer</div>
                <div class="order-number-value">{{ $order->order_number }}</div>
            </div>

            <div class="details">
                <div class="details-row">
                    <span class="details-label">Status</span>
                    <span class="status-badge {{ $order->status }}">{{ $order->status_label }}</span>
                </div>
                <div class="details-row">
                    <span class="details-label">Bestand</span>
                    <span class="details-value">{{ $order->pdf_original_name }}</span>
                </div>
                <div class="details-row">
                    <span class="details-label">Formaat</span>
                    <span class="details-value">{{ $order->format }} · {{ $order->page_count }} pagina's · @if($order->binding_type === 'booklet')Geniet boekje @else Losbladig ({{ $order->print_side === 'double' ? 'dubbelzijdig' : 'enkelzijdig' }})@endif · {{ $order->quantity ?? 1 }} {{ ($order->quantity ?? 1) === 1 ? 'exemplaar' : 'exemplaren' }}</span>
                </div>
                <div class="details-row">
                    <span class="details-label">Totaal</span>
                    <span class="details-value">{{ $order->formatted_total }}</span>
                </div>
            </div>

            @if($order->isPaid())
            <div class="details">
                <div class="details-row">
                    <span class="details-label">Verzendadres</span>
                    <span class="details-value" style="text-align: right;">
                        {{ $order->customer_name }}<br>
                        {{ $order->address_street }} {{ $order->address_number }}{{ $order->address_addition ? ' '.$order->address_addition : '' }}<br>
                        {{ $order->address_postcode }} {{ $order->address_city }}
                    </span>
                </div>
            </div>

            <div class="next-steps">
                <h3>Wat gebeurt er nu?</h3>
                <ol>
                    <li>Je ontvangt een bevestigingsmail op <strong>{{ $order->customer_email }}</strong></li>
                    <li>Wij printen en binden je document</li>
                    <li>Je ontvangt een Track & Trace code zodra het pakket onderweg is</li>
                    <li>Binnen 3 werkdagen wordt het bezorgd!</li>
                </ol>
            </div>
            @elseif($order->status === 'cancelled')
            <a href="/" class="retry-btn">Opnieuw bestellen</a>
            @endif
        </div>

        <div class="footer">
            Vragen? Neem contact op via info@printmijnpdf.nl
        </div>
    </div>
</body>
</html>
