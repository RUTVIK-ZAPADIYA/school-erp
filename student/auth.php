<?php
// Start student session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Enforce student role
if ((string) ($_SESSION['role'] ?? '') !== 'student') {
    header('Location: ../login.php');
    exit();
}

// Load database connection
require_once __DIR__ . '/../includes/db_connect.php';

if (!function_exists('student_auth_table_exists')) {
    // Verify table present
    function student_auth_table_exists($conn, $tableName)
    {
        if (!$conn instanceof mysqli) {
            return false;
        }

        $safeTable = $conn->real_escape_string($tableName);
        $result = $conn->query("SHOW TABLES LIKE '{$safeTable}'");

        return $result && $result->num_rows > 0;
    }
}

if (!function_exists('student_auth_column_exists')) {
    // Verify column present
    function student_auth_column_exists($conn, $tableName, $columnName)
    {
        if (!student_auth_table_exists($conn, $tableName)) {
            return false;
        }

        $safeTable = $conn->real_escape_string($tableName);
        $safeColumn = $conn->real_escape_string($columnName);
        $result = $conn->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");

        return $result && $result->num_rows > 0;
    }
}

if (!function_exists('student_auth_resolve_context')) {
    // Resolve identity context
    function student_auth_resolve_context($conn)
    {
        $sessionUserId = (int) ($_SESSION['user_id'] ?? 0);
        $legacyStudentId = (int) ($_SESSION['student_id'] ?? 0);
        $sessionProfileId = (int) ($_SESSION['student_profile_id'] ?? 0);

        $resolvedUserId = $sessionUserId > 0 ? $sessionUserId : 0;
        $resolvedStudentId = $sessionProfileId > 0 ? $sessionProfileId : 0;
        $resolvedName = trim((string) ($_SESSION['student_name'] ?? $_SESSION['name'] ?? 'Student'));
        $resolvedRollNo = trim((string) ($_SESSION['student_roll_no'] ?? ''));

        $userEmail = '';
        $userUsername = '';

        // Query users table
        if (student_auth_table_exists($conn, 'users')) {
            if ($resolvedUserId > 0) {
                $userStmt = $conn->prepare("SELECT id, username, name, email FROM users WHERE id = ? AND role = 'student' LIMIT 1");
                if ($userStmt) {
                    $userStmt->bind_param('i', $resolvedUserId);
                    $userStmt->execute();
                    $userResult = $userStmt->get_result();
                    $userRow = $userResult ? $userResult->fetch_assoc() : null;
                    if ($userRow) {
                        $resolvedUserId = (int) ($userRow['id'] ?? $resolvedUserId);
                        $userUsername = trim((string) ($userRow['username'] ?? ''));
                        $userEmail = trim((string) ($userRow['email'] ?? ''));
                        $nameCandidate = trim((string) ($userRow['name'] ?? ''));
                        if ($nameCandidate !== '') {
                            $resolvedName = $nameCandidate;
                        }
                    }
                    $userStmt->close();
                }
            } elseif ($legacyStudentId > 0) {
                $legacyUserStmt = $conn->prepare("SELECT id, username, name, email FROM users WHERE id = ? AND role = 'student' LIMIT 1");
                if ($legacyUserStmt) {
                    $legacyUserStmt->bind_param('i', $legacyStudentId);
                    $legacyUserStmt->execute();
                    $legacyUserResult = $legacyUserStmt->get_result();
                    $legacyUserRow = $legacyUserResult ? $legacyUserResult->fetch_assoc() : null;
                    if ($legacyUserRow) {
                        $resolvedUserId = (int) ($legacyUserRow['id'] ?? 0);
                        $userUsername = trim((string) ($legacyUserRow['username'] ?? ''));
                        $userEmail = trim((string) ($legacyUserRow['email'] ?? ''));
                        $nameCandidate = trim((string) ($legacyUserRow['name'] ?? ''));
                        if ($nameCandidate !== '') {
                            $resolvedName = $nameCandidate;
                        }
                    }
                    $legacyUserStmt->close();
                }
            }
        }

        // Query students table
        $studentRow = null;
        if (student_auth_table_exists($conn, 'students')) {
            if ($resolvedStudentId > 0) {
                $studentByIdStmt = $conn->prepare('SELECT * FROM students WHERE id = ? LIMIT 1');
                if ($studentByIdStmt) {
                    $studentByIdStmt->bind_param('i', $resolvedStudentId);
                    $studentByIdStmt->execute();
                    $studentByIdResult = $studentByIdStmt->get_result();
                    $studentRow = $studentByIdResult ? $studentByIdResult->fetch_assoc() : null;
                    $studentByIdStmt->close();
                }
            }

            if (!$studentRow && student_auth_column_exists($conn, 'students', 'user_id') && $resolvedUserId > 0) {
                $studentByUserStmt = $conn->prepare('SELECT * FROM students WHERE user_id = ? LIMIT 1');
                if ($studentByUserStmt) {
                    $studentByUserStmt->bind_param('i', $resolvedUserId);
                    $studentByUserStmt->execute();
                    $studentByUserResult = $studentByUserStmt->get_result();
                    $studentRow = $studentByUserResult ? $studentByUserResult->fetch_assoc() : null;
                    $studentByUserStmt->close();
                }
            }

            if (!$studentRow && $legacyStudentId > 0) {
                $legacyStudentStmt = $conn->prepare('SELECT * FROM students WHERE id = ? LIMIT 1');
                if ($legacyStudentStmt) {
                    $legacyStudentStmt->bind_param('i', $legacyStudentId);
                    $legacyStudentStmt->execute();
                    $legacyStudentResult = $legacyStudentStmt->get_result();
                    $studentRow = $legacyStudentResult ? $legacyStudentResult->fetch_assoc() : null;
                    $legacyStudentStmt->close();
                }
            }

            if (!$studentRow && $userEmail !== '' && student_auth_column_exists($conn, 'students', 'email')) {
                $studentByEmailStmt = $conn->prepare('SELECT * FROM students WHERE email = ? LIMIT 1');
                if ($studentByEmailStmt) {
                    $studentByEmailStmt->bind_param('s', $userEmail);
                    $studentByEmailStmt->execute();
                    $studentByEmailResult = $studentByEmailStmt->get_result();
                    $studentRow = $studentByEmailResult ? $studentByEmailResult->fetch_assoc() : null;
                    $studentByEmailStmt->close();
                }
            }

            if (!$studentRow && $userUsername !== '' && student_auth_column_exists($conn, 'students', 'username')) {
                $studentByUsernameStmt = $conn->prepare('SELECT * FROM students WHERE username = ? LIMIT 1');
                if ($studentByUsernameStmt) {
                    $studentByUsernameStmt->bind_param('s', $userUsername);
                    $studentByUsernameStmt->execute();
                    $studentByUsernameResult = $studentByUsernameStmt->get_result();
                    $studentRow = $studentByUsernameResult ? $studentByUsernameResult->fetch_assoc() : null;
                    $studentByUsernameStmt->close();
                }
            }

            if (!$studentRow && $userUsername !== '' && student_auth_column_exists($conn, 'students', 'roll_no')) {
                $studentByRollStmt = $conn->prepare('SELECT * FROM students WHERE roll_no = ? LIMIT 1');
                if ($studentByRollStmt) {
                    $studentByRollStmt->bind_param('s', $userUsername);
                    $studentByRollStmt->execute();
                    $studentByRollResult = $studentByRollStmt->get_result();
                    $studentRow = $studentByRollResult ? $studentByRollResult->fetch_assoc() : null;
                    $studentByRollStmt->close();
                }
            }

            if (!$studentRow && $resolvedName !== '') {
                $studentByNameStmt = $conn->prepare('SELECT * FROM students WHERE name = ? ORDER BY id DESC LIMIT 1');
                if ($studentByNameStmt) {
                    $studentByNameStmt->bind_param('s', $resolvedName);
                    $studentByNameStmt->execute();
                    $studentByNameResult = $studentByNameStmt->get_result();
                    $studentRow = $studentByNameResult ? $studentByNameResult->fetch_assoc() : null;
                    $studentByNameStmt->close();
                }
            }

            // Sync resolved student
            if ($studentRow) {
                $resolvedStudentId = (int) ($studentRow['id'] ?? $resolvedStudentId);

                $studentNameCandidate = trim((string) ($studentRow['name'] ?? ''));
                if ($studentNameCandidate !== '') {
                    $resolvedName = $studentNameCandidate;
                }

                $rollCandidate = trim((string) ($studentRow['roll_no'] ?? ''));
                if ($rollCandidate !== '') {
                    $resolvedRollNo = $rollCandidate;
                }

                if (student_auth_column_exists($conn, 'students', 'username')) {
                    $studentUsername = trim((string) ($studentRow['username'] ?? ''));
                    if ($studentUsername === '' && $userUsername !== '') {
                        $syncUsernameStmt = $conn->prepare('UPDATE students SET username = ? WHERE id = ?');
                        if ($syncUsernameStmt) {
                            $syncUsernameStmt->bind_param('si', $userUsername, $resolvedStudentId);
                            $syncUsernameStmt->execute();
                            $syncUsernameStmt->close();
                            $studentUsername = $userUsername;
                        }
                    }
                    if ($studentUsername !== '') {
                        $userUsername = $studentUsername;
                    }
                }

                if (student_auth_column_exists($conn, 'students', 'user_id') && $resolvedUserId > 0 && (int) ($studentRow['user_id'] ?? 0) !== $resolvedUserId) {
                    $syncUserIdStmt = $conn->prepare('UPDATE students SET user_id = ? WHERE id = ?');
                    if ($syncUserIdStmt) {
                        $syncUserIdStmt->bind_param('ii', $resolvedUserId, $resolvedStudentId);
                        $syncUserIdStmt->execute();
                        $syncUserIdStmt->close();
                    }
                }
            }
        }

        // Fill missing ids
        if ($resolvedStudentId <= 0 && $legacyStudentId > 0) {
            $resolvedStudentId = $legacyStudentId;
        }
        if ($resolvedStudentId <= 0 && $resolvedUserId > 0) {
            $resolvedStudentId = $resolvedUserId;
        }

        if ($resolvedName === '') {
            $resolvedName = 'Student';
        }

        // Persist session values
        if ($resolvedUserId > 0) {
            $_SESSION['user_id'] = $resolvedUserId;
            $_SESSION['student_user_id'] = $resolvedUserId;
        }
        $_SESSION['student_profile_id'] = $resolvedStudentId;
        $_SESSION['student_id'] = $resolvedStudentId;
        $_SESSION['student_name'] = $resolvedName;
        $_SESSION['name'] = $resolvedName;
        $_SESSION['student_roll_no'] = $resolvedRollNo;

        return [
            'user_id' => $resolvedUserId,
            'student_id' => $resolvedStudentId,
            'student_name' => $resolvedName,
            'student_roll_no' => $resolvedRollNo,
        ];
    }
}

