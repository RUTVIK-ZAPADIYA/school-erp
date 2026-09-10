<?php
if (!class_exists('SchoolErpDemoResult')) {
    class SchoolErpDemoResult
    {
        public $num_rows = 0;
        private $rows = [];
        private $position = 0;

        public function __construct(array $rows = [])
        {
            $this->rows = array_values($rows);
            $this->num_rows = count($this->rows);
        }

        public function fetch_assoc()
        {
            if (!isset($this->rows[$this->position])) return null;
            return $this->rows[$this->position++];
        }

        public function fetch_row()
        {
            $row = $this->fetch_assoc();
            return is_array($row) ? array_values($row) : null;
        }

        public function fetch_array($resultType = MYSQLI_BOTH)
        {
            $row = $this->fetch_assoc();
            if (!is_array($row)) return null;
            return $resultType === MYSQLI_NUM ? array_values($row) : $row;
        }

        public function free() { return true; }
    }
}

if (!class_exists('SchoolErpDemoStatement')) {
    class SchoolErpDemoStatement
    {
        private $sql;
        public $affected_rows = 0;
        public $insert_id = 0;
        public $error = '';

        public function __construct($sql) { $this->sql = (string) $sql; }
        public function bind_param(...$arguments) { return true; }
        public function bind_result(...$arguments) { return true; }
        public function execute() { return true; }
        public function get_result() { return new SchoolErpDemoResult(school_erp_demo_rows_for_sql($this->sql)); }
        public function fetch() { return false; }
        public function store_result() { return true; }
        public function close() { return true; }
    }
}

if (!class_exists('SchoolErpDemoConnection')) {
    class SchoolErpDemoConnection
    {
        public $error = '';
        public $errno = 0;
        public $insert_id = 0;
        public $affected_rows = 0;

        public function query($sql) { return new SchoolErpDemoResult(school_erp_demo_rows_for_sql($sql)); }
        public function prepare($sql) { return new SchoolErpDemoStatement($sql); }
        public function real_escape_string($value) { return addslashes((string) $value); }
        public function set_charset($charset) { return true; }
        public function select_db($database) { return true; }
        public function begin_transaction() { return true; }
        public function commit() { return true; }
        public function rollback() { return true; }
        public function multi_query($sql) { return true; }
        public function close() { return true; }
    }
}

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

