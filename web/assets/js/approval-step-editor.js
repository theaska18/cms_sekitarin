(function () {
    function start() {
        var editor = document.querySelector('[data-approval-flow-editor]');
        var list = editor && editor.querySelector('[data-approval-step-list]');
        var template = document.getElementById('approval-step-template');
        if (!editor || !list || !template || !window.jQuery || !window.jQuery.ui) return;
        var $ = window.jQuery;
        var stepNumber = 0;
        var stepLabel = editor.getAttribute('data-step-label') || 'Step';
        var addButton = editor.querySelector('[data-add-approval-step]');
        var disabled = editor.getAttribute('data-step-editor-disabled') === '1';
        var employeeUrl = editor.getAttribute('data-employee-search-url');
        var organizationUrl = editor.getAttribute('data-organization-search-url');

        function updateStep(row, index) {
            var title = row.querySelector('[data-step-title]');
            if (title) title.textContent = stepLabel + ' ' + (index + 1);
            row.querySelectorAll('[data-approver-type]').forEach(function (radio) {
                radio.name = 'steps[' + index + '][approver_type]';
                var legacy = row.querySelector('[data-legacy-user-id]');
                if (radio.value === 'user' && legacy) {
                    legacy.name = 'steps[' + index + '][approver_user_id]';
                }
            });
            var employeeSearch = row.querySelector('[data-employee-search]');
            var employeeId = row.querySelector('[data-employee-id]');
            var employeeLabel = row.querySelector('[data-employee-label]');
            var organizationSearch = row.querySelector('[data-organization-search]');
            var organizationId = row.querySelector('[data-organization-id]');
            var organizationLabel = row.querySelector('[data-organization-label]');
            if (employeeSearch) {
                employeeSearch.name = 'steps[' + index + '][employee_label]';
                employeeSearch.id = 'approval-step-' + index + '-employee';
                if (employeeLabel) employeeLabel.htmlFor = employeeSearch.id;
            }
            if (employeeId) employeeId.name = 'steps[' + index + '][approver_employee_id]';
            if (organizationSearch) {
                organizationSearch.name = 'steps[' + index + '][organization_label]';
                organizationSearch.id = 'approval-step-' + index + '-organization';
                if (organizationLabel) organizationLabel.htmlFor = organizationSearch.id;
            }
            if (organizationId) organizationId.name = 'steps[' + index + '][org_id]';
            if (row.dataset.approverTypeBound !== 'true') {
                row.querySelectorAll('[data-approver-type]').forEach(function (radio) {
                    radio.addEventListener('change', function () { updateTargets(row); });
                });
                row.dataset.approverTypeBound = 'true';
            }
            if (employeeSearch && employeeUrl && !$(employeeSearch).data('ui-autocomplete')) {
                $(employeeSearch).autocomplete({
                    minLength: 2,
                    delay: 250,
                    source: function (request, response) {
                        $.getJSON(employeeUrl, {q: request.term}).done(response).fail(function () { response([]); });
                    },
                    focus: function () { return false; },
                    select: function (event, ui) {
                        employeeSearch.value = ui.item.label;
                        if (employeeId) employeeId.value = ui.item.id;
                        return false;
                    }
                });
                employeeSearch.addEventListener('input', function () { if (employeeId) employeeId.value = ''; });
            }
            if (organizationSearch && organizationUrl && !$(organizationSearch).data('ui-autocomplete')) {
                $(organizationSearch).autocomplete({
                    minLength: 2,
                    delay: 250,
                    source: function (request, response) {
                        $.getJSON(organizationUrl, {q: request.term}).done(response).fail(function () { response([]); });
                    },
                    focus: function () { return false; },
                    select: function (event, ui) {
                        organizationSearch.value = ui.item.label;
                        if (organizationId) organizationId.value = ui.item.id;
                        return false;
                    }
                });
                organizationSearch.addEventListener('input', function () { if (organizationId) organizationId.value = ''; });
            }
            updateTargets(row);
        }

        function updateTargets(row) {
            var selected = row.querySelector('[data-approver-type]:checked');
            var type = selected ? selected.value : 'superior';
            var employeeTarget = row.querySelector('[data-employee-target]');
            var organizationTarget = row.querySelector('[data-organization-target]');
            if (employeeTarget) employeeTarget.hidden = type !== 'employee';
            if (organizationTarget) organizationTarget.hidden = type !== 'organization';
            var employeeId = row.querySelector('[data-employee-id]');
            var employeeSearch = row.querySelector('[data-employee-search]');
            var organizationId = row.querySelector('[data-organization-id]');
            var organizationSearch = row.querySelector('[data-organization-search]');
            if (type !== 'employee' && employeeId && employeeSearch) {
                employeeId.value = '';
                employeeSearch.value = '';
            }
            if (type !== 'organization' && organizationId && organizationSearch) {
                organizationId.value = '';
                organizationSearch.value = '';
            }
        }

        function renumber() {
            Array.prototype.forEach.call(list.querySelectorAll('[data-approval-step]'), function (row, index) {
                updateStep(row, index);
            });
            stepNumber = list.querySelectorAll('[data-approval-step]').length;
        }

        list.addEventListener('click', function (event) {
            var remove = event.target.closest('[data-remove-approval-step]');
            if (!remove || disabled) return;
            var rows = list.querySelectorAll('[data-approval-step]');
            if (rows.length <= 1) return;
            remove.closest('[data-approval-step]').remove();
            renumber();
        });
        if (addButton) addButton.addEventListener('click', function () {
            if (disabled) return;
            var row = template.content.firstElementChild.cloneNode(true);
            var index = stepNumber++;
            row.querySelector('[data-approver-type][value="superior"]').checked = true;
            list.appendChild(row);
            updateStep(row, index);
        });
        renumber();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
}());
