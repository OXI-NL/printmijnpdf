# PrintMijnPDF.nl

Een eenvoudige Laravel 11 applicatie voor PDF print services met iDEAL betalingen via Mollie.

## Features

- 📄 PDF upload met automatische formaat detectie (A4/A5)
- 💳 iDEAL betalingen via Mollie
- 📧 Automatische bevestigingsmails
- 🚚 Verzendnotificaties met Track & Trace
- 📱 Responsive design

## Vereisten

- PHP 8.2+
- MySQL 8.0+
- Composer
- Mollie account

## Installatie

```bash
# Clone repository
git clone https://github.com/OXI-NL/printmijnpdf.git
cd printmijnpdf

# Installeer dependencies
composer install

# Kopieer en configureer .env
cp .env.example .env
php artisan key:generate

# Configureer database en Mollie key in .env

# Run migrations
php artisan migrate

# Maak storage link
php artisan storage:link
```

## Configuratie

### .env variabelen

```env
# Database
DB_DATABASE=printmijnpdf
DB_USERNAME=your_user
DB_PASSWORD=your_password

# Mollie (verkrijg via my.mollie.com)
MOLLIE_KEY=live_xxxxxxxxxxxxx

# Admin notificaties
ADMIN_EMAIL=info@printmijnpdf.nl

# SMTP
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=info@printmijnpdf.nl
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls

# Prijzen (in eurocenten)
PRICE_PER_PAGE_A4=10
PRICE_PER_PAGE_A5=7
PRICE_STARTUP=1000
PRICE_BINDING=500
PRICE_SHIPPING=500
```

## Deployment

### Ploi / Forge / Server

1. Web root: `/public`
2. PHP 8.2+
3. Na deployment:
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan migrate --force
   ```

## API Endpoints

| Method | URL | Beschrijving |
|--------|-----|--------------|
| GET | `/` | Homepage |
| POST | `/api/calculate-price` | Prijs berekenen |
| POST | `/api/order` | Bestelling plaatsen |
| POST | `/webhook/mollie` | Mollie webhook |
| GET | `/bestelling/{nr}` | Bestellingspagina |
| POST | `/admin/orders/{nr}/ship` | Verzenden + Track & Trace |

## Emails

De applicatie verstuurt automatisch:
- **Bevestigingsmail** - Na succesvolle betaling
- **Verzendmail** - Met Track & Trace link (via admin endpoint)
- **Admin notificatie** - Bij elke nieuwe bestelling

## Licentie

Proprietary - NIVO Druk & Multimedia B.V.
