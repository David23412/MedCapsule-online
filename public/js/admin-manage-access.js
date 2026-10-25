const MANAGE_ACCESS_CONFIG = window.MANAGE_ACCESS_CONFIG || {};
const MANAGE_ACCESS_ROUTES = MANAGE_ACCESS_CONFIG.routes || {};
// Admin controls must recover when a request stalls.
$.ajaxSetup({timeout: 15000});

// ── Unified Notification System (global scope) ─────────────────────────
(function () {
    'use strict';

    var ICONS = {
        success: 'fa-check-circle',
        error:   'fa-times-circle',
        warning: 'fa-exclamation-triangle',
        info:    'fa-info-circle'
    };

    /**
     * showNotification(type, title [, message] [, duration])
     *   type     – 'success' | 'error' | 'warning' | 'info'
     *   title    – heading text
     *   message  – optional body text
     *   duration – ms before auto-dismiss (default 4000)
     */
    window.showNotification = function (type, title, message, duration) {
        // Normalise arguments: some callers pass (message, type) – detect and swap
        if (type && !ICONS[type] && ICONS[title]) {
            var tmp = type; type = title; title = tmp;
        }
        if (!ICONS[type]) type = 'info';
        if (typeof message === 'number') { duration = message; message = ''; }
        duration = typeof duration === 'number' ? duration : 4000;

        var container = document.getElementById('notificationContainer');
        if (!container) return;

        var el = document.createElement('div');
        el.className = 'notification ' + type;
        const escapeText = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[char]));
        el.innerHTML =
            '<div class="notification-icon"><i class="fas ' + ICONS[type] + '"></i></div>' +
            '<div class="notification-content">' +
                '<div class="notification-title">' + escapeText(title) + '</div>' +
                (message ? '<div class="notification-message">' + escapeText(message) + '</div>' : '') +
            '</div>' +
            '<div class="notification-progress"><div class="notification-progress-bar"></div></div>' +
            '<button class="notification-close" aria-label="Close"><i class="fas fa-times"></i></button>';

        container.appendChild(el);

        // Slide in
        requestAnimationFrame(function () {
            requestAnimationFrame(function () { el.classList.add('show'); });
        });

        // Progress bar countdown
        var bar = el.querySelector('.notification-progress-bar');
        if (bar) {
            bar.style.transition = 'width ' + duration + 'ms linear';
            requestAnimationFrame(function () { bar.style.width = '0%'; });
        }

        // Close handler
        el.querySelector('.notification-close').addEventListener('click', function () { dismiss(); });

        var timer = setTimeout(dismiss, duration);

        function dismiss() {
            clearTimeout(timer);
            el.classList.remove('show');
            el.addEventListener('transitionend', function handler() {
                el.removeEventListener('transitionend', handler);
                if (el.parentNode) el.parentNode.removeChild(el);
            });
            // Fallback removal if transitionend doesn't fire
            setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 400);
        }
    };
})();

        $(document).ready(function() {
            const tabNames = {'access': 'access', 'payment-history': 'payments', 'video-management': 'videos'};
            document.querySelectorAll('#manageTabs button[data-bs-toggle="tab"]').forEach(button => {
                button.addEventListener('shown.bs.tab', () => {
                    const panel = button.getAttribute('data-bs-target').slice(1);
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', tabNames[panel] || panel);
                    window.history.replaceState(null, '', url);
                    $('#manageSectionDescription').text(MANAGE_ACCESS_CONFIG.tabDescriptions?.[panel] || '');
                });
            });

            function normalizeRole(role) {
                const parsedRole = Number.parseInt(role, 10);
                return Number.isNaN(parsedRole) ? 0 : parsedRole;
            }

            function getRoleName(role) {
                const normalizedRole = normalizeRole(role);
                if (normalizedRole === 1) return 'Admin';
                if (normalizedRole === 2) return 'Tutor';
                return 'Student';
            }

            function normalizeSecurityStatus(status) {
                status = status || {};
                return {
                    label: status.label || 'Clear',
                    isBlocked: status.is_blocked === true || status.is_blocked === 1 || status.is_blocked === '1',
                    isSuspicious: status.is_suspicious === true || status.is_suspicious === 1 || status.is_suspicious === '1',
                    isPermanent: status.is_permanently_locked === true || status.is_permanently_locked === 1 || status.is_permanently_locked === '1',
                    hardLockScore: Number.parseInt(status.hard_lock_score || 0, 10) || 0,
                    loginAttempts: Number.parseInt(status.login_attempts || 0, 10) || 0,
                    lastSuspiciousAt: status.last_suspicious_at || ''
                };
            }

            function readSecurityStatusFromOption(option) {
                const $option = $(option);
                return normalizeSecurityStatus({
                    label: $option.data('security-label') || 'Clear',
                    is_blocked: $option.data('security-blocked'),
                    is_suspicious: $option.data('security-suspicious'),
                    is_permanently_locked: $option.data('security-permanent'),
                    hard_lock_score: $option.data('security-score'),
                    last_suspicious_at: $option.data('security-last')
                });
            }

            function escapeManageAccessHtml(value) {
                if (value === null || value === undefined) return '';
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function securityBadgeHtml(status) {
                status = normalizeSecurityStatus(status);
                if (status.isPermanent) {
                    return '<span class="badge bg-danger"><i class="fas fa-lock me-1"></i>Permanently locked</span>';
                }
                if (status.isBlocked) {
                    return '<span class="badge bg-warning text-dark"><i class="fas fa-ban me-1"></i>Temporarily blocked</span>';
                }
                if (status.isSuspicious) {
                    return '<span class="badge bg-warning text-dark"><i class="fas fa-triangle-exclamation me-1"></i>Suspicious</span>';
                }
                return '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Clear</span>';
            }

            function securityDetailText(status) {
                status = normalizeSecurityStatus(status);
                const parts = [];
                if (status.hardLockScore > 0) parts.push('hard-lock score ' + status.hardLockScore);
                if (status.loginAttempts > 0) parts.push(status.loginAttempts + ' recent login attempts');
                if (status.lastSuspiciousAt) parts.push('last suspicious ' + timeAgoLabel(status.lastSuspiciousAt));
                return parts.length ? parts.join(' • ') : 'No active security flags.';
            }

            function renderUserOption(user) {
                if (user.loading || !user.id) {
                    return user.text;
                }

                const roleName = getRoleName(user.role);
                const safeName = $('<span>').text(user.name || 'Unknown').html();
                const safeEmail = $('<span>').text(user.email || '').html();
                const safeNumber = user.number ? $('<span>').text(user.number).html() : '';
                const securityStatus = normalizeSecurityStatus(user.security_status);

                let display = '<div class="d-flex align-items-center justify-content-between w-100">';
                display += '<div class="flex-grow-1">';
                display += '<div class="fw-medium">' + safeName + '</div>';
                display += '<small class="text-muted">' + safeEmail + '</small>';
                if (safeNumber) {
                    display += '<br><small class="text-muted">Number: ' + safeNumber + '</small>';
                }
                display += '</div>';
                display += '<div class="d-flex flex-column align-items-end gap-1 ms-2">';
                display += '<span class="badge bg-secondary" style="font-size: 0.7rem;">' + roleName + '</span>';
                if (securityStatus.isBlocked || securityStatus.isSuspicious) {
                    display += securityBadgeHtml(securityStatus);
                }
                display += '</div>';
                display += '</div>';

                return display;
            }

            function renderUserSelection(user) {
                if (!user.id) {
                    return user.text;
                }

                const userName = user.name || user.text || 'Unknown';
                const userEmail = user.email || '';
                const userNumber = user.number || '';

                if (userEmail) {
                    return userNumber ? userName + ' (' + userEmail + ' • ' + userNumber + ')' : userName + ' (' + userEmail + ')';
                }

                return userName;
            }

            function buildAjaxUserResults(data) {
                if (!(data.success && data.users)) {
                    console.warn('No users found or API error:', data);
                    return { results: [] };
                }

                return {
                    results: data.users.map(function (user) {
                        return {
                            id: user.id,
                            text: user.name + ' (' + user.email + ')',
                            name: user.name,
                            email: user.email,
                            number: user.number,
                            role: normalizeRole(user.role),
                            is_mentored: user.is_mentored,
                            security_status: normalizeSecurityStatus(user.security_status)
                        };
                    })
                };
            }

            function select2UserSearchConfig(placeholder) {
                return {
                    placeholder: placeholder,
                    allowClear: true,
                    width: '100%',
                    minimumInputLength: 0,
                    debug: false,
                    ajax: {
                        url: '/manage-access/search-users',
                        type: 'GET',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                query: params.term || ''
                            };
                        },
                        processResults: function (data) {
                            return buildAjaxUserResults(data);
                        },
                        error: function(xhr, status, error) {
                            console.error('Select2 AJAX Error:', {
                                status: xhr.status,
                                statusText: xhr.statusText,
                                responseText: xhr.responseText,
                                error: error
                            });
                        },
                        cache: true
                    },
                    escapeMarkup: function (markup) {
                        return markup;
                    },
                    templateResult: renderUserOption,
                    templateSelection: renderUserSelection
                };
            }

            function ensureSelectedOption($select, user) {
                if (!user || !user.id) return;

                const existingOption = $select.find('option[value="' + user.id + '"]');
                const optionText = renderUserSelection(user);

                if (existingOption.length > 0) {
                    const securityStatus = normalizeSecurityStatus(user.security_status);
                    existingOption
                        .text(optionText)
                        .attr('data-mentored', (user.is_mentored === true || user.is_mentored === 1 || user.is_mentored === '1') ? '1' : '0')
                        .attr('data-name', user.name || '')
                        .attr('data-email', user.email || '')
                        .attr('data-number', user.number || '')
                        .attr('data-role', normalizeRole(user.role))
                        .attr('data-security-label', securityStatus.label)
                        .attr('data-security-blocked', securityStatus.isBlocked ? '1' : '0')
                        .attr('data-security-suspicious', securityStatus.isSuspicious ? '1' : '0')
                        .attr('data-security-permanent', securityStatus.isPermanent ? '1' : '0')
                        .attr('data-security-score', securityStatus.hardLockScore)
                        .attr('data-security-last', securityStatus.lastSuspiciousAt);
                    return;
                }

                const option = new Option(optionText, user.id, true, true);
                const securityStatus = normalizeSecurityStatus(user.security_status);
                $(option)
                    .attr('data-mentored', (user.is_mentored === true || user.is_mentored === 1 || user.is_mentored === '1') ? '1' : '0')
                        .attr('data-name', user.name || '')
                    .attr('data-email', user.email || '')
                    .attr('data-number', user.number || '')
                    .attr('data-role', normalizeRole(user.role))
                    .attr('data-security-label', securityStatus.label)
                    .attr('data-security-blocked', securityStatus.isBlocked ? '1' : '0')
                    .attr('data-security-suspicious', securityStatus.isSuspicious ? '1' : '0')
                    .attr('data-security-permanent', securityStatus.isPermanent ? '1' : '0')
                    .attr('data-security-score', securityStatus.hardLockScore)
                    .attr('data-security-last', securityStatus.lastSuspiciousAt);
                $select.append(option);
            }
            
            // Initialize Select2 for user search - FIXED: Show all users without typing
            $('.user-select').select2(select2UserSearchConfig('Type to search for user...'));
            
            let accessBusy = false;
            let accessLoadedFor = null;
            let accessLoadRequest = null;

            function updateAccessControls() {
                const userId = $('#user_id').val();
                const courseId = $('#course_id').val();
                const ready = !accessBusy && userId && accessLoadedFor === String(userId) && courseId;
                const selected = $('#course_id option:selected');
                const enrolled = selected.attr('data-enrolled') === 'true';
                const preview = selected.attr('data-access-level') === 'preview';
                $('#grantAccessBtn').prop('disabled', !ready || (enrolled && !preview));
                $('#revokeAccessBtn').prop('disabled', !ready || !enrolled);
                if (accessBusy) return;
                $('#grantAccessBtn').html('<i class="fas fa-user-plus me-2"></i>' + (preview ? 'Grant Full Course Access' : 'Grant Course Access'));
                $('#revokeAccessBtn').html('<i class="fas fa-user-times me-2"></i>Revoke Course Access');
                if (!userId) {
                    $('#accessSelectionSummary').text('1. Choose a user. 2. Choose a course. 3. Review their access below.');
                } else if (accessLoadedFor !== String(userId)) {
                    $('#accessSelectionSummary').text('Load course access before making changes.');
                } else if (!courseId) {
                    $('#accessSelectionSummary').text('Choose a course to see this user’s current access.');
                } else {
                    const status = enrolled ? (preview ? 'Preview access' : 'Full course access') : 'Not enrolled';
                    $('#accessSelectionSummary').text($('#user_id option:selected').attr('data-name') + ' · ' + selected.attr('data-course-name') + ' — ' + status);
                }
            }

            function changeCourseAccess(grant) {
                if (accessBusy) return;
                const userId = $('#user_id').val();
                const courseId = $('#course_id').val();
                const button = grant ? $('#grantAccessBtn') : $('#revokeAccessBtn');
                if (!userId || !courseId || button.prop('disabled')) return;
                accessBusy = true;
                $('#grantAccessBtn, #revokeAccessBtn, #user_id, #course_id').prop('disabled', true);
                button.html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');
                $.ajax({
                    url: grant ? '/manage-access/enroll' : '/manage-access/revoke',
                    method: 'POST',
                    timeout: 12000,
                    data: {user_id: userId, course_id: courseId, is_enrolled: grant ? 1 : 0, _token: MANAGE_ACCESS_CONFIG.csrfToken},
                    success: function(response) {
                        if (!response.success) {
                            showNotification('error', 'Access Not Updated', response.message || 'Please try again.');
                            return;
                        }
                        showNotification(grant ? 'success' : 'warning', grant ? 'Access Granted' : 'Access Revoked', response.message);
                        refreshEnrollmentData();
                        if (typeof window.loadPayments === 'function') window.loadPayments();
                    },
                    error: function(xhr) {
                        showNotification('error', 'Access Not Updated', xhr.responseJSON?.message || 'Please try again.');
                    },
                    complete: function() {
                        accessBusy = false;
                        $('#user_id').prop('disabled', false);
                        updateCourseDropdown(userId, courseId);
                    }
                });
            }
            $('#grantAccessBtn').on('click', function() { changeCourseAccess(true); });
            $('#revokeAccessBtn').on('click', function() { changeCourseAccess(false); });
            $('#course_id').on('change', updateAccessControls);
            $('#retryAccessStatus').on('click', function() { updateCourseDropdown($('#user_id').val()); });

            let currentSubjectLockRows = [];

            function selectedSubjectLockCourseId() {
                return $('#subject_lock_course_id').val();
            }

            function selectedSubjectLockSubject() {
                return $('#subject_lock_subject').val();
            }

            function setSubjectLockControlsEnabled(enabled) {
                $('#lockSubjectBtn, #unlockSubjectBtn').prop('disabled', !enabled);
            }

            function renderSubjectLockRows(subjects) {
                const $tbody = $('#subjectLockTableBody');
                const $select = $('#subject_lock_subject');
                currentSubjectLockRows = subjects || [];

                $tbody.empty();
                $select.empty();

                if (!currentSubjectLockRows.length) {
                    $select.prop('disabled', true).append('<option value="">No subjects found</option>');
                    $tbody.append('<tr><td colspan="3" class="text-muted text-center py-3">No topics with a subject were found for this course.</td></tr>');
                    $('#subjectLockSummary').text('No subjects');
                    $('#subjectLockStats').text('Add a subject to topics before using group locks.');
                    setSubjectLockControlsEnabled(false);
                    return;
                }

                $select.prop('disabled', false).append('<option value="">Choose subject...</option>');

                currentSubjectLockRows.forEach(function(row) {
                    const subject = row.subject || '';
                    const total = Number.parseInt(row.total_topics || 0, 10);
                    const locked = Number.parseInt(row.locked_topics || 0, 10);
                    const escapedSubject = escapeManageAccessHtml(subject);

                    $select.append(
                        '<option value="' + escapedSubject + '" data-total="' + total + '" data-locked="' + locked + '">' +
                            escapedSubject + ' (' + locked + '/' + total + ' locked)' +
                        '</option>'
                    );

                    $tbody.append(
                        '<tr>' +
                            '<td>' + escapedSubject + '</td>' +
                            '<td>' + total + '</td>' +
                            '<td><span class="badge ' + (locked >= total && total > 0 ? 'bg-danger' : (locked > 0 ? 'bg-warning text-dark' : 'bg-success')) + '">' + locked + '/' + total + '</span></td>' +
                        '</tr>'
                    );
                });

                $('#subjectLockSummary').text(currentSubjectLockRows.length + ' subject(s)');
                $('#subjectLockStats').text('Choose a subject to lock or unlock all matching topics in this course.');
                setSubjectLockControlsEnabled(false);
            }

            function loadSubjectLocksForCourse() {
                const courseId = selectedSubjectLockCourseId();
                const $select = $('#subject_lock_subject');

                setSubjectLockControlsEnabled(false);
                currentSubjectLockRows = [];

                if (!courseId) {
                    $select.prop('disabled', true).html('<option value="">Select a course first...</option>');
                    $('#subjectLockTableBody').html('<tr><td colspan="3" class="text-muted text-center py-3">Choose a course to view subject lock status.</td></tr>');
                    $('#subjectLockSummary').text('Select a course');
                    $('#subjectLockStats').text('Subjects are loaded from existing topics.');
                    return;
                }

                $select.prop('disabled', true).html('<option value="">Loading subjects...</option>');
                $('#subjectLockTableBody').html('<tr><td colspan="3" class="text-muted text-center py-3"><i class="fas fa-spinner fa-spin me-1"></i>Loading subjects...</td></tr>');
                $('#subjectLockSummary').text('Loading...');

                $.ajax({
                    url: MANAGE_ACCESS_ROUTES.courseSubjects || '/manage-access/course-subjects',
                    method: 'GET',
                    data: { course_id: courseId },
                    success: function(response) {
                        if (response.success) {
                            renderSubjectLockRows(response.subjects || []);
                        } else {
                            renderSubjectLockRows([]);
                            showNotification('error', 'Subjects Failed', response.message || 'Could not load course subjects.');
                        }
                    },
                    error: function(xhr) {
                        renderSubjectLockRows([]);
                        showNotification('error', 'Subjects Failed', xhr.responseJSON?.message || 'Could not load course subjects.');
                    }
                });
            }

            function updateSelectedSubjectStats() {
                const selectedOption = $('#subject_lock_subject option:selected');
                const subject = selectedSubjectLockSubject();

                if (!subject) {
                    $('#subjectLockStats').text('Choose a subject to lock or unlock all matching topics in this course.');
                    setSubjectLockControlsEnabled(false);
                    return;
                }

                const total = Number.parseInt(selectedOption.data('total') || 0, 10);
                const locked = Number.parseInt(selectedOption.data('locked') || 0, 10);
                $('#subjectLockStats').text(locked + ' of ' + total + ' topic(s) are currently locked.');
                setSubjectLockControlsEnabled(true);
            }

            function updateSubjectLockState(shouldLock) {
                const courseId = selectedSubjectLockCourseId();
                const subject = selectedSubjectLockSubject();

                if (!courseId || !subject) {
                    showNotification('warning', 'Missing Selection', 'Please select both a course and a subject.');
                    return;
                }

                const $btn = shouldLock ? $('#lockSubjectBtn') : $('#unlockSubjectBtn');
                const originalHtml = $btn.html();
                $('#lockSubjectBtn, #unlockSubjectBtn').prop('disabled', true);
                $btn.html('<i class="fas fa-spinner fa-spin me-1"></i>Working...');

                $.ajax({
                    url: shouldLock
                        ? (MANAGE_ACCESS_ROUTES.lockSubject || '/manage-access/subjects/lock')
                        : (MANAGE_ACCESS_ROUTES.unlockSubject || '/manage-access/subjects/unlock'),
                    method: 'POST',
                    data: {
                        course_id: courseId,
                        subject: subject,
                        _token: MANAGE_ACCESS_CONFIG.csrfToken
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification('success', shouldLock ? 'Subject Locked' : 'Subject Unlocked', response.message || 'Subject lock state updated.');
                            loadSubjectLocksForCourse();
                        } else {
                            showNotification('error', 'Update Failed', response.message || 'Could not update subject lock state.');
                            updateSelectedSubjectStats();
                        }
                    },
                    error: function(xhr) {
                        showNotification('error', 'Update Failed', xhr.responseJSON?.message || 'Could not update subject lock state.');
                        updateSelectedSubjectStats();
                    },
                    complete: function() {
                        $btn.html(originalHtml);
                    }
                });
            }

            $('#subject_lock_course_id').on('change', loadSubjectLocksForCourse);
            $('#subject_lock_subject').on('change', updateSelectedSubjectStats);
            $('#lockSubjectBtn').on('click', function(e) {
                e.preventDefault();
                updateSubjectLockState(true);
            });
            $('#unlockSubjectBtn').on('click', function(e) {
                e.preventDefault();
                updateSubjectLockState(false);
            });
            

            
            // User selection handler
            $('#user_id').on('change', function() {
                try {
                    const userId = $(this).val();
                    const userOption = $(this).find('option:selected');

                    if (userId) {
                        const userName = userOption.attr('data-name') || userOption.text();
                        const userEmail = userOption.data('email');
                        const userNumber = userOption.data('number');
                        const userRole = userOption.data('role');
                        const securityStatus = readSecurityStatusFromOption(userOption);

                        // Safe integer handling for role
                        const safeUserRole = normalizeRole(userRole);
                        const displayRole = safeUserRole === 1 ? 'ADMIN' : safeUserRole === 2 ? 'TUTOR' : 'STUDENT';

                        // Set user initial (first letter of name) - safe handling
                        const userInitial = (userName && userName.length > 0) ? userName.charAt(0).toUpperCase() : 'U';
                        $('#userInitial').text(userInitial);

                        // Update UI with safe fallbacks
                        $('#selectedUserName').text(userName || 'Unknown');
                        $('#selectedUserEmail').text(userEmail || 'No Email');
                        $('#selectedUserNumber').text(userNumber ? `Number: ${userNumber}` : '');
                        $('#selectedUserRole').removeClass().addClass('badge').addClass(safeUserRole === 1 ? 'bg-danger' : 'bg-secondary').text(displayRole);
                        $('#selectedUserSecurity').html(securityBadgeHtml(securityStatus) + ' <span class="text-muted">' + escHtml(securityDetailText(securityStatus)) + '</span>');
                        $('#userInfoSection').show();
                        $('#mentorshipToggleWrapper').show();
                        $('#mentorshipStatusToggle').prop('checked', userOption.attr('data-mentored') === '1');

                        // Update course dropdown with enrollment status
                        updateCourseDropdown(userId);
                    } else {
                        $('#userInfoSection').hide();
                        $('#mentorshipToggleWrapper').hide();
                        $('#selectedUserNumber').text('');
                        $('#selectedUserSecurity').empty();
                        // Reset course dropdown
                        resetCourseDropdown();
                    }
                } catch (error) {
                    console.error('Error in user selection handler:', error);
                    // Fallback behavior
                    $('#userInfoSection').hide();
                }
            });
            
            // Ignore responses for a previous user and preserve the course after saving.
            function updateCourseDropdown(userId, selectedCourseId = '') {
                accessLoadedFor = null;
                if (accessLoadRequest) accessLoadRequest.abort();
                $('#retryAccessStatus').prop('hidden', true);
                const courseSelect = $('#course_id');
                courseSelect.prop('disabled', true).empty().append(new Option('Loading course access...', ''));
                updateAccessControls();
                $('#accessSelectionSummary').text('Loading current course access...');
                accessLoadRequest = $.ajax({
                    url: '/manage-access/user-enrollments',
                    method: 'GET',
                    data: {user_id: userId},
                    success: function(response) {
                        if (String($('#user_id').val()) !== String(userId)) return;
                        if (!response.success || !Array.isArray(response.courses)) {
                            showAccessLoadError();
                            return;
                        }
                        courseSelect.empty().append(new Option('Choose a course...', ''));
                        response.courses.forEach(function(course) {
                            const enrolled = course.is_enrolled === true || course.is_enrolled === 1 || course.is_enrolled === '1';
                            const status = enrolled ? (course.access_level === 'preview' ? 'Preview access' : 'Full access') : 'Not enrolled';
                            const option = new Option(course.name + ' — ' + status, course.id);
                            $(option).attr('data-enrolled', String(enrolled)).attr('data-access-level', course.access_level || '')
                                .attr('data-course-name', course.name).attr('data-price', course.price);
                            courseSelect.append(option);
                        });
                        accessLoadedFor = String(userId);
                        courseSelect.prop('disabled', false).val(selectedCourseId);
                        updateAccessControls();
                    },
                    error: function(xhr, status) {
                        if (status === 'abort' || String($('#user_id').val()) !== String(userId)) return;
                        showAccessLoadError();
                    }
                });
            }

            function showAccessLoadError() {
                accessLoadedFor = null;
                $('#course_id').prop('disabled', true).empty().append(new Option('Course access unavailable', ''));
                updateAccessControls();
                $('#accessSelectionSummary').text('Could not load course access. Retry to continue.');
                $('#retryAccessStatus').prop('hidden', false);
            }

            function resetCourseDropdown() {
                accessLoadedFor = null;
                if (accessLoadRequest) accessLoadRequest.abort();
                $('#course_id').prop('disabled', true).empty().append(new Option('Choose a user first...', ''));
                $('#retryAccessStatus').prop('hidden', true);
                updateAccessControls();
            }
            resetCourseDropdown();

            $('#user_id').on('select2:select', function(event) {
                ensureSelectedOption($(this), event.params.data);
                $(this).trigger('change');
            });

            // Initialize Admin User Select2
            $('.admin-user-select').select2(select2UserSearchConfig('Type to search for user...'));

            $('.admin-user-select').on('select2:select', function(event) {
                ensureSelectedOption($(this), event.params.data);
            });
            
            // Admin Management Variables
            let selectedAdminUserId = null;
            let selectedAdminIsAdmin = false;
            let adminRoleBusy = false;
            let selectedAdminSecurityStatus = normalizeSecurityStatus();
            
            // Admin user selection handler
            $('#admin_user_id').on('change', function() {
                try {
                    const userId = $(this).val();
                    const userOption = $(this).find('option:selected');


                    if (userId) {
                        const userName = userOption.data('name');
                        const userEmail = userOption.data('email');
                        const userNumber = userOption.data('number');
                        const userRole = userOption.data('role');
                        selectedAdminSecurityStatus = readSecurityStatusFromOption(userOption);


                        selectedAdminUserId = userId;
                        const normalizedRole = normalizeRole(userRole);
                        selectedAdminIsAdmin = normalizedRole === 1;

                        const displayRole = normalizedRole === 1 ? 'ADMIN' : (normalizedRole === 2 ? 'TUTOR' : 'STUDENT');

                        // Update UI
                        $('#selectedAdminUserName').text(userName || 'Unknown');
                        $('#selectedAdminUserEmail').text(userEmail || 'No Email');
                        $('#selectedAdminUserNumber').text(userNumber ? `Number: ${userNumber}` : '');
                        $('#selectedAdminUserStatus').removeClass().addClass('badge').addClass(normalizedRole === 1 ? 'bg-danger' : 'bg-secondary').text(displayRole);
                        $('#selectedAdminSecurityStatus').html(securityBadgeHtml(selectedAdminSecurityStatus) + ' <span class="text-muted">' + escHtml(securityDetailText(selectedAdminSecurityStatus)) + '</span>');
                        $('#selectedUserInfo').show();
                        $('#securityActionPanel').show();
                        $('#securityActionText').text(securityDetailText(selectedAdminSecurityStatus));
                        $('#clearSecurityLockBtn').prop('disabled', !(selectedAdminSecurityStatus.isBlocked || selectedAdminSecurityStatus.isSuspicious));

                        // FIXED: Update buttons - compare with boolean true/false
                        if (selectedAdminIsAdmin === true) {
                            $('#promoteToAdminBtn').prop('disabled', true).html('<i class="fas fa-check me-2"></i>Already Admin');
                            $('#removeAdminBtn').prop('disabled', false).html('<i class="fas fa-user-times me-2"></i>Revoke Admin');
                            $('#adminActionHint').text('This user is currently an administrator');
                        } else {
                            $('#promoteToAdminBtn').prop('disabled', false).html('<i class="fas fa-user-shield me-2"></i>Promote to Admin');
                            $('#removeAdminBtn').prop('disabled', true).html('<i class="fas fa-user-times me-2"></i>Not Admin');
                            $('#adminActionHint').text('This user is not an administrator');
                        }
                    } else {
                        $('#selectedUserInfo').hide();
                        $('#selectedAdminUserNumber').text('');
                        $('#promoteToAdminBtn').prop('disabled', true).html('<i class="fas fa-user-shield me-2"></i>Promote to Admin');
                        $('#removeAdminBtn').prop('disabled', true).html('<i class="fas fa-user-times me-2"></i>Revoke Admin');
                        $('#adminActionHint').text('Select a user above to manage their admin privileges');
                        selectedAdminUserId = null;
                        selectedAdminIsAdmin = false;
                        selectedAdminSecurityStatus = normalizeSecurityStatus();
                        $('#selectedAdminSecurityStatus').empty();
                        $('#securityActionPanel').hide();
                    }
                } catch (error) {
                    console.error('Error in admin user selection handler:', error);
                    // Fallback behavior
                    $('#selectedUserInfo').hide();
                    $('#promoteToAdminBtn').prop('disabled', true);
                    $('#removeAdminBtn').prop('disabled', true);
                    $('#securityActionPanel').hide();
                }
            });
            

            
            // MENTORSHIP STATUS TOGGLE HANDLER (0/1)
            $('#mentorshipStatusToggle').on('change', function() {
                const userId = $('#user_id').val();
                if (!userId) return;

                const isChecked = $(this).is(':checked');
                const action = isChecked ? 'grant' : 'revoke';
                const $toggle = $(this);
                
                // Disable toggle during animated state
                $toggle.prop('disabled', true);
                
                $.ajax({
                    url: '/manage-access/toggle-mentorship',
                    method: 'POST',
                    data: {
                        user_id: userId,
                        action: action,
                        _token: MANAGE_ACCESS_CONFIG.csrfToken
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification(isChecked ? 'success' : 'warning', 'Mentorship Updated', response.message);
                            
                            // Update the badge
                            if (isChecked) {
                                $('#userMentorshipBadge').fadeIn(300);
                            } else {
                                $('#userMentorshipBadge').fadeOut(300);
                            }

                            // Update the data-mentored attribute on the option so it persists during current session
                            $('#user_id option[value="' + userId + '"]').attr('data-mentored', isChecked ? '1' : '0').data('mentored', isChecked ? '1' : '0');
                            
                            // Refresh the table to show the new 0/1 status
                            refreshEnrollmentData();
                        } else {
                            showNotification('error', 'Update Failed', response.message);
                            $toggle.prop('checked', !isChecked); // Revert
                        }
                    },
                    error: function(xhr) {
                        showNotification('error', 'Server Error', 'Failed to update mentorship status.');
                        $toggle.prop('checked', !isChecked); // Revert
                    },
                    complete: function() {
                        $toggle.prop('disabled', false);
                    }
                });
            });
            
            // Promote to Admin button handler
            $('#promoteToAdminBtn').on('click', function(e) {
                e.preventDefault();
                
                if (adminRoleBusy) return;
                if (!selectedAdminUserId || selectedAdminIsAdmin === true) {
                    showNotification('warning', 'Invalid Action', 'Please select a non-admin user to promote.');
                    return;
                }
                
                if (!confirm('Are you sure you want to promote this user to administrator?')) {
                    return;
                }
                
                adminRoleBusy = true;
                $('#admin_user_id, #promoteToAdminBtn, #removeAdminBtn').prop('disabled', true);
                var btn = $(this);
                btn.html('<i class="fas fa-spinner fa-spin me-2"></i>Promoting...').prop('disabled', true);
                
                $.ajax({
                    url: '/manage-access/toggle-admin',
                    method: 'POST',
                    data: {
                        user_id: selectedAdminUserId,
                        action: 'grant',
                        _token: MANAGE_ACCESS_CONFIG.csrfToken
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification('success', 'Admin Promoted!', response.message || 'User promoted to administrator successfully.');
                            // Update UI in place
                            selectedAdminIsAdmin = true;
                            $('#admin_user_id').find('option:selected').attr('data-role', 1).data('role', 1);
                            $('#selectedAdminUserStatus').removeClass('bg-secondary').addClass('bg-danger').text('ADMIN');
                            btn.prop('disabled', true).html('<i class="fas fa-check me-2"></i>Already Admin');
                            $('#removeAdminBtn').prop('disabled', false).html('<i class="fas fa-user-times me-2"></i>Revoke Admin');
                            $('#adminActionHint').text('This user is currently an administrator');
                        } else {
                            showNotification('error', 'Promotion Failed', response.message || 'Failed to promote user.');
                        }
                    },
                    error: function(xhr) {
                        showNotification('error', 'Promotion Failed', xhr.responseJSON?.message || 'Failed to promote user.');
                    },
                    complete: function() {
                        adminRoleBusy = false;
                        $('#admin_user_id').prop('disabled', false);
                        $('#promoteToAdminBtn').prop('disabled', selectedAdminIsAdmin);
                        $('#removeAdminBtn').prop('disabled', !selectedAdminIsAdmin);
                        if (!selectedAdminIsAdmin) {
                            btn.html('<i class="fas fa-user-shield me-2"></i>Promote to Admin').prop('disabled', false);
                        }
                    }
                });
            });
            
            // Remove Admin button handler
            $('#removeAdminBtn').on('click', function(e) {
                e.preventDefault();
                
                if (adminRoleBusy) return;
                if (!selectedAdminUserId || selectedAdminIsAdmin !== true) {
                    showNotification('warning', 'Invalid Action', 'Please select an admin user to revoke privileges.');
                    return;
                }
                
                if (!confirm('Are you sure you want to revoke administrator privileges from this user?')) {
                    return;
                }
                
                adminRoleBusy = true;
                $('#admin_user_id, #promoteToAdminBtn, #removeAdminBtn').prop('disabled', true);
                var btn = $(this);
                btn.html('<i class="fas fa-spinner fa-spin me-2"></i>Revoking...').prop('disabled', true);
                
                $.ajax({
                    url: '/manage-access/toggle-admin',
                    method: 'POST',
                    data: {
                        user_id: selectedAdminUserId,
                        action: 'revoke',
                        _token: MANAGE_ACCESS_CONFIG.csrfToken
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification('success', 'Admin Revoked', response.message || 'Administrator privileges revoked successfully.');
                            // Update UI in place
                            selectedAdminIsAdmin = false;
                            $('#admin_user_id').find('option:selected').attr('data-role', response.new_role).data('role', response.new_role);
                            $('#selectedAdminUserStatus').removeClass('bg-danger').addClass('bg-secondary').text('STUDENT');
                            btn.prop('disabled', true).html('<i class="fas fa-user-times me-2"></i>Not Admin');
                            $('#promoteToAdminBtn').prop('disabled', false).html('<i class="fas fa-user-shield me-2"></i>Promote to Admin');
                            $('#adminActionHint').text('This user is not an administrator');
                        } else {
                            showNotification('error', 'Revoke Failed', response.message || 'Failed to revoke admin privileges.');
                        }
                    },
                    error: function(xhr) {
                        showNotification('error', 'Revoke Failed', xhr.responseJSON?.message || 'Failed to revoke admin privileges.');
                    },
                    complete: function() {
                        adminRoleBusy = false;
                        $('#admin_user_id').prop('disabled', false);
                        $('#promoteToAdminBtn').prop('disabled', selectedAdminIsAdmin);
                        $('#removeAdminBtn').prop('disabled', !selectedAdminIsAdmin);
                        if (selectedAdminIsAdmin) {
                            btn.html('<i class="fas fa-user-times me-2"></i>Revoke Admin').prop('disabled', false);
                        }
                    }
                });
            });

            function markUserSecurityClear(userId, status) {
                status = normalizeSecurityStatus(status);
                $('option[value="' + userId + '"]').each(function() {
                    $(this)
                        .attr('data-security-label', status.label)
                        .attr('data-security-blocked', status.isBlocked ? '1' : '0')
                        .attr('data-security-suspicious', status.isSuspicious ? '1' : '0')
                        .attr('data-security-permanent', status.isPermanent ? '1' : '0')
                        .attr('data-security-score', status.hardLockScore)
                        .attr('data-security-last', status.lastSuspiciousAt)
                        .data('security-label', status.label)
                        .data('security-blocked', status.isBlocked ? '1' : '0')
                        .data('security-suspicious', status.isSuspicious ? '1' : '0')
                        .data('security-permanent', status.isPermanent ? '1' : '0')
                        .data('security-score', status.hardLockScore)
                        .data('security-last', status.lastSuspiciousAt);
                });
            }

            function clearSecurityLock(userId, btn) {
                if (!userId) {
                    showNotification('warning', 'No User Selected', 'Select a user before clearing a security lock.');
                    return;
                }

                if (!confirm('Clear temporary/permanent security lock state for this user?')) {
                    return;
                }

                const $btn = $(btn);
                const originalHtml = $btn.html();
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Clearing...');

                $.ajax({
                    url: '/manage-access/security-lock/clear',
                    method: 'POST',
                    data: {
                        user_id: userId,
                        _token: MANAGE_ACCESS_CONFIG.csrfToken
                    },
                    success: function(response) {
                        if (response.success) {
                            const status = normalizeSecurityStatus(response.security_status);
                            markUserSecurityClear(userId, status);
                            selectedAdminSecurityStatus = status;
                            $('#selectedAdminSecurityStatus').html(securityBadgeHtml(status) + ' <span class="text-muted">' + escHtml(securityDetailText(status)) + '</span>');
                            $('#securityActionText').text(securityDetailText(status));
                            $('#clearSecurityLockBtn').prop('disabled', true);
                            showNotification('success', 'Security Lock Cleared', response.message || 'Security lock state was cleared.');
                            loadSecurityUsers();
                        } else {
                            showNotification('error', 'Clear Failed', response.message || 'Failed to clear security lock.');
                        }
                    },
                    error: function(xhr) {
                        showNotification('error', 'Clear Failed', xhr.responseJSON?.message || 'Failed to clear security lock.');
                    },
                    complete: function() {
                        $btn.html(originalHtml);
                        if ($btn.attr('id') === 'clearSecurityLockBtn' && (selectedAdminSecurityStatus.isBlocked || selectedAdminSecurityStatus.isSuspicious)) {
                            $btn.prop('disabled', false);
                        }
                    }
                });
            }

            $('#clearSecurityLockBtn').on('click', function(e) {
                e.preventDefault();
                clearSecurityLock(selectedAdminUserId, this);
            });

            function loadSecurityUsers() {
                const tbody = $('#securityUsersTable tbody');
                tbody.html('<tr><td colspan="5" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading security watchlist...</td></tr>');

                $.ajax({
                    url: '/manage-access/security-users',
                    method: 'GET',
                    data: { limit: 50 },
                    success: function(response) {
                        tbody.empty();
                        if (!response.success || !response.users || response.users.length === 0) {
                            tbody.append('<tr><td colspan="5" class="text-center py-4 text-muted"><i class="fas fa-shield-alt fa-2x mb-2"></i><div>No suspicious or blocked accounts found</div></td></tr>');
                            return;
                        }

                        response.users.forEach(function(user) {
                            const status = normalizeSecurityStatus(user.security_status);
                            const lastEvent = user.last_event_at
                                ? escHtml((user.last_event_type || 'security_event') + ' • ' + timeAgoLabel(user.last_event_at))
                                : '—';
                            const disabled = status.isBlocked || status.isSuspicious ? '' : ' disabled';

                            tbody.append(
                                '<tr data-user-id="' + user.id + '">' +
                                    '<td><div class="fw-medium">' + escHtml(user.name || 'Unknown') + '</div><small class="text-muted">' + escHtml(user.email || '') + '</small></td>' +
                                    '<td>' + securityBadgeHtml(status) + '<div class="small text-muted mt-1">' + escHtml(securityDetailText(status)) + '</div></td>' +
                                    '<td><span class="badge bg-secondary">' + escHtml(user.last_risk_level || 'high') + '</span><div class="small text-muted mt-1">' + lastEvent + '</div></td>' +
                                    '<td>' + escHtml(String(user.high_risk_count || 0)) + '</td>' +
                                    '<td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm clear-security-row-btn" data-user-id="' + user.id + '"' + disabled + '><i class="fas fa-unlock me-1"></i>Revoke Lock</button></td>' +
                                '</tr>'
                            );
                        });
                    },
                    error: function(xhr) {
                        console.error('Failed to load security watchlist:', xhr.responseText);
                        tbody.html('<tr><td colspan="5" class="text-center py-4 text-danger">Failed to load security watchlist.</td></tr>');
                    }
                });
            }

            $('#securityUsersTable').on('click', '.clear-security-row-btn', function(e) {
                e.preventDefault();
                clearSecurityLock($(this).data('user-id'), this);
            });

            $('#refreshSecurityUsers').on('click', function() {
                const btn = $(this);
                btn.find('i').addClass('fa-spin');
                loadSecurityUsers();
                setTimeout(function() {
                    btn.find('i').removeClass('fa-spin');
                }, 800);
            });

            loadSecurityUsers();
            
            // Enrollment filter handler
            $('#enrollmentFilter').change(function() {
                const filter = $(this).val();
                const rows = $('#enrollmentsTable tbody tr');
                
                rows.each(function() {
                    const row = $(this);
                    const activeCount = parseInt(row.data('active-count') || 0);
                    const revokedCount = parseInt(row.data('revoked-count') || 0);
                    const isMentored = row.data('is-mentored') == '1';
                    
                    if (filter === 'all') {
                        row.show();
                    } else if (filter === 'mentored' && isMentored) {
                        row.show();
                    } else if (filter === 'has_active' && activeCount > 0) {
                        row.show();
                    } else if (filter === 'has_revoked' && revokedCount > 0) {
                        row.show();
                    } else {
                        row.hide();
                    }
                });
            });
            
            // Refresh enrollments handler
            $('#refreshEnrollments').click(function() {
                const btn = $(this);
                btn.find('i').addClass('fa-spin');
                
                refreshEnrollmentData();
                
                setTimeout(() => {
                    btn.find('i').removeClass('fa-spin');
                }, 1000);
            });
            
            // HTML escape helper to prevent XSS
            function escHtml(str) {
                if (!str) return '';
                var div = document.createElement('div');
                div.appendChild(document.createTextNode(str));
                return div.innerHTML;
            }

            // Relative time helper
            function timeAgoLabel(dateStr) {
                if (!dateStr) return 'Unknown';
                var diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
                if (diff < 60)    return 'just now';
                if (diff < 3600)  return Math.floor(diff / 60) + ' min ago';
                if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
                if (diff < 604800) return Math.floor(diff / 86400) + ' days ago';
                return Math.floor(diff / 604800) + ' weeks ago';
            }

            // Function to refresh enrollment data smoothly
            function refreshEnrollmentData() {
                $.ajax({
                    url: '/manage-access/enrollments',
                    method: 'GET',
                    success: function(response) {
                        if (response.success && response.enrollments) {
                            updateEnrollmentTable(response.enrollments);
                        }
                    },
                    error: function(xhr) {
                        console.error('Failed to refresh enrollment data:', xhr.responseText);
                    }
                });
            }
            
            // Function to update enrollment table (grouped by user)
            function updateEnrollmentTable(enrollments) {
                const tbody = $('#enrollmentsTable tbody');
                tbody.empty();
                
                if (enrollments.length === 0) {
                    tbody.append(`
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <div>No enrollments found</div>
                                <small>Start by granting course access to users</small>
                            </td>
                        </tr>
                    `);
                } else {
                    enrollments.forEach(function(userRow) {
                        var courseBadges = '';
                        if (userRow.courses && userRow.courses.length) {
                            userRow.courses.forEach(function(c) {
                                var cls = c.is_enrolled ? 'bg-success' : 'bg-secondary text-decoration-line-through';
                                var title = c.is_enrolled ? 'Active' : 'Revoked';
                                courseBadges += '<span class="badge ' + cls + '" style="font-size:0.72rem;" title="' + title + '">' + escHtml(c.course_name) + '</span> ';
                            });
                        }

                        var statusHtml = '';
                        if (userRow.active_count > 0 && userRow.revoked_count > 0) {
                            statusHtml = '<span class="badge bg-success">' + userRow.active_count + ' active</span> <span class="badge bg-secondary">' + userRow.revoked_count + ' revoked</span>';
                        } else if (userRow.active_count > 0) {
                            statusHtml = '<span class="badge bg-success"><i class="fas fa-check me-1"></i>' + userRow.active_count + ' active</span>';
                        } else {
                            statusHtml = '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>All revoked</span>';
                        }

                        var dateHtml = '<span class="text-muted">—</span>';
                        if (userRow.last_activity) {
                            var d = new Date(userRow.last_activity);
                            var dateStr = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                            var ago = timeAgoLabel(userRow.last_activity);
                            dateHtml = '<div>' + escHtml(dateStr) + '</div><small class="text-muted">' + escHtml(ago) + '</small>';
                        }

                        var safeName = escHtml(userRow.name || 'Unknown');
                        var safeEmail = escHtml(userRow.email || '');
                        var initial = safeName.charAt(0).toUpperCase() || 'U';

                        var isMentored = userRow.is_mentored == 1 || userRow.is_mentored === true;
                        var mentorshipHtml = isMentored ? 
                            '<span class="badge bg-success" style="font-size: 0.9rem; padding: 0.4em 0.8em;">1</span>' : 
                            '<span class="badge bg-light text-muted border" style="font-size: 0.9rem; padding: 0.4em 0.8em;">0</span>';

                        tbody.append(
                            '<tr data-active-count="' + userRow.active_count + '" data-revoked-count="' + userRow.revoked_count + '" data-is-mentored="' + (isMentored ? '1' : '0') + '">' +
                            '<td>' +
                                '<div class="d-flex align-items-center">' +
                                    '<div class="me-2">' +
                                        '<div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:0.8rem;">' +
                                            initial +
                                        '</div>' +
                                    '</div>' +
                                    '<div>' +
                                        '<div class="fw-medium">' + safeName + '</div>' +
                                        '<small class="text-muted">' + safeEmail + '</small>' +
                                    '</div>' +
                                '</div>' +
                            '</td>' +
                            '<td class="text-center">' + mentorshipHtml + '</td>' +
                            '<td><div class="d-flex flex-wrap gap-1" style="max-width:280px;">' + courseBadges + '</div></td>' +
                            '<td>' + statusHtml + '</td>' +
                            '<td>' + dateHtml + '</td>' +
                            '</tr>'
                        );
                    });
                }
            }
            
    // ── Last Active Users ──────────────────────────────────────────────
    (function initLastActiveUsers() {
        var laState = { page: 1, search: '', debounce: null };
        var studentProfileBaseUrl = MANAGE_ACCESS_CONFIG.studentProfileBaseUrl || '/student';
        var deleteUserBaseUrl = MANAGE_ACCESS_CONFIG.deleteUserBaseUrl || '/admin/delete/user';
        var STATUS_LABELS = {
            online:    'Online',
            recent:    'Recently',
            today:     'Today',
            this_week: 'This Week',
            inactive:  'Inactive',
            never:     'Never'
        };
        var MODE_LABELS = {
            quiz: 'Quiz',
            tutor: 'Tutor',
            assignment: 'Assignment',
            timed: 'Timed',
            practice: 'Practice'
        };

        function loadLastActive(page, search) {
            laState.page = page || 1;
            laState.search = search || '';

            var tbody = $('#lastActiveBody');
            tbody.html(
                '<tr><td colspan="7" class="text-center py-4 text-muted">' +
                '<i class="fas fa-spinner fa-spin fa-2x mb-2"></i>' +
                '<div>Loading user activity...</div></td></tr>'
            );

            $.ajax({
                url: '/manage-access/last-active-users',
                method: 'GET',
                data: { page: laState.page, per_page: 20, search: laState.search },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(resp) {
                    if (!resp.success) {
                        tbody.html(
                            '<tr><td colspan="7" class="text-center py-4 text-muted">' +
                            '<i class="fas fa-exclamation-triangle fa-2x mb-2"></i>' +
                            '<div>Failed to load data</div></td></tr>'
                        );
                        return;
                    }
                    renderLastActive(resp);
                },
                error: function() {
                    tbody.html(
                        '<tr><td colspan="7" class="text-center py-4 text-muted">' +
                        '<i class="fas fa-exclamation-triangle fa-2x mb-2"></i>' +
                        '<div>Failed to load data</div></td></tr>'
                    );
                }
            });
        }

        function renderLastActive(data) {
            var tbody = $('#lastActiveBody');
            tbody.empty();

            if (!data.users || data.users.length === 0) {
                tbody.html(
                    '<tr><td colspan="7" class="text-center py-4 text-muted">' +
                    '<i class="fas fa-users fa-2x mb-2"></i>' +
                    '<div>No users found</div></td></tr>'
                );
                $('#lastActivePagination').hide();
                return;
            }

            data.users.forEach(function(u) {
                var safeName = escHtml(u.name || 'Unknown');
                var safeEmail = escHtml(u.email || '');
                var initial = (u.name || u.email || 'U').charAt(0).toUpperCase();
                var profileUrl = studentProfileBaseUrl + '/' + encodeURIComponent(u.id) + '/profile';
                var deleteUrl = deleteUserBaseUrl + '/' + encodeURIComponent(u.id);
                var roleBadge = u.role === 1
                    ? '<span class="badge bg-danger">Admin</span>'
                    : u.role === 2
                        ? '<span class="badge bg-info">Tutor</span>'
                        : '<span class="badge bg-secondary">Student</span>';

                var statusLabel = STATUS_LABELS[u.status] || 'Unknown';
                var lastActiveText = escHtml(u.last_active_human || 'Never');

                // Build last quiz cell
                var quizCell = '<span class="text-muted" style="font-size:0.8rem;">No quizzes yet</span>';
                var lq = u.last_quiz;
                if (lq) {
                    var modeLabel = MODE_LABELS[lq.mode] || escHtml(lq.mode || 'Quiz');
                    var pctClass = lq.pct >= 70 ? 'text-success' : lq.pct >= 50 ? 'text-warning' : 'text-danger';
                    quizCell =
                        '<div style="font-size:0.82rem;line-height:1.35;">' +
                            '<div class="fw-medium text-truncate" style="max-width:160px;" title="' + escHtml(lq.topic) + '">' + escHtml(lq.topic) + '</div>' +
                            '<div class="text-muted" style="font-size:0.75rem;">' +
                                '<span class="badge bg-light text-dark border" style="font-size:0.7rem;">' + escHtml(modeLabel) + '</span> ' +
                                '<span class="' + pctClass + ' fw-semibold">' + lq.score + '/' + lq.total + ' (' + lq.pct + '%)</span>' +
                            '</div>' +
                        '</div>';
                }

                tbody.append(
                    '<tr data-loaded="1">' +
                    '<td>' +
                        '<div class="d-flex align-items-center">' +
                            '<div class="me-2">' +
                                '<div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:0.8rem;">' +
                                    escHtml(initial) +
                                '</div>' +
                            '</div>' +
                            '<div>' +
                                '<div class="fw-medium"><a href="' + escHtml(profileUrl) + '" class="text-decoration-none">' + safeName + '</a></div>' +
                                '<small class="text-muted">' + safeEmail + '</small>' +
                            '</div>' +
                        '</div>' +
                    '</td>' +
                    '<td>' + roleBadge + '</td>' +
                    '<td><span class="level-badge"><i class="fas fa-star" style="font-size:0.65rem;"></i> ' + parseInt(u.level) + '</span></td>' +
                    '<td>' +
                        '<div>' + lastActiveText + '</div>' +
                    '</td>' +
                    '<td>' + quizCell + '</td>' +
                    '<td><span class="activity-status ' + escHtml(u.status) + '"><span class="status-dot"></span>' + escHtml(statusLabel) + '</span></td>' +
                    '<td>' +
                        '<div class="d-flex gap-1 flex-wrap">' +
                            '<a href="' + escHtml(profileUrl) + '" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center" style="width:32px;height:32px;padding:0;" title="Open full student profile" aria-label="Open full student profile">' +
                                '<i class="fas fa-user"></i>' +
                            '</a>' +
                            '<button type="button" class="btn btn-sm btn-outline-danger last-active-delete-btn d-inline-flex align-items-center justify-content-center" style="width:32px;height:32px;padding:0;" data-user-id="' + escHtml(String(u.id)) + '" data-user-name="' + safeName + '" data-delete-url="' + escHtml(deleteUrl) + '" title="Delete this account" aria-label="Delete this account">' +
                                '<i class="fas fa-trash-alt"></i>' +
                            '</button>' +
                        '</div>' +
                    '</td>' +
                    '</tr>'
                );
            });

            // Pagination controls
            var pag = $('#lastActivePagination');
            pag.css('display', 'flex');
            $('#lastActiveInfo').text('Showing ' + data.users.length + ' of ' + data.total + ' users');
            $('#lastActivePageDisplay').text('Page ' + data.current_page + ' / ' + data.last_page);
            $('#lastActivePrev').prop('disabled', data.current_page <= 1);
            $('#lastActiveNext').prop('disabled', data.current_page >= data.last_page);
        }

        // Event handlers
        $('#refreshLastActive').on('click', function() {
            var btn = $(this);
            btn.find('i').addClass('fa-spin');
            loadLastActive(laState.page, laState.search);
            setTimeout(function() { btn.find('i').removeClass('fa-spin'); }, 1000);
        });

        $('#lastActiveSearch').on('input', function() {
            var val = $(this).val();
            clearTimeout(laState.debounce);
            laState.debounce = setTimeout(function() {
                loadLastActive(1, val);
            }, 350);
        });

        $('#lastActivePrev').on('click', function() {
            if (laState.page > 1) loadLastActive(laState.page - 1, laState.search);
        });

        $('#lastActiveNext').on('click', function() {
            loadLastActive(laState.page + 1, laState.search);
        });

        $('#lastActiveBody').on('click', '.last-active-delete-btn', function() {
            var $button = $(this);
            var userId = $button.data('user-id');
            var userName = $button.data('user-name') || 'this user';
            var deleteUrl = $button.data('delete-url');

            if (!deleteUrl) {
                showNotification('error', 'Delete Failed', 'Missing delete endpoint for this user.');
                return;
            }

            var confirmed = window.confirm('Delete ' + userName + ' and all related data? This cannot be undone.');
            if (!confirmed) {
                return;
            }

            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Deleting');

            $.ajax({
                url: deleteUrl,
                method: 'DELETE',
                data: { _token: MANAGE_ACCESS_CONFIG.csrfToken },
                headers: {
                    'X-CSRF-TOKEN': MANAGE_ACCESS_CONFIG.csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(resp) {
                    var remainingRows = $('#lastActiveBody tr[data-loaded="1"]').length;
                    var nextPage = remainingRows === 1 && laState.page > 1 ? laState.page - 1 : laState.page;
                    showNotification('success', 'User Deleted', resp.message || 'The account was deleted successfully.');
                    loadLastActive(nextPage, laState.search);
                },
                error: function(xhr) {
                    var message = xhr.responseJSON?.message || 'Failed to delete the user account.';
                    showNotification('error', 'Delete Failed', message);
                    $button.prop('disabled', false).html('<i class="fas fa-trash-alt me-1"></i>Delete');
                }
            });
        });

        // Auto-load on page ready (only if User Access tab is visible)
        var accessPane = document.getElementById('access');
        if (accessPane && accessPane.classList.contains('show')) {
            loadLastActive(1);
        }

        // Load when tab is shown
        var accessTab = document.getElementById('access-tab');
        if (accessTab) {
            accessTab.addEventListener('shown.bs.tab', function() {
                if (!$('#lastActiveBody tr[data-loaded]').length) {
                    loadLastActive(1);
                }
            });
        }
    })();

    // ── Real-time System Stats ────────────────────────────────────────
    (function initSystemStats() {
        var request = null;

        function loadSystemStats() {
            if (document.hidden || request) return;
            request = $.ajax({
                timeout: 12000,
                url: '/manage-access/system-stats',
                method: 'GET',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(d) {
                    if (!d.success) return;
                    // DB
                    var dbIcon = $('#sysDbIcon');
                    var dbText = $('#sysDbStatus');
                    if (d.db_online) {
                        dbIcon.css('color', '#198754');
                        dbText.text('Online').css('color', '');
                        $('#sysHealthBadge').html('<i class="fas fa-check-circle"></i>All Systems Operational')
                            .css({color: '', borderColor: ''});
                    } else {
                        dbIcon.css('color', '#dc3545');
                        dbText.text('Offline').css('color', '#dc3545');
                        $('#sysHealthBadge').html('<i class="fas fa-exclamation-triangle"></i> Issue Detected')
                            .css({ color: '#dc3545', borderColor: '#dc3545' });
                    }
                    // Load
                    $('#sysLoad').text(d.server_load !== null ? d.server_load : '—');
                    // Uptime
                    $('#sysUptime').text(d.uptime || '—');
                    // Memory
                    $('#sysMemory').text(d.memory_mb + 'MB');
                    // Response
                    $('#sysResponse').text(d.response_ms + 'ms');
                    // PHP
                    $('#sysPhp').text(d.php_version || '—');
                    // Live stat cards
                    $('#stat-active-users').text(d.active_now);
                    $('#stat-new-today').text(d.new_users_today);
                },
                error: function() { /* Keep the previous values until the next successful refresh. */ },
                complete: function() {
                    request = null;
                    $('#refreshSystemStats').find('i').removeClass('fa-spin');
                }
            });
        }

        // Initial load
        loadSystemStats();

        // Auto-refresh every 30 seconds
        setInterval(loadSystemStats, 30000);

        document.addEventListener('visibilitychange', loadSystemStats);

        // Manual refresh
        $('#refreshSystemStats').on('click', function() {
            var btn = $(this);
            btn.find('i').addClass('fa-spin');
            loadSystemStats();

        });
    })();

    // Payment History Tab Handler
    $('#payment-tab').on('click', function() {
        loadPayments();
    });
            
            if (MANAGE_ACCESS_CONFIG.activeTab === 'payment-history') { loadPayments(); }
            
            // Payment search handler
            $('#payment-search-btn').on('click', function() {
                const search = $('#payment-search').val();
                loadPayments(1, search);
            });
            
            // Enter key search
            $('#payment-search').on('keypress', function(e) {
                if (e.which === 13) {
                    const search = $(this).val();
                    loadPayments(1, search);
                }
            });

            // Check if table needs horizontal scrolling and show indicator
            function checkTableScroll() {
                const tableWrapper = $('.table-responsive')[1]; // Payment table is the second table-responsive
                const scrollIndicator = $('#scroll-indicator');

                if (tableWrapper) {
                    const hasHorizontalScroll = tableWrapper.scrollWidth > tableWrapper.clientWidth;

                    if (hasHorizontalScroll) {
                        scrollIndicator.show();
                    } else {
                        scrollIndicator.hide();
                    }
                }
            }

            // Check scroll on page load and window resize
            $(window).on('load resize', function() {
                // Small delay to ensure table is fully rendered
                setTimeout(checkTableScroll, 100);
            });

            $(document).on('payments:updated', checkTableScroll);

            // Initial check
            setTimeout(checkTableScroll, 500);

        });
        
        $(document).ready(function() {
            // Load settings when settings tab is clicked
            $('#settings-tab').on('click', function() {
                loadSettings();
            });

            if (MANAGE_ACCESS_CONFIG.activeTab === 'settings') { loadSettings(); }
            
            // Toggle fake user settings visibility
            $('#fakeLiveUsersToggle').on('change', function() {
                const isEnabled = $(this).is(':checked');
                $('#fakeUserSettings').slideToggle(300);
                
                if (!isEnabled) {
                    // Save disabled state immediately
                    saveSetting('fake_live_users_enabled', 'false', 'boolean');
                }
            });
            
            // Toggle fake students settings visibility
            $('#fakeStudentsToggle').on('change', function() {
                const isEnabled = $(this).is(':checked');
                $('#fakeStudentsSettings').slideToggle(300);
                
                if (!isEnabled) {
                    saveSetting('fake_students_enabled', 'false', 'boolean');
                }
            });
            
            // Save settings
            $('#saveFakeUserSettings').on('click', function() {
                const enabled = $('#fakeLiveUsersToggle').is(':checked');
                const min = parseInt($('#fakeUsersMin').val());
                const max = parseInt($('#fakeUsersMax').val());
                
                if (min >= max) {
                    showNotification('error', 'Invalid Range', 'Minimum must be less than maximum');
                    return;
                }
                
                // Save all settings
                Promise.all([
                    saveSetting('fake_live_users_enabled', enabled ? 'true' : 'false', 'boolean'),
                    saveSetting('fake_live_users_min', min.toString(), 'integer'),
                    saveSetting('fake_live_users_max', max.toString(), 'integer')
                ]).then(() => {
                    showNotification('success', 'Saved!', 'Settings updated successfully');
                    loadSettings();
                }).catch(() => {
                    showNotification('error', 'Error', 'Failed to save settings');
                });
            });
            
            // Test/Preview fake users
            $('#testFakeUsers').on('click', function() {
                $.ajax({
                    url: '/api/active-users',
                    method: 'GET',
                    success: function(response) {
                        $('#previewNumber').text(response.count);
                        $('#previewCount').slideDown();
                        
                        setTimeout(() => {
                            $('#previewCount').slideUp();
                        }, 5000);
                    }
                });
            });
        });
        
        // Adjust range with +/- buttons
        function adjustRange(type, delta) {
            const input = $('#fakeUsers' + (type === 'min' ? 'Min' : 'Max'));
            const currentValue = parseInt(input.val()) || 0;
            const newValue = Math.max(1, Math.min(999, currentValue + delta));
            input.val(newValue);
        }
        
        // Adjust student count with +/- buttons
        function adjustStudentCount(delta) {
            const input = $('#fakeStudentsCount');
            const currentValue = parseInt(input.val()) || 0;
            const newValue = Math.max(0, Math.min(99999, currentValue + delta));
            input.val(newValue);
        }
        
        // Save fake students settings
        $('#saveFakeStudentsSettings').on('click', function() {
            const enabled = $('#fakeStudentsToggle').is(':checked');
            const count = parseInt($('#fakeStudentsCount').val());
            
            Promise.all([
                saveSetting('fake_students_enabled', enabled ? 'true' : 'false', 'boolean'),
                saveSetting('fake_students_count', count.toString(), 'integer')
            ]).then(() => {
                showNotification('success', 'Saved!', 'Student count updated successfully');
                loadSettings();
            }).catch(() => {
                showNotification('error', 'Error', 'Failed to save student count');
            });
        });
        
        function loadSettings() {
            // Load fake users enabled
            $.get('/admin/settings/fake_live_users_enabled', function(response) {
                const enabled = response.value === true || response.value === 'true';
                $('#fakeLiveUsersToggle').prop('checked', enabled);
                if (enabled) {
                    $('#fakeUserSettings').show();
                }
                $('#currentMode').text(enabled ? 'Fake Mode' : 'Real Mode')
                    .removeClass('bg-secondary bg-success bg-warning')
                    .addClass(enabled ? 'bg-warning' : 'bg-success');
            });
            
            // Load min/max values
            $.get('/admin/settings/fake_live_users_min', function(response) {
                $('#fakeUsersMin').val(response.value || 87);
            });
            
            $.get('/admin/settings/fake_live_users_max', function(response) {
                $('#fakeUsersMax').val(response.value || 143);
            });
            
            // Load current displayed count
            $.get('/api/active-users', function(response) {
                $('#displayedCount').text(response.count);
            });
            
            // Load fake students enabled
            $.get('/admin/settings/fake_students_enabled', function(response) {
                const enabled = response.value === true || response.value === 'true';
                $('#fakeStudentsToggle').prop('checked', enabled);
                if (enabled) {
                    $('#fakeStudentsSettings').show();
                }
            });
            
            // Load student count
            $.get('/admin/settings/fake_students_count', function(response) {
                $('#fakeStudentsCount').val(response.value || 2847);
            });
        }
        
        $('#courseSurveyRequiredToggle').on('change', async function () {
            const toggle = this;
            const required = toggle.checked;
            const status = document.getElementById('courseSurveyStatus');
            toggle.disabled = true;
            status.textContent = 'Saving…';
            try {
                const response = await saveSetting('free_course_survey_required', required ? '1' : '0', 'boolean');
                if (!response.success) throw new Error('Setting could not be saved.');
                status.textContent = required
                    ? 'Saved. Students must complete the survey before starting free courses or previews.'
                    : 'Saved. Students can start free courses and previews without a survey.';
            } catch (_error) {
                toggle.checked = !required;
                status.textContent = 'Could not save. Please try again.';
            } finally {
                toggle.disabled = false;
            }
        });

        $('#hideEmptyTopicsToggle').on('change', async function () {
            const toggle = this;
            const enabled = toggle.checked;
            const status = document.getElementById('emptyTopicVisibilityStatus');
            toggle.disabled = true;
            status.textContent = 'Saving…';
            try {
                const response = await saveSetting('hide_empty_topics', enabled ? '1' : '0', 'boolean');
                if (!response.success) throw new Error('Setting could not be saved.');
                status.textContent = enabled ? 'Saved. Empty topics are hidden.' : 'Saved. All topics are visible.';
            } catch (_error) {
                toggle.checked = !enabled;
                status.textContent = 'Could not save. Please try again.';
            } finally {
                toggle.disabled = false;
            }
        });

        function saveSetting(key, value, type) {
            return $.ajax({
                url: '/admin/settings/update',
                method: 'POST',
                data: {
                    key: key,
                    value: value,
                    type: type,
                    _token: MANAGE_ACCESS_CONFIG.csrfToken
                }
            });
        }
        
        function applyPreset(min, max) {
            $('#fakeUsersMin').val(min);
            $('#fakeUsersMax').val(max);
            showNotification('success', 'Preset Applied', `Range set to ${min}-${max}. Click Save to apply.`);
        }
        
        // Adjust course fake count
        function adjustCourseCount(courseId, delta) {
            const input = $(`.course-fake-count[data-course-id="${courseId}"]`);
            const currentValue = parseInt(input.val()) || 0;
            const newValue = Math.max(0, Math.min(9999, currentValue + delta));
            input.val(newValue);
        }
        
        // Save individual course fake settings
        $('.save-course-fake').on('click', function() {
            const button = $(this);
            const courseId = $(this).data('course-id');
            const enabled = $(`.course-fake-toggle[data-course-id="${courseId}"]`).is(':checked');
            const input = document.querySelector(`.course-fake-count[data-course-id="${courseId}"]`);
            if (!input.reportValidity()) return;
            const count = Number(input.value);
            button.prop('disabled', true);
            
            $.ajax({
                url: '/admin/courses/' + courseId + '/fake-enrolled',
                method: 'POST',
                data: {
                    fake_enrolled_enabled: enabled ? 1 : 0,
                    fake_enrolled_count: count,
                    _token: MANAGE_ACCESS_CONFIG.csrfToken
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('success', 'Saved!', 'Course enrollment display updated');
                    } else {
                        showNotification('error', 'Error', response.message || 'Failed to save');
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'Failed to save course settings';
                    console.error('Save error:', xhr.responseJSON);
                    showNotification('error', 'Error', errorMsg);
                },
                complete: function() {
                    button.prop('disabled', false);
                }
            });
        });
        
        // Save all courses fake settings
        $('#saveAllCourseFakeSettings').on('click', function() {
            const inputs = Array.from(document.querySelectorAll('.course-fake-count'));
            if (inputs.some(input => !input.reportValidity())) return;
            const button = $(this);
            const coursesData = [];
            
            $('.course-fake-toggle').each(function() {
                const courseId = $(this).data('course-id');
                const enabled = $(this).is(':checked');
                const count = Number($(`.course-fake-count[data-course-id="${courseId}"]`).val());
                
                coursesData.push({
                    id: courseId,
                    fake_enrolled_enabled: enabled ? 1 : 0,
                    fake_enrolled_count: count
                });
            });
            
            button.prop('disabled', true);
            $.ajax({
                url: '/admin/courses/fake-enrolled/bulk',
                method: 'POST',
                data: {
                    courses: coursesData,
                    _token: MANAGE_ACCESS_CONFIG.csrfToken
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('success', 'Saved!', 'All course settings updated successfully');
                    } else {
                        showNotification('error', 'Error', response.message || 'Failed to save');
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'Failed to save all course settings';
                    console.error('Bulk save error:', xhr.responseJSON);
                    showNotification('error', 'Error', errorMsg);
                },
                complete: function() {
                    button.prop('disabled', false);
                }
            });
        });
        
        // Apply bulk count to all courses
        function applyBulkCourseCount() {
            const count = prompt('Enter the displayed student total for all courses (0–9999):', '250');
            if (count === null) return;
            if (!count.trim() || !Number.isInteger(Number(count)) || Number(count) < 0 || Number(count) > 9999) {
                showNotification('error', 'Invalid count', 'Enter a whole number between 0 and 9999.');
                return;
            }
            $('.course-fake-count').val(Number(count));
            $('.course-fake-toggle').prop('checked', true);
            showNotification('success', 'Applied!', `Set all courses to ${count} displayed students. Click "Save All Courses" to apply.`);
        }

        let previewTopicsRequest = null;

        $(document).ready(function() {

            $('#preview-topics-tab').on('click', function() {
                const courseId = $('#preview_course_id').val();
                if (courseId) {
                    loadPreviewTopics(courseId);
                }
            });

            if (MANAGE_ACCESS_CONFIG.activeTab === 'preview-topics') {
                const courseId = $('#preview_course_id').val();
                if (courseId) {
                    loadPreviewTopics(courseId);
                }
            }

            $('#preview_course_id').on('change', function() {
                const courseId = $(this).val();
                if (courseId) {
                    loadPreviewTopics(courseId);
                } else {
                    resetPreviewTopics('Pick a course to manage preview topics.');
                }
            });

            $('#previewSelectNone').on('click', function() {
                $('.preview-topic-checkbox').prop('checked', false);
            });

            $('#savePreviewTopics').on('click', function() {
                savePreviewTopics();
            });

            $('#video-tab').on('click', function() {
                loadVideos();
            });

            if (MANAGE_ACCESS_CONFIG.activeTab === 'video-management') { loadVideos(); }

            $('#video_course_id').on('change', function() {
                const courseId = $(this).val();
                const topicSelect = $('#video_topic_id');

                if (courseId) {
                    $.ajax({
                        url: `${MANAGE_ACCESS_ROUTES.courseTopicsBase || '/api/courses'}/${courseId}/topics`,
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: function(response) {
                            const topics = Array.isArray(response) ? response : (response.topics || []);
                            topicSelect.empty().append('<option value="">Select topic...</option>');
                            topics.forEach(topic => {
                                topicSelect.append(`<option value="${topic.id}">${topic.name}</option>`);
                            });
                            topicSelect.prop('disabled', false);
                        },
                        error: function() {
                            showNotification('error', 'Load Failed', 'Failed to load topics.');
                        }
                    });
                } else {
                    topicSelect.empty().append('<option value="">First select a course...</option>').prop('disabled', true);
                }
            });

            $('#addVideoForm').on('submit', function(e) {
                e.preventDefault();

                const formData = {
                    topic_id: $('#video_topic_id').val(),
                    youtube_url: $('#youtube_url').val(),
                    title: $('#video_title').val(),
                    description: $('#video_description').val(),
                    _token: MANAGE_ACCESS_CONFIG.csrfToken
                };

                $.ajax({
                    url: MANAGE_ACCESS_ROUTES.adminVideosStore || '/admin/videos',
                    method: 'POST',
                    data: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification('success', 'Video Added', 'Video added successfully!');
                            $('#addVideoForm')[0].reset();
                            $('#video_topic_id').prop('disabled', true).empty().append('<option value="">First select a course...</option>');
                            loadVideos();
                        } else {
                            showNotification('error', 'Add Failed', response.message || 'Failed to add video.');
                        }
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON?.message || xhr.responseJSON?.errors?.youtube_url?.[0] || xhr.responseJSON?.errors?.title?.[0] || xhr.responseJSON?.errors?.topic_id?.[0] || 'Failed to add video.';
                        showNotification('error', 'Add Failed', msg);
                    }
                });
            });
        });

        function resetPreviewTopics(message) {
            $('#previewTopicsList').hide().empty();
            $('#previewTopicsSummary').hide().empty();
            $('#previewTopicsEmpty').text(message).show();
            $('#savePreviewTopics, #previewSelectNone').prop('disabled', true);
        }

        function loadPreviewTopics(courseId) {
            resetPreviewTopics('Loading topics...');

            if (previewTopicsRequest) {
                previewTopicsRequest.abort();
            }

            previewTopicsRequest = $.ajax({
                url: `${MANAGE_ACCESS_ROUTES.courseTopicsBase || '/api/courses'}/${courseId}/topics`,
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    const topics = Array.isArray(response) ? response : (response.topics || []);
                    renderPreviewTopics(topics, response.meta || null);
                },
                error: function(xhr, status) {
                    if (status === 'abort') {
                        return;
                    }

                    resetPreviewTopics('Failed to load topics.');
                    showNotification('error', 'Load Failed', xhr.responseJSON?.message || 'Failed to load preview topics.');
                },
                complete: function() {
                    previewTopicsRequest = null;
                }
            });
        }

        function renderPreviewTopics(topics, meta) {
            const list = $('#previewTopicsList');
            const summary = $('#previewTopicsSummary');
            list.empty();
            summary.hide().empty();

            if (!topics.length) {
                resetPreviewTopics('No topics found for this course.');
                return;
            }

            const totalTopics = meta?.total_topics ?? topics.length;
            const openCount = meta?.open_to_preview_count ?? topics.filter(topic => topic.is_open_to_preview || topic.is_preview_enabled).length;
            const closedCount = meta?.not_open_to_preview_count ?? Math.max(totalTopics - openCount, 0);
            const defaultNote = meta?.using_default_preview
                ? ' No custom selection exists yet, so the app is using the default first topics preview rule.'
                : '';

            summary
                .html(`<strong>${openCount}</strong> open to preview, <strong>${closedCount}</strong> not open for a standard preview user.${defaultNote}`)
                .show();

            topics.forEach(topic => {
                const caseType = topic.case_type || 'quiz';
                const badgeClass = caseType === 'practical' ? 'bg-success' : (caseType === 'cases' ? 'bg-info' : 'bg-secondary');
                const isOpenToPreview = Boolean(topic.is_open_to_preview ?? topic.is_preview_enabled);
                const checked = isOpenToPreview ? 'checked' : '';
                const locked = topic.is_locked ? '<span class="badge bg-warning text-dark ms-2">Locked</span>' : '';
                const stateBadge = isOpenToPreview
                    ? '<span class="badge bg-success ms-2">Open to preview</span>'
                    : '<span class="badge bg-light text-dark border ms-2">Not in preview</span>';
                const sourceBadge = topic.preview_source === 'default_limit'
                    ? '<span class="badge bg-info text-dark ms-2">Default</span>'
                    : '';

                list.append(`
                    <label class="preview-topic-row d-flex align-items-center gap-3 border rounded p-3 mb-2" for="preview-topic-${topic.id}">
                        <input class="form-check-input preview-topic-checkbox m-0" type="checkbox" value="${topic.id}" id="preview-topic-${topic.id}" ${checked}>
                        <span class="flex-grow-1">
                            <span class="fw-semibold">${escapeManageAccessHtml(topic.name || 'Untitled topic')}</span>
                            <span class="badge ${badgeClass} ms-2">${escapeManageAccessHtml(caseType)}</span>
                            ${stateBadge}
                            ${sourceBadge}
                            ${locked}
                        </span>
                    </label>
                `);
            });

            $('#previewTopicsEmpty').hide();
            list.show();
            $('#savePreviewTopics, #previewSelectNone').prop('disabled', false);
        }

        function savePreviewTopics() {
            const courseId = $('#preview_course_id').val();
            if (!courseId) {
                showNotification('warning', 'Choose Course', 'Select a course first.');
                return;
            }

            const topicIds = $('.preview-topic-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            $('#savePreviewTopics').prop('disabled', true);

            $.ajax({
                url: `${MANAGE_ACCESS_ROUTES.previewTopicsBase || '/admin/courses'}/${courseId}/preview-topics`,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    topic_ids: topicIds,
                    _token: MANAGE_ACCESS_CONFIG.csrfToken
                }),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': MANAGE_ACCESS_CONFIG.csrfToken
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('success', 'Saved', response.message || 'Preview topics updated.');
                        loadPreviewTopics(courseId);
                    } else {
                        showNotification('error', 'Save Failed', response.message || 'Failed to save preview topics.');
                    }
                },
                error: function(xhr) {
                    showNotification('error', 'Save Failed', xhr.responseJSON?.message || 'Failed to save preview topics.');
                },
                complete: function() {
                    $('#savePreviewTopics').prop('disabled', false);
                }
            });
        }

        function loadVideos() {
            const tbody = $('#videos-table-body');
            tbody.html(`
                <tr>
                    <td colspan="4" class="text-center py-3">
                        <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                        <div>Loading videos...</div>
                    </td>
                </tr>
            `);

            $.ajax({
                url: MANAGE_ACCESS_ROUTES.adminVideosIndex || '/admin/videos',
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': MANAGE_ACCESS_CONFIG.csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    tbody.empty();

                    if (response.success && response.videos && response.videos.length > 0) {
                        $('#video-count').html(`<i class="fas fa-video"></i> ${response.videos.length}`);

                        response.videos.forEach(video => {
                            tbody.append(`
                                <tr>
                                    <td>
                                        <div class="fw-medium">${video.topic_name || 'N/A'}</div>
                                        <small class="text-muted">${video.course_name || 'N/A'}</small>
                                    </td>
                                    <td>
                                        <div class="fw-medium">${video.title || 'Untitled'}</div>
                                        <small class="text-muted">
                                            <i class="fab fa-youtube text-danger"></i>
                                            ${video.youtube_video_id ? video.youtube_video_id.substring(0, 8) + '...' : 'N/A'}
                                        </small>
                                    </td>
                                    <td><span class="badge bg-success">Active</span></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-danger btn-sm" onclick="deleteVideo(${video.id})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `);
                        });
                    } else {
                        $('#video-count').html('<i class="fas fa-video"></i> 0');
                        tbody.append(`
                            <tr>
                                <td colspan="4" class="text-center py-3 text-muted">
                                    <i class="fas fa-video fa-2x mb-2"></i>
                                    <div>No videos found</div>
                                    <small>Add your first video using the form</small>
                                </td>
                            </tr>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    tbody.html(`
                        <tr>
                            <td colspan="4" class="text-center py-3 text-danger">
                                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                                <div>Failed to load videos</div>
                                <small>${xhr.responseJSON?.message || error || 'Please try again'}</small>
                            </td>
                        </tr>
                    `);
                    showNotification('error', 'Load Failed', 'Failed to load videos: ' + (xhr.responseJSON?.message || error));
                }
            });
        }

        function deleteVideo(videoId) {
            if (confirm('Are you sure you want to delete this video?')) {
                $.ajax({
                    url: `${MANAGE_ACCESS_ROUTES.adminVideosDestroyBase || '/admin/videos'}/${videoId}`,
                    method: 'DELETE',
                    data: { _token: MANAGE_ACCESS_CONFIG.csrfToken },
                    success: function(response) {
                        if (response.success) {
                            showNotification('success', 'Video Deleted', 'Video deleted successfully!');
                            loadVideos();
                        } else {
                            showNotification('error', 'Delete Failed', 'Failed to delete video.');
                        }
                    },
                    error: function() {
                        showNotification('error', 'Delete Failed', 'Failed to delete video.');
                    }
                });
            }
        }

/* ── Survey Admin: Written Answers + Student Responses ── */
window.surveyAdmin = (function () {
    'use strict';

    var _searchTimer = null;
    var _studentsLoaded = false;

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function fetchJson(url) {
        return fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            });
    }

    function buildPaginationButtons(currentPage, lastPage, onClickFn) {
        var html = '';
        var start = Math.max(1, currentPage - 2);
        var end = Math.min(lastPage, currentPage + 2);

        if (currentPage > 1) {
            html += '<button class="btn btn-sm btn-outline-secondary" onclick="' + onClickFn + '(' + (currentPage - 1) + ')">‹</button>';
        }
        for (var p = start; p <= end; p++) {
            html += '<button class="btn btn-sm ' + (p === currentPage ? 'btn-primary' : 'btn-outline-secondary') + '" onclick="' + onClickFn + '(' + p + ')">' + p + '</button>';
        }
        if (currentPage < lastPage) {
            html += '<button class="btn btn-sm btn-outline-secondary" onclick="' + onClickFn + '(' + (currentPage + 1) + ')">›</button>';
        }
        return html;
    }

    /* ── Written Answers ── */
    function loadWrittenAnswers(page) {
        var panel = document.getElementById('written-answers-panel');
        var list = document.getElementById('written-answers-list');
        var pag = document.getElementById('written-answers-pagination');
        var loading = document.getElementById('written-answers-loading');
        if (!panel || !list) return;

        panel.style.display = '';
        if (loading) loading.style.display = '';
        list.innerHTML = '';
        if (pag) pag.innerHTML = '';

        fetchJson('/manage-access/survey/written-answers?page=' + page + '&per_page=15')
            .then(function (data) {
                if (loading) loading.style.display = 'none';
                var items = data.items || [];

                if (items.length === 0) {
                    list.innerHTML = '<div class="text-center py-3 text-muted small">No written answers found.</div>';
                    return;
                }

                var html = '';
                items.forEach(function (item) {
                    html += '<div class="survey-written-item">';
                    html += '<div class="written-meta">' + escapeHtml(item.user_name) + ' · ' + escapeHtml(item.user_email) + ' · ' + escapeHtml(item.submitted_at || '') + '</div>';
                    html += '<div class="written-text">' + escapeHtml(item.text) + '</div>';
                    html += '</div>';
                });
                list.innerHTML = html;

                if (data.last_page > 1 && pag) {
                    pag.innerHTML = buildPaginationButtons(data.page, data.last_page, 'surveyAdmin.loadWrittenAnswers');
                }
            })
            .catch(function () {
                if (loading) loading.style.display = 'none';
                list.innerHTML = '<div class="text-center py-3 text-danger small">Failed to load written answers.</div>';
            });
    }

    function hideWrittenAnswers() {
        var panel = document.getElementById('written-answers-panel');
        if (panel) panel.style.display = 'none';
    }

    /* ── Student Responses ── */
    function loadStudentResponses(page, search) {
        var wrap = document.getElementById('student-responses-table-wrap');
        var loading = document.getElementById('student-responses-loading');
        var pagWrap = document.getElementById('student-responses-pagination');
        var info = document.getElementById('student-responses-info');
        var pages = document.getElementById('student-responses-pages');
        if (!wrap) return;

        if (loading) loading.style.display = '';
        wrap.innerHTML = '';
        if (pagWrap) pagWrap.style.display = 'none';

        var url = '/manage-access/survey/student-responses?page=' + page + '&per_page=20';
        if (search && search.trim()) {
            url += '&search=' + encodeURIComponent(search.trim());
        }

        fetchJson(url)
            .then(function (data) {
                if (loading) loading.style.display = 'none';
                var items = data.items || [];

                if (items.length === 0) {
                    wrap.innerHTML = '<div class="text-center py-4 text-muted small">No survey responses found.</div>';
                    return;
                }

                var html = '<div class="table-responsive"><table class="survey-student-table">';
                html += '<thead><tr><th>Student</th><th>Email</th><th>Submitted</th><th>Year</th><th>Device</th><th style="width:80px"></th></tr></thead>';
                html += '<tbody>';

                items.forEach(function (item) {
                    var year = (item.answers && item.answers.q10) ? item.answers.q10.label : '—';
                    var device = (item.answers && item.answers.q6) ? item.answers.q6.label : '—';

                    html += '<tr>';
                    html += '<td class="student-name-cell" onclick="surveyAdmin.showStudentDetail(' + item.user_id + ')">' + escapeHtml(item.user_name) + '</td>';
                    html += '<td class="small text-muted">' + escapeHtml(item.user_email) + '</td>';
                    html += '<td class="small text-muted">' + escapeHtml(item.submitted_at || '') + '</td>';
                    html += '<td>' + escapeHtml(year) + '</td>';
                    html += '<td>' + escapeHtml(device) + '</td>';
                    html += '<td><button class="btn btn-sm btn-outline-primary" onclick="surveyAdmin.showStudentDetail(' + item.user_id + ')"><i class="fas fa-eye"></i></button></td>';
                    html += '</tr>';
                });

                html += '</tbody></table></div>';
                wrap.innerHTML = html;

                if (pagWrap && data.last_page > 0) {
                    pagWrap.style.display = 'flex';
                    if (info) {
                        var from = ((data.page - 1) * data.per_page) + 1;
                        var to = Math.min(data.page * data.per_page, data.total);
                        info.textContent = 'Showing ' + from + '–' + to + ' of ' + data.total + ' responses';
                    }
                    if (pages && data.last_page > 1) {
                        var searchVal = document.getElementById('survey-student-search') ? document.getElementById('survey-student-search').value : '';
                        pages.innerHTML = buildPaginationButtons(data.page, data.last_page, 'surveyAdmin.loadStudentResponsesWithSearch');
                    }
                }
            })
            .catch(function () {
                if (loading) loading.style.display = 'none';
                wrap.innerHTML = '<div class="text-center py-4 text-danger small">Failed to load student responses.</div>';
            });
    }

    function loadStudentResponsesWithSearch(page) {
        var searchInput = document.getElementById('survey-student-search');
        var search = searchInput ? searchInput.value : '';
        loadStudentResponses(page, search);
    }

    /* ── Student Detail Modal ── */
    function showStudentDetail(userId) {
        var nameEl = document.getElementById('studentDetailName');
        var metaEl = document.getElementById('studentDetailMeta');
        var bodyEl = document.getElementById('studentDetailBody');
        if (!bodyEl) return;

        bodyEl.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
        if (nameEl) nameEl.textContent = 'Loading…';
        if (metaEl) metaEl.textContent = '';

        var modal = new bootstrap.Modal(document.getElementById('studentDetailModal'));
        modal.show();

        fetchJson('/manage-access/survey/student/' + userId)
            .then(function (data) {
                if (nameEl) nameEl.textContent = data.user_name || 'Student';
                if (metaEl) metaEl.textContent = (data.user_email || '') + ' · Submitted: ' + (data.submitted_at || '—');

                var html = '';
                (data.answers || []).forEach(function (a) {
                    html += '<div class="student-detail-answer">';
                    html += '<div class="detail-question-id">' + escapeHtml(a.question_id) + '</div>';
                    html += '<div class="detail-prompt">' + escapeHtml(a.prompt) + '</div>';

                    if (!a.answered) {
                        html += '<div class="detail-value not-answered">Not answered</div>';
                    } else if (a.type === 'textarea') {
                        html += '<div class="detail-value is-textarea">' + escapeHtml(a.label || a.value) + '</div>';
                    } else {
                        html += '<div class="detail-value">' + escapeHtml(a.label || a.value) + '</div>';
                    }

                    html += '</div>';
                });

                bodyEl.innerHTML = html || '<div class="text-center py-4 text-muted">No answers found.</div>';
            })
            .catch(function () {
                bodyEl.innerHTML = '<div class="text-center py-4 text-danger">Failed to load student details.</div>';
            });
    }

    /* ── Search debounce ── */
    document.addEventListener('DOMContentLoaded', function () {
        var searchInput = document.getElementById('survey-student-search');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(_searchTimer);
                _searchTimer = setTimeout(function () {
                    loadStudentResponses(1, searchInput.value);
                }, 400);
            });
        }
    });

    return {
        loadWrittenAnswers: loadWrittenAnswers,
        hideWrittenAnswers: hideWrittenAnswers,
        loadStudentResponses: loadStudentResponses,
        loadStudentResponsesWithSearch: loadStudentResponsesWithSearch,
        showStudentDetail: showStudentDetail,
        _studentsLoaded: false
    };
})();

        document.addEventListener('DOMContentLoaded', function () {
            const surveyPane = document.getElementById('survey-results');

            if (!surveyPane) {
                return;
            }

            const animateNumber = (element) => {
                if (!element || element.dataset.animated === 'true') {
                    return;
                }

                const target = Number(element.dataset.target || 0);
                if (Number.isNaN(target)) {
                    return;
                }

                element.dataset.animated = 'true';

                const hasPercentSuffix = element.textContent.trim().includes('%');
                const duration = 900;
                const start = performance.now();

                const update = (timestamp) => {
                    const progress = Math.min((timestamp - start) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const value = target * eased;
                    const numeric = Math.round(value).toString();
                    const formatted = `${numeric}${hasPercentSuffix ? '%' : ''}`;

                    element.textContent = formatted;

                    if (progress < 1) {
                        requestAnimationFrame(update);
                    }
                };

                requestAnimationFrame(update);
            };

            const revealSurveyCard = (element) => {
                if (!element || element.classList.contains('is-visible')) {
                    return;
                }

                element.classList.add('is-visible');
                element.querySelectorAll('.survey-animated-number').forEach(animateNumber);
            };

            surveyPane.querySelectorAll('.survey-donut').forEach((donut) => {
                const tooltip = donut.querySelector('.survey-donut-tooltip');
                const segments = donut.querySelectorAll('.survey-donut-segment');

                if (!tooltip || !segments.length) {
                    return;
                }

                segments.forEach((segment) => {
                    segment.addEventListener('mouseenter', () => {
                        segments.forEach((item) => item.classList.remove('is-active'));
                        segment.classList.add('is-active');
                        tooltip.innerHTML = `<strong>${segment.dataset.percent || ''}</strong><span>${segment.dataset.label || ''}</span>`;
                        tooltip.classList.add('is-visible');
                    });

                    segment.addEventListener('mouseleave', () => {
                        segment.classList.remove('is-active');
                        tooltip.classList.remove('is-visible');
                    });
                });
            });

            const surveyCards = surveyPane.querySelectorAll('.survey-question-card, .survey-insight-card, .survey-metric-card');

            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) {
                            return;
                        }

                        revealSurveyCard(entry.target);
                        observer.unobserve(entry.target);
                    });
                }, {
                    threshold: 0.22,
                    rootMargin: '0px 0px -40px 0px'
                });

                surveyCards.forEach((card) => observer.observe(card));
            } else {
                surveyCards.forEach(revealSurveyCard);
            }

            if (surveyPane.classList.contains('show')) {
                surveyCards.forEach(revealSurveyCard);
            }

            const surveyTab = document.getElementById('survey-results-tab');
            if (surveyTab) {
                surveyTab.addEventListener('shown.bs.tab', function () {
                    surveyCards.forEach(revealSurveyCard);
                    // Auto-load student responses when tab first shown
                    if (window.surveyAdmin && !window.surveyAdmin._studentsLoaded) {
                        window.surveyAdmin.loadStudentResponses(1);
                        window.surveyAdmin._studentsLoaded = true;
                    }
                });
            }

            // If survey tab is already active on page load, load students
            if (window.surveyAdmin && surveyPane.classList.contains('show') && !window.surveyAdmin._studentsLoaded) {
                window.surveyAdmin.loadStudentResponses(1);
                window.surveyAdmin._studentsLoaded = true;
            }
        });
