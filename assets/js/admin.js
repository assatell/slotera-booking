(function () {
    'use strict';

    var longActionPattern = /(rescan|export|generate|send test|test email|queue|sync|rebuild|process|run audit|run scan)/i;

    function buttonText(button) {
        if (!button) return '';
        return String(button.value || button.textContent || '').replace(/\s+/g, ' ').trim();
    }

    function markProcessing(button) {
        if (!button || button.dataset.sltrProcessing === '1') return;
        if (button.dataset.sltrNoProcessing === '1' || button.classList.contains('sltr-generate-slug')) return;
        var text = buttonText(button);
        if (!longActionPattern.test(text) && button.dataset.sltrLongAction !== '1') return;

        button.dataset.sltrProcessing = '1';
        button.dataset.sltrOriginalText = text;
        button.classList.add('sltr-is-processing');
        button.setAttribute('aria-busy', 'true');
        button.disabled = true;

        if (button.tagName === 'INPUT') {
            button.value = (window.sltr_ajax && window.sltr_ajax.ux && window.sltr_ajax.ux.processing) || button.dataset.sltrProcessingText || '…';
        } else {
            button.textContent = (window.sltr_ajax && window.sltr_ajax.ux && window.sltr_ajax.ux.processing) || button.dataset.sltrProcessingText || '…';
        }
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        var submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
        markProcessing(submitter);
    });

    document.addEventListener('click', function (event) {
        var button = event.target.closest('a.button, button.button');
        if (!button || button.tagName === 'BUTTON' && button.type === 'submit') return;
        if (button.hasAttribute('disabled') || button.getAttribute('aria-disabled') === 'true') return;
        markProcessing(button);
    });
}());


