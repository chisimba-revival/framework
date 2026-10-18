/* Change search scope without losing either set of search selections. */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-quota-search-type]').forEach(function (select) {
        function update() {
            const form = select.closest('form');
            form.querySelectorAll('.userform').forEach(el => { el.hidden = select.value === 'context'; });
            form.querySelectorAll('.contextform').forEach(el => { el.hidden = select.value !== 'context'; });
        }
        select.addEventListener('change', update);
        update();
    });
});
