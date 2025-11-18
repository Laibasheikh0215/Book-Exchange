// ✅ SIMPLE NOTIFICATION POLLER
class NotificationPoller {
    constructor() {
        this.pollingInterval = null;
        this.lastCheck = null;
        this.userId = null;
    }

    start(userId) {
        this.userId = userId;
        console.log('🔔 Starting notification polling for user:', userId);
        
        // Check every 10 seconds (reduce frequency)
        this.pollingInterval = setInterval(() => {
            this.checkNotifications();
        }, 10000);
        
        // Check immediately
        this.checkNotifications();
    }

    stop() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            console.log('🛑 Notification polling stopped');
        }
    }

    async checkNotifications() {
        if (!this.userId) return;

        try {
            const response = await fetch(`http://localhost/project/backend/api/notifications/check_new.php?user_id=${this.userId}&last_check=${this.lastCheck || ''}`);
            
            // ✅ FIRST CHECK if response is OK
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // ✅ GET RESPONSE AS TEXT FIRST
            const responseText = await response.text();
            console.log('📡 Raw response:', responseText);
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('❌ JSON Parse Error:', parseError);
                console.error('❌ Invalid JSON received:', responseText);
                return;
            }
            
            if (data.success && data.has_new) {
                this.showNotifications(data.notifications);
                this.updateBadges(data.counts);
            }
            
            this.lastCheck = new Date().toISOString();
            
        } catch (error) {
            console.error('Notification check failed:', error);
        }
    }

    showNotifications(notifications) {
        if (!notifications || !Array.isArray(notifications)) return;
        
        notifications.forEach(notif => {
            this.showToast(notif);
        });
    }

    showToast(notification) {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `notification-toast alert alert-${this.getTypeClass(notification.type)}`;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideInRight 0.3s ease;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;

        toast.innerHTML = `
            <div class="d-flex align-items-start">
                <i class="${this.getIcon(notification.type)} me-2 mt-1"></i>
                <div class="flex-grow-1">
                    <strong class="d-block">${notification.title || 'Notification'}</strong>
                    <small class="d-block">${notification.message || 'No message'}</small>
                    <small class="text-muted">Just now</small>
                </div>
                <button type="button" class="btn-close btn-close-sm ms-2" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;

        document.body.appendChild(toast);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 5000);

        // Play sound
        this.playSound();

        // Click to view notifications
        toast.addEventListener('click', () => {
            if (typeof showNotifications === 'function') {
                showNotifications();
            }
            toast.remove();
        });
    }

    updateBadges(counts) {
        if (!counts) return;
        
        // Update all badges
        if (counts.unread_notifications > 0) {
            const badge = document.getElementById('sidebarNotificationsBadge');
            if (badge) {
                badge.textContent = counts.unread_notifications;
                badge.style.display = 'inline-block';
            }
        }

        if (counts.pending_requests > 0) {
            const badge1 = document.getElementById('pendingRequestsBadge');
            const badge2 = document.getElementById('requestsCount');
            if (badge1) {
                badge1.textContent = counts.pending_requests;
                badge1.style.display = 'inline-block';
            }
            if (badge2) {
                badge2.textContent = counts.pending_requests;
            }
        }

        if (counts.unread_messages > 0) {
            const badge = document.getElementById('unreadMessagesCount');
            if (badge) {
                badge.textContent = counts.unread_messages;
                badge.style.display = 'inline-block';
            }
        }
    }

    getTypeClass(type) {
        const classes = {
            'Request': 'warning',
            'Message': 'info',
            'System': 'primary',
            'Reminder': 'success'
        };
        return classes[type] || 'primary';
    }

    getIcon(type) {
        const icons = {
            'Request': 'fas fa-handshake',
            'Message': 'fas fa-comment',
            'System': 'fas fa-info-circle',
            'Reminder': 'fas fa-clock'
        };
        return icons[type] || 'fas fa-bell';
    }

    playSound() {
        // Simple notification sound
        try {
            const audio = new Audio("data:audio/wav;base64,UklGRigAAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YQQAAAAAAA==");
            audio.volume = 0.2;
            audio.play().catch(() => {});
        } catch (e) {
            // Ignore audio errors
        }
    }
}

// Global instance
const notificationPoller = new NotificationPoller();