/* Slotera admin package form component. Extracted in v1.0.522. */
/* v1.0.101 date range inventory flow + scheduled events */
(function(){
    function initDateFlowPanels(){
        var select = document.querySelector('.sltr-date-flow-select');
        if (!select) { return; }
        function sync(){
            var value = select.value || 'customer_choice';
            document.querySelectorAll('.sltr-date-flow-panel').forEach(function(panel){
                panel.style.display = panel.classList.contains('sltr-date-flow-' + value) ? '' : 'none';
            });
        }
        select.addEventListener('change', sync);
        sync();
    }
    function initScheduledEventsTable(){
        var table = document.querySelector('.sltr-scheduled-events-table');
        if (!table) { return; }
        var tbody = table.querySelector('tbody');
        var add = document.querySelector('.sltr-add-scheduled-event');
        function nextIndex(){ return tbody ? tbody.querySelectorAll('tr').length : 0; }
        function makeRow(i){
            var tr = document.createElement('tr');
            var base = 'mode_config[date_range_inventory][scheduled_events][' + i + ']';
            tr.innerHTML = '<td><input type="hidden" name="'+base+'[id]" value="'+(Date.now()+i)+'"><input type="text" name="'+base+'[title]" placeholder="' + sltrT('Group tour') + '"></td>'+
                '<td><input type="date" name="'+base+'[start_date]"> <input type="time" name="'+base+'[start_time]"></td>'+
                '<td><input type="date" name="'+base+'[end_date]"> <input type="time" name="'+base+'[end_time]"></td>'+
                '<td><label><input type="checkbox" name="'+base+'[use_time]" value="1"> Yes</label></td>'+
                '<td><input type="number" min="1" max="9999" name="'+base+'[seats]" value="1" style="width:80px;"></td>'+
                '<td><input type="number" step="0.01" min="0" name="'+base+'[price]" value="0" style="width:110px;"></td>'+
                '<td><button type="button" class="button sltr-remove-scheduled-event">Remove</button></td>';
            return tr;
        }
        if (add && tbody) {
            add.addEventListener('click', function(){ tbody.appendChild(makeRow(nextIndex())); });
        }
        table.addEventListener('click', function(event){
            var btn = event.target.closest('.sltr-remove-scheduled-event');
            if (!btn) { return; }
            var rows = tbody ? tbody.querySelectorAll('tr') : [];
            if (rows.length <= 1) {
                btn.closest('tr').querySelectorAll('input').forEach(function(input){ if (input.type === 'checkbox') { input.checked = false; } else { input.value = input.type === 'number' ? (input.name.indexOf('[seats]') !== -1 ? '1' : '0') : ''; } });
                return;
            }
            btn.closest('tr').remove();
        });
    }
    document.addEventListener('DOMContentLoaded', function(){ initDateFlowPanels(); initScheduledEventsTable(); });
})();

