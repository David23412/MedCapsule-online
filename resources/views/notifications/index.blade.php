<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Notifications - MedCapsule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        .navbar {
            background-color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.95);
        }

        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0;
        }

        .navbar-brand {
            color: #007bff;
            font-weight: 700;
            font-size: 1.5rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: transform 0.3s ease;
        }

        .navbar-brand:hover {
            color: #0056b3;
            transform: translateY(-1px);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .nav-link {
            color: #495057;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link i {
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: #007bff;
            background-color: #f8f9fa;
            transform: translateY(-1px);
        }

        .nav-link:hover i {
            transform: scale(1.1);
        }

        .main-content {
            margin-top: 80px;
            padding: 2rem;
        }

        .container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            max-width: 800px;
            margin: 0 auto;
        }

        h1 {
            text-align: center;
            margin-bottom: 2rem;
            color: #2c3e50;
            font-weight: 600;
        }

        .notification-list {
            margin-top: 1.5rem;
        }

        .notification-item {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            display: flex;
            align-items: flex-start;
            position: relative;
        }

        .notification-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .notification-item.unread {
            background-color: #e3f2fd;
            border-left: 4px solid #007bff;
        }

        .notification-item.read {
            background-color: #ffffff;
            border-left: 4px solid #e9ecef;
            opacity: 0.8;
        }

        .notification-icon {
            font-size: 1.5rem;
            margin-right: 1rem;
            margin-top: 0.2rem;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #343a40;
        }

        .notification-message {
            margin-bottom: 0.5rem;
            color: #495057;
        }

        .notification-time {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .notification-actions {
            margin-left: 1rem;
        }

        .mark-read-btn {
            background: none;
            border: none;
            color: #007bff;
            cursor: pointer;
            font-size: 0.9rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .mark-read-btn:hover {
            background-color: #f8f9fa;
            color: #0056b3;
        }

        .actions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .mark-all-read-btn {
            background-color: #e9ecef;
            border: none;
            color: #495057;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .mark-all-read-btn:hover {
            background-color: #dee2e6;
            transform: translateY(-1px);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }

        .profile-circle {
            width: 32px;
            height: 32px;
            background: #2196f3;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            transition: transform 0.2s ease;
            background-size: cover;
            background-position: center;
        }

        .profile-circle.no-image {
            background: #2196f3;
        }

        .profile-circle:hover {
            transform: scale(1.1);
        }

        .pagination-container {
            margin-top: 2rem;
            display: flex;
            justify-content: center;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="/">MedCapsule</a>
            <div class="nav-links">
                <a href="javascript:history.back()" class="nav-link">
                    <i class="fas fa-arrow-left"></i>
                    Back
                </a>
                <a href="{{ route('profile') }}" class="profile-circle {{ !Auth::user()->profile_picture_url ? 'no-image' : '' }}" 
                     style="{{ Auth::user()->profile_picture_url ? 'background-image: url(' . asset(Auth::user()->profile_picture_url) . ');' : '' }}">
                    @if(!Auth::user()->profile_picture_url)
                        {{ substr(Auth::user()->name, 0, 1) }}
                    @endif
                </a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">
            <h1>
                <i class="fas fa-bell me-2"></i>
                Your Notifications
            </h1>

            <div class="actions-header">
                <div class="filter-options">
                    <a href="{{ route('notifications.index') }}" class="btn btn-sm {{ !request()->has('unread') ? 'btn-primary' : 'btn-light' }}">All</a>
                    <a href="{{ route('notifications.index') }}?unread=1" class="btn btn-sm {{ request()->has('unread') ? 'btn-primary' : 'btn-light' }}">Unread</a>
                </div>
                
                @if($notifications->count() > 0)
                    <form action="{{ route('notifications.mark-all-read') }}" method="POST" id="mark-all-form">
                        @csrf
                        <button type="submit" class="mark-all-read-btn">
                            <i class="fas fa-check-double"></i>
                            Mark All as Read
                        </button>
                    </form>
                @endif
            </div>

            <div class="notification-list">
                @forelse($notifications as $notification)
                    <div class="notification-item {{ $notification->read ? 'read' : 'unread' }}" id="notification-{{ $notification->id }}">
                        <div class="notification-icon {{ $notification->color }}">
                            <i class="{{ $notification->icon }}"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">{{ $notification->title }}</div>
                            <div class="notification-message">{{ $notification->message }}</div>
                            <div class="notification-time">{{ $notification->time_ago }}</div>
                        </div>
                        @if(!$notification->read)
                            <div class="notification-actions">
                                <button class="mark-read-btn" data-id="{{ $notification->id }}">
                                    <i class="fas fa-check"></i>
                                    Mark as Read
                                </button>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <p>You don't have any notifications yet.</p>
                        <p class="small">Notifications will appear here when you reach milestones or have important updates.</p>
                    </div>
                @endforelse
            </div>

            <div class="pagination-container">
                {{ $notifications->links() }}
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mark single notification as read
            document.querySelectorAll('.mark-read-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const notificationId = this.dataset.id;
                    const notificationElement = document.getElementById(`notification-${notificationId}`);
                    
                    fetch(`/api/notifications/${notificationId}/read`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update the UI
                            notificationElement.classList.remove('unread');
                            notificationElement.classList.add('read');
                            this.parentElement.remove();
                            
                            // Update notification badge count
                            updateNotificationBadge(data.unread_count);
                        }
                    })
                    .catch(error => {
                        console.error('Error marking notification as read:', error);
                    });
                });
            });

            // Mark all notifications as read
            const markAllForm = document.getElementById('mark-all-form');
            if (markAllForm) {
                markAllForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    fetch('/notifications/mark-all-read', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update all notifications to read state
                            document.querySelectorAll('.notification-item.unread').forEach(item => {
                                item.classList.remove('unread');
                                item.classList.add('read');
                                const actionsElement = item.querySelector('.notification-actions');
                                if (actionsElement) {
                                    actionsElement.remove();
                                }
                            });
                            
                            // Update notification badge count
                            updateNotificationBadge(0);
                        }
                    })
                    .catch(error => {
                        console.error('Error marking all notifications as read:', error);
                    });
                });
            }

            function updateNotificationBadge(count) {
                // This would update any notification badge/counter in the layout
                // Since we don't have direct access to the layout, we'll just 
                // implement the function for when we add the badge later
                console.log('Notification count updated to:', count);
                
                // You would typically update a DOM element with the count:
                // const badge = document.getElementById('notification-badge');
                // if (badge) {
                //     if (count > 0) {
                //         badge.textContent = count;
                //         badge.classList.remove('d-none');
                //     } else {
                //         badge.classList.add('d-none');
                //     }
                // }
            }
        });
    </script>
</body>
</html> 