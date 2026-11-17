(function () {
    function start() {
        var table = document.querySelector('[data-menu-access-table]');
        if (!table) return;

        var form = table.closest('form');
        var status = document.querySelector('[data-menu-access-status]');
        var writeAvailable = table.getAttribute('data-menu-access-write') === '1';
        var toggleUrl = table.getAttribute('data-menu-access-toggle');
        var datatableUrl = table.getAttribute('data-menu-access-datatable');
        var pageLength = parseInt(table.getAttribute('data-menu-access-page-length'), 10);
        var labels = {
            saving: table.getAttribute('data-menu-access-saving') || '',
            saved: table.getAttribute('data-menu-access-saved') || '',
            failed: table.getAttribute('data-menu-access-save-error') || ''
        };
        var queue = [];
        var inFlight = false;
        var resetTimer = null;

        var canSave = writeAvailable && !!form && !!toggleUrl && !!window.fetch;

        function parseJson(value) {
            if (typeof value !== 'string' || value === '') return null;
            try {
                return JSON.parse(value);
            } catch (error) {
                return null;
            }
        }

        function csrfInput() {
            return form.querySelector('input[type="hidden"]');
        }

        function buildBody(fields) {
            var body = new URLSearchParams();
            var csrf = csrfInput();
            if (csrf && csrf.name) {
                body.set(csrf.name, csrf.value);
            }
            Object.keys(fields).forEach(function (key) {
                body.set(key, fields[key]);
            });

            return body.toString();
        }

        function updateCsrf(data) {
            if (!data || typeof data !== 'object' || !data.csrf) return;
            if (typeof data.csrf.name !== 'string' || typeof data.csrf.hash !== 'string') return;
            form.querySelectorAll('input[type="hidden"]').forEach(function (input) {
                if (input.name === data.csrf.name) {
                    input.value = data.csrf.hash;
                }
            });
        }

        function setStatus(text, isError) {
            if (!status) return;
            status.textContent = text;
            status.classList.toggle('text-rose-700', !!isError);
            status.classList.toggle('text-slate-600', !isError);
            if (resetTimer) {
                window.clearTimeout(resetTimer);
                resetTimer = null;
            }
            if (!isError && text !== '' && queue.length === 0 && !inFlight) {
                resetTimer = window.setTimeout(function () {
                    status.textContent = '';
                }, 2400);
            }
        }

        function accessCheckboxes(row) {
            return row ? row.querySelectorAll('[data-menu-access]') : [];
        }

        function syncAccessState(row, menuChecked) {
            accessCheckboxes(row).forEach(function (accessCheckbox) {
                if (!menuChecked) {
                    accessCheckbox.checked = false;
                }
                accessCheckbox.disabled = !menuChecked || !writeAvailable;
            });
        }

        function snapshotAccesses(row) {
            return Array.prototype.map.call(accessCheckboxes(row), function (accessCheckbox) {
                return accessCheckbox.checked;
            });
        }

        function restoreAccesses(row, snapshot) {
            Array.prototype.forEach.call(accessCheckboxes(row), function (accessCheckbox, index) {
                accessCheckbox.checked = !!snapshot[index];
            });
        }

        function flush() {
            if (inFlight || queue.length === 0) return;
            inFlight = true;
            var task = queue.shift();
            setStatus(labels.saving, false);
            fetch(toggleUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: buildBody(task.fields)
            }).then(function (response) {
                return response.json().then(function (data) {
                    return { ok: response.ok, data: data };
                });
            }).then(function (result) {
                updateCsrf(result.data);
                if (!result.ok || !result.data || result.data.saved !== true) {
                    throw new Error('save failed');
                }
                setStatus(labels.saved, false);
            }).catch(function () {
                task.revert();
                setStatus(labels.failed, true);
            }).then(function () {
                inFlight = false;
                flush();
            });
        }

        function enqueue(fields, revert) {
            queue.push({ fields: fields, revert: revert });
            flush();
        }

        if (canSave) table.addEventListener('change', function (event) {
            var target = event.target;
            if (!target || !target.matches) return;

            if (target.matches('[data-menu-toggle]')) {
                var menuRow = target.closest('[data-menu-row]');
                var previousChecked = !target.checked;
                var accessSnapshot = snapshotAccesses(menuRow);
                syncAccessState(menuRow, target.checked);
                enqueue({
                    menu_code: target.value,
                    checked: target.checked ? '1' : '0'
                }, function () {
                    target.checked = previousChecked;
                    restoreAccesses(menuRow, accessSnapshot);
                    syncAccessState(menuRow, previousChecked);
                });
                return;
            }

            if (target.matches('[data-menu-access]')) {
                var accessRow = target.closest('[data-menu-row]');
                var menuToggle = accessRow ? accessRow.querySelector('[data-menu-toggle]') : null;
                if (!menuToggle) return;
                var previousAccessChecked = !target.checked;
                enqueue({
                    menu_code: menuToggle.value,
                    access_code: target.value,
                    checked: target.checked ? '1' : '0'
                }, function () {
                    target.checked = previousAccessChecked;
                });
            }
        });

        var language = parseJson(table.getAttribute('data-menu-access-language'));
        if (!datatableUrl || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.DataTable) {
            return;
        }

        window.jQuery(table).DataTable({
            processing: true,
            serverSide: true,
            deferRender: true,
            autoWidth: false,
            orderMulti: false,
            pageLength: isNaN(pageLength) ? 25 : pageLength,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[0, 'asc']],
            language: language || {},
            ajax: {
                url: datatableUrl,
                type: 'GET',
                dataSrc: 'data'
            },
            columns: [
                {data: 'group', className: 'whitespace-nowrap px-2 py-3 align-top text-slate-700 sm:px-6 sm:py-4'},
                {data: 'menu', className: 'px-2 py-3 align-top sm:px-6 sm:py-4'},
                {data: 'accesses', orderable: false, className: 'px-2 py-3 align-top sm:px-6 sm:py-4'}
            ]
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
}());
