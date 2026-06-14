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
