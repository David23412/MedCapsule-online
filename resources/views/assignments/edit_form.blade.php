<!-- Assignment Context Info -->
<div class="assignment-context">
    <div class="context-header">
        <div class="context-icon">
            <i class="fas fa-edit"></i>
        </div>
        <div class="context-details">
            <h4 class="context-title">{{ $assignment->title ?: ($assignment->topic->name ?? 'Assignment') }}</h4>
            <div class="context-meta">
                <span class="meta-item">
                    <i class="fas fa-book"></i>
                    {{ $assignment->course->name ?? 'Unknown Course' }}
                </span>
                <span class="meta-separator">•</span>
                <span class="meta-item">
                    <i class="fas fa-bookmark"></i>
                    {{ $assignment->topic->name ?? 'Unknown Topic' }}
                </span>
            </div>
        </div>
    </div>
    
    @if($assignment->assignedUsers && $assignment->assignedUsers->count() > 0)
        <div class="assigned-students">
            <div class="students-label">
                <i class="fas fa-users"></i>
                {{ $assignment->assignedUsers->count() }} Student{{ $assignment->assignedUsers->count() > 1 ? 's' : '' }}
            </div>
            <div class="students-list">
                @foreach($assignment->assignedUsers->take(3) as $user)
                    <div class="student-avatar" title="{{ $user->name }}">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                @endforeach
                @if($assignment->assignedUsers->count() > 3)
                    <div class="student-avatar more-students">
                        +{{ $assignment->assignedUsers->count() - 3 }}
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

