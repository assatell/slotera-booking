(function () {
    'use strict';
    if (!window.sltr_ajax || !window.sltr_ajax.url || !window.sltr_ajax.nonce) { return; }

    var startedAt = Date.now();
    var maxScroll = 0;
    var sent = false;
    var bookingStarted = false;
    var bookingCreated = false;
    var sessionAnalytics = window.sltr_ajax.visitor_analytics_session_enabled === true || window.sltr_ajax.visitor_analytics_session_enabled === 1 || window.sltr_ajax.visitor_analytics_session_enabled === '1';

    function storageKey() { return 'sltrVisitorSession'; }
    function sessionId() {
        if (!sessionAnalytics) { return ''; }
        try {
            var existing = window.sessionStorage.getItem(storageKey());
            if (existing) { return existing; }
            var id = 's_' + Math.random().toString(36).slice(2) + '_' + Date.now().toString(36);
            window.sessionStorage.setItem(storageKey(), id);
            return id;
        } catch (e) {
            return 's_' + Math.random().toString(36).slice(2) + '_' + Date.now().toString(36);
        }
    }
    function qs(name) {
        try { return new URL(window.location.href).searchParams.get(name) || ''; } catch (e) { return ''; }
    }
    function cleanPageUrl() {
        try { return window.location.origin + window.location.pathname; } catch (e) { return window.location.href.split(/[?#]/)[0]; }
    }
    function cleanReferrer() {
        if (!document.referrer) { return ''; }
        try {
            var ref = new URL(document.referrer);
            return ref.origin === window.location.origin ? ref.origin + ref.pathname : ref.origin + '/';
        } catch (e) { return ''; }
    }
    function detectPackageId() {
        var fromQuery = qs('sltr_package_id');
        if (fromQuery) { return fromQuery; }
        var input = document.querySelector('input[name="package_id"], select[name="package_id"], [data-sltr-package-id], [data-package-id]');
        if (!input) { return '0'; }
        return input.value || input.getAttribute('data-sltr-package-id') || input.getAttribute('data-package-id') || '0';
    }
    function detectPageType() {
        if (document.querySelector('.sltr-package-landing') || qs('sltr_package_id')) { return 'service'; }
        if (document.querySelector('.sltr-booking-form')) { return 'booking'; }
        if (document.querySelector('.sltr-categories-page')) { return 'category'; }
        return 'page';
    }
    function buildPayload() {
        var duration = Math.max(0, Math.round((Date.now() - startedAt) / 1000));
        return {
            action: 'sltr_track_visitor_event',
            nonce: window.sltr_ajax.nonce,
            session_id: sessionId(),
            page_url: cleanPageUrl(),
            page_title: document.title || '',
            page_type: detectPageType(),
            package_id: detectPackageId(),
            post_id: (window.sltr_ajax.current_post_id || 0),
            referrer: cleanReferrer(),
            utm_source: qs('utm_source'),
            utm_medium: qs('utm_medium'),
            utm_campaign: qs('utm_campaign'),
            duration_seconds: duration,
            viewport_events: maxScroll,
            booking_started: bookingStarted ? 1 : 0,
            booking_created: bookingCreated ? 1 : 0,
            final_event: 1
        };
    }
    function encode(data) {
        var params = new URLSearchParams();
        Object.keys(data).forEach(function (key) { params.append(key, data[key]); });
        return params;
    }
    function send() {
        if (sent) { return; }
        sent = true;
        var body = encode(buildPayload());
        if (navigator.sendBeacon) {
            try {
                var blob = new Blob([body.toString()], { type: 'application/x-www-form-urlencoded; charset=UTF-8' });
                if (navigator.sendBeacon(window.sltr_ajax.url, blob)) { return; }
            } catch (e) {}
        }
        fetch(window.sltr_ajax.url, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString(), keepalive: true
        }).catch(function () {});
    }

    document.addEventListener('scroll', function () {
        var doc = document.documentElement;
        var height = Math.max(1, doc.scrollHeight - window.innerHeight);
        maxScroll = Math.max(maxScroll, Math.round((window.scrollY / height) * 100));
    }, { passive: true });
    document.addEventListener('input', function (event) {
        if (event.target && event.target.closest && event.target.closest('.sltr-booking-form')) { bookingStarted = true; }
    }, true);
    document.addEventListener('click', function (event) {
        if (event.target && event.target.closest && event.target.closest('.sltr-booking-form, .sltr-package-landing-button')) { bookingStarted = true; }
    }, true);
    document.addEventListener('sltr:booking-created', function () { bookingCreated = true; });
    window.addEventListener('pagehide', send);
    document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'hidden') { send(); } });
}());
