<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | PrintMijnPDF</title>
    <meta name="description" content="@yield('description')">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" type="image/png" sizes="48x48" href="/favicon.png">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @yield('schema')
    <style>
        :root {
            --primary: #d63447;
            --text: #1a1a2e;
            --text-muted: #6c757d;
            --bg: #f8f9fa;
            --border: #e9ecef;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text);
            background: var(--bg);
            line-height: 1.65;
        }
        .container { max-width: 720px; margin: 0 auto; padding: 0 1rem; }
        header { padding: 1.25rem 0; border-bottom: 1px solid var(--border); background: #fff; }
        header a { color: var(--text); font-weight: 700; text-decoration: none; }
        main { padding: 2rem 0 3rem; }
        h1 { font-size: 1.75rem; line-height: 1.25; margin: 0 0 0.5rem; }
        h2 { font-size: 1.15rem; margin: 2rem 0 0.5rem; }
        p, li { font-size: 15px; }
        ul { padding-left: 1.25rem; }
        a { color: var(--primary); }
        .lead { color: var(--text-muted); margin-top: 0; }
        .cta { display: inline-block; margin-top: 1.5rem; padding: 0.75rem 1.25rem; border-radius: 10px; background: var(--primary); color: #fff; font-weight: 600; text-decoration: none; }
        .updated { color: var(--text-muted); font-size: 13px; margin-top: 2rem; }
        footer { border-top: 1px solid var(--border); padding: 1.5rem 0; font-size: 14px; color: var(--text-muted); text-align: center; }
        footer a { color: inherit; }
    </style>
</head>
<body>
    <header>
        <div class="container"><a href="{{ url('/') }}">← PrintMijnPDF</a></div>
    </header>
    <main class="container">
        @yield('content')
    </main>
    <footer>
        <div class="container">
            <p>
                <a href="{{ url('/') }}">PrintMijnPDF</a> · Onderdeel van NIVO Druk & Multimedia B.V.<br>
                <a href="{{ route('over-ons') }}">Over ons</a> · <a href="{{ route('privacy') }}">Privacy</a> · <a href="{{ route('leveringsvoorwaarden') }}">Leveringsvoorwaarden</a> ·
                <a href="mailto:info@printmijnpdf.nl">info@printmijnpdf.nl</a> · <a href="tel:0152192525">015-219 2525</a>
            </p>
        </div>
    </footer>
</body>
</html>
