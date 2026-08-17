import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const button = document.querySelector('.mobile-menu');
    const sidebar = document.querySelector('.app-sidebar');

    button?.addEventListener('click', () => sidebar?.classList.toggle('open'));
});
