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
        const randomQuizForm = document.getElementById('randomQuizForm');
        const randomQuizModal = document.getElementById('randomQuizModal');
        const assignmentIconCircle = document.querySelector('.assignment-icon-circle');
        const assignmentCircle = document.querySelector('.assignment-circle');
        const questionLimit = document.getElementById('questionLimit');
        const timeLimit = document.getElementById('timeLimit');
        const courseCheckboxes = document.querySelectorAll('#randomQuizModal input[name="selected_courses[]"]');
        const sidebarStorageKey = 'medcapsule-topics-sidebar-collapsed';

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

        const savedSidebarState = getStoredSidebarState();
        if (savedSidebarState !== null) {
            applySidebarState(savedSidebarState === 'true');
        } else if (window.innerWidth < 768) {
            applySidebarState(true);
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

                card.style.display = passesFilter && passesSearch ? 'block' : 'none';
                if (passesFilter && passesSearch) {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }
            });
        }

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
            const { topicId, progress, availableSources, allSourcesMastered, assignmentId, mode } = e.detail;

            if (progress) {
                updateSourcesContainer({
                    progress: progress,
                    availableSources: availableSources
                });
            }

            if (mode !== 'tutor') {
                let targetCard = null;

                if (assignmentId) {
                    targetCard = document.querySelector(`[data-assignment-id="${assignmentId}"]`);
                } else {
                    targetCard = document.querySelector(`[data-topic-id="${topicId}"][data-has-assignment="false"]`);
                }

                if (targetCard) {
                    const gradeBadge = targetCard.querySelector('.grade-badge');
                    if (gradeBadge) {
                        gradeBadge.className = `grade-badge ${allSourcesMastered ? 'grade-mastered' : 'grade-good'}`;
                        gradeBadge.innerHTML = allSourcesMastered
                            ? '<i class="fas fa-star"></i> Mastered'
                            : '<i class="fas fa-check"></i> Good';
                    }

                    if (assignmentId) {
                        updateAssignmentProgress(assignmentId, targetCard);
                    }
                }
            }
        });

        let sourceSelectionModal;
        function getModal() {
            if (!sourceSelectionModal && sourceModal) {
                sourceSelectionModal = new bootstrap.Modal(sourceModal);
            }
            return sourceSelectionModal;
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
                const card = document.createElement('div');
                card.className = 'source-card';
                card.setAttribute('data-source', source);

                if (progress.total_questions_available > 0) {
                    card.classList.add('selected');
                }

                let statusClass;
                let statusText;
                let statusIcon;

                if (progress.has_attempts) {
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
                                <div class="source-progress-value ${statusClass}" style="width: ${progress.percentage}%;">
                                </div>
                            </div>
                            <div class="source-stats">
                                <span class="questions-count">
                                    <i class="fas fa-book-open me-1"></i>${progress.completed}/${progress.total_questions_available} Questions
                                </span>
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
                    }
                });
            });
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

            cachedFetchJson(`/topics/${topicId}/source-progress?mode=quiz`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': csrfToken.content,
                    'Accept': 'application/json'
                }
            }, 15000, `source-progress:${topicId}:quiz`)
                .then(data => {
                    updateSourcesContainer(data);
                    modal.show();
                })
                .catch(() => {
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

            window.location.href = `${baseUrl}?${sourceParams}`;
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
            const sourceSelect = document.getElementById('sourceSelect');

            if (selectedCourses.length === 0) {
                sourceSelect.innerHTML = '<option value="">Select courses first</option>';
                return;
            }

            sourceSelect.innerHTML = '<option disabled selected>Loading sources...</option>';

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
                    sourceSelect.innerHTML = '<option value="">No sources available</option>';
                }
            } catch (error) {
                console.error('Error loading sources:', error);
                sourceSelect.innerHTML = '<option value="">Error loading sources</option>';
            }
        }

        function renderRandomQuizSources(sources) {
            const sourceSelect = document.getElementById('sourceSelect');
            sourceSelect.innerHTML = '';

            if (sources.length === 0) {
                const option = document.createElement('option');
                option.disabled = true;
                option.textContent = 'No sources available';
                sourceSelect.appendChild(option);
                return;
            }

            const allOption = document.createElement('option');
            allOption.value = 'all';
            allOption.textContent = 'All Sources';
            allOption.selected = true;
            sourceSelect.appendChild(allOption);

            sources.forEach(source => {
                const option = document.createElement('option');
                option.value = source.name;
                option.textContent = `${source.name} (${source.question_count} questions)`;
                sourceSelect.appendChild(option);
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

                const sourceSelect = document.getElementById('sourceSelect');
                if (!sourceSelect.value) {
                    alert('Please select a source for the random quiz.');
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
                    if (key === 'selected_courses[]' || key === 'selected_sources[]') {
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
