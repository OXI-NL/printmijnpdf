<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Je bestelling is onderweg! - PrintMijnPDF</title>
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
                            <p style="color: rgba(255,255,255,0.9); font-size: 14px; margin: 15px 0 0; font-weight: 400;">Je bestelling is onderweg!</p>
                            <!--[if mso]>
                            </v:textbox>
                            </v:rect>
                            <![endif]-->
                        </td>
                    </tr>

                    <!-- Blauwe verzonden banner -->
                    <tr>
                        <td bgcolor="#3b82f6" style="background: #3b82f6; padding: 14px 30px; text-align: center;">
                            <span style="color: white; font-size: 14px; font-weight: 500;">🚚 Verzonden via PostNL</span>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 35px 30px;">
                            <p style="color: #1a1a2e; font-size: 17px; margin: 0 0 8px; font-weight: 600;">
                                Goed nieuws, {{ $order->customer_name }}!
                            </p>
                            
                            <p style="color: #64748b; font-size: 15px; margin: 0 0 28px; line-height: 1.7;">
                                Je bestelling is zojuist verzonden en is onderweg naar je toe. Hieronder vind je de Track & Trace code waarmee je je pakket kunt volgen.
                            </p>

                            <!-- Track & Trace box -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-radius: 14px; margin-bottom: 25px; border: 1px solid #93c5fd;">
                                <tr>
                                    <td style="padding: 22px; text-align: center;">
                                        <p style="color: #3b82f6; font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; margin: 0 0 10px; font-weight: 600;">Track & Trace code</p>
                                        <p style="color: #1e40af; font-size: 20px; font-weight: 700; margin: 0 0 16px; font-family: 'SF Mono', Monaco, 'Courier New', monospace; letter-spacing: 1px;">{{ $order->track_trace }}</p>
                                        <a href="https://postnl.nl/tracktrace/?B={{ $order->track_trace }}&P={{ $order->address_postcode }}&D=NL&T=C" style="display: inline-block; background: #3b82f6; color: white; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-size: 14px; font-weight: 600;">Volg je pakket →</a>
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
                                                    <p style="color: #1e293b; font-size: 15px; font-weight: 600; margin: 0;">{{ $order->order_number }}</p>
                                                </td>
                                                <td style="text-align: right;">
                                                    <p style="color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 4px;">Verzonden op</p>
                                                    <p style="color: #1e293b; font-size: 15px; font-weight: 600; margin: 0;">{{ $order->shipped_at->format('d-m-Y') }}</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Verzendadres -->
                            <p style="color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; margin: 0 0 12px; font-weight: 600;">Wordt bezorgd op</p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: #f8fafc; border-radius: 12px; margin-bottom: 28px; border: 1px solid #e2e8f0;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <p style="color: #1e293b; font-size: 14px; margin: 0; line-height: 1.7; font-weight: 500;">
                                            {{ $order->customer_name }}<br>
                                            <span style="font-weight: 400; color: #475569;">{{ $order->address_street }} {{ $order->address_number }}{{ $order->address_addition ? ' '.$order->address_addition : '' }}<br>
                                            {{ $order->address_postcode }} {{ $order->address_city }}</span>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Inhoud pakket -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <p style="color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; margin: 0 0 10px; font-weight: 600;">Inhoud</p>
                                        <p style="color: #1e293b; font-size: 14px; margin: 0; line-height: 1.6;">
                                            <strong>{{ $order->pdf_original_name }}</strong><br>
                                            <span style="color: #64748b;">{{ $order->format }} · {{ $order->page_count }} pagina's · @if($order->binding_type === 'booklet')Geniet boekje@else Losbladig ({{ $order->print_side === 'double' ? 'dubbelzijdig' : 'enkelzijdig' }})@endif</span>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <!-- Footer -->
                    <tr>
                        <td bgcolor="#1e293b" style="background: #1e293b; padding: 28px 30px; text-align: center;">
                            <p style="color: #94a3b8; font-size: 13px; margin: 0 0 8px; line-height: 1.6;">
                                Vragen over je bezorging?
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
