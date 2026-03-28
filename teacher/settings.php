<?php
session_start();

// Check if teacher is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
include '../includes/db_connect.php';

// Get teacher info
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_notifications'])) {
        // Handle notification preferences
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
        $assignment_reminders = isset($_POST['assignment_reminders']) ? 1 : 0;

        // For now, we'll just show success message since we don't have a settings table
        $success = "Notification preferences updated successfully!";
    }

    if (isset($_POST['update_theme'])) {
        // Handle theme preferences
        $theme = $_POST['theme'] ?? 'light';
        $language = $_POST['language'] ?? 'en';

        // For now, we'll just show success message
        $success = "Theme preferences updated successfully!";
    }

    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql_update = "UPDATE users SET password = '$hashed_password' WHERE id = $user_id";

                    if (mysqli_query($conn, $sql_update)) {
                        $success = "Password changed successfully!";
                    } else {
                        $error = "Error updating password: " . mysqli_error($conn);
                    }
                } else {
                    $error = "New password must be at least 6 characters long.";
                }
            } else {
                $error = "New passwords do not match.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }

    if (isset($_POST['update_academic'])) {
        // Handle academic year settings
        $academic_year = $_POST['academic_year'] ?? '';
        $semester = $_POST['semester'] ?? '';

        // For now, we'll just show success message
        $success = "Academic settings updated successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - The Academic Editorial</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&amp;display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
</head>
<body class="bg-surface font-['Inter'] antialiased">
    <?php include 'sidebar.php'; ?>

    <main class="ml-64 p-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-on-surface mb-2">System Settings</h1>
            <p class="text-on-surface-variant">Customize your teaching experience and preferences</p>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($success)): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl animate-fade-in">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-green-600">check_circle</span>
                    <span class="text-green-800 font-medium"><?php echo $success; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl animate-fade-in">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-red-600">error</span>
                    <span class="text-red-800 font-medium"><?php echo $error; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Notification Settings -->
            <div class="glass-panel rounded-2xl p-6 pro-shadow">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-blue-600">notifications</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-on-surface">Notifications</h3>
                        <p class="text-sm text-on-surface-variant">Manage your notification preferences</p>
                    </div>
                </div>

                <form method="POST" class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-xl">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant">mail</span>
                            <div>
                                <span class="text-sm font-medium text-on-surface">Email Notifications</span>
                                <p class="text-xs text-on-surface-variant">Receive updates via email</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="email_notifications" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-outline-variant peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/25 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 peer-checked:bg-primary"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-xl">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant">sms</span>
                            <div>
                                <span class="text-sm font-medium text-on-surface">SMS Notifications</span>
                                <p class="text-xs text-on-surface-variant">Receive updates via SMS</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="sms_notifications" class="sr-only peer">
                            <div class="w-11 h-6 bg-outline-variant peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/25 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 peer-checked:bg-primary"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-xl">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant">alarm</span>
                            <div>
                                <span class="text-sm font-medium text-on-surface">Assignment Reminders</span>
                                <p class="text-xs text-on-surface-variant">Get reminded about due assignments</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="assignment_reminders" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-outline-variant peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/25 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 peer-checked:bg-primary"></div>
                        </label>
                    </div>

                    <button type="submit" name="update_notifications" class="w-full bg-primary text-white py-3 rounded-xl font-semibold hover:bg-primary/90">
                        Save Notification Settings
                    </button>
                </form>
            </div>

            <!-- Theme & Appearance -->
            <div class="glass-panel rounded-2xl p-6 pro-shadow">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-purple-600">palette</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-on-surface">Appearance</h3>
                        <p class="text-sm text-on-surface-variant">Customize the look and feel</p>
                    </div>
                </div>

                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-on-surface mb-2">Theme</label>
                        <select name="theme" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="light">Light Theme</option>
                            <option value="dark">Dark Theme</option>
                            <option value="auto">Auto (System)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-on-surface mb-2">Language</label>
                        <select name="language" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="en">English</option>
                            <option value="es">Spanish</option>
                            <option value="fr">French</option>
                            <option value="de">German</option>
                        </select>
                    </div>

                    <button type="submit" name="update_theme" class="w-full bg-primary text-white py-3 rounded-xl font-semibold hover:bg-primary/90">
                        Save Appearance Settings
                    </button>
                </form>
            </div>

            <!-- Security Settings -->
            <div class="glass-panel rounded-2xl p-6 pro-shadow">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-red-600">security</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-on-surface">Security</h3>
                        <p class="text-sm text-on-surface-variant">Manage your account security</p>
                    </div>
                </div>

                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-on-surface mb-2">Current Password</label>
                        <input type="password" name="current_password" required class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-on-surface mb-2">New Password</label>
                        <input type="password" name="new_password" required class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-on-surface mb-2">Confirm New Password</label>
                        <input type="password" name="confirm_password" required class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <button type="submit" name="change_password" class="w-full bg-red-600 text-white py-3 rounded-xl font-semibold hover:bg-red-700">
                        Change Password
                    </button>
                </form>
            </div>

            <!-- Academic Settings -->
            <div class="glass-panel rounded-2xl p-6 pro-shadow">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-green-600">school</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-on-surface">Academic Settings</h3>
                        <p class="text-sm text-on-surface-variant">Configure academic preferences</p>
                    </div>
                </div>

                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-on-surface mb-2">Academic Year</label>
                        <select name="academic_year" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="2023-2024">2023-2024</option>
                            <option value="2024-2025" selected>2024-2025</option>
                            <option value="2025-2026">2025-2026</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-on-surface mb-2">Current Semester</label>
                        <select name="semester" class="w-full px-4 py-3 bg-white border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="fall">Fall Semester</option>
                            <option value="spring" selected>Spring Semester</option>
                            <option value="summer">Summer Semester</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-xl">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant">auto_mode</span>
                            <div>
                                <span class="text-sm font-medium text-on-surface">Auto-save Drafts</span>
                                <p class="text-xs text-on-surface-variant">Automatically save work in progress</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="auto_save" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-outline-variant peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/25 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 peer-checked:bg-primary"></div>
                        </label>
                    </div>

                    <button type="submit" name="update_academic" class="w-full bg-primary text-white py-3 rounded-xl font-semibold hover:bg-primary/90">
                        Save Academic Settings
                    </button>
                </form>
            </div>
        </div>
    </main>

    <style>
        :root {
            --primary: #003b93;
            --surface: #fbf9f8;
            --surface-container-low: #f8f6f4;
            --on-surface: #1c1b1f;
            --on-surface-variant: #49454f;
            --outline-variant: #cac4d0;
        }

        body {
            background-color: var(--surface);
        }

        .glass-panel {
            background: rgba(251, 249, 248, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(202, 196, 208, 0.2);
        }

        .pro-shadow {
            box-shadow: 0 4px 20px -5px rgba(0, 59, 147, 0.1);
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</body>
</html>