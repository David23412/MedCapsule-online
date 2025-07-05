<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Medcapsule</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('css/dark-mode.css') }}" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
        }

        .header {
            background: var(--bg-secondary);
            box-shadow: 0 1px 3px var(--shadow-color);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 50;
        }

        .nav-link {
            color: var(--text-secondary);
            transition: color 0.2s;
        }

        .nav-link:hover {
            color: var(--text-primary);
        }

        .nav-link.active {
            color: var(--text-primary);
            font-weight: 500;
        }

        .profile-circle {
            width: 40px;
            height: 40px;
            background: var(--border-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }

        .profile-circle:hover {
            background: var(--text-secondary);
        }

        .profile-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.5rem;
            background: var(--bg-secondary);
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px var(--shadow-color);
            width: 200px;
            display: none;
        }

        .profile-dropdown.active {
            display: block;
        }

        .profile-dropdown a {
            display: block;
            padding: 0.75rem 1rem;
            color: var(--text-secondary);
            transition: all 0.2s;
        }

        .profile-dropdown a:hover {
            background: var(--bg-primary);
            color: var(--text-primary);
        }

        .profile-dropdown .divider {
            height: 1px;
            background: var(--border-color);
            margin: 0.5rem 0;
        }

        .profile-dropdown .logout {
            color: #ef4444;
        }

        .profile-dropdown .logout:hover {
            background: #fee2e2;
        }

        /* Settings specific styles */
        .settings-section {
            background: var(--bg-secondary);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px var(--shadow-color);
        }

        .settings-section h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--border-color);
            transition: .4s;
            border-radius: 24px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 2px;
            bottom: 2px;
            background-color: var(--bg-secondary);
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: #3b82f6;
        }

        input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }

        .duration-option {
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--text-primary);
        }

        .duration-option:hover {
            border-color: #3b82f6;
            background: var(--bg-primary);
        }

        .duration-option.selected {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        .save-button {
            background: #3b82f6;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .save-button:hover {
            background: #2563eb;
        }

        /* Text colors */
        .text-gray-900 {
            color: var(--text-primary);
        }

        .text-gray-500 {
            color: var(--text-secondary);
        }

        .text-gray-600 {
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo and Navigation -->
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="text-xl font-bold text-gray-900">Medcapsule</a>
                    <nav class="ml-8 space-x-8">
                        <a href="{{ route('home') }}" class="nav-link">Home</a>
                        <a href="{{ route('courses.index') }}" class="nav-link">Courses</a>
                        <a href="{{ route('mistakes.index') }}" class="nav-link">Mistakes</a>
                        <a href="{{ route('settings.index') }}" class="nav-link active">Settings</a>
                    </nav>
                </div>

                <!-- Profile Section -->
                <div class="relative">
                    <div class="profile-circle" id="profileToggle">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div class="profile-dropdown" id="profileDropdown">
                        <a href="{{ route('home') }}">Profile</a>
                        <a href="{{ route('settings.index') }}">Settings</a>
                        <div class="divider"></div>
                        <a href="#" class="logout">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Settings</h1>
            
            <!-- Appearance -->
            <div class="settings-section">
                <h2>Appearance</h2>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-medium text-gray-900">Dark Mode</h3>
                        <p class="text-sm text-gray-500">Switch between light and dark theme</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="darkMode">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <!-- Notifications -->
            <div class="settings-section">
                <h2>Notifications</h2>
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-medium text-gray-900">Daily Study Reminder</h3>
                            <p class="text-sm text-gray-500">Get reminded to study every day</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="dailyReminder">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-medium text-gray-900">Weekly Progress Report</h3>
                            <p class="text-sm text-gray-500">Receive a weekly summary of your progress</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="weeklyReminder">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Study Preferences -->
            <div class="settings-section">
                <h2>Study Preferences</h2>
                <div>
                    <h3 class="font-medium text-gray-900 mb-4">Preferred Study Session Duration</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="duration-option" data-duration="15">15 minutes</div>
                        <div class="duration-option" data-duration="30">30 minutes</div>
                        <div class="duration-option" data-duration="45">45 minutes</div>
                        <div class="duration-option" data-duration="60">1 hour</div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end">
                <button class="save-button" id="saveSettings">Save Changes</button>
            </div>
        </div>
    </main>

    <script>
        // Load saved settings
        function loadSettings() {
            const settings = JSON.parse(localStorage.getItem('userSettings') || '{}');
            
            // Set dark mode
            document.documentElement.setAttribute('data-theme', settings.dark_mode ? 'dark' : 'light');
            document.getElementById('darkMode').checked = settings.dark_mode || false;
            
            // Set notification preferences
            document.getElementById('dailyReminder').checked = settings.daily_reminder || false;
            document.getElementById('weeklyReminder').checked = settings.weekly_reminder || false;
            
            // Set study duration
            const duration = settings.study_duration || 30;
            document.querySelectorAll('.duration-option').forEach(option => {
                if (option.dataset.duration === duration.toString()) {
                    option.classList.add('selected');
                } else {
                    option.classList.remove('selected');
                }
            });
        }

        // Save settings
        function saveSettings() {
            const settings = {
                dark_mode: document.getElementById('darkMode').checked,
                daily_reminder: document.getElementById('dailyReminder').checked,
                weekly_reminder: document.getElementById('weeklyReminder').checked,
                study_duration: parseInt(document.querySelector('.duration-option.selected')?.dataset.duration || '30')
            };

            // Save to localStorage
            localStorage.setItem('userSettings', JSON.stringify(settings));

            // Apply dark mode immediately
            document.documentElement.setAttribute('data-theme', settings.dark_mode ? 'dark' : 'light');

            // Save to server
            fetch('{{ route("settings.update") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(settings)
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error('Failed to save settings');
                }
            })
            .catch(error => {
                console.error('Error saving settings:', error);
            });
        }

        // Dark mode toggle
        document.getElementById('darkMode').addEventListener('change', (e) => {
            const theme = e.target.checked ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', theme);
            saveSettings();
        });

        // Study duration selection
        const durationOptions = document.querySelectorAll('.duration-option');
        durationOptions.forEach(option => {
            option.addEventListener('click', () => {
                durationOptions.forEach(opt => opt.classList.remove('selected'));
                option.classList.add('selected');
                saveSettings();
            });
        });

        // Notification toggles
        document.getElementById('dailyReminder').addEventListener('change', saveSettings);
        document.getElementById('weeklyReminder').addEventListener('change', saveSettings);

        // Save button
        document.getElementById('saveSettings').addEventListener('click', saveSettings);

        // Load settings on page load
        loadSettings();

        // Profile dropdown toggle
        const profileToggle = document.getElementById('profileToggle');
        const profileDropdown = document.getElementById('profileDropdown');

        profileToggle.addEventListener('click', () => {
            profileDropdown.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!profileToggle.contains(e.target) && !profileDropdown.contains(e.target)) {
                profileDropdown.classList.remove('active');
            }
        });
    </script>
</body>
</html> 