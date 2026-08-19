import './bootstrap';
import {
    Activity, ArrowLeft, Bell, BellOff, CalendarDays, ChartNoAxesColumn, Check, CheckCheck, ChevronDown, ChevronLeft, ChevronRight, CircleAlert, CircleCheckBig, CircleDot,
    ClipboardCheck, Clock3, Columns3, createIcons, Download, Flag, Folder, FolderKanban, FolderPlus, GitBranch, House, List, ListChecks, LoaderCircle,
    ListFilter, Menu, MessageSquare, MoreVertical, PanelLeftClose, Pencil, Play, Plus, Search, Settings, Share2,
    RefreshCw, Save, ScanEye, ShieldCheck, SlidersHorizontal, SquareCheckBig, Table2, Tag, Trash2, TriangleAlert, UserCheck, UserPlus, Users, X,
} from 'lucide';

document.addEventListener('DOMContentLoaded', () => {
    createIcons({ icons: {
        Activity, ArrowLeft, Bell, BellOff, CalendarDays, ChartNoAxesColumn, Check, CheckCheck, ChevronDown, ChevronLeft, ChevronRight, CircleAlert, CircleCheckBig, CircleDot,
        ClipboardCheck, Clock3, Columns3, Download, Flag, Folder, FolderKanban, FolderPlus, GitBranch, House, List, ListChecks, LoaderCircle,
        ListFilter, Menu, MessageSquare, MoreVertical, PanelLeftClose, Pencil, Play, Plus, Search, Settings, Share2,
        RefreshCw, Save, ScanEye, ShieldCheck, SlidersHorizontal, SquareCheckBig, Table2, Tag, Trash2, TriangleAlert, UserCheck, UserPlus, Users, X,
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

    const confirmModal = document.querySelector('#confirm-modal');
    const showConfirm = (message) => new Promise((resolve) => {
        if (!confirmModal) return resolve(false);
        confirmModal.querySelector('#confirm-message').textContent = message;
        const finish = (accepted) => {
            confirmModal.close();
            resolve(accepted);
        };
        confirmModal.querySelector('[data-confirm-accept]').onclick = () => finish(true);
        confirmModal.querySelector('[data-confirm-cancel]').onclick = () => finish(false);
        confirmModal.oncancel = (event) => { event.preventDefault(); finish(false); };
        confirmModal.showModal();
    });
    const submitDelete = async (button, message) => {
        if (!button?.dataset.action || !await showConfirm(message)) return;
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
        const closeMenu = (item) => {
            const popover = item._openPopover;
            item.classList.remove('open');
            item.querySelector('.action-menu-toggle')?.setAttribute('aria-expanded', 'false');
            if (popover) {
                popover.classList.remove('open');
                item.appendChild(popover);
                item._openPopover = null;
            }
        };

        if (toggle) {
            event.stopPropagation();
            const menu = toggle.closest('.action-menu');
            const wasOpen = menu.classList.contains('open');
            openMenus.forEach(closeMenu);
            if (!wasOpen) {
                const rect = toggle.getBoundingClientRect();
                const popover = menu.querySelector('.action-menu-popover');
                popover.style.top = `${rect.bottom + 6}px`;
                popover.style.right = `${window.innerWidth - rect.right}px`;
                menu._openPopover = popover;
                document.body.appendChild(popover);
                popover.classList.add('open');
                menu.classList.add('open');
                toggle.setAttribute('aria-expanded', 'true');
            }
            return;
        }

        openMenus.forEach(closeMenu);
        if (deleteButton) submitDelete({ dataset: { action: deleteButton.dataset.deleteAction } }, deleteButton.dataset.deleteMessage);
    });
    document.querySelectorAll('[data-auto-submit]').forEach((select) => select.addEventListener('change', () => select.form.submit()));
    document.querySelectorAll('[data-filter-toggle]').forEach((button) => button.addEventListener('click', () => {
        const panel = document.querySelector('.task-filter-form');
        panel?.classList.toggle('open');
        button.classList.toggle('active', panel?.classList.contains('open'));
    }));
    document.querySelector('[data-density-toggle]')?.addEventListener('click', (event) => {
        document.querySelector('.task-data-card')?.classList.toggle('dense');
        event.currentTarget.classList.toggle('active');
    });
    document.querySelector('[data-export-table]')?.addEventListener('click', () => {
        const rows = [...document.querySelectorAll('.task-reference-table tr')].map((row) =>
            [...row.querySelectorAll('th,td')].slice(1, -1).map((cell) => `"${cell.innerText.trim().replaceAll('"', '""')}"`).join(','),
        );
        const blob = new Blob([`\uFEFF${rows.join('\n')}`], { type: 'text/csv;charset=utf-8' });
        const link = Object.assign(document.createElement('a'), { href: URL.createObjectURL(blob), download: 'danh-sach-task.csv' });
        link.click();
        URL.revokeObjectURL(link.href);
    });

    const kanban = document.querySelector('.task-kanban');
    if (kanban) {
        let draggedCard = null;
        const updateKanbanCounts = () => document.querySelectorAll('.kanban-column').forEach((column) => {
            const count = column.querySelectorAll('.kanban-task').length;
            column.querySelector('header b').textContent = count;
            column.querySelector('.kanban-empty')?.remove();
            if (!count) {
                const empty = Object.assign(document.createElement('p'), { className: 'kanban-empty', textContent: 'Không có task' });
                column.querySelector('.kanban-list').appendChild(empty);
            }
        });
        const showToast = (message, error = false) => {
            const toast = Object.assign(document.createElement('div'), { className: `ui-toast ${error ? 'error' : 'success'}`, textContent: message });
            document.body.appendChild(toast);
            requestAnimationFrame(() => toast.classList.add('show'));
            setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 200); }, 2200);
        };

        kanban.addEventListener('dragstart', (event) => {
            draggedCard = event.target.closest('.kanban-task');
            if (!draggedCard) return;
            draggedCard.classList.add('dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', draggedCard.dataset.taskId);
        });
        kanban.addEventListener('dragend', () => {
            draggedCard?.classList.remove('dragging');
            document.querySelectorAll('.kanban-column.drag-over').forEach((column) => column.classList.remove('drag-over'));
            draggedCard = null;
        });
        document.querySelectorAll('.kanban-column').forEach((column) => {
            column.addEventListener('dragover', (event) => {
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
                column.classList.add('drag-over');
            });
            column.addEventListener('dragleave', (event) => {
                if (!column.contains(event.relatedTarget)) column.classList.remove('drag-over');
            });
            column.addEventListener('drop', async (event) => {
                event.preventDefault();
                column.classList.remove('drag-over');
                if (!draggedCard || draggedCard.closest('.kanban-column') === column) return;
                const originalList = draggedCard.parentElement;
                const targetList = column.querySelector('.kanban-list');
                targetList.appendChild(draggedCard);
                updateKanbanCounts();
                try {
                    const response = await fetch(draggedCard.dataset.statusUrl, {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({ status: column.dataset.kanbanStatus }),
                    });
                    if (!response.ok) throw new Error('Update failed');
                    showToast('Đã cập nhật trạng thái task.');
                } catch {
                    originalList.appendChild(draggedCard);
                    updateKanbanCounts();
                    showToast('Không thể cập nhật trạng thái. Vui lòng thử lại.', true);
                }
            });
        });
    }
    document.querySelector('[data-project-filter-toggle]')?.addEventListener('click', (event) => {
        const panel = document.querySelector('.project-filter-panel');
        panel?.classList.toggle('open');
        event.currentTarget.classList.toggle('active', panel?.classList.contains('open'));
    });
    document.querySelector('[data-calendar-filter]')?.addEventListener('click', (event) => {
        const panel = document.querySelector('.calendar-filter-panel');
        panel?.classList.toggle('open');
        event.currentTarget.classList.toggle('active', panel?.classList.contains('open'));
    });
    document.querySelector('[data-report-export]')?.addEventListener('click', () => {
        const rows = [['Chỉ số', 'Giá trị']];
        document.querySelectorAll('.report-metrics article').forEach((card) => rows.push([card.querySelector('small').textContent, card.querySelector('strong').textContent]));
        const csv = rows.map((row) => row.map((value) => `"${value.replaceAll('"', '""')}"`).join(',')).join('\n');
        const blob = new Blob([`\uFEFF${csv}`], { type: 'text/csv;charset=utf-8' });
        const link = Object.assign(document.createElement('a'), { href: URL.createObjectURL(blob), download: 'bao-cao-taskflow.csv' });
        link.click(); URL.revokeObjectURL(link.href);
    });
    const memberModal = document.querySelector('#member-modal');
    const memberEditModal = document.querySelector('#member-edit-modal');
    document.querySelector('[data-member-invite]')?.addEventListener('click', () => memberModal?.showModal());
    memberModal?.querySelectorAll('[data-member-close]').forEach((button) => button.addEventListener('click', () => memberModal.close()));
    memberModal?.addEventListener('click', (event) => { if (event.target === memberModal) memberModal.close(); });
    document.addEventListener('click', (event) => {
        const editButton = event.target.closest('[data-member-edit]');
        if (!editButton || !memberEditModal) return;
        const form = memberEditModal.querySelector('form');
        form.action = editButton.dataset.action;
        form.elements.namedItem('name').value = editButton.dataset.name;
        form.elements.namedItem('email').value = editButton.dataset.email;
        form.elements.namedItem('role').value = editButton.dataset.role;
        form.elements.namedItem('password').value = '';
        memberEditModal.showModal();
    });
    memberEditModal?.querySelectorAll('[data-member-edit-close]').forEach((button) => button.addEventListener('click', () => memberEditModal.close()));
    memberEditModal?.addEventListener('click', (event) => { if (event.target === memberEditModal) memberEditModal.close(); });
    const labelModal = document.querySelector('#label-modal');
    const labelEditModal = document.querySelector('#label-edit-modal');
    document.querySelectorAll('[data-label-create]').forEach((button) => button.addEventListener('click', () => labelModal?.showModal()));
    labelModal?.querySelectorAll('[data-label-close]').forEach((button) => button.addEventListener('click', () => labelModal.close()));
    labelModal?.addEventListener('click', (event) => { if (event.target === labelModal) labelModal.close(); });
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-label-edit]');
        if (!button || !labelEditModal) return;
        const form = labelEditModal.querySelector('form');
        form.action = button.dataset.action;
        form.elements.namedItem('name').value = button.dataset.name;
        form.elements.namedItem('color').value = button.dataset.color;
        form.elements.namedItem('description').value = button.dataset.description;
        form.elements.namedItem('is_archived')[1].checked = button.dataset.archived === '1';
        labelEditModal.querySelector('.color-input span').textContent = button.dataset.color.toUpperCase();
        labelEditModal.showModal();
    });
    labelEditModal?.querySelectorAll('[data-label-edit-close]').forEach((button) => button.addEventListener('click', () => labelEditModal.close()));
    labelEditModal?.addEventListener('click', (event) => { if (event.target === labelEditModal) labelEditModal.close(); });
    document.querySelectorAll('.color-input input[type="color"]').forEach((input) => input.addEventListener('input', () => { input.nextElementSibling.textContent = input.value.toUpperCase(); }));
    document.querySelectorAll('[data-password-toggle]').forEach((button) => button.addEventListener('click', () => {
        const input = button.previousElementSibling;
        input.type = input.type === 'password' ? 'text' : 'password';
        button.innerHTML = `<i data-lucide="${input.type === 'password' ? 'eye-off' : 'eye'}"></i>`;
        createIcons({ icons });
    }));
    const profilePhotoButton = document.querySelector('.profile-photo .btn');
    const savedAvatar = document.querySelector('.user-avatar img');
    const profileAvatar = document.querySelector('.profile-photo .avatar');
    if (savedAvatar && profileAvatar) profileAvatar.replaceChildren(savedAvatar.cloneNode());
    profilePhotoButton?.addEventListener('click', () => {
        const input = Object.assign(document.createElement('input'), { type: 'file', name: 'avatar', accept: 'image/*' });
        input.hidden = true;
        input.addEventListener('change', () => {
            if (!input.files.length) return;
            const form = profilePhotoButton.closest('form');
            form.enctype = 'multipart/form-data';
            form.appendChild(input);
            form.submit();
        });
        document.body.appendChild(input);
        input.click();
    });
    const preferenceForm = document.querySelector('.preference-settings form');
    preferenceForm?.querySelectorAll('input,select').forEach((control) => control.addEventListener('change', () => preferenceForm.requestSubmit()));
    document.querySelector('.preference-settings')?.setAttribute('id', 'preferences');
    document.querySelector('.theme-options')?.setAttribute('id', 'appearance');
    preferenceForm?.elements.namedItem('notification_sound')?.closest('.setting-switch')?.setAttribute('id', 'notifications');
    document.querySelector('.settings-fields [name="timezone"]')?.closest('label')?.setAttribute('id', 'time-management');
    document.querySelector('.password-settings')?.setAttribute('id', 'security');
    const sessionSettings = document.querySelector('.session-settings');
    sessionSettings?.setAttribute('id', 'sessions');
    if (sessionSettings && !sessionSettings.querySelector('[data-logout-sessions]')) {
        const button = Object.assign(document.createElement('button'), { type: 'button', className: 'text-action', textContent: 'Đăng xuất tất cả' });
        button.dataset.logoutSessions = '';
        sessionSettings.querySelector('.card-heading')?.appendChild(button);
        button.addEventListener('click', async () => {
            if (!await showConfirm('Đăng xuất khỏi tất cả thiết bị khác?')) return;
            const form = document.createElement('form');
            form.method = 'POST'; form.action = '/settings/sessions';
            form.innerHTML = `<input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}"><input type="hidden" name="_method" value="DELETE">`;
            document.body.appendChild(form); form.submit();
        });
    }
    document.querySelectorAll('[data-settings-link]').forEach((link) => link.addEventListener('click', () => {
        document.querySelectorAll('[data-settings-link]').forEach((item) => item.classList.remove('active'));
        link.classList.add('active');
    }));
    document.querySelector('[data-member-export]')?.addEventListener('click', () => {
        const rows = [...document.querySelectorAll('.member-table-wrap tr')].map((row) => [...row.querySelectorAll('th,td')].map((cell) => `"${cell.innerText.trim().replaceAll('"', '""')}"`).join(','));
        const blob = new Blob([`\uFEFF${rows.join('\n')}`], { type: 'text/csv;charset=utf-8' });
        const link = Object.assign(document.createElement('a'), { href: URL.createObjectURL(blob), download: 'danh-sach-thanh-vien.csv' });
        link.click(); URL.revokeObjectURL(link.href);
    });
});
