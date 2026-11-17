<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$template_record = isset($approval_flow_template_record) && is_array($approval_flow_template_record) ? $approval_flow_template_record : array();
$template_steps = isset($approval_flow_template_steps) && is_array($approval_flow_template_steps) ? $approval_flow_template_steps : array();
$template_errors = isset($approval_flow_template_errors) && is_array($approval_flow_template_errors) ? $approval_flow_template_errors : array();
$template_menu_labels = isset($approval_flow_template_menu_labels) && is_array($approval_flow_template_menu_labels) ? $approval_flow_template_menu_labels : array();
$template_field_specs = isset($approval_flow_template_field_specs) && is_array($approval_flow_template_field_specs) ? $approval_flow_template_field_specs : array();
$template_is_edit = !empty($approval_flow_template_is_edit);
$template_backend_available = !empty($approval_flow_template_backend_available);
$template_write_available = !empty($approval_flow_template_write_available);
$template_disabled = !$template_backend_available || !$template_write_available;
$template_title = $template_is_edit
    ? $labels['approval_flow_template_edit_title']
    : $labels['approval_flow_template_new_title'];
$template_control_class = 'min-h-12 w-full rounded-lg border border-slate-300 bg-white px-3 text-base text-slate-900 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500 disabled:opacity-60 read-only:cursor-not-allowed read-only:bg-slate-50 read-only:text-slate-500 read-only:opacity-60 sm:text-sm';
$template_maxlength = function ($field) use ($template_field_specs) {
    $max = isset($template_field_specs[$field]['max_length']) ? (int) $template_field_specs[$field]['max_length'] : 0;
    return $max > 0 ? ' maxlength="' . $max . '"' : '';
};
$template_error_labels = array();
foreach ($template_errors as $template_error) {
    $template_error_key = 'approval_flow_template_' . $template_error;
    if (isset($labels[$template_error_key])) {
        $template_error_labels[] = $labels[$template_error_key];
    }
}
$template_json_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
<main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 lg:px-8"
      data-approval-flow-editor
      data-step-label="<?= html_escape($labels['approval_flow_template_step_title']) ?>"
      data-step-editor-disabled="<?= $template_disabled ? '1' : '0' ?>"
      data-step-editor-allow-empty="1"
      data-employee-search-url="<?= html_escape($approval_flow_template_employee_search_url) ?>"
      data-organization-search-url="<?= html_escape($approval_flow_template_organization_search_url) ?>">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li><?= html_escape($labels['system']) ?></li>
            <li aria-hidden="true">/</li>
            <li><a href="/admin/system/approval_flow_template" class="hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"><?= html_escape($labels['approval_flow_template_heading']) ?></a></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="font-medium text-slate-800"><?= html_escape($template_title) ?></li>
        </ol>
    </nav>

    <header class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($template_title) ?></h1>
        <p class="mt-2 text-base leading-7 text-slate-600"><?= html_escape($labels['approval_flow_template_form_intro']) ?></p>
    </header>

    <?php if (!$template_backend_available): ?>
        <div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p class="font-semibold"><?= html_escape($labels['approval_flow_template_unavailable']) ?></p>
            <p class="mt-1"><?= html_escape($labels['module_backend_unavailable']) ?></p>
        </div>
    <?php endif; ?>
    <?php if (!$template_write_available): ?>
        <div role="note" class="mb-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"><?= html_escape($labels['approval_flow_template_write_disabled']) ?></div>
    <?php endif; ?>
    <?php if ($template_error_labels): ?>
        <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
            <p class="font-semibold"><?= html_escape($labels['approval_flow_template_invalid']) ?></p>
            <ul class="mt-2 list-inside list-disc">
                <?php foreach ($template_error_labels as $template_error_label): ?><li><?= html_escape($template_error_label) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= html_escape($current_path) ?>" method="post" class="w-full rounded-t-2xl rounded-b-none border border-slate-200 bg-white p-4 shadow-sm sm:p-8">
        <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">
        <div class="space-y-6">
            <div>
                <label for="approval-flow-template-code" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['approval_flow_template_code_label']) ?></label>
                <input id="approval-flow-template-code" name="flow_code" type="text" required<?= $template_maxlength('flow_code') ?> value="<?= html_escape(isset($template_record['flow_code']) ? $template_record['flow_code'] : '') ?>" <?= $template_disabled ? 'disabled' : 'autofocus' ?> <?= $template_is_edit ? 'readonly' : '' ?> autocomplete="off" autocapitalize="none" spellcheck="false" data-normalize-code class="<?= $template_control_class ?> sm:max-w-xs">
                <p class="mt-1.5 text-sm text-slate-500"><?= html_escape($labels['approval_flow_template_code_help']) ?></p>
            </div>

            <div>
                <label for="approval-flow-template-name" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['approval_flow_template_name_label']) ?></label>
                <input id="approval-flow-template-name" name="flow_name" type="text" required<?= $template_maxlength('flow_name') ?> value="<?= html_escape(isset($template_record['flow_name']) ? $template_record['flow_name'] : '') ?>" <?= $template_disabled ? 'disabled' : '' ?> autocomplete="off" data-title-case-name class="<?= $template_control_class ?> sm:max-w-xl">
                <p class="mt-1.5 text-sm text-slate-500"><?= html_escape($labels['approval_flow_template_name_help']) ?></p>
            </div>

            <div>
                <label for="approval-flow-template-description" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['approval_flow_template_description_label']) ?></label>
                <textarea id="approval-flow-template-description" name="description" rows="3"<?= $template_maxlength('description') ?> <?= $template_disabled ? 'disabled' : '' ?> class="<?= $template_control_class ?> py-2.5"><?= html_escape(isset($template_record['description']) ? $template_record['description'] : '') ?></textarea>
                <p class="mt-1.5 text-sm text-slate-500"><?= html_escape($labels['approval_flow_template_description_help']) ?></p>
            </div>

            <div class="flex items-start gap-3">
                <input id="approval-flow-template-active" name="active_flag" type="checkbox" value="1" <?= !empty($template_record['active_flag']) ? 'checked' : '' ?> <?= $template_disabled ? 'disabled' : '' ?> class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-50">
                <div>
                    <label for="approval-flow-template-active" class="block text-sm font-semibold text-slate-800"><?= html_escape($labels['approval_flow_template_active_label']) ?></label>
                    <p class="mt-1 text-sm text-slate-500"><?= html_escape($labels['approval_flow_template_active_help']) ?></p>
                </div>
            </div>

            <div>
                <label for="approval-flow-template-menu-search" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['approval_flow_template_menus_label']) ?></label>
                <div id="approval-flow-template-menu-selected" class="mb-2 flex flex-wrap gap-2">
                    <?php foreach ($template_menu_labels as $template_menu_code => $template_menu_label): ?>
                        <span data-menu-token class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 py-1 pl-3 pr-1 text-sm text-indigo-950">
                            <input type="hidden" name="menu_codes[]" value="<?= html_escape($template_menu_code) ?>">
                            <span><?= html_escape($template_menu_label) ?></span>
                            <button type="button" data-remove-menu aria-label="<?= html_escape($labels['approval_flow_template_remove_menu']) ?>" class="flex h-7 w-7 items-center justify-center rounded-full text-indigo-700 hover:bg-indigo-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500" <?= $template_disabled ? 'disabled' : '' ?>>&times;</button>
                        </span>
                    <?php endforeach; ?>
                </div>
                <input id="approval-flow-template-menu-search" type="text" autocomplete="off" placeholder="<?= html_escape($labels['approval_flow_template_menu_search']) ?>" aria-autocomplete="list" aria-describedby="approval-flow-template-menu-help" <?= $template_disabled ? 'disabled' : '' ?> class="<?= $template_control_class ?>">
                <p id="approval-flow-template-menu-help" class="mt-1.5 text-sm text-slate-500"><?= html_escape($labels['approval_flow_template_menu_help']) ?></p>
            </div>

            <?php $this->load->view('admin/approval_flow/_step_editor', array(
                'step_editor_steps' => $template_steps,
                'step_editor_disabled' => $template_disabled,
                'step_editor_allow_empty' => TRUE,
                'step_editor_labels' => array(
                    'heading' => $labels['approval_flow_template_steps_heading'],
                    'help' => $labels['approval_flow_template_steps_help'] . ' ' . $labels['approval_flow_template_steps_auto_approve_help'],
                    'step_title' => $labels['approval_flow_template_step_title'],
                    'approver_type' => $labels['approval_flow_template_approver_type'],
                    'superior' => $labels['approval_flow_template_approver_superior'],
                    'organization' => $labels['approval_flow_template_approver_organization'],
                    'employee' => $labels['approval_flow_template_approver_employee'],
                    'legacy_user' => $labels['approval_flow_template_approver_user'],
                    'remove_step' => $labels['approval_flow_template_remove_step'],
                    'add_step' => $labels['approval_flow_template_add_step'],
                    'choose_organization' => $labels['approval_flow_template_choose_organization'],
                    'search_organization' => $labels['approval_flow_template_search_organization'],
                    'choose_employee' => $labels['approval_flow_template_choose_employee'],
                    'search_employee' => $labels['approval_flow_template_search_employee'],
                ),
            )); ?>
        </div>

        <div data-approval-flow-template-sticky-actions class="sticky bottom-0 z-30 -mx-5 -mb-5 mt-8 flex flex-col-reverse gap-3 border-t border-slate-100 bg-white/10 px-5 pb-3 pt-4 backdrop-blur-sm sm:-mx-8 sm:-mb-8 sm:flex-row sm:justify-between sm:px-8">
            <a href="/admin/system/approval_flow_template" class="admin-back-link inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12.5 4.5 7 10l5.5 5.5"/></svg><?= html_escape($labels['approval_flow_template_back']) ?></a>
            <button type="submit" <?= $template_disabled ? 'disabled aria-disabled="true"' : '' ?> class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-300"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4 3.5h10l2.5 2.5v10.5H3.5v-13H4Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6.5 3.5v4h6v-4M6.5 16v-5h7v5"/></svg><?= html_escape($labels['approval_flow_template_save']) ?></button>
        </div>
    </form>