if (!function_exists('school_erp_demo_rows_for_sql')) {
    function school_erp_demo_rows_for_sql($sql)
    {
        $normalizedSql = strtolower((string) $sql);
        if (strpos($normalizedSql, 'show tables') !== false || strpos($normalizedSql, 'show columns') !== false) {
            return [['Field' => 'id', 'Tables_in_school_erp' => 'demo']];
        }
        if (strpos($normalizedSql, 'count(') !== false || strpos($normalizedSql, 'sum(') !== false || strpos($normalizedSql, 'avg(') !== false) {
            return [['total' => 12, 'count' => 12, 'avg_marks' => 86.5, 'total_fees' => 12500, 0 => 12]];
        }

        $rows = [
            'users' => [
                ['id' => 1, 'user_id' => 1, 'username' => 'admin', 'name' => 'Demo Administrator', 'email' => 'admin@demo-school.test', 'role' => 'admin', 'status' => 'Active', 'phone' => '+91 90000 00001', 'created_at' => '2026-04-01'],
                ['id' => 2, 'user_id' => 2, 'username' => 'teacher', 'name' => 'Demo Teacher', 'email' => 'teacher@demo-school.test', 'role' => 'teacher', 'status' => 'Active', 'phone' => '+91 90000 00002', 'created_at' => '2026-04-01'],
                ['id' => 3, 'user_id' => 3, 'username' => 'student', 'name' => 'Demo Student', 'email' => 'student@demo-school.test', 'role' => 'student', 'status' => 'Active', 'phone' => '+91 90000 00003', 'created_at' => '2026-04-01'],
            ],
            'teachers' => [
                ['id' => 2, 'user_id' => 2, 'name' => 'Demo Teacher', 'username' => 'teacher', 'email' => 'teacher@demo-school.test', 'phone' => '+91 90000 00002', 'subject' => 'Mathematics', 'qualification' => 'M.Sc. Mathematics', 'experience' => 8, 'status' => 'Active', 'created_at' => '2026-04-01'],
                ['id' => 4, 'user_id' => 4, 'name' => 'Anita Sharma', 'username' => 'anita.sharma', 'email' => 'anita@demo-school.test', 'phone' => '+91 90000 00004', 'subject' => 'Science', 'qualification' => 'M.Sc. Physics', 'experience' => 6, 'status' => 'Active', 'created_at' => '2026-04-02'],
            ],
            'classes' => [
                ['id' => 1, 'name' => 'Class 10', 'class_name' => 'Class 10', 'section' => 'A', 'teacher_id' => 2, 'teacher_name' => 'Demo Teacher', 'room_number' => '101', 'status' => 'Active'],
                ['id' => 2, 'name' => 'Class 9', 'class_name' => 'Class 9', 'section' => 'B', 'teacher_id' => 4, 'teacher_name' => 'Anita Sharma', 'room_number' => '202', 'status' => 'Active'],
            ],
            'students' => [
                ['id' => 3, 'user_id' => 3, 'roll_no' => 'STU001', 'name' => 'Demo Student', 'username' => 'student', 'class' => 'Class 10', 'class_id' => 1, 'class_name' => 'Class 10', 'section' => 'A', 'email' => 'student@demo-school.test', 'phone' => '+91 90000 00003', 'status' => 'Active', 'created_at' => '2026-04-01'],
                ['id' => 5, 'user_id' => 5, 'roll_no' => 'STU002', 'name' => 'Riya Patel', 'username' => 'stu002', 'class' => 'Class 10', 'class_id' => 1, 'class_name' => 'Class 10', 'section' => 'A', 'email' => 'riya@demo-school.test', 'phone' => '+91 90000 00005', 'status' => 'Active', 'created_at' => '2026-04-02'],
                ['id' => 6, 'user_id' => 6, 'roll_no' => 'STU003', 'name' => 'Arjun Mehta', 'username' => 'stu003', 'class' => 'Class 9', 'class_id' => 2, 'class_name' => 'Class 9', 'section' => 'B', 'email' => 'arjun@demo-school.test', 'phone' => '+91 90000 00006', 'status' => 'Active', 'created_at' => '2026-04-03'],
            ],
            'subjects' => [
                ['id' => 1, 'name' => 'Mathematics', 'subject_name' => 'Mathematics', 'code' => 'MATH101', 'credits' => 4, 'status' => 'Active', 'teacher_name' => 'Demo Teacher', 'class_name' => 'Class 10'],
                ['id' => 2, 'name' => 'Science', 'subject_name' => 'Science', 'code' => 'SCI101', 'credits' => 4, 'status' => 'Active', 'teacher_name' => 'Anita Sharma', 'class_name' => 'Class 10'],
                ['id' => 3, 'name' => 'English', 'subject_name' => 'English', 'code' => 'ENG101', 'credits' => 3, 'status' => 'Active', 'teacher_name' => 'Demo Teacher', 'class_name' => 'Class 10'],
            ],
            'assignments' => [
                ['id' => 1, 'title' => 'Algebra Practice Set', 'description' => 'Solve the assigned algebra problems.', 'teacher_id' => 2, 'subject_id' => 1, 'class_id' => 1, 'class_name' => 'Class 10', 'subject_name' => 'Mathematics', 'due_date' => date('Y-m-d', strtotime('+2 days')), 'total_marks' => 100, 'total_points' => 100, 'file_path' => null, 'created_at' => date('Y-m-d')],
                ['id' => 2, 'title' => 'Science Lab Report', 'description' => 'Submit your observations and conclusion.', 'teacher_id' => 2, 'subject_id' => 2, 'class_id' => 1, 'class_name' => 'Class 10', 'subject_name' => 'Science', 'due_date' => date('Y-m-d', strtotime('+5 days')), 'total_marks' => 50, 'total_points' => 50, 'file_path' => null, 'created_at' => date('Y-m-d')],
            ],
            'fees' => [
                ['id' => 1, 'student_id' => 3, 'student_user_id' => 3, 'student_name' => 'Demo Student', 'student_class' => 'Class 10', 'amount' => 12500, 'fee_type' => 'Tuition Fee', 'due_date' => date('Y-m-d', strtotime('+20 days')), 'status' => 'pending', 'razorpay_order_id' => null],
                ['id' => 2, 'student_id' => 5, 'student_user_id' => 5, 'student_name' => 'Riya Patel', 'student_class' => 'Class 10', 'amount' => 18000, 'fee_type' => 'Annual Fee', 'due_date' => date('Y-m-d', strtotime('+12 days')), 'status' => 'paid', 'razorpay_order_id' => 'demo_order_002'],
            ],
            'notices' => [
                ['id' => 1, 'title' => 'Parent Teacher Meeting', 'message' => 'The next parent teacher meeting is scheduled for Friday.', 'target_audience' => 'all', 'class_id' => 0, 'publish_date' => date('Y-m-d'), 'expiry_date' => date('Y-m-d', strtotime('+15 days')), 'status' => 'Published', 'created_at' => date('Y-m-d')],
                ['id' => 2, 'title' => 'Mid-term Examination Schedule', 'message' => 'Please review the examination schedule on the notice board.', 'target_audience' => 'students', 'class_id' => 1, 'publish_date' => date('Y-m-d', strtotime('-2 days')), 'expiry_date' => date('Y-m-d', strtotime('+25 days')), 'status' => 'Published', 'created_at' => date('Y-m-d')],
            ],
            'attendance' => [
                ['id' => 1, 'student_id' => 3, 'class_id' => 1, 'teacher_id' => 2, 'subject_id' => 1, 'date' => date('Y-m-d'), 'attendance_date' => date('Y-m-d'), 'status' => 'Present'],
                ['id' => 2, 'student_id' => 5, 'class_id' => 1, 'teacher_id' => 2, 'subject_id' => 1, 'date' => date('Y-m-d'), 'attendance_date' => date('Y-m-d'), 'status' => 'Present'],
            ],
            'grades' => [
                ['id' => 1, 'student_id' => 3, 'student_user_id' => 3, 'subject_id' => 1, 'subject' => 'Mathematics', 'subject_name' => 'Mathematics', 'exam_type' => 'Mid Term', 'obtained_marks' => 86, 'total_marks' => 100, 'grade' => 'A'],
                ['id' => 2, 'student_id' => 3, 'student_user_id' => 3, 'subject_id' => 2, 'subject' => 'Science', 'subject_name' => 'Science', 'exam_type' => 'Mid Term', 'obtained_marks' => 88, 'total_marks' => 100, 'grade' => 'A'],
            ],
            'schedule' => [
                ['id' => 1, 'class_id' => 1, 'subject_id' => 1, 'subject_name' => 'Mathematics', 'teacher_id' => 2, 'teacher_name' => 'Demo Teacher', 'day_of_week' => 'Monday', 'start_time' => '09:00:00', 'end_time' => '10:00:00', 'room' => '101'],
                ['id' => 2, 'class_id' => 1, 'subject_id' => 2, 'subject_name' => 'Science', 'teacher_id' => 2, 'teacher_name' => 'Demo Teacher', 'day_of_week' => 'Tuesday', 'start_time' => '10:00:00', 'end_time' => '11:00:00', 'room' => '101'],
            ],
            'leave_applications' => [
                ['id' => 1, 'application_id' => 1, 'student_id' => 3, 'student_user_id' => 3, 'student_name' => 'Demo Student', 'leave_type' => 'Personal', 'from_date' => date('Y-m-d', strtotime('+7 days')), 'to_date' => date('Y-m-d', strtotime('+8 days')), 'reason' => 'Family event', 'status' => 'Pending', 'created_at' => date('Y-m-d')],
            ],
            'support_tickets' => [
                ['id' => 1, 'student_id' => 3, 'title' => 'Demo support request', 'category' => 'General', 'status' => 'Open', 'message' => 'Please review my request.', 'admin_reply' => null, 'created_at' => date('Y-m-d')],
            ],
        ];

        if (preg_match('/\bfrom\s+[`]?([a-z0-9_]+)[`]?\b/', $normalizedSql, $fromMatch)) {
            $fromTable = $fromMatch[1];
            if (isset($rows[$fromTable])) {
                return $rows[$fromTable];
            }
        }

        foreach ($rows as $table => $tableRows) {
            if (preg_match('/\bjoin\s+[`]?'.$table.'[`]?\b/', $normalizedSql)) {
                return $tableRows;
            }
        }

        return [];
    }
}