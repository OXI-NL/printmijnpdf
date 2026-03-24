<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factuur - PrintMijnPDF</title>
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
                            <p style="color: rgba(255,255,255,0.9); font-size: 14px; margin: 15px 0 0; font-weight: 400;">Je factuur is beschikbaar</p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 35px 30px;">
                            <p style="color: #1a1a2e; font-size: 17px; margin: 0 0 8px; font-weight: 600;">
                                Hallo {{ $order->customer_name }},
                            </p>

                            <p style="color: #64748b; font-size: 15px; margin: 0 0 28px; line-height: 1.7;">
                                Hierbij ontvang je de factuur voor je bestelling bij PrintMijnPDF. De factuur is als PDF bijgevoegd bij deze e-mail.
                            </p>

                            <!-- Factuur details box -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: linear-gradient(135deg, #fef2f2 0%, #fff1f2 100%); border-radius: 14px; margin-bottom: 25px; border: 1px solid #fecaca;">
                                <tr>
                                    <td style="padding: 22px;">
                                        <p style="color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; margin: 0 0 6px; font-weight: 600;">Factuurnummer</p>
                                        <p style="color: #e63946; font-size: 22px; font-weight: 700; margin: 0; font-family: 'SF Mono', Monaco, 'Courier New', monospace; letter-spacing: 0.5px;">{{ $invoice->invoice_number }}</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Factuur samenvatting -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 25px; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 14px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                        <span style="color: #64748b; font-size: 13px;">Bestelnummer</span>
                                    </td>
                                    <td style="padding: 14px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: right;">
                                        <span style="color: #1e293b; font-size: 13px; font-weight: 600;">{{ $order->order_number }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">
                                        <span style="color: #64748b; font-size: 13px;">Factuurdatum</span>
                                    </td>
                                    <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0; text-align: right;">
                                        <span style="color: #1e293b; font-size: 13px; font-weight: 500;">{{ $invoice->invoice_date->format('d-m-Y') }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 14px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                        <span style="color: #64748b; font-size: 13px;">Subtotaal excl. BTW</span>
                                    </td>
                                    <td style="padding: 14px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: right;">
                                        <span style="color: #1e293b; font-size: 13px; font-weight: 500;">{{ $invoice->formatted_excl_btw }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0;">
                                        <span style="color: #64748b; font-size: 13px;">BTW (21%)</span>
                                    </td>
                                    <td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0; text-align: right;">
                                        <span style="color: #1e293b; font-size: 13px; font-weight: 500;">{{ $invoice->formatted_btw }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px; background: #1e293b;">
                                        <span style="color: white; font-size: 14px; font-weight: 600;">Totaal incl. BTW</span>
                                    </td>
                                    <td style="padding: 16px; background: #1e293b; text-align: right;">
                                        <span style="color: white; font-size: 16px; font-weight: 700;">{{ $invoice->formatted_total }}</span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Info box -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <p style="color: #475569; font-size: 13px; margin: 0; line-height: 1.7;">
                                            De factuur in PDF-formaat vind je als bijlage bij deze e-mail. Dit bedrag is reeds voldaan via iDEAL.
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
                                Vragen over je factuur?
                            </p>
                            <a href="mailto:info@printmijnpdf.nl" style="color: #e63946; font-size: 14px; font-weight: 600; text-decoration: none;">info@printmijnpdf.nl</a>
                            <p style="color: #475569; font-size: 12px; margin: 20px 0 0;">
                                &copy; {{ date('Y') }} PrintMijnPDF.nl &middot; Alle rechten voorbehouden
                            </p>
                        </td>
                    </tr>

                </table>

                <!-- Tiny footer -->
                <p style="color: #94a3b8; font-size: 11px; margin: 25px 0 0; text-align: center;">
                    Je ontvangt deze email omdat je een factuur hebt aangevraagd bij PrintMijnPDF.nl
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
