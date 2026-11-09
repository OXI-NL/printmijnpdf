{{--
    Herkomst van de bestelling (eerste aanraking per tabblad). Draait op elke
    instappagina: homepage, landingspagina's en juridische pagina's, niet op de
    bevestigingspagina (daar komt de klant terug van Mollie).

    - Het eerste bezoek in een tabblad legt instappad, externe verwijzer en
      campagnegegevens (UTM, klik-ID's) vast. Latere pagina's overschrijven niets,
      dus landingspagina → homepage houdt de landingspagina en de bron.
    - Geen standaardwaarden: ontbreekt een bron, dan blijft hij leeg. 'direct'
      wordt nooit ingevuld; de server en het beheer noemen dat "onbekend".
    - Alleen sessionStorage, geen verzoeken naar buiten, niets naar GA4.
      Instappad zonder querystring.

    Alleen JavaScript in het script-blok, zonder Blade:
    tests/js/attribution.test.mjs voert dit bestand uit.
--}}
    <script>
    window.PMP_Attribution = {
        // Zelfde sleutelnamen als de vorige versie, zodat een tabblad dat al open stond zijn gegevens houdt
        CAMPAIGN_KEYS: ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid', 'msclkid'],
        LANDING_KEY: 'pmp_landing_page',
        REFERRER_KEY: 'pmp_referrer',

        storage: function() {
            try { return window.sessionStorage || null; } catch (e) { return null; }
        },

        read: function(key) {
            var s = this.storage();
            try { return s ? (s.getItem(key) || '') : ''; } catch (e) { return ''; }
        },

        write: function(key, value) {
            var s = this.storage();
            try { if (s) s.setItem(key, value); } catch (e) {}
        },

        init: function() {
            // Al vastgelegd in dit tabblad: eerste instap blijft staan
            if (this.read(this.LANDING_KEY)) return;

            var params;
            try { params = new URLSearchParams(window.location.search); } catch (e) { params = null; }
            var self = this;
            if (params) {
                this.CAMPAIGN_KEYS.forEach(function(key) {
                    var val = params.get(key);
                    if (val) self.write(key, val);
                });
            }

            if (document.referrer) {
                try {
                    if (new URL(document.referrer).hostname !== window.location.hostname) {
                        this.write(this.REFERRER_KEY, document.referrer);
                    }
                } catch (e) {}
            }

            // Als laatste: dit is de markering dat de instap is vastgelegd
            this.write(this.LANDING_KEY, window.location.pathname || '/');
        },

        getData: function() {
            var data = {};
            var self = this;
            this.CAMPAIGN_KEYS.forEach(function(key) { data[key] = self.read(key); });
            data.landing_page = this.read(this.LANDING_KEY);
            data.referrer = this.read(this.REFERRER_KEY);
            return data;
        },

        appendToForm: function(formData) {
            var data = this.getData();
            Object.keys(data).forEach(function(key) {
                if (data[key]) formData.append('attribution_' + key, data[key]);
            });
            return formData;
        }
    };
    PMP_Attribution.init();
    </script>
