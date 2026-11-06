{{--
    Trechter en definities: docs/analytics-meting.md. Nooit bestandsnamen,
    PDF-inhoud of klantgegevens meesturen. Alleen JavaScript in het script-blok,
    zonder Blade: tests/js/analytics-helper.test.mjs voert dit bestand uit.
--}}
    <script>
    window.PMP_Analytics = {
        state: {
            uploaded: false,
            uploadTime: null,
            finishing: null,     // 'booklet|double' enz., laatst gemeten keuze
            shipping: null,      // 'delivery' of 'pickup', laatst gemeten keuze
            checkoutStarted: false,
            paymentStarted: false,
            exitTracked: false,
            currentStage: 'landing'
        },

        track: function(eventName, params) {
            params = params || {};
            if (typeof gtag === 'function') {
                gtag('event', eventName, params);
            }
        },

        /** Trechterstap 2: de server heeft een nieuwe PDF geanalyseerd en de prijs staat in beeld. Eén keer per PDF. */
        trackUpload: function(pageCount, fileSizeMB, info) {
            info = info || {};
            var s = this.state;
            s.uploaded = true;
            s.uploadTime = Date.now();
            s.finishing = info.finishing || null;
            s.shipping = null;
            s.checkoutStarted = false;
            s.paymentStarted = false;
            s.exitTracked = false;
            s.currentStage = 'uploaded';
            this.track('pdf_upload', {
                page_count: pageCount,
                file_size_mb: Math.round(fileSizeMB * 10) / 10,
                pdf_format: info.format || '',
                booklet_possible: info.bookletPossible ? 'yes' : 'no'
            });
            this.track('view_item', {
                currency: 'EUR',
                value: Math.round((info.value || 0) * 100) / 100,
                items: [this.item(info.finishing, info.format, 1)]
            });
        },

        /** Upload of analyse mislukt. error_type: not_pdf, too_large, format_rejected, analysis_failed, server, network */
        trackUploadFailed: function(errorType) {
            this.track('pdf_upload_failed', { error_type: errorType });
        },

        /** Andere afwerking gekozen (geen trechterstap). Alleen bij een echte wijziging. */
        trackFinishing: function(bindingType, printSide, quantity) {
            var key = bindingType + '|' + printSide;
            if (!this.state.uploaded || this.state.finishing === key) return;
            this.state.finishing = key;
            this.track('select_finishing', {
                finishing_type: bindingType,
                print_side: printSide,
                quantity: quantity
            });
        },

        /**
         * Trechterstap 3: verzenden of afhalen gekozen. De eerste keuze en elke echte
         * wissel per PDF. Mag vóór of na het invullen van de klantgegevens komen.
         */
        trackShipping: function(method, value) {
            if (!this.state.uploaded || this.state.shipping === method) return;
            this.state.shipping = method;
            this.state.currentStage = 'shipping_selected';
            this.track('add_shipping_info', {
                currency: 'EUR',
                value: Math.round(value * 100) / 100,
                shipping_tier: method
            });
        },

        /**
         * Eerste invoer in de klantgegevens. Eén keer per PDF. Géén stap in de gesloten
         * trechter: de klant mag dit vóór of na de bezorgkeuze doen.
         */
        trackBeginCheckout: function(value) {
            if (!this.state.uploaded || this.state.checkoutStarted) return;
            this.state.checkoutStarted = true;
            this.state.currentStage = 'checkout';
            this.track('begin_checkout', {
                currency: 'EUR',
                value: Math.round(value * 100) / 100
            });
        },

        /**
         * Trechterstap 4: de server heeft de order aangemaakt en stuurt de klant naar
         * iDEAL (Mollie). Bestellen kan alleen met een bezorgkeuze; is die voor deze PDF
         * nog niet gemeten (bv. gekozen vóór een nieuwe upload), dan eerst alsnog
         * add_shipping_info, zodat stap 3 altijd vóór stap 4 staat.
         */
        trackPaymentStart: function(value, shippingTier) {
            if (this.state.uploaded && this.state.shipping !== shippingTier) {
                this.trackShipping(shippingTier, value);
            }
            this.state.paymentStarted = true;
            this.state.currentStage = 'payment';
            this.track('add_payment_info', {
                currency: 'EUR',
                value: Math.round(value * 100) / 100,
                payment_type: 'ideal',
                shipping_tier: shippingTier
            });
        },

        /** Bestellen lukte niet. error_type: validation, server, network */
        trackPaymentStartFailed: function(errorType) {
            this.track('payment_start_failed', { error_type: errorType });
        },

        /** Bezoeker verlaat de pagina met een geanalyseerde PDF, zonder naar de betaling te gaan. */
        trackExit: function() {
            var s = this.state;
            if (!s.uploaded || s.paymentStarted || s.exitTracked) return;
            s.exitTracked = true;
            this.track('checkout_exit', {
                checkout_stage: s.currentStage,
                seconds_since_upload: s.uploadTime ? Math.round((Date.now() - s.uploadTime) / 1000) : 0
            });
        },

        item: function(finishing, format, quantity) {
            var booklet = String(finishing || '').indexOf('booklet') === 0;
            return {
                item_id: booklet ? 'booklet' : 'loose',
                item_name: booklet ? 'Geniet boekje' : 'Losse pagina\'s',
                item_variant: format || '',
                quantity: quantity || 1
            };
        }
    };

    // pagehide werkt ook op mobiel, beforeunload niet betrouwbaar
    window.addEventListener('pagehide', function() {
        PMP_Analytics.trackExit();
    });
    </script>
