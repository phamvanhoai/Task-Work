import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const button = document.querySelector('.mobile-menu');
    const sidebar = document.querySelector('.app-sidebar');
    const shell = document.querySelector('.app-shell');
    const collapse = document.querySelector('.sidebar-collapse');
    const notify = document.querySelector('.notify');
    const notificationMenu = document.querySelector('.notification-menu');
    const search = document.querySelector('.top-search input');

    button?.addEventListener('click', () => sidebar?.classList.toggle('open'));
    collapse?.addEventListener('click', () => {
        shell?.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebar-collapsed', shell?.classList.contains('sidebar-collapsed') ? '1' : '0');
    });
    if (localStorage.getItem('sidebar-collapsed') === '1') shell?.classList.add('sidebar-collapsed');
    notify?.addEventListener('click', (event) => {
        event.stopPropagation();
        notificationMenu?.classList.toggle('open');
    });
    document.addEventListener('click', () => notificationMenu?.classList.remove('open'));
    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            search?.focus();
        }
    });
});
