// FS Tech System Manager - app JS (no build step)

// Theme toggle (Bootstrap 5.3 native dark mode)
(function () {
    const KEY = 'fst-theme';

    function current() {
        return localStorage.getItem(KEY) ||
            (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    }

    function apply(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        const icon = document.getElementById('themeIcon');
        if (icon) icon.className = 'bi ' + (theme === 'dark' ? 'bi-sun-fill' : 'bi-moon-stars-fill');
        localStorage.setItem(KEY, theme);
    }

    window.fstToggleTheme = function () {
        apply(current() === 'dark' ? 'light' : 'dark');
    };

    apply(current());
})();

// Sidebar collapse + mobile
(function () {
    const KEY = 'fst-sidebar';
    const sidebar = document.getElementById('fstSidebar');
    const main = document.getElementById('fstMain');
    const backdrop = document.getElementById('fstBackdrop');

    if (localStorage.getItem(KEY) === 'collapsed') {
        sidebar?.classList.add('collapsed');
        main?.classList.add('expanded');
    }

    window.fstToggleSidebar = function () {
        if (window.innerWidth < 992) {
            sidebar?.classList.toggle('show');
            backdrop?.classList.toggle('show');
            return;
        }
        const collapsed = sidebar?.classList.toggle('collapsed');
        main?.classList.toggle('expanded', collapsed);
        localStorage.setItem(KEY, collapsed ? 'collapsed' : 'expanded');
    };

    backdrop?.addEventListener('click', () => {
        sidebar?.classList.remove('show');
        backdrop?.classList.remove('show');
    });
})();

// Confirmation helper: fstConfirm(event, message, okLabel, okClass)
function fstConfirm(event, message, okLabel, okClass) {
    event.preventDefault();
    const el = event.currentTarget;
    const modalEl = document.createElement('div');
    modalEl.className = 'modal fade';
    modalEl.innerHTML =
        '<div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content">' +
        '<div class="modal-body text-center py-4">' +
        '<i class="bi bi-exclamation-triangle-fill text-warning mb-2" style="font-size:2rem"></i>' +
        '<p class="mb-0" id="fstConfirmMsg">' + message + '</p></div>' +
        '<div class="modal-footer border-0 justify-content-center pb-4">' +
        '<button class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>' +
        '<button class="btn ' + (okClass || 'btn-danger') + ' btn-sm px-3" id="fstConfirmOk">' + (okLabel || 'Delete') + '</button>' +
        '</div></div></div>';
    document.body.appendChild(modalEl);
    const modal = new bootstrap.Modal(modalEl);
    modalEl.querySelector('#fstConfirmOk').addEventListener('click', () => {
        modal.hide();
        modalEl.addEventListener('hidden.bs.modal', () => el.closest('form')?.submit());
    });
    modal.show();
    modalEl.addEventListener('hidden.bs.modal', () => modalEl.remove());
}

// Kanban drag & drop
document.addEventListener('DOMContentLoaded', () => {
    if (typeof Sortable === 'undefined' || !document.getElementById('kanban')) return;

    function updateCounts() {
        document.querySelectorAll('.fst-kanban-col').forEach(function (col) {
            const badge = col.querySelector('.fst-col-count');
            if (badge) badge.textContent = col.querySelectorAll('.fst-task-card').length;
        });
    }

    document.querySelectorAll('.fst-kanban-body').forEach(function (list) {
        new Sortable(list, {
            group: 'tasks',
            animation: 150,
            ghostClass: 'fst-drag-ghost',
            dragClass: 'dragging',
            filter: 'input, textarea, select, .fst-notes form, button',
            preventOnFilter: false,
            onStart: function (evt) {
                evt.item.classList.add('dragging');
                document.querySelectorAll('.fst-kanban-body').forEach(function (body) {
                    if (body !== evt.from) body.classList.add('drag-over');
                });
            },
            onEnd: function (evt) {
                evt.item.classList.remove('dragging');
                document.querySelectorAll('.fst-kanban-body').forEach(function (body) {
                    body.classList.remove('drag-over');
                });
                updateCounts();

                const taskCard = evt.item;
                const newStatus = evt.to.dataset.status;
                const url = taskCard.dataset.statusUrl;
                if (!url || taskCard.dataset.status === newStatus) return;

                taskCard.dataset.status = newStatus;

                const form = new FormData();
                form.append('status', newStatus);
                form.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                fetch(url, { method: 'PATCH', body: form }).catch(function () { window.location.reload(); });
            },
        });
    });
});

// Auto-dismiss alerts
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.alert-auto').forEach((el) => {
        setTimeout(() => bootstrap.Alert.getOrCreateInstance(el).close(), 4000);
    });
});
