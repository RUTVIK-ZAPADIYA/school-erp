<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (
    (!isset($_SESSION['teacher_id']) || !isset($_SESSION['teacher_name']))
    && isset($_SESSION['user_id'])
    && (string) ($_SESSION['role'] ?? '') === 'teacher'
) {
    $_SESSION['teacher_id'] = (int) $_SESSION['user_id'];
    $_SESSION['teacher_name'] = (string) ($_SESSION['name'] ?? 'Teacher');
}

if (
    !isset($_SESSION['teacher_id'])
    || (string) ($_SESSION['role'] ?? '') !== 'teacher'
) {
    header('Location: ../login.php');
    exit();
}

function teacher_auth_table_exists($conn, $tableName)
{
    if (!$conn instanceof mysqli) {
        return false;
    }

    $safeTable = $conn->real_escape_string($tableName);
    $result = $conn->query("SHOW TABLES LIKE '{$safeTable}'");

    return $result && $result->num_rows > 0;
}

function teacher_auth_column_exists($conn, $tableName, $columnName)
{
    if (!teacher_auth_table_exists($conn, $tableName)) {
        return false;
    }

    $safeTable = $conn->real_escape_string($tableName);
    $safeColumn = $conn->real_escape_string($columnName);
    $result = $conn->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");

    return $result && $result->num_rows > 0;
}

function teacher_auth_sanitize_ids(array $ids)
{
    $cleanIds = [];
    foreach ($ids as $id) {
        $intId = (int) $id;
        if ($intId > 0) {
            $cleanIds[] = $intId;
        }
    }

    $cleanIds = array_values(array_unique($cleanIds));
    if (empty($cleanIds)) {
        return [0];
    }

    return $cleanIds;
}

function teacher_auth_resolve_context($conn)
{
    $sessionTeacherId = (int) ($_SESSION['teacher_id'] ?? 0);
    $sessionUserId = (int) ($_SESSION['user_id'] ?? 0);
    $sessionTeacherProfileId = (int) ($_SESSION['teacher_profile_id'] ?? 0);

    $resolvedUserId = $sessionUserId > 0 ? $sessionUserId : $sessionTeacherId;
    $resolvedTeacherProfileId = $sessionTeacherProfileId;
    $resolvedTeacherName = (string) ($_SESSION['teacher_name'] ?? $_SESSION['name'] ?? 'Teacher');

    if (teacher_auth_table_exists($conn, 'teachers')) {
        $hasUserIdColumn = teacher_auth_column_exists($conn, 'teachers', 'user_id');
        $hasNameColumn = teacher_auth_column_exists($conn, 'teachers', 'name');

        if ($hasUserIdColumn && $resolvedUserId > 0) {
            $selectColumns = 'id, user_id';
            if ($hasNameColumn) {
                $selectColumns .= ', name';
            }

            $teacherByUserStmt = $conn->prepare("SELECT {$selectColumns} FROM teachers WHERE user_id = ? LIMIT 1");
            if ($teacherByUserStmt) {
                $teacherByUserStmt->bind_param('i', $resolvedUserId);
                $teacherByUserStmt->execute();
                $teacherByUserResult = $teacherByUserStmt->get_result();
                $teacherByUserRow = $teacherByUserResult ? $teacherByUserResult->fetch_assoc() : null;
                if ($teacherByUserRow) {
                    $resolvedTeacherProfileId = (int) ($teacherByUserRow['id'] ?? 0);
                    $resolvedUserId = (int) ($teacherByUserRow['user_id'] ?? $resolvedUserId);
                    if ($hasNameColumn) {
                        $teacherNameCandidate = trim((string) ($teacherByUserRow['name'] ?? ''));
                        if ($teacherNameCandidate !== '') {
                            $resolvedTeacherName = $teacherNameCandidate;
                        }
                    }
                }
                $teacherByUserStmt->close();
            }
        }

        if ($sessionTeacherId > 0 && ($resolvedTeacherProfileId <= 0 || $resolvedUserId <= 0)) {
            $selectColumns = 'id';
            if ($hasUserIdColumn) {
                $selectColumns .= ', user_id';
            }
            if ($hasNameColumn) {
                $selectColumns .= ', name';
            }

            $teacherByIdStmt = $conn->prepare("SELECT {$selectColumns} FROM teachers WHERE id = ? LIMIT 1");
            if ($teacherByIdStmt) {
                $teacherByIdStmt->bind_param('i', $sessionTeacherId);
                $teacherByIdStmt->execute();
                $teacherByIdResult = $teacherByIdStmt->get_result();
                $teacherByIdRow = $teacherByIdResult ? $teacherByIdResult->fetch_assoc() : null;
                if ($teacherByIdRow) {
                    $resolvedTeacherProfileId = (int) ($teacherByIdRow['id'] ?? $resolvedTeacherProfileId);
                    if ($hasUserIdColumn && (int) ($teacherByIdRow['user_id'] ?? 0) > 0) {
                        $resolvedUserId = (int) $teacherByIdRow['user_id'];
                    }
                    if ($hasNameColumn) {
                        $teacherNameCandidate = trim((string) ($teacherByIdRow['name'] ?? ''));
                        if ($teacherNameCandidate !== '') {
                            $resolvedTeacherName = $teacherNameCandidate;
                        }
                    }
                }
                $teacherByIdStmt->close();
            }
        }
    }

    if ($resolvedUserId <= 0) {
        $resolvedUserId = $sessionTeacherId > 0 ? $sessionTeacherId : $sessionUserId;
    }

    $teacherIds = teacher_auth_sanitize_ids([
        $resolvedUserId,
        $resolvedTeacherProfileId,
        $sessionTeacherId,
        $sessionUserId,
    ]);

    if ($resolvedUserId > 0) {
        $_SESSION['user_id'] = $resolvedUserId;
        $_SESSION['teacher_id'] = $resolvedUserId;
    }
    if ($resolvedTeacherProfileId > 0) {
        $_SESSION['teacher_profile_id'] = $resolvedTeacherProfileId;
    }
    if ($resolvedTeacherName !== '') {
        $_SESSION['teacher_name'] = $resolvedTeacherName;
    }

    return [
        'user_id' => $resolvedUserId,
        'teacher_profile_id' => $resolvedTeacherProfileId,
        'teacher_name' => $resolvedTeacherName,
        'teacher_ids' => $teacherIds,
        'teacher_ids_sql' => implode(',', $teacherIds),
    ];
}

