class NotificationSystem {
    constructor() {
        this.container = null;
        this.queue = [];
        this.isShowing = false;
        this.maxVisible = 3;
        this.visibleNotifications = [];
        this.initContainer();
    }

    initContainer() {
        this.container = document.createElement('div');
        this.container.className = 'notification-container';
        document.body.appendChild(this.container);
    }

    show(title, message, type = 'success', duration = 3000) {
        this.queue.push({ title, message, type, duration });
        this.processQueue();
    }

    processQueue() {
        if (this.isShowing || this.queue.length === 0) return;
        
        if (this.visibleNotifications.length < this.maxVisible) {
            this.isShowing = true;
            const options = this.queue.shift();
            this.createNotification(options);
        }
    }

   // Cập nhật trong method createNotification
createNotification(options) {
    const icons = {
        success: '<i class="fas fa-check-circle"></i>',
        error: '<i class="fas fa-times-circle"></i>',
        warning: '<i class="fas fa-exclamation-triangle"></i>',
        info: '<i class="fas fa-info-circle"></i>'
    };

    const notification = document.createElement('div');
    notification.className = `notification notification-${options.type}`;
    
    // Thêm style animation duration
    notification.style.animationDuration = `${options.duration}ms`;
    
    notification.innerHTML = `
        <span class="notification-icon">${icons[options.type] || icons.success}</span>
        <div class="notification-content">
            <div class="notification-title">${options.title}</div>
            <div class="notification-message">${options.message}</div>
        </div>
        <button class="notification-close" aria-label="Đóng thông báo">
            <i class="fas fa-times"></i>
        </button>
    `;

    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => this.hide(notification));

    this.container.appendChild(notification);

    // Trigger animation
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0) scale(1)';
    }, 10);

    // Auto close
    if (options.duration > 0) {
        setTimeout(() => this.hide(notification), options.duration);
    }

    this.visibleNotifications.push(notification);
    this.isShowing = false;
    this.processQueue();

    return notification;
}
    hide(notification) {
        if (!notification || !notification.parentNode) return;

        // Add slideOut animation
        notification.style.animation = 'slideOut 0.4s cubic-bezier(0.4, 0, 0.6, 1) forwards';
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
            this.visibleNotifications = this.visibleNotifications.filter(n => n !== notification);
            this.processQueue();
        }, 400);
    }

    // Tiện ích methods
    success(message, title = 'Thành công', duration = 3000) {
        this.show(title, message, 'success', duration);
    }

    error(message, title = 'Lỗi', duration = 3000) {
        this.show(title, message, 'error', duration);
    }

    warning(message, title = 'Cảnh báo', duration = 3000) {
        this.show(title, message, 'warning', duration);
    }

    info(message, title = 'Thông báo', duration = 3000) {
        this.show(title, message, 'info', duration);
    }
}

// CSS cần bổ sung animation slideOut
const additionalCSS = `
.notification.hide {
    animation: slideOut 0.4s cubic-bezier(0.4, 0, 0.6, 1) forwards;
}

@keyframes slideOut {
    0% {
        opacity: 1;
        transform: translateX(0);
    }
    20% {
        opacity: 0.8;
    }
    100% {
        opacity: 0;
        transform: translateX(120%);
    }
}
`;

// Thêm CSS vào document
const style = document.createElement('style');
style.textContent = additionalCSS;
document.head.appendChild(style);

// Khởi tạo toàn cục
if (!window.notification) {
    window.notification = new NotificationSystem();
}