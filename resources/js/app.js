import './bootstrap';

document.addEventListener('click', (event) => {
    const button = event.target instanceof Element
        ? event.target.closest('[data-toggle-password]')
        : null;

    if (!button) {
        return;
    }

    const selector = button.getAttribute('data-toggle-password');

    if (!selector) {
        return;
    }

    const input = document.querySelector(selector);

    if (!(input instanceof HTMLInputElement)) {
        return;
    }

    const icon = button.querySelector('[data-password-icon]');
    const showPassword = input.type === 'password';

    input.type = showPassword ? 'text' : 'password';

    if (icon) {
        icon.textContent = showPassword ? 'visibility_off' : 'visibility';
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.matches('[data-prevent-double-submit]')) {
        return;
    }

    if (form.dataset.submitting === 'true') {
        event.preventDefault();
        return;
    }

    form.dataset.submitting = 'true';

    const submitButton = form.querySelector('[type="submit"]');

    if (submitButton instanceof HTMLButtonElement) {
        submitButton.disabled = true;
        submitButton.setAttribute('aria-busy', 'true');

        const label = submitButton.querySelector('[data-submit-label]');
        if (label) {
            label.textContent = submitButton.dataset.submittingText || 'Procesando...';
        }
    }
});