function teacher_auth_class_owner_id($conn, $classId, array $teacherIds)
{
    $classId = (int) $classId;
    if ($classId <= 0) {
        return 0;
    }

    if (!teacher_auth_table_exists($conn, 'classes') || !teacher_auth_column_exists($conn, 'classes', 'teacher_id')) {
        return 0;
    }

    $safeTeacherIds = teacher_auth_sanitize_ids($teacherIds);
    $teacherIdSql = implode(',', $safeTeacherIds);

    $stmt = $conn->prepare("SELECT teacher_id FROM classes WHERE id = ? AND teacher_id IN ({$teacherIdSql}) LIMIT 1");
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param('i', $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return (int) ($row['teacher_id'] ?? 0);
}

function teacher_auth_first_existing_column($conn, $tableName, array $candidates)
{
    foreach ($candidates as $candidate) {
        if (teacher_auth_column_exists($conn, $tableName, $candidate)) {
            return $candidate;
        }
    }

    return null;
}

function teacher_auth_class_label_by_id($conn, $classId)
{
    $classId = (int) $classId;
    if ($classId <= 0 || !teacher_auth_table_exists($conn, 'classes')) {
        return '';
    }

    $selectColumns = [];
    if (teacher_auth_column_exists($conn, 'classes', 'name')) {
        $selectColumns[] = 'name';
    }
    if (teacher_auth_column_exists($conn, 'classes', 'class_name')) {
        $selectColumns[] = 'class_name';
    }
    if (empty($selectColumns)) {
        return '';
    }

    $selectParts = [];
    foreach ($selectColumns as $selectColumn) {
        $selectParts[] = "`{$selectColumn}`";
    }

    $stmt = $conn->prepare('SELECT ' . implode(', ', $selectParts) . ' FROM classes WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return '';
    }

    $stmt->bind_param('i', $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        return '';
    }

    foreach (['name', 'class_name'] as $columnName) {
        $value = trim((string) ($row[$columnName] ?? ''));
        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

function teacher_auth_student_class_where_sql($conn, $studentAlias, $classId, $classLabel = '')
{
    $alias = trim((string) $studentAlias);
    if ($alias === '') {
        $alias = 'students';
    }

    $classId = (int) $classId;
    $parts = [];
    $hasClassIdColumn = teacher_auth_column_exists($conn, 'students', 'class_id');

    if ($classId > 0 && $hasClassIdColumn) {
        $parts[] = "{$alias}.class_id = {$classId}";
    }

    if (teacher_auth_column_exists($conn, 'students', 'class')) {
        $normalizedStudentClass = "LOWER(REPLACE(REPLACE(TRIM(COALESCE({$alias}.`class`, '')), ' ', ''), '-', ''))";
        $classCandidates = [];

        $trimmedLabel = trim((string) $classLabel);
        if ($trimmedLabel !== '') {
            $classCandidates[] = $trimmedLabel;
        }

        if ($classId > 0) {
            $classCandidates[] = (string) $classId;
        }

        $classCandidates = array_values(array_unique($classCandidates));
        foreach ($classCandidates as $candidate) {
            $normalizedCandidate = strtolower(str_replace([' ', '-'], '', trim((string) $candidate)));
            if ($normalizedCandidate === '') {
                continue;
            }

            $safeCandidate = $conn->real_escape_string($normalizedCandidate);
            $fallbackCondition = "{$normalizedStudentClass} = '{$safeCandidate}'";

            if ($hasClassIdColumn) {
                $fallbackCondition = "(COALESCE({$alias}.class_id, 0) = 0 AND {$fallbackCondition})";
            }

            $parts[] = $fallbackCondition;
        }
    }

    if (empty($parts)) {
        return '1=0';
    }

    return '(' . implode(' OR ', $parts) . ')';
}
