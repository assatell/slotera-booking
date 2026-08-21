<?php
if (!defined('ABSPATH')) { exit; }

if (!function_exists('sltr_render_package_form_state_script')) {
    function sltr_render_package_form_state_script(): void
    {
        ?>
<script>
(function(){
    'use strict';
    function syncBool(input){
        if(!input){return;}
        var target=null;
        var targetName=input.getAttribute('data-target');
        if(targetName){ target=document.querySelector('input[type="hidden"][name="'+targetName.replace(/"/g,'\\"')+'"]'); }
        if(!target){ var cell=input.closest('td,p,label')||input.parentNode; target=cell?cell.querySelector('input[type="hidden"].sltr-active-value,input[type="hidden"].sltr-bool-hidden'):null; }
        if(target){ target.value=input.checked?'1':'0'; }
    }
    function parts(name){
        var m=name.match(/^mode_config\[([^\]]+)\](.*)$/); if(!m){return null;}
        var out=[m[1]], rest=m[2], r;
        var re=/\[([^\]]*)\]/g;
        while((r=re.exec(rest))!==null){ out.push(r[1]); }
        return out;
    }
    function assign(root,path,value){
        var obj=root;
        for(var i=0;i<path.length;i++){
            var key=path[i];
            var last=i===path.length-1;
            if(key===''){
                if(!Array.isArray(obj)){return;}
                if(last){ obj.push(value); return; }
                var child={}; obj.push(child); obj=child; continue;
            }
            if(last){ obj[key]=value; return; }
            var nextIsArray=path[i+1]==='';
            if(!obj[key] || typeof obj[key] !== 'object'){ obj[key]=nextIsArray?[]:{}; }
            obj=obj[key];
        }
    }
    function write(form){
        var target=form.querySelector('input[name="sltr_package_compact_state"]'); if(!target||!window.JSON){return;}
        var state={simple:{},fixed:{},flex:{},date_range_inventory:{}};
        form.querySelectorAll('[name^="mode_config["]').forEach(function(input){
            if(input.disabled){return;}
            var path=parts(input.name); if(!path||!path.length){return;}
            var type=(input.type||'').toLowerCase();
            if(type==='radio' && !input.checked){return;}
            if(type==='checkbox' && path[path.length-1]===''){
                if(input.checked){ assign(state,path,input.value); }
                return;
            }
            var value=(type==='checkbox')?(input.checked?'1':'0'):input.value;
            assign(state,path,value);
        });
        var simplePriceMode=form.querySelector('[name="mode_config[simple][price_mode]"]');
        if(simplePriceMode){
            if(!state.simple){state.simple={};}
            state.simple.price_mode=simplePriceMode.value||'fixed';
            var stablePriceMode=form.querySelector('input[name="sltr_simple_price_mode"]');
            if(stablePriceMode){stablePriceMode.value=state.simple.price_mode;}
        }
        var confirmInput=form.querySelector('[name="confirm_immediately_simple"][type="checkbox"]');
        if(confirmInput){
            if(!state.simple){state.simple={};}
            state.simple.confirm_immediately=confirmInput.checked?'1':'0';
            var stableConfirm=form.querySelector('input[name="sltr_confirm_immediately_simple"]');
            if(stableConfirm){stableConfirm.value=confirmInput.checked?'1':'0';}
            var canonicalConfirm=form.querySelector('input[name="confirm_immediately_simple"][type="hidden"]');
            if(canonicalConfirm){canonicalConfirm.value=confirmInput.checked?'1':'0';}
        }
        target.value=JSON.stringify(state);
    }
    function scheduleWrite(input){
        var form=input&&input.form?input.form:document.querySelector('form input[name="action"][value="sltr_save_package"]');
        form=form&&form.closest?form.closest('form'):form;
        if(!form){return;}
        form.querySelectorAll('.sltr-active-toggle,.sltr-bool-toggle').forEach(syncBool);
        write(form);
    }
    document.addEventListener('change',function(e){
        if(e.target&&e.target.matches&&e.target.matches('.sltr-active-toggle,.sltr-bool-toggle,input[name^="mode_config["],select[name^="mode_config["],textarea[name^="mode_config["]')){
            if(e.target.matches('.sltr-active-toggle,.sltr-bool-toggle')){syncBool(e.target);}
            scheduleWrite(e.target);
        }
    });
    document.addEventListener('input',function(e){
        if(e.target&&e.target.matches&&e.target.matches('input[name^="mode_config["],textarea[name^="mode_config["]')){scheduleWrite(e.target);}
    });
    function syncBookingType(){
        var select=document.getElementById('sltr-package-booking-type');
        var standard=document.getElementById('sltr-standard-booking-settings');
        var events=document.getElementById('sltr-scheduled-event-settings');
        if(!select){return;}
        var isEvents=select.value==='events';
        if(standard){standard.style.display=isEvents?'none':'';}
        if(events){events.style.display=isEvents?'':'none';}
    }
    function syncEventPackageTitle(){
        var title=document.querySelector('input[name="title"]');
        var label=document.querySelector('[data-sltr-event-package-title]');
        if(title&&label){label.textContent=title.value||'New package';}
    }
    document.addEventListener('change',function(e){
        if(e.target&&e.target.id==='sltr-package-booking-type'){syncBookingType();}
    });
    document.addEventListener('input',function(e){
        if(e.target&&e.target.name==='title'){syncEventPackageTitle();}
    });
    document.addEventListener('DOMContentLoaded',function(){syncBookingType();syncEventPackageTitle();});
    document.addEventListener('DOMContentLoaded',function(){scheduleWrite(null);});
    setTimeout(function(){scheduleWrite(null);},0);
    document.addEventListener('submit',function(e){var form=e.target;if(!form||!form.querySelector('input[name="action"][value="sltr_save_package"]')){return;} form.querySelectorAll('.sltr-active-toggle,.sltr-bool-toggle').forEach(syncBool); write(form);},true);
})();
</script>
        <?php
    }
}
