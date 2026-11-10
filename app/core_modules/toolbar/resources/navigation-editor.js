/** Confirm destructive navigation actions without inline handlers. */
document.addEventListener('submit', function (event) {
    const form = event.target;
    if (form instanceof HTMLFormElement && form.dataset.navigationConfirm
        && !window.confirm(form.dataset.navigationConfirm)) event.preventDefault();
});

// Learning links have a fixed heading and are restricted to signed-in users.
const placement = document.getElementById('external-placement');
if (placement) {
    const audience = document.getElementById('external-audience');
    let siteAudience = audience.value;
    let wasLearning = false;
    const updatePlacement = () => {
        const learning = placement.value === 'learning';
        if (learning && !wasLearning) { siteAudience = audience.value; audience.value = 'members'; }
        if (!learning && wasLearning) audience.value = siteAudience;
        wasLearning = learning;
        document.querySelectorAll('[data-site-link-field]').forEach(field => {
            field.disabled = learning;
            if (field !== audience) field.closest('.chisimba-form-field').hidden = learning;
        });
    };
    placement.addEventListener('change', updatePlacement);
    updatePlacement();
}
