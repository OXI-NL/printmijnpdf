<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo e($meta['title']); ?></title>
    <meta name="description" content="<?php echo e($meta['description']); ?>">
    <meta name="keywords" content="<?php echo e($meta['keywords'] ?? ''); ?>">
    <link rel="canonical" href="<?php echo e($meta['canonical']); ?>">

    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo e($meta['title']); ?>">
    <meta property="og:description" content="<?php echo e($meta['description']); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo e($meta['canonical']); ?>">
    <meta property="og:image" content="<?php echo e(url('og-image.jpg')); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="nl_NL">
    <meta property="og:site_name" content="PrintMijnPDF">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo e($meta['title']); ?>">
    <meta name="twitter:description" content="<?php echo e($meta['description']); ?>">
    <meta name="twitter:image" content="<?php echo e(url('og-image.jpg')); ?>">

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
        "name": "<?php echo e($hero['title']); ?>",
        "description": "<?php echo e($meta['description']); ?>",
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

    <?php if(isset($faq) && count($faq) > 0): ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "FAQPage",
        "mainEntity": [
            <?php $__currentLoopData = $faq; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            {
                "@type": "Question",
                "name": "<?php echo e($item['question']); ?>",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "<?php echo e($item['answer']); ?>"
                }
            }<?php if(!$loop->last): ?>,<?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        ]
    }
    </script>
    <?php endif; ?>

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
            color: #f05a28;
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
            background: #f05a28;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .hero-cta:hover {
            background: #d94f21;
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
            <a href="<?php echo e(url('/')); ?>" class="logo">PrintMijnPDF</a>
            <span class="header-cta">015-219 2525</span>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <h1><?php echo e($hero['title']); ?></h1>
            <p class="hero-subtitle"><?php echo e($hero['subtitle']); ?></p>
            <a href="<?php echo e(url('/')); ?>#upload" class="hero-cta"><?php echo e($hero['cta']); ?> &rarr;</a>
        </div>
    </section>

    <section class="benefits">
        <div class="container">
            <div class="benefits-grid">
                <?php $__currentLoopData = $benefits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $benefit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="benefit">
                    <div class="benefit-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <?php switch($benefit['icon']):
                                case ('clock'): ?>
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                    <?php break; ?>
                                <?php case ('palette'): ?>
                                    <circle cx="13.5" cy="6.5" r="2.5"></circle>
                                    <circle cx="19" cy="13" r="2.5"></circle>
                                    <circle cx="5" cy="13" r="2.5"></circle>
                                    <circle cx="13.5" cy="19.5" r="2.5"></circle>
                                    <circle cx="12" cy="12" r="3"></circle>
                                    <?php break; ?>
                                <?php case ('book'): ?>
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                    <?php break; ?>
                                <?php case ('euro'): ?>
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M8 12h6"></path>
                                    <path d="M8 9h6"></path>
                                    <path d="M14 15.5a3.5 3.5 0 1 1 0-7"></path>
                                    <?php break; ?>
                                <?php case ('users'): ?>
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    <?php break; ?>
                                <?php case ('heart'): ?>
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                    <?php break; ?>
                                <?php case ('zap'): ?>
                                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                                    <?php break; ?>
                                <?php case ('briefcase'): ?>
                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                    <?php break; ?>
                                <?php case ('refresh'): ?>
                                    <polyline points="23 4 23 10 17 10"></polyline>
                                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                                    <?php break; ?>
                                <?php case ('shield'): ?>
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                    <?php break; ?>
                                <?php case ('phone'): ?>
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                    <?php break; ?>
                                <?php case ('file-text'): ?>
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                    <?php break; ?>
                                <?php case ('package'): ?>
                                    <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                    <?php break; ?>
                                <?php case ('repeat'): ?>
                                    <polyline points="17 1 21 5 17 9"></polyline>
                                    <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                                    <polyline points="7 23 3 19 7 15"></polyline>
                                    <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                                    <?php break; ?>
                                <?php default: ?>
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="9 12 12 15 16 10"></polyline>
                            <?php endswitch; ?>
                        </svg>
                    </div>
                    <div>
                        <h3><?php echo e($benefit['title']); ?></h3>
                        <p><?php echo e($benefit['text']); ?></p>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container">
            <p class="content-intro"><?php echo e($content['intro']); ?></p>

            <?php $__currentLoopData = $content['sections']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $section): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="content-section">
                <h2><?php echo e($section['title']); ?></h2>
                <p><?php echo e($section['text']); ?></p>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </section>

    <?php if(isset($faq) && count($faq) > 0): ?>
    <section class="faq">
        <div class="container">
            <h2>Veelgestelde vragen</h2>

            <?php $__currentLoopData = $faq; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <details class="faq-item">
                <summary><?php echo e($item['question']); ?></summary>
                <div class="faq-answer"><?php echo e($item['answer']); ?></div>
            </details>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="cta-section">
        <div class="container">
            <h2>Klaar om te printen?</h2>
            <p>Upload je PDF en ontvang je drukwerk binnen 3 werkdagen.</p>
            <a href="<?php echo e(url('/')); ?>#upload" class="hero-cta">Start je bestelling &rarr;</a>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>
                <a href="<?php echo e(url('/')); ?>">PrintMijnPDF</a> ·
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
<?php /**PATH /Users/elcoroest/Documents/GitHub/printmijnpdf/resources/views/landing/page.blade.php ENDPATH**/ ?>