/* Slotera admin package form component. Extracted in v1.0.522. */
/* v1.0.114 package admin live price preview + incomplete warnings */
(function(){
    function money(value){
        var n = parseFloat(value || '0');
        if (!isFinite(n)) { n = 0; }
        var rounded = Math.round(n * 100) / 100;
        var str = String(rounded.toFixed(2)).replace(/\.00$/, '').replace(/(\.\d)0$/, '$1');
        return str;
    }
    function field(mode, key){
        return document.querySelector('[name="mode_config[' + mode + '][' + key + ']"]');
    }
    function durationHidden(mode, key){
        return field(mode, key);
    }
    function titleValue(){
        var input = document.getElementById('sltr-package-title');
        return input && input.value.trim() ? input.value.trim() : sltrT('Package title');
    }
    function activePanel(){
        var hidden = document.getElementById('sltr-booking-mode');
        var mode = hidden ? hidden.value : 'simple';
        return document.querySelector('[data-sltr-preview-mode="' + mode + '"]');
    }
    function priceFor(mode){
        if (mode === 'simple') {
            var priceMode = field('simple', 'price_mode');
            var pm = priceMode ? priceMode.value : 'fixed';
            if (pm === 'request') { return sltrT('Price on request'); }
            var p = money(field('simple', 'price') ? field('simple', 'price').value : 0);
            return pm === 'from' ? sltrT('From %s').replace('%s', p) : p;
        }
        if (mode === 'fixed') {
            return money(field('fixed', 'price') ? field('fixed', 'price').value : 0);
        }
        if (mode === 'flex') {
            return money(field('flex', 'price') ? field('flex', 'price').value : 0) + ' / hour';
        }
        var flow = field('date_range_inventory', 'date_flow');
        if (flow && flow.value === 'admin_scheduled') {
            var linkedEventPrice = document.querySelector('[data-sltr-linked-event-price]');
            var eventPrice = document.querySelector('[name^="mode_config[date_range_inventory][scheduled_events]"][name$="[price]"]');
            var value = linkedEventPrice && linkedEventPrice.value !== '' ? linkedEventPrice.value : (eventPrice ? eventPrice.value : 0);
            return money(value) + ' per event';
        }
        var unit = field('date_range_inventory', 'price_unit');
        var unitValue = unit ? unit.value : 'per_night';
        var label = unitValue === 'per_day' ? 'day' : (unitValue === 'per_hour' ? 'hour' : (unitValue === 'per_night' ? 'night' : ''));
        var price = money(field('date_range_inventory', 'price') ? field('date_range_inventory', 'price').value : 0);
        return label ? price + ' / ' + label : price;
    }
    function warningsFor(mode){
        var warnings = [];
        if (mode === 'simple') {
            var pm = field('simple', 'price_mode');
            var price = parseFloat(field('simple', 'price') ? field('simple', 'price').value : '0');
            if ((!pm || pm.value !== 'request') && (!isFinite(price) || price <= 0)) { warnings.push(sltrT('Set a price, or switch Price display to Price on request.')); }
        } else if (mode === 'fixed') {
            var fixedDuration = parseInt(durationHidden('fixed', 'duration_minutes') ? durationHidden('fixed', 'duration_minutes').value : '0', 10);
            var fixedPrice = parseFloat(field('fixed', 'price') ? field('fixed', 'price').value : '0');
            if (!fixedDuration || fixedDuration <= 0) { warnings.push(sltrT('Set a fixed slot duration.')); }
            if (!isFinite(fixedPrice) || fixedPrice <= 0) { warnings.push(sltrT('Set a slot price.')); }
        } else if (mode === 'flex') {
            var step = parseInt(durationHidden('flex', 'slot_step') ? durationHidden('flex', 'slot_step').value : '0', 10);
            var flexPrice = parseFloat(field('flex', 'price') ? field('flex', 'price').value : '0');
            if (!step || step <= 0) { warnings.push(sltrT('Set a duration step.')); }
            if (!isFinite(flexPrice) || flexPrice <= 0) { warnings.push(sltrT('Set an hourly price.')); }
        } else {
            var flow = field('date_range_inventory', 'date_flow');
            if (flow && flow.value === 'admin_scheduled') {
                var ready = false;
                document.querySelectorAll('.sltr-scheduled-events-table tbody tr').forEach(function(row){
                    var sd = row.querySelector('[name$="[start_date]"]');
                    var ed = row.querySelector('[name$="[end_date]"]');
                    var seats = row.querySelector('[name$="[seats]"]');
                    if (sd && sd.value && ed && ed.value && seats && parseInt(seats.value || '0', 10) > 0) { ready = true; }
                });
                if (!ready) { warnings.push(sltrT('Add at least one event with start date, end date and seats.')); }
            } else {
                var hasUnit = false;
                document.querySelectorAll('.sltr-inventory-units-table tbody tr').forEach(function(row){
                    var active = row.querySelector('[name$="[active_checked]"]') || row.querySelector('[name$="[active]"]');
                    var cap = row.querySelector('[name$="[capacity]"]');
                    if ((!active || active.checked) && cap && parseInt(cap.value || '0', 10) > 0) { hasUnit = true; }
                });
                if (!hasUnit) { warnings.push(sltrT('Add at least one active room/unit with capacity.')); }
                var drPrice = parseFloat(field('date_range_inventory', 'price') ? field('date_range_inventory', 'price').value : '0');
                if (!isFinite(drPrice) || drPrice <= 0) { warnings.push(sltrT('Set the base date-range price.')); }
            }
        }
        return warnings;
    }
    function syncDurationHidden(container){
        if (!container) { return; }
        container.querySelectorAll('.sltr-duration-input').forEach(function(group){
            var nums = group.querySelectorAll('input[type="number"]');
            var hidden = group.querySelector('input[type="hidden"]');
            if (nums.length >= 2 && hidden) {
                var h = parseInt(nums[0].value || '0', 10);
                var m = parseInt(nums[1].value || '0', 10);
                hidden.value = String(Math.max(0, (isFinite(h) ? h : 0) * 60 + (isFinite(m) ? m : 0)));
            }
        });
    }
    function renderPanel(panel){
        if (!panel) { return; }
        var mode = panel.getAttribute('data-sltr-preview-mode') || 'simple';
        var title = panel.querySelector('[data-preview-title]');
        var price = panel.querySelector('[data-preview-price]');
        var warnings = panel.querySelector('[data-preview-warnings]');
        if (title) { title.textContent = titleValue(); }
        if (price) { price.textContent = priceFor(mode); }
        var list = warningsFor(mode);
        if (warnings) {
            panel.classList.toggle('is-complete', list.length === 0);
            if (list.length === 0) {
                warnings.innerHTML = '<p class="sltr-admin-ok">✓ This block has the minimum settings needed to publish.</p>';
            } else {
                warnings.innerHTML = '<p><strong>Before publishing:</strong></p><ul>' + list.map(function(item){ return '<li>' + item.replace(/[&<>]/g, function(c){ return { '&':'&amp;', '<':'&lt;', '>':'&gt;' }[c]; }) + '</li>'; }).join('') + '</ul>';
            }
        }
    }
    function refresh(){
        syncDurationHidden(document);
        document.querySelectorAll('[data-sltr-preview-mode]').forEach(renderPanel);
    }
    document.addEventListener('DOMContentLoaded', function(){
        refresh();
        document.addEventListener('input', refresh);
        document.addEventListener('change', refresh);
        var blocks = document.querySelector('.sltr-booking-blocks');
        if (blocks) { blocks.addEventListener('click', function(){ setTimeout(refresh, 0); }); }
    });
})();
