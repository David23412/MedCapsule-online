(function () {
    const profilePageConfig = window.ProfilePageConfig || {};

    let performanceChart;
    let distributionChart;
    let goalSaving = false;

    async function readProfileResponse(response) {
        const data = await response.json();
        if (!response.ok || data.success === false) {
            throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'The request failed. Please try again.');
        }
        return data;
    }

    function initializePerformanceChart() {

    const performanceCanvas = document.getElementById('performanceChart');
    if (performanceCanvas && window.Chart) {
        const performanceCtx = performanceCanvas.getContext('2d');
        const chartData = Array.isArray(profilePageConfig.chartData) ? profilePageConfig.chartData.slice() : [];
        const sortedChartData = chartData
            .map((point) => ({
                ...point,
                x: Number(point.x),
                y: Number(point.y)
            }))
            .filter((point) => Number.isFinite(point.x) && Number.isFinite(point.y))
            .sort((a, b) => a.x - b.x);

        const createGradientLineColor = (ctx, dataPoints) => {
            if (dataPoints.length < 2) {
                const performance = dataPoints.length > 0 ? dataPoints[0].y : 0;
                return performance >= 90 ? '#10B981' : performance >= 50 ? '#FFD700' : '#EF4444';
            }

            const gradient = ctx.createLinearGradient(0, 0, ctx.canvas.width, 0);
            dataPoints.forEach((point, index) => {
                const position = dataPoints.length > 1 ? index / (dataPoints.length - 1) : 0;
                const color = point.y >= 90 ? '#10B981' : point.y >= 50 ? '#FFD700' : '#EF4444';
                gradient.addColorStop(position, color);
            });
            return gradient;
        };

        const createSegmentedFill = (ctx, dataPoints) => {
            const gradient = ctx.createLinearGradient(0, 0, ctx.canvas.width, 0);

            dataPoints.forEach((point, index) => {
                const position = dataPoints.length > 1 ? index / (dataPoints.length - 1) : 0;
                const zoneColor = point.y >= 90
                    ? 'rgba(16, 185, 129, 0.3)'
                    : point.y >= 50
                        ? 'rgba(255, 215, 0, 0.3)'
                        : 'rgba(239, 68, 68, 0.3)';

                if (index === 0) {
                    gradient.addColorStop(0, zoneColor);
                } else {
                    gradient.addColorStop(position - 0.001, zoneColor);
                    gradient.addColorStop(position, zoneColor);
                }
            });

            return gradient;
        };

        performanceChart = new Chart(performanceCtx, {
            type: 'line',
            data: {
                datasets: [{
                    label: 'Quiz Performance',
                    data: sortedChartData,
                    borderColor: createGradientLineColor(performanceCtx, sortedChartData),
                    backgroundColor: createSegmentedFill(performanceCtx, sortedChartData),
                    tension: 0.32,
                    cubicInterpolationMode: 'monotone',
                    fill: {
                        target: 'origin'
                    },
                    spanGaps: false,
                    pointBackgroundColor: (context) => {
                        const value = context.raw?.y || 0;
                        return value >= 90 ? '#10B981' : value >= 50 ? '#FFD700' : '#EF4444';
                    },
                    pointBorderColor: '#fff',
                    pointBorderWidth: 3,
                    pointRadius: (context) => {
                        const dataLength = context.chart.data.datasets[0].data.length;
                        const index = context.dataIndex;
                        const recency = (index + 1) / dataLength;
                        return Math.max(4, Math.min(8, 4 + (recency * 4)));
                    },
                    pointHoverRadius: 12,
                    pointHoverBorderWidth: 4,
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: (context) => {
                        const value = context.raw?.y || 0;
                        return value >= 90 ? '#10B981' : value >= 50 ? '#FFD700' : '#EF4444';
                    },
                    pointStyle: 'circle',
                    hoverBackgroundColor: '#fff',
                    hoverBorderColor: '#3a7bd5'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                parsing: false,
                normalized: true,
                animation: { duration: 1000, easing: 'easeInOutQuart' },
                interaction: { intersect: false, mode: 'nearest', axis: 'xy' },
                layout: { padding: { top: 5, bottom: 5, left: 5, right: 5 } },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        mode: 'nearest',
                        intersect: false,
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#333',
                        bodyColor: '#666',
                        borderColor: 'rgba(58, 123, 213, 0.2)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        padding: 12,
                        boxPadding: 6,
                        titleFont: {
                            family: "'Poppins', sans-serif",
                            size: 13,
                            weight: '600'
                        },
                        bodyFont: {
                            family: "'Poppins', sans-serif",
                            size: 12
                        },
                        displayColors: false,
                        callbacks: {
                            label: function (context) {
                                const attempt = context.raw;
                                return [
                                    `Topic: ${attempt.topic}`,
                                    `Accuracy: ${attempt.y}%`,
                                    `Date: ${attempt.date}`
                                ];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        min: 0,
                        max: 100,
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false,
                            offset: true
                        },
                        ticks: {
                            stepSize: 20,
                            callback: function (value) { return value + '%'; },
                            font: { family: "'Poppins', sans-serif", size: 11 },
                            color: '#666',
                            padding: 5
                        },
                        border: { display: false }
                    },
                    x: {
                        type: 'time',
                        time: {
                            minUnit: 'hour',
                            displayFormats: {
                                hour: 'MMM d, h:mm a',
                                day: 'MMM d'
                            },
                            tooltipFormat: 'MMM d, yyyy h:mm a'
                        },
                        grid: { display: false, offset: true },
                        ticks: {
                            autoSkip: true,
                            maxTicksLimit: 7,
                            maxRotation: 45,
                            minRotation: 0,
                            sampleSize: 7,
                            font: { family: "'Poppins', sans-serif", size: 11 },
                            color: '#666',
                            padding: 5
                        },
                        border: { display: false }
                    }
                },
                elements: {
                    point: {
                        hitRadius: 25,
                        hoverRadius: 8,
                        hoverBorderWidth: 3,
                        borderWidth: 2,
                        radius: 5,
                        backgroundColor: '#3a7bd5',
                        borderColor: '#fff',
                        hoverBackgroundColor: '#fff',
                        hoverBorderColor: '#3a7bd5'
                    },
                    line: {
                        tension: 0.32,
                        cubicInterpolationMode: 'monotone',
                        borderWidth: 3,
                        borderCapStyle: 'round',
                        borderJoinStyle: 'round',
                        capBezierPoints: true,
                        fill: true
                    }
                },
                hover: {
                    mode: 'nearest',
                    intersect: false,
                    animationDuration: 300
                },
                transitions: {
                    active: { animation: { duration: 300, easing: 'easeOutQuart' } }
                }
            }
        });
    }

    }
    function initializeDistributionChart() {
    const distributionCanvas = document.getElementById('distributionChart');
    if (distributionCanvas && window.Chart) {
        const distributionCtx = distributionCanvas.getContext('2d');
        const correctCount = Number(profilePageConfig.correctCount || 0);
        const incorrectCount = Math.max(0, Number(profilePageConfig.incorrectCount || 0));
        const totalCount = Number(profilePageConfig.totalCount || 0);
        const safeTotalCount = totalCount > 0 ? totalCount : 1;

        distributionChart = new Chart(distributionCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    `Correct (${Math.round(correctCount / safeTotalCount * 100)}%)`,
                    `Incorrect (${Math.round(incorrectCount / safeTotalCount * 100)}%)`
                ],
                datasets: [{
                    data: [correctCount, incorrectCount],
                    backgroundColor: ['#28a745', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 900, easing: 'easeInOutQuart' },
                normalized: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    }
    for (const initialize of [initializePerformanceChart, initializeDistributionChart]) {
        try { initialize(); } catch (error) {
            console.warn('Profile chart could not be loaded:', error);
        }
    }

    const completionCourseSelect = document.getElementById('courseCompletionCourseSelect');
    const completionSourceSelect = document.getElementById('courseCompletionSourceSelect');
    const completionWidget = profilePageConfig.courseCompletion || {};
    const completionCourses = Array.isArray(completionWidget.courses) ? completionWidget.courses : [];
    const completionCoursesById = new Map(completionCourses.map((course) => [String(course.id), course]));

    const formatNumber = (value) => new Intl.NumberFormat().format(Number(value || 0));
    const formatPercent = (value) => {
        const numericValue = Number(value || 0);
        return `${Number.isInteger(numericValue) ? numericValue : numericValue.toFixed(1)}%`;
    };

    const setText = (id, value) => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        }
    };

    function populateCompletionSources(course) {
        if (!completionSourceSelect || !course) {
            return;
        }

        completionSourceSelect.innerHTML = '<option value="all">All sources</option>';
        (course.sources || []).forEach((source) => {
            const option = document.createElement('option');
            option.value = source.source;
            option.textContent = source.source;
            completionSourceSelect.appendChild(option);
        });
    }

    function updateCompletionWidget() {
        if (!completionCourseSelect || !completionSourceSelect) {
            return;
        }

        const course = completionCoursesById.get(String(completionCourseSelect.value)) || completionCourses[0];
        if (!course) {
            return;
        }

        const selectedSource = completionSourceSelect.value || 'all';
        const sourceData = selectedSource === 'all'
            ? null
            : (course.sources || []).find((source) => source.source === selectedSource);
        const totalQuestions = sourceData ? sourceData.total_questions : course.total_questions;
        const completedQuestions = sourceData ? sourceData.completed_questions : course.completed_questions;
        const percentage = Number(sourceData ? sourceData.percentage : course.percentage) || 0;
        const remainingQuestions = Math.max(0, Number(totalQuestions || 0) - Number(completedQuestions || 0));
        const sourceCount = (course.sources || []).length;
        const clampedPercentage = Math.max(0, Math.min(100, percentage));

        const ring = document.getElementById('courseCompletionRing');
        const ringProgress = document.getElementById('courseCompletionRingProgress');
        const ringSpark = document.getElementById('courseCompletionRingSpark');
        if (ring) {
            ring.style.setProperty('--completion-spark-angle', `${(clampedPercentage / 100) * 360}deg`);
            ring.setAttribute('aria-label', `Course coverage ${formatPercent(clampedPercentage)}`);
        }
        if (ringProgress) {
            ringProgress.style.strokeDashoffset = String(100 - clampedPercentage);
        }
        if (ringSpark) {
            ringSpark.style.opacity = clampedPercentage > 0 ? '1' : '0';
        }

        const meter = document.getElementById('courseCompletionMeter');
        if (meter) {
            meter.style.width = `${clampedPercentage}%`;
        }

        setText('courseCompletionPercent', formatPercent(percentage));
        setText('courseCompletionScope', sourceData ? sourceData.source : 'All sources');
        setText('courseCompletionCourseName', course.name);
        setText('courseCompletionSolved', formatNumber(completedQuestions));
        setText('courseCompletionTotal', formatNumber(totalQuestions));
        setText('courseCompletionRemaining', `${formatNumber(remainingQuestions)} remaining`);
        setText('courseCompletionSourceCount', `${formatNumber(sourceCount)} source${sourceCount === 1 ? '' : 's'}`);
    }

    if (completionCourseSelect && completionSourceSelect && completionCourses.length > 0) {
        const selectedCourseId = completionWidget.selected_course_id || completionCourses[0].id;
        completionCourseSelect.value = String(selectedCourseId);
        if (!completionCoursesById.has(String(completionCourseSelect.value))) {
            completionCourseSelect.value = String(completionCourses[0].id);
        }

        populateCompletionSources(completionCoursesById.get(String(completionCourseSelect.value)) || completionCourses[0]);
        updateCompletionWidget();

        completionCourseSelect.addEventListener('change', function () {
            const course = completionCoursesById.get(String(completionCourseSelect.value));
            populateCompletionSources(course);
            completionSourceSelect.value = 'all';
            updateCompletionWidget();
        });

        completionSourceSelect.addEventListener('change', updateCompletionWidget);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarBackdrop = document.getElementById('profileSidebarBackdrop');
        const sidebarStorageKey = 'medcapsule-profile-sidebar-collapsed';
        const mobileBreakpoint = 992;
        const isMobileViewport = () => window.innerWidth < mobileBreakpoint;
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

        const closeMobileSidebar = () => {
            document.body.classList.remove('mobile-sidebar-open');
        };

        const savedSidebarState = getStoredSidebarState();
        if (savedSidebarState !== null) {
            applySidebarState(savedSidebarState === 'true');
        } else if (window.innerWidth < 768) {
            applySidebarState(true);
        }

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                if (isMobileViewport()) {
                    document.body.classList.toggle('mobile-sidebar-open');
                    return;
                }

                const collapsed = !document.body.classList.contains('sidebar-collapsed');
                applySidebarState(collapsed);
                setStoredSidebarState(collapsed);
            });
        }

        if (sidebarBackdrop) {
            sidebarBackdrop.addEventListener('click', closeMobileSidebar);
        }

        window.addEventListener('resize', function () {
            if (!isMobileViewport()) {
                closeMobileSidebar();
            }
        });

        const profilePicInput = document.getElementById('profilePictureInput');
        const pictureContainer = document.getElementById('profilePictureContainer');
        if (profilePicInput && pictureContainer) {
            let uploading = false;
            pictureContainer.addEventListener('click', () => { if (!uploading) profilePicInput.click(); });
            pictureContainer.addEventListener('keydown', event => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    if (!uploading) profilePicInput.click();
                }
            });
            profilePicInput.addEventListener('change', async function () {
                const file = profilePicInput.files[0];
                if (!file || uploading) return;
                if (file.size > 5 * 1024 * 1024 || !['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)) {
                    alert('Choose a JPG, PNG, GIF, or WebP image under 5 MB.');
                    profilePicInput.value = '';
                    return;
                }
                uploading = true;
                profilePicInput.disabled = true;
                pictureContainer.setAttribute('aria-busy', 'true');
                const formData = new FormData();
                formData.append('profile_picture', file);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                try {
                    const data = await fetch(document.getElementById('profileForm').action, {
                        method: 'POST', body: formData,
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    }).then(readProfileResponse);
                    if (data.new_url) document.getElementById('profileImage').src = data.new_url;
                    if (data.message) alert(data.message);
                } catch (error) {
                    alert(error.message || 'Your photo could not be uploaded. Please try again.');
                } finally {
                    uploading = false;
                    profilePicInput.disabled = false;
                    profilePicInput.value = '';
                    pictureContainer.removeAttribute('aria-busy');
                }
            });
        }

        if (document.querySelector('.confetti-container')) {
            createConfetti();
        }
    });

    function createConfetti() {
        const container = document.querySelector('.confetti-container');
        if (!container) {
            return;
        }

        const colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'];

        for (let i = 0; i < 50; i++) {
            const confetti = document.createElement('div');
            confetti.style.cssText = `
                position: absolute;
                width: 8px;
                height: 8px;
                background: ${colors[Math.floor(Math.random() * colors.length)]};
                left: ${Math.random() * 100}%;
                top: -10px;
                border-radius: 50%;
                animation: confettiFall ${2 + Math.random() * 3}s linear infinite;
                animation-delay: ${Math.random() * 2}s;
                opacity: 0.8;
            `;
            container.appendChild(confetti);
        }
    }

    window.openGoalModal = function openGoalModal() {
        if (document.getElementById('goalModal')) return;
        const currentGoal = Number(profilePageConfig.weeklyGoal || 50);
        const currentDailyStudyMinutes = Number(profilePageConfig.dailyStudyMinutes || 90);
        const currentPlannerFocus = String(profilePageConfig.plannerFocus || 'balanced');
        const currentExamDate = profilePageConfig.examDate || '';
        const modal = document.createElement('div');
        modal.innerHTML = `
            <div class="modal fade show" id="goalModal" role="dialog" aria-modal="true" aria-labelledby="goalModalTitle" style="display: block; background: rgba(0,0,0,0.6);" onclick="if(event.target === this) closeGoalModal()">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
                    <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
                        <div class="modal-header" style="border-bottom: 1px solid #e2e8f0; padding: 1.5rem;">
                            <h5 id="goalModalTitle" class="modal-title" style="font-weight: 700; font-size: 1.3rem; color: #1e293b;">
                                Planner Settings
                            </h5>
                            <button type="button" class="btn-close" aria-label="Close planner settings" onclick="closeGoalModal()"></button>
                        </div>
                        <div class="modal-body" style="padding: 2rem;">
                            <div class="mb-4">
                                <label for="goalInput" class="form-label" style="font-weight: 600; color: #475569; font-size: 0.95rem;">Questions per week</label>
                                <input type="number" class="form-control" id="goalInput" value="${currentGoal}"
                                       min="10" max="500" step="5"
                                       style="font-size: 1.5rem; padding: 1rem; border: 2px solid #cbd5e1; border-radius: 10px; text-align: center; font-weight: 700;">
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-sm-12">
                                    <label for="dailyStudyMinutesInput" class="form-label" style="font-weight: 600; color: #475569; font-size: 0.95rem;">Daily study time</label>
                                    <input type="number" class="form-control" id="dailyStudyMinutesInput" value="${currentDailyStudyMinutes}"
                                           min="30" max="480" step="15"
                                           style="padding: 0.85rem; border: 2px solid #cbd5e1; border-radius: 10px; font-weight: 700;">
                                </div>
                                <div class="col-sm-6">
                                    <label for="examDateInput" class="form-label" style="font-weight: 600; color: #475569; font-size: 0.95rem;">Exam date</label>
                                    <input type="date" class="form-control" id="examDateInput" value="${currentExamDate}"
                                           style="padding: 0.85rem; border: 2px solid #cbd5e1; border-radius: 10px; font-weight: 600;">
                                </div>
                                <div class="col-sm-6">
                                    <label for="plannerFocusInput" class="form-label" style="font-weight: 600; color: #475569; font-size: 0.95rem;">Planner focus</label>
                                    <select class="form-select" id="plannerFocusInput"
                                            style="padding: 0.85rem; border: 2px solid #cbd5e1; border-radius: 10px; font-weight: 600;">
                                        <option value="balanced" ${currentPlannerFocus === 'balanced' ? 'selected' : ''}>Balanced</option>
                                        <option value="revision" ${currentPlannerFocus === 'revision' ? 'selected' : ''}>Revision</option>
                                        <option value="exam_prep" ${currentPlannerFocus === 'exam_prep' ? 'selected' : ''}>Exam prep</option>
                                    </select>
                                </div>
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary" onclick="setQuickGoal(25)" style="padding: 0.75rem; font-weight: 600;">25 questions</button>
                                <button class="btn btn-outline-primary" onclick="setQuickGoal(50)" style="padding: 0.75rem; font-weight: 600;">50 questions</button>
                                <button class="btn btn-outline-primary" onclick="setQuickGoal(100)" style="padding: 0.75rem; font-weight: 600;">100 questions</button>
                            </div>
                        </div>
                        <div class="modal-footer" style="border-top: 1px solid #e2e8f0; padding: 1.5rem; gap: 10px;">
                            <button type="button" class="btn btn-secondary" onclick="closeGoalModal()" style="flex: 1; padding: 0.75rem; font-weight: 600;">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="saveGoal()" style="flex: 1; padding: 0.75rem; font-weight: 600; background: #007bff; border: none;">
                                Save Goal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        document.getElementById('goalInput')?.focus();
    };

    window.closeGoalModal = function closeGoalModal() {
        if (goalSaving) return;
        const modal = document.getElementById('goalModal');
        if (modal) {
            if (modal.parentElement && modal.parentElement !== document.body) modal.parentElement.remove();
            else modal.remove();
        }
    };

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && document.getElementById('goalModal')) window.closeGoalModal();
    });

    window.setQuickGoal = function setQuickGoal(value) {
        document.getElementById('goalInput').value = value;
    };

    window.saveGoal = function saveGoal() {
        if (goalSaving || !document.getElementById('goalModal')) return;
        const goal = Number(document.getElementById('goalInput').value);
        const dailyStudyMinutes = Number(document.getElementById('dailyStudyMinutesInput').value);
        const examDate = document.getElementById('examDateInput').value || null;
        const plannerFocus = document.getElementById('plannerFocusInput').value;

        if (!Number.isInteger(goal) || goal < 10 || goal > 500) {
            alert('Please enter a goal between 10 and 500 questions');
            return;
        }

        if (!Number.isInteger(dailyStudyMinutes) || dailyStudyMinutes < 30 || dailyStudyMinutes > 480) {
            alert('Please enter daily study time between 30 and 480 minutes');
            return;
        }

        const saveBtn = document.querySelector('#goalModal .btn-primary');
        const originalBtnText = saveBtn.innerHTML;
        goalSaving = true;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

        fetch(profilePageConfig.updateGoalRoute, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                weekly_goal: goal,
                daily_study_minutes: dailyStudyMinutes,
                exam_date: examDate,
                planner_focus: plannerFocus
            })
        })
            .then(readProfileResponse)
            .then(data => {
                if (data.success) {
                    goalSaving = false;
                    window.closeGoalModal();
                    
                    // Update the DOM in-place
                    profilePageConfig.weeklyGoal = goal;
                    profilePageConfig.dailyStudyMinutes = dailyStudyMinutes;
                    profilePageConfig.plannerFocus = plannerFocus;
                    profilePageConfig.examDate = examDate;

                    // Update goal text (e.g., "/ 50")
                    const goalDisplay = document.getElementById('profileWeeklyGoal');
                    if (goalDisplay) {
                        goalDisplay.textContent = `/ ${goal}`;
                    }

                    // Update progress bar percentage
                    const currentQuestions = parseInt(document.getElementById('profileWeeklyQuestions')?.textContent || '0');
                    const newPercent = Math.min(100, Math.round((currentQuestions / goal) * 100));
                    const progressColor = newPercent >= 100 ? '#10b981' : newPercent >= 70 ? '#3b82f6' : newPercent >= 40 ? '#f59e0b' : '#ef4444';
                    const progressBar = document.getElementById('profileWeeklyBar');
                    if (progressBar) {
                        progressBar.style.width = `${newPercent}%`;
                        progressBar.style.background = `linear-gradient(90deg, ${progressColor}, ${progressColor}dd)`;
                        progressBar.setAttribute('aria-valuenow', String(newPercent));
                    }
                    const percentDisplay = document.getElementById('profileWeeklyPercent');
                    if (percentDisplay) {
                        percentDisplay.textContent = `${newPercent}%`;
                        percentDisplay.style.color = progressColor;
                    }
                    const weeklyCount = document.getElementById('profileWeeklyQuestions');
                    if (weeklyCount) weeklyCount.style.color = progressColor;

                    // Update badges
                    const badges = document.querySelectorAll('.study-goals-card .badge');
                    if (badges.length >= 2) {
                        badges[0].textContent = `${dailyStudyMinutes}m / day`;
                        badges[1].textContent = plannerFocus.replace('_', ' ');
                        
                        // Handle exam date badge
                        if (examDate) {
                            const dateObj = new Date(examDate + 'T00:00:00');
                            const options = { year: 'numeric', month: 'short', day: 'numeric' };
                            const formattedDate = dateObj.toLocaleDateString('en-US', options);
                            
                            if (badges.length >= 3) {
                                badges[2].textContent = `Exam ${formattedDate}`;
                            } else {
                                // Create new badge if it didn't exist
                                const badgeContainer = document.querySelector('.study-goals-card .d-flex.flex-wrap');
                                if (badgeContainer) {
                                    const newBadge = document.createElement('span');
                                    newBadge.className = 'badge rounded-pill text-bg-light';
                                    newBadge.style.padding = '0.55rem 0.85rem';
                                    newBadge.style.fontWeight = '600';
                                    newBadge.textContent = `Exam ${formattedDate}`;
                                    badgeContainer.appendChild(newBadge);
                                }
                            }
                        }
                    }

                    if (!examDate && badges.length >= 3) badges[2].remove();

                    // Show success micro-interaction
                    if (progressBar) {
                        progressBar.classList.add('pulse');
                        setTimeout(() => progressBar.classList.remove('pulse'), 1000);
                    }
                } else {
                    alert('Failed to save goal. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error saving goal:', error);
                alert(error.message || 'Failed to save goal. Please try again.');
            })
            .finally(() => {
                goalSaving = false;
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalBtnText;
            });
    };

    // Cross-Tab Sync: Listen for quiz completions in other tabs
    window.addEventListener('storage', function(e) {
        if (e.key === 'quizProgressUpdate' || e.key === 'assignmentCompleted') {

            refreshProfileStats();
        }
    });

    // Also listen for local events
    document.addEventListener('sourceProgressUpdated', function() {

        refreshProfileStats();
    });

    let progressRequest = null;
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') refreshProfileStats();
    });
    document.addEventListener('assignmentCompleted', refreshProfileStats);

    function refreshProfileStats() {
        if (document.visibilityState === 'hidden' || navigator.onLine === false) return;
        if (progressRequest) return progressRequest;
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 15000);
        progressRequest = fetch('/profile/progress', {
            signal: controller.signal,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(readProfileResponse)
        .then(data => {
            if (data.accuracy_rate !== undefined) {
                // Update Accuracy Card
                const accuracyValue = document.getElementById('profileAccuracy');
                if (accuracyValue) {
                    accuracyValue.textContent = `${Number(data.accuracy_rate).toFixed(1)}%`;
                }
                const accuracySub = document.querySelector('.accuracy-card small');
                if (accuracySub) {
                    accuracySub.textContent = `Based on ${data.total_questions_attempted} attempts`;
                }

                // Update Total Questions Card
                const totalQuestionsValue = document.getElementById('profileTotalQuestions');
                if (totalQuestionsValue) {
                    totalQuestionsValue.textContent = data.total_questions_attempted;
                }

                // Update Charts
                if (distributionChart) {
                    const safeTotal = data.total_questions_attempted > 0 ? data.total_questions_attempted : 1;
                    distributionChart.data.datasets[0].data = [data.correct_answers_count, Math.max(0, data.total_questions_attempted - data.correct_answers_count)];
                    distributionChart.data.labels = [
                        `Correct (${Math.round(data.correct_answers_count / safeTotal * 100)}%)`,
                        `Incorrect (${Math.round((Math.max(0, data.total_questions_attempted - data.correct_answers_count)) / safeTotal * 100)}%)`
                    ];
                    distributionChart.update('none'); // Update without animation for a "live" feel
                }

                // Note: performanceChart update would require the full chartData array, 
                // which /profile/progress might not return. If needed, we could fetch another endpoint.
                // For now, accuracy and distribution are high-impact.
            }
        })
        .catch(() => {})
        .finally(() => { clearTimeout(timeout); progressRequest = null; });
        return progressRequest;
    }

    // Privacy Toggle logic
    document.addEventListener('DOMContentLoaded', function() {
        const privacyToggle = document.getElementById('leaderboardToggle');
        if (privacyToggle) {
            privacyToggle.addEventListener('change', function() {
                if (this.disabled) return;
                const isChecked = this.checked;
                this.disabled = true;
                const originalValue = !isChecked;
                
                // Visual feedback: briefly fade out
                const control = this.closest('.privacy-control');
                if (control) {
                    control.classList.add('updating');
                }

                fetch('/profile/update-privacy', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        show_on_leaderboard: isChecked
                    })
                })
                .then(readProfileResponse)
                .then(data => {
                    if (control) {
                        control.classList.remove('updating');
                    }
                    if (data.success) {
                        // Success - maybe show a quick toast or just let the switch stay
                    } else {
                        // Revert on failure
                        this.checked = originalValue;
                        alert(data.message || 'Failed to update privacy settings.');
                    }
                })
                .catch(error => {
                    console.error('Error updating privacy:', error);
                    if (control) {
                        control.classList.remove('updating');
                    }
                    this.checked = originalValue;
                    alert(error.message || 'An error occurred. Please try again.');
                }).finally(() => { this.disabled = false; });
            });
        }
    });

})();