<!-- Edit Form -->
<form id="editAssignmentForm" method="POST" class="edit-form">
    @csrf
    @method('PUT')
    
    <div class="form-sections">
        <!-- Title Section -->
        <div class="form-section">
            <div class="section-header">
                <i class="fas fa-heading section-icon"></i>
                <label for="edit_title" class="section-label">Assignment Title</label>
            </div>
            <input type="text" 
                   class="form-input" 
                   id="edit_title" 
                   name="title" 
                   value="{{ $assignment->title }}"
                   placeholder="Enter assignment title (optional)">
            <div class="field-hint">Leave blank to use topic name as title</div>
        </div>
        
        <!-- Timing Section -->
        <div class="form-section">
            <div class="section-header">
                <i class="fas fa-clock section-icon"></i>
                <span class="section-label">Timing & Duration</span>
            </div>
            
            <div class="form-grid">
                <div class="form-field">
                    <label for="edit_deadline" class="field-label">
                        <i class="fas fa-calendar-alt"></i>
                        Deadline
                    </label>
                    
                    <div class="datetime-inputs">
                        <input type="date" 
                               class="form-input date-input" 
                               id="edit_deadline_date" 
                               name="deadline_date" 
                               value="{{ $assignment->deadline ? $assignment->deadline->format('Y-m-d') : '' }}"
                               placeholder="Date">
                        
                        <select class="form-input time-input" id="edit_deadline_hour" name="deadline_hour">
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ $assignment->deadline && $assignment->deadline->format('g') == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                        
                        <select class="form-input time-input" id="edit_deadline_minute" name="deadline_minute">
                            @for($i = 0; $i < 60; $i += 15)
                                <option value="{{ $i }}" {{ $assignment->deadline && $assignment->deadline->format('i') == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                            @endfor
                        </select>
                        
                        <select class="form-input time-input" id="edit_deadline_ampm" name="deadline_ampm">
                            <option value="AM" {{ $assignment->deadline && $assignment->deadline->format('A') == 'AM' ? 'selected' : '' }}>AM</option>
                            <option value="PM" {{ $assignment->deadline && $assignment->deadline->format('A') == 'PM' ? 'selected' : '' }}>PM</option>
                        </select>
                    </div>
                    
                    <!-- Hidden field for the actual datetime value -->
                    <input type="hidden" id="edit_deadline" name="deadline" value="">
                    
                    @if($assignment->deadline)
                        <div class="field-hint">
                            Current: {{ $assignment->deadline->format('M j, Y g:i A') }}
                            ({{ $assignment->deadline->diffForHumans() }})
                        </div>
                    @endif
                </div>
                
                <div class="form-field">
                    <label for="edit_duration" class="field-label">
                        <i class="fas fa-hourglass-half"></i>
                        Duration
                    </label>
                    <div class="input-group">
                        <input type="number" 
                               class="form-input" 
                               id="edit_duration" 
                               name="duration_minutes" 
                               value="{{ $assignment->duration_minutes }}"
                               min="0" 
                               max="1440"
                               placeholder="0">
                        <span class="input-suffix">minutes</span>
                    </div>
                    <div class="field-hint">0 = No time limit</div>
                </div>
            </div>
        </div>
        
        <!-- Status Section -->
        <div class="form-section">
            <div class="section-header">
                <i class="fas fa-flag section-icon"></i>
                <label for="edit_status" class="section-label">Assignment Status</label>
            </div>
            
            <div class="status-selector">
                <div class="status-option {{ $assignment->getCurrentStatusAttribute() === 'active' ? 'selected' : '' }}" 
                     data-value="active">
                    <div class="status-indicator status-active"></div>
                    <div class="status-details">
                        <div class="status-name">Active</div>
                        <div class="status-description">Students can access and complete</div>
                    </div>
                    <input type="radio" name="status" value="active" 
                           {{ $assignment->getCurrentStatusAttribute() === 'active' ? 'checked' : '' }}>
                </div>
                
                <div class="status-option {{ $assignment->getCurrentStatusAttribute() === 'overdue' ? 'selected' : '' }}" 
                     data-value="overdue">
                    <div class="status-indicator status-overdue"></div>
                    <div class="status-details">
                        <div class="status-name">Overdue</div>
                        <div class="status-description">Past deadline, limited access</div>
                    </div>
                    <input type="radio" name="status" value="overdue" 
                           {{ $assignment->getCurrentStatusAttribute() === 'overdue' ? 'checked' : '' }}>
                </div>
            </div>
        </div>
        
        <!-- Per-User Extensions Section -->
        @if($assignment->assignedUsers && $assignment->assignedUsers->count() > 0)
        <div class="form-section">
            <div class="section-header">
                <i class="fas fa-user-clock section-icon"></i>
                <span class="section-label">Individual Student Extensions</span>
            </div>
            
            <div class="user-extensions">
                <div class="extensions-info">
                    <i class="fas fa-info-circle"></i>
                    Grant individual deadline extensions to specific students
                </div>
                
                @foreach($assignment->assignedUsers as $user)
                <div class="user-extension-item">
                    <div class="user-info-ext">
                        <div class="user-avatar-ext">{{ substr($user->name, 0, 1) }}</div>
                        <div class="user-details-ext">
                            <div class="user-name-ext">{{ $user->name }}</div>
                            <div class="user-email-ext">{{ $user->email }}</div>
                        </div>
                    </div>
                    
                    <div class="extension-controls">
                        <div class="extension-buttons">
                            <button type="button" class="extension-btn" data-user="{{ $user->id }}" data-hours="24">
                                <i class="fas fa-plus"></i>
                                +1 Day
                            </button>
                            <button type="button" class="extension-btn" data-user="{{ $user->id }}" data-hours="168">
                                <i class="fas fa-plus"></i>
                                +1 Week
                            </button>
                            <button type="button" class="extension-btn custom-btn" data-user="{{ $user->id }}">
                                <i class="fas fa-edit"></i>
                                Custom
                            </button>
                        </div>
                        
                        <!-- Custom extension input (hidden by default) -->
                        <div class="custom-extension" id="custom-ext-{{ $user->id }}" style="display: none;">
                            <div class="custom-datetime">
                                <input type="date" class="form-input custom-date" placeholder="Date">
                                <input type="time" class="form-input custom-time" placeholder="Time">
                                <button type="button" class="btn btn-sm btn-primary apply-custom" data-user="{{ $user->id }}">
                                    Apply
                                </button>
                                <button type="button" class="btn btn-sm btn-outline cancel-custom" data-user="{{ $user->id }}">
                                    Cancel
                                </button>
                            </div>
                        </div>
                        
                        <!-- Current extension display -->
                        <div class="current-extension" id="current-ext-{{ $user->id }}">
                            @if($assignment->hasExtensionForUser($user->id))
                                @php $extInfo = $assignment->getExtensionInfoForUser($user->id) @endphp
                                <span class="extension-status extended">
                                    Extended by {{ $extInfo['extension_human'] }}
                                    <br>
                                    <small>Until {{ $extInfo['extended_deadline']->format('M j, g:i A') }}</small>
                                </span>
                            @else
                                <span class="extension-status">Same as assignment deadline</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    
    <!-- Form Actions -->
    <div class="form-actions">
        <button type="button" class="btn btn-outline" onclick="closeEditModal()">
            <i class="fas fa-times"></i>
            Cancel
        </button>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i>
            Update Assignment
        </button>
    </div>
</form>

<style>
/* Modern, Minimal Edit Form Styles */
.assignment-context {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.context-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}

.context-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.context-details {
    flex: 1;
    min-width: 0;
}

.context-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.5rem 0;
    line-height: 1.3;
}

.context-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.875rem;
    color: #64748b;
    font-weight: 500;
}

