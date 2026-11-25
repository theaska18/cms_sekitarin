<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$is_new = $user_mode === 'new';
$heading = $labels[$is_new ? 'user_form_new' : 'user_form_edit'];
$record = is_array($user_record) ? $user_record : array();
$errors = is_array($user_errors) ? $user_errors : array();
$error_labels = array('tenant_invalid', 'user_code_required', 'user_code_too_long', 'user_code_duplicate', 'sequence_generation', 'sequence_exhausted', 'username_required', 'username_too_long', 'username_duplicate', 'full_name_required', 'full_name_too_long', 'password_required', 'password_too_long', 'role_invalid', 'employee_invalid', 'api_secret_required', 'api_secret_too_long', 'save_error');
?>
<link rel="stylesheet" href="/assets/css/vendor/jquery-ui.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/jquery-ui.min.css') ? filemtime(FCPATH . 'assets/css/vendor/jquery-ui.min.css') : '1' ?>">
<main class="-mx-4 w-[calc(100%+2rem)] px-4 pt-6 pb-0 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 sm:pt-8 sm:pb-0 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500"><ol class="flex items-center gap-2"><li><?= html_escape($labels['application']) ?></li><li aria-hidden="true">/</li><li><a class="hover:text-slate-900" href="/admin/application/user"><?= html_escape($labels['user_heading']) ?></a></li><li aria-hidden="true">/</li><li aria-current="page" class="font-medium text-slate-800"><?= html_escape($heading) ?></li></ol></nav>
    <header class="mb-7"><h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($heading) ?></h1><p class="mt-2 text-base text-slate-600"><?= html_escape($labels['user_form_intro']) ?></p></header>
    <?php if (!$user_backend_available): ?><div role="alert" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><p class="font-semibold"><?= html_escape($labels['user_unavailable']) ?></p><p class="mt-1"><?= html_escape($labels['module_backend_unavailable']) ?></p></div><?php endif; ?>
    <?php if (!$user_write_available): ?><div role="note" class="mb-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"><?= html_escape($labels['user_write_disabled']) ?></div><?php endif; ?>
    <?php if ($errors): ?><div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><p class="font-semibold"><?= html_escape($labels['user_invalid']) ?></p><ul class="mt-2 list-disc space-y-1 pl-5"><?php foreach ($errors as $error): $key = in_array($error, $error_labels, TRUE) ? 'user_' . $error : 'user_save_error'; ?><li><?= html_escape($labels[$key]) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" action="<?= $is_new ? '/admin/application/user/new' : '/admin/application/user/change/' . (int) $user_id ?>" class="space-y-6 rounded-t-2xl rounded-b-none border border-slate-200 bg-white px-5 pt-5 pb-0 shadow-sm sm:px-8 sm:pt-8 sm:pb-0" autocomplete="off">
        <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">
        <?php if (!$is_new): ?><input type="hidden" name="tenant_id" value="<?= (int) $record['tenant_id'] ?>"><?php endif; ?>
        <?php if ($is_new): ?><?php if (!empty($user_can_tenant)): ?><div><label for="tenant_id" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['user_tenant']) ?> <span class="text-rose-600" aria-hidden="true">*</span></label><?php if ($user_tenants): ?><select id="tenant_id" name="tenant_id" required class="min-h-12 w-full rounded-lg border border-slate-300 bg-white px-3 text-base sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"><?php foreach ($user_tenants as $tenant): ?><option value="<?= (int) $tenant['tenant_id'] ?>"<?= (string) $record['tenant_id'] === (string) $tenant['tenant_id'] ? ' selected' : '' ?>><?= html_escape($tenant['tenant_code'] . ' — ' . $tenant['tenant_name']) ?></option><?php endforeach; ?></select><?php else: ?><p role="alert" class="text-sm text-rose-700"><?= html_escape($labels['user_unavailable']) ?></p><?php endif; ?></div><?php else: ?><input type="hidden" name="tenant_id" value="<?= (int) $record['tenant_id'] ?>"><?php endif; ?>
        <div>
            <label for="user_code" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['user_code']) ?><?php if (empty($user_code_auto_numbered)): ?> <span class="text-rose-600" aria-hidden="true">*</span><?php endif; ?></label>
            <input id="user_code" name="user_code" type="text"<?= empty($user_code_auto_numbered) ? ' required' : ' disabled aria-disabled="true"' ?><?php if (!empty($user_field_specs['user_code']['max_length'])): ?> maxlength="<?= (int) $user_field_specs['user_code']['max_length'] ?>"<?php endif; ?> value="<?= html_escape(!empty($user_code_auto_numbered) ? $user_code_preview : $record['user_code']) ?>" aria-describedby="user-code-sequence-help" class="min-h-12 w-full rounded-lg border border-slate-300 px-3 text-base sm:text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500 disabled:opacity-70">
            <p id="user-code-sequence-help" class="mt-1.5 text-sm text-slate-500" <?= empty($user_code_auto_numbered) ? 'hidden' : '' ?>><?= html_escape(!empty($user_code_auto_numbered) ? $labels['user_code_auto_help'] : $labels['user_code_manual_help']) ?></p>
        </div>
        <?php else: ?><div><label for="user_code" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['user_code']) ?></label><input id="user_code" type="text" readonly value="<?= html_escape($record['user_code']) ?>" class="min-h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-base sm:text-sm text-slate-600"><p class="mt-1 text-xs text-slate-500"><?= html_escape($labels['user_tenant']) ?>: <?= html_escape((string) $record['tenant_id']) ?></p></div><?php endif; ?>
        <div><label for="username" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['user_name']) ?> <span class="text-rose-600" aria-hidden="true">*</span></label><input id="username" name="username" type="text" required <?php if (!empty($user_field_specs['username']['max_length'])): ?>maxlength="<?= (int) $user_field_specs['username']['max_length'] ?>"<?php endif; ?> value="<?= html_escape($record['username']) ?>" autocapitalize="none" spellcheck="false" class="min-h-12 w-full rounded-lg border border-slate-300 px-3 text-base sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"></div>
        <?php if (!empty($user_has_chat_flag)): ?><div class="rounded-xl border border-slate-200 p-4"><label for="chat_flag" class="flex min-h-6 items-center gap-3 text-sm font-medium text-slate-800"><input id="chat_flag" name="chat_flag" type="checkbox" value="1"<?= !empty($record['chat_flag']) ? ' checked' : '' ?> class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"><span><?= html_escape($labels['user_chat_flag']) ?></span></label></div><?php endif; ?>
        <div><label for="role_id" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['user_role']) ?> <span class="text-rose-600" aria-hidden="true">*</span></label><select id="role_id" name="role_id" required class="min-h-12 w-full rounded-lg border border-slate-300 bg-white px-3 text-base sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"><option value=""><?= html_escape($labels['user_role_choose']) ?></option><?php foreach ($user_roles as $role): ?><option value="<?= (int) $role['role_id'] ?>"<?= (string) $record['role_id'] === (string) $role['role_id'] ? ' selected' : '' ?>><?= html_escape($role['role_name']) ?></option><?php endforeach; ?></select><?php if (!$user_roles): ?><p class="mt-1 text-xs text-slate-500"><?= html_escape($labels['user_role_invalid']) ?></p><?php endif; ?></div>
        <div><label for="full_name" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['user_full_name']) ?><?= empty($record['employee_flag']) ? ' <span class="text-rose-600" aria-hidden="true">*</span>' : '' ?></label><input id="full_name" name="full_name" type="text"<?= empty($record['employee_flag']) ? ' required' : ' disabled' ?> <?php if (!empty($user_field_specs['full_name']['max_length'])): ?>maxlength="<?= (int) $user_field_specs['full_name']['max_length'] ?>"<?php endif; ?> value="<?= html_escape(!empty($record['employee_flag']) ? (isset($record['employee_name']) ? $record['employee_name'] : '') : (isset($record['full_name']) ? $record['full_name'] : '')) ?>" class="min-h-12 w-full rounded-lg border border-slate-300 px-3 text-base sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"><p class="mt-1 text-xs text-slate-500"><?= html_escape($labels['user_full_name_help']) ?></p></div>
        <div class="rounded-xl border border-slate-200 p-4"><label for="employee_flag" class="flex min-h-6 items-center gap-3 text-sm font-medium text-slate-800"><input id="employee_flag" name="employee_flag" type="checkbox" value="1"<?= !empty($record['employee_flag']) ? ' checked' : '' ?> class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"><span><?= html_escape($labels['user_employee_flag']) ?></span></label><div id="employee-fields" class="mt-4"><label for="employee-search" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['user_employee']) ?></label><div class="relative"><input id="employee-search" type="text" autocomplete="off" aria-autocomplete="list" value="<?= html_escape(isset($record['employee_name']) ? $record['employee_name'] : '') ?>" placeholder="<?= html_escape($labels['user_employee_search']) ?>" class="min-h-12 w-full rounded-lg border border-slate-300 pe-10 ps-3 text-base sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false" class="pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><circle cx="10.8" cy="10.8" r="6.8"/><path stroke-linecap="round" d="m16 16 4.2 4.2"/></svg></div><input id="employee_id" name="employee_id" type="hidden" value="<?= html_escape(isset($record['employee_id']) ? $record['employee_id'] : '') ?>"><p class="mt-1 text-xs text-slate-500"><?= html_escape($labels['user_employee_help']) ?></p></div></div>
        <div class="rounded-xl border border-slate-200 p-4"><label for="access_api_flag" class="flex min-h-6 items-center gap-3 text-sm font-medium text-slate-800"><input id="access_api_flag" name="access_api_flag" type="checkbox" value="1"<?= !empty($record['access_api_flag']) ? ' checked' : '' ?> class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"><span><?= html_escape($labels['user_access_api_flag']) ?></span></label><p class="mt-1 text-xs text-slate-500"><?= html_escape($labels['user_access_api_help']) ?></p><div id="api-secret-fields" class="mt-4"><label for="api_secret" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['user_api_secret']) ?> <span class="text-rose-600" aria-hidden="true">*</span></label><div class="flex flex-col gap-2 sm:flex-row"><input id="api_secret" name="api_secret" type="text" <?php if (!empty($user_field_specs['api_secret']['max_length'])): ?>maxlength="<?= (int) $user_field_specs['api_secret']['max_length'] ?>"<?php endif; ?> value="<?= html_escape(isset($record['api_secret']) ? $record['api_secret'] : '') ?>" autocomplete="off" class="min-h-11 min-w-0 flex-1 rounded-lg border border-slate-300 px-3 font-mono text-base sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"><button id="generate-api-secret" type="button" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v4h4M4.7 8a6 6 0 1 1-.2 4"/></svg><?= html_escape($labels['user_api_generate']) ?></button></div></div></div>
        <?php if ($is_new): ?><div><label for="password" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['user_password']) ?> <span class="text-rose-600" aria-hidden="true">*</span></label><input id="password" name="password" type="password" required <?php if (!empty($user_field_specs['password']['max_length'])): ?>maxlength="<?= (int) $user_field_specs['password']['max_length'] ?>"<?php endif; ?> autocomplete="new-password" class="min-h-12 w-full rounded-lg border border-slate-300 px-3 text-base sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"></div><?php endif; ?>
        <div class="sticky bottom-0 z-20 -mx-5 mt-8 flex items-center justify-between gap-3 border-t border-slate-100 bg-white/10 px-5 pb-3 pt-4 backdrop-blur-sm shadow-none sm:-mx-8 sm:px-8"><a href="/admin/application/user" class="admin-back-link inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12.5 4.5 7 10l5.5 5.5M7 10h10"/></svg><?= html_escape($labels['user_back']) ?></a><button type="submit"<?= !$user_write_available ? ' disabled' : '' ?> class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4 3h10l3 3v11H3V3h1Zm2 0v5h8V3m-8 9h8v5H6v-5Z"/></svg><?= html_escape($labels['user_save']) ?></button></div>
    </form>
