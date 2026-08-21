/* Slotera admin package form component. Extracted in v1.0.522. */
(function(){
    function initBookingBlocks(){
        var wrap = document.querySelector('.sltr-booking-blocks');
        var hidden = document.getElementById('sltr-booking-mode');
        if (!wrap || !hidden) { return; }
        var blocks = Array.prototype.slice.call(wrap.querySelectorAll('.sltr-booking-block'));
        function activate(mode){
            hidden.value = mode;
            blocks.forEach(function(block){
                var active = block.getAttribute('data-mode') === mode;
                block.classList.toggle('is-active', active);
                var radio = block.querySelector('input[name="booking_mode_selector"]');
                if (radio) { radio.checked = active; }
            });
        }
        wrap.addEventListener('change', function(event){
            if (event.target && event.target.name === 'booking_mode_selector') {
                activate(event.target.value);
            }
        });
        activate(hidden.value || wrap.getAttribute('data-active-mode') || 'simple');


    }
    function initPaymentMethodExclusivity(){
        document.addEventListener('change', function(event){
            var target = event.target;
            if (!target || !target.closest) { return; }
            var fieldset = target.closest('.sltr-payment-policy-options');
            if (!fieldset) { return; }

            if (target.classList.contains('sltr-hide-payment-methods')) {
                if (target.checked) {
                    fieldset.querySelectorAll('.sltr-payment-option-toggle').forEach(function(option){
                        option.checked = false;
                    });
                }
                return;
            }

            if (target.classList.contains('sltr-payment-option-toggle') && target.checked) {
                var hideToggle = fieldset.querySelector('.sltr-hide-payment-methods');
                if (hideToggle) {
                    hideToggle.checked = false;
                }
            }
        });

        document.querySelectorAll('.sltr-payment-policy-options').forEach(function(fieldset){
            var hideToggle = fieldset.querySelector('.sltr-hide-payment-methods');
            if (!hideToggle || !hideToggle.checked) { return; }
            fieldset.querySelectorAll('.sltr-payment-option-toggle').forEach(function(option){
                option.checked = false;
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function(){
        initBookingBlocks();
        initPaymentMethodExclusivity();
    });
})();
