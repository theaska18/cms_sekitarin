(function () {
    function initialize() {
        var form = document.querySelector('[data-hierarchy-member-form]');
        if (!form) return;

        var endpoint = form.getAttribute('data-employee-search-url');
        var labels = {
            searching: form.dataset.searchingLabel,
            searchFailed: form.dataset.searchFailedLabel,
            noMatches: form.dataset.noMatchesLabel,
            resultCount: form.dataset.resultCountTemplate,
            selected: form.dataset.selectedTemplate,
            minimum: form.dataset.minimumLabel
        };
        var requestTimers = new WeakMap();
        var activeRequests = new WeakMap();

        function format(template, value) {
            return String(template || '').replace('%s', value);
        }

        function closeList(input) {
            var listbox = document.getElementById(input.getAttribute('aria-controls'));
            if (listbox) {
                listbox.innerHTML = '';
                listbox.classList.add('hidden');
            }
            input.setAttribute('aria-expanded', 'false');
        }

        function setSelected(input, selected) {
            var parent = input.parentNode;
            var button = parent.querySelector('[data-hierarchy-clear]');
            if (!button) {
                if (parent.style.position !== 'relative') parent.style.position = 'relative';
                button = document.createElement('button');
                button.type = 'button';
                button.setAttribute('data-hierarchy-clear', '1');
                button.setAttribute('aria-label', 'Clear selection');
                button.setAttribute('title', 'Clear selection');
                button.innerHTML = '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" style="width:1rem;height:1rem"><path stroke-linecap="round" d="m5 5 10 10M15 5 5 15"/></svg>';
                button.style.cssText = 'position:absolute;inset-inline-end:.65rem;top:50%;transform:translateY(-50%);display:none;width:1.25rem;height:1.25rem;align-items:center;justify-content:center;border:0;border-radius:9999px;background:transparent;color:#64748b;cursor:pointer;z-index:2';
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    input.value = '';
                    var target = document.getElementById(input.getAttribute('data-employee-id-target'));
                    if (target) target.value = '';
                    closeList(input);
                    setSelected(input, false);
                    input.focus();
                    input.dispatchEvent(new Event('input', {bubbles: true}));
                });
                parent.appendChild(button);
            }
            input.readOnly = selected;
            input.setAttribute('aria-readonly', selected ? 'true' : 'false');
            input.style.paddingInlineEnd = selected ? '2.5rem' : '';
            button.style.display = selected ? 'inline-flex' : 'none';
        }

        function selectItem(input, item) {
            var idField = document.getElementById(input.getAttribute('data-employee-id-target'));
            var status = input.parentNode.querySelector('[data-employee-status]');
            input.value = item.label;
            if (idField) idField.value = item.id;
            if (status) status.textContent = format(labels.selected, item.label);
            closeList(input);
            setSelected(input, true);
        }

        function render(input, items) {
            var listbox = document.getElementById(input.getAttribute('aria-controls'));
            var status = input.parentNode.querySelector('[data-employee-status]');
            if (!listbox) return;
            listbox.innerHTML = '';
            items.forEach(function (item) {
                var option = document.createElement('li');
                option.setAttribute('role', 'option');
                option.setAttribute('aria-selected', 'false');
                option.setAttribute('data-employee-id', item.id);
                option.className = 'cursor-pointer px-3 py-2 text-sm text-slate-800 hover:bg-indigo-50';
                option.textContent = item.label;
                option.addEventListener('mousedown', function (event) { event.preventDefault(); });
                option.addEventListener('click', function () { selectItem(input, item); });
                listbox.appendChild(option);
            });
            listbox.classList.toggle('hidden', items.length === 0);
            input.setAttribute('aria-expanded', items.length ? 'true' : 'false');
            if (status) status.textContent = items.length ? format(labels.resultCount, items.length) : labels.noMatches;
        }

        form.addEventListener('input', function (event) {
            var input = event.target.closest('[data-employee-autocomplete]');
            if (!input) return;
            var idField = document.getElementById(input.getAttribute('data-employee-id-target'));
            var status = input.parentNode.querySelector('[data-employee-status]');
            if (idField) idField.value = '';
            closeList(input);
            setSelected(input, false);
            var term = input.value.trim();
            if (term.length < 2) {
                if (status) status.textContent = labels.minimum;
                return;
            }

            if (requestTimers.has(input)) window.clearTimeout(requestTimers.get(input));
            if (activeRequests.has(input)) activeRequests.get(input).abort();
            if (status) status.textContent = labels.searching;
            requestTimers.set(input, window.setTimeout(function () {
                var controller = new AbortController();
                activeRequests.set(input, controller);
                var url = new URL(endpoint, window.location.href);
                url.searchParams.set('q', term);
                fetch(url.toString(), {credentials: 'same-origin', headers: {'Accept': 'application/json'}, signal: controller.signal})
                    .then(function (response) {
                        if (!response.ok) throw new Error('Employee search failed');
                        return response.json();
                    })
                    .then(function (items) {
                        if (input.value.trim() === term) render(input, Array.isArray(items) ? items : []);
                    })
                    .catch(function (error) {
                        if (error.name !== 'AbortError' && input.value.trim() === term && status) status.textContent = labels.searchFailed;
                    });
            }, 250));
        });

        form.addEventListener('keydown', function (event) {
            var input = event.target.closest('[data-employee-autocomplete]');
            if (!input) return;
            var listbox = document.getElementById(input.getAttribute('aria-controls'));
            if (!listbox || listbox.classList.contains('hidden')) return;
            var options = Array.prototype.slice.call(listbox.querySelectorAll('[role="option"]'));
            if (!options.length) return;
            var current = options.findIndex(function (option) { return option.getAttribute('aria-selected') === 'true'; });
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                current = event.key === 'ArrowDown' ? (current + 1) % options.length : (current <= 0 ? options.length - 1 : current - 1);
                options.forEach(function (option, index) {
                    option.setAttribute('aria-selected', String(index === current));
                    option.classList.toggle('bg-indigo-50', index === current);
                    if (index === current) option.scrollIntoView({block: 'nearest'});
                });
            } else if (event.key === 'Enter' && current >= 0) {
                event.preventDefault();
                selectItem(input, {
                    id: options[current].getAttribute('data-employee-id'),
                    label: options[current].textContent
                });
            } else if (event.key === 'Escape') {
                closeList(input);
            }
        });

        Array.prototype.forEach.call(form.querySelectorAll('[data-employee-autocomplete]'), function (input) {
            var target = document.getElementById(input.getAttribute('data-employee-id-target'));
            if (input.value.trim() !== '' && target && target.value.trim() !== '') setSelected(input, true);
            else setSelected(input, false);
        });

        form.addEventListener('focusout', function (event) {
            var input = event.target.closest('[data-employee-autocomplete]');
            if (input) window.setTimeout(function () { if (document.activeElement !== input) closeList(input); }, 150);
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
    else initialize();
})();
