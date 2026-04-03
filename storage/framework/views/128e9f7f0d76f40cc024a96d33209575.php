<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Betaling niet gelukt - PrintMijnPDF</title>
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
                            <!--[if mso]>
                            <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:520px;height:120px;">
                            <v:fill type="gradient" color="#e63946" color2="#d62839" angle="135"/>
                            <v:textbox inset="0,0,0,0">
                            <![endif]-->
                            <!-- Logo -->
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
                            <p style="color: rgba(255,255,255,0.9); font-size: 14px; margin: 15px 0 0; font-weight: 400;">Betaling niet gelukt</p>
                            <!--[if mso]>
                            </v:textbox>
                            </v:rect>
                            <![endif]-->
                        </td>
                    </tr>

                    <!-- Oranje warning banner -->
                    <tr>
                        <td bgcolor="#f59e0b" style="background: #f59e0b; padding: 14px 30px; text-align: center;">
                            <span style="color: white; font-size: 14px; font-weight: 500;">⚠️ Betaling niet voltooid</span>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 35px 30px;">
                            <p style="color: #1a1a2e; font-size: 17px; margin: 0 0 8px; font-weight: 600;">
                                Hallo <?php echo e($order->customer_name); ?>,
                            </p>
                            
                            <p style="color: #64748b; font-size: 14px; line-height: 1.7; margin: 0 0 25px;">
                                Helaas is de betaling voor je bestelling niet gelukt. Je bestelling is daarom niet verwerkt.
                            </p>

                            <!-- Order info -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: #fef3c7; border-radius: 14px; margin-bottom: 25px; border: 1px solid #fcd34d;">
                                <tr>
                                    <td style="padding: 18px 20px; text-align: center;">
                                        <span style="color: #92400e; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Je bestelling</span>
                                        <p style="color: #78350f; font-size: 15px; margin: 8px 0 0; font-weight: 500;">
                                            <?php echo e($order->pdf_original_name); ?><br>
                                            <span style="font-size: 13px; color: #a16207;"><?php echo e($order->format); ?> · <?php echo e($order->page_count); ?> pagina's · <?php echo e($order->formatted_total); ?></span>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Wat nu? -->
                            <p style="color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; margin: 0 0 12px; font-weight: 600;">Wat nu?</p>
                            
                            <p style="color: #64748b; font-size: 14px; line-height: 1.7; margin: 0 0 20px;">
                                Geen zorgen! Je kunt eenvoudig opnieuw bestellen. Upload je PDF opnieuw en rond de betaling af.
                            </p>

                            <!-- CTA Button -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 25px;">
                                <tr>
                                    <td align="center">
                                        <a href="<?php echo e($retryUrl); ?>" style="display: inline-block; background: #e63946; color: white; padding: 14px 32px; border-radius: 10px; font-size: 15px; font-weight: 600; text-decoration: none;">Opnieuw bestellen</a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Hulp nodig -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <p style="color: #64748b; font-size: 13px; margin: 0; line-height: 1.6;">
                                            <strong style="color: #475569;">Problemen met betalen?</strong><br>
                                            Neem contact op via <a href="mailto:info@printmijnpdf.nl" style="color: #e63946; text-decoration: none;">info@printmijnpdf.nl</a> en we helpen je graag verder.
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
                                Vragen?
                            </p>
                            <a href="mailto:info@printmijnpdf.nl" style="color: #e63946; font-size: 14px; font-weight: 600; text-decoration: none;">info@printmijnpdf.nl</a>
                            <p style="color: #475569; font-size: 12px; margin: 20px 0 0;">
                                © <?php echo e(date('Y')); ?> PrintMijnPDF.nl · Alle rechten voorbehouden
                            </p>
                        </td>
                    </tr>

                </table>

                <!-- Tiny footer -->
                <p style="color: #94a3b8; font-size: 11px; margin: 25px 0 0; text-align: center;">
                    Je ontvangt deze email omdat je een bestelling hebt geplaatst op PrintMijnPDF.nl
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
<?php /**PATH /Users/elcoroest/Documents/GitHub/printmijnpdf/resources/views/emails/payment-failed.blade.php ENDPATH**/ ?>