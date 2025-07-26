/**
 * REAL-TIME ASSIGNMENT STATUS ENFORCER
 * 
 * This module provides INSTANT status updates for assignments
 * NO MORE DELAYS, NO MORE SCHEDULING - EVERYTHING HAPPENS INSTANTLY!
 */

class RealTimeAssignmentEnforcer {
    constructor() {
        this.isEnforcing = false;
        this.lastEnforcement = 0;
        this.minInterval = 1000; // Minimum 1 second between enforcements
        
        // Auto-enforce on page load
        this.enforceOnLoad();
        
        // Set up real-time monitoring
        this.setupRealTimeMonitoring();
    }

    /**
     * INSTANT enforcement on page load
     */
    enforceOnLoad() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.enforceAllStatuses());
        } else {
            this.enforceAllStatuses();
        }
    }

    /**
     * Set up real-time monitoring for assignment status changes
     */
    setupRealTimeMonitoring() {
        // Monitor for assignment deadline changes
        this.monitorDeadlineChanges();
        
        // Monitor for time limit expirations
        this.monitorTimeLimits();
        
        // Set up periodic enforcement (as backup)
        setInterval(() => this.enforceAllStatuses(), 30000); // Every 30 seconds as backup
    }

    /**
     * INSTANT status enforcement for all assignments
     */
    async enforceAllStatuses() {
        if (this.isEnforcing) return;
        
        const now = Date.now();
        if (now - this.lastEnforcement < this.minInterval) return;
        
        this.isEnforcing = true;
        this.lastEnforcement = now;
        
        try {
            const response = await fetch('/assignments/enforce-real-time-status', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({})
            });
            
            const data = await response.json();
            
            if (data.success && data.updated_count > 0) {
                console.log(`✅ INSTANT: Updated ${data.updated_count} assignment statuses`);
                this.updateUI(data);
            }
        } catch (error) {
            console.error('❌ Real-time enforcement failed:', error);
        } finally {
            this.isEnforcing = false;
        }
    }

    /**
     * INSTANT status enforcement for specific assignment
     */
    async enforceAssignmentStatus(assignmentId) {
        try {
            const response = await fetch('/assignments/enforce-real-time-status', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ assignment_id: assignmentId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                console.log(`✅ INSTANT: Assignment ${assignmentId} status: ${data.old_status} → ${data.new_status}`);
                
                if (data.updated) {
                    this.updateAssignmentUI(assignmentId, data.old_status, data.new_status);
                }
                
                return data;
            }
        } catch (error) {
            console.error(`❌ Failed to enforce status for assignment ${assignmentId}:`, error);
        }
        
        return null;
    }

    /**
     * Monitor for deadline changes in real-time
     */
    monitorDeadlineChanges() {
        // Monitor all deadline elements
        const deadlineElements = document.querySelectorAll('[data-deadline]');
        
        deadlineElements.forEach(element => {
            const deadline = new Date(element.dataset.deadline);
            const assignmentId = element.dataset.assignmentId;
            
            if (deadline && assignmentId) {
                this.scheduleDeadlineCheck(assignmentId, deadline);
            }
        });
    }

    /**
     * Schedule a check for when an assignment becomes overdue
     */
    scheduleDeadlineCheck(assignmentId, deadline) {
        const now = new Date();
        const timeUntilDeadline = deadline.getTime() - now.getTime();
        
        if (timeUntilDeadline > 0 && timeUntilDeadline < 24 * 60 * 60 * 1000) { // Within 24 hours
            setTimeout(() => {
                console.log(`⏰ DEADLINE REACHED: Enforcing status for assignment ${assignmentId}`);
                this.enforceAssignmentStatus(assignmentId);
            }, timeUntilDeadline + 1000); // 1 second after deadline
        }
    }

    /**
     * Monitor for time limit expirations
     */
    monitorTimeLimits() {
        const timerElements = document.querySelectorAll('[data-time-limit]');
        
        timerElements.forEach(element => {
            const timeLimit = parseInt(element.dataset.timeLimit);
            const assignmentId = element.dataset.assignmentId;
            const startTime = new Date(element.dataset.startTime);
            
            if (timeLimit && assignmentId && startTime) {
                this.scheduleTimeLimitCheck(assignmentId, startTime, timeLimit);
            }
        });
    }

    /**
     * Schedule a check for when time limit expires
     */
    scheduleTimeLimitCheck(assignmentId, startTime, timeLimitSeconds) {
        const now = new Date();
        const expirationTime = new Date(startTime.getTime() + (timeLimitSeconds * 1000));
        const timeUntilExpiration = expirationTime.getTime() - now.getTime();
        
        if (timeUntilExpiration > 0) {
            setTimeout(() => {
                console.log(`⏱️ TIME LIMIT EXPIRED: Enforcing status for assignment ${assignmentId}`);
                this.enforceAssignmentStatus(assignmentId);
            }, timeUntilExpiration + 1000); // 1 second after expiration
        }
    }

    /**
     * Update UI after status changes
     */
    updateUI(data) {
        if (data.updated_count > 0) {
            // Show notification
            this.showNotification(`${data.updated_count} assignment(s) updated instantly!`, 'success');
            
            // Optionally reload page for immediate visual update
            if (data.updated_count > 0) {
                setTimeout(() => location.reload(), 1000);
            }
        }
    }

    /**
     * Update specific assignment UI
     */
    updateAssignmentUI(assignmentId, oldStatus, newStatus) {
        const assignmentElement = document.querySelector(`[data-assignment-id="${assignmentId}"]`);
        
        if (assignmentElement) {
            // Update status badge
            const statusBadge = assignmentElement.querySelector('.status-badge');
            if (statusBadge) {
                statusBadge.className = statusBadge.className.replace(/status-\w+/, `status-${newStatus}`);
                statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
            }
            
            // Update row styling
            assignmentElement.className = assignmentElement.className.replace(/assignment-\w+/, `assignment-${newStatus}`);
            
            // Show instant feedback
            this.showNotification(`Assignment status updated: ${oldStatus} → ${newStatus}`, 'info');
        }
    }

    /**
     * Show notification to user
     */
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            <i class="fas fa-bolt"></i> <strong>INSTANT UPDATE:</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }
}

// Initialize real-time enforcer
window.realTimeAssignmentEnforcer = new RealTimeAssignmentEnforcer();

// Global function for manual enforcement
window.enforceRealTimeStatus = function(assignmentId = null) {
    if (assignmentId) {
        return window.realTimeAssignmentEnforcer.enforceAssignmentStatus(assignmentId);
    } else {
        return window.realTimeAssignmentEnforcer.enforceAllStatuses();
    }
};

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RealTimeAssignmentEnforcer;
}