/*
 * Shared Finance list filter panel.
 * Sends date range, status/flag, search, and page size to server-side DataTables.
 */
(function (window, document) {
    'use strict';

    function value(panel, name) {
        var node = panel.querySelector('[data-finance-filter="' + name + '"]');
        return node ? String(node.value || '').trim() : '';
    }

    function setValue(panel, name, next) {
        var node = panel.querySelector('[data-finance-filter="' + name + '"]');
        if (node) {
            node.value = next;
        }
    }

    function init(panel) {
        if (panel.getAttribute('data-finance-filter-ready') === '1') {
            return;
        }
        var target = panel.getAttribute('data-target') || '';
        var table = window.jQuery ? window.jQuery(target) : null;
        if (!table || !table.length || !window.jQuery.fn.DataTable || !window.jQuery.fn.DataTable.isDataTable(table[0])) {
            return;
        }
        panel.setAttribute('data-finance-filter-ready', '1');
        var api = table.DataTable();
        var defaults = {
            start_on: panel.getAttribute('data-default-start') || '',
            end_on: panel.getAttribute('data-default-end') || ''
        };
        var initialDetails = {};
        var detailFields = panel.querySelectorAll('[data-finance-filter-details] [data-finance-filter]');
        Array.prototype.forEach.call(detailFields, function (node) {
            var name = node.getAttribute('data-finance-filter');
            if (name !== 'search' && name !== 'page_size') {
                initialDetails[name] = String(node.value || '');
            }
        });

        function filters() {
            var data = {
                status: value(panel, 'status'),
                flag: value(panel, 'flag')
            };
            Array.prototype.forEach.call(detailFields, function (node) {
                var name = node.getAttribute('data-finance-filter');
                if (name !== 'search' && name !== 'page_size' && name !== 'status' && name !== 'flag') {
                    data[name] = String(node.value || '').trim();
                }
            });
            return data;
        }

        table.on('preXhr.dt', function (event, settings, data) {
            var current = filters();
            Object.keys(current).forEach(function (key) {
                data[key] = current[key];
            });
        });

        var container = table.closest('.dt-container');
        container.find('.dt-layout-row').has('.dt-search, .dt-length').hide();

        var searchTimer = null;
        var search = panel.querySelector('[data-finance-filter="search"]');
        if (search) {
            search.addEventListener('input', function () {
                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(function () {
                    api.search(search.value).draw();
                }, 300);
            });
        }

        var pageSize = panel.querySelector('[data-finance-filter="page_size"]');
        if (pageSize) {
            pageSize.value = String(api.page.len());
            pageSize.addEventListener('change', function () {
                api.page.len(parseInt(pageSize.value, 10) || 10).draw();
            });
        }

        var details = panel.querySelector('[data-finance-filter-details]');
        var toggle = panel.querySelector('[data-finance-filter-toggle]');
        var indicator = panel.querySelector('[data-finance-filter-indicator]');

        function activeDetails() {
            var pageSize = value(panel, 'page_size');
            if (pageSize !== '10') return true;
            var changed = false;
            Array.prototype.forEach.call(detailFields, function (node) {
                var name = node.getAttribute('data-finance-filter');
                if (name === 'search' || name === 'page_size') return;
                if (String(node.value || '') !== String(initialDetails[name] || '')) changed = true;
            });
            return changed;
        }

        function updateIndicator() {
            if (!indicator) return;
            indicator.hidden = !activeDetails();
        }

        function setDetailsOpen(open) {
            if (!details || !toggle) return;
            details.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) {
                var first = details.querySelector('input:not([type="hidden"]), select, button');
                if (first) first.focus();
            }
        }

        if (toggle && details) {
            toggle.addEventListener('click', function () {
                setDetailsOpen(details.hidden);
            });
            details.addEventListener('change', updateIndicator);
            details.addEventListener('input', updateIndicator);
            panel.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !details.hidden) {
                    setDetailsOpen(false);
                    toggle.focus();
                }
            });
        }
        updateIndicator();

        var apply = panel.querySelector('[data-finance-filter-apply]');
        if (apply) {
            apply.addEventListener('click', function () {
                api.ajax.reload();
            });
        }

        var reset = panel.querySelector('[data-finance-filter-reset]');
        if (reset) {
            reset.addEventListener('click', function () {
                Array.prototype.forEach.call(detailFields, function (node) {
                    var name = node.getAttribute('data-finance-filter');
                    if (name === 'search' || name === 'page_size') return;
                    if (name === 'status' || name === 'flag') {
                        node.value = '';
                    } else if (Object.prototype.hasOwnProperty.call(initialDetails, name)) {
                        node.value = initialDetails[name];
                    }
                });
                setValue(panel, 'search', '');
                setValue(panel, 'page_size', '10');
                api.search('');
                api.page.len(10);
                updateIndicator();
                api.ajax.reload();
            });
        }
    }

    function initAll() {
        var panels = document.querySelectorAll('[data-finance-filter-panel]');
        Array.prototype.forEach.call(panels, init);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})(window, document);
