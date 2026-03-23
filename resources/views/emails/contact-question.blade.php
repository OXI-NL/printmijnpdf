<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; padding: 2rem;">
    <div style="max-width: 560px; margin: 0 auto; background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <span style="color: #dc2626; font-weight: 700; font-size: 20px;">🖨️ PrintMijnPDF</span>
        </div>

        @if($isCustomerCopy)
            <p>Beste {{ $customerName }},</p>
            <p>Bedankt voor je bericht. We hebben je vraag ontvangen en reageren zo snel mogelijk, meestal binnen 24 uur.</p>
            <p><strong>Je vraag:</strong></p>
        @else
            <p><strong>Nieuwe vraag via het contactformulier</strong></p>
            <p><strong>Naam:</strong> {{ $customerName }}<br>
            <strong>E-mail:</strong> {{ $customerEmail }}</p>
            <p><strong>Vraag:</strong></p>
        @endif

        <div style="background: #f9fafb; border-left: 3px solid #dc2626; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
            {!! nl2br(e($question)) !!}
        </div>

        @if($isCustomerCopy)
            <p style="color: #6b7280; font-size: 14px;">Met vriendelijke groet,<br>PrintMijnPDF.nl<br>
            <a href="tel:0152192525">015-219 2525</a></p>
        @endif
    </div>
</body>
</html>
