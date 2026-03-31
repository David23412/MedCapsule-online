// Add this to your existing JavaScript
// RUTHLESS PERFORMANCE: Debounce utility
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

document.addEventListener('DOMContentLoaded', function() {
    // SCROLL REVEAL ANIMATION
    const revealElements = document.querySelectorAll('.reveal');
    const revealOnScroll = () => {
        let remainingHidden = 0;
        revealElements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const elementVisible = 150;
            if (elementTop < window.innerHeight - elementVisible) {
                element.classList.add('active');
            } else if (!element.classList.contains('active')) {
                remainingHidden++;
            }
        });

        if (remainingHidden === 0) {
            window.removeEventListener('scroll', revealOnScroll);
        }
    };
    window.addEventListener('scroll', revealOnScroll, { passive: true });
    revealOnScroll();

    // NAVBAR SCROLL EFFECT
    const navbar = document.querySelector('.navbar');
    const handleNavbarScroll = () => {
        if (!navbar) {
            return;
        }

        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    };
    window.addEventListener('scroll', handleNavbarScroll, { passive: true });

    // SMOOTH SCROLL FOR ANCHOR LINKS
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#' && document.querySelector(href)) {
                e.preventDefault();
                document.querySelector(href).scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Contact button toggle
    const contactToggle = document.getElementById('contactToggle');
    const contactOptions = document.getElementById('contactOptions');

    if (contactToggle && contactOptions) {
        contactToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            contactOptions.classList.toggle('show');
        });

        document.addEventListener('click', function(e) {
            if (!contactToggle.contains(e.target) && !contactOptions.contains(e.target)) {
                contactOptions.classList.remove('show');
            }
        });
    }

    // Batch DOM operations for performance
    requestAnimationFrame(() => {
        const progressCircle = document.querySelector('.progress-fill');
        const progressValue = document.querySelector('.progress-value');

        if (progressCircle && progressValue) {
            const progress = 85;
            const circumference = 2 * Math.PI * 26;
            const offset = circumference - (progress / 100) * circumference;

            progressCircle.style.strokeDasharray = `${circumference} ${circumference}`;
            progressCircle.style.strokeDashoffset = offset;
            progressValue.textContent = `${progress}%`;
        }

        const streakValue = 12;
        const streakFire = document.querySelector('.streak-fire');

        if (streakFire) {
            if (streakValue > 0) {
                streakFire.style.display = 'inline-block';
                streakFire.style.animationDuration = `${2 - (streakValue * 0.1)}s`;
            } else {
                streakFire.style.display = 'none';
            }
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const courseInfoModalElement = document.getElementById('courseInfoModal');
    if (!courseInfoModalElement || typeof bootstrap === 'undefined') {
        return;
    }

    const courseModal = new bootstrap.Modal(courseInfoModalElement);
    const viewCourseBtns = document.querySelectorAll('.view-course-btn');

    viewCourseBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const courseId = this.dataset.courseId;
            const courseName = this.dataset.courseName;
            const courseDescription = this.dataset.courseDescription;
            const topicsCount = this.dataset.topicsCount;
            const questionsCount = this.dataset.questionsCount;

            document.querySelector('.modal-title').textContent = courseName;
            document.querySelector('.course-description').textContent = courseDescription;
            document.querySelector('.topics-count').textContent = topicsCount;
            document.querySelector('.questions-count').textContent = questionsCount;
            document.querySelector('.start-course-btn').href = `/courses/${courseId}`;

            courseModal.show();
        });
    });
});

// Intersection Observer for counter animations
const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const counter = entry.target;
            const target = parseInt(counter.innerText, 10);
            let count = 0;
            const duration = 2000;
            const increment = target / (duration / 16);

            const updateCount = () => {
                count += increment;
                if (count < target) {
                    counter.innerText = Math.ceil(count);
                    requestAnimationFrame(updateCount);
                } else {
                    counter.innerText = target;
                }
            };

            updateCount();
            counterObserver.unobserve(counter);
        }
    });
}, { threshold: 0.1 });

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.counter').forEach(counter => {
        counterObserver.observe(counter);
    });
});

