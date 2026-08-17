import './bootstrap';
import {
    Activity, Bell, CalendarDays, ChartNoAxesColumn, Check, ChevronDown, CircleAlert, CircleCheckBig,
    ClipboardCheck, Clock3, Columns3, createIcons, Download, Folder, FolderKanban, GitBranch, House, List, ListChecks,
    ListFilter, Menu, MessageSquare, MoreVertical, PanelLeftClose, Pencil, Play, Plus, Search, Settings, Share2,
    Save, ShieldCheck, SlidersHorizontal, SquareCheckBig, Table2, Tag, Trash2, TriangleAlert, UserPlus, Users, X,
} from 'lucide';

document.addEventListener('DOMContentLoaded', () => {
    createIcons({ icons: {
        Activity, Bell, CalendarDays, ChartNoAxesColumn, Check, ChevronDown, CircleAlert, CircleCheckBig,
        ClipboardCheck, Clock3, Columns3, Download, Folder, FolderKanban, GitBranch, House, List, ListChecks,
        ListFilter, Menu, MessageSquare, MoreVertical, PanelLeftClose, Pencil, Play, Plus, Search, Settings, Share2,
        Save, ShieldCheck, SlidersHorizontal, SquareCheckBig, Table2, Tag, Trash2, TriangleAlert, UserPlus, Users, X,
    } });
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

    const taskModal = document.querySelector('#task-modal');
    const taskForm = document.querySelector('#task-modal-form');
    const method = document.querySelector('#task-method');
    const modalTitle = document.querySelector('#task-modal-title');
    const submitLabel = document.querySelector('#task-submit-label');
    const taskDelete = document.querySelector('#task-delete');
    const storeAction = taskForm?.action;

    const openTaskModal = async (link) => {
        taskForm?.reset();
        method?.setAttribute('disabled', 'disabled');
        if (link.href.includes('/create')) {
            taskForm.action = storeAction;
            modalTitle.textContent = 'Tạo task mới';
            submitLabel.textContent = 'Tạo task';
            taskDelete.hidden = true;
        } else {
            const response = await fetch(link.href, { headers: { Accept: 'application/json' } });
            const task = await response.json();
            taskForm.action = link.href.replace(/\/edit$/, '');
            method?.removeAttribute('disabled');
            modalTitle.textContent = 'Cập nhật task';
            submitLabel.textContent = 'Lưu thay đổi';
            taskDelete.hidden = false;
            taskDelete.dataset.action = taskForm.action;
            for (const field of ['title', 'project_id', 'assignee_id', 'status', 'priority', 'due_date', 'description']) {
                const input = taskForm.elements.namedItem(field);
                if (input) input.value = field === 'due_date' && task[field] ? task[field].slice(0, 10) : (task[field] ?? '');
            }
        }
        taskModal?.showModal();
    };
    document.querySelectorAll('a[href*="/tasks/create"], a[href*="/tasks/"][href$="/edit"]').forEach((link) => {
        link.addEventListener('click', (event) => {
            if (!taskModal) return;
            event.preventDefault();
            openTaskModal(link);
        });
    });
    taskModal?.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', () => taskModal.close()));
    taskModal?.addEventListener('click', (event) => {
        if (event.target === taskModal) taskModal.close();
    });

    const projectModal = document.querySelector('#project-modal');
    const projectForm = document.querySelector('#project-modal-form');
    const projectMethod = document.querySelector('#project-method');
    const projectTitle = document.querySelector('#project-modal-title');
    const projectSubmit = document.querySelector('#project-submit-label');
    const projectDelete = document.querySelector('#project-delete');
    const projectStoreAction = projectForm?.action;
    const openProjectModal = async (link) => {
        projectForm?.reset();
        projectMethod?.setAttribute('disabled', 'disabled');
        if (link.href.includes('/create')) {
            projectForm.action = projectStoreAction;
            projectTitle.textContent = 'Tạo dự án mới';
            projectSubmit.textContent = 'Tạo dự án';
            projectDelete.hidden = true;
        } else {
            const response = await fetch(link.href, { headers: { Accept: 'application/json' } });
            const project = await response.json();
            projectForm.action = link.href.replace(/\/edit$/, '');
            projectMethod?.removeAttribute('disabled');
            projectTitle.textContent = 'Cập nhật dự án';
            projectSubmit.textContent = 'Lưu thay đổi';
            projectDelete.hidden = false;
            projectDelete.dataset.action = projectForm.action;
            for (const field of ['name', 'key', 'owner_id', 'status', 'priority', 'start_date', 'due_date', 'description']) {
                const input = projectForm.elements.namedItem(field);
                if (input) input.value = ['start_date', 'due_date'].includes(field) && project[field] ? project[field].slice(0, 10) : (project[field] ?? '');
            }
            const memberIds = (project.members ?? []).map((member) => String(member.id));
            for (const option of projectForm.elements.namedItem('members[]').options) option.selected = memberIds.includes(option.value);
        }
        projectModal?.showModal();
    };
    document.querySelectorAll('a[href*="/projects/create"], a[href*="/projects/"][href$="/edit"]').forEach((link) => link.addEventListener('click', (event) => {
        if (!projectModal) return;
        event.preventDefault();
        openProjectModal(link);
    }));
    projectModal?.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', () => projectModal.close()));
    projectModal?.addEventListener('click', (event) => { if (event.target === projectModal) projectModal.close(); });

    const submitDelete = (button, message) => {
        if (!button?.dataset.action || !window.confirm(message)) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = button.dataset.action;
        form.innerHTML = `<input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}"><input type="hidden" name="_method" value="DELETE">`;
        document.body.appendChild(form);
        form.submit();
    };
    taskDelete?.addEventListener('click', () => submitDelete(taskDelete, 'Xóa task này? Hành động không thể hoàn tác.'));
    projectDelete?.addEventListener('click', () => submitDelete(projectDelete, 'Xóa dự án và toàn bộ task bên trong? Hành động không thể hoàn tác.'));
    document.addEventListener('click', (event) => {
        const toggle = event.target.closest('.action-menu-toggle');
        const deleteButton = event.target.closest('[data-delete-action]');
        const openMenus = document.querySelectorAll('.action-menu.open');

        if (toggle) {
            event.stopPropagation();
            const menu = toggle.closest('.action-menu');
            const wasOpen = menu.classList.contains('open');
            openMenus.forEach((item) => item.classList.remove('open'));
            if (!wasOpen) {
                const rect = toggle.getBoundingClientRect();
                const popover = menu.querySelector('.action-menu-popover');
                popover.style.top = `${rect.bottom + 6}px`;
                popover.style.right = `${window.innerWidth - rect.right}px`;
                menu.classList.add('open');
                toggle.setAttribute('aria-expanded', 'true');
            }
            return;
        }

        openMenus.forEach((item) => {
            item.classList.remove('open');
            item.querySelector('.action-menu-toggle')?.setAttribute('aria-expanded', 'false');
        });
        if (deleteButton) submitDelete({ dataset: { action: deleteButton.dataset.deleteAction } }, deleteButton.dataset.deleteMessage);
    });
});