.meta-item i {
    color: #3b82f6;
    font-size: 0.75rem;
}

.meta-separator {
    color: #cbd5e1;
    font-weight: 300;
}

.assigned-students {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
}

.students-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.students-label i {
    color: #3b82f6;
}

.students-list {
    display: flex;
    gap: 0.375rem;
}

.student-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.student-avatar.more-students {
    background: #6b7280;
    font-size: 0.625rem;
}

/* Form Styles */
.edit-form {
    margin-top: 0;
}

.form-sections {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-section {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1.25rem;
    transition: all 0.2s ease;
}

.form-section:hover {
    border-color: #cbd5e1;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.section-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.section-icon {
    color: #3b82f6;
    font-size: 1rem;
}

.section-label {
    font-size: 1rem;
    font-weight: 600;
    color: #0f172a;
    margin: 0;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.field-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin: 0;
}

.field-label i {
    color: #64748b;
    font-size: 0.75rem;
}

.form-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    background: white;
    font-family: inherit;
}

.form-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    transform: scale(1.01);
}

.form-input:hover:not(:focus) {
    border-color: #cbd5e1;
}

.input-group {
    position: relative;
    display: flex;
    align-items: center;
}

.input-suffix {
    position: absolute;
    right: 1rem;
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 500;
    pointer-events: none;
}

.field-hint {
    font-size: 0.75rem;
    color: #64748b;
    font-style: italic;
}

/* Status Selector */
.status-selector {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.status-option {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.status-option:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
}

.status-option.selected {
    border-color: #3b82f6;
    background: #f0f9ff;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
}

.status-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}

.status-indicator.status-active {
    background: #10b981;
}

.status-indicator.status-overdue {
    background: #ef4444;
}

.status-details {
    flex: 1;
}

.status-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 0.25rem;
}

.status-description {
    font-size: 0.75rem;
    color: #64748b;
}

/* Form Actions */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
    margin-top: 1.5rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    text-decoration: none;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;
    font-family: inherit;
}

.btn:hover {
    transform: translateY(-1px);
}

.btn:active {
    transform: translateY(0);
}

