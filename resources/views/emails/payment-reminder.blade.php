<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Je bestelling wacht nog - PrintMijnPDF</title>
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
                            <p style="color: rgba(255,255,255,0.9); font-size: 14px; margin: 15px 0 0; font-weight: 400;">Herinnering</p>
                            <!--[if mso]>
                            </v:textbox>
                            </v:rect>
                            <![endif]-->
                        </td>
                    </tr>

                    <!-- Blauwe info banner -->
                    <tr>
                        <td bgcolor="#3b82f6" style="background: #3b82f6; padding: 14px 30px; text-align: center;">
                            <span style="color: white; font-size: 14px; font-weight: 500;">⏰ Je bestelling wacht nog op betaling</span>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 35px 30px;">
                            <p style="color: #1a1a2e; font-size: 17px; margin: 0 0 8px; font-weight: 600;">
                                Hallo {{ $order->customer_name }},
                            </p>
                            
                            <p style="color: #64748b; font-size: 14px; line-height: 1.7; margin: 0 0 25px;">
                                We zagen dat je een bestelling bent gestart, maar de betaling nog niet hebt afgerond. Geen probleem — je kunt gewoon opnieuw bestellen wanneer het je uitkomt.
                            </p>

                            <!-- Order info -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: #eff6ff; border-radius: 14px; margin-bottom: 25px; border: 1px solid #bfdbfe;">
                                <tr>
                                    <td style="padding: 18px 20px; text-align: center;">
                                        <span style="color: #1e40af; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Je bestelling</span>
                                        <p style="color: #1e3a8a; font-size: 15px; margin: 8px 0 0; font-weight: 500;">
                                            {{ $order->pdf_original_name }}<br>
                                            <span style="font-size: 13px; color: #3b82f6;">{{ $order->format }} · {{ $order->page_count }} pagina's · {{ $order->formatted_total }}</span>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 25px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $retryUrl }}" style="display: inline-block; background: #e63946; color: white; padding: 14px 32px; border-radius: 10px; font-size: 15px; font-weight: 600; text-decoration: none;">Nu bestellen</a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Extra info -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: #f0fdf4; border-radius: 12px; border: 1px solid #bbf7d0;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <p style="color: #166534; font-size: 13px; margin: 0; line-height: 1.6;">
                                            <strong>📦 Snelle levering</strong><br>
                                            <span style="color: #15803d;">Bestel nu en ontvang je print binnen 3 werkdagen!</span>
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
                                © {{ date('Y') }} PrintMijnPDF.nl · Alle rechten voorbehouden
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