(function () {
    'use strict';

    function wrapResponsiveTables() {
        document.querySelectorAll('.sltr-email-preview--themed').forEach(function (preview) {
            preview.style.setProperty('--sltr-email-form-bg', preview.dataset.formBg || '#fff');
            var card = preview.querySelector('.sltr-email-preview__card');
            var header = preview.querySelector('.sltr-email-preview__header');
            var body = preview.querySelector('.sltr-email-preview__body');
            var footer = preview.querySelector('.sltr-email-preview__footer');
            if (card) { card.style.setProperty('--sltr-email-card-bg', card.dataset.cardBg || '#fff'); card.style.setProperty('--sltr-email-card-border', card.dataset.cardBorder || '#dbe3ef'); }
            if (header) { header.style.setProperty('--sltr-email-primary', header.dataset.primary || '#2563eb'); header.style.setProperty('--sltr-email-primary-text', header.dataset.primaryText || '#fff'); }
            if (body) body.style.setProperty('--sltr-email-text', body.dataset.text || '#0f172a');
            if (footer) { footer.style.setProperty('--sltr-email-footer-bg', footer.dataset.footerBg || '#fff'); footer.style.setProperty('--sltr-email-muted', footer.dataset.muted || '#64748b'); }
        });
        document.querySelectorAll('.wrap[class*="sltr-"] p.submit, .sltr-admin p.submit, .sltr-admin-wrap p.submit, .sltr-admin-page p.submit').forEach(function (actions) {
            actions.classList.add('sltr-form-actions');
        });
        document.querySelectorAll('.wrap[class*="sltr-"] table.widefat, .sltr-admin table.widefat, .sltr-admin-wrap table.widefat, .sltr-admin-page table.widefat').forEach(function (table) {
            if (table.closest('.sltr-responsive-table-wrapper, .sltr-table-wrap')) return;
            table.classList.add('sltr-responsive-table');
            var wrapper = document.createElement('div');
            wrapper.className = 'sltr-responsive-table-wrapper';
            wrapper.tabIndex = 0;
            wrapper.setAttribute('role', 'region');
            wrapper.setAttribute('aria-label', table.getAttribute('aria-label') || ((window.sltr_ajax && window.sltr_ajax.ux && window.sltr_ajax.ux.table_label) || ''));
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        });
    }

    var pendingAction = null;
    var modal = null;

    function uxText(key, fallback) {
        return (window.sltr_ajax && window.sltr_ajax.ux && window.sltr_ajax.ux[key]) || fallback || '';
    }

    function ensureModal() {
        if (modal) return modal;
        modal = document.createElement('div');
        modal.className = 'sltr-confirm-modal';
        modal.hidden = true;
        modal.innerHTML = '<div class="sltr-confirm-modal__backdrop" data-sltr-confirm-backdrop></div>' +
            '<div class="sltr-confirm-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="sltr-confirm-title" aria-describedby="sltr-confirm-message">' +
            '<div class="sltr-confirm-modal__header"><h2 id="sltr-confirm-title"></h2></div>' +
            '<div class="sltr-confirm-modal__body"><p id="sltr-confirm-message"></p><label class="sltr-confirm-modal__check" hidden><input type="checkbox" data-sltr-confirm-check> <span data-sltr-confirm-check-label></span></label></div>' +
            '<div class="sltr-confirm-modal__actions"><button type="button" class="button sltr-confirm-cancel" data-sltr-confirm-cancel></button><button type="button" class="button button-primary" data-sltr-confirm-accept></button></div>' +
            '</div>';
        document.body.appendChild(modal);
        modal.querySelector('[data-sltr-confirm-cancel]').textContent = uxText('cancel_button', 'Cancel');
        modal.querySelector('[data-sltr-confirm-accept]').textContent = uxText('confirm_button', 'Confirm');
        modal.addEventListener('click', function (event) {
            if (event.target.closest('[data-sltr-confirm-cancel], [data-sltr-confirm-backdrop]')) closeModal(false);
            if (event.target.closest('[data-sltr-confirm-accept]')) closeModal(true);
        });
        document.addEventListener('keydown', function (event) {
            if (!modal.hidden && event.key === 'Escape') closeModal(false);
        });
        return modal;
    }

    function openModal(options, action) {
        var current = ensureModal();
        pendingAction = action;
        current.querySelector('#sltr-confirm-title').textContent = options.title || uxText('confirm_title');
        current.querySelector('#sltr-confirm-message').textContent = options.message || uxText('confirm_message');
        var accept = current.querySelector('[data-sltr-confirm-accept]');
        accept.textContent = options.confirm || uxText('confirm_button');
        accept.className = 'button ' + (options.danger ? 'button-link-delete sltr-confirm-danger' : 'button-primary');
        var checkWrap = current.querySelector('.sltr-confirm-modal__check');
        var check = current.querySelector('[data-sltr-confirm-check]');
        var checkLabel = current.querySelector('[data-sltr-confirm-check-label]');
        var requireCheck = options.requireCheck === true;
        checkWrap.hidden = !requireCheck;
        check.checked = false;
        checkLabel.textContent = options.checkLabel || uxText('confirm_check_label', 'I understand the consequences of this action.');
        accept.disabled = requireCheck;
        check.onchange = function () { accept.disabled = requireCheck && !check.checked; };
        current.hidden = false;
        document.body.classList.add('sltr-modal-open');
        requestAnimationFrame(function () {
            var cancel = current.querySelector('button[data-sltr-confirm-cancel]');
            if (cancel) cancel.focus();
        });
    }

    function closeModal(accepted) {
        if (!modal) return;
        modal.hidden = true;
        document.body.classList.remove('sltr-modal-open');
        var action = pendingAction;
        pendingAction = null;
        if (accepted && typeof action === 'function') action();
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-sltr-confirm]');
        if (!trigger || trigger.tagName === 'FORM') return;
        if (trigger.dataset.sltrConfirmed === '1') { delete trigger.dataset.sltrConfirmed; return; }
        event.preventDefault();
        openModal({
            title: trigger.dataset.sltrConfirmTitle || uxText('confirm_title'),
            message: trigger.dataset.sltrConfirm || uxText('confirm_message'),
            confirm: trigger.dataset.sltrConfirmButton || uxText('confirm_button'),
            danger: trigger.dataset.sltrConfirmDanger !== '0',
            requireCheck: trigger.dataset.sltrConfirmRequireCheck === '1',
            checkLabel: trigger.dataset.sltrConfirmCheckLabel || ''
        }, function () {
            if (trigger.tagName === 'A' && trigger.href) window.location.href = trigger.href;
            else { trigger.dataset.sltrConfirmed = '1'; trigger.click(); }
        });
    });

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.sltrConfirm || form.dataset.sltrConfirmed === '1') return;
        event.preventDefault();
        openModal({
            title: form.dataset.sltrConfirmTitle || uxText('confirm_title'),
            message: form.dataset.sltrConfirm,
            confirm: form.dataset.sltrConfirmButton || uxText('confirm_button'),
            danger: form.dataset.sltrConfirmDanger !== '0',
            requireCheck: form.dataset.sltrConfirmRequireCheck === '1',
            checkLabel: form.dataset.sltrConfirmCheckLabel || ''
        }, function () {
            form.dataset.sltrConfirmed = '1';
            form.requestSubmit(event.submitter || undefined);
        });
    }, true);

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', wrapResponsiveTables);
    else wrapResponsiveTables();
}());
