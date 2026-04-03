<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $meta['title'] }}</title>
    <meta name="description" content="{{ $meta['description'] }}">
    <meta name="keywords" content="{{ $meta['keywords'] ?? '' }}">
    <link rel="canonical" href="{{ $meta['canonical'] }}">

    <!-- Open Graph -->
    <meta property="og:title" content="{{ $meta['title'] }}">
    <meta property="og:description" content="{{ $meta['description'] }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $meta['canonical'] }}">
    <meta property="og:image" content="{{ url('og-image.jpg') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="nl_NL">
    <meta property="og:site_name" content="PrintMijnPDF">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $meta['title'] }}">
    <meta name="twitter:description" content="{{ $meta['description'] }}">
    <meta name="twitter:image" content="{{ url('og-image.jpg') }}">

    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Service",
        "name": "{{ $hero['title'] }}",
        "description": "{{ $meta['description'] }}",
        "provider": {
            "@type": "PrintingService",
            "name": "PrintMijnPDF",
            "url": "https://printmijnpdf.nl"
        },
        "areaServed": {
            "@type": "Country",
            "name": "Netherlands"
        }
    }
    </script>

    @if(isset($faq) && count($faq) > 0)
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "FAQPage",
        "mainEntity": [
            @foreach($faq as $index => $item)
            {
                "@type": "Question",
                "name": "{{ $item['question'] }}",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "{{ $item['answer'] }}"
                }
            }@if(!$loop->last),@endif
            @endforeach
        ]
    }
    </script>
    @endif

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: #262626;
            background: #fff;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        /* Header */
        .header {
            padding: 1rem 0;
            border-bottom: 1px solid #e5e5e5;
        }

        .header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.25rem;
            font-weight: 700;
            color: #e63946;
            text-decoration: none;
        }

        .header-cta {
            font-size: 0.875rem;
            color: #525252;
        }

        /* Hero */
        .hero {
            padding: 3rem 0;
            text-align: center;
            background: linear-gradient(180deg, #fafafa 0%, #fff 100%);
        }

        .hero h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #171717;
            margin-bottom: 0.5rem;
        }

        .hero-subtitle {
            font-size: 1.125rem;
            color: #525252;
            margin-bottom: 1.5rem;
        }

        .hero-cta {
            display: inline-block;
            padding: 0.875rem 2rem;
            background: #e63946;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .hero-cta:hover {
            background: #d62839;
        }

        /* Benefits */
        .benefits {
            padding: 2.5rem 0;
            border-bottom: 1px solid #e5e5e5;
        }

        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .benefit {
            display: flex;
            gap: 0.75rem;
        }

        .benefit-icon {
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            color: #16a34a;
        }

        .benefit h3 {
            font-size: 0.9375rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .benefit p {
            font-size: 0.875rem;
            color: #525252;
        }

        /* Content */
        .content {
            padding: 2.5rem 0;
        }

        .content-intro {
            font-size: 1.125rem;
            color: #404040;
            margin-bottom: 2rem;
        }

        .content-section {
            margin-bottom: 1.5rem;
        }

        .content-section h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .content-section p {
            color: #525252;
        }

        /* FAQ */
        .faq {
            padding: 2.5rem 0;
            background: #fafafa;
        }

        .faq h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .faq-item {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            margin-bottom: 0.75rem;
            overflow: hidden;
        }

        .faq-item summary {
            padding: 1rem 1.25rem;
            font-weight: 500;
            cursor: pointer;
            list-style: none;
        }

        .faq-item summary::-webkit-details-marker {
            display: none;
        }

        .faq-item[open] summary {
            border-bottom: 1px solid #e5e5e5;
        }

        .faq-answer {
            padding: 1rem 1.25rem;
            color: #525252;
            font-size: 0.9375rem;
        }

        /* CTA Section */
        .cta-section {
            padding: 3rem 0;
            text-align: center;
        }

        .cta-section h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .cta-section p {
            color: #525252;
            margin-bottom: 1.5rem;
        }

        /* Footer */
        .footer {
            padding: 2rem 0;
            border-top: 1px solid #e5e5e5;
            text-align: center;
            font-size: 0.875rem;
            color: #737373;
        }

        .footer a {
            color: #525252;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 640px) {
            .hero h1 {
                font-size: 1.5rem;
            }

            .benefits-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <!-- GA4 -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-9F48GD4CX5"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-9F48GD4CX5');
    </script>
</head>
<body>
    <header class="header">
        <div class="container header-inner">
            <a href="{{ url('/') }}" class="logo">PrintMijnPDF</a>
            <span class="header-cta">015-219 2525</span>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <h1>{{ $hero['title'] }}</h1>
            <p class="hero-subtitle">{{ $hero['subtitle'] }}</p>
            <a href="{{ url('/') }}#upload" class="hero-cta">{{ $hero['cta'] }} &rarr;</a>
        </div>
    </section>

    <section class="benefits">
        <div class="container">
            <div class="benefits-grid">
                @foreach($benefits as $benefit)
                <div class="benefit">
                    <div class="benefit-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            @switch($benefit['icon'])
                                @case('clock')
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                    @break
                                @case('palette')
                                    <circle cx="13.5" cy="6.5" r="2.5"></circle>
                                    <circle cx="19" cy="13" r="2.5"></circle>
                                    <circle cx="5" cy="13" r="2.5"></circle>
                                    <circle cx="13.5" cy="19.5" r="2.5"></circle>
                                    <circle cx="12" cy="12" r="3"></circle>
                                    @break
                                @case('book')
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                    @break
                                @case('euro')
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M8 12h6"></path>
                                    <path d="M8 9h6"></path>
                                    <path d="M14 15.5a3.5 3.5 0 1 1 0-7"></path>
                                    @break
                                @case('users')
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    @break
                                @case('heart')
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                    @break
                                @case('zap')
                                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                                    @break
                                @case('briefcase')
                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                    @break
                                @case('refresh')
                                    <polyline points="23 4 23 10 17 10"></polyline>
                                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                                    @break
                                @case('shield')
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                    @break
                                @case('phone')
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                    @break
                                @case('file-text')
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                    @break
                                @case('package')
                                    <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                    @break
                                @case('repeat')
                                    <polyline points="17 1 21 5 17 9"></polyline>
                                    <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                                    <polyline points="7 23 3 19 7 15"></polyline>
                                    <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                                    @break
                                @default
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="9 12 12 15 16 10"></polyline>
                            @endswitch
                        </svg>
                    </div>
                    <div>
                        <h3>{{ $benefit['title'] }}</h3>
                        <p>{{ $benefit['text'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container">
            <p class="content-intro">{{ $content['intro'] }}</p>

            @foreach($content['sections'] as $section)
            <div class="content-section">
                <h2>{{ $section['title'] }}</h2>
                <p>{{ $section['text'] }}</p>
            </div>
            @endforeach
        </div>
    </section>

    @if(isset($faq) && count($faq) > 0)
    <section class="faq">
        <div class="container">
            <h2>Veelgestelde vragen</h2>

            @foreach($faq as $item)
            <details class="faq-item">
                <summary>{{ $item['question'] }}</summary>
                <div class="faq-answer">{{ $item['answer'] }}</div>
            </details>
            @endforeach
        </div>
    </section>
    @endif

    <section class="cta-section">
        <div class="container">
            <h2>Klaar om te printen?</h2>
            <p>Upload je PDF en ontvang je drukwerk binnen 3 werkdagen.</p>
            <a href="{{ url('/') }}#upload" class="hero-cta">Start je bestelling &rarr;</a>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>
                <a href="{{ url('/') }}">PrintMijnPDF</a> ·
                Onderdeel van <a href="https://nivo.com" target="_blank" rel="noopener">NIVO Druk & Multimedia B.V.</a>
            </p>
            <p style="margin-top: 0.5rem;">
                <a href="mailto:info@printmijnpdf.nl">info@printmijnpdf.nl</a> ·
                <a href="tel:0152192525">015-219 2525</a>
            </p>
        </div>
    </footer>
</body>
</html>
