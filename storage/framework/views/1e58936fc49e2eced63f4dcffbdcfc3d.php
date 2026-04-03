<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orderbon - <?php echo e($order->order_number); ?></title>
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
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 580px; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 8px 40px rgba(0,0,0,0.08);">

                    <!-- Header -->
                    <tr>
                        <td bgcolor="#1e293b" style="background: #1e293b; padding: 30px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td>
                                        <table role="presentation" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td bgcolor="#e63946" style="background: #e63946; width: 40px; height: 40px; border-radius: 10px; text-align: center; vertical-align: middle;">
                                                    <span style="color: white; font-size: 20px; font-weight: bold;">P</span>
                                                </td>
                                                <td style="padding-left: 12px;">
                                                    <span style="color: white; font-size: 18px; font-weight: 600;">Print<span style="font-weight: 700;">Mijn</span>PDF</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td style="text-align: right;">
                                        <span style="color: #94a3b8; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Orderbon</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Order nummer banner -->
                    <tr>
                        <td bgcolor="#e63946" style="background: #e63946; padding: 20px 30px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td>
                                        <span style="color: rgba(255,255,255,0.8); font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Bestelnummer</span><br>
                                        <span style="color: white; font-size: 24px; font-weight: 700; font-family: 'SF Mono', Monaco, 'Courier New', monospace; letter-spacing: 0.5px;"><?php echo e($order->order_number); ?></span>
                                    </td>
                                    <td style="text-align: right;">
                                        <span style="color: rgba(255,255,255,0.8); font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Totaal</span><br>
                                        <span style="color: white; font-size: 24px; font-weight: 700;"><?php echo e($order->formatted_total); ?></span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Besteldatum & status -->
                    <tr>
                        <td style="padding: 25px 30px 0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="padding: 8px 12px; background: #f0fdf4; border-radius: 8px; text-align: center;">
                                        <span style="color: #166534; font-size: 13px; font-weight: 600;">Betaald op <?php echo e($order->paid_at ? $order->paid_at->format('d-m-Y \o\m H:i') : $order->created_at->format('d-m-Y \o\m H:i')); ?></span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Klantgegevens -->
                    <tr>
                        <td style="padding: 25px 30px 0;">
                            <p style="color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; margin: 0 0 10px; font-weight: 600;">Klantgegevens</p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 12px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; width: 120px;">
                                        <span style="color: #64748b; font-size: 13px;">Naam</span>
                                    </td>
                                    <td style="padding: 12px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                        <span style="color: #1e293b; font-size: 13px; font-weight: 600;"><?php echo e($order->customer_name); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">
                                        <span style="color: #64748b; font-size: 13px;">E-mail</span>
                                    </td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">
                                        <a href="mailto:<?php echo e($order->customer_email); ?>" style="color: #e63946; font-size: 13px; text-decoration: none; font-weight: 500;"><?php echo e($order->customer_email); ?></a>
                                    </td>
                                </tr>
                                <?php if($order->customer_phone): ?>
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
                                        <span style="color: #64748b; font-size: 13px;">Telefoon</span>
                                    </td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
                                        <a href="tel:<?php echo e($order->customer_phone); ?>" style="color: #1e293b; font-size: 13px; text-decoration: none;"><?php echo e($order->customer_phone); ?></a>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td style="padding: 12px 16px;<?php echo e($order->customer_phone ? '' : ' background: #f8fafc;'); ?>">
                                        <span style="color: #64748b; font-size: 13px;">Adres</span>
                                    </td>
                                    <td style="padding: 12px 16px;<?php echo e($order->customer_phone ? '' : ' background: #f8fafc;'); ?>">
                                        <span style="color: #1e293b; font-size: 13px;">
                                            <?php echo e($order->address_street); ?> <?php echo e($order->address_number); ?><?php echo e($order->address_addition ? ' '.$order->address_addition : ''); ?><br>
                                            <?php echo e($order->address_postcode); ?> <?php echo e($order->address_city); ?>

                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Levering -->
                    <tr>
                        <td style="padding: 20px 30px 0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background: <?php echo e($order->delivery_type === 'pickup' ? '#f0f9ff' : '#fefce8'); ?>; border-radius: 10px; padding: 14px 16px;">
                                <tr>
                                    <td style="padding: 14px 16px;">
                                        <?php if($order->delivery_type === 'pickup'): ?>
                                            <span style="color: #0369a1; font-size: 14px; font-weight: 600;">Afhalen bij NIVO</span><br>
                                            <span style="color: #0369a1; font-size: 13px;">Exportweg 11, 2645ED Delfgauw</span>
                                        <?php else: ?>
                                            <span style="color: #854d0e; font-size: 14px; font-weight: 600;">Verzenden via PostNL</span><br>
                                            <span style="color: #854d0e; font-size: 13px;">Naar: <?php echo e($order->address_street); ?> <?php echo e($order->address_number); ?><?php echo e($order->address_addition ? ' '.$order->address_addition : ''); ?>, <?php echo e($order->address_postcode); ?> <?php echo e($order->address_city); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Productgegevens -->
                    <tr>
                        <td style="padding: 25px 30px 0;">
                            <p style="color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; margin: 0 0 10px; font-weight: 600;">Productgegevens</p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 12px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                        <span style="color: #64748b; font-size: 13px;">Bestand</span>
                                    </td>
                                    <td style="padding: 12px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: right;">
                                        <span style="color: #1e293b; font-size: 13px; font-weight: 600;"><?php echo e($order->pdf_original_name); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">
                                        <span style="color: #64748b; font-size: 13px;">Formaat</span>
                                    </td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; text-align: right;">
                                        <span style="color: #1e293b; font-size: 13px; font-weight: 500;"><?php echo e($order->format); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                        <span style="color: #64748b; font-size: 13px;">Aantal pagina's</span>
                                    </td>
                                    <td style="padding: 12px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: right;">
                                        <span style="color: #1e293b; font-size: 13px; font-weight: 500;"><?php echo e($order->page_count); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">
                                        <span style="color: #64748b; font-size: 13px;">Afwerking</span>
                                    </td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; text-align: right;">
                                        <span style="color: #1e293b; font-size: 13px; font-weight: 600;">
                                            <?php if($order->binding_type === 'booklet'): ?>
                                                Geniet boekje (dubbelzijdig)
                                            <?php else: ?>
                                                Losse pagina's (<?php echo e($order->print_side === 'double' ? 'dubbelzijdig' : 'enkelzijdig'); ?>)
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                        <span style="color: #64748b; font-size: 13px;">Aantal exemplaren</span>
                                    </td>
                                    <td style="padding: 12px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: right;">
                                        <span style="color: #1e293b; font-size: 13px; font-weight: 600;"><?php echo e($order->quantity ?? 1); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; background: #f8fafc;">
                                        <span style="color: #64748b; font-size: 13px;">Print</span>
                                    </td>
                                    <td style="padding: 12px 16px; background: #f8fafc; text-align: right;">
                                        <span style="color: #1e293b; font-size: 13px; font-weight: 500;">Full colour (CMYK)</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Prijsspecificatie -->
                    <tr>
                        <td style="padding: 25px 30px 0;">
                            <p style="color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; margin: 0 0 10px; font-weight: 600;">Prijsspecificatie</p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 10px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                        <span style="color: #64748b; font-size: 13px;">Opstartkosten<?php echo e(($order->quantity ?? 1) > 1 ? ' ('.$order->quantity.'×)' : ''); ?></span>
                                    </td>
                                    <td style="padding: 10px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: right;">
                                        <span style="color: #1e293b; font-size: 13px;">&euro; <?php echo e(number_format($order->price_startup / 100, 2, ',', '.')); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #e2e8f0;">
                                        <span style="color: #64748b; font-size: 13px;"><?php echo e($order->page_count); ?> pag.<?php echo e(($order->quantity ?? 1) > 1 ? ' × '.$order->quantity.' ex.' : ''); ?> &times; &euro; <?php echo e(number_format($order->price_pages / $order->page_count / ($order->quantity ?? 1) / 100, 2, ',', '.')); ?></span>
                                    </td>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #e2e8f0; text-align: right;">
                                        <span style="color: #1e293b; font-size: 13px;">&euro; <?php echo e(number_format($order->price_pages / 100, 2, ',', '.')); ?></span>
                                    </td>
                                </tr>
                                <?php if($order->binding_type === 'booklet'): ?>
                                <tr>
                                    <td style="padding: 10px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                        <span style="color: #64748b; font-size: 13px;">Nieten (boekje)</span>
                                    </td>
                                    <td style="padding: 10px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: right;">
                                        <span style="color: #1e293b; font-size: 13px;">&euro; <?php echo e(number_format($order->price_binding / 100, 2, ',', '.')); ?></span>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td style="padding: 10px 16px;<?php echo e($order->binding_type !== 'booklet' ? ' background: #f8fafc;' : ''); ?> border-bottom: 1px solid #e2e8f0;">
                                        <span style="color: #64748b; font-size: 13px;"><?php echo e($order->delivery_type === 'pickup' ? 'Afhalen' : 'Verzendkosten (PostNL)'); ?></span>
                                    </td>
                                    <td style="padding: 10px 16px;<?php echo e($order->binding_type !== 'booklet' ? ' background: #f8fafc;' : ''); ?> border-bottom: 1px solid #e2e8f0; text-align: right;">
                                        <span style="color: #1e293b; font-size: 13px;">
                                            <?php if($order->price_shipping > 0): ?>
                                                &euro; <?php echo e(number_format($order->price_shipping / 100, 2, ',', '.')); ?>

                                            <?php else: ?>
                                                Gratis
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php if(($order->price_discount ?? 0) > 0): ?>
                                <tr>
                                    <td style="padding: 10px 16px; background: #f0fdf4; border-bottom: 1px solid #e2e8f0;">
                                        <span style="color: #16a34a; font-size: 13px;">Korting (<?php echo e($order->promo_code); ?>)</span>
                                    </td>
                                    <td style="padding: 10px 16px; background: #f0fdf4; border-bottom: 1px solid #e2e8f0; text-align: right;">
                                        <span style="color: #16a34a; font-size: 13px;">-&euro; <?php echo e(number_format($order->price_discount / 100, 2, ',', '.')); ?></span>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td style="padding: 14px 16px; background: #1e293b;">
                                        <span style="color: white; font-size: 15px; font-weight: 700;">Totaal (incl. BTW)</span>
                                    </td>
                                    <td style="padding: 14px 16px; background: #1e293b; text-align: right;">
                                        <span style="color: white; font-size: 18px; font-weight: 700;"><?php echo e($order->formatted_total); ?></span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Admin link -->
                    <tr>
                        <td style="padding: 25px 30px 30px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="text-align: center; padding: 16px; background: #f8fafc; border-radius: 12px;">
                                        <a href="<?php echo e(url('/admin/orders')); ?>" style="color: #e63946; font-size: 14px; font-weight: 600; text-decoration: none;">Bekijk in admin panel &rarr;</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td bgcolor="#1e293b" style="background: #1e293b; padding: 20px 30px; text-align: center;">
                            <p style="color: #475569; font-size: 12px; margin: 0;">
                                Automatisch gegenereerde orderbon &middot; <?php echo e(now()->format('d-m-Y H:i')); ?>

                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
<?php /**PATH /Users/elcoroest/Documents/GitHub/printmijnpdf/resources/views/emails/admin-order-notification.blade.php ENDPATH**/ ?>