// Notification bell for home page — polling mode
function notificationBell() {
    return {
        notifications: [],
        isOpen: false,
        unreadCount: 0,
        loading: false,
        lastFetched: 0,
        notificationSound: null,
        prevUnreadCount: 0,
        _pollingInterval: null,

        init() {
            this.setUserInteractionFlag();

            this.fetchNotifications().then(() => {
                this.prevUnreadCount = this.unreadCount;
            });

            this.initializePolling();
        },

        initializePolling() {
            this._pollingInterval = setInterval(() => {
                this.fetchNotifications();
            }, 60000);
        },

        setUserInteractionFlag() {
            window.addEventListener('click', () => {
                if (!window.hasUserInteraction) {
                    window.hasUserInteraction = true;
                }
            });
        },

        playNotificationSound() {
            if (!window.hasUserInteraction) {
                return;
            }
            if (!this.notificationSound) {
                this.notificationSound = new Audio('/sounds/Soft-ui-notification-sound-effect.mp3');
            }
            this.notificationSound.currentTime = 0;
            this.notificationSound.play().catch(() => {});
        },

        async fetchNotifications() {
            try {
                this.loading = true;
                const response = await fetch('/notifications', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();

                if (data.success) {
                    this.notifications = data.notifications || [];
                    this.unreadCount = data.unread_count || 0;
                    this.lastFetched = Date.now();
                    this.updateBadge();
                }
            } catch (error) {
                console.error('Error fetching notifications:', error);
            } finally {
                this.loading = false;
            }
        },

        updateBadge() {
            const badge = document.getElementById('notificationBadge');
            const displayCount = Math.min(this.unreadCount, 99);

            if (displayCount > 0) {
                if (badge) {
                    const oldCount = parseInt(badge.textContent, 10) || 0;
                    if (oldCount !== displayCount) {
                        badge.classList.add('badge-count-change');
                        setTimeout(() => {
                            badge.textContent = displayCount;
                            badge.setAttribute('aria-label', `${displayCount} unread notifications`);
                        }, 150);
                        setTimeout(() => {
                            badge.classList.remove('badge-count-change');
                        }, 300);
                    }
                } else {
                    const newBadge = document.createElement('span');
                    newBadge.id = 'notificationBadge';
                    newBadge.className = 'clean-notification-badge badge-appear';
                    newBadge.textContent = displayCount;
                    newBadge.setAttribute('aria-label', `${displayCount} unread notifications`);

                    const bell = document.querySelector('.notification-bell');
                    if (bell) {
                        bell.appendChild(newBadge);
                    }
                }
            } else if (badge) {
                badge.classList.add('badge-disappear');
                setTimeout(() => {
                    if (badge.parentNode) {
                        badge.remove();
                    }
                }, 300);
            }
        },

        cleanup() {
            if (this._pollingInterval) {
                clearInterval(this._pollingInterval);
                this._pollingInterval = null;
            }
        }
    };
}

window.addEventListener('beforeunload', () => {
    if (window.notificationBellInstance) {
        window.notificationBellInstance.cleanup();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const streakIndicators = document.querySelectorAll('.streak-indicator');

    streakIndicators.forEach(streakIndicator => {
        const streakProgress = streakIndicator.querySelector('.streak-progress');
        const streak = parseInt(streakIndicator.dataset.streak || 0, 10);
        const streakFire = streakIndicator.querySelector('.streak-fire');

        if (streakProgress) {
            const maxStreak = 7;
            const cycleDay = streak % maxStreak;
            const isWeeklyMilestone = streak > 0 && cycleDay === 0;
            const progress = isWeeklyMilestone ? 1 : cycleDay / maxStreak;
            const radius = parseFloat(streakProgress.getAttribute('r') || '40');
            const circumference = 2 * Math.PI * radius;

            streakProgress.style.strokeDasharray = circumference;
            streakProgress.style.strokeDashoffset = circumference;

            setTimeout(() => {
                streakProgress.style.strokeDashoffset = circumference * (1 - progress);
            }, 300);

            if (isWeeklyMilestone) {
                setTimeout(() => {
                    streakIndicator.classList.add('streak-week-complete');
                    if (streakFire) {
                        streakFire.classList.add('streak-fire-celebrate');
                    }
                    streakProgress.style.filter = 'drop-shadow(0 0 12px rgba(255, 179, 0, 0.95))';
                }, 1650);

                setTimeout(() => {
                    streakProgress.style.strokeDashoffset = circumference;
                }, 2500);

                setTimeout(() => {
                    streakIndicator.classList.remove('streak-week-complete');
                    if (streakFire) {
                        streakFire.classList.remove('streak-fire-celebrate');
                    }
                    streakProgress.style.filter = '';
                }, 3400);
            }
        }

        if (streakFire && streak > 0) {
            streakIndicator.addEventListener('mouseenter', () => {
                streakFire.style.transform = 'scale(1.3) rotate(10deg)';
                streakFire.style.filter = 'drop-shadow(0 0 8px rgba(255, 107, 107, 0.8))';
                streakIndicator.style.transform = 'translateY(-2px) scale(1.05)';
            });

            streakIndicator.addEventListener('mouseleave', () => {
                streakFire.style.transform = 'scale(1) rotate(0deg)';
                streakFire.style.filter = 'none';
                streakIndicator.style.transform = 'translateY(0) scale(1)';
            });
        }

        streakIndicator.addEventListener('click', () => {
            streakIndicator.style.transform = 'scale(0.95)';
            setTimeout(() => {
                streakIndicator.style.transform = 'scale(1)';
            }, 150);

            window.location.href = window.MedCapsule.profileRoute;
        });
    });
});
