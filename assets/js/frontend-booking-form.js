(function () {
    function sltrNormalizeLocale(value) {
        const raw = value ? String(value).replace('-', '_') : '';
        if (!raw) return '';
        const lower = raw.toLowerCase();
        const aliases = {
            es: 'es_ES',
            es_es: 'es_ES',
            it: 'it_IT',
            it_it: 'it_IT',
            fr: 'fr_FR',
            fr_fr: 'fr_FR',
            et: 'et',
            lv: 'lv',
            lt: 'lt_LT',
            lt_lt: 'lt_LT',
            ru: 'ru_RU',
            ru_ru: 'ru_RU',
            bg: 'bg_BG',
            bg_bg: 'bg_BG',
            mt: 'mt_MT',
            mt_mt: 'mt_MT',
            no: 'no_NO',
            no_no: 'no_NO',
            nb: 'no_NO',
            nb_no: 'no_NO',
            is: 'is_IS',
            is_is: 'is_IS',
            ga: 'ga_IE',
            ga_ie: 'ga_IE',
        };
        return aliases[lower] || raw;
    }

    function sltrActiveFrontendLocale() {
        const configured = window.sltr_ajax && (window.sltr_ajax.frontend_locale || window.sltr_ajax.frontend_locale_js) ? (window.sltr_ajax.frontend_locale || window.sltr_ajax.frontend_locale_js) : '';
        const htmlLang = document.documentElement ? document.documentElement.getAttribute('lang') : '';
        const detected = configured || (window.sltr_ajax && window.sltr_ajax.locale) || htmlLang || 'en_US';
        return sltrNormalizeLocale(detected);
    }

    function sltrT(text) {
        const locale = sltrActiveFrontendLocale();
        if (window.sltr_ajax && window.sltr_ajax.i18n_locales && window.sltr_ajax.i18n_locales[locale] && window.sltr_ajax.i18n_locales[locale][text]) {
            return window.sltr_ajax.i18n_locales[locale][text];
        }
        if (window.sltr_ajax && window.sltr_ajax.i18n && window.sltr_ajax.i18n[text]) {
            const configuredLocale = sltrNormalizeLocale(window.sltr_ajax.frontend_locale || window.sltr_ajax.frontend_locale_js || '');
            if (!configuredLocale || configuredLocale === locale) {
                return window.sltr_ajax.i18n[text];
            }
        }
        return text;
    }

    function sltrFormat(text) {
        const args = Array.prototype.slice.call(arguments, 1);
        let index = 0;
        return sltrT(text).replace(/%(?:(\d+)\$)?[sd]/g, function (match, position) {
            const argIndex = position ? (parseInt(position, 10) - 1) : index++;
            const value = args[argIndex];
            return value === undefined || value === null ? '' : String(value);
        });
    }
    let selectedPackage = null;
    let selectedPackageMeta = null;
    let selectedSlot = null;
    let selectedUnit = null;
    let selectedExtras = [];
    let simpleQuote = null;
    let dateRangeData = null;
    let calendarDate = new Date();
    let availableDates = new Set();
    let datesLoading = false;
    let isSubmitting = false;
    let appliedCouponCode = '';
    let appliedCouponFinalAmount = '';


    function frontendLocale() {
        return sltrActiveFrontendLocale().replace('_', '-');
    }

    const sltrCalendarLocales = {
        bg_BG: {
            monthsLong: ['януари', 'февруари', 'март', 'април', 'май', 'юни', 'юли', 'август', 'септември', 'октомври', 'ноември', 'декември'],
            monthsShort: ['ян.', 'фев.', 'март', 'апр.', 'май', 'юни', 'юли', 'авг.', 'септ.', 'окт.', 'ноем.', 'дек.'],
            weekdaysLong: ['неделя', 'понеделник', 'вторник', 'сряда', 'четвъртък', 'петък', 'събота'],
            weekdaysShort: ['нед', 'пон', 'вто', 'сря', 'чет', 'пет', 'съб']
        },
        mt_MT: {
            monthsLong: ['Jannar', 'Frar', 'Marzu', 'April', 'Mejju', 'Ġunju', 'Lulju', 'Awwissu', 'Settembru', 'Ottubru', 'Novembru', 'Diċembru'],
            monthsShort: ['Jan', 'Fra', 'Mar', 'Apr', 'Mej', 'Ġun', 'Lul', 'Aww', 'Set', 'Ott', 'Nov', 'Diċ'],
            weekdaysLong: ['Il-Ħadd', 'It-Tnejn', 'It-Tlieta', 'L-Erbgħa', 'Il-Ħamis', 'Il-Ġimgħa', 'Is-Sibt'],
            weekdaysShort: ['Ħad', 'Tne', 'Tli', 'Erb', 'Ħam', 'Ġim', 'Sib']
        },
        no_NO: {
            monthsLong: ['januar', 'februar', 'mars', 'april', 'mai', 'juni', 'juli', 'august', 'september', 'oktober', 'november', 'desember'],
            monthsShort: ['jan.', 'feb.', 'mars', 'apr.', 'mai', 'juni', 'juli', 'aug.', 'sep.', 'okt.', 'nov.', 'des.'],
            weekdaysLong: ['søndag', 'mandag', 'tirsdag', 'onsdag', 'torsdag', 'fredag', 'lørdag'],
            weekdaysShort: ['søn.', 'man.', 'tir.', 'ons.', 'tor.', 'fre.', 'lør.']
        },
        is_IS: {
            monthsLong: ['janúar', 'febrúar', 'mars', 'apríl', 'maí', 'júní', 'júlí', 'ágúst', 'september', 'október', 'nóvember', 'desember'],
            monthsShort: ['jan.', 'feb.', 'mar.', 'apr.', 'maí', 'jún.', 'júl.', 'ágú.', 'sep.', 'okt.', 'nóv.', 'des.'],
            weekdaysLong: ['sunnudagur', 'mánudagur', 'þriðjudagur', 'miðvikudagur', 'fimmtudagur', 'föstudagur', 'laugardagur'],
            weekdaysShort: ['sun.', 'mán.', 'þri.', 'mið.', 'fim.', 'fös.', 'lau.']
        },
        ga_IE: {
            monthsLong: ['Eanáir', 'Feabhra', 'Márta', 'Aibreán', 'Bealtaine', 'Meitheamh', 'Iúil', 'Lúnasa', 'Meán Fómhair', 'Deireadh Fómhair', 'Samhain', 'Nollaig'],
            monthsShort: ['Ean', 'Feabh', 'Már', 'Aib', 'Beal', 'Meith', 'Iúil', 'Lún', 'MFómh', 'DFómh', 'Samh', 'Noll'],
            weekdaysLong: ['Dé Domhnaigh', 'Dé Luain', 'Dé Máirt', 'Dé Céadaoin', 'Déardaoin', 'Dé hAoine', 'Dé Sathairn'],
            weekdaysShort: ['Domh', 'Luan', 'Máirt', 'Céad', 'Déar', 'Aoine', 'Sath']
        },
    };

    function localizedDate(date, options) {
        const locale = sltrActiveFrontendLocale();
        const manual = sltrCalendarLocales[locale];
        if (manual && options) {
            const parts = [];
            if (options.weekday) {
                parts.push(options.weekday === 'long' ? manual.weekdaysLong[date.getDay()] : manual.weekdaysShort[date.getDay()]);
            }
            if (options.day) {
                parts.push(String(date.getDate()));
            }
            if (options.month) {
                let monthText = options.month === 'long' ? manual.monthsLong[date.getMonth()] : manual.monthsShort[date.getMonth()];
                if (!options.weekday && !options.day && options.year) {
                    monthText = monthText.charAt(0).toUpperCase() + monthText.slice(1);
                }
                parts.push(monthText);
            }
            if (options.year) {
                parts.push(String(date.getFullYear()));
            }
            if (parts.length) {
                return parts.join(' ');
            }
        }
        try {
            return date.toLocaleDateString(frontendLocale(), options);
        } catch (e) {
            return date.toLocaleDateString(undefined, options);
        }
    }

    function updateCalendarWeekdays() {
        const wrap = document.querySelector('#sltr-calendar .sltr-calendar-weekdays');
        if (!wrap) return;
        const baseMonday = new Date(2024, 0, 1); // Monday
        wrap.querySelectorAll('span').forEach(function (span, index) {
            const day = new Date(baseMonday);
            day.setDate(baseMonday.getDate() + index);
            span.textContent = localizedDate(day, { weekday: 'short' });
        });
    }

    function pad(value) {
        return String(value).padStart(2, '0');
    }

    function formatDate(date) {
        return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
    }

    function formatHumanDate(value) {
        if (!value) return '—';
        const parts = value.split('-');
        if (parts.length !== 3) return value;
        const date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
        return localizedDate(date, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
    }

    function formatTime(value) {
        return value ? String(value).slice(0, 5) : '';
    }

    function eventDateLabel(event) {
        if (!event) return '—';
        const startDate = event.start_date || '';
        const endDate = event.end_date || '';
        const startTime = formatTime(event.start_time || '');
        const endTime = formatTime(event.end_time || '');
        if (startDate && startDate === endDate) {
            const dateLabel = formatHumanDate(startDate);
            if (startTime && endTime) return dateLabel + ', ' + startTime + '–' + endTime;
            if (startTime) return dateLabel + ', ' + startTime;
            return dateLabel;
        }
        const start = formatHumanDate(startDate) + (startTime ? ' ' + startTime : '');
        const end = formatHumanDate(endDate) + (endTime ? ' ' + endTime : '');
        return start + ' → ' + end;
    }

    function getMessage(id) {
        return document.getElementById(id);
    }

    function applyMessageState(el, text, type) {
        if (!el) return;
        el.textContent = text || '';
        el.classList.remove('is-error', 'is-success', 'is-info');
        if (type) el.classList.add('is-' + type);
        el.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');
        if (type === 'error') {
            el.setAttribute('role', 'alert');
        } else {
            el.removeAttribute('role');
        }
    }

    function setMessage(id, text, type) {
        const el = getMessage(id);
        if (!el) return;
        applyMessageState(el, text, type);

        if (id !== 'sltr-coupon-message' && id !== 'sltr-global-message') {
            applyMessageState(getMessage('sltr-global-message'), text, type);
        }
    }

    function setInlineAvailability(text, type) {
        const el = document.getElementById('sltr-inline-availability');
        if (el) {
            el.textContent = text || '';
            el.classList.remove('is-error', 'is-success', 'is-info');
            if (type) el.classList.add('is-' + type);
            el.style.display = 'none';
        }
        applyMessageState(getMessage('sltr-global-message'), text, type);
    }

    function focusFirstInvalidField() {
        const first = document.querySelector('#sltr-booking [data-field-key][data-required="1"]:not([disabled])');
        const invalid = Array.from(document.querySelectorAll('#sltr-booking [data-field-key][data-required="1"]:not([disabled])')).find(function(field){
            return !String(field.value || '').trim();
        });
        const target = invalid || first;
        if (target) {
            target.classList.add('sltr-field-invalid');
            target.focus({ preventScroll: false });
        }
    }

    function setButtonBusy(button, busy, busyText) {
        if (!button) return;
        if (busy) {
            button.disabled = true;
            button.dataset.originalText = button.textContent;
            button.textContent = busyText || sltrT('Please wait...');
            button.classList.add('is-loading');
            return;
        }
        button.disabled = false;
        button.textContent = button.dataset.originalText || button.textContent;
        button.classList.remove('is-loading');
    }

    function showStep(step) {
        const root = document.getElementById('sltr-booking');
        if (root) root.setAttribute('data-current-step', String(step));

        if (Number(step) === 4) {
            applyMessageState(getMessage('sltr-date-message'), '', 'info');
            applyMessageState(getMessage('sltr-slot-message'), '', 'info');
            applyMessageState(getMessage('sltr-global-message'), '', 'info');
            const inlineAvailability = document.getElementById('sltr-inline-availability');
            if (inlineAvailability) {
                inlineAvailability.textContent = '';
                inlineAvailability.classList.remove('is-error', 'is-success', 'is-info');
            }
        }
        document.querySelectorAll('#sltr-booking .sltr-step').forEach(function (el) {
            const active = el.getAttribute('data-step') === String(step);
            el.style.display = active ? 'block' : 'none';
            el.classList.toggle('is-current', active);
        });

        document.querySelectorAll('#sltr-booking [data-progress]').forEach(function (el) {
            el.classList.toggle('is-active', Number(el.getAttribute('data-progress')) <= Number(step));
        });

        const current = document.querySelector('#sltr-booking .sltr-step.is-current');
        if (current) {
            current.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function updateCouponSummary(data) {
        const box = document.getElementById('sltr-coupon-summary');
        const discount = document.getElementById('sltr-summary-coupon-discount');
        const finalAmount = document.getElementById('sltr-summary-final-amount');
        if (!box) return;
        if (!data || !data.valid) {
            box.style.display = 'none';
            if (discount) discount.textContent = '—';
            if (finalAmount) finalAmount.textContent = '—';
            return;
        }
        box.style.display = 'grid';
        if (discount) discount.textContent = '-' + (data.discount_amount || '0.00');
        if (finalAmount) finalAmount.textContent = data.final_amount || '0.00';
    }

    function clearCoupon() {
        appliedCouponCode = '';
        appliedCouponFinalAmount = '';
        if (selectedPackageMeta && selectedPackageMeta.mode === 'simple') simpleQuote = null;
        updateCouponSummary(null);
        setMessage('sltr-coupon-message', '', 'info');
    }

    function unitPriceSuffix(priceUnit) {
        if (priceUnit === 'per_night') return ' / night';
        if (priceUnit === 'per_day') return ' / day';
        if (priceUnit === 'per_hour') return ' / hour';
        return '';
    }

    function dateRangeSelectedSummary() {
        if (!selectedPackageMeta || selectedPackageMeta.mode !== 'date_range_inventory' || !selectedUnit) {
            return '';
        }
        if (selectedUnit.summary) {
            return selectedUnit.summary;
        }
        const total = selectedUnit.totalAmount || '';
        const base = total ? formatMoney(total) : '';
        if (selectedPackageMeta && selectedPackageMeta.dateFlow === 'admin_scheduled') {
            return base;
        }
        const nights = Number(selectedUnit.nights || 0);
        const days = Number(selectedUnit.days || 0);
        const unitPrice = selectedUnit.unitPrice || '';
        const priceUnit = selectedUnit.priceUnit || '';
        let detail = '';
        if (unitPrice && priceUnit && priceUnit !== 'fixed') {
            const qty = priceUnit === 'per_night' ? nights : (priceUnit === 'per_day' ? days : 0);
            const qtyLabel = priceUnit === 'per_night' ? nightsLabel(qty) : (qty === 1 ? '1 day' : qty + ' days');
            detail = formatMoney(unitPrice) + unitPriceSuffix(priceUnit) + (qty ? ' × ' + qtyLabel : '');
        } else if (nights) {
            detail = nightsLabel(nights);
        }
        if (base && detail) return base + ' (' + detail + ')';
        return base || detail;
    }

    function updateSummary() {
        const dateInput = document.getElementById('sltr-date');
        const service = document.getElementById('sltr-summary-service');
        const date = document.getElementById('sltr-summary-date');
        const time = document.getElementById('sltr-summary-time');
        const timeWrap = document.getElementById('sltr-summary-time-wrap');
        const rangeEnd = document.getElementById('sltr-range-end');
        const urgency = document.getElementById('sltr-summary-urgency');

        if (service) {
            const title = selectedPackageMeta ? selectedPackageMeta.title : '—';
            if (selectedPackageMeta && selectedPackageMeta.mode === 'date_range_inventory') {
                const rangeSummary = dateRangeSelectedSummary();
                service.textContent = title + (rangeSummary ? ' · ' + rangeSummary : '');
            } else if (selectedPackageMeta && selectedPackageMeta.mode === 'simple') {
                const priceValue = selectedPackageMeta.hidePriceOnFrontend ? '' : (appliedCouponFinalAmount || (selectedPackageMeta.price || ''));
                service.textContent = title + (priceValue ? ' · ' + priceValue : '');
            } else {
                const duration = selectedPackageMeta && selectedPackageMeta.duration ? ' · ' + selectedPackageMeta.duration : '';
                let priceValue = selectedPackageMeta && selectedPackageMeta.hidePriceOnFrontend ? '' : (appliedCouponFinalAmount || (selectedPackageMeta && selectedPackageMeta.price ? selectedPackageMeta.price : ''));
                if (selectedPackageMeta && selectedPackageMeta.fullDayBooking && !selectedPackageMeta.hidePriceOnFrontend && !appliedCouponFinalAmount) {
                    priceValue = formatMoney(numericAmount(selectedPackageMeta.priceRaw || selectedPackageMeta.price) * fixedFullDayDays());
                }
                const price = priceValue ? ' · ' + priceValue : '';
                service.textContent = title + duration + price;
            }
        }

        if (urgency) {
            const note = selectedPackageMeta && selectedPackageMeta.campaignNote ? selectedPackageMeta.campaignNote : '';
            const dynamic = (selectedUnit && selectedUnit.dynamicLabel) ? selectedUnit.dynamicLabel : (selectedPackageMeta && selectedPackageMeta.dynamicLabel ? selectedPackageMeta.dynamicLabel : '');
            const combined = [dynamic, note].filter(Boolean).join(' · ');
            urgency.textContent = combined;
            urgency.style.display = combined ? 'block' : 'none';
        }

        if (date) {
            if (selectedPackageMeta && selectedPackageMeta.mode === 'simple') {
                date.textContent = sltrT('Not scheduled yet');
            } else if (selectedPackageMeta && selectedPackageMeta.mode === 'date_range_inventory') {
                date.textContent = selectedPackageMeta.dateFlow === 'admin_scheduled' && selectedUnit && selectedUnit.event
                    ? eventDateLabel(selectedUnit.event)
                    : (formatHumanDate(dateInput ? dateInput.value : '') + ' → ' + formatHumanDate(rangeEnd ? rangeEnd.value : ''));
            } else if (selectedPackageMeta && selectedPackageMeta.fullDayBooking) {
                const fixedEnd = document.getElementById('sltr-fixed-end-date');
                date.textContent = formatHumanDate(dateInput ? dateInput.value : '') + ' → ' + formatHumanDate(fixedEnd ? fixedEnd.value : '');
            } else {
                date.textContent = formatHumanDate(dateInput ? dateInput.value : '');
            }
        }

        if (time) {
            if (selectedPackageMeta && selectedPackageMeta.mode === 'simple') {
                if (timeWrap) timeWrap.style.display = '';
                time.textContent = sltrT('To be confirmed');
            } else if (selectedPackageMeta && selectedPackageMeta.mode === 'date_range_inventory') {
                if (selectedPackageMeta.dateFlow === 'admin_scheduled' && selectedUnit && selectedUnit.event) {
                    const event = selectedUnit.event;
                    const useTime = !!event.use_time;
                    const start = useTime ? formatTime(event.start_time || '') : '';
                    const end = useTime ? formatTime(event.end_time || '') : '';
                    time.textContent = !useTime ? '—' : (start && end && end !== start ? start + ' - ' + end : (start || end || '—'));
                    if (timeWrap) timeWrap.style.display = 'none';
                } else {
                    if (timeWrap) timeWrap.style.display = '';
                    const label = selectedUnit && selectedUnit.name && !isDefaultUnitName(selectedUnit.name) ? selectedUnit.name : sltrT('Selected option');
                    time.textContent = selectedUnit ? label : '—';
                }
            } else if (selectedPackageMeta && selectedPackageMeta.fullDayBooking) {
                if (timeWrap) timeWrap.style.display = 'none';
                time.textContent = '—';
            } else {
                if (timeWrap) timeWrap.style.display = '';
                time.textContent = selectedSlot ? (selectedPackageMeta && selectedPackageMeta.displayStartTimeOnly ? formatTime(selectedSlot.start) : formatTime(selectedSlot.start) + ' - ' + formatTime(selectedSlot.end)) : '—';
            }
        }

        updatePaymentSummary();
    }

    function updatePaymentSummary() {
        const totalWrap = document.getElementById('sltr-summary-total-wrap');
        const nowWrap = document.getElementById('sltr-summary-pay-now-wrap');
        const laterWrap = document.getElementById('sltr-summary-pay-later-wrap');
        const taxWrap = document.getElementById('sltr-summary-tax-wrap');
        const totalEl = document.getElementById('sltr-summary-total');
        const nowEl = document.getElementById('sltr-summary-pay-now');
        const laterEl = document.getElementById('sltr-summary-pay-later');
        const taxEl = document.getElementById('sltr-summary-tax');
        const taxLabelEl = document.getElementById('sltr-summary-tax-label');
        const dynamicWrap = document.getElementById('sltr-summary-dynamic-wrap');
        const dynamicEl = document.getElementById('sltr-summary-dynamic');
        if (!totalWrap || !nowWrap || !laterWrap) return;
        if (!selectedPackageMeta) {
            [totalWrap, nowWrap, laterWrap].forEach(function(el){ el.style.display = 'none'; }); if (taxWrap) taxWrap.style.display = 'none'; if (dynamicWrap) dynamicWrap.style.display = 'none';
            return;
        }
        if (selectedPackageMeta.hidePriceOnFrontend) {
            [totalWrap, nowWrap, laterWrap].forEach(function(el){ el.style.display = 'none'; });
            if (taxWrap) taxWrap.style.display = 'none';
            if (dynamicWrap) dynamicWrap.style.display = 'none';
            return;
        }
        const decision = paymentDecisionForChoice(activePaymentChoice());
        const hasAmount = decision.total > 0;
        totalWrap.style.display = hasAmount ? '' : 'none';
        nowWrap.style.display = '';
        laterWrap.style.display = decision.remaining > 0 ? '' : 'none';
        const dynamicLabel = (selectedUnit && selectedUnit.dynamicLabel) ? selectedUnit.dynamicLabel : (selectedPackageMeta && selectedPackageMeta.dynamicLabel ? selectedPackageMeta.dynamicLabel : '');
        const packageDiscountLabel = selectedPackageMeta && selectedPackageMeta.packageDiscountLabel ? selectedPackageMeta.packageDiscountLabel : '';
        const discountBreakdown = [packageDiscountLabel, dynamicLabel].filter(Boolean).join(' · ');
        if (dynamicWrap) dynamicWrap.style.display = discountBreakdown ? '' : 'none';
        if (dynamicEl) dynamicEl.textContent = discountBreakdown || '—';
        const taxAmount = numericAmount((selectedUnit && selectedUnit.taxAmount) ? selectedUnit.taxAmount : (selectedPackageMeta ? selectedPackageMeta.taxAmount : 0));
        if (taxWrap) taxWrap.style.display = taxAmount > 0 ? '' : 'none';
        if (taxEl) taxEl.textContent = formatMoney(taxAmount);
        if (taxLabelEl) taxLabelEl.textContent = (selectedUnit && selectedUnit.taxLabel) ? selectedUnit.taxLabel : (selectedPackageMeta && selectedPackageMeta.taxLabel ? selectedPackageMeta.taxLabel : sltrT('Tax'));
        if (totalEl) totalEl.textContent = hasAmount ? formatMoney(decision.total) : sltrT('Price on request');
        if (nowEl) nowEl.textContent = formatMoney(decision.dueNow);
        if (laterEl) laterEl.textContent = formatMoney(decision.remaining);
    }

    function updateCouponVisibility() {
        const couponBox = document.getElementById('sltr-coupon-box');
        if (!couponBox) return;

        const shouldShow = Boolean(selectedPackageMeta && selectedPackageMeta.hasAvailableCoupon);
        couponBox.style.display = shouldShow ? '' : 'none';

        if (!shouldShow) {
            clearCoupon();
            const couponInput = document.getElementById('sltr-coupon-code');
            if (couponInput) couponInput.value = '';
        }
    }

    function resetFromPackage() {
        selectedSlot = null;
        selectedUnit = null;
        selectedExtras = [];
        simpleQuote = null;
        dateRangeData = null;
        availableDates = new Set();
        const dateInput = document.getElementById('sltr-date');
        if (dateInput) dateInput.value = '';
        const rangeStart = document.getElementById('sltr-range-start');
        const rangeEnd = document.getElementById('sltr-range-end');
        const urgency = document.getElementById('sltr-summary-urgency');
        if (rangeStart) rangeStart.value = '';
        if (rangeEnd) rangeEnd.value = '';
        const fixedEnd = document.getElementById('sltr-fixed-end-date');
        const fixedWrap = document.getElementById('sltr-fixed-full-day-end');
        if (fixedEnd) fixedEnd.value = '';
        if (fixedWrap) fixedWrap.hidden = true;
        const slots = document.getElementById('sltr-slots');
        if (slots) slots.innerHTML = '';
        ['sltr-date-range-results','sltr-included-services','sltr-extra-services','sltr-details-extra-services'].forEach(function(id){ const el=document.getElementById(id); if(el){el.innerHTML=''; el.style.display='none';} });
        setMessage('sltr-date-message', '', 'info');
        setMessage('sltr-slot-message', '', 'info');
        setMessage('sltr-message', '', 'info');
        updateSummary();
        clearCoupon();
        const couponInput = document.getElementById('sltr-coupon-code');
        if (couponInput) couponInput.value = '';
        updateCouponVisibility();
    }

    function renderCalendar() {
        const calendar = document.getElementById('sltr-calendar');
        if (!calendar) return;

        updateCalendarWeekdays();
        const title = calendar.querySelector('.sltr-calendar-title');
        const daysContainer = calendar.querySelector('.sltr-calendar-days');
        const year = calendarDate.getFullYear();
        const month = calendarDate.getMonth();
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        title.textContent = localizedDate(calendarDate, { month: 'long', year: 'numeric' });
        daysContainer.innerHTML = '';
        calendar.classList.toggle('is-loading', datesLoading);

        const firstDay = new Date(year, month, 1);
        const firstWeekday = (firstDay.getDay() + 6) % 7;
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const selectedDateValue = document.getElementById('sltr-date')?.value || '';

        for (let i = 0; i < firstWeekday; i++) {
            const empty = document.createElement('span');
            empty.className = 'sltr-calendar-empty';
            daysContainer.appendChild(empty);
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day);
            date.setHours(0, 0, 0, 0);
            const dateValue = formatDate(date);
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'sltr-calendar-day';
            button.textContent = String(day);
            button.setAttribute('data-date', dateValue);

            const isPast = date < today;
            const isAvailable = availableDates.has(dateValue);
            button.setAttribute('aria-label', localizedDate(date, { weekday: 'long', month: 'short', day: 'numeric' }) + (isAvailable ? ': ' + sltrT('available') : ': ' + sltrT('unavailable')));

            if (isAvailable && !isPast && !datesLoading) {
                button.classList.add('is-available');
                button.setAttribute('title', sltrT('Available'));
            }

            if (isPast || datesLoading || !isAvailable) {
                button.disabled = true;
                button.classList.add('is-disabled');
            }

            if (!isPast && !datesLoading && !isAvailable) {
                button.classList.add('is-unavailable');
                button.setAttribute('title', sltrT('No available times'));
            }

            if (dateValue === selectedDateValue) {
                button.classList.add('is-selected');
            }

            daysContainer.appendChild(button);
        }
    }

    function loadAvailableDates() {
        if (!selectedPackage) return;

        datesLoading = true;
        availableDates = new Set();
        setInlineAvailability(sltrT('Checking available dates...'), 'info');
        setMessage('sltr-date-message', sltrT('Loading available dates...'), 'info');
        renderCalendar();
        fetch(sltr_ajax.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'sltr_get_available_dates',
                nonce: sltr_ajax.nonce,
                package_id: selectedPackage,
                year: calendarDate.getFullYear(),
                month: calendarDate.getMonth() + 1
            })
        }).then(function (response) {
            return response.json();
        }).then(function (data) {
            datesLoading = false;
            const dates = data && data.success && data.data && data.data.dates ? data.data.dates : [];
            availableDates = new Set(dates);

            if (!dates.length) {
                setInlineAvailability(sltrT('No available dates in this month. Try the next month.'), 'info');
                setMessage('sltr-date-message', sltrT('No available dates in this month.'), 'info');
            } else {
                setInlineAvailability(sltrFormat(dates.length === 1 ? '%d available date this month.' : '%d available dates this month.', dates.length), 'success');
                setMessage('sltr-date-message', sltrT('Choose an available date.'), 'info');
            }

            renderCalendar();
        }).catch(function () {
            datesLoading = false;
            setInlineAvailability(sltrT('Availability could not be loaded. Please try again.'), 'error');
            setMessage('sltr-date-message', sltrT('Could not load available dates. Please try again.'), 'error');
            renderCalendar();
        });
    }

    function loadSlots(dateValue) {
        const container = document.getElementById('sltr-slots');
        const hiddenDate = document.getElementById('sltr-date');

        if (!container || !selectedPackage || !dateValue) return;

        if (hiddenDate) hiddenDate.value = dateValue;
        selectedSlot = null;
        updateSummary();
        setMessage('sltr-slot-message', sltrT('Loading available times...'), 'info');
        container.innerHTML = '<div class="sltr-loading"><span></span>' + escapeHtml(sltrT('Loading available times...')) + '</div>';
        showStep(3);

        fetch(sltr_ajax.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'sltr_get_slots',
                nonce: sltr_ajax.nonce,
                package_id: selectedPackage,
                date: dateValue
            })
        }).then(function (response) {
            return response.json();
        }).then(function (data) {
            container.innerHTML = '';
            setMessage('sltr-slot-message', '', 'info');

            if (!data.success || !data.data || !data.data.length) {
                container.innerHTML = '<p class="sltr-empty-state">' + escapeHtml(sltrT('No slots available for this date. Choose another available date.')) + '</p>';
                return;
            }

            setMessage('sltr-slot-message', sltrFormat(data.data.length === 1 ? '%d available time for %s.' : '%d available times for %s.', data.data.length, formatHumanDate(dateValue)), 'success');

            data.data.forEach(function (slot) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'sltr-slot';
                button.setAttribute('data-start', slot.start);
                button.setAttribute('data-end', slot.end);
                button.textContent = selectedPackageMeta && (selectedPackageMeta.displayStartTimeOnly || selectedPackageMeta.fullDayBooking) ? formatTime(slot.start) : formatTime(slot.start) + ' - ' + formatTime(slot.end);
                container.appendChild(button);
            });
        }).catch(function () {
            container.innerHTML = '';
            setMessage('sltr-slot-message', sltrT('Could not load slots. Please try again.'), 'error');
        });
    }


    function formatMoney(value) {
        const decimals = (window.sltr_ajax && Number.isInteger(Number(sltr_ajax.currency_decimals))) ? Number(sltr_ajax.currency_decimals) : 2;
        const decimalSeparator = (window.sltr_ajax && typeof sltr_ajax.currency_decimal_separator === 'string') ? sltr_ajax.currency_decimal_separator : '.';
        const thousandsSeparator = (window.sltr_ajax && typeof sltr_ajax.currency_thousands_separator === 'string') ? sltr_ajax.currency_thousands_separator : ' ';
        const parts = Number(value || 0).toFixed(decimals).split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSeparator);
        const number = decimals > 0 ? parts[0] + decimalSeparator + parts[1] : parts[0];
        const symbol = (window.sltr_ajax && sltr_ajax.currency_symbol) ? sltr_ajax.currency_symbol : '';
        const position = (window.sltr_ajax && sltr_ajax.currency_position) ? sltr_ajax.currency_position : 'right_space';
        if (!symbol) return number;
        if (position === 'left') return symbol + number;
        if (position === 'left_space') return symbol + ' ' + number;
        if (position === 'right') return number + symbol;
        return number + ' ' + symbol;
    }

    function numericAmount(value) {
        if (typeof value === 'number') return isFinite(value) ? value : 0;
        let text = String(value || '').trim();
        if (!text) return 0;
        text = text.replace(/[^0-9,.-]/g, '').replace(/,/g, '.');
        const parts = text.split('.');
        if (parts.length > 2) {
            text = parts.slice(0, -1).join('') + '.' + parts[parts.length - 1];
        }
        const amount = parseFloat(text);
        return isFinite(amount) ? amount : 0;
    }

    function fixedFullDayDays() {
        if (!selectedPackageMeta || !selectedPackageMeta.fullDayBooking) return 1;
        const startEl = document.getElementById('sltr-date');
        const endEl = document.getElementById('sltr-fixed-end-date');
        if (!startEl || !endEl || !startEl.value || !endEl.value) return 1;
        const start = new Date(startEl.value + 'T00:00:00');
        const end = new Date(endEl.value + 'T00:00:00');
        const days = Math.round((end.getTime() - start.getTime()) / 86400000);
        return days > 0 ? days : 1;
    }

    function updateFixedFullDayTotal() {
        const out = document.getElementById('sltr-fixed-full-day-total');
        if (!out || !selectedPackageMeta || !selectedPackageMeta.fullDayBooking) return;
        const days = fixedFullDayDays();
        const base = numericAmount(selectedPackageMeta.priceRaw || selectedPackageMeta.price);
        out.textContent = formatMoney(base * days) + ' · ' + days + (days === 1 ? ' day' : ' days');
        renderDateRangePayment(selectedPackageMeta.policy || 'booking_only');
        updateSummary();
    }

    function prepareFixedFullDayEndDate() {
        const wrap = document.getElementById('sltr-fixed-full-day-end');
        const startEl = document.getElementById('sltr-date');
        const endEl = document.getElementById('sltr-fixed-end-date');
        if (!wrap || !startEl || !endEl || !startEl.value) return;
        const start = new Date(startEl.value + 'T00:00:00');
        start.setDate(start.getDate() + 1);
        const min = formatDate(start);
        endEl.min = min;
        if (!endEl.value || endEl.value < min) endEl.value = min;
        wrap.hidden = false;
        updateFixedFullDayTotal();
    }

    function renderSimpleExtras() {
        const box = document.getElementById('sltr-details-extra-services');
        if (!box) return;
        const items = selectedPackageMeta && selectedPackageMeta.mode === 'simple' && Array.isArray(selectedPackageMeta.extras) ? selectedPackageMeta.extras : [];
        box.hidden = !items.length;
        box.style.display = items.length ? 'block' : 'none';
        box.innerHTML = items.length ? '<h4>' + escapeHtml(sltrT('Add extras')) + '</h4>' : '';
        items.forEach(function(item){
            const label = document.createElement('label');
            label.className = 'sltr-extra-option';
            const checked = selectedExtras.indexOf(String(item.id)) !== -1 ? ' checked' : '';
            label.innerHTML = '<span>' + escapeHtml(item.name || '') + '</span><strong>+' + escapeHtml(formatMoney(item.price || 0)) + '</strong><input type="checkbox" value="' + escapeHtml(item.id) + '"' + checked + ' aria-label="' + escapeHtml(item.name || sltrT('Add extras')) + '">' + (item.description ? '<small>' + escapeHtml(item.description) + '</small>' : '');
            box.appendChild(label);
        });
    }

    function requestSimpleQuote() {
        if (!selectedPackageMeta || selectedPackageMeta.mode !== 'simple' || !selectedPackage) {
            simpleQuote = null;
            return Promise.resolve(null);
        }
        const email = document.getElementById('sltr-email');
        return fetch(sltr_ajax.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'sltr_quote_simple_booking',
                nonce: sltr_ajax.nonce,
                package_id: selectedPackage,
                extra_ids: selectedExtras.join(','),
                coupon_code: appliedCouponCode || '',
                email: email ? email.value : ''
            })
        }).then(function(response){ return response.json(); }).then(function(data){
            if (!data.success || !data.data) return null;
            simpleQuote = data.data;
            if (appliedCouponCode) {
                appliedCouponFinalAmount = data.data.final_amount || '';
                updateCouponSummary(data.data);
            }
            updateSummary();
            renderDateRangePayment(selectedPackageMeta.policy || 'booking_only');
            return data.data;
        }).catch(function(){ return null; });
    }

    function currentTotalAmount() {
        if (selectedPackageMeta && selectedPackageMeta.mode === 'simple' && simpleQuote && simpleQuote.final_amount !== undefined) {
            return numericAmount(simpleQuote.final_amount);
        }
        if (appliedCouponFinalAmount) {
            const couponAmount = numericAmount(appliedCouponFinalAmount);
            if (couponAmount > 0) return couponAmount;
        }
        if (selectedPackageMeta && selectedPackageMeta.mode === 'date_range_inventory' && selectedUnit) {
            return numericAmount(selectedUnit.totalAmount || selectedUnit.baseAmount || selectedPackageMeta.priceRaw || selectedPackageMeta.price);
        }
        if (selectedPackageMeta && selectedPackageMeta.mode === 'simple' && selectedPackageMeta.priceMode === 'request') {
            return 0;
        }
        const baseAmount = numericAmount(selectedPackageMeta ? (selectedPackageMeta.priceRaw || selectedPackageMeta.price) : 0);
        return selectedPackageMeta && selectedPackageMeta.fullDayBooking ? baseAmount * fixedFullDayDays() : baseAmount;
    }

    function depositAmount(total) {
        total = Number(total || 0);
        if (!selectedPackageMeta || total <= 0) return 0;
        const type = selectedPackageMeta.depositType || 'percent';
        const value = Number(selectedPackageMeta.depositValue || 0);
        if (!isFinite(value) || value <= 0) return 0;
        return Math.min(total, type === 'fixed' ? value : (total * Math.min(100, value) / 100));
    }

    function activePaymentChoice() {
        if (selectedPackageMeta && selectedPackageMeta.hidePaymentMethods) return 'booking_only';
        if (!selectedPackageMeta) return 'booking_only';
        const choices = paymentChoicesForPolicy(selectedPackageMeta.policy || 'booking_only');
        const choiceEl = document.getElementById('sltr-payment-choice');
        const chosen = choiceEl && choiceEl.value ? choiceEl.value : defaultPaymentChoice(choices);
        return choices.indexOf(chosen) !== -1 ? chosen : defaultPaymentChoice(choices);
    }

    function paymentDecisionForChoice(choice) {
        const total = currentTotalAmount();
        let dueNow = 0;
        if (choice === 'full_payment') dueNow = total;
        if (choice === 'deposit_payment') dueNow = depositAmount(total);
        const remaining = Math.max(0, total - dueNow);
        return { total: total, dueNow: dueNow, remaining: remaining };
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>\"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '\"': '&quot;', "'": '&#039;' })[char] || char;
        });
    }

    function isDefaultUnitName(name) {
        const normalized = String(name || '').trim().toLowerCase();
        return normalized === '' || normalized === 'default unit' || normalized === 'unit' || normalized === 'room';
    }

    function visibleUnitName(unit, totalUnits) {
        const name = String(unit && unit.name ? unit.name : '').trim();
        if (!name || isDefaultUnitName(name)) {
            return totalUnits > 1 ? '' : '';
        }
        return name;
    }

    function nightsLabel(count) {
        count = Number(count || 0);
        return count === 1 ? sltrT('1 night') : count + ' nights';
    }

    function seatsLabel(count, serverLabel) {
        count = Number(count || 0);
        if (serverLabel) return String(serverLabel);
        return sltrT('%d places left').replace('%d', count);
    }

    function lowAvailabilityLabel(count, serverLabel) {
        count = Number(count || 0);
        const enabled = !selectedPackageMeta || selectedPackageMeta.lowAvailabilityEnabled !== false;
        const threshold = Number((selectedPackageMeta && selectedPackageMeta.lowAvailabilityThreshold) || (window.sltr_ajax && sltr_ajax.low_availability_threshold) || 5);
        if (serverLabel) return String(serverLabel);
        if (count <= 0) return sltrT('Sold out');
        if (enabled && count <= threshold) return sltrT('Only %d spots left').replace('%d', count);
        return seatsLabel(count, serverLabel);
    }

    function paymentChoicesForPolicy(policy) {
        return window.SloteraBookingModes.paymentChoices(policy);
    }

    function paymentChoiceLabel(choice) {
        if (choice === 'full_payment') return sltrT('Pay in full');
        if (choice === 'deposit_payment') return sltrT('Pay deposit');
        return sltrT('Payment method');
    }

    function defaultPaymentChoice(choices) {
        return window.SloteraBookingModes.defaultPaymentChoice(choices);
    }

    function paymentChoiceDescription(choice, decision) {
        if (choice === 'full_payment') return sltrT('Pay %s now.').replace('%s', formatMoney(decision.dueNow));
        if (choice === 'deposit_payment') return sltrT('Pay %1$s now. Remaining %2$s on arrival.').replace('%1$s', formatMoney(decision.dueNow)).replace('%2$s', formatMoney(decision.remaining));
        return sltrT('No payment now') + (decision.remaining > 0 ? '. ' + sltrT('Pay %s on arrival.').replace('%s', formatMoney(decision.remaining)) : '.');
    }

    function updateSubmitButtonForPaymentChoice(choice) {
        const submit = document.getElementById('sltr-submit');
        if (!submit) return;
        const decision = paymentDecisionForChoice(choice || activePaymentChoice());
        if (decision.dueNow > 0) {
            submit.textContent = sltrT('Pay %s').replace('%s', formatMoney(decision.dueNow));
        } else {
            submit.textContent = (selectedPackageMeta && selectedPackageMeta.bookingButtonText) ? selectedPackageMeta.bookingButtonText : sltrT('Book now');
        }
    }

    function setPaymentChoice(choice) {
        const choiceEl = document.getElementById('sltr-payment-choice');
        if (choiceEl) choiceEl.value = choice;
        document.querySelectorAll('.sltr-payment-option-card').forEach(function(card){
            const isActive = card.getAttribute('data-choice') === choice;
            card.classList.toggle('is-selected', isActive);
            const radio = card.querySelector('input[type="radio"]');
            if (radio) radio.checked = isActive;
        });
        const requiresPayment = choice === 'full_payment' || choice === 'deposit_payment';
        const gatewayWrap = document.getElementById('sltr-date-range-gateway-wrap');
        const note = document.getElementById('sltr-pay-on-arrival-note');
        if (gatewayWrap) gatewayWrap.style.display = requiresPayment ? 'block' : 'none';
        if (note) note.style.display = requiresPayment ? 'none' : 'block';
        updateSubmitButtonForPaymentChoice(choice);
        updateSummary();
    }

    function renderDateRangePayment(policy) {
        const box = document.getElementById('sltr-date-range-payment');
        const choiceWrap = document.getElementById('sltr-payment-choice-wrap');
        const choiceEl = document.getElementById('sltr-payment-choice');
        const cards = document.getElementById('sltr-payment-option-cards');
        const legacyPayment = document.querySelector('.sltr-payment-methods');
        if (legacyPayment) legacyPayment.style.display = selectedPackageMeta ? 'none' : '';
        if (!box) return;
        if (selectedPackageMeta && selectedPackageMeta.hidePaymentMethods) {
            box.style.display = 'none';
            if (choiceWrap) choiceWrap.style.display = 'none';
            setPaymentChoice('booking_only');
            return;
        }
        box.style.display = selectedPackageMeta ? 'block' : 'none';
        if (!selectedPackageMeta) return;
        policy = policy || selectedPackageMeta.policy || 'booking_only';
        selectedPackageMeta.policy = policy;
        const choices = paymentChoicesForPolicy(policy);
        const defaultChoice = defaultPaymentChoice(choices);

        if (!choices.length) {
            if (choiceWrap) choiceWrap.style.display = 'none';
            setPaymentChoice(null);
            return;
        }
        if (choiceEl) {
            choiceEl.innerHTML = '';
            choices.forEach(function(choice){
                const option = document.createElement('option');
                option.value = choice;
                option.textContent = paymentChoiceLabel(choice);
                choiceEl.appendChild(option);
            });
            choiceEl.value = choices.indexOf(choiceEl.value) !== -1 ? choiceEl.value : defaultChoice;
        }
        if (choiceWrap) choiceWrap.style.display = 'block';
        if (cards) {
            cards.innerHTML = '';
            choices.forEach(function(choice){
                const decision = paymentDecisionForChoice(choice);
                const card = document.createElement('button');
                card.type = 'button';
                card.className = 'sltr-payment-option-card';
                card.setAttribute('data-choice', choice);
                const recommended = choice === 'deposit_payment';
                if (recommended) card.classList.add('is-recommended');
                card.innerHTML = '<span class="sltr-payment-radio"><input type="radio" aria-hidden="true" tabindex="-1"></span>' +
                    '<span class="sltr-payment-card-body"><strong>' + escapeHtml(paymentChoiceLabel(choice)) + (recommended ? ' <em>' + escapeHtml(sltrT('Recommended')) + '</em>' : '') + '</strong><small>' + escapeHtml(paymentChoiceDescription(choice, decision)) + '</small></span>' +
                    '<b>' + escapeHtml(formatMoney(decision.dueNow)) + '</b>';
                card.addEventListener('click', function(){ setPaymentChoice(choice); });
                cards.appendChild(card);
            });
        }
        setPaymentChoice(choiceEl ? choiceEl.value : defaultChoice);
    }

    function renderDateRangeResults(payload) {
        const results = document.getElementById('sltr-date-range-results');
        const included = document.getElementById('sltr-included-services');
        const extras = document.getElementById('sltr-extra-services');
        if (!results) return;
        dateRangeData = payload || {};
        selectedUnit = null;
        selectedExtras = [];
        results.innerHTML = '';
        results.style.display = 'block';
        const units = payload.units || [];
        if (!units.length) {
            results.innerHTML = '<p class="sltr-empty-state">' + escapeHtml(sltrT('No options are available for these dates.')) + '</p>';
            return;
        }
        units.forEach(function(unit){
            const q = unit.quote || {};
            const btn = document.createElement('button');
            const name = visibleUnitName(unit, units.length);
            const capacity = Number(unit.capacity || 0);
            btn.type = 'button';
            btn.className = 'sltr-slot sltr-unit-option sltr-conversion-option';
            btn.setAttribute('data-unit-id', unit.id);
            btn.setAttribute('data-unit-name', name);
            btn.setAttribute('data-total-amount', q.total_amount || '');
            btn.setAttribute('data-base-amount', q.base_amount || '');
            btn.setAttribute('data-tax-amount', q.tax_amount || '');
            btn.setAttribute('data-tax-label', q.tax_label || sltrT('VAT'));
            btn.setAttribute('data-dynamic-label', q.dynamic_label || '');
            btn.setAttribute('data-unit-price', unit.price || '');
            btn.setAttribute('data-price-unit', q.price_unit || '');
            btn.setAttribute('data-nights', q.nights || '');
            btn.setAttribute('data-days', q.days || '');
            const unitTotal = q.total_amount || '';
            const unitNights = Number(q.nights || 0);
            const unitDays = Number(q.days || 0);
            const unitPrice = unit.price || q.unit_price || '';
            const priceUnit = q.price_unit || '';
            let unitSummary = unitTotal ? formatMoney(unitTotal) : '';
            if (unitPrice && priceUnit && priceUnit !== 'fixed') {
                const qty = priceUnit === 'per_night' ? unitNights : (priceUnit === 'per_day' ? unitDays : 0);
                const qtyLabel = priceUnit === 'per_night' ? nightsLabel(qty) : (qty === 1 ? '1 day' : qty + ' days');
                unitSummary += qty ? ' (' + formatMoney(unitPrice) + unitPriceSuffix(priceUnit) + ' × ' + qtyLabel + ')' : ' (' + formatMoney(unitPrice) + unitPriceSuffix(priceUnit) + ')';
            } else if (unitNights && unitSummary) {
                unitSummary += ' (' + nightsLabel(unitNights) + ')';
            }
            btn.setAttribute('data-summary', unitSummary);
            btn.innerHTML =
                (name ? '<strong class="sltr-option-title">' + escapeHtml(name) + '</strong>' : '') +
                '<span class="sltr-option-main"><b>' + escapeHtml(formatMoney(q.total_amount)) + '</b><small>' + escapeHtml(nightsLabel(q.nights || 0)) + '</small></span>' +
                (q.dynamic_label ? '<span class="sltr-dynamic-offer-note">' + escapeHtml(q.dynamic_label) + '</span>' : '') +
                '<span class="sltr-option-sub">' + escapeHtml(capacity > 0 ? lowAvailabilityLabel(capacity, unit.availability_label) : sltrT('Available')) + '</span>' +
                '<span class="sltr-option-cta">' + escapeHtml(sltrT('Continue')) + '</span>';
            results.appendChild(btn);
        });
        if (included) {
            const text = payload.included_services || (selectedPackageMeta ? selectedPackageMeta.included : '');
            included.style.display = text ? 'block' : 'none';
            included.innerHTML = text ? '<h4>What is included</h4><p>' + escapeHtml(text).replace(/\n/g, '<br>') + '</p>' : '';
        }
        if (extras) {
            const items = payload.extras || [];
            extras.style.display = items.length ? 'block' : 'none';
            extras.innerHTML = items.length ? '<h4>' + escapeHtml(sltrT('Add extras')) + '</h4>' : '';
            items.forEach(function(item){
                const label = document.createElement('label');
                label.className = 'sltr-extra-option';
                label.innerHTML = '<span>' + escapeHtml(item.name) + '</span><strong>' + escapeHtml(formatMoney(item.price)) + '</strong><input type="checkbox" value="' + escapeHtml(item.id) + '" aria-label="' + escapeHtml(item.name || sltrT('Add extras')) + '"><small>' + escapeHtml(item.price_type) + '</small>';
                extras.appendChild(label);
            });
        }
        renderDateRangePayment(payload.payment_policy);
    }



    function renderScheduledEvents(payload) {
        const results = document.getElementById('sltr-date-range-results');
        const included = document.getElementById('sltr-included-services');
        const extras = document.getElementById('sltr-extra-services');
        if (!results) return;
        dateRangeData = payload || {};
        selectedUnit = null;
        selectedExtras = [];
        results.innerHTML = '';
        results.style.display = 'block';
        const events = payload.events || [];
        if (!events.length) {
            results.innerHTML = '<p class="sltr-empty-state">' + escapeHtml(sltrT('No scheduled events are available.')) + '</p>';
            return;
        }
        events.forEach(function(event){
            const q = event.quote || {};
            const title = event.title || (selectedPackageMeta ? selectedPackageMeta.title : sltrT('Scheduled event'));
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'sltr-slot sltr-unit-option sltr-event-option sltr-conversion-option';
            btn.setAttribute('data-unit-id', event.id);
            btn.setAttribute('data-unit-name', title);
            btn.setAttribute('data-event-json', JSON.stringify(event));
            btn.setAttribute('data-total-amount', q.total_amount || event.price || '');
            btn.setAttribute('data-base-amount', q.base_amount || event.price || '');
            btn.setAttribute('data-tax-amount', q.tax_amount || '');
            btn.setAttribute('data-tax-label', q.tax_label || sltrT('VAT'));
            btn.setAttribute('data-dynamic-label', q.dynamic_label || '');
            btn.setAttribute('data-unit-price', q.base_amount || event.price || '');
            btn.setAttribute('data-price-unit', q.price_unit || 'fixed');
            btn.setAttribute('data-nights', q.nights || '');
            btn.setAttribute('data-days', q.days || '');
            const unitTotal = q.total_amount || '';
            const unitNights = Number(q.nights || 0);
            const unitDays = Number(q.days || 0);
            const unitPrice = event.price || q.unit_price || q.base_amount || '';
            const priceUnit = q.price_unit || 'fixed';
            let unitSummary = unitTotal ? formatMoney(unitTotal) : '';
            if (unitPrice && priceUnit && priceUnit !== 'fixed') {
                const qty = priceUnit === 'per_night' ? unitNights : (priceUnit === 'per_day' ? unitDays : 0);
                const qtyLabel = priceUnit === 'per_night' ? nightsLabel(qty) : (qty === 1 ? '1 day' : qty + ' days');
                unitSummary += qty ? ' (' + formatMoney(unitPrice) + unitPriceSuffix(priceUnit) + ' × ' + qtyLabel + ')' : ' (' + formatMoney(unitPrice) + unitPriceSuffix(priceUnit) + ')';
            }
            btn.setAttribute('data-summary', unitSummary);
            btn.innerHTML =
                '<strong class="sltr-option-title">' + escapeHtml(title) + '</strong>' +
                '<span class="sltr-option-date">' + escapeHtml(eventDateLabel(event)) + '</span>' +
                (q.dynamic_label ? '<span class="sltr-dynamic-offer-note">' + escapeHtml(q.dynamic_label) + '</span>' : '') +
                '<span class="sltr-option-main"><b>' + escapeHtml(formatMoney(q.total_amount || event.price)) + '</b><small>' + escapeHtml(lowAvailabilityLabel(event.seats_left || 0, event.availability_label)) + '</small></span>' +
                '<span class="sltr-option-cta">' + escapeHtml(sltrT('Book now')) + '</span>';
            results.appendChild(btn);
        });
        if (included) {
            const text = payload.included_services || (selectedPackageMeta ? selectedPackageMeta.included : '');
            included.style.display = text ? 'block' : 'none';
            included.innerHTML = text ? '<h4>What is included</h4><p>' + escapeHtml(text).replace(/\n/g, '<br>') + '</p>' : '';
        }
        if (extras) {
            const items = payload.extras || [];
            extras.style.display = items.length ? 'block' : 'none';
            extras.innerHTML = items.length ? '<h4>' + escapeHtml(sltrT('Add extras')) + '</h4>' : '';
            items.forEach(function(item){
                const label = document.createElement('label');
                label.className = 'sltr-extra-option';
                label.innerHTML = '<span>' + escapeHtml(item.name) + '</span><strong>' + escapeHtml(formatMoney(item.price)) + '</strong><input type="checkbox" value="' + escapeHtml(item.id) + '" aria-label="' + escapeHtml(item.name || sltrT('Add extras')) + '"><small>' + escapeHtml(item.price_type) + '</small>';
                extras.appendChild(label);
            });
        }
        renderDateRangePayment(payload.payment_policy);
    }

    function loadScheduledEvents() {
        if (!selectedPackage) return;
        setMessage('sltr-date-message', sltrT('Loading scheduled events...'), 'info');
        showStep(3);
        const results = document.getElementById('sltr-date-range-results');
        if (results) { results.style.display = 'block'; results.innerHTML = '<div class="sltr-loading"><span></span>' + escapeHtml(sltrT('Loading scheduled events...')) + '</div>'; }
        fetch(sltr_ajax.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'sltr_get_scheduled_events', nonce: sltr_ajax.nonce, package_id: selectedPackage })
        }).then(function(response){ return response.json(); }).then(function(data){
            if (!data.success) { setMessage('sltr-slot-message', data.data || sltrT('Could not load scheduled events.'), 'error'); if(results) results.innerHTML=''; return; }
            setMessage('sltr-date-message', '', 'info'); setMessage('sltr-slot-message', sltrT('Choose your preferred date.'), 'info'); renderScheduledEvents(data.data || {});
        }).catch(function(){ setMessage('sltr-slot-message', sltrT('Could not load scheduled events. Please try again.'), 'error'); if(results) results.innerHTML=''; });
    }

    function loadDateRangeUnits() {
        const trigger = document.getElementById('sltr-check-range');
        const start = document.getElementById('sltr-range-start');
        const end = document.getElementById('sltr-range-end');
        const hiddenDate = document.getElementById('sltr-date');
        if (!selectedPackage || !start || !end || !start.value || !end.value) {
            setMessage('sltr-date-message', sltrT('Choose both dates first.'), 'error');
            return;
        }
        if (hiddenDate) hiddenDate.value = start.value;
        setButtonBusy(trigger, true, sltrT('Checking...'));
        setMessage('sltr-date-message', sltrT('Checking availability...'), 'info');
        showStep(3);
        const results = document.getElementById('sltr-date-range-results');
        if (results) { results.style.display = 'block'; results.innerHTML = '<div class="sltr-loading"><span></span>' + escapeHtml(sltrT('Checking rooms...')) + '</div>'; }
        fetch(sltr_ajax.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'sltr_get_date_range_units', nonce: sltr_ajax.nonce, package_id: selectedPackage, start_date: start.value, end_date: end.value })
        }).then(function(response){ return response.json(); }).then(function(data){
            setButtonBusy(trigger, false);
            if (!data.success) { setMessage('sltr-slot-message', data.data || sltrT('Could not check availability.'), 'error'); if(results) results.innerHTML=''; return; }
            setMessage('sltr-date-message', '', 'info'); setMessage('sltr-slot-message', sltrT('Choose the best available option.'), 'info'); renderDateRangeResults(data.data || {});
        }).catch(function(){ setButtonBusy(trigger, false); setMessage('sltr-slot-message', sltrT('Could not check availability. Please try again.'), 'error'); if(results) results.innerHTML=''; });
    }

    function sltrEscapeDataId(value) {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(value);
        }
        return String(value).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
    }

    function selectPackage(packageButton) {
        if (!packageButton) return;

        selectedPackage = packageButton.getAttribute('data-id');
        selectedPackageMeta = {
            title: packageButton.getAttribute('data-title') || packageButton.querySelector('strong')?.textContent || '',
            duration: packageButton.getAttribute('data-duration') || '',
            price: packageButton.getAttribute('data-price') || '',
            priceRaw: packageButton.getAttribute('data-price-raw') || '',
            mode: packageButton.getAttribute('data-mode') || 'fixed',
            dateFlow: packageButton.getAttribute('data-date-flow') || 'customer_choice',
            policy: packageButton.getAttribute('data-policy') || 'booking_only',
            depositType: packageButton.getAttribute('data-deposit-type') || 'percent',
            depositValue: packageButton.getAttribute('data-deposit-value') || '0',
            included: packageButton.getAttribute('data-included') || '',
            priceMode: packageButton.getAttribute('data-price-mode') || '',
            campaignNote: packageButton.getAttribute('data-campaign-note') || '',
            taxAmount: packageButton.getAttribute('data-tax-amount') || '0',
            taxLabel: packageButton.getAttribute('data-tax-label') || sltrT('Tax'),
            dynamicLabel: packageButton.getAttribute('data-dynamic-label') || '',
            packageDiscountLabel: packageButton.getAttribute('data-package-discount-label') || '',
            lowAvailabilityEnabled: packageButton.getAttribute('data-low-availability-enabled') !== '0',
            lowAvailabilityThreshold: Number(packageButton.getAttribute('data-low-availability-threshold') || 5),
            hidePaymentMethods: packageButton.getAttribute('data-hide-payment-methods') === '1',
            hidePriceOnFrontend: packageButton.getAttribute('data-hide-price-on-frontend') === '1',
            displayStartTimeOnly: packageButton.getAttribute('data-display-start-time-only') === '1',
            fullDayBooking: packageButton.getAttribute('data-full-day-booking') === '1',
            bookingButtonText: packageButton.getAttribute('data-booking-button-text') || sltrT('Book now'),
            hasAvailableCoupon: packageButton.getAttribute('data-has-available-coupon') === '1',
            extras: (function(){
                try {
                    const parsed = JSON.parse(packageButton.getAttribute('data-extra-services') || '[]');
                    return Array.isArray(parsed) ? parsed.filter(function(item){ return item && Number(item.active || 0) === 1; }) : [];
                } catch (e) { return []; }
            })()
        };
        resetFromPackage();
        document.querySelectorAll('.sltr-package').forEach(function (btn) {
            btn.classList.remove('is-selected');
        });
        packageButton.classList.add('is-selected');
        renderDateRangePayment(selectedPackageMeta.policy);
        const detailsBackButton = document.getElementById('sltr-details-back');
        if (detailsBackButton) {
            detailsBackButton.style.display = selectedPackageMeta.mode === 'simple' ? 'none' : '';
        }
        if (selectedPackageMeta.mode === 'simple') {
            const dateInput = document.getElementById('sltr-date');
            if (dateInput) dateInput.value = formatDate(new Date());
            selectedSlot = { start: '00:00:00', end: '00:00:00' };
            selectedUnit = null;
            renderSimpleExtras();
            requestSimpleQuote();
            renderDateRangePayment(selectedPackageMeta ? selectedPackageMeta.policy : 'booking_only');
            updateSummary();
            showStep(4);
            return;
        }
        showStep(2);
        const rangeFields = document.getElementById('sltr-date-range-fields');
        const calendar = document.getElementById('sltr-calendar');
        const dateTitle = document.getElementById('sltr-step-date-title');
        const optionTitle = document.getElementById('sltr-step-time-title');
        if (selectedPackageMeta.mode === 'date_range_inventory') {
            if (calendar) calendar.style.display = 'none';
            if (selectedPackageMeta.dateFlow === 'admin_scheduled') {
                if (dateTitle) dateTitle.textContent = sltrT('Available dates');
                if (optionTitle) optionTitle.textContent = sltrT('Choose your date');
                if (rangeFields) rangeFields.style.display = 'none';
                loadScheduledEvents();
            } else {
                if (dateTitle) dateTitle.textContent = sltrT('Choose dates');
                if (optionTitle) optionTitle.textContent = sltrT('Choose your option');
                if (rangeFields) rangeFields.style.display = 'flex';
                setMessage('sltr-date-message', sltrT('Choose check-in and check-out dates.'), 'info');
            }
        } else {
            if (dateTitle) dateTitle.textContent = sltrT('Select date');
            if (optionTitle) optionTitle.textContent = selectedPackageMeta.fullDayBooking ? sltrT('Select end date') : sltrT('Select time');
            if (rangeFields) rangeFields.style.display = 'none';
            if (calendar) calendar.style.display = '';
            loadAvailableDates();
        }
    }


    function translateStaticFrontendText(root) {
        root = root || document;
        const attrMap = [
            ['#sltr-submit', 'data-default-label'],
            ['#sltr-submit', 'data-payment-label'],
            ['#sltr-submit', 'data-prepayment-label'],
            ['.sltr-calendar-prev', 'aria-label'],
            ['.sltr-calendar-next', 'aria-label']
        ];
        attrMap.forEach(function (item) {
            root.querySelectorAll(item[0]).forEach(function (el) {
                const value = el.getAttribute(item[1]);
                if (value) el.setAttribute(item[1], sltrT(value));
            });
        });
        root.querySelectorAll('button, .sltr-option-cta, .sltr-payment-section-title').forEach(function (el) {
            const original = (el.textContent || '').trim();
            if (!original) return;
            const translated = sltrT(original);
            if (translated !== original) el.textContent = translated;
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        translateStaticFrontendText(document);
        renderCalendar();

        const params = new URLSearchParams(window.location.search);
        if (params.get('sltr_step') === 'calendar' && params.get('sltr_package_id')) {
            const packageId = sltrEscapeDataId(params.get('sltr_package_id'));
            const packageButton = document.querySelector('#sltr-booking .sltr-package[data-id="' + packageId + '"]') || document.querySelector('#sltr-booking .sltr-package');
            if (packageButton) {
                selectPackage(packageButton);
            }
        }
        const fixedEndDate = document.getElementById('sltr-fixed-end-date');
        if (fixedEndDate) {
            fixedEndDate.addEventListener('change', updateFixedFullDayTotal);
        }
        const paymentChoice = document.getElementById('sltr-payment-choice');
        if (paymentChoice) {
            paymentChoice.addEventListener('change', function(){ setPaymentChoice(paymentChoice.value || activePaymentChoice()); });
        }
    });

    document.addEventListener('click', function (event) {
        const packageSelect = event.target.closest('.sltr-package-select');
        if (packageSelect) {
            const packageButton = packageSelect.closest('.sltr-package');
            selectPackage(packageButton);
            return;
        }

        const checkRange = event.target.closest('#sltr-check-range');
        if (checkRange) { loadDateRangeUnits(); return; }

        const unitButton = event.target.closest('.sltr-unit-option');
        if (unitButton) {
            document.querySelectorAll('.sltr-unit-option').forEach(function(btn){ btn.classList.remove('is-selected'); });
            unitButton.classList.add('is-selected');
            let eventData = null;
            const rawEvent = unitButton.getAttribute('data-event-json');
            if (rawEvent) { try { eventData = JSON.parse(rawEvent); } catch(e) { eventData = null; } }
            selectedUnit = {
                id: unitButton.getAttribute('data-unit-id'),
                name: unitButton.getAttribute('data-unit-name'),
                event: eventData,
                totalAmount: unitButton.getAttribute('data-total-amount') || '',
                baseAmount: unitButton.getAttribute('data-base-amount') || '',
                unitPrice: unitButton.getAttribute('data-unit-price') || '',
                priceUnit: unitButton.getAttribute('data-price-unit') || '',
                nights: unitButton.getAttribute('data-nights') || '',
                days: unitButton.getAttribute('data-days') || '',
                summary: unitButton.getAttribute('data-summary') || '',
                taxAmount: unitButton.getAttribute('data-tax-amount') || '',
                taxLabel: unitButton.getAttribute('data-tax-label') || '',
                dynamicLabel: unitButton.getAttribute('data-dynamic-label') || ''
            };
            if (selectedPackageMeta && selectedPackageMeta.mode === 'date_range_inventory' && selectedUnit.summary) {
                selectedPackageMeta.price = selectedUnit.summary;
            }
            // Payment cards are initially rendered before an event is selected. Re-render them
            // now so deposit/full-payment amounts use the selected event quote instead of 0.00.
            if (selectedPackageMeta && selectedPackageMeta.mode === 'date_range_inventory') {
                renderDateRangePayment(selectedPackageMeta.policy || 'booking_only');
            }
            if (eventData) {
                const hiddenDate = document.getElementById('sltr-date');
                const rangeEnd = document.getElementById('sltr-range-end');
        const urgency = document.getElementById('sltr-summary-urgency');
                if (hiddenDate) hiddenDate.value = eventData.start_date || '';
                if (rangeEnd) rangeEnd.value = eventData.end_date || '';
                selectedSlot = { start: eventData.start_time || '00:00:00', end: eventData.end_time || '23:59:59' };
            } else {
                selectedSlot = { start: '00:00:00', end: '23:59:59' };
            }
            updateSummary();
            showStep(4);
            setTimeout(updateSummary, 0);
            return;
        }

        const prev = event.target.closest('.sltr-calendar-prev');
        if (prev) {
            calendarDate = new Date(calendarDate.getFullYear(), calendarDate.getMonth() - 1, 1);
            loadAvailableDates();
            return;
        }

        const next = event.target.closest('.sltr-calendar-next');
        if (next) {
            calendarDate = new Date(calendarDate.getFullYear(), calendarDate.getMonth() + 1, 1);
            loadAvailableDates();
            return;
        }

        const dayButton = event.target.closest('.sltr-calendar-day');
        if (dayButton && !dayButton.disabled) {
            document.querySelectorAll('.sltr-calendar-day').forEach(function (btn) {
                btn.classList.remove('is-selected');
            });
            dayButton.classList.add('is-selected');
            const selectedDate = dayButton.getAttribute('data-date');
            if (selectedPackageMeta && selectedPackageMeta.fullDayBooking) {
                const hiddenDate = document.getElementById('sltr-date');
                const slots = document.getElementById('sltr-slots');
                const optionTitle = document.getElementById('sltr-step-time-title');
                if (hiddenDate) hiddenDate.value = selectedDate;
                if (slots) slots.innerHTML = '';
                selectedSlot = { start: '00:00:00', end: '00:00:00' };
                if (optionTitle) optionTitle.textContent = sltrT('Select end date');
                prepareFixedFullDayEndDate();
                updateSummary();
                showStep(3);
                return;
            }
            loadSlots(selectedDate);
            return;
        }

        const slotButton = event.target.closest('.sltr-slot');
        if (slotButton) {
            document.querySelectorAll('.sltr-slot').forEach(function (btn) {
                btn.classList.remove('is-selected');
            });
            slotButton.classList.add('is-selected');
            selectedSlot = {
                start: slotButton.getAttribute('data-start'),
                end: slotButton.getAttribute('data-end')
            };
            if (selectedPackageMeta && selectedPackageMeta.fullDayBooking) {
                selectedSlot.end = selectedSlot.start;
                setMessage('sltr-message', '', 'info');
                prepareFixedFullDayEndDate();
                updateSummary();
                return;
            }
            setMessage('sltr-message', '', 'info');
            updateSummary();
            showStep(4);
            const firstField = document.querySelector('[data-field-key]');
            if (firstField) setTimeout(function () { firstField.focus(); }, 250);
            return;
        }

        const fullDayContinue = event.target.closest('#sltr-fixed-full-day-continue');
        if (fullDayContinue) {
            const endEl = document.getElementById('sltr-fixed-end-date');
            const startEl = document.getElementById('sltr-date');
            if (!startEl || !startEl.value || !endEl || !endEl.value || endEl.value <= startEl.value) {
                setMessage('sltr-slot-message', sltrT('Please choose a valid end date.'), 'error');
                return;
            }
            updateFixedFullDayTotal();
            showStep(4);
            const firstField = document.querySelector('[data-field-key]');
            if (firstField) setTimeout(function () { firstField.focus(); }, 250);
            return;
        }

        const applyCoupon = event.target.closest('#sltr-apply-coupon');
        if (applyCoupon) {
            const input = document.getElementById('sltr-coupon-code');
            const email = document.getElementById('sltr-email');
            const code = input ? String(input.value || '').trim() : '';
            if (!selectedPackage || !code) {
                clearCoupon();
                setMessage('sltr-coupon-message', sltrT('Enter a coupon code first.'), 'error');
                return;
            }
            setButtonBusy(applyCoupon, true, sltrT('Checking...'));
            setMessage('sltr-coupon-message', sltrT('Checking coupon...'), 'info');
            fetch(sltr_ajax.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: selectedPackageMeta && selectedPackageMeta.mode === 'simple' ? 'sltr_quote_simple_booking' : 'sltr_validate_coupon',
                    nonce: sltr_ajax.nonce,
                    package_id: selectedPackage,
                    coupon_code: code,
                    email: email ? email.value : '',
                    extra_ids: selectedExtras.join(','),
                    booking_days: selectedPackageMeta && selectedPackageMeta.fullDayBooking ? fixedFullDayDays() : 1
                })
            }).then(function (response) { return response.json(); }).then(function (data) {
                setButtonBusy(applyCoupon, false);
                if (!data.success || !data.data || !data.data.valid) {
                    clearCoupon();
                    if (selectedPackageMeta && selectedPackageMeta.mode === 'simple') requestSimpleQuote();
                    setMessage('sltr-coupon-message', data.data || sltrT('Coupon could not be applied.'), 'error');
                    return;
                }
                appliedCouponCode = data.data.code || code;
                appliedCouponFinalAmount = data.data.final_amount || '';
                if (selectedPackageMeta && selectedPackageMeta.mode === 'simple') simpleQuote = data.data;
                updateCouponSummary(data.data);
                updateSummary();
                renderDateRangePayment(selectedPackageMeta ? selectedPackageMeta.policy : 'booking_only');
                setMessage('sltr-coupon-message', sltrT('Coupon applied.'), 'success');
            }).catch(function () {
                setButtonBusy(applyCoupon, false);
                clearCoupon();
                setMessage('sltr-coupon-message', sltrT('Could not check coupon. Please try again.'), 'error');
            });
            return;
        }

        const backButton = event.target.closest('.sltr-back');
        if (backButton) {
            showStep(backButton.getAttribute('data-back'));
        }
    });


    document.addEventListener('input', function (event) {
        if (event.target && event.target.closest && (event.target.closest('#sltr-extra-services') || event.target.closest('#sltr-details-extra-services'))) {
            const selector = selectedPackageMeta && selectedPackageMeta.mode === 'simple' ? '#sltr-details-extra-services' : '#sltr-extra-services';
            selectedExtras = Array.from(document.querySelectorAll(selector + ' input[type="checkbox"]:checked')).map(function(input){ return input.value; });
            if (selectedPackageMeta && selectedPackageMeta.mode === 'simple') {
                requestSimpleQuote();
            } else {
                updateSummary();
                renderDateRangePayment(selectedPackageMeta ? selectedPackageMeta.policy : 'booking_only');
            }
        }
        if (event.target && event.target.matches && event.target.matches('[data-field-key]')) {
            event.target.classList.remove('sltr-field-invalid');
        }
        if (event.target && event.target.id === 'sltr-coupon-code') {
            appliedCouponCode = '';
            appliedCouponFinalAmount = '';
            updateCouponSummary(null);
            if (selectedPackageMeta && selectedPackageMeta.mode === 'simple') requestSimpleQuote(); else updateSummary();
        }
    });

    document.addEventListener('click', async function (event) {
        if (!event.target || event.target.id !== 'sltr-submit') {
            return;
        }

        const submit = event.target;
        const bookingRoot = document.getElementById('sltr-booking');
        if ((submit.disabled || submit.getAttribute('aria-disabled') === 'true') || (bookingRoot && bookingRoot.getAttribute('data-payment-required-unavailable') === '1')) {
            setMessage('sltr-message', sltrT('Online payment is currently unavailable. Please contact us before booking.'), 'error');
            return;
        }
        const date = document.getElementById('sltr-date');
        const dynamicFields = document.querySelectorAll('[data-field-key]');
        const fieldValues = {};
        let requiredFieldsComplete = true;
        dynamicFields.forEach(function (field) {
            const key = field.getAttribute('data-field-key');
            if (!key) return;
            fieldValues[key] = field.value || '';
            if (field.getAttribute('data-required') === '1' && !String(field.value || '').trim()) {
                requiredFieldsComplete = false;
            }
        });
        const honeypot = document.getElementById('sltr-company-website');
        const formStartedAt = document.getElementById('sltr-form-started-at');
        const marketingConsent = document.getElementById('sltr-marketing-consent');
        const paymentMethod = document.getElementById('sltr-payment-method');
        const prepayMethod = document.getElementById('sltr-prepay-method');
        const paymentContainer = document.querySelector('.sltr-payment-methods');
        const paymentEnabled = paymentContainer && paymentContainer.getAttribute('data-payment-mode-enabled') === '1';
        const prepaymentEnabled = paymentContainer && paymentContainer.getAttribute('data-prepayment-mode-enabled') === '1';
        let paymentMode = (prepaymentEnabled && !paymentEnabled && prepayMethod) ? 'prepay' : ((paymentEnabled && !prepaymentEnabled && paymentMethod) ? 'payment' : 'none');
        let selectedPaymentMethod = paymentMode === 'prepay' ? prepayMethod.value : (paymentMode === 'payment' ? paymentMethod.value : '');
        let paymentChoice = '';
        if (selectedPackageMeta && selectedPackageMeta.mode === 'date_range_inventory') {
            const policy = selectedPackageMeta.policy || 'booking_only';
            const choiceEl = document.getElementById('sltr-payment-choice');
            const gatewayEl = document.getElementById('sltr-date-range-payment-method');
            const choices = paymentChoicesForPolicy(policy);
            paymentChoice = choices.length > 1 && choiceEl ? choiceEl.value : choices[0];
            paymentMode = paymentChoice === 'deposit_payment' ? 'prepay' : (paymentChoice === 'full_payment' ? 'payment' : 'none');
            selectedPaymentMethod = paymentMode === 'none' ? '' : (gatewayEl ? gatewayEl.value : '');
        } else if (selectedPackageMeta) {
            const policy = selectedPackageMeta.policy || 'booking_only';
            const choiceEl = document.getElementById('sltr-payment-choice');
            const gatewayEl = document.getElementById('sltr-date-range-payment-method');
            const choices = paymentChoicesForPolicy(policy);
            paymentChoice = choices.length > 1 && choiceEl ? choiceEl.value : choices[0];
            paymentMode = paymentChoice === 'deposit_payment' ? 'prepay' : (paymentChoice === 'full_payment' ? 'payment' : 'none');
            selectedPaymentMethod = paymentMode === 'none' ? '' : (gatewayEl ? gatewayEl.value : '');
        }
        if (((selectedPackageMeta && paymentMode !== 'none') || (!selectedPackageMeta && (paymentEnabled || prepaymentEnabled))) && !selectedPaymentMethod) {
            setMessage('sltr-message', sltrT('Please choose a payment method.'), 'error');
            return;
        }
        const turnstileResponse = document.querySelector('input[name="cf-turnstile-response"]');
        const recaptchaResponse = document.querySelector('textarea[name="g-recaptcha-response"]');
        let recaptchaV3Response = '';
        if (window.sltr_ajax && sltr_ajax.captcha_provider === 'recaptcha_v3' && typeof window.sltrGetRecaptchaV3Token === 'function') {
            try {
                recaptchaV3Response = await window.sltrGetRecaptchaV3Token('slotera_booking');
            } catch (error) {
                recaptchaV3Response = '';
            }
        }

        if (isSubmitting) return;

        if (!selectedPackage || !requiredFieldsComplete || (selectedPackageMeta && selectedPackageMeta.mode !== 'simple' && (!selectedSlot || !date)) || (selectedPackageMeta && selectedPackageMeta.mode === 'date_range_inventory' && !selectedUnit)) {
            setMessage('sltr-message', sltrT('Please complete all booking steps and required fields.'), 'error');
            if (!requiredFieldsComplete) { focusFirstInvalidField(); }
            return;
        }

        isSubmitting = true;
        setButtonBusy(submit, true, paymentMode === 'prepay' ? sltrT('PrePaying...') : (paymentMode === 'payment' ? sltrT('Paying...') : sltrT('Booking...')));
        setMessage('sltr-message', sltrT('Creating booking...'), 'info');

        fetch(sltr_ajax.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'sltr_create_booking',
                nonce: sltr_ajax.nonce,
                package_id: selectedPackage,
                date: selectedPackageMeta && selectedPackageMeta.mode === 'simple' ? formatDate(new Date()) : date.value,
                start: selectedSlot ? selectedSlot.start : '00:00:00',
                end: selectedSlot ? selectedSlot.end : '00:00:00',
                end_date: (selectedPackageMeta && selectedPackageMeta.fullDayBooking && document.getElementById('sltr-fixed-end-date'))
                    ? document.getElementById('sltr-fixed-end-date').value
                    : (document.getElementById('sltr-range-end') ? document.getElementById('sltr-range-end').value : ''),
                resource_id: selectedUnit ? selectedUnit.id : '',
                name: fieldValues.name || '',
                email: fieldValues.email || '',
                phone: fieldValues.phone || '',
                city: fieldValues.city || '',
                state: fieldValues.state || '',
                address: fieldValues.address || '',
                company: fieldValues.company || '',
                notes: fieldValues.notes || '',
                company_website: honeypot ? honeypot.value : '',
                form_started_at: formStartedAt ? formStartedAt.value : '',
                cf_turnstile_response: turnstileResponse ? turnstileResponse.value : '',
                g_recaptcha_response: recaptchaV3Response || (recaptchaResponse ? recaptchaResponse.value : ''),
                payment_method: selectedPaymentMethod,
                payment_mode: paymentMode,
                payment_choice: paymentChoice,
                extra_ids: selectedExtras.join(','),
                coupon_code: appliedCouponCode,
                marketing_consent: marketingConsent && marketingConsent.checked ? '1' : '0'
            })
        }).then(function (response) {
            return response.json();
        }).then(function (data) {
            if (!data.success) {
                setMessage('sltr-message', data.data || sltrT('Booking failed.'), 'error');
                isSubmitting = false;
                setButtonBusy(submit, false);
                return;
            }
            setMessage('sltr-message', sltrT('Booking successful. Redirecting...'), 'success');
            document.dispatchEvent(new CustomEvent('sltr:booking-created', { detail: data.data || {} }));

            if (data.data && data.data.redirect_url) {
                window.location.href = data.data.redirect_url;
                return;
            }

            isSubmitting = false;
            setButtonBusy(submit, false);
        }).catch(function (error) {
            setMessage('sltr-message', sltrT('Booking failed. Please try again.'), 'error');
            isSubmitting = false;
            setButtonBusy(submit, false);
        });
    });

    document.addEventListener('click', function (event) {
        const option = event.target.closest('[data-sltr-category-target]');
        if (option) {
            const wrapper = option.closest('.sltr-categories-booking');
            const panelId = option.getAttribute('data-sltr-category-target');
            const panel = panelId ? document.getElementById(panelId) : null;
            if (wrapper && panel) {
                wrapper.querySelectorAll('.sltr-category-step, .sltr-category-panel').forEach(function (el) {
                    el.hidden = true;
                });
                panel.hidden = false;
                panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            return;
        }

        const back = event.target.closest('[data-sltr-category-back]');
        if (back) {
            const wrapper = back.closest('.sltr-categories-booking');
            if (wrapper) {
                wrapper.querySelectorAll('.sltr-category-panel').forEach(function (el) { el.hidden = true; });
                const step = wrapper.querySelector('.sltr-category-step');
                if (step) {
                    step.hidden = false;
                    step.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        }
    });


    document.addEventListener('click', function (event) {
        var link = event.target && event.target.closest ? event.target.closest('[data-sltr-google-maps-popup]') : null;
        if (!link) return;
        var url = link.getAttribute('href') || '';
        if (!url) return;
        var popup = window.open(url, 'sltrGoogleMaps', 'width=1100,height=800,resizable=yes,scrollbars=yes');
        if (popup) {
            event.preventDefault();
            try { popup.opener = null; } catch (ignore) {}
            popup.focus();
        }
    });

    document.addEventListener('click', function (event) {
        var button = event.target && event.target.closest ? event.target.closest('[data-sltr-video-unmute]') : null;
        if (!button) return;
        var wrapper = button.closest('.sltr-package-media-video'), video = wrapper ? wrapper.querySelector('video') : null;
        if (!video) return;
        video.muted = false; video.volume = 1; button.hidden = true;
        var promise = video.play();
        if (promise && typeof promise.catch === 'function') promise.catch(function () {});
    });
})();
