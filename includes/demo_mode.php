<?php
// Static presentation data used when the application is deployed without MySQL.
if (!function_exists('school_erp_demo_accounts')) {
    function school_erp_demo_accounts()
    {
        return [
            'admin' => ['id' => 1, 'username' => 'admin', 'password' => 'admin123', 'role' => 'admin', 'name' => 'Demo Administrator', 'email' => 'admin@demo-school.test'],
            'teacher' => ['id' => 2, 'username' => 'teacher', 'password' => 'teacher123', 'role' => 'teacher', 'name' => 'Demo Teacher', 'email' => 'teacher@demo-school.test'],
            'student' => ['id' => 3, 'username' => 'student', 'password' => 'student123', 'role' => 'student', 'name' => 'Demo Student', 'email' => 'student@demo-school.test'],
        ];
    }
}

if (!function_exists('school_erp_demo_find_account')) {
    function school_erp_demo_find_account($username, $password)
    {
        $needle = strtolower(trim((string) $username));
        foreach (school_erp_demo_accounts() as $account) {
            if (($needle === strtolower($account['username']) || $needle === strtolower($account['email']))
                && hash_equals($account['password'], (string) $password)) {
                return $account;
            }
        }
        return null;
    }
}

if (!function_exists('school_erp_demo_dashboard_data')) {
    function school_erp_demo_dashboard_data($role)
    {
        $data = [
            'admin' => ['students' => 248, 'teachers' => 18, 'fees_paid' => 1845000, 'fees_pending' => 326000, 'attendance' => 92.4, 'enrollments' => 14, 'previous_enrollments' => 11],
            'teacher' => ['total_students' => 32, 'total_assignments' => 12, 'graded_submissions' => 27, 'today_attendance' => 29],
            'student' => ['attendance' => 42, 'average_marks' => 86.5, 'pending_fees' => 12500, 'leave_applications' => 1, 'assignments' => 6],
        ];
        return $data[$role] ?? [];
    }
}