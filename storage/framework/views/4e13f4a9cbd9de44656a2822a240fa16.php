<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Je bestelling ligt klaar! - PrintMijnPDF</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td {font-family: Arial, Helvetica, sans-serif !important;}
    </style>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f0f2f5; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f0f2f5; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 520px; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 8px 40px rgba(0,0,0,0.08);">

                    <!-- Header met logo -->
                    <tr>
                        <td bgcolor="#e63946" style="background: #e63946; background: linear-gradient(135deg, #e63946 0%, #d62839 100%); padding: 35px 30px; text-align: center;">
                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin: 0 auto;">
                                <tr>
                                    <td bgcolor="#ffffff" style="background: white; width: 50px; height: 50px; border-radius: 12px; text-align: center; vertical-align: middle;">
                                        <span style="color: #e63946; font-size: 24px; font-weight: bold;">P</span>
                                    </td>
                                    <td style="padding-left: 12px;">
                                        <span style="color: white; font-size: 22px; font-weight: 600; letter-spacing: -0.5px;">Print<span style="font-weight: 700;">Mijn</span>PDF</span>
                                    </td>
                                </tr>
                            </table>
                            <p style="color: rgba(255,255,255,0.9); font-size: 14px; margin: 15px 0 0; font-weight: 400;">Je bestelling ligt klaar!</p>
                        </td>
                    </tr>

                    <!-- Groene afhalen banner -->
                    <tr>
                        <td bgcolor="#0369a1" style="background: #0369a1; padding: 14px 30px; text-align: center;">
                            <span style="color: white; font-size: 14px; font-weight: 500;">🏪 Klaar om af te halen</span>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 35px 30px;">
                            <p style="color: #1a1a2e; font-size: 17px; margin: 0 0 8px; font-weight: 600;">
                                Goed nieuws, <?php echo e($order->customer_name); ?>!
                            </p>

                            <p style="color: #64748b; font-size: 15px; margin: 0 0 28px; line-height: 1.7;">
                                Je bestelling is klaar en kan worden afgehaald. Je bent welkom op het onderstaande adres tijdens de afhaaltijden.
                            </p>

                            <!-- Afhaaladres box -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-radius: 14px; margin-bottom: 25px; border: 1px solid #93c5fd;">
                                <tr>
                                    <td style="padding: 22px;">
                                        <p style="color: #0369a1; font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; margin: 0 0 12px; font-weight: 600;">Afhaaladres</p>
                                        <p style="color: #1e293b; font-size: 15px; margin: 0; line-height: 1.7; font-weight: 500;">
                                            NIVO Druk & Multimedia<br>
                                            <span style="font-weight: 400; color: #475569;">Exportweg 11<br>
                                            2645 ED Delfgauw</span>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Afhaaltijd box -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: #f0fdf4; border-radius: 14px; margin-bottom: 25px; border: 1px solid #86efac;">
                                <tr>
                                    <td style="padding: 22px; text-align: center;">
                                        <p style="color: #166534; font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; margin: 0 0 8px; font-weight: 600;">Afhaaltijd</p>
                                        <p style="color: #166534; font-size: 20px; font-weight: 700; margin: 0;">Ma t/m vr: 17:00 – 17:30</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Order nummer box -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: #f8fafc; border-radius: 14px; margin-bottom: 25px; border: 1px solid #e2e8f0;">
                                <tr>
                                    <td style="padding: 18px 22px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td>
                                                    <p style="color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 4px;">Bestelnummer</p>
                                                    <p style="color: #1e293b; font-size: 15px; font-weight: 600; margin: 0;"><?php echo e($order->order_number); ?></p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Inhoud pakket -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <p style="color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; margin: 0 0 10px; font-weight: 600;">Inhoud</p>
                                        <p style="color: #1e293b; font-size: 14px; margin: 0; line-height: 1.6;">
                                            <strong><?php echo e($order->pdf_original_name); ?></strong><br>
                                            <span style="color: #64748b;"><?php echo e($order->format); ?> · <?php echo e($order->page_count); ?> pagina's · <?php if($order->binding_type === 'booklet'): ?>Geniet boekje@else Losbladig (<?php echo e($order->print_side === 'double' ? 'dubbelzijdig' : 'enkelzijdig'); ?>)<?php endif; ?></span>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td bgcolor="#1e293b" style="background: #1e293b; padding: 28px 30px; text-align: center;">
                            <p style="color: #94a3b8; font-size: 13px; margin: 0 0 8px; line-height: 1.6;">
                                Vragen over het afhalen?
                            </p>
                            <a href="mailto:info@printmijnpdf.nl" style="color: #e63946; font-size: 14px; font-weight: 600; text-decoration: none;">info@printmijnpdf.nl</a>
                            <span style="color: #94a3b8; font-size: 13px;"> · </span>
                            <a href="tel:0152192525" style="color: #e63946; font-size: 14px; font-weight: 600; text-decoration: none;">015-219 2525</a>
                            <p style="color: #475569; font-size: 12px; margin: 20px 0 0;">
                                © <?php echo e(date('Y')); ?> PrintMijnPDF.nl · Alle rechten voorbehouden
                            </p>
                        </td>
                    </tr>

                </table>

                <p style="color: #94a3b8; font-size: 11px; margin: 25px 0 0; text-align: center;">
                    Je ontvangt deze email omdat je een bestelling hebt geplaatst op PrintMijnPDF.nl
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
<?php /**PATH /Users/elcoroest/Documents/GitHub/printmijnpdf/resources/views/emails/order-ready-pickup.blade.php ENDPATH**/ ?>