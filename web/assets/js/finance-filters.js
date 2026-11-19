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

    function limitDecimal(node) {
        var limit = parseInt(node.getAttribute('data-decimal-limit'), 10);
        if (isNaN(limit) || limit < 0) {
            return;
        }
        var apply = function () {
            var text = String(node.value || '');
            var match = /^(-?)(\d*)([.,]?)(\d*)(.*)$/.exec(text);
            if (!match || !match[3] || match[4].length <= limit) {
                return;
            }
            node.value = match[1] + match[2] + match[3] + match[4].slice(0, limit);
        };
        if (node.getAttribute('data-decimal-limit-ready') === '1') {
            return;
        }
        node.setAttribute('data-decimal-limit-ready', '1');
        node.addEventListener('input', apply);
        node.addEventListener('blur', apply);
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
            search.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                }
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

        panel.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && event.target.closest('[data-finance-filter]')) {
                event.preventDefault();
            }
        });

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

        var exportWrap = panel.querySelector('[data-finance-export]');
        var exportToggle = panel.querySelector('[data-finance-export-toggle]');
        var exportMenu = panel.querySelector('[data-finance-export-menu]');
        function closeExport() {
            if (exportMenu) exportMenu.hidden = true;
            if (exportToggle) exportToggle.setAttribute('aria-expanded', 'false');
        }
        function tableRows() {
            var table = document.querySelector(target);
            if (!table) return null;
            return table;
        }
        function ignoredColumns(table) {
            var ignored = {};
            var header = table.querySelector('thead tr');
            if (!header) return ignored;
            Array.prototype.forEach.call(header.querySelectorAll('th,td'), function (cell, index) {
                if (cell.hasAttribute('data-export-ignore')) ignored[index] = true;
            });
            return ignored;
        }
        function exportCsv() {
            var table = tableRows();
            if (!table) return;
            var ignored = ignoredColumns(table);
            var rows = [];
            Array.prototype.forEach.call(table.querySelectorAll('tr'), function (row) {
                var cells = [];
                Array.prototype.forEach.call(row.querySelectorAll('th,td'), function (cell, index) {
                    if (ignored[index]) return;
                    cells.push('"' + String(cell.innerText || '').replace(/"/g, '""') + '"');
                });
                if (cells.length) rows.push(cells.join(','));
            });
            var blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'finance-export.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(link.href);
        }
        function exportPdf() {
            var table = tableRows();
            if (!table) return;
            var ignored = ignoredColumns(table);
            var clone = table.cloneNode(true);
            Array.prototype.forEach.call(clone.querySelectorAll('tr'), function (row) {
                var cells = Array.prototype.slice.call(row.querySelectorAll('th,td'));
                for (var index = cells.length - 1; index >= 0; index--) {
                    if (ignored[index]) row.removeChild(cells[index]);
                }
            });
            var win = window.open('', '_blank');
            if (!win) return;
            win.document.write('<!doctype html><html><head><title>Finance Export</title><style>body{font-family:Arial,sans-serif;padding:20px}table{border-collapse:collapse;width:100%;font-size:12px}th,td{border:1px solid #ccc;padding:6px;text-align:left}thead{background:#f2f2f2}</style></head><body>' + clone.outerHTML + '</body></html>');
            win.document.close();
            win.focus();
            win.print();
        }
        if (exportToggle && exportMenu) {
            exportToggle.addEventListener('click', function () {
                var open = exportMenu.hidden;
                exportMenu.hidden = !open;
                exportToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            var pdf = panel.querySelector('[data-finance-export-pdf]');
            var excel = panel.querySelector('[data-finance-export-excel]');
            if (pdf) pdf.addEventListener('click', function () { closeExport(); exportPdf(); });
            if (excel) excel.addEventListener('click', function () { closeExport(); exportCsv(); });
            document.addEventListener('click', function (event) {
                if (exportWrap && !exportWrap.contains(event.target)) closeExport();
            });
        }

        if (panel.getAttribute('data-local-date-adjusted') === '1') {
            window.setTimeout(function () {
                api.ajax.reload(null, false);
            }, 0);
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
        Array.prototype.forEach.call(document.querySelectorAll('[data-decimal-limit]'), limitDecimal);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})(window, document);
