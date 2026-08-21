(function (window) {
    'use strict';

    const aliases = Object.freeze({ flexible: 'flex' });
    const knownModes = Object.freeze(['simple', 'fixed', 'flex', 'date_range_inventory']);

    function normalize(mode) {
        mode = String(mode || 'simple').toLowerCase();
        mode = aliases[mode] || mode;
        return knownModes.indexOf(mode) !== -1 ? mode : 'simple';
    }

    function paymentChoices(policy) {
        const choices = {
            full_payment: ['full_payment'],
            deposit_payment: ['deposit_payment'],
            full_or_deposit: ['deposit_payment', 'full_payment'],
            booking_or_full: ['full_payment', 'booking_only'],
            booking_or_deposit: ['deposit_payment', 'booking_only'],
            all_options: ['deposit_payment', 'full_payment', 'booking_only']
        };
        return (choices[String(policy || 'booking_only')] || ['booking_only']).slice();
    }

    function defaultPaymentChoice(choices) {
        choices = Array.isArray(choices) ? choices : [];
        if (choices.indexOf('deposit_payment') !== -1) return 'deposit_payment';
        if (choices.indexOf('full_payment') !== -1) return 'full_payment';
        return choices[0] || null;
    }

    window.SloteraBookingModes = Object.freeze({
        normalize: normalize,
        paymentChoices: paymentChoices,
        defaultPaymentChoice: defaultPaymentChoice,
        requiresUnit: function (mode) { return normalize(mode) === 'date_range_inventory'; },
        requiresTimeSelection: function (mode) { return normalize(mode) !== 'simple'; }
    });
}(window));