</main>
<script>(function(){var form=document.querySelector('main form[method="post"]');if(!form)return;var fields=Array.prototype.slice.call(form.querySelectorAll('input:not([type="hidden"]),select,textarea'));function reflectState(field){var muted=field.disabled||field.readOnly;field.classList.toggle('bg-slate-50',muted);field.classList.toggle('text-slate-500',muted);field.classList.toggle('opacity-70',muted);}fields.forEach(function(field){reflectState(field);if(field.id==='user_code')field.addEventListener('blur',function(){field.value=field.value.toLowerCase().replace(/\s+/g,'');sizeFields();});if(field.id==='username')field.addEventListener('input',function(){var start=field.selectionStart,clean=field.value.toLowerCase().replace(/\s+/g,'');if(field.value!==clean){field.value=clean;field.setSelectionRange(Math.max(0,start-(field.value.length-clean.length)),Math.max(0,start-(field.value.length-clean.length)));}sizeFields();});if(field.id==='full_name')field.addEventListener('blur',function(){field.value=field.value.replace(/\S+/g,function(word){var chars=Array.from(word);chars[0]=chars[0].toLocaleUpperCase();return chars.join('');});sizeFields();});});if(window.MutationObserver){var observer=new MutationObserver(function(records){records.forEach(function(record){reflectState(record.target);});});fields.forEach(function(field){observer.observe(field,{attributes:true,attributeFilter:['disabled','readonly']});});}function sizeFields(){var mobile=window.matchMedia('(max-width: 639px)').matches;fields.forEach(function(field){if(field.tagName!=='INPUT'||field.type!=='text'||field.readOnly||field.disabled||field.id==='employee-search'||field.getAttribute('aria-autocomplete')==='list')return;if(mobile){field.style.width='100%';return;}var canvas=document.createElement('canvas'),ctx=canvas.getContext('2d'),style=window.getComputedStyle(field);ctx.font=style.font;var text=field.value||field.placeholder||'';var px=Math.min(672,Math.max(192,ctx.measureText(text).width+32));field.style.width=px+'px';field.style.maxWidth='100%';});}fields.forEach(function(field){field.addEventListener('input',sizeFields);});window.addEventListener('resize',sizeFields);sizeFields();var field=fields.find(function(field){return field.tagName==='INPUT'&&!field.disabled&&!field.readOnly&&field.type!=='hidden'&&field.getClientRects().length;});if(field){field.focus({preventScroll:true});}})();</script>
<script>(function(){var flag=document.getElementById('access_api_flag'),secret=document.getElementById('api_secret'),generate=document.getElementById('generate-api-secret'),fields=document.getElementById('api-secret-fields');if(!flag||!secret||!generate)return;function toggle(){var on=flag.checked;secret.disabled=!on;secret.required=on;generate.disabled=!on;fields.classList.toggle('opacity-50',!on);if(!on)secret.value='';}flag.addEventListener('change',toggle);generate.addEventListener('click',function(){if(!flag.checked)return;var bytes=new Uint8Array(32);window.crypto.getRandomValues(bytes);secret.value=Array.prototype.map.call(bytes,function(byte){return byte.toString(16).padStart(2,'0');}).join('');secret.dispatchEvent(new Event('input',{bubbles:true}));});toggle();})();</script>
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/vendor/jquery-ui.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery-ui.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery-ui.min.js') : '1' ?>"></script>
<script>
(function () {
    var tenant = document.getElementById('tenant_id');
    var flag = document.getElementById('employee_flag');
    var fields = document.getElementById('employee-fields');
    var search = document.getElementById('employee-search');
    var hidden = document.getElementById('employee_id');
    var roles = document.getElementById('role_id');
    var fullName = document.getElementById('full_name');
    if (!flag || !fields || !search || !hidden || !fullName || !window.jQuery || !window.jQuery.ui) { return; }
    var $ = window.jQuery;

    function toggle() {
        var enabled = flag.checked;
        search.disabled = !enabled;
        search.required = enabled;
        fullName.disabled = enabled;
        fullName.required = !enabled;
        if (enabled) { fullName.value = hidden.value ? search.value : ''; }
        fields.classList.toggle('opacity-50', !enabled);
        if (!enabled) {
            hidden.value = '';
            search.value = '';
            if ($(search).data('ui-autocomplete')) { $(search).autocomplete('close'); }
        }
    }
    flag.addEventListener('change', toggle);
    toggle();

    if (tenant) {
        tenant.addEventListener('change', function () {
            roles.innerHTML = '<option value=""><?= html_escape($labels['user_role_choose']) ?></option>';
            hidden.value = '';
            fullName.value = '';
            search.value = '';
            if (!tenant.value) { return; }
            fetch('/admin/application/user/roles/' + encodeURIComponent(tenant.value), {
                headers: {'Accept': 'application/json'}
            }).then(function (response) {
                if (!response.ok) { throw new Error(); }
                return response.json();
            }).then(function (rows) {
                rows.forEach(function (row) {
                    var option = document.createElement('option');
                    option.value = row.role_id;
                    option.textContent = row.role_name;
                    roles.appendChild(option);
                });
            }).catch(function () {});
        });
    }

    $(search).autocomplete({
        minLength: 2,
        delay: 250,
        source: function (request, response) {
            $.getJSON('/admin/application/user/employees', {
                term: request.term,
                tenant_id: tenant ? tenant.value : '<?= (int) (isset($record['tenant_id']) ? $record['tenant_id'] : 0) ?>',
                user_id: '<?= (int) (isset($record['user_id']) ? $record['user_id'] : 0) ?>'
            }).done(function (rows) {
                response(rows.map(function (row) {
                    return {
                        label: row.employee_code + ' — ' + row.employee_name,
                        value: row.employee_name,
                        id: row.employee_id
                    };
                }));
            }).fail(function () { response([]); });
        },
        focus: function () { return false; },
        select: function (event, ui) {
            hidden.value = ui.item.id;
            search.value = ui.item.value;
            fullName.value = ui.item.value;
            return false;
        }
    });
    search.addEventListener('input', function () {
        if (hidden.value) {
            hidden.value = '';
            fullName.value = '';
        }
    });
}());
</script>

