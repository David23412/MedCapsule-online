(function () {
    'use strict';
    let state = {page: 1, search: ''};
    let request;
    let requestVersion = 0;
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[char]));
        // Load payments function
        window.loadPayments = function(page = state.page, search = state.search) {
            state = {page, search};
            const version = ++requestVersion;
            request?.abort();
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

            request = $.ajax({
                timeout: 12000,
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
                    if (version !== requestVersion) return;
                        if (response.success) {
                        
                        // Update statistics with proper type checking
                        const totalRevenue = parseFloat(response.stats.total_revenue || 0);
                        const completedCount = parseInt(response.stats.completed_count || 0);
                        const pendingCount = parseInt(response.stats.pending_count || 0);
                        const rejectedCount = parseInt(response.stats.rejected_count || 0);
                        
                        $('#total-revenue').text(totalRevenue.toFixed(2) + ' EGP');
                        $('#confirmed-payment-revenue').text(Number(response.stats.confirmed_payment_revenue || 0).toFixed(2) + ' EGP');
                        $('#manual-enrollment-revenue').text(Number(response.stats.manual_enrollment_revenue || 0).toFixed(2) + ' EGP');
                        $('#manual-enrollment-count').text(Number(response.stats.manual_enrollment_count || 0));
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
                            response.payments.forEach(function(rawPayment) {
                                const payment = Object.fromEntries(Object.entries(rawPayment).map(([key, value]) => [key, escapeHtml(value)]));
                                payment.id = Number(rawPayment.id);
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
                                paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-payment-page="${response.pagination.current_page - 1}">Previous</a></li>`;
                            }
                            
                            // Page numbers (simplified)
                            for (let i = Math.max(1, response.pagination.current_page - 2); i <= Math.min(response.pagination.last_page, response.pagination.current_page + 2); i++) {
                                const active = i === response.pagination.current_page ? 'active' : '';
                                paginationHtml += `<li class="page-item ${active}"><a class="page-link" href="#" data-payment-page="${i}">${i}</a></li>`;
                            }
                            
                            // Next button
                            if (response.pagination.current_page < response.pagination.last_page) {
                                paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-payment-page="${response.pagination.current_page + 1}">Next</a></li>`;
                            }
                            
                            paginationHtml += '</ul>';
                            pagination.html(paginationHtml);
                            pagination.find('[data-payment-page]').on('click', function(event) {
                                event.preventDefault();
                                window.loadPayments(Number(this.dataset.paymentPage), search);
                            });
                        }
                    }
                },
                complete: function() {
                    if (version === requestVersion) {
                        request = null;
                        $(document).trigger('payments:updated');
                    }
                },
                error: function(xhr, status) {
                    if (version !== requestVersion || status === 'abort') return;
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
                                <small>${escapeHtml(errorMessage)}</small>
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
                    return '<span class="data-pill status-default">' + escapeHtml(status) + '</span>';
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


})();