if (!isset($GLOBALS['student_auth_context']) || !is_array($GLOBALS['student_auth_context'])) {
    // Cache global context
    $GLOBALS['student_auth_context'] = student_auth_resolve_context($conn);
}

if (!function_exists('student_auth_context')) {
    // Read cached context
    function student_auth_context()
    {
        return (array) ($GLOBALS['student_auth_context'] ?? []);
    }
}

if (!function_exists('student_auth_student_ids')) {
    // Build candidate ids
    function student_auth_student_ids()
    {
        $context = student_auth_context();
        $ids = [];

        foreach (['student_id', 'user_id'] as $key) {
            $id = (int) ($context[$key] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        $ids = array_values(array_unique($ids));
        if (empty($ids)) {
            return [0];
        }

        return $ids;
    }
}

if (!function_exists('student_auth_student_id_filter_sql')) {
    // Build SQL filter
    function student_auth_student_id_filter_sql($columnName = 'student_id')
    {
        $ids = student_auth_student_ids();
        $placeholderCount = count($ids);

        if ($placeholderCount <= 1) {
            return [
                'sql' => $columnName . ' = ?',
                'types' => 'i',
                'params' => [(int) ($ids[0] ?? 0)],
            ];
        }

        return [
            'sql' => $columnName . ' IN (' . implode(', ', array_fill(0, $placeholderCount, '?')) . ')',
            'types' => str_repeat('i', $placeholderCount),
            'params' => array_map('intval', $ids),
        ];
    }
}

if (!function_exists('student_auth_bind_dynamic_params')) {
    // Bind dynamic values
    function student_auth_bind_dynamic_params($stmt, $types, array &$params)
    {
        if (!$stmt || $types === '') {
            return false;
        }

        $bindArgs = [$types];
        foreach ($params as $index => &$value) {
            $bindArgs[] = &$value;
        }

        return call_user_func_array([$stmt, 'bind_param'], $bindArgs);
    }
}

if (!function_exists('student_auth_link_filter_sql')) {
    // Build linked filter
    function student_auth_link_filter_sql($conn, $tableName, $studentIdColumn = 'student_id', $studentUserIdColumn = 'student_user_id', $tableAlias = '')
    {
        $ids = student_auth_student_ids();
        $placeholderCount = count($ids);
        $placeholders = implode(', ', array_fill(0, $placeholderCount, '?'));

        $aliasPrefix = trim((string) $tableAlias);
        if ($aliasPrefix !== '') {
            $aliasPrefix = rtrim($aliasPrefix, '.') . '.';
        }

        $baseSql = $aliasPrefix . $studentIdColumn . ' IN (' . $placeholders . ')';
        $types = str_repeat('i', $placeholderCount);
        $params = array_map('intval', $ids);

        if (
            $studentUserIdColumn !== ''
            && student_auth_column_exists($conn, $tableName, $studentUserIdColumn)
        ) {
            $baseSql = '(' . $baseSql . ' OR ' . $aliasPrefix . $studentUserIdColumn . ' IN (' . $placeholders . '))';
            $types .= str_repeat('i', $placeholderCount);
            $params = array_merge($params, array_map('intval', $ids));
        }

        return [
            'sql' => $baseSql,
            'types' => $types,
            'params' => $params,
        ];
    }
}
