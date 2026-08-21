(function () {
    'use strict';

    function getSiteKey() {
        return window.sltr_recaptcha_v3 && String(window.sltr_recaptcha_v3.site_key || '').trim();
    }

    function execute(action) {
        var siteKey = getSiteKey();
        if (!siteKey || !window.grecaptcha || typeof window.grecaptcha.ready !== 'function') {
            return Promise.reject(new Error('recaptcha_unavailable'));
        }

        return new Promise(function (resolve, reject) {
            window.grecaptcha.ready(function () {
                window.grecaptcha.execute(siteKey, { action: action }).then(resolve).catch(reject);
            });
        });
    }

    window.sltrGetRecaptchaV3Token = execute;

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || !form.matches || !form.matches('form[data-sltr-recaptcha-v3-action]')) {
            return;
        }
        if (form.getAttribute('data-sltr-recaptcha-v3-ready') === '1') {
            form.removeAttribute('data-sltr-recaptcha-v3-ready');
            return;
        }

        event.preventDefault();
        var action = form.getAttribute('data-sltr-recaptcha-v3-action') || 'slotera_contact';
        execute(action).then(function (token) {
            var input = form.querySelector('[data-sltr-recaptcha-v3-token]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'g_recaptcha_response';
                input.setAttribute('data-sltr-recaptcha-v3-token', '');
                form.appendChild(input);
            }
            input.value = token || '';
            form.setAttribute('data-sltr-recaptcha-v3-ready', '1');
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        }).catch(function () {
            form.setAttribute('data-sltr-recaptcha-v3-ready', '1');
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    }, true);
}());