/* v1.0.102 visual repeaters for date range customer choice */
(function(){
    function rowCount(tbody){ return tbody ? tbody.querySelectorAll('tr').length : 0; }
    function clearOrRemove(btn, defaults){
        var tr = btn.closest('tr');
        var tbody = tr && tr.parentNode;
        if (!tr || !tbody) { return; }
        if (tbody.querySelectorAll('tr').length <= 1) {
            tr.querySelectorAll('input, select').forEach(function(input){
                if (input.type === 'checkbox') { input.checked = defaults.checkbox !== false; return; }
                if (input.tagName === 'SELECT') { input.value = defaults.select || input.options[0].value; return; }
                if (input.type === 'number') {
                    input.value = input.name.indexOf('[capacity]') !== -1 ? (defaults.capacity || '1') : (input.name.indexOf('[price]') !== -1 || input.name.indexOf('[hourly_price]') !== -1 ? '0' : '');
                    return;
                }
                if (input.classList && input.classList.contains('sltr-active-value')) { input.value = defaults.checkbox !== false ? '1' : '0'; return; }
                if (input.type !== 'hidden') { input.value = ''; }
            });
            return;
        }
        tr.remove();
    }
    function initInventoryUnits(){
        var table = document.querySelector('.sltr-inventory-units-table');
        var add = document.querySelector('.sltr-add-inventory-unit');
        if (!table || !add) { return; }
        var tbody = table.querySelector('tbody');
        add.addEventListener('click', function(){
            var i = rowCount(tbody);
            var base = 'mode_config[date_range_inventory][inventory_units][' + i + ']';
            var tr = document.createElement('tr');
            tr.innerHTML = '<td><input type="hidden" name="'+base+'[id]" value="'+(Date.now()+i)+'"><input type="hidden" class="sltr-active-value" name="'+base+'[active]" value="0"><label><input type="checkbox" class="sltr-active-toggle" data-target="'+base+'[active]" value="1"></label></td>'+
                '<td><input type="text" name="'+base+'[name]" placeholder="Room 1"></td>'+
                '<td><input type="text" name="'+base+'[description]"></td>'+
                '<td><input type="number" min="1" max="9999" name="'+base+'[capacity]" value="1" style="width:80px;"></td>'+
                '<td><input type="number" step="0.01" min="0" name="'+base+'[price]" value="0" style="width:110px;"></td>'+
                '<td><input type="number" step="0.01" min="0" name="'+base+'[hourly_price]" value="0" style="width:110px;"></td>'+
                '<td><button type="button" class="button sltr-remove-row">Remove</button></td>';
            tbody.appendChild(tr);
        });
    }
    function initDateOverrides(){
        var table = document.querySelector('.sltr-date-overrides-table');
        var add = document.querySelector('.sltr-add-date-override');
        if (!table || !add) { return; }
        var tbody = table.querySelector('tbody');
        add.addEventListener('click', function(){
            var i = rowCount(tbody);
            var base = 'mode_config[date_range_inventory][date_inventory_overrides][' + i + ']';
            var tr = document.createElement('tr');
            tr.innerHTML = '<td><input type="date" name="'+base+'[start_date]"></td>'+
                '<td><input type="date" name="'+base+'[end_date]"></td>'+
                '<td><input type="number" min="0" max="9999" name="'+base+'[capacity]" value="0" style="width:100px;"></td>'+
                '<td><input type="number" step="0.01" min="0" name="'+base+'[price]" value="0" style="width:110px;"></td>'+
                '<td><label><input type="checkbox" name="'+base+'[closed]" value="1"></label></td>'+
                '<td><button type="button" class="button sltr-remove-row">Remove</button></td>';
            tbody.appendChild(tr);
        });
    }
    function initExtraServices(){
        var table = document.querySelector('.sltr-extra-services-table');
        var add = document.querySelector('.sltr-add-extra-service');
        if (!table || !add) { return; }
        var tbody = table.querySelector('tbody');
        add.addEventListener('click', function(){
            var i = rowCount(tbody);
            var base = 'mode_config[date_range_inventory][extra_services][' + i + ']';
            var tr = document.createElement('tr');
            tr.innerHTML = '<td><input type="hidden" name="'+base+'[id]" value="'+(Date.now()+i)+'"><input type="hidden" class="sltr-active-value" name="'+base+'[active]" value="0"><label><input type="checkbox" class="sltr-active-toggle" data-target="'+base+'[active]" value="1"></label></td>'+
                '<td><input type="text" name="'+base+'[name]" placeholder="' + sltrT('Airport transfer') + '"></td>'+
                '<td><input type="text" name="'+base+'[description]"></td>'+
                '<td><input type="number" step="0.01" min="0" name="'+base+'[price]" value="0" style="width:110px;"></td>'+
                '<td><select name="'+base+'[price_type]"><option value="once">Once</option><option value="per_day">Per day</option><option value="per_night">Per night</option><option value="per_hour">Per hour</option><option value="per_guest">Per guest</option></select></td>'+
                '<td><button type="button" class="button sltr-remove-row">Remove</button></td>';
            tbody.appendChild(tr);
        });
    }
    document.addEventListener('DOMContentLoaded', function(){
        initInventoryUnits(); initDateOverrides(); initExtraServices();
        function syncBoolToggle(input) {
            if (!input) { return; }
            var target = null;
            var targetName = input.getAttribute('data-target');
            if (targetName) {
                target = document.querySelector('input[type="hidden"][name="' + targetName.replace(/"/g, '\"') + '"]');
            }
            if (!target) {
                var cell = input.closest('td, p, label') || input.parentNode;
                target = cell ? cell.querySelector('input[type="hidden"].sltr-active-value, input[type="hidden"].sltr-bool-hidden') : null;
            }
            if (target) { target.value = input.checked ? '1' : '0'; }
        }
        document.addEventListener('change', function(event){
            var input = event.target;
            if (input && input.classList && (input.classList.contains('sltr-active-toggle') || input.classList.contains('sltr-bool-toggle'))) {
                syncBoolToggle(input);
            }
        });
        document.addEventListener('click', function(event){
            var btn = event.target.closest('.sltr-remove-row');
            if (!btn) { return; }
            clearOrRemove(btn, {checkbox: true, capacity: '1', select: 'once'});
            var row = btn.closest('tr');
            if (row) { row.querySelectorAll('.sltr-active-toggle, .sltr-bool-toggle').forEach(syncBoolToggle); }
        });
        var form = document.querySelector('form input[name="action"][value="sltr_save_package"]');
        form = form ? form.closest('form') : null;
        function sltrModeConfigParts(name) {
            var match = name.match(/^mode_config\[([^\]]+)\](.*)$/);
            if (!match) { return null; }
            var out = [match[1]];
            var rest = match[2];
            var re = /\[([^\]]*)\]/g;
            var part;
            while ((part = re.exec(rest)) !== null) { out.push(part[1]); }
            return out;
        }
        function sltrAssignModeConfig(root, path, value) {
            var obj = root;
            for (var i = 0; i < path.length; i++) {
                var key = path[i];
                var last = i === path.length - 1;
                if (key === '') {
                    if (!Array.isArray(obj)) { return; }
                    if (last) { obj.push(value); return; }
                    var child = {};
                    obj.push(child);
                    obj = child;
                    continue;
                }
                if (last) { obj[key] = value; return; }
                var nextIsArray = path[i + 1] === '';
                if (!obj[key] || typeof obj[key] !== 'object') { obj[key] = nextIsArray ? [] : {}; }
                obj = obj[key];
            }
        }
        function writeCompactPackageState(form) {
            var target = form.querySelector('input[name="sltr_package_compact_state"]');
            if (!target || !window.JSON) { return; }
            var state = {simple: {}, fixed: {}, flex: {}, date_range_inventory: {}};
            form.querySelectorAll('[name^="mode_config["]').forEach(function(input){
                if (input.disabled) { return; }
                var path = sltrModeConfigParts(input.name);
                if (!path || !path.length) { return; }
                var type = (input.type || '').toLowerCase();
                if (type === 'radio' && !input.checked) { return; }
                if (type === 'checkbox' && path[path.length - 1] === '') {
                    if (input.checked) { sltrAssignModeConfig(state, path, input.value); }
                    return;
                }
                var value = type === 'checkbox' ? (input.checked ? '1' : '0') : input.value;
                sltrAssignModeConfig(state, path, value);
            });
            var confirmInput = form.querySelector('[name="confirm_immediately_simple"][type="checkbox"]');
            if (confirmInput) {
                state.simple.confirm_immediately = confirmInput.checked ? '1' : '0';
                var stableConfirm = form.querySelector('input[name="sltr_confirm_immediately_simple"]');
                if (stableConfirm) { stableConfirm.value = confirmInput.checked ? '1' : '0'; }
                var canonicalConfirm = form.querySelector('input[name="confirm_immediately_simple"][type="hidden"]');
                if (canonicalConfirm) { canonicalConfirm.value = confirmInput.checked ? '1' : '0'; }
            }
            target.value = JSON.stringify(state);
        }
        if (form) {
            var refreshCompactState = function(){
                form.querySelectorAll('.sltr-active-toggle, .sltr-bool-toggle').forEach(syncBoolToggle);
                writeCompactPackageState(form);
            };
            form.addEventListener('submit', refreshCompactState);
            form.addEventListener('change', function(event){
                var input = event.target;
                if (input && input.matches && input.matches('.sltr-active-toggle, .sltr-bool-toggle, input[name^="mode_config["], select[name^="mode_config["], textarea[name^="mode_config["]')) { refreshCompactState(); }
            });
            form.addEventListener('input', function(event){
                var input = event.target;
                if (input && input.matches && input.matches('input[name^="mode_config["], textarea[name^="mode_config["]')) { refreshCompactState(); }
            });
            refreshCompactState();
            setTimeout(refreshCompactState, 0);
        }
        document.querySelectorAll('.sltr-active-toggle, .sltr-bool-toggle').forEach(syncBoolToggle);
    });
})();
