// Modern Animated Toast Notification System
(function() {
    function getOrCreateContainer() {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        return container;
    }

    /**
     * Show a modern toast notification
     * @param {string} message - Notification text
     * @param {'success'|'error'|'warning'|'info'} [type='info'] - Toast category
     * @param {string} [title=''] - Optional title header
     * @param {number} [duration=4000] - Duration in ms (0 for persistent)
     */
    window.showToast = function(message, type = 'info', title = '', duration = 4000) {
        const container = getOrCreateContainer();
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        const iconMap = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-triangle',
            warning: 'fa-exclamation-circle',
            info: 'fa-info-circle'
        };

        const defaultTitles = {
            success: 'Success',
            error: 'Error',
            warning: 'Attention',
            info: 'Info'
        };

        const icon = iconMap[type] || 'fa-info-circle';
        const displayTitle = title || defaultTitles[type] || 'Notice';

        toast.innerHTML = `
            <div class="toast-icon"><i class="fas ${icon}"></i></div>
            <div class="toast-body">
                <div class="toast-title">${displayTitle}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button type="button" class="toast-close" aria-label="Dismiss">&times;</button>
            ${duration > 0 ? '<div class="toast-progress-track"><div class="toast-progress-fill"></div></div>' : ''}
        `;

        container.appendChild(toast);

        // Animate entrance
        requestAnimationFrame(() => {
            toast.classList.add('toast-visible');
        });

        // Handle auto-dismiss and progress bar
        let dismissTimer;
        if (duration > 0) {
            const fill = toast.querySelector('.toast-progress-fill');
            if (fill) {
                fill.style.transition = `width ${duration}ms linear`;
                requestAnimationFrame(() => {
                    fill.style.width = '0%';
                });
            }
            dismissTimer = setTimeout(() => dismiss(toast), duration);
        }

        // Close button
        const closeBtn = toast.querySelector('.toast-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                if (dismissTimer) clearTimeout(dismissTimer);
                dismiss(toast);
            });
        }

        function dismiss(target) {
            target.classList.remove('toast-visible');
            target.classList.add('toast-hiding');
            setTimeout(() => {
                if (target.parentNode) {
                    target.parentNode.removeChild(target);
                }
            }, 300);
        }
    };
})();
