(function () {
    function sltrT(text) {
        if (window.sltr_ajax && window.sltr_ajax.i18n && window.sltr_ajax.i18n[text]) {
            return window.sltr_ajax.i18n[text];
        }
        return text;
    }
    'use strict';

    const themePalettes = {
        light: {
            form_background_color: '#ffffff',
            form_text_color: '#0f172a',
            card_background_color: '#ffffff',
            card_border_color: '#dbe3ef',
            primary_color: '#2563eb',
            primary_text_color: '#ffffff',
            muted_text_color: '#64748b',
            price_old_color: '#94a3b8',
            price_new_color: '#0f172a',
            discount_badge_background_color: '#dc2626',
            discount_badge_text_color: '#ffffff',
            tooltip_icon_color: '#2563eb',
            tooltip_background_color: '#0f172a',
            tooltip_text_color: '#ffffff',
            calendar_background_color: '#f8fafc',
            calendar_text_color: '#0f172a',
            calendar_border_color: '#dbe3ef',
            calendar_day_background_color: '#ffffff',
            calendar_disabled_background_color: '#f1f5f9',
            calendar_disabled_text_color: '#94a3b8'
        },
        dark: {
            form_background_color: '#0f172a',
            form_text_color: '#ffffff',
            card_background_color: '#111827',
            card_border_color: '#334155',
            primary_color: '#334155',
            primary_text_color: '#ffffff',
            muted_text_color: '#cbd5e1',
            price_old_color: '#EF4444',
            price_new_color: '#61CE4B',
            discount_badge_background_color: '#ef4444',
            discount_badge_text_color: '#ffffff',
            tooltip_icon_color: '#ffffff',
            tooltip_background_color: '#334155',
            tooltip_text_color: '#ffffff',
            calendar_background_color: '#111827',
            calendar_text_color: '#ffffff',
            calendar_border_color: '#334155',
            calendar_day_background_color: '#0f172a',
            calendar_disabled_background_color: '#1e293b',
            calendar_disabled_text_color: '#BCC9DC'
        },
        soft: {
            form_background_color: '#fff7ed',
            form_text_color: '#431407',
            card_background_color: '#ffffff',
            card_border_color: '#fed7aa',
            primary_color: '#f97316',
            primary_text_color: '#ffffff',
            muted_text_color: '#9a3412',
            price_old_color: '#c2410c',
            price_new_color: '#7c2d12',
            discount_badge_background_color: '#dc2626',
            discount_badge_text_color: '#ffffff',
            tooltip_icon_color: '#f97316',
            tooltip_background_color: '#7c2d12',
            tooltip_text_color: '#fff7ed',
            calendar_background_color: '#ffedd5',
            calendar_text_color: '#431407',
            calendar_border_color: '#fed7aa',
            calendar_day_background_color: '#fff7ed',
            calendar_disabled_background_color: '#fed7aa',
            calendar_disabled_text_color: '#9a3412'
        },
        minimal: {
            form_background_color: '#ffffff',
            form_text_color: '#111827',
            card_background_color: '#ffffff',
            card_border_color: '#111827',
            primary_color: '#111827',
            primary_text_color: '#ffffff',
            muted_text_color: '#4b5563',
            price_old_color: '#6b7280',
            price_new_color: '#111827',
            discount_badge_background_color: '#111827',
            discount_badge_text_color: '#ffffff',
            tooltip_icon_color: '#111827',
            tooltip_background_color: '#111827',
            tooltip_text_color: '#ffffff',
            calendar_background_color: '#ffffff',
            calendar_text_color: '#111827',
            calendar_border_color: '#111827',
            calendar_day_background_color: '#ffffff',
            calendar_disabled_background_color: '#f3f4f6',
            calendar_disabled_text_color: '#6b7280'
        }
    };

    const cssVars = {
        form_background_color: '--sltr-form-bg',
        form_text_color: '--sltr-form-text',
        card_background_color: '--sltr-card-bg',
        card_border_color: '--sltr-card-border',
        primary_color: '--sltr-primary',
        primary_text_color: '--sltr-primary-text',
        muted_text_color: '--sltr-muted',
        price_old_color: '--sltr-price-old',
        price_new_color: '--sltr-price-new',
        discount_badge_background_color: '--sltr-discount-bg',
        discount_badge_text_color: '--sltr-discount-text',
        tooltip_icon_color: '--sltr-tooltip-icon',
        tooltip_background_color: '--sltr-tooltip-bg',
        tooltip_text_color: '--sltr-tooltip-text',
        calendar_background_color: '--sltr-calendar-bg',
        calendar_text_color: '--sltr-calendar-text',
        calendar_border_color: '--sltr-calendar-border',
        calendar_day_background_color: '--sltr-calendar-day-bg',
        calendar_disabled_background_color: '--sltr-calendar-disabled-bg',
        calendar_disabled_text_color: '--sltr-calendar-disabled-text'
    };

    function getSettingsRoot() {
        return document.querySelector('.sltr-appearance-preview');
    }

    function applyPalette(palette) {
        const root = getSettingsRoot();
        if (!root || !palette) {
            return;
        }

        Object.keys(palette).forEach(function (key) {
            if (cssVars[key]) {
                root.style.setProperty(cssVars[key], palette[key]);
            }
        });
    }

    function syncInputsFromPalette(palette) {
        if (!palette) {
            return;
        }

        document.querySelectorAll('.sltr-color-input').forEach(function (input) {
            const key = input.getAttribute('data-setting');
            if (palette[key]) {
                input.value = palette[key];
            }
        });
    }

    function applyCustomInputs() {
        const palette = {};
        document.querySelectorAll('.sltr-color-input').forEach(function (input) {
            const key = input.getAttribute('data-setting');
            if (key && input.value) {
                palette[key] = input.value;
            }
        });
        applyPalette(palette);
    }

    function setThemeClass(theme) {
        const root = getSettingsRoot();
        if (!root) {
            return;
        }

        ['light', 'dark', 'soft', 'minimal', 'custom'].forEach(function (name) {
            root.classList.remove('sltr-theme-' + name);
        });
        root.classList.add('sltr-theme-' + theme);
    }

    function updateTooltipSizing() {
        const ratioInput = document.querySelector('.sltr-tooltip-size-ratio-input');
        const textSizeInput = document.querySelector('.sltr-tooltip-text-size-input');
        const root = getSettingsRoot();

        if (!root) {
            return;
        }

        if (ratioInput) {
            root.style.setProperty('--sltr-tooltip-size-ratio', ratioInput.value || '1.15');
        }
        if (textSizeInput) {
            root.style.setProperty('--sltr-tooltip-text-size', (textSizeInput.value || '13') + 'px');
        }
    }

    function updateOldPriceStyle() {
        const styleInput = document.querySelector('.sltr-price-old-style-input');
        const ratioInput = document.querySelector('.sltr-price-old-size-ratio-input');
        const root = getSettingsRoot();

        if (!root) {
            return;
        }

        if (styleInput) {
            root.style.setProperty('--sltr-price-old-decoration', styleInput.value || 'line-through');
        }
        if (ratioInput) {
            root.style.setProperty('--sltr-price-old-size', (ratioInput.value || '0.85') + 'em');
        }
    }

    function initAppearancePreview() {
        const select = document.querySelector('.sltr-appearance-theme-select');
        if (!select) {
            return;
        }

        select.addEventListener('change', function () {
            const theme = select.value || 'light';
            setThemeClass(theme);

            if (theme === 'custom') {
                applyCustomInputs();
            } else {
                syncInputsFromPalette(themePalettes[theme]);
                applyPalette(themePalettes[theme]);
            }

            updateOldPriceStyle();
            updateTooltipSizing();
        });

        document.querySelectorAll('.sltr-color-input').forEach(function (input) {
            input.addEventListener('input', function () {
                select.value = 'custom';
                setThemeClass('custom');
                applyCustomInputs();
                updateOldPriceStyle();
            updateTooltipSizing();
            });
        });

        document.querySelectorAll('.sltr-price-old-style-input, .sltr-price-old-size-ratio-input, .sltr-tooltip-size-ratio-input, .sltr-tooltip-text-size-input').forEach(function (input) {
            input.addEventListener('input', function () { updateOldPriceStyle(); updateTooltipSizing(); });
            input.addEventListener('change', function () { updateOldPriceStyle(); updateTooltipSizing(); });
        });

        if (select.value === 'custom') {
            applyCustomInputs();
        } else {
            applyPalette(themePalettes[select.value] || themePalettes.light);
        }
        updateOldPriceStyle();
            updateTooltipSizing();
    }

    function initSaveAnchorMemory() {
        document.querySelectorAll('.sltr-settings-section').forEach(function (section) {
            const form = section.querySelector('form');
            if (!form || !section.id) {
                return;
            }

            let input = form.querySelector('input[name="return_to"]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'return_to';
                form.appendChild(input);
            }
            input.value = section.id;
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initAppearancePreview();
        initSaveAnchorMemory();
    });
})();

/* v1.0.52 package right-column builder and multi-image media fields */