</main>
<link rel="stylesheet" href="/assets/css/vendor/jquery-ui.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/jquery-ui.min.css') ? filemtime(FCPATH . 'assets/css/vendor/jquery-ui.min.css') : '1' ?>">
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/vendor/jquery-ui.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery-ui.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery-ui.min.js') : '1' ?>"></script>
<script src="/assets/js/approval-step-editor.js?v=<?= is_file(FCPATH . 'assets/js/approval-step-editor.js') ? filemtime(FCPATH . 'assets/js/approval-step-editor.js') : '1' ?>"></script>
<script>
(function () {
    var codeField = document.querySelector('[data-normalize-code]');
    if (codeField && !codeField.readOnly) {
        codeField.addEventListener('blur', function () {
            codeField.value = codeField.value.toLowerCase().replace(/\s+/g, '');
        });
    }
    var nameField = document.querySelector('[data-title-case-name]');
    if (nameField) {
        nameField.addEventListener('blur', function () {
            nameField.value = nameField.value.toLocaleLowerCase().replace(/(^|\s)(\S)/gu, function (match) {
                return match.toLocaleUpperCase();
            });
        });
    }
    var menuSearch = document.getElementById('approval-flow-template-menu-search');
    var menuSelected = document.getElementById('approval-flow-template-menu-selected');
    var appendMenuToken = function (item) {
        if (!menuSelected) return;
        var selected = menuSelected.querySelectorAll('input[name="menu_codes[]"]');
        for (var i = 0; i < selected.length; i++) {
            if (selected[i].value === item.id) return;
        }
        var token = document.createElement('span');
        token.setAttribute('data-menu-token', '');
        token.className = 'inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 py-1 pl-3 pr-1 text-sm text-indigo-950';
        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'menu_codes[]';
        hidden.value = item.id;
        var label = document.createElement('span');
        label.textContent = item.label;
        var remove = document.createElement('button');
        remove.type = 'button';
        remove.setAttribute('data-remove-menu', '');
        remove.setAttribute('aria-label', <?= json_encode($labels['approval_flow_template_remove_menu'], $template_json_flags) ?>);
        remove.className = 'flex h-7 w-7 items-center justify-center rounded-full text-indigo-700 hover:bg-indigo-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500';
        remove.innerHTML = '&times;';
        token.appendChild(hidden);
        token.appendChild(label);
        token.appendChild(remove);
        menuSelected.appendChild(token);
    };
    if (menuSelected) {
        menuSelected.addEventListener('click', function (event) {
            var remove = event.target.closest('[data-remove-menu]');
            if (remove) remove.closest('[data-menu-token]').remove();
        });
    }
    if (menuSearch && menuSelected && !menuSearch.disabled && window.jQuery && window.jQuery.ui) {
        window.jQuery(menuSearch).autocomplete({
            minLength: 2,
            delay: 250,
            source: function (request, response) {
                window.jQuery.getJSON(<?= json_encode($approval_flow_template_menu_lookup_url, $template_json_flags) ?>, {term: request.term})
                    .done(response)
                    .fail(function () { response([]); });
            },
            focus: function () { return false; },
            select: function (event, ui) {
                appendMenuToken(ui.item);
                menuSearch.value = '';
                return false;
            }
        });
        menuSearch.addEventListener('keydown', function (event) {
            if (event.key === 'Backspace' && menuSearch.value === '') {
                var tokens = menuSelected.querySelectorAll('[data-menu-token]');
                if (tokens.length) tokens[tokens.length - 1].remove();
            }
        });
    }
}());
</script>
