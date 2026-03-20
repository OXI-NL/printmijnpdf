<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PrintMijnPDF.nl - Jouw PDF als ingebonden boekje thuisbezorgd</title>
    <meta name="description" content="Ontvang je digitale magazines, brochures of documenten liever op papier? Upload je PDF en wij maken er een professioneel ingebonden boekje van. Full colour, tot 64 pagina's, binnen 3 werkdagen bezorgd.">
    <meta name="keywords" content="PDF printen, boekje laten drukken, magazine printen, brochure afdrukken, PDF naar boek, digitaal naar papier">
    <meta property="og:title" content="PrintMijnPDF.nl - Van digitaal naar écht papier">
    <meta property="og:description" content="Upload je PDF en ontvang een professioneel ingebonden boekje. Full colour print, binnen 3 werkdagen thuisbezorgd.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://printmijnpdf.nl">
    <link rel="canonical" href="https://printmijnpdf.nl">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            color: #1a1a2e;
            line-height: 1.6;
        }
        .container { max-width: 540px; margin: 0 auto; padding: 2.5rem 1.5rem 3rem; }
        .logo { display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 0.5rem; }
        .logo-icon {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, #e63946 0%, #d62839 100%);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(230, 57, 70, 0.3);
        }
        .logo-icon svg { width: 28px; height: 28px; color: white; }
        .logo-text { font-size: 26px; font-weight: 700; color: #1a1a2e; letter-spacing: -0.5px; }
        .logo-text span { color: #e63946; }
        header { text-align: center; margin-bottom: 2rem; }
        .tagline { font-size: 16px; color: #6c757d; margin-top: 0.5rem; }
        .progress-steps { display: flex; justify-content: center; gap: 8px; margin-bottom: 2rem; }
        .progress-step { width: 10px; height: 10px; border-radius: 50%; background: #dee2e6; transition: all 0.3s ease; }
        .progress-step.active { background: #e63946; width: 32px; border-radius: 5px; }
        .progress-step.completed { background: #40c057; }
        .card { background: white; border-radius: 20px; padding: 2rem; box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06); }
        .step { display: none; }
        .step.active { display: block; }
        .step-title { font-size: 18px; font-weight: 600; color: #1a1a2e; margin-bottom: 0.5rem; }
        .step-subtitle { font-size: 14px; color: #6c757d; margin-bottom: 1.5rem; }
        .pricing-label { font-size: 12px; font-weight: 600; color: #adb5bd; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1rem; }
        .pricing-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; }
        .price-item { background: #f8f9fa; border-radius: 12px; padding: 0.875rem; }
        .price-item.full-width { grid-column: 1 / -1; display: flex; justify-content: space-between; align-items: center; }
        .price-label { font-size: 12px; color: #6c757d; margin-bottom: 2px; }
        .price-value { font-size: 18px; font-weight: 600; color: #1a1a2e; }
        .price-item.full-width .price-label { margin-bottom: 0; }
        .colour-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #6c757d; margin-top: 12px; margin-bottom: 1.5rem; }
        .colour-dots { display: flex; gap: 3px; }
        .colour-dot { width: 8px; height: 8px; border-radius: 50%; }
        .colour-dot.cyan { background: #00b4d8; }
        .colour-dot.magenta { background: #e63946; }
        .colour-dot.yellow { background: #ffd60a; }
        .colour-dot.black { background: #1a1a2e; }
        .dropzone {
            border: 2px dashed #dee2e6; border-radius: 16px; padding: 2.5rem 1.5rem;
            text-align: center; cursor: pointer; transition: all 0.25s ease;
        }
        .dropzone:hover { border-color: #e63946; background: #fff5f5; }
        .dropzone.dragover { border-color: #e63946; background: #fff5f5; transform: scale(1.01); }
        .dropzone-icon {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, #e63946 0%, #d62839 100%);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem; box-shadow: 0 4px 12px rgba(230, 57, 70, 0.25);
        }
        .dropzone-icon svg { width: 24px; height: 24px; color: white; }
        .dropzone-title { font-size: 16px; font-weight: 600; color: #1a1a2e; margin-bottom: 4px; }
        .dropzone-subtitle { font-size: 14px; color: #6c757d; }
        .file-input { display: none; }
        .pdf-result { display: none; background: #f0fff4; border: 2px solid #40c057; border-radius: 16px; padding: 1.25rem; margin-bottom: 1.5rem; }
        .pdf-result.visible { display: block; }
        .pdf-header { display: flex; align-items: center; gap: 12px; margin-bottom: 1rem; }
        .pdf-icon { width: 44px; height: 44px; background: #40c057; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .pdf-icon svg { width: 22px; height: 22px; color: white; }
        .pdf-details { flex: 1; min-width: 0; }
        .pdf-name { font-size: 14px; font-weight: 600; color: #1a1a2e; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pdf-meta { font-size: 13px; color: #6c757d; }
        .pdf-remove { width: 32px; height: 32px; border: none; background: rgba(255,255,255,0.8); border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; flex-shrink: 0; }
        .pdf-remove:hover { background: #fee2e2; }
        .pdf-remove svg { width: 16px; height: 16px; color: #6c757d; }
        .pdf-remove:hover svg { color: #e63946; }
        .pdf-specs { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; }
        .pdf-spec { background: rgba(255,255,255,0.7); border-radius: 8px; padding: 0.625rem; text-align: center; }
        .pdf-spec-label { font-size: 11px; color: #6c757d; margin-bottom: 2px; }
        .pdf-spec-value { font-size: 15px; font-weight: 600; color: #1a1a2e; }
        .loading { display: none; text-align: center; padding: 2rem; }
        .loading.visible { display: block; }
        .spinner { width: 40px; height: 40px; border: 3px solid #f0f0f0; border-top-color: #e63946; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 1rem; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loading-text { font-size: 14px; color: #6c757d; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
        .form-row.single { grid-template-columns: 1fr; }
        .form-group { position: relative; }
        .form-group label { display: block; font-size: 13px; font-weight: 500; color: #495057; margin-bottom: 6px; }
        .form-group input { width: 100%; padding: 0.875rem 1rem; font-family: inherit; font-size: 15px; border: 2px solid #e9ecef; border-radius: 10px; transition: all 0.2s ease; background: #f8f9fa; }
        .form-group input:focus { outline: none; border-color: #e63946; background: white; }
        .form-group input::placeholder { color: #adb5bd; }
        .form-group.error input { border-color: #e63946; background: #fff5f5; }
        .form-group .error-msg { font-size: 12px; color: #e63946; margin-top: 4px; display: none; }
        .form-group.error .error-msg { display: block; }
        .summary-card { background: #f8f9fa; border-radius: 12px; padding: 1.25rem; margin-bottom: 1rem; }
        .summary-header { display: flex; align-items: center; gap: 12px; padding-bottom: 1rem; border-bottom: 1px solid #e9ecef; margin-bottom: 1rem; }
        .summary-icon { width: 40px; height: 40px; background: #e63946; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .summary-icon svg { width: 20px; height: 20px; color: white; }
        .summary-file-name { font-size: 14px; font-weight: 600; color: #1a1a2e; }
        .summary-file-meta { font-size: 13px; color: #6c757d; }
        .summary-row { display: flex; justify-content: space-between; font-size: 14px; color: #6c757d; padding: 6px 0; }
        .summary-row.total { border-top: 2px solid #e9ecef; margin-top: 8px; padding-top: 12px; font-size: 18px; font-weight: 600; color: #1a1a2e; }
        .summary-address { background: #f8f9fa; border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; }
        .summary-address-label { font-size: 12px; font-weight: 600; color: #adb5bd; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .summary-address-text { font-size: 14px; color: #1a1a2e; line-height: 1.5; }
        .btn { width: 100%; padding: 1rem 1.5rem; border: none; font-family: inherit; font-size: 16px; font-weight: 600; border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.25s ease; }
        .btn-primary { background: linear-gradient(135deg, #e63946 0%, #d62839 100%); color: white; box-shadow: 0 4px 12px rgba(230, 57, 70, 0.3); }
        .btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(230, 57, 70, 0.4); }
        .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-secondary { background: #f8f9fa; color: #495057; margin-bottom: 10px; }
        .btn-secondary:hover { background: #e9ecef; }
        .btn svg { width: 20px; height: 20px; }
        .footer { text-align: center; margin-top: 1.5rem; font-size: 13px; color: #adb5bd; }
        .error-banner { background: #fee2e2; border: 1px solid #e63946; color: #991b1b; padding: 1rem; border-radius: 10px; margin-bottom: 1rem; font-size: 14px; display: none; }
        .error-banner.visible { display: block; }
        
        /* Hero Section */
        .hero-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
            text-align: center;
        }
        .hero-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 1rem;
            line-height: 1.3;
        }
        .hero-text {
            font-size: 15px;
            color: #495057;
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }
        .hero-text strong {
            color: #1a1a2e;
        }
        .hero-features {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-bottom: 1.25rem;
        }
        .hero-feature {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            font-weight: 500;
            color: #1a1a2e;
        }
        .hero-feature-icon {
            font-size: 18px;
        }
        .hero-note {
            font-size: 13px;
            color: #6c757d;
            background: #f8f9fa;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            margin: 0;
        }
        .hero-note a {
            color: #e63946;
            text-decoration: none;
            font-weight: 500;
        }
        .hero-note a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .container { padding: 1.5rem 1rem 2rem; }
            .card { padding: 1.5rem; border-radius: 16px; }
            .logo-text { font-size: 22px; }
            .logo-icon { width: 42px; height: 42px; }
            .price-value { font-size: 16px; }
            .pdf-specs { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
            .form-row.postcode-city { grid-template-columns: 120px 1fr; }
            .hero-section { padding: 1.5rem; }
            .hero-title { font-size: 20px; }
            .hero-features { gap: 1rem; }
            .hero-feature { font-size: 13px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <div class="logo-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="12" y1="18" x2="12" y2="12"></line>
                        <line x1="9" y1="15" x2="15" y2="15"></line>
                    </svg>
                </div>
                <div class="logo-text">Print<span>Mijn</span>PDF</div>
            </div>
            <p class="tagline">Upload je PDF. Wij printen en versturen.</p>
        </header>

        <!-- SEO Hero Section -->
        <section class="hero-section">
            <h1 class="hero-title">Van digitaal naar écht papier</h1>
            <p class="hero-text">
                Ontvang je wel eens een <strong>magazine, boekje of brochure</strong> per e-mail die je liever in je handen wilt houden? 
                Wij maken er een professioneel <strong>ingebonden boekje</strong> van — gedrukt op hoogwaardig papier, zodat je het lekker kunt doorbladeren.
            </p>
            <div class="hero-features">
                <div class="hero-feature">
                    <span class="hero-feature-icon">📄</span>
                    <span>Tot 64 pagina's</span>
                </div>
                <div class="hero-feature">
                    <span class="hero-feature-icon">🎨</span>
                    <span>Full colour print</span>
                </div>
                <div class="hero-feature">
                    <span class="hero-feature-icon">📦</span>
                    <span>Binnen 3 werkdagen thuis</span>
                </div>
            </div>
            <p class="hero-note">
                <strong>Meer dan 64 pagina's?</strong> Geen probleem! Neem contact op via <a href="mailto:info@printmijnpdf.nl">info@printmijnpdf.nl</a>
            </p>
        </section>

        <div class="progress-steps">
            <div class="progress-step active" data-step="1"></div>
            <div class="progress-step" data-step="2"></div>
            <div class="progress-step" data-step="3"></div>
        </div>

        <div class="card">
            <div class="error-banner" id="errorBanner"></div>

            <!-- Step 1: Upload PDF -->
            <div class="step active" id="step1">
                <div class="pricing-label">Prijzen</div>
                <div class="pricing-grid">
                    <div class="price-item">
                        <div class="price-label">Per pagina A4</div>
                        <div class="price-value">€ {{ number_format($prices['a4'], 2, ',', '.') }}</div>
                    </div>
                    <div class="price-item">
                        <div class="price-label">Per pagina A5</div>
                        <div class="price-value">€ {{ number_format($prices['a5'], 2, ',', '.') }}</div>
                    </div>
                    <div class="price-item">
                        <div class="price-label">Opstartkosten</div>
                        <div class="price-value">€ {{ number_format($prices['startup'], 2, ',', '.') }}</div>
                    </div>
                    <div class="price-item">
                        <div class="price-label">Inbinden</div>
                        <div class="price-value">€ {{ number_format($prices['binding'], 2, ',', '.') }}</div>
                    </div>
                </div>
                <div class="price-item full-width">
                    <div class="price-label">Verzendkosten</div>
                    <div class="price-value">€ {{ number_format($prices['shipping'], 2, ',', '.') }}</div>
                </div>

                <div class="colour-badge">
                    <div class="colour-dots">
                        <div class="colour-dot cyan"></div>
                        <div class="colour-dot magenta"></div>
                        <div class="colour-dot yellow"></div>
                        <div class="colour-dot black"></div>
                    </div>
                    Alles in full colour
                </div>

                <div class="dropzone" id="dropzone">
                    <div class="dropzone-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                    </div>
                    <div class="dropzone-title">Sleep je PDF hierheen</div>
                    <div class="dropzone-subtitle">of klik om te selecteren</div>
                </div>
                <input type="file" id="fileInput" class="file-input" accept=".pdf">

                <div class="loading" id="loading">
                    <div class="spinner"></div>
                    <div class="loading-text">PDF analyseren...</div>
                </div>

                <div class="pdf-result" id="pdfResult">
                    <div class="pdf-header">
                        <div class="pdf-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </div>
                        <div class="pdf-details">
                            <div class="pdf-name" id="pdfName">document.pdf</div>
                            <div class="pdf-meta" id="pdfMeta">Klaar voor print</div>
                        </div>
                        <button class="pdf-remove" id="pdfRemove" type="button">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="pdf-specs">
                        <div class="pdf-spec">
                            <div class="pdf-spec-label">Formaat</div>
                            <div class="pdf-spec-value" id="pdfFormat">A4</div>
                        </div>
                        <div class="pdf-spec">
                            <div class="pdf-spec-label">Pagina's</div>
                            <div class="pdf-spec-value" id="pdfPages">0</div>
                        </div>
                        <div class="pdf-spec">
                            <div class="pdf-spec-label">Prijs</div>
                            <div class="pdf-spec-value" id="pdfPrice">€ 0,00</div>
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary" id="toStep2" disabled type="button">
                    Volgende: Verzendadres
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </button>
            </div>

            <!-- Step 2: Address -->
            <div class="step" id="step2">
                <div class="step-title">Verzendadres</div>
                <div class="step-subtitle">Waar mogen we je bestelling naartoe sturen?</div>

                <div class="form-row single">
                    <div class="form-group">
                        <label for="name">Naam</label>
                        <input type="text" id="name" placeholder="Volledige naam" autocomplete="name">
                        <div class="error-msg">Vul je naam in</div>
                    </div>
                </div>

                <div class="form-row single">
                    <div class="form-group">
                        <label for="email">E-mailadres</label>
                        <input type="email" id="email" placeholder="voor bevestiging" autocomplete="email">
                        <div class="error-msg">Vul een geldig e-mailadres in</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label for="street">Straatnaam</label>
                        <input type="text" id="street" placeholder="Straatnaam" autocomplete="address-line1">
                        <div class="error-msg">Vul je straatnaam in</div>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="number">Huisnr.</label>
                        <input type="text" id="number" placeholder="Nr." autocomplete="address-line2">
                        <div class="error-msg">Verplicht</div>
                    </div>
                </div>

                <div class="form-row postcode-city">
                    <div class="form-group">
                        <label for="postcode">Postcode</label>
                        <input type="text" id="postcode" placeholder="1234 AB" autocomplete="postal-code" style="text-transform: uppercase;">
                        <div class="error-msg">Ongeldige postcode</div>
                    </div>
                    <div class="form-group">
                        <label for="city">Plaats</label>
                        <input type="text" id="city" placeholder="Plaatsnaam" autocomplete="address-level2">
                        <div class="error-msg">Vul je plaats in</div>
                    </div>
                </div>

                <div style="margin-top: 1.5rem;">
                    <button class="btn btn-secondary" id="backToStep1" type="button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="19" y1="12" x2="5" y2="12"></line>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                        Terug
                    </button>
                    <button class="btn btn-primary" id="toStep3" type="button">
                        Naar overzicht
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Step 3: Summary & Pay -->
            <div class="step" id="step3">
                <div class="step-title">Overzicht</div>
                <div class="step-subtitle">Controleer je bestelling en betaal</div>

                <div class="summary-card">
                    <div class="summary-header">
                        <div class="summary-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                        </div>
                        <div>
                            <div class="summary-file-name" id="summaryFileName">document.pdf</div>
                            <div class="summary-file-meta" id="summaryFileMeta">A4 · 24 pagina's</div>
                        </div>
                    </div>

                    <div class="summary-row">
                        <span>Opstartkosten</span>
                        <span id="summaryStartup">€ 10,00</span>
                    </div>
                    <div class="summary-row">
                        <span id="summaryPagesLabel">0 pagina's × € 0,10</span>
                        <span id="summaryPagesPrice">€ 0,00</span>
                    </div>
                    <div class="summary-row">
                        <span>Inbinden</span>
                        <span id="summaryBinding">€ 5,00</span>
                    </div>
                    <div class="summary-row">
                        <span>Verzending</span>
                        <span id="summaryShipping">€ 5,00</span>
                    </div>
                    <div class="summary-row total">
                        <span>Totaal</span>
                        <span id="summaryTotal">€ 20,00</span>
                    </div>
                </div>

                <div class="summary-address">
                    <div class="summary-address-label">Verzenden naar</div>
                    <div class="summary-address-text" id="summaryAddress">
                        Naam<br>
                        Straat 123<br>
                        1234 AB Plaats
                    </div>
                </div>

                <button class="btn btn-secondary" id="backToStep2" type="button">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Terug
                </button>
                <button class="btn btn-primary" id="payButton" type="button">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15l-4-4 1.41-1.41L11 14.17l5.59-5.59L18 10l-7 7z"/>
                    </svg>
                    Betalen met iDEAL
                </button>
            </div>
        </div>

        <div class="footer">
            Veilig betalen · Binnen 3 werkdagen bezorgd
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        // CSRF token voor AJAX requests
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        
        // Initialize PDF.js
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        // Prijzen van server
        const PRICES = {
            a4: {{ $prices['a4'] * 100 }},
            a5: {{ $prices['a5'] * 100 }},
            startup: {{ $prices['startup'] * 100 }},
            binding: {{ $prices['binding'] * 100 }},
            shipping: {{ $prices['shipping'] * 100 }}
        };

        // Standard dimensions
        const FORMATS = {
            'A4': { width: 595, height: 842, tolerance: 15 },
            'A5': { width: 420, height: 595, tolerance: 10 }
        };
        const BLEED_SIZES = [8.5, 14, 17];

        // State
        let currentFile = null;
        let pageCount = 0;
        let detectedFormat = 'A4';
        let hasBleed = false;
        let bleedMM = 0;
        let pricePerPage = PRICES.a4;
        let totalPrice = 0;
        let calculatedPrices = {};

        // DOM
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('fileInput');
        const loading = document.getElementById('loading');
        const pdfResult = document.getElementById('pdfResult');
        const errorBanner = document.getElementById('errorBanner');
        const steps = document.querySelectorAll('.step');
        const progressSteps = document.querySelectorAll('.progress-step');

        // Format price
        function formatPrice(cents) {
            return '€ ' + (cents / 100).toFixed(2).replace('.', ',');
        }

        // Show error
        function showError(msg) {
            errorBanner.textContent = msg;
            errorBanner.classList.add('visible');
            setTimeout(() => errorBanner.classList.remove('visible'), 5000);
        }

        // Detect format
        async function detectFormat(page) {
            const viewport = page.getViewport({ scale: 1 });
            let w = Math.min(viewport.width, viewport.height);
            let h = Math.max(viewport.width, viewport.height);

            for (const [formatName, format] of Object.entries(FORMATS)) {
                if (Math.abs(w - format.width) <= format.tolerance && 
                    Math.abs(h - format.height) <= format.tolerance) {
                    return { format: formatName, hasBleed: false, bleedMM: 0 };
                }
                for (const bleed of BLEED_SIZES) {
                    const bleedW = format.width + (bleed * 2);
                    const bleedH = format.height + (bleed * 2);
                    if (Math.abs(w - bleedW) <= format.tolerance && 
                        Math.abs(h - bleedH) <= format.tolerance) {
                        return { format: formatName, hasBleed: true, bleedMM: Math.round(bleed / 2.835) };
                    }
                }
            }

            const isLarge = w > 480;
            return { format: isLarge ? 'A4' : 'A5', hasBleed: false, bleedMM: 0 };
        }

        // Calculate price
        function calculatePrice() {
            pricePerPage = detectedFormat === 'A5' ? PRICES.a5 : PRICES.a4;
            const pages = pageCount * pricePerPage;
            totalPrice = PRICES.startup + pages + PRICES.binding + PRICES.shipping;
            calculatedPrices = {
                startup: PRICES.startup,
                pages: pages,
                binding: PRICES.binding,
                shipping: PRICES.shipping,
                total: totalPrice
            };
            return totalPrice;
        }

        // Handle file
        async function handleFile(file) {
            if (!file || file.type !== 'application/pdf') {
                showError('Upload alsjeblieft een PDF bestand.');
                return;
            }

            currentFile = file;
            dropzone.style.display = 'none';
            loading.classList.add('visible');
            pdfResult.classList.remove('visible');

            try {
                const arrayBuffer = await file.arrayBuffer();
                const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
                pageCount = pdf.numPages;

                const page = await pdf.getPage(1);
                const formatResult = await detectFormat(page);
                
                detectedFormat = formatResult.format;
                hasBleed = formatResult.hasBleed;
                bleedMM = formatResult.bleedMM;

                calculatePrice();

                document.getElementById('pdfName').textContent = file.name;
                document.getElementById('pdfFormat').textContent = detectedFormat;
                document.getElementById('pdfPages').textContent = pageCount;
                document.getElementById('pdfPrice').textContent = formatPrice(totalPrice);

                if (hasBleed) {
                    document.getElementById('pdfMeta').textContent = `${detectedFormat} +${bleedMM}mm afloop`;
                } else {
                    document.getElementById('pdfMeta').textContent = 'Klaar voor print';
                }

                loading.classList.remove('visible');
                pdfResult.classList.add('visible');
                document.getElementById('toStep2').disabled = false;

            } catch (error) {
                console.error('PDF error:', error);
                loading.classList.remove('visible');
                dropzone.style.display = 'block';
                showError('Kon de PDF niet lezen. Probeer een ander bestand.');
            }
        }

        // Remove file
        function removeFile() {
            currentFile = null;
            pageCount = 0;
            fileInput.value = '';
            dropzone.style.display = 'block';
            pdfResult.classList.remove('visible');
            document.getElementById('toStep2').disabled = true;
        }

        // Navigate steps
        function goToStep(stepNum) {
            steps.forEach(s => s.classList.remove('active'));
            document.getElementById('step' + stepNum).classList.add('active');
            progressSteps.forEach((p, i) => {
                p.classList.remove('active', 'completed');
                if (i + 1 < stepNum) p.classList.add('completed');
                else if (i + 1 === stepNum) p.classList.add('active');
            });
        }

        // Validate step 2
        function validateStep2() {
            let valid = true;
            ['name', 'email', 'street', 'number', 'postcode', 'city'].forEach(id => {
                const input = document.getElementById(id);
                const group = input.closest('.form-group');
                const value = input.value.trim();
                group.classList.remove('error');

                if (!value) { group.classList.add('error'); valid = false; }
                if (id === 'email' && value && !value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                    group.classList.add('error'); valid = false;
                }
                if (id === 'postcode' && value && !value.match(/^\d{4}\s?[A-Za-z]{2}$/)) {
                    group.classList.add('error'); valid = false;
                }
            });
            return valid;
        }

        // Update summary
        function updateSummary() {
            document.getElementById('summaryFileName').textContent = currentFile.name;
            document.getElementById('summaryFileMeta').textContent = `${detectedFormat} · ${pageCount} pagina's`;
            document.getElementById('summaryStartup').textContent = formatPrice(calculatedPrices.startup);
            document.getElementById('summaryPagesLabel').textContent = `${pageCount} pagina's × ${formatPrice(pricePerPage)}`;
            document.getElementById('summaryPagesPrice').textContent = formatPrice(calculatedPrices.pages);
            document.getElementById('summaryBinding').textContent = formatPrice(calculatedPrices.binding);
            document.getElementById('summaryShipping').textContent = formatPrice(calculatedPrices.shipping);
            document.getElementById('summaryTotal').textContent = formatPrice(calculatedPrices.total);

            const name = document.getElementById('name').value;
            const street = document.getElementById('street').value;
            const number = document.getElementById('number').value;
            const postcode = document.getElementById('postcode').value.toUpperCase();
            const city = document.getElementById('city').value;
            document.getElementById('summaryAddress').innerHTML = `${name}<br>${street} ${number}<br>${postcode} ${city}`;
        }

        // Submit order
        async function submitOrder() {
            const btn = document.getElementById('payButton');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner" style="width:20px;height:20px;border-width:2px;margin:0;"></span> Laden...';

            const formData = new FormData();
            formData.append('pdf', currentFile);
            formData.append('page_count', pageCount);
            formData.append('format', detectedFormat);
            formData.append('has_bleed', hasBleed ? '1' : '0');
            formData.append('bleed_mm', bleedMM);
            formData.append('name', document.getElementById('name').value);
            formData.append('email', document.getElementById('email').value);
            formData.append('street', document.getElementById('street').value);
            formData.append('number', document.getElementById('number').value);
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
                btn.innerHTML = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15l-4-4 1.41-1.41L11 14.17l5.59-5.59L18 10l-7 7z"/></svg> Betalen met iDEAL';
            }
        }

        // Event listeners
        dropzone.addEventListener('click', () => fileInput.click());
        dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('dragover'); });
        dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
        dropzone.addEventListener('drop', e => { e.preventDefault(); dropzone.classList.remove('dragover'); handleFile(e.dataTransfer.files[0]); });
        fileInput.addEventListener('change', e => handleFile(e.target.files[0]));
        document.getElementById('pdfRemove').addEventListener('click', removeFile);

        document.getElementById('toStep2').addEventListener('click', () => goToStep(2));
        document.getElementById('backToStep1').addEventListener('click', () => goToStep(1));
        document.getElementById('toStep3').addEventListener('click', () => { if (validateStep2()) { updateSummary(); goToStep(3); } });
        document.getElementById('backToStep2').addEventListener('click', () => goToStep(2));
        document.getElementById('payButton').addEventListener('click', submitOrder);

        document.querySelectorAll('#step2 input').forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim()) this.closest('.form-group').classList.remove('error');
            });
        });
    </script>
</body>
</html>