<script>
(function () {
    var field = document.getElementById('user_code');
    var tenant = document.getElementById('tenant_id');
    var help = document.getElementById('user-code-sequence-help');
    if (!field || !tenant || !help || <?= $is_new ? 'false' : 'true' ?>) return;
    var messages = {
        automatic: <?= json_encode($labels['user_code_auto_help'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        manual: <?= json_encode($labels['user_code_manual_help'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        unavailable: <?= json_encode($labels['user_sequence_generation'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
    };
    function applyState(state) {
        if (!state || typeof state.automatic !== 'boolean') {
            field.value = '';
            field.disabled = true;
            field.required = false;
            help.hidden = false;
            help.textContent = messages.unavailable;
            return;
        }
        field.disabled = state.automatic;
        field.required = !state.automatic;
        field.value = state.automatic ? String(state.preview || '') : '';
        help.hidden = false;
        help.textContent = state.automatic ? messages.automatic : messages.manual;
    }
    tenant.addEventListener('change', function () {
        if (!tenant.value) {
            applyState({automatic: false, preview: ''});
            return;
        }
        field.disabled = true;
        field.required = false;
        fetch('/admin/application/user/sequence_config/' + encodeURIComponent(tenant.value), {
            headers: {'Accept': 'application/json'}
        }).then(function (response) {
            if (!response.ok) throw new Error('Sequence settings unavailable');
            return response.json();
        }).then(applyState).catch(function () { applyState(null); });
    });
}());
</script>
<script>
(function () {
    var tenant = document.getElementById('tenant_id');
    var code = document.getElementById('user_code');
    var help = document.getElementById('user-code-sequence-help');
    if (!tenant || tenant.tagName !== 'SELECT' || !code || !help || <?= $is_new ? 'false' : 'true' ?>) return;
    var messages = {
        automatic: <?= json_encode($labels['user_code_auto_help'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        manual: <?= json_encode($labels['user_code_manual_help'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        unavailable: <?= json_encode($labels['user_sequence_generation'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
    };
    function applyState(state) {
        if (!state || typeof state.automatic !== 'boolean') {
            code.value = '';
            code.disabled = true;
            code.required = false;
            help.hidden = false;
            help.textContent = messages.unavailable;
            return;
        }
        code.disabled = state.automatic;
        code.required = !state.automatic;
        code.value = state.automatic ? String(state.preview || '') : '';
        help.hidden = false;
        help.textContent = state.automatic ? messages.automatic : messages.manual;
    }
    tenant.addEventListener('change', function () {
        if (!tenant.value) {
            applyState({automatic: false, preview: ''});
            return;
        }
        code.value = '';
        code.disabled = true;
        code.required = false;
        fetch('/admin/application/user/sequence_config/' + encodeURIComponent(tenant.value), {
            headers: {'Accept': 'application/json'}
        }).then(function (response) {
            if (!response.ok) throw new Error('Sequence configuration is unavailable');
            return response.json();
        }).then(applyState).catch(function () { applyState(null); });
    });
}());
</script>
