const MANAGE_ACCESS_CONFIG = window.MANAGE_ACCESS_CONFIG || {};

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
        el.innerHTML =
            '<div class="notification-icon"><i class="fas ' + ICONS[type] + '"></i></div>' +
            '<div class="notification-content">' +
                '<div class="notification-title">' + title + '</div>' +
                (message ? '<div class="notification-message">' + message + '</div>' : '') +
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
            
            // Initialize Select2 for user search - FIXED: Show all users without typing
            $('.user-select').select2({
                placeholder: 'Type to search for user...',
                allowClear: true,
                width: '100%',
                minimumInputLength: 0, // FIXED: Show all users immediately
                debug: false,
                ajax: {
                    url: '/manage-access/search-users',
                    type: 'GET',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        console.log('🔍 Select2 AJAX Data:', params);
                        return {
                            query: params.term || '' // FIXED: Send empty string if no search term
                        };
                    },
                    processResults: function (data, params) {
                        console.log('📊 Select2 Process Results:', data);
                        if (data.success && data.users) {
                            return {
                                results: data.users.map(function (user) {
                                    return {
                                        id: user.id,
                                        text: user.name + ' (' + user.email + ')',
                                        name: user.name,
                                        email: user.email,
                                        number: user.number,
                                        role: user.role
                                    };
                                })
                            };
                        } else {
                            console.warn('❌ No users found or API error:', data);
                            return {
                                results: []
                            };
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ Select2 AJAX Error:', {
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
                templateResult: function (user) {
                    console.log('🎨 Template Result:', user);
                    if (user.loading) {
                        return user.text;
                    }
                    if (!user.id) {
                        return user.text;
                    }

                    // Get role name
                    let roleName = 'Student';
                    if (user.role === 1) roleName = 'Admin';
                    else if (user.role === 2) roleName = 'Tutor';

                    // Create display with role badge
                    let display = '<div class="d-flex align-items-center justify-content-between w-100">';
                    display += '<div class="flex-grow-1">';
                    display += '<div class="fw-medium">' + user.name + '</div>';
                    display += '<small class="text-muted">' + user.email + '</small>';
                    if (user.number) {
                        display += '<br><small class="text-muted">Number: ' + user.number + '</small>';
                    }
                    display += '</div>';
                    display += '<span class="badge bg-secondary ms-2" style="font-size: 0.7rem;">' + roleName + '</span>';
                    display += '</div>';

                    return display;
                },
                templateSelection: function (user) {
                    console.log('✅ Template Selection:', user);
                    if (!user.id) {
                        return user.text;
                    }
                    // FIXED: Handle both AJAX results and pre-loaded options
                    const userName = user.name || user.text || 'Unknown';
                    const userEmail = user.email || '';
                    const userNumber = user.number || '';
                    
                    if (userEmail) {
                        return userNumber ? userName + ' (' + userEmail + ' • ' + userNumber + ')' : userName + ' (' + userEmail + ')';
                    }
                    return userName;
                }
            });

            // Add event listeners for debugging
            $('.user-select').on('select2:open', function() {
                console.log('🔓 Select2 opened');
            });

            $('.user-select').on('select2:close', function() {
                console.log('🔒 Select2 closed');
            });

            $('.user-select').on('select2:select', function(e) {
                console.log('✅ User selected:', e.params.data);
            });

            $('.user-select').on('select2:unselect', function(e) {
                console.log('❌ User unselected:', e.params.data);
            });
            
            // GRANT ACCESS BUTTON HANDLER
            $('#grantAccessBtn').on('click', function(e) {
                e.preventDefault();
                
                const userId = $('#user_id').val();
                const courseId = $('#course_id').val();
                
                if (!userId || !courseId) {
                    showNotification('warning', 'Missing Selection', 'Please select both a user and a course first.');
                    return;
                }
                
                var btn = $(this);
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');
                
                $.ajax({
                    url: '/manage-access/enroll',
                    method: 'POST',
                    data: {
                        user_id: userId,
                        course_id: courseId,
                        _token: MANAGE_ACCESS_CONFIG.csrfToken
                    },
                    success: function(response) {
                        showNotification('success', 'Access Granted!', response.message || 'User has been successfully enrolled in the course.');
                        refreshEnrollmentData();
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON?.message || 'Failed to grant access. Please try again.';
                        showNotification('error', 'Enrollment Failed', msg);
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('<i class="fas fa-user-plus me-2"></i>Grant Access');
                    }
                });
            });
            
            // REVOKE ACCESS BUTTON HANDLER
            $('#revokeAccessBtn').on('click', function(e) {
                e.preventDefault();
                
                const userId = $('#user_id').val();
                const courseId = $('#course_id').val();
                
                if (!userId || !courseId) {
                    showNotification('warning', 'Missing Selection', 'Please select both a user and a course first.');
                    return;
                }
                
                var btn = $(this);
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');
                
                $.ajax({
                    url: '/manage-access/revoke',
                    method: 'POST',
                    data: {
                        user_id: userId,
                        course_id: courseId,
                        _token: MANAGE_ACCESS_CONFIG.csrfToken
                    },
                    success: function(response) {
                        showNotification('warning', 'Access Revoked', response.message || 'User access has been successfully revoked.');
                        refreshEnrollmentData();
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON?.message || 'Failed to revoke access. Please try again.';
                        showNotification('error', 'Revoke Failed', msg);
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('<i class="fas fa-user-times me-2"></i>Revoke Access');
                    }
                });
            });
            

            
            // User selection handler
            $('#user_id').on('change', function() {
                try {
                    const userId = $(this).val();
                    const userOption = $(this).find('option:selected');

                    if (userId) {
                        const userName = userOption.text();
                        const userEmail = userOption.data('email');
                        const userNumber = userOption.data('number');
                        const userRole = userOption.data('role');

                        // Safe integer handling for role
                        const safeUserRole = (userRole && typeof userRole === 'number') ? userRole : 0;
                        const displayRole = safeUserRole === 1 ? 'ADMIN' : safeUserRole === 2 ? 'TUTOR' : 'STUDENT';

                        // Set user initial (first letter of name) - safe handling
                        const userInitial = (userName && userName.length > 0) ? userName.charAt(0).toUpperCase() : 'U';
                        $('#userInitial').text(userInitial);

                        // Update UI with safe fallbacks
                        $('#selectedUserName').text(userName || 'Unknown');
                        $('#selectedUserEmail').text(userEmail || 'No Email');
                        $('#selectedUserNumber').text(userNumber ? `Number: ${userNumber}` : '');
                        $('#selectedUserRole').removeClass().addClass('badge').addClass(safeUserRole === 1 ? 'bg-danger' : 'bg-secondary').text(displayRole);
                        $('#userInfoSection').show();

                        // Update course dropdown with enrollment status
                        updateCourseDropdown(userId);
                    } else {
                        $('#userInfoSection').hide();
                        $('#selectedUserNumber').text('');
                        // Reset course dropdown
                        resetCourseDropdown();
                    }
                } catch (error) {
                    console.error('Error in user selection handler:', error);
                    // Fallback behavior
                    $('#userInfoSection').hide();
                }
            });
            
            // Function to update course dropdown with enrollment status
            function updateCourseDropdown(userId) {
                $.ajax({
                    url: '/manage-access/user-enrollments',
                    method: 'GET',
                    data: { user_id: userId },
                    success: function(response) {
                        if (response.success) {
                            const courseSelect = $('#course_id');
                            courseSelect.empty();
                            courseSelect.append('<option value="">Choose course...</option>');
                            
                            response.courses.forEach(function(course) {
                                const isEnrolled = course.is_enrolled;
                                const statusBadge = isEnrolled ? 
                                    '<span class="badge bg-success">✓ Enrolled</span>' : 
                                    '<span class="badge bg-secondary">✗ Not Enrolled</span>';
                                
                                const priceText = course.formatted_price ? `(${course.formatted_price})` : '';
                                
                                courseSelect.append(`
                                    <option value="${course.id}" data-enrolled="${isEnrolled}" data-price="${course.price}">
                                        ${course.name} ${priceText} - ${statusBadge}
                                    </option>
                                `);
                            });
                        }
                    },
                    error: function(xhr) {
                        console.error('Failed to load user enrollments:', xhr.responseText);
                        resetCourseDropdown();
                    }
                });
            }
            
            // Function to reset course dropdown
            function resetCourseDropdown() {
                const courseSelect = $('#course_id');
                courseSelect.empty();
                courseSelect.append('<option value="">Choose course...</option>');

                // Populate course options from config
                if (MANAGE_ACCESS_CONFIG.courseOptions && MANAGE_ACCESS_CONFIG.courseOptions.length > 0) {
                    MANAGE_ACCESS_CONFIG.courseOptions.forEach(function(course) {
                        courseSelect.append('<option value="' + course.id + '" data-price="' + course.price + '">' + course.name + ' (' + course.formattedPrice + ')</option>');
                    });
                }
            }
            
            // Initialize Admin User Select2
            $('.admin-user-select').select2({
                placeholder: 'Type to search for user...',
                allowClear: true,
                width: '100%'
            });
            
            // Admin Management Variables
            let selectedAdminUserId = null;
            let selectedAdminIsAdmin = false;
            
            // Admin user selection handler
            $('#admin_user_id').on('change', function() {
                try {
                    const userId = $(this).val();
                    const userOption = $(this).find('option:selected');

                    console.log('Admin user selection:', {userId, userOption});

                    if (userId) {
                        const userName = userOption.data('name');
                        const userEmail = userOption.data('email');
                        const userNumber = userOption.data('number');
                        const userRole = userOption.data('role');

                        console.log('User data:', {userName, userEmail, userNumber, userRole});

                        selectedAdminUserId = userId;
                        selectedAdminIsAdmin = userRole === 1; // FIXED: Store boolean correctly

                        // FIXED: Proper role handling - userRole is integer (1=admin, 2=tutor, 3=student)
                        const displayRole = userRole === 1 ? 'ADMIN' : (userRole === 2 ? 'TUTOR' : 'STUDENT');

                        // Update UI
                        $('#selectedAdminUserName').text(userName || 'Unknown');
                        $('#selectedAdminUserEmail').text(userEmail || 'No Email');
                        $('#selectedAdminUserNumber').text(userNumber ? `Number: ${userNumber}` : '');
                        $('#selectedAdminUserStatus').removeClass().addClass('badge').addClass(userRole === 1 ? 'bg-danger' : 'bg-secondary').text(displayRole);
                        $('#selectedUserInfo').show();

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
                    }
                } catch (error) {
                    console.error('Error in admin user selection handler:', error);
                    // Fallback behavior
                    $('#selectedUserInfo').hide();
                    $('#promoteToAdminBtn').prop('disabled', true);
                    $('#removeAdminBtn').prop('disabled', true);
                }
            });
            
            // Promote to Admin button handler
            $('#promoteToAdminBtn').on('click', function(e) {
                e.preventDefault();
                
                if (!selectedAdminUserId || selectedAdminIsAdmin === true) {
                    showNotification('warning', 'Invalid Action', 'Please select a non-admin user to promote.');
                    return;
                }
                
                if (!confirm('Are you sure you want to promote this user to administrator?')) {
                    return;
                }
                
                var btn = $(this);
                btn.html('<i class="fas fa-spinner fa-spin me-2"></i>Promoting...').prop('disabled', true);
                
                $.ajax({
                    url: '/manage-access/toggle-admin',
                    method: 'POST',
                    data: {
                        user_id: selectedAdminUserId,
                        action: 'promote',
                        _token: MANAGE_ACCESS_CONFIG.csrfToken
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification('success', 'Admin Promoted!', response.message || 'User promoted to administrator successfully.');
                            // Update UI in place
                            selectedAdminIsAdmin = true;
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
                        if (!selectedAdminIsAdmin) {
                            btn.html('<i class="fas fa-user-shield me-2"></i>Promote to Admin').prop('disabled', false);
                        }
                    }
                });
            });
            
            // Remove Admin button handler
            $('#removeAdminBtn').on('click', function(e) {
                e.preventDefault();
                
                if (!selectedAdminUserId || selectedAdminIsAdmin !== true) {
                    showNotification('warning', 'Invalid Action', 'Please select an admin user to revoke privileges.');
                    return;
                }
                
                if (!confirm('Are you sure you want to revoke administrator privileges from this user?')) {
                    return;
                }
                
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
                        if (selectedAdminIsAdmin) {
                            btn.html('<i class="fas fa-user-times me-2"></i>Revoke Admin').prop('disabled', false);
                        }
                    }
                });
            });
            
            // Enrollment filter handler
            $('#enrollmentFilter').change(function() {
                const filter = $(this).val();
                const rows = $('#enrollmentsTable tbody tr');
                
                rows.each(function() {
                    const row = $(this);
                    const status = row.data('status');
                    
                    if (filter === 'all' || status === filter) {
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
            
            // Function to update enrollment table
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
                    enrollments.forEach(function(enrollment) {
                        const statusBadge = enrollment.is_enrolled ? 
                            '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Enrolled</span>' :
                            '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Revoked</span>';
                        
                        const date = new Date(enrollment.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                        const timeAgo = timeAgoLabel(enrollment.created_at);
                        
                        tbody.append(`
                            <tr data-status="${enrollment.is_enrolled ? 'enrolled' : 'revoked'}">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                ${enrollment.name ? enrollment.name.charAt(0).toUpperCase() : 'U'}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="fw-medium">${enrollment.name || 'Unknown'}</div>
                                            <small class="text-muted">${enrollment.email}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-primary">${enrollment.course_name}</span>
                                </td>
                                <td>
                                    <div>${date}</div>
                                    <small class="text-muted">${timeAgo}</small>
                                </td>
                                <td>${statusBadge}</td>
                            </tr>
                        `);
                    });
                }
            }
            
    // ── Last Active Users ──────────────────────────────────────────────
    (function initLastActiveUsers() {
        var laState = { page: 1, search: '', debounce: null };
        var STATUS_LABELS = {
            online:    'Online',
            recent:    'Recently',
            today:     'Today',
            this_week: 'This Week',
            inactive:  'Inactive',
            never:     'Never'
        };

        function loadLastActive(page, search) {
            laState.page = page || 1;
            laState.search = search || '';

            var tbody = $('#lastActiveBody');
            tbody.html(
                '<tr><td colspan="5" class="text-center py-4 text-muted">' +
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
                            '<tr><td colspan="5" class="text-center py-4 text-muted">' +
                            '<i class="fas fa-exclamation-triangle fa-2x mb-2"></i>' +
                            '<div>Failed to load data</div></td></tr>'
                        );
                        return;
                    }
                    renderLastActive(resp);
                },
                error: function() {
                    tbody.html(
                        '<tr><td colspan="5" class="text-center py-4 text-muted">' +
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
                    '<tr><td colspan="5" class="text-center py-4 text-muted">' +
                    '<i class="fas fa-users fa-2x mb-2"></i>' +
                    '<div>No users found</div></td></tr>'
                );
                $('#lastActivePagination').hide();
                return;
            }

            data.users.forEach(function(u) {
                var initial = (u.name || u.email || 'U').charAt(0).toUpperCase();
                var roleBadge = u.role === 1
                    ? '<span class="badge bg-danger">Admin</span>'
                    : u.role === 2
                        ? '<span class="badge bg-info">Tutor</span>'
                        : '<span class="badge bg-secondary">Student</span>';

                var statusLabel = STATUS_LABELS[u.status] || 'Unknown';
                var lastActiveText = u.last_active_human || 'Never';

                tbody.append(
                    '<tr>' +
                    '<td>' +
                        '<div class="d-flex align-items-center">' +
                            '<div class="me-2">' +
                                '<div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:0.8rem;">' +
                                    initial +
                                '</div>' +
                            '</div>' +
                            '<div>' +
                                '<div class="fw-medium">' + (u.name || 'Unknown') + '</div>' +
                                '<small class="text-muted">' + u.email + '</small>' +
                            '</div>' +
                        '</div>' +
                    '</td>' +
                    '<td>' + roleBadge + '</td>' +
                    '<td><span class="level-badge"><i class="fas fa-star" style="font-size:0.65rem;"></i> ' + u.level + '</span></td>' +
                    '<td>' +
                        '<div>' + lastActiveText + '</div>' +
                    '</td>' +
                    '<td><span class="activity-status ' + u.status + '"><span class="status-dot"></span>' + statusLabel + '</span></td>' +
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

    // Payment History Tab Handler
    $('#payment-tab').on('click', function() {
        console.log('Payment tab clicked, loading payments...');
        loadPayments();
    });
            
            if (MANAGE_ACCESS_CONFIG.activeTab === 'payment-history') { console.log('Payment tab active on load, fetching payments...'); loadPayments(); }
            
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
                        console.log('Table needs horizontal scrolling - indicator shown');
                    } else {
                        scrollIndicator.hide();
                        console.log('Table fits in container - indicator hidden');
                    }
                }
            }

            // Check scroll on page load and window resize
            $(window).on('load resize', function() {
                // Small delay to ensure table is fully rendered
                setTimeout(checkTableScroll, 100);
            });

            // Check scroll when payment data is loaded
            // We'll use a different approach since loadPayments doesn't return a promise
            let scrollCheckTimeout;
            const originalLoadPayments = loadPayments;
            loadPayments = function(page = 1, search = '') {
                if (scrollCheckTimeout) {
                    clearTimeout(scrollCheckTimeout);
                }
                originalLoadPayments.call(this, page, search);
                // Check scroll after a delay to allow table to render
                scrollCheckTimeout = setTimeout(checkTableScroll, 1000);
            };

            // Monitor table content changes
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && mutation.target.id === 'payments-table-body') {
                        setTimeout(checkTableScroll, 100);
                    }
                });
            });

            // Start observing
            const targetNode = document.getElementById('payments-table-body');
            if (targetNode) {
                observer.observe(targetNode, { childList: true });
            }

            // Initial check
            setTimeout(checkTableScroll, 500);

        });
        
        // Load payments function
        function loadPayments(page = 1, search = '') {
            console.log('Loading payments...', {page, search});

            // Show loading state
            const tbody = $('#payments-table-body');
            tbody.html(`
                <tr>
                    <td colspan="9" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                        <div>Loading payments...</div>
                    </td>
                </tr>
            `);

            $.ajax({
                url: '/manage-access/payments',
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': MANAGE_ACCESS_CONFIG.csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                data: {
                    page: page,
                    search: search
                },
                success: function(response) {
                                            console.log('Payments loaded:', response);
                        console.log('Response type:', typeof response);
                        console.log('Success:', response.success);
                        console.log('Stats:', response.stats);
                        console.log('Payments count:', response.payments ? response.payments.length : 'undefined');
                        
                        if (response.success) {
                        console.log('Response stats:', response.stats);
                        
                        // Update statistics with proper type checking
                        const totalRevenue = parseFloat(response.stats.total_revenue || 0);
                        const completedCount = parseInt(response.stats.completed_count || 0);
                        const pendingCount = parseInt(response.stats.pending_count || 0);
                        const rejectedCount = parseInt(response.stats.rejected_count || 0);
                        
                        $('#total-revenue').text(totalRevenue.toFixed(2) + ' EGP');
                        $('#completed-count').text(completedCount);
                        $('#pending-count').text(pendingCount);
                        $('#rejected-count').text(rejectedCount);
                        
                        // Update payments table
                        const tbody = $('#payments-table-body');
                        tbody.empty();
                        
                        if (response.payments.length === 0) {
                            tbody.append(`
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        <i class="fas fa-credit-card fa-3x mb-3 d-block"></i>
                                        <div>No payments found</div>
                                        <small>Payments will appear here once users make purchases</small>
                                    </td>
                                </tr>
                            `);
                        } else {
                            response.payments.forEach(function(payment) {
                                const statusBadge = getStatusBadge(payment.status);
                                const actions = getPaymentActions(payment);
                                const date = new Date(payment.created_at).toLocaleDateString();
                                
                                // Create price display
                                const priceDisplay = `<div class="fw-bold">${payment.amount} EGP</div>`;
                                
                                // FIXED: Show only transaction code (what user enters)
                                const transactionCode = payment.reference_number || payment.transaction_id || 'N/A';
                                const transactionDisplay = `<code class="small fw-bold" style="color: #2196f3; font-size: 0.85rem;">${transactionCode}</code>`;

                                // FIXED: Add yellow highlight for completed payments with data attribute
                                const rowStyle = payment.status === 'completed' ? 'background-color: #fffbeb !important;' : '';
                                const dataStatus = payment.status === 'completed' ? 'data-payment-status="completed"' : '';

                                tbody.append(`
                                    <tr data-payment-id="${payment.id}" ${dataStatus} style="${rowStyle}">
                                        <td>#${payment.id}</td>
                                        <td>
                                            <div class="fw-medium">${payment.user_name}</div>
                                            <small class="text-muted">${payment.user_email}</small>
                                        </td>
                                        <td>
                                            <span class="data-pill course">${payment.course_name}</span>
                                        </td>
                                        <td>${priceDisplay}</td>
                                        <td><span class="data-pill method">${payment.payment_method}</span></td>
                                        <td>${statusBadge}</td>
                                        <td>${transactionDisplay}</td>
                                        <td>${date}</td>
                                        <td>${actions}</td>
                                    </tr>
                                `);
                            });
                        }
                        
                        // Update pagination info
                        $('#showing-records').text(response.payments.length);
                        $('#total-records-count').text(response.pagination.total);
                        
                        // Generate pagination (simple version)
                        const pagination = $('#payment-pagination');
                        pagination.empty();
                        
                        if (response.pagination.last_page > 1) {
                            let paginationHtml = '<ul class="pagination pagination-sm mb-0">';
                            
                            // Previous button
                            if (response.pagination.current_page > 1) {
                                paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadPayments(${response.pagination.current_page - 1}, '${search}')">Previous</a></li>`;
                            }
                            
                            // Page numbers (simplified)
                            for (let i = 1; i <= Math.min(response.pagination.last_page, 5); i++) {
                                const active = i === response.pagination.current_page ? 'active' : '';
                                paginationHtml += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="loadPayments(${i}, '${search}')">${i}</a></li>`;
                            }
                            
                            // Next button
                            if (response.pagination.current_page < response.pagination.last_page) {
                                paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadPayments(${response.pagination.current_page + 1}, '${search}')">Next</a></li>`;
                            }
                            
                            paginationHtml += '</ul>';
                            pagination.html(paginationHtml);
                        }
                    }
                },
                error: function(xhr) {
                    console.error('Error loading payments:', xhr.responseText);
                    console.error('Status:', xhr.status);
                    console.error('Response:', xhr.responseJSON);
                    
                    let errorMessage = 'Please try again later';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.status === 404) {
                        errorMessage = 'Payment endpoint not found. Please check the route.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error occurred while loading payments.';
                    }
                    
                    $('#payments-table-body').html(`
                        <tr>
                            <td colspan="9" class="text-center py-4 text-danger">
                                <i class="fas fa-exclamation-triangle fa-2x mb-2 d-block"></i>
                                <div>Error loading payments</div>
                                <small>${errorMessage}</small>
                                <br><small class="text-muted">Status: ${xhr.status}</small>
                            </td>
                        </tr>
                    `);
                }
            });
        }
        
        function getStatusBadge(status) {
            switch(status) {
                case 'completed':
                    return '<span class="data-pill status-completed">Completed</span>';
                case 'pending':
                    return '<span class="data-pill status-pending">Pending</span>';
                case 'rejected':
                    return '<span class="data-pill status-rejected">Rejected</span>';
                default:
                    return '<span class="data-pill status-default">' + status + '</span>';
            }
        }
        
        function getPaymentActions(payment) {
            if (payment.status === 'pending') {
                return `
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-success btn-sm" onclick="acceptPayment(${payment.id})" title="Accept Payment">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="rejectPayment(${payment.id})" title="Reject Payment">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            } else {
                return '<span class="text-muted small">No actions</span>';
            }
        }

        // Payment Management Functions
        function acceptPayment(paymentId) {
            fetch(`/admin/payments/verify/${paymentId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': MANAGE_ACCESS_CONFIG.csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ admin_notes: 'Payment accepted' }),
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    if (response.status === 419) {
                        throw new Error('CSRF token expired. Please refresh the page.');
                    } else if (response.status === 403) {
                        throw new Error('Access denied. Admin privileges required.');
                    } else if (response.status === 401) {
                        throw new Error('Not authenticated. Please log in.');
                    } else {
                        throw new Error(`Server error: ${response.status} ${response.statusText}`);
                    }
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showNotification('success', 'Payment Accepted', 'Payment has been verified and approved.');
                    
                    // Update the UI with yellow highlight
                    const row = document.querySelector(`[data-payment-id="${paymentId}"]`);
                    if (row) {
                        row.style.setProperty('background-color', '#fffbeb', 'important');
                        row.setAttribute('data-payment-status', 'completed');
                        
                        const statusCell = row.querySelector('.payment-status');
                        if (statusCell) {
                            statusCell.textContent = 'Completed';
                            statusCell.className = 'badge payment-status bg-success';
                        }
                        
                        const actionsCell = row.querySelector('td:last-child');
                        if (actionsCell) {
                            actionsCell.innerHTML = '<span class="text-muted small">Completed</span>';
                        }
                    }
                    
                    loadPayments();
                } else {
                    throw new Error(data.message || 'Payment verification failed');
                }
            })
            .catch(error => {
                showNotification('error', 'Payment Failed', error.message);
            });
        }

        function rejectPayment(paymentId) {
            fetch(`/admin/payments/${paymentId}/reject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': MANAGE_ACCESS_CONFIG.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ reason: 'Payment rejected' }),
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Server error: ${response.status} ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showNotification('warning', 'Payment Rejected', 'Payment has been rejected.');
                    
                    const row = document.querySelector(`[data-payment-id="${paymentId}"]`);
                    if (row) {
                        const statusCell = row.querySelector('.payment-status');
                        if (statusCell) {
                            statusCell.textContent = 'Rejected';
                            statusCell.className = 'badge payment-status bg-danger';
                        }
                        
                        const actionsCell = row.querySelector('td:last-child');
                        if (actionsCell) {
                            actionsCell.innerHTML = '<span class="text-muted small">Rejected</span>';
                        }
                    }
                    
                    loadPayments();
                } else {
                    showNotification('error', 'Reject Failed', data.message || 'Unknown error');
                }
            })
            .catch(error => {
                showNotification('error', 'Reject Failed', error.message);
            });
        }

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
            const courseId = $(this).data('course-id');
            const enabled = $(`.course-fake-toggle[data-course-id="${courseId}"]`).is(':checked');
            const count = parseInt($(`.course-fake-count[data-course-id="${courseId}"]`).val()) || 0;
            
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
                        showNotification('success', 'Saved!', 'Course fake enrolled settings updated');
                    } else {
                        showNotification('error', 'Error', response.message || 'Failed to save');
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'Failed to save course settings';
                    console.error('Save error:', xhr.responseJSON);
                    showNotification('error', 'Error', errorMsg);
                }
            });
        });
        
        // Save all courses fake settings
        $('#saveAllCourseFakeSettings').on('click', function() {
            const coursesData = [];
            
            $('.course-fake-toggle').each(function() {
                const courseId = $(this).data('course-id');
                const enabled = $(this).is(':checked');
                const count = parseInt($(`.course-fake-count[data-course-id="${courseId}"]`).val()) || 0;
                
                coursesData.push({
                    id: courseId,
                    fake_enrolled_enabled: enabled ? 1 : 0,
                    fake_enrolled_count: count
                });
            });
            
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
                }
            });
        });
        
        // Apply bulk count to all courses
        function applyBulkCourseCount() {
            const count = prompt('Enter fake enrolled count to apply to all courses:', '250');
            if (count !== null && !isNaN(count)) {
                $('.course-fake-count').val(parseInt(count));
                $('.course-fake-toggle').prop('checked', true);
                showNotification('success', 'Applied!', `Set all courses to ${count} fake enrolled users. Click "Save All Courses" to apply.`);
            }
        }

        $(document).ready(function() {
            $('#video-tab').on('click', function() {
                loadVideos();
            });

            if (MANAGE_ACCESS_CONFIG.activeTab === 'video-management') { loadVideos(); }

            $('#video_course_id').on('change', function() {
                const courseId = $(this).val();
                const topicSelect = $('#video_topic_id');

                if (courseId) {
                    $.ajax({
                        url: `/api/courses/${courseId}/topics`,
                        method: 'GET',
                        success: function(topics) {
                            topicSelect.empty().append('<option value="">Select topic...</option>');
                            topics.forEach(topic => {
                                topicSelect.append(`<option value="${topic.id}">${topic.name}</option>`);
                            });
                            topicSelect.prop('disabled', false);
                        },
                        error: function() {
                            showNotification('Failed to load topics', 'error');
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
                    url: '/admin/videos',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            showNotification('Video added successfully!', 'success');
                            $('#addVideoForm')[0].reset();
                            $('#video_topic_id').prop('disabled', true).empty().append('<option value="">First select a course...</option>');
                            loadVideos();
                        } else {
                            showNotification(response.message || 'Failed to add video', 'error');
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        showNotification(response?.message || 'Failed to add video', 'error');
                    }
                });
            });
        });

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
                url: '/admin/videos',
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
                    showNotification('Failed to load videos: ' + (xhr.responseJSON?.message || error), 'error');
                }
            });
        }

        function deleteVideo(videoId) {
            if (confirm('Are you sure you want to delete this video?')) {
                $.ajax({
                    url: `/admin/videos/${videoId}`,
                    method: 'DELETE',
                    data: { _token: MANAGE_ACCESS_CONFIG.csrfToken },
                    success: function(response) {
                        if (response.success) {
                            showNotification('Video deleted successfully!', 'success');
                            loadVideos();
                        } else {
                            showNotification('Failed to delete video', 'error');
                        }
                    },
                    error: function() {
                        showNotification('Failed to delete video', 'error');
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
