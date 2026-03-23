<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PrintMijnPDF.nl - Print je PDF. Morgen in huis.</title>
    <meta name="description" content="Upload je PDF en ontvang een professioneel geprint document. Boekje of losse pagina's, full colour, binnen 3 werkdagen thuisbezorgd of gratis afhalen.">
    <meta name="keywords" content="PDF printen, boekje laten drukken, magazine printen, brochure afdrukken, PDF naar boek">
    <meta property="og:title" content="PrintMijnPDF.nl - Print je PDF. Morgen in huis.">
    <meta property="og:description" content="Upload je PDF en ontvang een professioneel geprint document. Binnen 3 werkdagen thuisbezorgd.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://printmijnpdf.nl">
    <link rel="canonical" href="https://printmijnpdf.nl">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary: #e63946;
            --primary-dark: #d62839;
            --success: #40c057;
            --text: #1a1a2e;
            --text-muted: #6c757d;
            --bg: #f8f9fa;
            --card: #ffffff;
            --border: #e9ecef;
        }
        
        body {
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(180deg, #fff 0%, var(--bg) 100%);
            min-height: 100vh;
            color: var(--text);
            line-height: 1.6;
        }
        
        /* ===== HEADER ===== */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .logo-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(230, 57, 70, 0.25);
        }
        
        .logo-icon svg { width: 22px; height: 22px; color: white; }
        .logo-text { font-size: 20px; font-weight: 700; color: var(--text); }
        .logo-text span { color: var(--primary); }
        
        .header-link {
            font-size: 14px;
            color: var(--text-muted);
            text-decoration: none;
        }
        .header-link:hover { color: var(--primary); }
        
        /* ===== HERO ===== */
        .hero {
            text-align: center;
            padding: 2rem 1.5rem 3rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .hero h1 {
            font-size: 32px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }
        
        .hero .subtext {
            font-size: 18px;
            color: var(--text-muted);
            margin-bottom: 2rem;
        }
        
        .hero .subtext span {
            color: var(--text);
            font-weight: 500;
        }
        
        /* ===== UPLOAD DROPZONE ===== */
        .dropzone {
            border: 2px dashed #cbd5e1;
            border-radius: 16px;
            padding: 2.5rem 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s ease;
            background: white;
            margin-bottom: 1.5rem;
        }
        
        .dropzone:hover { border-color: var(--primary); background: #fff5f6; }
        .dropzone.dragover { border-color: var(--primary); background: #fff5f6; transform: scale(1.01); }
        
        .dropzone-icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 8px 24px rgba(230, 57, 70, 0.3);
        }
        
        .dropzone-icon svg { width: 28px; height: 28px; color: white; }
        .dropzone-title { font-size: 18px; font-weight: 600; color: var(--text); margin-bottom: 4px; }
        .dropzone-subtitle { font-size: 15px; color: var(--text-muted); margin-bottom: 0.5rem; }
        .dropzone-hint { font-size: 13px; color: #adb5bd; }
        .file-input { display: none; }
        
        /* Upload states */
        .upload-progress,
        .upload-processing { display: none; }
        
        .dropzone.uploading .upload-idle { display: none; }
        .dropzone.uploading .upload-progress { display: block; }
        .dropzone.processing .upload-idle { display: none; }
        .dropzone.processing .upload-processing { display: block; }
        
        .progress-bar-container {
            width: 100%;
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            margin: 1rem 0;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            border-radius: 3px;
            transition: width 0.3s ease;
            width: 0%;
        }
        
        .upload-spinner {
            width: 48px; height: 48px;
            border: 3px solid #e9ecef;
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin { to { transform: rotate(360deg); } }
        
        /* Trust line */
        .trust-line {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 14px;
            color: var(--text-muted);
        }
        
        .trust-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .trust-item svg {
            width: 16px; height: 16px;
            color: var(--success);
        }
        
        /* ===== MAIN CONTENT ===== */
        .main-content {
            max-width: 540px;
            margin: 0 auto;
            padding: 0 1.5rem 3rem;
        }
        
        /* ===== PROGRESS INDICATOR ===== */
        .progress-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 0 0.5rem;
            position: relative;
        }
        
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 16px;
            left: 40px;
            right: 40px;
            height: 2px;
            background: var(--border);
            z-index: 0;
        }
        
        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            z-index: 1;
        }
        
        .step-number {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: #e9ecef;
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .step-item.active .step-number {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(230, 57, 70, 0.3);
        }
        
        .step-item.completed .step-number {
            background: var(--success);
            color: white;
        }
        
        .step-label {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-muted);
        }
        
        .step-item.active .step-label { color: var(--text); }
        
        /* ===== SECTIONS ===== */
        .section {
            background: var(--card);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }
        
        .section.hidden { display: none; }
        
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .section-title svg {
            width: 20px; height: 20px;
            color: var(--primary);
        }
        
        /* ===== PDF RESULT ===== */
        .pdf-result {
            display: none;
            background: #f0fff4;
            border: 2px solid var(--success);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .pdf-result.visible { display: block; }
        
        .pdf-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .pdf-icon {
            width: 44px; height: 44px;
            background: var(--success);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .pdf-icon svg { width: 22px; height: 22px; color: white; }
        
        .pdf-details { flex: 1; min-width: 0; }
        
        .pdf-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .pdf-meta { font-size: 13px; color: var(--text-muted); }
        
        .pdf-change {
            font-size: 13px;
            color: var(--primary);
            background: none;
            border: none;
            cursor: pointer;
            text-decoration: underline;
            padding: 4px 8px;
        }
        
        .pdf-change:hover { color: var(--primary-dark); }
        
        /* ===== BINDING OPTIONS ===== */
        .binding-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 1rem;
        }
        
        .binding-option {
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            position: relative;
        }
        
        .binding-option:hover { border-color: #cbd5e1; }
        
        .binding-option.selected {
            border-color: var(--primary);
            background: #fff5f6;
        }
        
        .binding-option.unavailable {
            opacity: 0.5;
            pointer-events: none;
        }
        
        .binding-option .icon {
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .binding-option .title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 4px;
        }
        
        .binding-option .note {
            font-size: 12px;
            color: var(--text-muted);
        }
        
        /* Sub options for loose pages */
        .sub-options {
            display: none;
            gap: 10px;
            margin-top: 12px;
        }
        
        .sub-options.visible {
            display: flex;
        }
        
        .sub-option {
            flex: 1;
            border: 2px solid var(--border);
            border-radius: 10px;
            padding: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
        }
        
        .sub-option:hover { border-color: #cbd5e1; }
        .sub-option.selected { border-color: var(--primary); background: #fff5f6; }
        
        .sub-option .title { font-size: 14px; font-weight: 500; color: var(--text); }
        
        /* ===== PRICE SUMMARY ===== */
        .price-summary {
            background: var(--bg);
            border-radius: 12px;
            padding: 1rem;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: var(--text-muted);
            padding: 6px 0;
        }
        
        .price-row.subtle {
            font-size: 13px;
            color: #adb5bd;
        }
        
        .price-row.total {
            border-top: 2px solid var(--border);
            margin-top: 8px;
            padding-top: 12px;
            font-size: 18px;
            font-weight: 600;
            color: var(--text);
        }
        
        .price-note {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        /* ===== FORM ===== */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .form-row.single { grid-template-columns: 1fr; }
        .form-row.triple { grid-template-columns: 2fr 1fr 1fr; }
        
        .form-group { position: relative; }
        
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #495057;
            margin-bottom: 6px;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            font-family: inherit;
            font-size: 15px;
            border: 2px solid var(--border);
            border-radius: 10px;
            transition: all 0.2s ease;
            background: var(--bg);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
        }
        
        .form-group input::placeholder { color: #adb5bd; }
        
        .form-group.error input {
            border-color: var(--primary);
            background: #fff5f5;
        }
        
        .form-group .error-msg {
            font-size: 12px;
            color: var(--primary);
            margin-top: 4px;
            display: none;
        }
        
        .form-group.error .error-msg { display: block; }
        
        .field-hint {
            font-size: 12px;
            color: #adb5bd;
            margin-top: 4px;
            display: block;
        }
        
        /* ===== DELIVERY OPTIONS ===== */
        .delivery-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 1.5rem;
        }
        
        .delivery-option {
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .delivery-option:hover { border-color: #cbd5e1; }
        
        .delivery-option.selected {
            border-color: var(--primary);
            background: #fff5f6;
        }
        
        .delivery-option input { display: none; }
        
        .delivery-radio {
            width: 20px; height: 20px;
            border: 2px solid #cbd5e1;
            border-radius: 50%;
            flex-shrink: 0;
            position: relative;
        }
        
        .delivery-option.selected .delivery-radio {
            border-color: var(--primary);
        }
        
        .delivery-option.selected .delivery-radio::after {
            content: '';
            position: absolute;
            top: 4px; left: 4px;
            width: 8px; height: 8px;
            background: var(--primary);
            border-radius: 50%;
        }
        
        .delivery-content { flex: 1; }
        .delivery-title { font-size: 15px; font-weight: 600; color: var(--text); }
        .delivery-subtitle { font-size: 13px; color: var(--text-muted); }
        .delivery-price { font-size: 15px; font-weight: 600; color: var(--text); }
        
        .pickup-info {
            display: none;
            background: #f0f9ff;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 12px;
            font-size: 14px;
            color: #0369a1;
        }
        
        .pickup-info.visible { display: block; }
        
        /* ===== BUTTONS ===== */
        .btn {
            width: 100%;
            padding: 1rem 1.5rem;
            border: none;
            font-family: inherit;
            font-size: 16px;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.25s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 14px rgba(230, 57, 70, 0.35);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(230, 57, 70, 0.4);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn svg { width: 20px; height: 20px; }
        
        .payment-trust {
            text-align: center;
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        
        /* ===== FAQ ===== */
        .faq {
            max-width: 540px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }
        
        .faq h2 {
            font-size: 20px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .faq details {
            background: white;
            border-radius: 12px;
            margin-bottom: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        
        .faq summary {
            padding: 1rem 1.25rem;
            font-size: 15px;
            font-weight: 500;
            color: var(--text);
            cursor: pointer;
            list-style: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .faq summary::-webkit-details-marker { display: none; }
        
        .faq summary::after {
            content: '+';
            font-size: 20px;
            color: var(--text-muted);
            transition: transform 0.2s;
        }
        
        .faq details[open] summary::after {
            transform: rotate(45deg);
        }
        
        .faq details p {
            padding: 0 1.25rem 1rem;
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.6;
        }
        
        /* ===== FOOTER ===== */
        .footer {
            background: white;
            border-top: 1px solid var(--border);
            padding: 2rem 1.5rem;
            margin-top: 2rem;
        }
        
        .footer-grid {
            max-width: 800px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
        }
        
        @media (max-width: 640px) {
            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        .footer-col h4 {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.75rem;
        }
        
        .footer-col p {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 4px;
        }
        
        .footer-bottom {
            max-width: 800px;
            margin: 2rem auto 0;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
            text-align: center;
            font-size: 13px;
            color: var(--text-muted);
        }
        
        /* ===== MOBILE STICKY BAR ===== */
        .mobile-sticky-bar { display: none; }

        @media (max-width: 640px) {
            .hero h1 { font-size: 26px; }
            .hero .subtext { font-size: 16px; }
            .binding-options { grid-template-columns: 1fr; }
            .form-row.triple { grid-template-columns: 1fr 1fr; }
            
            .mobile-sticky-bar {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: white;
                border-top: 1px solid var(--border);
                padding: 12px 16px;
                z-index: 100;
                display: none;
                justify-content: space-between;
                align-items: center;
                box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
            }
            
            .mobile-sticky-bar.visible { display: flex; }
            
            .mobile-sticky-bar .total {
                font-size: 20px;
                font-weight: 700;
                color: var(--text);
            }
            
            .mobile-sticky-bar .total-label {
                font-size: 12px;
                color: var(--text-muted);
            }
            
            .mobile-sticky-bar .btn {
                width: auto;
                padding: 12px 24px;
            }
            
            body.has-sticky { padding-bottom: 80px; }
        }
        
        /* ===== INLINE NOTICE ===== */
        .inline-notice {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 1rem;
            display: none;
        }
        
        .inline-notice.visible { display: flex; align-items: center; gap: 10px; }
        .inline-notice.info { background: #f0f9ff; color: #0369a1; }
        .inline-notice.error { background: #fef2f2; color: #b91c1c; }
        .inline-notice svg { width: 18px; height: 18px; flex-shrink: 0; }
        
        /* ===== ERROR STATE ===== */
        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: none;
        }
        
        .error-message.visible { display: flex; align-items: center; gap: 12px; }
        .error-message svg { width: 24px; height: 24px; color: #b91c1c; flex-shrink: 0; }
        .error-message p { font-size: 14px; color: #b91c1c; }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <a href="/" class="logo">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
            </div>
            <span class="logo-text">Print<span>Mijn</span>PDF</span>
        </a>
        <a href="mailto:info@printmijnpdf.nl" class="header-link">Hulp nodig?</a>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <h1>Print je PDF. Morgen in huis.</h1>
        <p class="subtext"><span>Upload</span> → <span>kies afwerking</span> → <span>betaal</span>. Klaar.</p>
        
        <!-- Upload Dropzone -->
        <div class="dropzone" id="dropzone">
            <input type="file" accept=".pdf,application/pdf" class="file-input" id="fileInput">
            
            <div class="upload-idle">
                <div class="dropzone-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                </div>
                <p class="dropzone-title">Sleep je PDF hier</p>
                <p class="dropzone-subtitle">of klik om te bladeren</p>
                <p class="dropzone-hint">Maximaal 100 MB · Alleen PDF</p>
            </div>
            
            <div class="upload-progress">
                <p class="dropzone-title">Bezig met uploaden...</p>
                <div class="progress-bar-container">
                    <div class="progress-bar" id="uploadProgressBar"></div>
                </div>
                <p class="dropzone-subtitle" id="uploadProgressText">0%</p>
            </div>
            
            <div class="upload-processing">
                <div class="upload-spinner"></div>
                <p class="dropzone-title">Even geduld</p>
                <p class="dropzone-subtitle">We analyseren je document...</p>
            </div>
        </div>
        
        <!-- Trust Line -->
        <div class="trust-line">
            <div class="trust-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Binnen 3 werkdagen
            </div>
            <div class="trust-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Veilig betalen
            </div>
            <div class="trust-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Gratis afhalen
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Progress Steps -->
        <div class="progress-steps" id="progressSteps">
            <div class="step-item active" data-step="1">
                <div class="step-number">1</div>
                <span class="step-label">Uploaden</span>
            </div>
            <div class="step-item" data-step="2">
                <div class="step-number">2</div>
                <span class="step-label">Afwerking</span>
            </div>
            <div class="step-item" data-step="3">
                <div class="step-number">3</div>
                <span class="step-label">Adres</span>
            </div>
            <div class="step-item" data-step="4">
                <div class="step-number">4</div>
                <span class="step-label">Betalen</span>
            </div>
        </div>

        <!-- Error Message -->
        <div class="error-message" id="errorMessage">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <p id="errorText"></p>
        </div>

        <!-- PDF Result -->
        <div class="pdf-result" id="pdfResult">
            <div class="pdf-header">
                <div class="pdf-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
                <div class="pdf-details">
                    <p class="pdf-name" id="pdfName"></p>
                    <p class="pdf-meta" id="pdfMeta"></p>
                </div>
                <button class="pdf-change" id="pdfChange">Wijzigen</button>
            </div>
        </div>

        <!-- Inline Notice (for >64 pages etc) -->
        <div class="inline-notice" id="inlineNotice">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="16" x2="12" y2="12"/>
                <line x1="12" y1="8" x2="12.01" y2="8"/>
            </svg>
            <span id="inlineNoticeText"></span>
        </div>

        <!-- Section: Afwerking -->
        <section class="section hidden" id="sectionBinding">
            <h3 class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                </svg>
                Kies je afwerking
            </h3>
            
            <div class="binding-options">
                <div class="binding-option selected" id="optBooklet" data-binding="booklet">
                    <div class="icon">📖</div>
                    <div class="title">Geniet boekje</div>
                    <div class="note">Dubbelzijdig · Max 64 pag.</div>
                </div>
                <div class="binding-option" id="optLoose" data-binding="loose">
                    <div class="icon">📄</div>
                    <div class="title">Losse pagina's</div>
                    <div class="note">Enkel- of dubbelzijdig</div>
                </div>
            </div>
            
            <div class="sub-options" id="subOptions">
                <div class="sub-option selected" id="optDouble" data-side="double">
                    <div class="title">Dubbelzijdig</div>
                </div>
                <div class="sub-option" id="optSingle" data-side="single">
                    <div class="title">Enkelzijdig</div>
                </div>
            </div>
        </section>

        <!-- Section: Prijsoverzicht -->
        <section class="section hidden" id="sectionPrice">
            <h3 class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
                Prijsoverzicht
            </h3>
            
            <div class="price-summary">
                <div class="price-row">
                    <span id="pricePageLabel">0 pagina's × €0,10</span>
                    <span id="pricePagesValue">€0,00</span>
                </div>
                <div class="price-row" id="priceBindingRow" style="display: none;">
                    <span>Nieten (boekje)</span>
                    <span id="priceBindingValue">€5,00</span>
                </div>
                <div class="price-row" id="priceShippingRow">
                    <span id="priceShippingLabel">Verzending (PostNL)</span>
                    <span id="priceShippingValue">€5,00</span>
                </div>
                <div class="price-row subtle">
                    <span>Incl. voorbereiding & kwaliteitscheck</span>
                    <span>€10,00</span>
                </div>
                <div class="price-row total">
                    <span>Totaal</span>
                    <span id="priceTotalValue">€0,00</span>
                </div>
            </div>
            <p class="price-note">💡 Geen verborgen kosten. Prijs inclusief BTW.</p>
        </section>

        <!-- Section: Adresgegevens -->
        <section class="section hidden" id="sectionAddress">
            <h3 class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
                Je gegevens
            </h3>
            
            <div class="form-row single">
                <div class="form-group">
                    <label for="name">Naam</label>
                    <input type="text" id="name" name="name" placeholder="Je volledige naam" required>
                    <span class="error-msg">Vul je naam in</span>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email">E-mailadres</label>
                    <input type="email" id="email" name="email" placeholder="jouw@email.nl" required>
                    <span class="error-msg">Voer een geldig e-mailadres in</span>
                    <span class="field-hint">Hier sturen we je bevestiging en track & trace.</span>
                </div>
                <div class="form-group">
                    <label for="phone">Telefoon (optioneel)</label>
                    <input type="tel" id="phone" name="phone" placeholder="06-12345678">
                    <span class="field-hint">Alleen voor bezorgproblemen.</span>
                </div>
            </div>
            
            <div class="form-row triple">
                <div class="form-group">
                    <label for="street">Straat</label>
                    <input type="text" id="street" name="street" placeholder="Straatnaam" required>
                    <span class="error-msg">Vul je straatnaam in</span>
                </div>
                <div class="form-group">
                    <label for="number">Nr.</label>
                    <input type="text" id="number" name="number" placeholder="12" required>
                    <span class="error-msg">Vul in</span>
                </div>
                <div class="form-group">
                    <label for="addition">Toev.</label>
                    <input type="text" id="addition" name="addition" placeholder="A">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="postcode">Postcode</label>
                    <input type="text" id="postcode" name="postcode" placeholder="1234 AB" required>
                    <span class="error-msg">Voer een geldige postcode in</span>
                </div>
                <div class="form-group">
                    <label for="city">Plaats</label>
                    <input type="text" id="city" name="city" placeholder="Amsterdam" required>
                    <span class="error-msg">Vul je woonplaats in</span>
                </div>
            </div>
            
            <span class="field-hint" style="margin-bottom: 1rem; display: block;">🔒 Je gegevens worden niet gedeeld met derden.</span>
            
            <!-- Delivery Options -->
            <div class="delivery-options">
                <label class="delivery-option selected" id="deliveryShipping">
                    <input type="radio" name="delivery_type" value="shipping" checked>
                    <div class="delivery-radio"></div>
                    <div class="delivery-content">
                        <div class="delivery-title">Thuisbezorgd</div>
                        <div class="delivery-subtitle">Binnen 3 werkdagen in huis</div>
                    </div>
                    <div class="delivery-price">€5,00</div>
                </label>
                
                <label class="delivery-option" id="deliveryPickup">
                    <input type="radio" name="delivery_type" value="pickup">
                    <div class="delivery-radio"></div>
                    <div class="delivery-content">
                        <div class="delivery-title">Afhalen bij NIVO</div>
                        <div class="delivery-subtitle">Elke werkdag 17:00–17:30</div>
                    </div>
                    <div class="delivery-price">Gratis</div>
                </label>
            </div>
            
            <div class="pickup-info" id="pickupInfo">
                📍 <strong>NIVO</strong>, Exportweg 11, 2645ED Delfgauw<br>
                Je ontvangt een mail zodra je bestelling klaarligt.
            </div>
        </section>

        <!-- Section: Betalen -->
        <section class="section hidden" id="sectionPay">
            <button type="button" class="btn btn-primary" id="payButton" disabled>
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15l-4-4 1.41-1.41L11 14.17l5.59-5.59L18 10l-7 7z"/>
                </svg>
                <span id="payButtonText">Afrekenen met iDEAL – €0,00</span>
            </button>
            <p class="payment-trust">🔒 Veilig betalen via Mollie</p>
        </section>
    </main>

    <!-- FAQ -->
    <section class="faq">
        <h2>Veelgestelde vragen</h2>
        
        <details>
            <summary>Hoe snel wordt mijn bestelling geleverd?</summary>
            <p>Bestel je vóór 11:00 op een werkdag? Dan is je pakket binnen 3 werkdagen in huis. Je ontvangt een track & trace code zodra we verzenden.</p>
        </details>
        
        <details>
            <summary>Welke bestandsformaten accepteren jullie?</summary>
            <p>We accepteren alleen PDF-bestanden. Heb je een Word- of ander document? Sla het eerst op als PDF.</p>
        </details>
        
        <details>
            <summary>Kan ik mijn bestelling afhalen?</summary>
            <p>Ja! Afhalen is gratis. Elke werkdag tussen 17:00 en 17:30 bij NIVO, Exportweg 11, 2645ED Delfgauw.</p>
        </details>
        
        <details>
            <summary>Wat als mijn PDF meer dan 64 pagina's heeft?</summary>
            <p>Documenten boven 64 pagina's printen we als losse pagina's in plaats van een geniet boekje. De prijs per pagina blijft gelijk.</p>
        </details>
        
        <details>
            <summary>Op welk papier wordt er geprint?</summary>
            <p>We printen in full colour op 120 grams wit papier. Professionele kwaliteit, geschikt voor rapporten, scripties en presentaties.</p>
        </details>
        
        <details>
            <summary>Kan ik mijn bestelling nog annuleren?</summary>
            <p>Neem zo snel mogelijk contact met ons op via info@printmijnpdf.nl. Is je bestelling nog niet geprint? Dan annuleren we kosteloos.</p>
        </details>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-grid">
            <div class="footer-col">
                <h4>PrintMijnPDF</h4>
                <p>Snel, simpel en betaalbaar printen.</p>
            </div>
            <div class="footer-col">
                <h4>Contact</h4>
                <p>info@printmijnpdf.nl</p>
                <p>Reactie binnen 24 uur</p>
            </div>
            <div class="footer-col">
                <h4>Afhalen</h4>
                <p>NIVO</p>
                <p>Exportweg 11</p>
                <p>2645ED Delfgauw</p>
                <p>Ma–vr 17:00–17:30</p>
            </div>
            <div class="footer-col">
                <h4>Betalen</h4>
                <p>iDEAL</p>
                <p>Veilig via Mollie</p>
            </div>
        </div>
        <div class="footer-bottom">
            © 2026 PrintMijnPDF.nl · Onderdeel van NIVO Druk & Multimedia B.V.
        </div>
    </footer>

    <!-- Mobile Sticky Bar -->
    <div class="mobile-sticky-bar" id="mobileSticky">
        <div>
            <div class="total-label">Totaal</div>
            <div class="total" id="mobileTotalPrice">€0,00</div>
        </div>
        <button class="btn btn-primary" id="mobilePayButton">
            Afrekenen
        </button>
    </div>

    <script>
        // Config
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const MAX_BOOKLET_PAGES = 64;
        const PRICES = {
            startup: 1000,
            perPageA4: 10,
            perPageA5: 7,
            binding: 500,
            shipping: 500
        };

        // State
        let currentFile = null;
        let pageCount = 0;
        let detectedFormat = 'A4';
        let hasBleed = false;
        let bleedMM = 0;
        let bindingType = 'booklet';
        let printSide = 'double';
        let deliveryType = 'shipping';

        // DOM elements
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('fileInput');
        const pdfResult = document.getElementById('pdfResult');
        const progressSteps = document.getElementById('progressSteps');
        const errorMessage = document.getElementById('errorMessage');
        const inlineNotice = document.getElementById('inlineNotice');

        const sectionBinding = document.getElementById('sectionBinding');
        const sectionPrice = document.getElementById('sectionPrice');
        const sectionAddress = document.getElementById('sectionAddress');
        const sectionPay = document.getElementById('sectionPay');

        const optBooklet = document.getElementById('optBooklet');
        const optLoose = document.getElementById('optLoose');
        const subOptions = document.getElementById('subOptions');
        const optDouble = document.getElementById('optDouble');
        const optSingle = document.getElementById('optSingle');

        const deliveryShipping = document.getElementById('deliveryShipping');
        const deliveryPickup = document.getElementById('deliveryPickup');
        const pickupInfo = document.getElementById('pickupInfo');

        const payButton = document.getElementById('payButton');
        const mobileSticky = document.getElementById('mobileSticky');
        const mobilePayButton = document.getElementById('mobilePayButton');

        // Utility functions
        function formatPrice(cents) {
            return '€' + (cents / 100).toFixed(2).replace('.', ',');
        }

        function showError(message) {
            document.getElementById('errorText').textContent = message;
            errorMessage.classList.add('visible');
            setTimeout(() => errorMessage.classList.remove('visible'), 5000);
        }

        function showInlineNotice(message, type = 'info') {
            document.getElementById('inlineNoticeText').textContent = message;
            inlineNotice.className = 'inline-notice visible ' + type;
        }

        function hideInlineNotice() {
            inlineNotice.classList.remove('visible');
        }

        function updateProgressStep(step) {
            document.querySelectorAll('.step-item').forEach((item, index) => {
                const stepNum = index + 1;
                item.classList.remove('active', 'completed');
                if (stepNum < step) {
                    item.classList.add('completed');
                    item.querySelector('.step-number').innerHTML = '✓';
                } else if (stepNum === step) {
                    item.classList.add('active');
                    item.querySelector('.step-number').textContent = stepNum;
                } else {
                    item.querySelector('.step-number').textContent = stepNum;
                }
            });
        }

        // File handling
        function handleFile(file) {
            if (!file) return;

            if (file.type !== 'application/pdf') {
                showError('Dit bestand is geen PDF. Sla je document op als PDF en probeer opnieuw.');
                return;
            }

            if (file.size > 100 * 1024 * 1024) {
                showError('Dit bestand is te groot (max 100 MB). Probeer je PDF te comprimeren.');
                return;
            }

            currentFile = file;
            uploadFile(file);
        }

        async function uploadFile(file) {
            dropzone.classList.add('uploading');
            const progressBar = document.getElementById('uploadProgressBar');
            const progressText = document.getElementById('uploadProgressText');

            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                progressBar.style.width = progress + '%';
                progressText.textContent = Math.round(progress) + '%';
            }, 200);

            try {
                const formData = new FormData();
                formData.append('pdf', file);

                dropzone.classList.remove('uploading');
                dropzone.classList.add('processing');

                const response = await fetch('/api/calculate-price', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    body: formData
                });

                clearInterval(progressInterval);
                progressBar.style.width = '100%';

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Er ging iets mis bij het analyseren');
                }

                pageCount = data.page_count;
                detectedFormat = data.format || 'A4';
                hasBleed = data.has_bleed || false;
                bleedMM = data.bleed_mm || 0;

                showUploadSuccess(file, data);

            } catch (error) {
                clearInterval(progressInterval);
                showError(error.message || 'Er ging iets mis. Probeer het opnieuw.');
                resetUpload();
            }
        }

        function showUploadSuccess(file, data) {
            dropzone.classList.remove('uploading', 'processing');
            dropzone.style.display = 'none';
            pdfResult.classList.add('visible');

            document.getElementById('pdfName').textContent = file.name;
            document.getElementById('pdfMeta').textContent = 
                `${pageCount} pagina's · ${detectedFormat} · ${(file.size / 1024 / 1024).toFixed(1)} MB`;

            if (pageCount > MAX_BOOKLET_PAGES) {
                bindingType = 'loose';
                printSide = 'double';
                optBooklet.classList.add('unavailable');
                optBooklet.classList.remove('selected');
                optLoose.classList.add('selected');
                subOptions.classList.add('visible');
                showInlineNotice(
                    `Je document heeft ${pageCount} pagina's. We printen dit als losse pagina's.`,
                    'info'
                );
            }

            sectionBinding.classList.remove('hidden');
            sectionPrice.classList.remove('hidden');
            sectionAddress.classList.remove('hidden');
            sectionPay.classList.remove('hidden');

            updateProgressStep(2);
            updatePriceDisplay();
            validateForm();

            setTimeout(() => {
                sectionBinding.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 300);

            if (window.innerWidth <= 640) {
                mobileSticky.classList.add('visible');
                document.body.classList.add('has-sticky');
            }
        }

        function resetUpload() {
            dropzone.classList.remove('uploading', 'processing');
            dropzone.style.display = 'block';
            pdfResult.classList.remove('visible');
            fileInput.value = '';
            currentFile = null;
            pageCount = 0;

            sectionBinding.classList.add('hidden');
            sectionPrice.classList.add('hidden');
            sectionAddress.classList.add('hidden');
            sectionPay.classList.add('hidden');

            updateProgressStep(1);
            hideInlineNotice();

            if (optBooklet.classList.contains('unavailable')) {
                optBooklet.classList.remove('unavailable');
            }
            optBooklet.classList.add('selected');
            optLoose.classList.remove('selected');
            subOptions.classList.remove('visible');
            bindingType = 'booklet';
            printSide = 'double';

            mobileSticky.classList.remove('visible');
            document.body.classList.remove('has-sticky');
        }

        function calculatePrices() {
            const pricePerPage = detectedFormat === 'A4' ? PRICES.perPageA4 : PRICES.perPageA5;
            const pagesCost = pageCount * pricePerPage;
            const bindingCost = bindingType === 'booklet' ? PRICES.binding : 0;
            const shippingCost = deliveryType === 'shipping' ? PRICES.shipping : 0;
            const total = PRICES.startup + pagesCost + bindingCost + shippingCost;

            return { pagesCost, bindingCost, shippingCost, total };
        }

        function updatePriceDisplay() {
            const prices = calculatePrices();
            const pricePerPage = detectedFormat === 'A4' ? PRICES.perPageA4 : PRICES.perPageA5;

            document.getElementById('pricePageLabel').textContent = 
                `${pageCount} pagina's × ${formatPrice(pricePerPage)}`;
            document.getElementById('pricePagesValue').textContent = formatPrice(prices.pagesCost);

            const bindingRow = document.getElementById('priceBindingRow');
            if (bindingType === 'booklet') {
                bindingRow.style.display = 'flex';
                document.getElementById('priceBindingValue').textContent = formatPrice(PRICES.binding);
            } else {
                bindingRow.style.display = 'none';
            }

            if (deliveryType === 'pickup') {
                document.getElementById('priceShippingLabel').textContent = 'Afhalen';
                document.getElementById('priceShippingValue').textContent = 'Gratis';
            } else {
                document.getElementById('priceShippingLabel').textContent = 'Verzending (PostNL)';
                document.getElementById('priceShippingValue').textContent = formatPrice(PRICES.shipping);
            }

            document.getElementById('priceTotalValue').textContent = formatPrice(prices.total);
            document.getElementById('payButtonText').textContent = 
                `Afrekenen met iDEAL – ${formatPrice(prices.total)}`;
            document.getElementById('mobileTotalPrice').textContent = formatPrice(prices.total);

            const totalEl = document.getElementById('priceTotalValue');
            totalEl.style.color = 'var(--primary)';
            setTimeout(() => totalEl.style.color = '', 300);
        }

        function validateForm() {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const street = document.getElementById('street').value.trim();
            const number = document.getElementById('number').value.trim();
            const postcode = document.getElementById('postcode').value.trim();
            const city = document.getElementById('city').value.trim();

            const isValid = currentFile && 
                name.length >= 2 && 
                /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) &&
                street.length >= 2 &&
                number.length >= 1 &&
                /^\d{4}\s?[A-Za-z]{2}$/.test(postcode) &&
                city.length >= 2;

            payButton.disabled = !isValid;
            mobilePayButton.disabled = !isValid;

            return isValid;
        }

        function validateField(input) {
            const group = input.closest('.form-group');
            if (!group) return;

            let isValid = true;
            const value = input.value.trim();

            switch (input.id) {
                case 'name':
                case 'street':
                case 'city':
                    isValid = value.length >= 2;
                    break;
                case 'email':
                    isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                    break;
                case 'number':
                    isValid = value.length >= 1;
                    break;
                case 'postcode':
                    isValid = /^\d{4}\s?[A-Za-z]{2}$/.test(value);
                    break;
            }

            if (value && !isValid) {
                group.classList.add('error');
            } else {
                group.classList.remove('error');
            }

            validateForm();
        }

        async function submitOrder() {
            if (!validateForm()) return;

            updateProgressStep(4);

            const btn = payButton;
            btn.disabled = true;
            btn.innerHTML = '<span class="upload-spinner" style="width:20px;height:20px;border-width:2px;margin:0;"></span> Laden...';

            const formData = new FormData();
            formData.append('pdf', currentFile);
            formData.append('page_count', pageCount);
            formData.append('format', detectedFormat);
            formData.append('has_bleed', hasBleed ? '1' : '0');
            formData.append('bleed_mm', bleedMM);
            formData.append('binding_type', bindingType);
            formData.append('print_side', printSide);
            formData.append('delivery_type', deliveryType);
            formData.append('name', document.getElementById('name').value);
            formData.append('email', document.getElementById('email').value);
            formData.append('phone', document.getElementById('phone').value);
            formData.append('street', document.getElementById('street').value);
            formData.append('number', document.getElementById('number').value);
            formData.append('addition', document.getElementById('addition').value);
            formData.append('postcode', document.getElementById('postcode').value);
            formData.append('city', document.getElementById('city').value);

            try {
                const response = await fetch('/api/order', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    body: formData
                });

                const data = await response.json();

                if (data.success && data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    throw new Error(data.message || 'Er ging iets mis');
                }
            } catch (error) {
                showError(error.message);
                btn.disabled = false;
                btn.innerHTML = `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15l-4-4 1.41-1.41L11 14.17l5.59-5.59L18 10l-7 7z"/></svg> <span id="payButtonText">Afrekenen met iDEAL – ${formatPrice(calculatePrices().total)}</span>`;
                updateProgressStep(3);
            }
        }

        // Event listeners
        dropzone.addEventListener('click', () => fileInput.click());
        dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('dragover'); });
        dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
        dropzone.addEventListener('drop', e => { 
            e.preventDefault(); 
            dropzone.classList.remove('dragover'); 
            handleFile(e.dataTransfer.files[0]); 
        });
        fileInput.addEventListener('change', e => handleFile(e.target.files[0]));
        document.getElementById('pdfChange').addEventListener('click', resetUpload);

        optBooklet.addEventListener('click', () => {
            if (pageCount > MAX_BOOKLET_PAGES) return;
            bindingType = 'booklet';
            printSide = 'double';
            optBooklet.classList.add('selected');
            optLoose.classList.remove('selected');
            subOptions.classList.remove('visible');
            hideInlineNotice();
            updatePriceDisplay();
        });

        optLoose.addEventListener('click', () => {
            bindingType = 'loose';
            optLoose.classList.add('selected');
            optBooklet.classList.remove('selected');
            subOptions.classList.add('visible');
            updatePriceDisplay();
        });

        optDouble.addEventListener('click', () => {
            printSide = 'double';
            optDouble.classList.add('selected');
            optSingle.classList.remove('selected');
        });

        optSingle.addEventListener('click', () => {
            printSide = 'single';
            optSingle.classList.add('selected');
            optDouble.classList.remove('selected');
        });

        deliveryShipping.addEventListener('click', () => {
            deliveryType = 'shipping';
            deliveryShipping.classList.add('selected');
            deliveryPickup.classList.remove('selected');
            pickupInfo.classList.remove('visible');
            updatePriceDisplay();
        });

        deliveryPickup.addEventListener('click', () => {
            deliveryType = 'pickup';
            deliveryPickup.classList.add('selected');
            deliveryShipping.classList.remove('selected');
            pickupInfo.classList.add('visible');
            updatePriceDisplay();
        });

        document.querySelectorAll('#sectionAddress input').forEach(input => {
            input.addEventListener('blur', () => validateField(input));
            input.addEventListener('input', () => validateForm());
        });

        payButton.addEventListener('click', submitOrder);
        mobilePayButton.addEventListener('click', submitOrder);

        const addressSection = document.getElementById('sectionAddress');
        const addressObserver = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting && currentFile) {
                updateProgressStep(3);
            }
        }, { threshold: 0.3 });
        addressObserver.observe(addressSection);
    </script>
</body>
</html>
