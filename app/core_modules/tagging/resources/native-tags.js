/* Native tag suggestions. Manual entry remains available when requests fail. */
(function () {
    'use strict';
    function initialise() {
        document.querySelectorAll('input[data-tag-suggestions]').forEach(function (input) {
            if (input.dataset.tagsReady) return;
            input.dataset.tagsReady = 'true';
            var list = document.getElementById(input.getAttribute('list'));
            var timer, request, generation = 0;
            input.addEventListener('input', function () {
                clearTimeout(timer);
                if (request) request.abort();
                var current = ++generation;
                list.replaceChildren();
                var full = input.value;
                var comma = full.lastIndexOf(',');
                var prefix = comma < 0 ? '' : full.slice(0, comma + 1) + ' ';
                var query = full.slice(comma + 1).trim();
                if (!query || query.length > 100) return;
                var chosen = full.slice(0, Math.max(0, comma)).split(',').map(function (tag) {
                    return tag.trim().toLocaleLowerCase();
                });
                timer = setTimeout(async function () {
                    request = new AbortController();
                    try {
                        var url = new URL(input.dataset.tagSuggestions, location.href);
                        if (url.origin !== location.origin) return;
                        url.searchParams.set('tag', query);
                        var response = await fetch(url, {signal: request.signal, credentials: 'same-origin'});
                        if (!response.ok) return;
                        var tags = await response.json();
                        if (current !== generation || input.value !== full || !Array.isArray(tags)) return;
                        tags.slice(0, 20).forEach(function (tag) {
                            if (typeof tag !== 'string' || tag.includes(',') || chosen.includes(tag.toLocaleLowerCase())) return;
                            var option = document.createElement('option');
                            option.value = prefix + tag;
                            list.appendChild(option);
                        });
                    } catch (error) {
                        if (current === generation) list.replaceChildren();
                    }
                }, 200);
            });
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialise);
    else initialise();
}());