.btn-primary {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.btn-primary:hover {
    background: #2563eb;
    border-color: #2563eb;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-outline {
    background: white;
    color: #6b7280;
    border-color: #d1d5db;
}

.btn-outline:hover {
    background: #f9fafb;
    border-color: #9ca3af;
    color: #374151;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Responsive Design */
@media (max-width: 768px) {
    .assignment-context {
        padding: 1rem;
    }
    
    .context-header {
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 0.75rem;
    }
    
    .context-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .context-title {
        font-size: 1.125rem;
    }
    
    .assigned-students {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .form-section {
        padding: 1rem;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .form-actions {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .btn {
        justify-content: center;
        width: 100%;
    }
    
    .status-option {
        padding: 0.75rem;
    }
}

/* Interactive Status Selection */
.status-option:hover .status-indicator {
    transform: scale(1.2);
    transition: transform 0.2s ease;
}

.status-option.selected .status-indicator {
    transform: scale(1.1);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

/* Loading States */
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

.form-input:disabled {
    background: #f9fafb;
    color: #9ca3af;
    cursor: not-allowed;
}

/* Focus Management */
.form-input:focus + .input-suffix {
    color: #3b82f6;
}

/* Animation for smooth interactions */
* {
    scroll-behavior: smooth;
}

.form-section {
    animation: fadeInUp 0.3s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Quick Deadline Buttons */
.quick-deadline-buttons {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
    flex-wrap: wrap;
}

.quick-buttons-label {
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 500;
}

.quick-btn {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #64748b;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.15s ease;
    font-weight: 500;
}

.quick-btn:hover {
    background: #3b82f6;
    border-color: #3b82f6;
    color: white;
    transform: translateY(-1px);
}

.quick-btn[data-clear="true"] {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626;
}

.quick-btn[data-clear="true"]:hover {
    background: #dc2626;
    border-color: #dc2626;
    color: white;
}

/* DateTime Inputs */
.datetime-inputs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
}

.date-input, .time-input {
    font-size: 0.875rem;
}

/* User Extensions */
.user-extensions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.extensions-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 6px;
    font-size: 0.875rem;
    color: #0369a1;
}

.extensions-info i {
    color: #0284c7;
}

.user-extension-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.user-extension-item:hover {
    border-color: #cbd5e1;
    background: #f1f5f9;
}

.user-info-ext {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-avatar-ext {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
}

.user-details-ext {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.user-name-ext {
    font-weight: 600;
    color: #0f172a;
    font-size: 0.875rem;
}

.user-email-ext {
    font-size: 0.75rem;
    color: #64748b;
}

.extension-controls {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
}

.extension-buttons {
    display: flex;
    gap: 0.5rem;
}

.extension-btn {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    color: #374151;
    cursor: pointer;
    transition: all 0.15s ease;
}

.extension-btn:hover {
    background: #3b82f6;
    border-color: #3b82f6;
    color: white;
    transform: translateY(-1px);
}

.extension-btn.custom-btn {
    background: #f59e0b;
    border-color: #f59e0b;
    color: white;
}

.extension-btn.custom-btn:hover {
    background: #d97706;
    border-color: #d97706;
}

.custom-extension {
    margin-top: 0.5rem;
}

.custom-datetime {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 6px;
}

.custom-date, .custom-time {
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    width: auto;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
}

.current-extension {
    font-size: 0.75rem;
    color: #64748b;
    font-style: italic;
}

.extension-status {
    padding: 0.25rem 0.5rem;
    background: #f1f5f9;
    border-radius: 4px;
    font-weight: 500;
}

.extension-status.extended {
    background: #dcfce7;
    color: #166534;
}

/* Responsive Extensions */
@media (max-width: 768px) {
    .datetime-inputs {
        grid-template-columns: 1fr;
    }
    
    .user-extension-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .extension-controls {
        width: 100%;
        align-items: stretch;
    }
    
    .extension-buttons {
        justify-content: center;
    }
    
    .custom-datetime {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .custom-date, .custom-time {
        width: 100%;
    }
}
</style>

<script>
// Interactive status selection
document.addEventListener('DOMContentLoaded', function() {
    const statusOptions = document.querySelectorAll('.status-option');
    
    statusOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all options
            statusOptions.forEach(opt => opt.classList.remove('selected'));
            
            // Add selected class to clicked option
            this.classList.add('selected');
            
            // Check the radio button
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
            }
        });
    });
    
    // Handle separate date and time inputs (12-hour format)
    const dateInput = document.getElementById('edit_deadline_date');
    const hourInput = document.getElementById('edit_deadline_hour');
    const minuteInput = document.getElementById('edit_deadline_minute');
    const ampmInput = document.getElementById('edit_deadline_ampm');
    const hiddenDeadlineInput = document.getElementById('edit_deadline');
    
    function updateHiddenDeadline() {
        if (dateInput && hourInput && minuteInput && ampmInput && hiddenDeadlineInput) {
            const date = dateInput.value;
            const hour = parseInt(hourInput.value);
            const minute = parseInt(minuteInput.value);
            const ampm = ampmInput.value;
            
            if (date && hour && minute !== undefined && ampm) {
                // Convert 12-hour to 24-hour format
                let hour24 = hour;
                if (ampm === 'PM' && hour !== 12) {
                    hour24 = hour + 12;
                } else if (ampm === 'AM' && hour === 12) {
                    hour24 = 0;
                }
                
                // Format as YYYY-MM-DDTHH:MM
                const formattedHour = String(hour24).padStart(2, '0');
                const formattedMinute = String(minute).padStart(2, '0');
                hiddenDeadlineInput.value = `${date}T${formattedHour}:${formattedMinute}`;
            } else if (date) {
                hiddenDeadlineInput.value = `${date}T23:59`;
            } else {
                hiddenDeadlineInput.value = '';
            }
        }
    }
    
    if (dateInput) {
        dateInput.addEventListener('change', updateHiddenDeadline);
    }
    if (hourInput) {
        hourInput.addEventListener('change', updateHiddenDeadline);
    }
    if (minuteInput) {
        minuteInput.addEventListener('change', updateHiddenDeadline);
    }
    if (ampmInput) {
        ampmInput.addEventListener('change', updateHiddenDeadline);
    }
    
    // Initialize hidden deadline field on page load
    updateHiddenDeadline();
    
    // Add quick time buttons for common deadlines
    const deadlineField = dateInput?.closest('.form-field');
    if (deadlineField && dateInput && hourInput && minuteInput && ampmInput) {
        const quickButtons = document.createElement('div');
        quickButtons.className = 'quick-deadline-buttons';
        quickButtons.innerHTML = `
            <div class="quick-buttons-label">Quick set:</div>
            <button type="button" class="quick-btn" data-hours="1">1 hour</button>
            <button type="button" class="quick-btn" data-hours="24">1 day</button>
            <button type="button" class="quick-btn" data-hours="168">1 week</button>
            <button type="button" class="quick-btn" data-clear="true">Clear</button>
        `;
        
        deadlineField.appendChild(quickButtons);
        
        // Handle quick button clicks
        quickButtons.addEventListener('click', function(e) {
            if (e.target.classList.contains('quick-btn')) {
                e.preventDefault();
                
                if (e.target.dataset.clear) {
                    dateInput.value = '';
                    hourInput.value = '12';
                    minuteInput.value = '0';
                    ampmInput.value = 'PM';
                } else {
                    const hours = parseInt(e.target.dataset.hours);
                    const futureDate = new Date();
                    futureDate.setHours(futureDate.getHours() + hours);
                    
                    // Format for separate inputs
                    const year = futureDate.getFullYear();
                    const month = String(futureDate.getMonth() + 1).padStart(2, '0');
                    const day = String(futureDate.getDate()).padStart(2, '0');
                    
                    // Convert to 12-hour format
                    let hour = futureDate.getHours();
                    const minute = futureDate.getMinutes();
                    const ampm = hour >= 12 ? 'PM' : 'AM';
                    
                    hour = hour % 12;
                    hour = hour ? hour : 12; // 0 should be 12
                    
                    dateInput.value = `${year}-${month}-${day}`;
                    hourInput.value = hour;
                    minuteInput.value = Math.floor(minute / 15) * 15; // Round to nearest 15
                    ampmInput.value = ampm;
                }
                
                // Update hidden field
                updateHiddenDeadline();
            }
        });
    }
    
    // Handle user extension buttons
    const extensionButtons = document.querySelectorAll('.extension-btn');
    const userExtensions = {}; // Store user-specific extensions
    
    extensionButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.dataset.user;
            const hours = this.dataset.hours;
            
            if (this.classList.contains('custom-btn')) {
                // Show custom extension form
                const customForm = document.getElementById(`custom-ext-${userId}`);
                if (customForm) {
                    customForm.style.display = customForm.style.display === 'none' ? 'block' : 'none';
                }
            } else if (hours) {
                // Apply preset extension
                const futureDate = new Date();
                futureDate.setHours(futureDate.getHours() + parseInt(hours));
                
                userExtensions[userId] = futureDate;
                updateExtensionDisplay(userId, futureDate);
            }
        });
    });
    
    // Handle custom extension apply/cancel
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('apply-custom')) {
            const userId = e.target.dataset.user;
            const customForm = document.getElementById(`custom-ext-${userId}`);
            const dateInput = customForm.querySelector('.custom-date');
            const timeInput = customForm.querySelector('.custom-time');
            
            if (dateInput.value && timeInput.value) {
                const customDate = new Date(`${dateInput.value}T${timeInput.value}`);
                userExtensions[userId] = customDate;
                updateExtensionDisplay(userId, customDate);
                customForm.style.display = 'none';
            }
        }
        
        if (e.target.classList.contains('cancel-custom')) {
            const userId = e.target.dataset.user;
            const customForm = document.getElementById(`custom-ext-${userId}`);
            customForm.style.display = 'none';
        }
    });
    
    function updateExtensionDisplay(userId, extensionDate) {
        const currentExtDisplay = document.getElementById(`current-ext-${userId}`);
        if (currentExtDisplay) {
            const now = new Date();
            const diffHours = Math.round((extensionDate - now) / (1000 * 60 * 60));
            
            let displayText = '';
            if (diffHours > 24) {
                const days = Math.round(diffHours / 24);
                displayText = `Extended by ${days} day${days > 1 ? 's' : ''}`;
            } else {
                displayText = `Extended by ${diffHours} hour${diffHours > 1 ? 's' : ''}`;
            }
            
            currentExtDisplay.innerHTML = `<span class="extension-status extended">${displayText}</span>`;
        }
    }
    
    // Add user extensions to form submission
    const form = document.getElementById('editAssignmentForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Add user extensions as hidden inputs
            Object.keys(userExtensions).forEach(userId => {
                const extensionDate = userExtensions[userId];
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = `user_extensions[${userId}]`;
                hiddenInput.value = extensionDate.toISOString();
                form.appendChild(hiddenInput);
            });
        });
    }
});
</script>