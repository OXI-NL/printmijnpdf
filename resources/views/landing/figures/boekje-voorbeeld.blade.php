{{-- Illustratie van een opengeslagen, geniet boekje. $variant: cadeau, recepten of reis. Geen foto van echt drukwerk. --}}
@php
    $accent = ['cadeau' => '#e63946', 'recepten' => '#d97706', 'reis' => '#0f766e'][$variant] ?? '#e63946';
    $soft = ['cadeau' => '#fde2e4', 'recepten' => '#fef3c7', 'reis' => '#ccfbf1'][$variant] ?? '#fde2e4';
@endphp
<svg class="kh-figure" viewBox="0 0 240 150" role="img" aria-label="Illustratie: opengeslagen boekje ({{ $variant }})" xmlns="http://www.w3.org/2000/svg">
    <rect x="12" y="14" width="216" height="128" rx="6" fill="#000" opacity=".06"/>
    {{-- linker- en rechterpagina --}}
    <rect x="16" y="10" width="104" height="128" rx="3" fill="#fff" stroke="#e5e5e5"/>
    <rect x="120" y="10" width="104" height="128" rx="3" fill="#fff" stroke="#e5e5e5"/>
    <line x1="120" y1="10" x2="120" y2="138" stroke="#d4d4d4" stroke-width="1.5"/>
    {{-- nietjes in de rug --}}
    <rect x="118.5" y="40" width="3" height="12" rx="1" fill="#a3a3a3"/>
    <rect x="118.5" y="96" width="3" height="12" rx="1" fill="#a3a3a3"/>

    @if($variant === 'cadeau')
        <rect x="26" y="20" width="84" height="54" rx="2" fill="{{ $soft }}"/>
        <text x="68" y="56" text-anchor="middle" font-family="DM Sans, sans-serif" font-size="26" font-weight="700" fill="{{ $accent }}">50!</text>
        <circle cx="36" cy="28" r="2.5" fill="{{ $accent }}"/><circle cx="100" cy="30" r="2" fill="#f59e0b"/><circle cx="92" cy="66" r="2.5" fill="#0ea5e9"/>
        <rect x="26" y="82" width="70" height="6" rx="3" fill="{{ $accent }}"/>
        <rect x="26" y="94" width="84" height="4" rx="2" fill="#e5e5e5"/><rect x="26" y="102" width="78" height="4" rx="2" fill="#e5e5e5"/><rect x="26" y="110" width="82" height="4" rx="2" fill="#e5e5e5"/><rect x="26" y="118" width="60" height="4" rx="2" fill="#e5e5e5"/>
        <rect x="130" y="20" width="40" height="40" rx="2" fill="{{ $soft }}"/><rect x="174" y="20" width="40" height="40" rx="2" fill="#e0f2fe"/>
        <rect x="130" y="66" width="84" height="4" rx="2" fill="#e5e5e5"/><rect x="130" y="74" width="80" height="4" rx="2" fill="#e5e5e5"/><rect x="130" y="82" width="70" height="4" rx="2" fill="#e5e5e5"/>
        <rect x="130" y="94" width="84" height="34" rx="2" fill="#fef3c7"/>
    @elseif($variant === 'recepten')
        <rect x="26" y="20" width="84" height="56" rx="2" fill="{{ $soft }}"/>
        <ellipse cx="68" cy="56" rx="26" ry="9" fill="#fff" stroke="{{ $accent }}" stroke-width="2"/>
        <path d="M44 54 Q68 30 92 54" fill="{{ $accent }}" opacity=".35"/>
        <rect x="26" y="84" width="60" height="6" rx="3" fill="{{ $accent }}"/>
        <circle cx="29" cy="100" r="2" fill="{{ $accent }}"/><rect x="34" y="98" width="56" height="4" rx="2" fill="#e5e5e5"/>
        <circle cx="29" cy="109" r="2" fill="{{ $accent }}"/><rect x="34" y="107" width="48" height="4" rx="2" fill="#e5e5e5"/>
        <circle cx="29" cy="118" r="2" fill="{{ $accent }}"/><rect x="34" y="116" width="62" height="4" rx="2" fill="#e5e5e5"/>
        <rect x="130" y="20" width="56" height="6" rx="3" fill="{{ $accent }}"/>
        @foreach([34, 42, 50, 58, 66, 78, 86, 94, 102, 114, 122] as $y)
        <rect x="130" y="{{ $y }}" width="{{ 60 + ($y * 7) % 26 }}" height="4" rx="2" fill="#e5e5e5"/>
        @endforeach
    @else
        <rect x="26" y="20" width="84" height="64" rx="2" fill="{{ $soft }}"/>
        <path d="M34 72 C48 40, 62 74, 76 46 S 98 36, 102 30" fill="none" stroke="{{ $accent }}" stroke-width="2" stroke-dasharray="4 3"/>
        <circle cx="34" cy="72" r="3.5" fill="{{ $accent }}"/><circle cx="102" cy="30" r="3.5" fill="{{ $accent }}"/>
        <rect x="26" y="92" width="64" height="6" rx="3" fill="{{ $accent }}"/>
        <rect x="26" y="104" width="84" height="4" rx="2" fill="#e5e5e5"/><rect x="26" y="112" width="76" height="4" rx="2" fill="#e5e5e5"/><rect x="26" y="120" width="80" height="4" rx="2" fill="#e5e5e5"/>
        <rect x="130" y="20" width="84" height="50" rx="2" fill="#e0f2fe"/>
        <path d="M130 70 L156 46 L174 60 L190 44 L214 70 Z" fill="{{ $accent }}" opacity=".45"/>
        <circle cx="200" cy="32" r="6" fill="#fbbf24"/>
        <rect x="130" y="78" width="40" height="40" rx="2" fill="{{ $soft }}" transform="rotate(-4 150 98)"/>
        <rect x="176" y="80" width="38" height="4" rx="2" fill="#e5e5e5"/><rect x="176" y="88" width="34" height="4" rx="2" fill="#e5e5e5"/><rect x="176" y="96" width="36" height="4" rx="2" fill="#e5e5e5"/>
    @endif
</svg>
