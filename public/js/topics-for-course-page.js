(function() {
    const topicsPageConfig = window.TopicsPageConfig || {};

    document.addEventListener('DOMContentLoaded', function() {
        const topicsContainer = document.querySelector('.topics-container');
        const caseFilters = document.querySelectorAll('.case-filter');
        const topicCards = document.querySelectorAll('.topic-card');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sourceModal = document.getElementById('sourceSelectionModal');
        const sourcesContainer = document.querySelector('.sources-container');
        const sourceTopicId = document.getElementById('source_topic_id');
        const sourceCourseId = document.getElementById('source_course_id');
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        const sourceSelectionForm = document.getElementById('sourceSelectionForm');
        const tutorScopeSection = document.getElementById('tutorScopeSection');
        const tutorScopeHint = document.getElementById('tutorScopeHint');
        const mobileHamburger = document.getElementById('topicsMobileHamburger');
        const mobileSidebar = document.getElementById('topicsSidebar');
        const mobileSidebarBackdrop = document.getElementById('topicsSidebarBackdrop');
        const mobileSidebarCloseBtn = document.getElementById('topicsSidebarCloseBtn');
        const randomQuizForm = document.getElementById('randomQuizForm');
        const randomQuizModal = document.getElementById('randomQuizModal');
        const assignmentIconCircle = document.querySelector('.assignment-icon-circle');
        const assignmentCircle = document.querySelector('.assignment-circle');
        const progressCountElement = document.querySelector('.progress-count');
        const progressPercentElement = document.querySelector('.progress-percent');
        const topicProgressSegments = Array.from(document.querySelectorAll('.topics-progress-segment[data-topic-id]'));
        const progressPrevBtn = document.getElementById('progressPrevBtn');
        const progressNextBtn = document.getElementById('progressNextBtn');
        const topicsProgressTrack = document.getElementById('topicsProgressTrack');
        const questionLimit = document.getElementById('questionLimit');
        const timeLimit = document.getElementById('timeLimit');
        const courseCheckboxes = document.querySelectorAll('#randomQuizModal input[name="selected_courses[]"]');
        const sidebarStorageKey = 'medcapsule-topics-sidebar-collapsed';
        const recentProgressRoute = topicsPageConfig.recentProgressRoute || '/api/progress/recent-updates';
        let latestProgressSyncTimestamp = 0;

        const perfStore = window.__topicsPerfStore || (window.__topicsPerfStore = {
            data: new Map(),
            inflight: new Map(),
            prefetched: new Set()
        });

        const getStoredSidebarState = () => {
            try {
                return localStorage.getItem(sidebarStorageKey);
            } catch (error) {
                return null;
            }
        };

        const setStoredSidebarState = (collapsed) => {
            try {
                localStorage.setItem(sidebarStorageKey, collapsed ? 'true' : 'false');
            } catch (error) {
                return;
            }
        };

        const applySidebarState = (collapsed) => {
            document.body.classList.toggle('sidebar-collapsed', collapsed);
            if (sidebarToggle) {
                sidebarToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            }
        };

        // Only restore the desktop sidebar-collapsed state on actual desktop
        // On tablet/iPad (768–991px) and mobile (<768px), the sidebar is now an
        // offcanvas drawer — sidebar-collapsed has no meaningful effect there.
        const savedSidebarState = getStoredSidebarState();
        if (savedSidebarState !== null && window.innerWidth >= 992) {
            applySidebarState(savedSidebarState === 'true');
        }

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                const collapsed = !document.body.classList.contains('sidebar-collapsed');
                applySidebarState(collapsed);
                setStoredSidebarState(collapsed);
            });
        }

        function initializeMobileSidebar() {
            if (!mobileHamburger || !mobileSidebar || !mobileSidebarBackdrop) {
                return;
            }

            function openSidebar() {
                mobileSidebar.classList.add('is-open');
                mobileSidebarBackdrop.classList.add('is-visible');
                document.body.classList.add('topics-sidebar-open');
                mobileHamburger.setAttribute('aria-expanded', 'true');
            }

            function closeSidebar() {
                mobileSidebar.classList.remove('is-open');
                mobileSidebarBackdrop.classList.remove('is-visible');
                document.body.classList.remove('topics-sidebar-open');
                mobileHamburger.setAttribute('aria-expanded', 'false');
            }

            mobileHamburger.addEventListener('click', function() {
                if (mobileSidebar.classList.contains('is-open')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });

            mobileSidebarBackdrop.addEventListener('click', closeSidebar);
            mobileSidebarCloseBtn?.addEventListener('click', closeSidebar);
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeSidebar();
                }
            });
        }

        function initializeProgressTrackNavigation() {
            if (!progressPrevBtn || !progressNextBtn || !topicsProgressTrack) {
                return;
            }

            const allTopics = Array.isArray(topicsPageConfig.progressTopics)
                ? topicsPageConfig.progressTopics
                : [];
            const totalTopics = allTopics.length;
            const segmentsToShow = 10;
            let currentIndex = 0;

            function updateSegments() {
                const endIndex = Math.min(currentIndex + segmentsToShow, totalTopics);
                topicsProgressTrack.innerHTML = '';

                for (let index = currentIndex; index < endIndex; index += 1) {
                    const topic = allTopics[index];
                    if (!topic) {
                        continue;
                    }

                    const topicProgress = Array.isArray(topic.user_progress) ? topic.user_progress : [];
                    const isMastered = topicProgress.length > 0 && Number(topicProgress[0]?.percentage_grade || 0) >= 80;

                    const segment = document.createElement('span');
                    segment.className = `topics-progress-segment${isMastered ? ' is-mastered' : ''}`;
                    segment.setAttribute('data-topic-id', String(topic.id));
                    segment.setAttribute('data-index', String(index));
                    segment.setAttribute('aria-hidden', 'true');
                    segment.style.cursor = 'pointer';
                    segment.addEventListener('click', function() {
                        const topicId = this.getAttribute('data-topic-id');
                        const topicCard = document.querySelector(`.topic-card[data-topic-id="${topicId}"]`);
                        if (!topicCard) {
                            return;
                        }

                        const allFilter = document.querySelector('.case-filter[data-filter="all"]');
                        if (allFilter && !allFilter.classList.contains('active')) {
                            allFilter.click();
                        }

                        const searchInput = document.getElementById('topicSearch');
                        if (searchInput && searchInput.value) {
                            searchInput.value = '';
                            searchInput.dispatchEvent(new Event('input'));
                        }

                        window.setTimeout(() => {
                            topicCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            topicCard.classList.add('topic-card-highlight');
                            window.setTimeout(() => {
                                topicCard.classList.remove('topic-card-highlight');
                            }, 2000);
                        }, 50);
                    });

                    topicsProgressTrack.appendChild(segment);
                }

                progressPrevBtn.disabled = currentIndex === 0;
                progressNextBtn.disabled = endIndex >= totalTopics;
                progressPrevBtn.style.opacity = currentIndex === 0 ? '0.3' : '1';
                progressNextBtn.style.opacity = endIndex >= totalTopics ? '0.3' : '1';
            }

            progressPrevBtn.addEventListener('click', function() {
                if (currentIndex > 0) {
                    currentIndex = Math.max(0, currentIndex - segmentsToShow);
                    updateSegments();
                }
            });

            progressNextBtn.addEventListener('click', function() {
                if (currentIndex + segmentsToShow < totalTopics) {
                    currentIndex = Math.min(currentIndex + segmentsToShow, totalTopics - segmentsToShow);
                    updateSegments();
                }
            });

            updateSegments();
        }

        function showTaskCelebrationToast() {
            const celebration = topicsPageConfig.taskCelebration || {};
            const completedTasks = Array.isArray(celebration.just_completed_tasks)
                ? celebration.just_completed_tasks
                : [];
            const awardedNow = Number(celebration.awarded_now || 0);
            const toastStack = document.getElementById('taskToastStack');

            if (!toastStack || awardedNow <= 0 || completedTasks.length === 0) {
                return;
            }

            const primaryTask = completedTasks[0];
            const taskCount = completedTasks.length;
            const title = taskCount === 1
                ? primaryTask.title
                : `${taskCount} tasks completed`;
            const meta = taskCount === 1
                ? `+${primaryTask.xp} XP earned`
                : `+${awardedNow} XP earned`;

            const toast = document.createElement('div');
            toast.className = 'task-toast';
            toast.innerHTML = `
                <div class="task-toast-icon" aria-hidden="true">
                    <i class="fas fa-check"></i>
                </div>
                <div class="task-toast-copy">
                    <span class="task-toast-kicker">Task Complete</span>
                    <strong class="task-toast-title">${title}</strong>
                    <span class="task-toast-meta">${meta}</span>
                </div>
            `;

            toastStack.appendChild(toast);

            requestAnimationFrame(() => {
                toast.classList.add('is-visible');
            });

            window.setTimeout(() => {
                toast.classList.remove('is-visible');
                window.setTimeout(() => toast.remove(), 260);
            }, 3200);
        }

        function cachedFetchJson(url, options = {}, ttlMs = 0, cacheKey = url) {
            const now = Date.now();
            const cached = perfStore.data.get(cacheKey);

            if (cached && ttlMs > 0 && (now - cached.ts) < ttlMs) {
                return Promise.resolve(cached.payload);
            }

            const inflight = perfStore.inflight.get(cacheKey);
            if (inflight) {
                return inflight;
            }

            const req = fetch(url, options)
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then((payload) => {
                    perfStore.data.set(cacheKey, { ts: Date.now(), payload });
                    return payload;
                })
                .finally(() => {
                    perfStore.inflight.delete(cacheKey);
                });

            perfStore.inflight.set(cacheKey, req);
            return req;
        }

        function debounce(fn, wait = 180) {
            let timeoutId;
            return (...args) => {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => fn(...args), wait);
            };
        }

        function prefetchQuizUrl(url) {
            if (!url || perfStore.prefetched.has(url)) {
                return;
            }

            perfStore.prefetched.add(url);

            const link = document.createElement('link');
            link.rel = 'prefetch';
            link.as = 'document';
            link.href = url;
            document.head.appendChild(link);
        }

        function normalizeTimestamp(value) {
            const numericValue = Number(value || 0);
            if (!Number.isFinite(numericValue)) {
                return 0;
            }

            return numericValue > 9999999999 ? Math.floor(numericValue / 1000) : numericValue;
        }

        function getOverallTopicPercentage(detail) {
            const overallPercentage = Number(detail?.overall_topic_progress?.overall?.percentage);
            if (Number.isFinite(overallPercentage)) {
                return Math.max(0, Math.min(100, overallPercentage));
            }

            if (detail?.allSourcesMastered) {
                return 100;
            }

            return null;
        }

        function updateCourseProgressSummary(topicId, percentage) {
            if (!topicProgressSegments.length || percentage === null) {
                return;
            }

            const targetSegment = document.querySelector(`.topics-progress-segment[data-topic-id="${topicId}"]`);
            if (!targetSegment) {
                return;
            }

            targetSegment.classList.toggle('is-mastered', percentage >= 80);

            const totalTopics = topicProgressSegments.length;
            const masteredCount = topicProgressSegments.filter(segment => segment.classList.contains('is-mastered')).length;
            const masteredPercentage = totalTopics > 0 ? Math.round((masteredCount / totalTopics) * 100) : 0;

            if (progressCountElement) {
                progressCountElement.textContent = `${masteredCount} of ${totalTopics} complete`;
            }

            if (progressPercentElement) {
                progressPercentElement.textContent = `${masteredPercentage}%`;
            }
        }

        function updateTopicCardProgress(detail) {
            const { topicId, assignmentId, mode } = detail;
            if (mode === 'tutor') {
                return false;
            }

            let targetCard = null;

            if (assignmentId) {
                targetCard = document.querySelector(`[data-assignment-id="${assignmentId}"]`);
            } else {
                targetCard = document.querySelector(`[data-topic-id="${topicId}"][data-has-assignment="false"]`);
            }

            if (!targetCard) {
                return false;
            }

            const newPercent = getOverallTopicPercentage(detail);

            const gradeBadge = targetCard.querySelector('.grade-badge');
            if (gradeBadge && newPercent !== null) {
                gradeBadge.className = 'grade-badge';
                if (newPercent >= 100) {
                    gradeBadge.classList.add('grade-mastered');
                    gradeBadge.innerHTML = '<i class="fas fa-star"></i> Mastered';
                } else if (newPercent >= 66) {
                    gradeBadge.classList.add('grade-good');
                    gradeBadge.innerHTML = '<i class="fas fa-check"></i> Good';
                } else if (newPercent >= 33) {
                    gradeBadge.classList.add('grade-in-progress');
                    gradeBadge.innerHTML = '<i class="fas fa-sync-alt"></i> In Progress';
                } else {
                    gradeBadge.classList.add('grade-bad');
                    gradeBadge.innerHTML = '<i class="fas fa-times"></i> Just Started';
                }
            }

            const progressBar = targetCard.querySelector('.progress .progress-bar');
            const progressLabel = targetCard.querySelector('.progress-label span:last-child');

            if (progressBar && newPercent !== null) {
                progressBar.style.width = `${newPercent}%`;
            }

            if (progressLabel && newPercent !== null) {
                progressLabel.textContent = `${newPercent.toFixed(1)}%`;
            }

            const statItem = targetCard.querySelector('.topic-actions .stat-item');
            if (statItem && newPercent !== null) {
                if (newPercent >= 100) {
                    statItem.innerHTML = '<i class="fas fa-check-circle text-success"></i> Mastered';
                } else if (newPercent >= 66) {
                    statItem.innerHTML = '<i class="fas fa-check text-success"></i> Good';
                } else if (newPercent >= 33) {
                    statItem.innerHTML = '<i class="fas fa-sync-alt text-info"></i> Making progress';
                } else {
                    statItem.innerHTML = '<i class="fas fa-rocket text-primary"></i> Just getting started';
                }
            }

            const quizBtn = targetCard.querySelector('.start-quiz-btn span');
            if (quizBtn && quizBtn.textContent.trim() === 'Start Quiz') {
                quizBtn.textContent = 'Retake Quiz';
            }

            if (!assignmentId) {
                updateCourseProgressSummary(topicId, newPercent);
            } else {
                updateAssignmentProgress(assignmentId, targetCard);
            }

            return true;
        }

        function applyProgressUpdate(detail) {
            const hasMatchingTopicCard = detail?.assignmentId
                ? Boolean(document.querySelector(`[data-assignment-id="${detail.assignmentId}"]`))
                : Boolean(document.querySelector(`[data-topic-id="${detail?.topicId}"][data-has-assignment="false"]`));
            const hasMatchingHeaderSegment = Boolean(document.querySelector(`.topics-progress-segment[data-topic-id="${detail?.topicId}"]`));
            const isCurrentModalTopic = sourceTopicId && Number(sourceTopicId.value || 0) === Number(detail?.topicId || 0);
            const shouldApply = hasMatchingTopicCard || hasMatchingHeaderSegment || isCurrentModalTopic;

            if (!shouldApply) {
                return;
            }

            const timestamp = normalizeTimestamp(detail?.timestamp);
            if (timestamp > 0 && timestamp <= latestProgressSyncTimestamp) {
                return;
            }

            if (timestamp > 0) {
                latestProgressSyncTimestamp = timestamp;
            }

            const progress = detail?.progress || detail?.sourceProgress || {};
            const availableSources = Array.isArray(detail?.availableSources)
                ? detail.availableSources
                : Object.keys(progress);

            if (sourceModal && sourceModal.classList.contains('show') && progress && Object.keys(progress).length > 0) {
                updateSourcesContainer({
                    progress,
                    availableSources
                });
            }

            updateTopicCardProgress({
                ...detail,
                progress,
                availableSources
            });
        }

        function hydrateProgressFromStorage() {
            try {
                const rawUpdate = localStorage.getItem('quizProgressUpdate');
                if (!rawUpdate) {
                    return;
                }

                applyProgressUpdate(JSON.parse(rawUpdate));
            } catch (error) {
                console.error('Failed to hydrate quiz progress from storage:', error);
            }
        }

        function applyAssignmentCompletion(detail) {
            const assignmentId = Number(detail?.assignmentId || 0);
            if (!assignmentId) {
                return;
            }

            const targetCard = document.querySelector(`[data-assignment-id="${assignmentId}"]`);
            if (!targetCard) {
                return;
            }

            targetCard.dataset.assignmentStatus = 'completed';
            targetCard.classList.remove('assignment-active', 'assignment-overdue');
            targetCard.classList.add('assignment-completed');

            const statusBar = targetCard.querySelector('.assignment-status-bar');
            if (statusBar) {
                statusBar.className = 'assignment-status-bar assignment-completed';
            }

            const assignmentInfo = targetCard.querySelector('.assignment-info-minimal');
            if (assignmentInfo) {
                assignmentInfo.innerHTML = '<span><i class="fas fa-check-circle text-success"></i> Assignment completed</span>';
            }

            const countdown = targetCard.querySelector('.assignment-countdown-minimal');
            if (countdown) {
                pauseCountdown(countdown);
                countdown.remove();
            }

            const statItem = targetCard.querySelector('.stat-item');
            if (statItem) {
                statItem.innerHTML = '<i class="fas fa-check-circle text-success"></i>Assignment Completed';
            }

            const actionButton = targetCard.querySelector('.start-quiz-btn');
            if (actionButton) {
                actionButton.style.pointerEvents = 'none';
                actionButton.style.opacity = '0.65';
                actionButton.innerHTML = '<span>Completed</span><i class="fas fa-check"></i>';
            }
        }

        function hydrateAssignmentCompletionFromStorage() {
            try {
                const rawCompletion = localStorage.getItem('assignmentCompleted');
                if (!rawCompletion) {
                    return;
                }

                applyAssignmentCompletion(JSON.parse(rawCompletion));
            } catch (error) {
                console.error('Failed to hydrate assignment completion from storage:', error);
            }
        }

        async function fetchRecentProgressUpdates() {
            try {
                const response = await fetch(`${recentProgressRoute}?since=${latestProgressSyncTimestamp}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                if (!data.success || !Array.isArray(data.updates)) {
                    return;
                }

                data.updates
                    .sort((a, b) => normalizeTimestamp(a.timestamp) - normalizeTimestamp(b.timestamp))
                    .forEach(update => applyProgressUpdate(update));
            } catch (error) {
                console.error('Failed to fetch recent topic progress updates:', error);
            }
        }

        const topicSearch = document.getElementById('topicSearch');
        const clearSearch = document.getElementById('clearSearch');

        const searchableCards = Array.from(topicCards).map(card => {
            const titleElement = card.querySelector('.topic-title-text');
            const originalTitle = (titleElement?.textContent || '').trim();

            return {
                card,
                caseType: (card.dataset.caseType || '').toLowerCase(),
                hasAssignment: card.dataset.hasAssignment === 'true',
                titleElement,
                originalTitle,
                originalTitleLower: originalTitle.toLowerCase()
            };
        });

        function updateTitleHighlight(cardMeta, searchTerm) {
            const { titleElement, originalTitle } = cardMeta;
            if (!titleElement) {
                return;
            }

            if (!searchTerm) {
                titleElement.textContent = originalTitle;
                return;
            }

            const originalLower = cardMeta.originalTitleLower;
            let searchStart = 0;
            let matchIndex = originalLower.indexOf(searchTerm, searchStart);

            if (matchIndex === -1) {
                titleElement.textContent = originalTitle;
                return;
            }

            const fragment = document.createDocumentFragment();

            while (matchIndex !== -1) {
                if (matchIndex > searchStart) {
                    fragment.appendChild(document.createTextNode(originalTitle.slice(searchStart, matchIndex)));
                }

                const highlight = document.createElement('span');
                highlight.className = 'search-highlight';
                highlight.textContent = originalTitle.slice(matchIndex, matchIndex + searchTerm.length);
                fragment.appendChild(highlight);

                searchStart = matchIndex + searchTerm.length;
                matchIndex = originalLower.indexOf(searchTerm, searchStart);
            }

            if (searchStart < originalTitle.length) {
                fragment.appendChild(document.createTextNode(originalTitle.slice(searchStart)));
            }

            titleElement.textContent = '';
            titleElement.appendChild(fragment);
        }

        function applyFiltersAndSearch() {
            const searchTerm = topicSearch?.value.toLowerCase().trim() || '';
            const activeFilter = document.querySelector('.case-filter.active')?.dataset.filter || 'all';
            const isAssignmentsFilterActive = topicsContainer?.classList.contains('assignments-filter-active');

            searchableCards.forEach(cardMeta => {
                const { card, caseType, hasAssignment, originalTitleLower } = cardMeta;

                let passesFilter = false;
                if (isAssignmentsFilterActive) {
                    passesFilter = hasAssignment;
                } else if (activeFilter === 'all') {
                    passesFilter = !hasAssignment;
                } else {
                    passesFilter = !hasAssignment && caseType === activeFilter.toLowerCase();
                }

                const passesSearch = !searchTerm || (passesFilter && originalTitleLower.includes(searchTerm));
                updateTitleHighlight(cardMeta, (passesFilter && passesSearch) ? searchTerm : '');

                const isVisible = passesFilter && passesSearch;
                card.style.display = isVisible ? 'block' : 'none';
                if (isVisible) {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }

                // Also hide/show the parent wrapper to prevent empty white space
                const wrapper = card.closest('.topic-with-video-container');
                if (wrapper) {
                    wrapper.style.display = isVisible ? '' : 'none';
                }
            });
        }

        initializeMobileSidebar();
        initializeProgressTrackNavigation();

        if (topicSearch && clearSearch) {
            topicSearch.addEventListener('input', function() {
                clearSearch.style.display = this.value.trim() ? 'flex' : 'none';
                applyFiltersAndSearch();
            });

            clearSearch.addEventListener('click', function() {
                topicSearch.value = '';
                this.style.display = 'none';
                applyFiltersAndSearch();
                topicSearch.focus();
            });
        }

        if (assignmentIconCircle && assignmentCircle) {
            assignmentIconCircle.addEventListener('click', function() {
                caseFilters.forEach(f => f.classList.remove('active'));
                const circleElement = this.querySelector('.assignment-circle');
                if (circleElement) {
                    circleElement.classList.add('active');
                }
                if (topicsContainer) {
                    topicsContainer.classList.add('assignments-filter-active');
                }
                applyFiltersAndSearch();
            });
        }

        showTaskCelebrationToast();
        applyFiltersAndSearch();

        caseFilters.forEach(filter => {
            filter.addEventListener('click', function() {
                caseFilters.forEach(f => f.classList.remove('active'));
                if (assignmentCircle) {
                    assignmentCircle.classList.remove('active');
                }
                this.classList.add('active');
                const filterType = this.dataset.filter || this.textContent.trim().toLowerCase();

                if (filterType === 'assignments') {
                    topicsContainer?.classList.add('assignments-filter-active');
                } else {
                    topicsContainer?.classList.remove('assignments-filter-active');
                }

                applyFiltersAndSearch();

                // On mobile, scroll so filtered topics are visible
                if (window.innerWidth <= 767.98 && topicsContainer) {
                    const filtersEl = document.querySelector('.case-filters');
                    if (filtersEl) {
                        filtersEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            });
        });

        const countdownObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                const countdown = entry.target;
                if (entry.isIntersecting) {
                    startCountdown(countdown);
                } else {
                    pauseCountdown(countdown);
                }
            });
        }, { threshold: 0.1 });

        const activeCountdowns = new Map();

        function startCountdown(countdown) {
            if (activeCountdowns.has(countdown)) {
                return;
            }

            const updateInterval = setInterval(() => {
                updateSingleCountdown(countdown);
            }, 1000);

            activeCountdowns.set(countdown, updateInterval);
            updateSingleCountdown(countdown);
        }

        function pauseCountdown(countdown) {
            const interval = activeCountdowns.get(countdown);
            if (interval) {
                clearInterval(interval);
                activeCountdowns.delete(countdown);
            }
        }

        function updateSingleCountdown(countdown) {
            const deadline = new Date(countdown.dataset.deadline);
            const now = new Date();
            const timeLeft = deadline - now;

            if (timeLeft <= 0) {
                handleOverdueAssignment(countdown);
                pauseCountdown(countdown);
                return;
            }

            const totalSeconds = Math.floor(timeLeft / 1000);
            const days = Math.floor(totalSeconds / (3600 * 24));
            const hours = Math.floor((totalSeconds % (3600 * 24)) / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;

            let displayHours = hours;
            const ampm = displayHours >= 12 ? 'PM' : 'AM';
            displayHours = displayHours % 12;
            displayHours = displayHours ? displayHours : 12;

            const formattedHours12 = String(displayHours).padStart(2, '0');
            const formattedMinutes = String(minutes).padStart(2, '0');
            const formattedSeconds = String(seconds).padStart(2, '0');

            const countdownText = days > 0
                ? `${days}d ${formattedHours12}:${formattedMinutes}:${formattedSeconds} ${ampm}`
                : `${formattedHours12}:${formattedMinutes}:${formattedSeconds} ${ampm}`;

            if (timeLeft < 3600000) {
                countdown.classList.add('countdown-urgent');
            } else {
                countdown.classList.remove('countdown-urgent');
            }

            const display = countdown.querySelector('.countdown-display');
            if (display) {
                display.textContent = countdownText;
            }

            if (timeLeft < 3600000) {
                const banner = countdown.closest('.assignment-status-banner');
                if (banner) {
                    banner.style.animation = 'urgentBanner 1s infinite';
                }
            }
        }

        function handleOverdueAssignment(countdown) {
            const topicCard = countdown.closest('.topic-card');
            if (!topicCard) {
                return;
            }

            topicCard.classList.add('topic-locked');

            const banner = topicCard.querySelector('.assignment-status-banner');
            if (banner) {
                banner.className = 'assignment-status-banner assignment-overdue';
                banner.innerHTML = '<div class="assignment-info overdue"><i class="fas fa-exclamation-triangle"></i><span class="assignment-label">ASSIGNMENT OVERDUE</span><span class="overdue-text">Topic Locked</span></div>';
            }

            const startBtn = topicCard.querySelector('.start-quiz-btn');
            if (startBtn) {
                Object.assign(startBtn.style, {
                    pointerEvents: 'none',
                    background: '#f1f3f4',
                    color: '#9ca3af',
                    border: '2px solid #e5e7eb',
                    opacity: '0.6',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                });
                startBtn.innerHTML = '<i class="fas fa-lock lock-icon" style="color: #9ca3af; font-size: 1.2em; margin: 0;"></i>';
            }
        }

        document.querySelectorAll('.assignment-countdown-minimal').forEach(countdown => {
            countdownObserver.observe(countdown);
        });

        document.addEventListener('sourceProgressUpdated', function(e) {
            applyProgressUpdate(e.detail);
        });

        // 🔥 NEW: Cross-tab communication via storage event
        window.addEventListener('storage', function(e) {
            if (e.key === 'quizProgressUpdate' && e.newValue) {
                try {
                    const data = JSON.parse(e.newValue);

                    applyProgressUpdate({
                        ...data,
                        mode: data.mode || 'quiz'
                    });
                } catch (err) {
                    console.error('Error parsing storage update:', err);
                }
            }

            if (e.key === 'assignmentCompleted' && e.newValue) {
                try {
                    applyAssignmentCompletion(JSON.parse(e.newValue));
                } catch (err) {
                    console.error('Error parsing assignment completion update:', err);
                }
            }
        });

        hydrateProgressFromStorage();
        hydrateAssignmentCompletionFromStorage();
        fetchRecentProgressUpdates();
        window.addEventListener('pageshow', function(e) {
            // Small delay to let server-side POST from quiz completion settle
            setTimeout(fetchRecentProgressUpdates, e.persisted ? 300 : 0);
        });

        // Instant AJAX polling: fetch updates every 15 seconds while page is visible
        let progressPollInterval = null;
        function startProgressPolling() {
            if (progressPollInterval) return;
            progressPollInterval = setInterval(function() {
                fetchRecentProgressUpdates();
            }, 15000);
        }
        function stopProgressPolling() {
            if (progressPollInterval) {
                clearInterval(progressPollInterval);
                progressPollInterval = null;
            }
        }
        startProgressPolling();

        // Also re-fetch immediately when tab becomes visible again
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                // Slight delay to ensure server has processed any quiz completion POST
                setTimeout(function() {
                    fetchRecentProgressUpdates();
                    hydrateProgressFromStorage();
                    hydrateAssignmentCompletionFromStorage();
                }, 300);
                startProgressPolling();
            } else {
                stopProgressPolling();
            }
        });

        document.addEventListener('assignmentCompleted', function(e) {
            applyAssignmentCompletion(e.detail);
        });

        let sourceSelectionModal;
        function getModal() {
            if (!sourceSelectionModal && sourceModal) {
                sourceSelectionModal = new bootstrap.Modal(sourceModal);
            }
            return sourceSelectionModal;
        }

        function updateTutorSourceCtaState() {
            if (!sourceSelectionForm || !startSourceQuizBtn) {
                return;
            }

            const selectedModeInput = sourceSelectionForm.querySelector('input[name="quiz_mode"]:checked');
            const selectedMode = selectedModeInput ? selectedModeInput.value : 'quiz';
            const tutorScope = sourceSelectionForm.querySelector('input[name="tutor_scope"]:checked')?.value || 'remaining';
            const selectedCards = getSelectedSourceCards();
            const totalRemaining = selectedCards.reduce((sum, card) => {
                return sum + Number(card.dataset.remainingQuestions || 0);
            }, 0);
            const totalAttempted = selectedCards.reduce((sum, card) => {
                return sum + Number(card.dataset.attemptedQuestions || card.dataset.completedQuestions || 0);
            }, 0);

            if (selectedMode !== 'tutor') {
                startSourceQuizBtn.innerHTML = '<i class="fas fa-play me-2"></i>Start Quiz';
                return;
            }

            if (tutorScope === 'remaining' && totalRemaining > 0 && totalAttempted > 0) {
                startSourceQuizBtn.innerHTML = '<i class="fas fa-rotate-right me-2"></i>Continue Solving';
                return;
            }

            startSourceQuizBtn.innerHTML = '<i class="fas fa-play me-2"></i>Start Tutor Mode';
        }

        document.querySelectorAll('input[name="quiz_mode"]').forEach(radio => {
            radio.addEventListener('change', async function() {
                const topicId = sourceTopicId.value;
                if (!topicId) {
                    return;
                }

                sourcesContainer.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

                try {
                    const mode = this.value;
                    const data = await cachedFetchJson(`/topics/${topicId}/source-progress?mode=${mode}`, {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken.content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        credentials: 'same-origin'
                    }, 15000, `source-progress:${topicId}:${mode}`);

                    updateSourcesContainer(data);
                } catch (error) {
                    sourcesContainer.innerHTML = '<div class="alert alert-danger">Error loading sources. Please try again.</div>';
                    updateTutorSourceCtaState();
                }
            });
        });

        async function updateAssignmentProgress(assignmentId, targetCard) {
            try {
                const response = await fetch(`/api/assignments/${assignmentId}/progress`, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin'
                });

                if (response.ok) {
                    const data = await response.json();
                    const progressBar = targetCard.querySelector('.progress .progress-bar');
                    const progressLabel = targetCard.querySelector('.progress-label span:last-child');

                    if (progressBar && data.progress !== undefined) {
                        progressBar.style.width = `${data.progress}%`;
                    }

                    if (progressLabel && data.progress !== undefined) {
                        progressLabel.textContent = `${data.progress.toFixed(1)}%`;
                    }
                }
            } catch (error) {
                console.error('Failed to update assignment progress:', error);
            }
        }

        function updateSourcesContainer(data) {
            sourcesContainer.innerHTML = '';

            if (!data.availableSources || data.availableSources.length === 0) {
                sourcesContainer.innerHTML = '<div class="alert alert-info">No questions available for this topic.</div>';
                return;
            }

            data.availableSources.forEach(source => {
                const progress = data.progress[source] || { completed: 0, total: 0, percentage: 0 };
                const attemptedCount = Number(progress.attempted_count ?? progress.attempted ?? progress.completed ?? 0);
                const correctCount = Number(progress.correct ?? progress.completed ?? 0);
                const totalAvailable = Number(progress.total_questions_available ?? progress.total ?? 0);
                const remainingCount = Number(progress.remaining_count ?? Math.max(0, totalAvailable - correctCount));
                const card = document.createElement('div');
                card.className = 'source-card';
                card.setAttribute('data-source', source);
                card.dataset.remainingQuestions = String(remainingCount);
                card.dataset.totalQuestionsAvailable = String(totalAvailable);
                card.dataset.completedQuestions = String(attemptedCount);
                card.dataset.attemptedQuestions = String(attemptedCount);
                card.dataset.correctQuestions = String(correctCount);

                if (totalAvailable > 0) {
                    card.classList.add('selected');
                }

                let statusClass;
                let statusText;
                let statusIcon;

                if (data.mode === 'tutor') {
                    if (remainingCount === 0 && totalAvailable > 0) {
                        statusClass = 'completed';
                        statusText = 'Completed';
                        statusIcon = 'fas fa-check-circle';
                    } else if (attemptedCount > 0) {
                        statusClass = 'in-progress';
                        statusText = 'In Progress';
                        statusIcon = 'fas fa-clock';
                    } else {
                        statusClass = 'not-started';
                        statusText = 'Not started yet';
                        statusIcon = 'fas fa-circle';
                    }
                } else if (progress.has_attempts) {
                    if (progress.percentage >= 90) {
                        statusClass = 'completed';
                        statusText = 'Completed';
                        statusIcon = 'fas fa-check-circle';
                    } else if (progress.percentage >= 50) {
                        statusClass = 'in-progress';
                        statusText = 'In Progress';
                        statusIcon = 'fas fa-clock';
                    } else {
                        statusClass = 'failed';
                        statusText = 'Failed';
                        statusIcon = 'fas fa-times-circle';
                    }
                } else {
                    statusClass = 'not-started';
                    statusText = 'Not started yet';
                    statusIcon = 'fas fa-circle';
                }

                card.innerHTML = `
                    <div class="source-status ${statusClass}"></div>
                    <div class="source-content">
                        <div class="source-info-wrapper">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <input type="checkbox" name="sources[]" value="${source}" class="form-check-input" ${progress.total_questions_available > 0 ? 'checked' : ''}>
                                    <span class="ms-2 source-name">${source}</span>
                                </div>
                                <div class="source-status-text ${statusClass}">
                                    <i class="${statusIcon} me-1"></i>${statusText}
                                </div>
                            </div>
                            <div class="source-progress-bar">
                                <div class="source-progress-value ${statusClass}" style="width: ${Number(progress.percentage || 0)}%;">
                                </div>
                            </div>
                            <div class="source-stats">
                                <span class="questions-count">
                                    <i class="fas fa-book-open me-1"></i>${data.mode === 'tutor'
                                        ? `${correctCount}/${totalAvailable} solved correctly`
                                        : `${progress.completed}/${totalAvailable} Questions`}
                                </span>
                                ${data.mode === 'tutor' && attemptedCount > 0 && remainingCount > 0
                                    ? `<span class="questions-count"><i class="fas fa-rotate-right me-1"></i>${remainingCount} left to fix</span>`
                                    : ''}
                            </div>
                        </div>
                    </div>
                `;

                sourcesContainer.appendChild(card);

                // Add robust click handler for the entire card
                card.addEventListener('click', function(e) {
                    // If the user clicked the checkbox directly, the browser already toggled it.
                    // We just need to update the card's visual state.
                    if (e.target.type === 'checkbox') {
                        this.classList.toggle('selected', e.target.checked);
                        
                        // Prevent unchecking the last remaining checkbox
                        const anyChecked = sourcesContainer.querySelector('input[type="checkbox"]:checked');
                        if (!anyChecked) {
                            e.target.checked = true;
                            this.classList.add('selected');
                        }
                        syncTutorScopeState();
                        return;
                    }
                    
                    // If they clicked anything else on the card, manually toggle the checkbox
                    const checkbox = this.querySelector('input[type="checkbox"]');
                    if (checkbox) {
                        const newState = !checkbox.checked;
                        
                        // Prevent unchecking the last remaining checkbox
                        const anyChecked = sourcesContainer.querySelector('input[type="checkbox"]:checked');
                        if (!newState && !anyChecked || (anyChecked && anyChecked.length === 1 && anyChecked[0] === checkbox && !newState)) {
                            // Already handled by logic above, but being explicit
                            return;
                        }
                        
                        checkbox.checked = newState;
                        this.classList.toggle('selected', newState);
                        
                        // Final safety check for "at least one"
                        const stillChecked = sourcesContainer.querySelector('input[type="checkbox"]:checked');
                        if (!stillChecked) {
                            checkbox.checked = true;
                            this.classList.add('selected');
                        }

                        syncTutorScopeState();
                    }
                });
            });

            syncTutorScopeState();
        }

        function getSelectedSourceCards() {
            return Array.from(sourcesContainer.querySelectorAll('.source-card')).filter(card => {
                const checkbox = card.querySelector('input[type="checkbox"]');
                return checkbox && checkbox.checked;
            });
        }

        function syncTutorScopeState() {
            if (!sourceSelectionForm || !tutorScopeSection) {
                return;
            }

            const selectedModeInput = sourceSelectionForm.querySelector('input[name="quiz_mode"]:checked');
            const isTutorMode = selectedModeInput && selectedModeInput.value === 'tutor';
            
            const remainingRadio = document.getElementById('tutorScopeRemaining');
            const fullRadio = document.getElementById('tutorScopeFull');

            if (!isTutorMode) {
                tutorScopeSection.classList.add('d-none');
                if (fullRadio) fullRadio.checked = true;
                updateTutorSourceCtaState();
                return;
            }

            const selectedCards = getSelectedSourceCards();
            const totalRemaining = selectedCards.reduce((sum, card) => {
                return sum + Number(card.dataset.remainingQuestions || 0);
            }, 0);
            const totalCompleted = selectedCards.reduce((sum, card) => {
                return sum + Number(card.dataset.attemptedQuestions || card.dataset.completedQuestions || 0);
            }, 0);

            if (totalCompleted === 0) {
                tutorScopeSection.classList.add('d-none');
                if (remainingRadio) remainingRadio.checked = true;
                updateTutorSourceCtaState();
                return;
            }

            tutorScopeSection.classList.remove('d-none');

            if (!remainingRadio || !fullRadio) {
                return;
            }

            const hasRemaining = totalRemaining > 0;
            remainingRadio.disabled = !hasRemaining;

            if (!hasRemaining) {
                fullRadio.checked = true;
                if (tutorScopeHint) {
                    tutorScopeHint.textContent = 'Everything in these sources is already correct. Start again only if you want a full replay.';
                }
                updateTutorSourceCtaState();
                return;
            }

            if (!remainingRadio.checked && !fullRadio.checked) {
                remainingRadio.checked = true;
            }

            if (tutorScopeHint) {
                tutorScopeHint.textContent = 'Continue will load only the questions you still have wrong in tutor mode.';
            }

            updateTutorSourceCtaState();
        }

        document.addEventListener('click', function(e) {
            const button = e.target.closest('.start-quiz-btn');
            if (!button) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            if (button.dataset.processing === 'true') {
                return;
            }
            button.dataset.processing = 'true';

            const topicId = button.dataset.topicId;
            const courseId = button.dataset.courseId;
            const originalHref = button.dataset.quizUrl;

            const originalContent = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            button.style.pointerEvents = 'none';

            const isAssignmentCard = button.closest('[data-has-assignment="true"]');
            if (isAssignmentCard || !topicId || !courseId) {
                window.location.href = originalHref;
                return;
            }

            const modal = getModal();
            if (!modal) {
                window.location.href = originalHref;
                return;
            }

            button.innerHTML = originalContent;
            button.style.pointerEvents = '';
            button.dataset.processing = 'false';

            sourceTopicId.value = topicId;
            sourceCourseId.value = courseId;
            sourceSelectionForm.action = originalHref;

            const selectedMode = sourceSelectionForm.querySelector('input[name="quiz_mode"]:checked')?.value || 'quiz';

            cachedFetchJson(`/topics/${topicId}/source-progress?mode=${selectedMode}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': csrfToken.content,
                    'Accept': 'application/json'
                }
            }, 15000, `source-progress:${topicId}:${selectedMode}`)
                .then(data => {
                    updateSourcesContainer(data);
                    modal.show();
                })
                .catch(() => {
                    updateTutorSourceCtaState();
                    modal.show();
                });
        });

        document.addEventListener('mouseover', function(e) {
            const button = e.target.closest('.start-quiz-btn');
            if (button) {
                prefetchQuizUrl(button.dataset.quizUrl);
            }
        });

        document.addEventListener('focusin', function(e) {
            const button = e.target.closest('.start-quiz-btn');
            if (button) {
                prefetchQuizUrl(button.dataset.quizUrl);
            }
        });

        sourceSelectionForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const checkedSources = this.querySelectorAll('input[name="sources[]"]:checked');
            if (checkedSources.length === 0) {
                alert('Please select at least one source to start the quiz.');
                return;
            }

            const selectedMode = this.querySelector('input[name="quiz_mode"]:checked').value;
            const courseId = this.querySelector('#source_course_id').value;
            const topicId = this.querySelector('#source_topic_id').value;
            const selectedSources = Array.from(checkedSources).map(cb => cb.value);
            const sourceParams = selectedSources.map(s => `sources[]=${s}`).join('&');
            const baseUrl = selectedMode === 'tutor'
                ? `/quiz/${courseId}/${topicId}/tutor`
                : `/quiz/${courseId}/${topicId}`;
            let extraParams = '';

            if (selectedMode === 'tutor') {
                const tutorScope = this.querySelector('input[name="tutor_scope"]:checked')?.value || 'full';
                const totalRemaining = getSelectedSourceCards().reduce((sum, card) => {
                    return sum + Number(card.dataset.remainingQuestions || 0);
                }, 0);

                if (tutorScope === 'remaining' && totalRemaining === 0) {
                    alert('There are no tutor mistakes left in the selected source(s). Choose "Retry" to replay the full source.');
                    return;
                }

                extraParams = `&tutor_scope=${encodeURIComponent(tutorScope)}`;
            }

            window.location.href = `${baseUrl}?${sourceParams}${extraParams}`;
        });

        if (randomQuizModal) {
            randomQuizModal.addEventListener('show.bs.modal', function() {
                loadRandomQuizSources();
            });

            courseCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    debouncedLoadRandomQuizSources();
                });
            });
        }

        const debouncedLoadRandomQuizSources = debounce(loadRandomQuizSources, 180);

        async function loadRandomQuizSources() {
            const selectedCourses = Array.from(courseCheckboxes).filter(cb => cb.checked).map(cb => cb.value);
            const container = document.getElementById('sourceCheckboxContainer');
            const placeholder = document.getElementById('sourcePlaceholder');

            if (selectedCourses.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-3" id="sourcePlaceholder"><i class="fas fa-info-circle mb-2"></i><p class="mb-0">Select courses above to load available sources.</p></div>';
                return;
            }

            if (placeholder) {
                placeholder.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div><span class="ms-2">Loading sources...</span></div>';
            }

            try {
                const queryParams = new URLSearchParams();
                selectedCourses.forEach(courseId => {
                    queryParams.append('course_ids[]', courseId);
                });

                const sortedIds = [...selectedCourses].sort().join(',');
                const cacheKey = `courses-sources:${sortedIds}`;

                const data = await cachedFetchJson('/api/courses-sources?' + queryParams.toString(), {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken.content,
                        'Accept': 'application/json'
                    }
                }, 30000, cacheKey);

                if (data.success && data.sources && data.sources.length > 0) {
                    renderRandomQuizSources(data.sources);
                } else {
                    container.innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-exclamation-circle mb-2"></i><p class="mb-0">No sources available for selected courses.</p></div>';
                }
            } catch (error) {
                console.error('Error loading sources:', error);
                container.innerHTML = '<div class="text-center text-danger py-3"><i class="fas fa-exclamation-triangle mb-2"></i><p class="mb-0">Error loading sources.</p></div>';
            }
        }

        function renderRandomQuizSources(sources) {
            const container = document.getElementById('sourceCheckboxContainer');
            if (!container) return;
            
            container.innerHTML = '';

            if (sources.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-3"><p class="mb-0">No sources available</p></div>';
                return;
            }

            // Create "All Sources" checkbox
            const allDiv = document.createElement('div');
            allDiv.className = 'form-check custom-checkbox';
            allDiv.innerHTML = `
                <input class="form-check-input" type="checkbox" name="sources[]" value="all" id="sourceAll" checked>
                <label class="form-check-label" for="sourceAll">All Sources</label>
            `;
            container.appendChild(allDiv);

            const sourceAllCheckbox = allDiv.querySelector('#sourceAll');

            sources.forEach((source, index) => {
                const div = document.createElement('div');
                div.className = 'form-check custom-checkbox';
                div.innerHTML = `
                    <input class="form-check-input source-specific-checkbox" type="checkbox" name="sources[]" value="${source.name}" id="source${index}">
                    <label class="form-check-label" for="source${index}">
                        ${source.name} <span class="text-muted small">(${source.question_count})</span>
                    </label>
                `;
                container.appendChild(div);

                const checkbox = div.querySelector('input');
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        sourceAllCheckbox.checked = false;
                    } else {
                        // If all specific checkboxes are unchecked, check "All"
                        const anyChecked = container.querySelector('.source-specific-checkbox:checked');
                        if (!anyChecked) {
                            sourceAllCheckbox.checked = true;
                        }
                    }
                });
            });

            sourceAllCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    // Uncheck all specific checkboxes
                    container.querySelectorAll('.source-specific-checkbox').forEach(cb => {
                        cb.checked = false;
                    });
                }
            });
        }

        if (randomQuizForm) {
            randomQuizForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const selectedCourses = this.querySelectorAll('input[name="selected_courses[]"]:checked');
                if (selectedCourses.length === 0) {
                    alert('Please select at least one course for the random quiz.');
                    return false;
                }

                const selectedSources = this.querySelectorAll('input[name="sources[]"]:checked');
                if (selectedSources.length === 0) {
                    alert('Please select at least one source for the random quiz.');
                    return false;
                }

                const questionLimitValue = parseInt(questionLimit?.value || 10, 10);
                if (questionLimitValue < 1 || questionLimitValue > 100) {
                    alert('Please enter a valid question limit (1-100).');
                    return false;
                }

                const timeLimitValue = parseFloat(timeLimit?.value || 30);
                if (timeLimitValue < 0.1 || timeLimitValue > 180) {
                    alert('Please enter a valid time limit (1-180 minutes).');
                    return false;
                }

                const modal = document.getElementById('randomQuizModal');
                const selectedModeElement = modal.querySelector('input[name="quiz_mode"]:checked');
                const selectedMode = selectedModeElement ? selectedModeElement.value : 'quiz';

                const formData = new FormData(this);
                const params = new URLSearchParams();

                for (const [key, value] of formData.entries()) {
                    if (key === 'selected_courses[]' || key === 'sources[]') {
                        params.append(key, value);
                    } else {
                        params.append(key, value);
                    }
                }

                params.set('quiz_mode', selectedMode);
                const url = selectedMode === 'tutor'
                    ? `${topicsPageConfig.randomTutorRoute}?${params.toString()}`
                    : `${topicsPageConfig.randomQuizRoute}?${params.toString()}`;

                window.location.href = url;
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        if (window.performance && window.performance.mark) {
            window.performance.mark('topics-page-start');
        }

        const urlParams = new URLSearchParams(window.location.search);
        const savedFilter = urlParams.get('filter') || sessionStorage.getItem('topicsFilter') || 'all';

        if (savedFilter !== 'all') {
            document.body.classList.add(`${savedFilter}-filter-active`);
        }
    });

    window.handleVideoPlay = function handleVideoPlay(event, topicId) {
        event.preventDefault();
        event.stopImmediatePropagation();

        const videoLink = event.target.closest('a');
        if (videoLink && videoLink.href) {
            window.location.href = videoLink.href;
        } else {
            console.error('Could not find video link for topic:', topicId);
        }

        return false;
    };

    document.addEventListener('DOMContentLoaded', function() {
        const videoButtons = document.querySelectorAll('.video-play-btn');
        videoButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopImmediatePropagation();

                const href = this.getAttribute('href');
                if (href) {
                    window.location.href = href;
                } else {
                    console.error('No href found on video button');
                }

                return false;
            });
        });
    });
})();
