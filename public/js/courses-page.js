(function() {
    const coursesPageConfig = window.CoursesPageConfig || {};

    // Keep cover loading native: one URL, browser lazy loading, no duplicate requests.
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.course-cover').forEach(image => {
            const showFallback = () => { image.hidden = true; };
            image.addEventListener('error', showFallback);
            if (image.complete && image.naturalWidth === 0) showFallback();
        });
    });

    let trackPendingPayment = () => {};

    window.notificationBell = function notificationBell() {
        return {
            notifications: [],
            isOpen: false,
            unreadCount: 0,
            loading: false,
            lastFetched: 0,
            notificationSound: null,
            prevUnreadCount: 0,

            init() {
                this.setUserInteractionFlag();
                this.fetchNotifications().then(() => {
                    this.prevUnreadCount = this.unreadCount;
                });

                let pollInterval = 30000;

                const checkForNewNotifications = () => {
                    if (document.visibilityState === 'visible' && Date.now() - this.lastFetched >= 60000) {
                        this.fetchNotifications();
                    }
                    setTimeout(checkForNewNotifications, pollInterval);
                };

                document.addEventListener('visibilitychange', () => {
                    pollInterval = document.visibilityState === 'visible' ? 30000 : 180000;
                });

                setTimeout(checkForNewNotifications, pollInterval);

                this.$watch('unreadCount', (newCount, oldCount) => {
                    if (newCount > oldCount && oldCount !== 0) {
                        this.playNotificationSound();

                        const bellIcon = this.$el.querySelector('i.fa-bell');
                        if (bellIcon) {
                            bellIcon.style.animation = 'none';
                            void bellIcon.offsetWidth;
                            bellIcon.style.animation = 'bellRing 1s';
                        }
                    }
                });
            },

            setUserInteractionFlag() {
                window.addEventListener('click', () => {
                    if (!window.hasUserInteraction) {
                        window.hasUserInteraction = true;
                    }
                }, { once: true });
            },

            playNotificationSound() {
                if (!window.hasUserInteraction) {
                    return;
                }
                try {
                    if (!this.notificationSound) {
                        this.notificationSound = new Audio('/sounds/Soft-ui-notification-sound-effect.mp3');
                    }
                    this.notificationSound.volume = 0.5;
                    this.notificationSound.play().catch(() => {});
                } catch (_e) {}
            },

            getTypeIcon(type) {
                switch (type) {
                    case 'achievement':
                    case 'correct_answers_milestone':
                        return 'fas fa-award text-warning';
                    case 'streak':
                    case 'study_streak':
                        return 'fas fa-fire text-danger';
                    case 'milestone':
                        return 'fas fa-trophy text-success';
                    case 'topic_mastery':
                        return 'fas fa-graduation-cap text-success';
                    case 'review_mistakes':
                        return 'fas fa-redo text-primary';
                    case 'weak_topics':
                    case 'weak_topic_added':
                        return 'fas fa-exclamation-triangle text-danger';
                    case 'leaderboard_rank':
                        return 'fas fa-crown text-warning';
                    case 'performance':
                        return 'fas fa-chart-line text-info';
                    case 'quiz_completed':
                        return 'fas fa-check-circle text-success';
                    case 'course_welcome':
                        return 'fas fa-book text-primary';
                    case 'course_progress':
                        return 'fas fa-running text-info';
                    case 'review_reminder':
                        return 'fas fa-history text-secondary';
                    case 'info':
                    default:
                        return 'fas fa-bell text-primary';
                }
            },

            fetchNotifications() {
                if (this.loading) {
                    return Promise.resolve();
                }

                this.loading = true;
                this.lastFetched = Date.now();

                return fetch(`/notifications?t=${Date.now()}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    credentials: 'same-origin'
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        this.notifications = data.notifications || [];
                        this.unreadCount = data.unread_count || 0;

                        if (this.prevUnreadCount === 0) {
                            this.prevUnreadCount = this.unreadCount;
                        }

                        return data;
                    })
                    .catch(error => console.error('Error fetching notifications:', error))
                    .finally(() => {
                        this.loading = false;
                    });
            },

            toggleNotifications() {
                this.isOpen = !this.isOpen;

                if (this.isOpen && Date.now() - this.lastFetched >= 30000) {
                    this.fetchNotifications();
                }
            },

            markAllAsRead() {
                if (this.unreadCount === 0) {
                    return;
                }

                fetch('/notifications/mark-all-read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    credentials: 'same-origin'
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            this.notifications.forEach(notification => {
                                notification.is_read = true;
                            });
                            this.unreadCount = 0;
                        }
                    })
                    .catch(error => console.error('Error marking notifications as read:', error));
            }
        };
    };

    let correctAnswers = Number(coursesPageConfig.correctAnswersInitial || 0);
    let goalLevel = Math.floor(correctAnswers / 100) + 1;
    let currentGoal = goalLevel * 100;

    const goalCountElement = document.getElementById('goalCount');
    const correctCountElement = document.getElementById('correctCount');

    if (goalCountElement) {
        goalCountElement.textContent = currentGoal;
    }
    if (correctCountElement) {
        correctCountElement.textContent = correctAnswers;
    }

    function updateProgressFill() {
        const progressFill = document.querySelector('.liquid-fill');
        if (!progressFill) {
            return;
        }

        const progress = ((correctAnswers % 100) / 100) * 100;
        progressFill.style.width = `${progress}%`;

        document.querySelectorAll('.milestone-mark').forEach((mark, index) => {
            const milestone = (index + 1) * 20;
            const isActive = (correctAnswers % 100) >= milestone;
            mark.classList.toggle('active', isActive);
        });
    }

    function addCelebrationEffect() {
        const container = document.querySelector('.milestone-container');
        if (!container) {
            return;
        }

        const colors = ['#4f46e5', '#6366f1', '#8b5cf6', '#d946ef', '#ec4899', '#f43f5e', '#ef4444', '#f97316', '#f59e0b', '#10b981'];

        for (let i = 0; i < 30; i++) {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.style.left = `${Math.random() * 100}%`;
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.animationDuration = `${0.5 + Math.random() * 0.5}s`;
            confetti.style.animationDelay = `${Math.random() * 0.2}s`;
            container.appendChild(confetti);

            setTimeout(() => {
                confetti.remove();
            }, 2000);
        }
    }

    function createFireworks() {
        const milestone = document.querySelector('.milestone-container');
        if (!milestone) {
            return;
        }

        const fireworks = document.createElement('div');
        fireworks.className = 'fireworks';
        milestone.appendChild(fireworks);

        setTimeout(() => {
            fireworks.remove();
        }, 1500);
    }

    function levelUpGoal() {
        const progressFill = document.querySelector('.liquid-fill');
        const goalCountEl = document.getElementById('goalCount');
        const correctCountEl = document.getElementById('correctCount');
        const milestoneContainer = document.querySelector('.milestone-container');

        if (!progressFill || !goalCountEl || !correctCountEl || !milestoneContainer) {
            return;
        }

        goalLevel++;
        currentGoal = goalLevel * 100;

        progressFill.style.transition = 'width 0.7s cubic-bezier(0.4, 1.6, 0.6, 1)';
        progressFill.style.width = '100%';
        progressFill.classList.add('lava');
        milestoneContainer.classList.add('celebrate');

        try {
            addCelebrationEffect();
            createFireworks();
        } catch (e) {
            console.warn('Celebration effects not available', e);
        }

        setTimeout(() => {
            correctCountEl.textContent = correctAnswers;
            goalCountEl.textContent = currentGoal;

            progressFill.style.transition = 'none';
            progressFill.style.width = '0';
            progressFill.classList.remove('lava');
            milestoneContainer.classList.remove('celebrate');
            void progressFill.offsetWidth;
            progressFill.style.transition = 'width 0.8s cubic-bezier(0.34, 1.56, 0.64, 1)';
            updateProgressFill();

            document.querySelectorAll('.milestone-mark').forEach(mark => mark.classList.remove('active'));

            setTimeout(() => {
                addCelebrationEffect();
            }, 1000);
        }, 1500);
    }

    function updateProgress(newProgress) {
        correctAnswers = newProgress;
        const correctCount = document.getElementById('correctCount');

        if (correctCount) {
            correctCount.textContent = correctAnswers;
        }

        if (correctAnswers >= currentGoal) {
            levelUpGoal();
        } else {
            updateProgressFill();
        }

        if (correctCount) {
            correctCount.classList.add('pulse');
            setTimeout(() => correctCount.classList.remove('pulse'), 300);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        try {
            const dropdowns = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            dropdowns.map(function(dropdownToggle) {
                return new bootstrap.Dropdown(dropdownToggle);
            });
        } catch (e) {
            console.warn('Dropdown init error suppressed:', e);
        }

        updateProgress(correctAnswers);

        document.querySelectorAll('.course-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-8px)';
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        });

        // AJAX Enrollment Handler
        document.querySelectorAll('.ajax-enroll-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const courseId = this.dataset.courseId;
                const enrollUrl = this.dataset.enrollUrl;
                const topicsUrl = this.dataset.topicsUrl;
                const originalContent = this.innerHTML;

                // Visual feedback: Start loading
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Enrolling...</span>';

                fetch(enrollUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ course_id: courseId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Success! Transform button to "Enter Course"
                        this.classList.remove('enroll', 'free');
                        this.classList.add('continue');
                        this.innerHTML = '<i class="fas fa-play"></i> <span>Enter Course</span>';
                        this.disabled = false;

                        // Update click behavior to go to topics
                        this.onclick = function() {
                            window.location.href = topicsUrl;
                        };

                        // Optional: Show a subtle success toast if available
                        if (window.showToast) {
                            window.showToast('Successfully enrolled!', 'success');
                        }
                    } else {
                        // Error handling
                        this.innerHTML = originalContent;
                        this.disabled = false;
                        alert(data.message || 'Enrollment failed. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Enrollment error:', error);
                    this.innerHTML = originalContent;
                    this.disabled = false;
                    alert('An error occurred during enrollment.');
                });
            });
        });
    });

    window.addEventListener('load', function() {
        updateProgress(correctAnswers);
    });

    document.addEventListener('progressUpdate', function(e) {
        updateProgress(e.detail.progress);
    });

    window.addEventListener('userProgressUpdated', function(event) {
        if (event.detail && event.detail.correctAnswers !== undefined) {
            updateProgress(event.detail.correctAnswers);
            const countNode = document.getElementById('correctCount');
            if (countNode) {
                countNode.textContent = event.detail.correctAnswers;
            }
        }
    });

    function updateCourseCardProgress(courseProgressMap = {}) {
        document.querySelectorAll('.course-card[data-course-id]').forEach(card => {
            const courseId = card.dataset.courseId;
            const rawPercentage = Number(courseProgressMap[courseId] ?? 0);
            const percentage = Number.isFinite(rawPercentage)
                ? Math.max(0, Math.min(100, rawPercentage))
                : 0;

            const progressContainer = card.querySelector('.progress-container');
            const progressFill = card.querySelector('.progress-fill');
            const progressPercentage = card.querySelector('.progress-percentage');

            if (progressContainer) {
                progressContainer.setAttribute('aria-valuenow', String(Math.round(percentage)));
            }

            if (progressFill) {
                progressFill.style.width = `${percentage}%`;
            }

            if (progressPercentage) {
                progressPercentage.textContent = `${Math.round(percentage)}%`;
            }
        });
    }

    function applyUserProgressSnapshot(data = {}) {
        if (data.correct_answers_count !== undefined && data.correct_answers_count !== correctAnswers) {
            updateProgress(data.correct_answers_count);
            const countNode = document.getElementById('correctCount');
            if (countNode) {
                countNode.textContent = data.correct_answers_count;
            }
        }

        if (data.course_completion_percentages) {
            updateCourseCardProgress(data.course_completion_percentages);
        }
    }

    let progressRequest = null;
    let progressPollInterval = null;
    let progressSessionExpired = false;

    function canRefreshProgress() {
        return Boolean(coursesPageConfig.authUserId)
            && !progressSessionExpired
            && document.visibilityState === 'visible'
            && navigator.onLine !== false;
    }

    function fetchUserProgressSnapshot() {
        if (!canRefreshProgress()) return Promise.resolve({});
        if (progressRequest) return progressRequest;

        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 15000);
        progressRequest = fetch('/profile/progress', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            signal: controller.signal
        }).then(response => {
            if (!response.ok) {
                if (response.status === 401 || response.status === 419) {
                    progressSessionExpired = true;
                    stopProgressPolling();
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        }).finally(() => {
            clearTimeout(timeout);
            progressRequest = null;
        });
        return progressRequest;
    }

    function refreshUserProgressInstantly() {
        return fetchUserProgressSnapshot().then(applyUserProgressSnapshot).catch(() => {});
    }

    function startProgressPolling() {
        if (progressPollInterval || !canRefreshProgress()) return;
        // Events refresh immediately; this is only a fallback for changes on another device.
        progressPollInterval = setInterval(refreshUserProgressInstantly, 60000);
    }

    function stopProgressPolling() {
        if (!progressPollInterval) return;
        clearInterval(progressPollInterval);
        progressPollInterval = null;
    }

    startProgressPolling();
    document.addEventListener('visibilitychange', function() {
        if (canRefreshProgress()) {
            refreshUserProgressInstantly();
            startProgressPolling();
        } else {
            stopProgressPolling();
        }
    });
    window.addEventListener('online', function() {
        refreshUserProgressInstantly();
        startProgressPolling();
    });
    window.addEventListener('beforeunload', stopProgressPolling);
    window.addEventListener('storage', function(event) {
        if (event.key === 'quizProgressUpdate' || event.key === 'assignmentCompleted') {
            refreshUserProgressInstantly();
        }
    });
    document.addEventListener('sourceProgressUpdated', refreshUserProgressInstantly);
    document.addEventListener('assignmentCompleted', refreshUserProgressInstantly);

    try {
        const goalModalElement = document.getElementById('goalModal');
        if (goalModalElement) {
            const goalModal = new bootstrap.Modal(goalModalElement);
            window.openGoalModal = function() {
                goalModal.show();
            };
        }
    } catch (e) {
        // Modal not present.
    }

    window.showUnlockInstructions = function showUnlockInstructions() {
        document.getElementById('backdrop').classList.add('show');
        document.getElementById('unlockAlert').classList.add('show');
    };

    window.hideUnlockInstructions = function hideUnlockInstructions() {
        document.getElementById('backdrop').classList.remove('show');
        document.getElementById('unlockAlert').classList.remove('show');
    };

    let scrollTimeout;
    window.scrollCourses = function scrollCourses(direction) {
        const container = document.querySelector('.courses-grid');
        if (!container) {
            return;
        }

        const cardWidth = 340;
        const gap = 24;
        const scrollAmount = cardWidth + gap;
        const currentScroll = container.scrollLeft;
        const targetScroll = direction === 'left'
            ? Math.max(0, currentScroll - scrollAmount)
            : currentScroll + scrollAmount;

        container.scrollTo({
            left: targetScroll,
            behavior: 'smooth'
        });
    };

    function updateScrollArrows(container = null) {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            const coursesGrid = container || document.querySelector('.courses-grid');
            const leftArrow = document.querySelector('.scroll-left');
            const rightArrow = document.querySelector('.scroll-right');

            if (!coursesGrid || !leftArrow || !rightArrow) {
                return;
            }

            const visibleCards = document.querySelectorAll('.course-card[style*="display: block"], .course-card:not([style*="display: none"])');
            const needsScrolling = visibleCards.length > 3 && coursesGrid.scrollWidth > coursesGrid.offsetWidth;

            requestAnimationFrame(() => {
                if (!needsScrolling) {
                    leftArrow.style.display = 'none';
                    rightArrow.style.display = 'none';
                } else {
                    leftArrow.style.display = 'flex';
                    rightArrow.style.display = 'flex';
                    leftArrow.style.opacity = coursesGrid.scrollLeft > 0 ? '1' : '0.5';
                    rightArrow.style.opacity = (coursesGrid.scrollLeft + coursesGrid.offsetWidth) < coursesGrid.scrollWidth ? '1' : '0.5';
                }
            });
        }, 16);
    }

    const coursesGridElement = document.querySelector('.courses-grid');
    if (coursesGridElement) {
        let scrollTicking = false;
        coursesGridElement.addEventListener('scroll', () => {
            if (!scrollTicking) {
                requestAnimationFrame(() => {
                    updateScrollArrows(coursesGridElement);
                    scrollTicking = false;
                });
                scrollTicking = true;
            }
        }, { passive: true });
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateScrollArrows(document.querySelector('.courses-grid'));
    });

    document.addEventListener('DOMContentLoaded', function() {
        const streakIndicators = document.querySelectorAll('.streak-indicator');

        streakIndicators.forEach(streakIndicator => {
            const streakProgress = streakIndicator.querySelector('.streak-progress');
            const streakFire = streakIndicator.querySelector('.streak-fire');
            const streak = parseInt(streakIndicator.dataset.streak || 0, 10);

            if (!streakProgress) {
                return;
            }

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
                }, 1450);

                setTimeout(() => {
                    streakProgress.style.strokeDashoffset = circumference;
                }, 2200);

                setTimeout(() => {
                    streakIndicator.classList.remove('streak-week-complete');
                    if (streakFire) {
                        streakFire.classList.remove('streak-fire-celebrate');
                    }
                    streakProgress.style.filter = '';
                }, 3000);
            }

            if (streakFire && streak > 0) {
                streakIndicator.addEventListener('mouseenter', () => {
                    streakFire.style.transform = 'scale(1.3) rotate(10deg)';
                    streakFire.style.filter = 'drop-shadow(0 0 8px rgba(255, 107, 107, 0.8))';
                });

                streakIndicator.addEventListener('mouseleave', () => {
                    streakFire.style.transform = 'scale(1) rotate(0deg)';
                    streakFire.style.filter = 'none';
                });
            }
        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        const semesterDropdown = document.getElementById('semesterDropdown');

        if (semesterDropdown) {
            semesterDropdown.value = '';
            setTimeout(() => {
                window.filterCoursesByAcademicYear();
            }, 100);
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        try {
            if (typeof bootstrap === 'undefined') {
                console.warn('Bootstrap not loaded yet, waiting...');
                setTimeout(() => {
                    initializePaymentModal();
                }, 1000);
                return;
            }

            initializePaymentModal();
        } catch (e) {
            console.warn('Payment modal initialization error:', e);
        }
    });

    function initializePaymentModal() {
        if (typeof bootstrap === 'undefined') {
            console.warn('Bootstrap not loaded yet, retrying...');
            setTimeout(initializePaymentModal, 100);
            return;
        }

        const paymentModalElement = document.getElementById('paymentModal');
        if (!paymentModalElement) {
            return;
        }

        const paymentModal = new bootstrap.Modal(paymentModalElement);

        function initializeInjectedPaymentContent() {
            const paymentMethodField = document.getElementById('paymentMethodField');
            if (paymentMethodField) {
                paymentMethodField.value = 'instapay';
            }

            document.querySelectorAll('#paymentModal .copy-btn').forEach(button => {
                if (button.dataset.bound) {
                    return;
                }

                button.dataset.bound = 'true';
                button.addEventListener('click', async function() {
                    const value = this.dataset.clipboard || '';
                    if (!value) {
                        return;
                    }

                    try {
                        await navigator.clipboard.writeText(value);
                        const originalHtml = this.innerHTML;
                        this.innerHTML = '<i class="fas fa-check"></i>';
                        window.setTimeout(() => {
                            this.innerHTML = originalHtml;
                        }, 1100);
                    } catch (error) {
                        console.warn('Clipboard copy failed:', error);
                    }
                });
            });
        }

        let paymentOptionsLoading = false;
        document.addEventListener('click', function(event) {
            const button = event.target.closest('.show-payment-modal');
            if (!button || button.disabled || paymentOptionsLoading) return;
            paymentOptionsLoading = true;
            const courseId = button.dataset.courseId;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(`/payments/${courseId}/options`, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json().then(data => ({ ok: response.ok, data })))
                .then(({ ok, data }) => {
                    if (!ok) {
                        throw new Error(data.message || data.error || 'Payment options are not available for this course.');
                    }

                    return data;
                })
                .then(data => {
                    if (data.success) {
                        document.querySelector('#paymentModal .modal-content').innerHTML = data.html;
                        initializeInjectedPaymentContent();
                        paymentModal.show();

                        const paymentForm = document.getElementById('coursePaymentForm');
                        if (!paymentForm) {
                            return;
                        }

                        paymentForm.addEventListener('submit', function(e) {
                            e.preventDefault();

                            const formData = new FormData(this);
                            const submitBtn = this.querySelector('#submitBtn');
                            if (submitBtn.disabled) return;
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

                            fetch(`/payments/${courseId}/process`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                },
                                body: formData
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        paymentModal.hide();
                                        trackPendingPayment(courseId);
                                        const originalButton = document.getElementById(`paymentBtn_${courseId}`);
                                        if (!originalButton) {
                                            return;
                                        }

                                        originalButton.innerHTML = '<i class="fas fa-clock" style="color: #ff8c00;"></i> <span style="color: #ff8c00; font-weight: bold;">Payment Pending</span>';
                                        originalButton.disabled = true;
                                        originalButton.style.animation = 'successPulse 1s ease-in-out';
                                        originalButton.style.backgroundColor = '#fff7ed';
                                        originalButton.style.borderColor = '#fed7aa';
                                        originalButton.style.color = '#ea580c';
                                        originalButton.querySelector('i').style.color = '#ea580c';
                                        originalButton.querySelector('span').style.color = '#ea580c';
                                    } else {
                                        alert(data.message);
                                        submitBtn.disabled = false;
                                        submitBtn.innerHTML = 'Submit Payment';
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('An error occurred. Please try again.');
                                    submitBtn.disabled = false;
                                    submitBtn.innerHTML = 'Submit Payment';
                                });
                        });
                    } else {
                        alert(data.message || data.error || 'Payment options are not available for this course.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert(error.message || 'An error occurred. Please try again.');
                }).finally(() => { paymentOptionsLoading = false; });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (!coursesPageConfig.authUserId) return;

        // The server is authoritative; legacy browser keys from other accounts are ignored.
        const pendingPayments = new Set((coursesPageConfig.pendingCourseIds || []).map(String));
        let paymentStatusPolling = null;
        let paymentStatusRequest = null;
        let paymentSessionExpired = false;

        trackPendingPayment = function(courseId) {
            pendingPayments.add(String(courseId));
            updateButtonToPendingCourse(courseId);
            startPaymentStatusPolling();
        };

        function startPaymentStatusPolling() {
            if (paymentStatusPolling || paymentSessionExpired || !pendingPayments.size
                || document.visibilityState !== 'visible') return;
            paymentStatusPolling = setInterval(checkPaymentCompletions, 5000);
        }

        function stopPaymentStatusPolling() {
            if (!paymentStatusPolling) return;
            clearInterval(paymentStatusPolling);
            paymentStatusPolling = null;
        }

        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                checkPaymentCompletions();
                startPaymentStatusPolling();
            } else {
                stopPaymentStatusPolling();
            }
        });
        window.addEventListener('online', checkPaymentCompletions);
        window.addEventListener('beforeunload', stopPaymentStatusPolling);

        function checkPaymentCompletions() {
            if (paymentSessionExpired || !pendingPayments.size || document.visibilityState !== 'visible'
                || navigator.onLine === false) return Promise.resolve();
            if (paymentStatusRequest) return paymentStatusRequest;

            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 15000);
            paymentStatusRequest = fetch('/api/check-payment-status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                },
                signal: controller.signal,
                body: JSON.stringify({ course_ids: [...pendingPayments] })
            }).then(response => {
                if (!response.ok) {
                    if (response.status === 401 || response.status === 419) {
                        paymentSessionExpired = true;
                        stopPaymentStatusPolling();
                    }
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            }).then(data => {
                if (!data.success) return;
                (data.pending_courses || []).forEach(updateButtonToPendingCourse);
                (data.rejected_courses || []).forEach(courseId => {
                    pendingPayments.delete(String(courseId));
                    updateButtonToRejectedCourse(courseId);
                });
                (data.completed_courses || []).forEach(courseId => {
                    pendingPayments.delete(String(courseId));
                    updateButtonToAccessCourse(courseId);
                });
                if (!pendingPayments.size) stopPaymentStatusPolling();
            }).catch(() => {
                // Leave server-rendered pending state intact; a later check can retry.
            }).finally(() => {
                clearTimeout(timeout);
                paymentStatusRequest = null;
            });
            return paymentStatusRequest;
        }

        checkPaymentCompletions();
        startPaymentStatusPolling();

        function updateButtonToPendingCourse(courseId) {
            const button = document.querySelector(`#paymentBtn_${courseId}`);
            if (!button || button.textContent.includes('Payment Pending')) {
                return;
            }

            button.innerHTML = '<i class="fas fa-clock" style="color: #ea580c;"></i> <span style="color: #ea580c; font-weight: bold;">Payment Pending</span>';
            button.disabled = true;
            button.style.backgroundColor = '#fff7ed';
            button.style.borderColor = '#fed7aa';
            button.style.color = '#ea580c';
            button.style.cursor = 'default';
            button.style.pointerEvents = 'none';
            button.classList.remove('show-payment-modal');
        }

        function updateButtonToAccessCourse(courseId) {
            const button = document.querySelector(`#paymentBtn_${courseId}`);
            if (!button) {
                return;
            }

            const newLink = document.createElement('a');
            newLink.href = `/topics/${courseId}`;
            newLink.className = 'action-btn continue';
            newLink.innerHTML = '<i class="fas fa-play"></i> <span>Access Course</span>';
            button.parentNode.replaceChild(newLink, button);
            newLink.style.animation = 'successPulse 0.8s ease-in-out';
            showPaymentCompletedNotification();
        }

        function updateButtonToRejectedCourse(courseId) {
            const button = document.querySelector(`#paymentBtn_${courseId}`);
            if (!button) {
                return;
            }

            button.innerHTML = '<i class="fas fa-exclamation-triangle" style="color: #dc2626;"></i> <span style="color: #dc2626; font-weight: bold;">Payment Rejected</span>';
            button.disabled = false;
            button.style.backgroundColor = '#fef2f2';
            button.style.borderColor = '#fecaca';
            button.style.color = '#dc2626';
            button.style.cursor = 'pointer';
            button.style.pointerEvents = 'auto';
            button.style.transition = 'all 0.3s ease';
            // Re-enable payment modal after a brief delay to show rejection
            setTimeout(() => {
                button.classList.add('show-payment-modal');
                button.innerHTML = '<i class="fas fa-credit-card"></i> <span>Retry Payment</span>';
                button.style.backgroundColor = '';
                button.style.borderColor = '';
                button.style.color = '';
            }, 3000);
        }

        function showPaymentCompletedNotification() {
            const notification = document.createElement('div');
            notification.className = 'position-fixed top-0 end-0 p-3';
            notification.style.zIndex = '9999';
            notification.innerHTML = `
                <div class="toast show" role="alert">
                    <div class="toast-header bg-success text-white">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong class="me-auto">Payment Approved!</strong>
                    </div>
                    <div class="toast-body">
                        Your payment has been approved. You can now access the course!
                    </div>
                </div>
            `;
            document.body.appendChild(notification);

            setTimeout(() => notification.remove(), 5000);
        }
    });

    let filterTimeout;
    window.filterCoursesByAcademicYear = function filterCoursesByAcademicYear() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(() => {
            const selectedYear = document.getElementById('semesterDropdown').value;
            const courseCards = document.querySelectorAll('.course-card');

            requestAnimationFrame(() => {
                courseCards.forEach(card => {
                    const cardAcademicYear = card.getAttribute('data-academic-year');

                    if (selectedYear === '' || cardAcademicYear === selectedYear) {
                        card.hidden = false;
                        card.style.opacity = '1';
                    } else {
                        card.hidden = true;
                        card.style.opacity = '0';
                    }
                });

                updateScrollArrows();
            });
        }, 100);
    };

    document.addEventListener('DOMContentLoaded', function() {
        requestAnimationFrame(() => {
            const dropdown = document.getElementById('semesterDropdown');
            if (dropdown && dropdown.value) {
                window.filterCoursesByAcademicYear();
            } else {
                document.querySelectorAll('.course-card').forEach(card => {
                    card.hidden = false;
                    card.style.opacity = '1';
                });
            }

            updateScrollArrows();
        });
    });

    window.MedCapsule = {
        csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        baseUrl: window.location.origin,
        profileRoute: coursesPageConfig.profileRoute || '',
        user: coursesPageConfig.user || null,
        performance: {
            startTime: performance.now(),
            domReady: false
        }
    };

    (function() {
        const perfData = {
            startTime: performance.now(),
            domStart: null,
            domEnd: null,
            imagesLoaded: 0,
            totalImages: 0,
            totalScripts: 0,
            totalCSS: 0,
            bottlenecks: []
        };

        function markMilestone(name, time = performance.now()) {
            const duration = time - perfData.startTime;

            if (duration > 500) {
                perfData.bottlenecks.push({ name, duration });
            }

            if (window.performance && window.performance.mark) {
                window.performance.mark(name);
            }
        }

        if (document.readyState === 'loading') {
            perfData.domStart = performance.now();
            document.addEventListener('DOMContentLoaded', () => {
                perfData.domEnd = performance.now();
                markMilestone('DOM_READY', perfData.domEnd);
            });
        } else {
            markMilestone('DOM_ALREADY_READY');
        }

        window.addEventListener('load', () => {
            markMilestone('WINDOW_LOAD');
            perfData.totalImages = document.querySelectorAll('img, [style*="background-image"], [data-bg-image]').length;
            perfData.totalScripts = document.querySelectorAll('script[src]').length;
            perfData.totalCSS = document.querySelectorAll('link[rel="stylesheet"]').length;

            setTimeout(() => {
                generatePerformanceReport();
            }, 100);
        });

        function trackImageLoading() {
            const images = document.querySelectorAll('img');
            images.forEach(img => {
                if (img.complete) {
                    perfData.imagesLoaded++;
                } else {
                    img.addEventListener('load', () => {
                        perfData.imagesLoaded++;
                        if (perfData.imagesLoaded === images.length) {
                            markMilestone('ALL_IMAGES_LOADED');
                        }
                    });
                    img.addEventListener('error', () => {
                        perfData.imagesLoaded++;
                    });
                }
            });
        }

        function generatePerformanceReport() {
            const totalTime = performance.now() - perfData.startTime;

            window.PERF_DATA = perfData;
            window.PERF_DATA.totalTime = totalTime;
        }

        document.addEventListener('DOMContentLoaded', () => {
            trackImageLoading();

            setTimeout(() => {
                if (document.querySelector('.navbar')) {
                    markMilestone('NAVBAR_RENDERED');
                }
                if (document.querySelector('.courses-grid')) {
                    markMilestone('COURSES_GRID_RENDERED');
                }
                if (document.querySelector('.clean-notification-bell')) {
                    markMilestone('NOTIFICATION_BELL_RENDERED');
                }
            }, 10);
        });

        window.runPerformanceTest = function() {
            location.reload();
        };

        window.analyzeBottlenecks = function() {
            if (window.PERF_DATA) {
                return window.PERF_DATA;
            }
            return null;
        };
    })();

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    }

    document.addEventListener('DOMContentLoaded', function() {
        const profileCircles = document.querySelectorAll('.profile-circle, .profile-circle-small');

        profileCircles.forEach(circle => {
            circle.addEventListener('error', function() {
                this.style.backgroundImage = 'none';
                this.classList.add('no-image');
                this.style.background = 'linear-gradient(135deg, #007bff, #00d4ff)';
            });

            const computedStyle = window.getComputedStyle(circle);
            if (!computedStyle.backgroundImage || computedStyle.backgroundImage === 'none') {
                circle.classList.add('no-image');
                circle.style.background = 'linear-gradient(135deg, #007bff, #00d4ff)';
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const scrollProgress = document.getElementById('scrollProgress');
        const onWindowScroll = () => {
            if (!scrollProgress) {
                return;
            }
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const progress = (scrollTop / scrollHeight) * 100;
            scrollProgress.style.width = progress + '%';
        };

        let scrollTicking = false;
        window.addEventListener('scroll', () => {
            if (scrollTicking) {
                return;
            }
            scrollTicking = true;
            requestAnimationFrame(() => {
                onWindowScroll();
                scrollTicking = false;
            });
        }, { passive: true });

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

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href !== '#' && document.querySelector(href)) {
                    e.preventDefault();
                    document.querySelector(href).scrollIntoView({ behavior: 'smooth' });
                }
            });
        });

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
    });
})();
