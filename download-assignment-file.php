<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/assignment_file_helper.php';

function assignment_download_fail($statusCode, $message)
{
    http_response_code((int) $statusCode);
    echo htmlspecialchars((string) $message);
    exit();
}

function assignment_download_stream_file($absolutePath, $downloadName)
{
    if (!is_file($absolutePath)) {
        assignment_download_fail(404, 'File not found.');
    }

    $mimeType = 'application/octet-stream';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $detectedType = finfo_file($finfo, $absolutePath);
            if (is_string($detectedType) && $detectedType !== '') {
                $mimeType = $detectedType;
            }
            finfo_close($finfo);
        }
    }

    $safeName = trim(str_replace(["\r", "\n", '"'], '', (string) $downloadName));
    if ($safeName === '') {
        $safeName = basename($absolutePath);
    }

    if (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $safeName . '"');
    header('Content-Length: ' . (string) filesize($absolutePath));
    header('X-Content-Type-Options: nosniff');

    readfile($absolutePath);
    exit();
}

$role = (string) ($_SESSION['role'] ?? '');
if (!in_array($role, ['teacher', 'student'], true)) {
    assignment_download_fail(403, 'Unauthorized access.');
}

$type = strtolower(trim((string) ($_GET['type'] ?? '')));
$recordId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!in_array($type, ['assignment', 'submission'], true) || $recordId <= 0) {
    assignment_download_fail(400, 'Invalid download request.');
}

$relativePath = '';

if ($role === 'teacher') {
    require_once __DIR__ . '/teacher/auth.php';

    $teacherContext = teacher_auth_resolve_context($conn);
    $teacherIds = teacher_auth_sanitize_ids((array) ($teacherContext['teacher_ids'] ?? []));
    $teacherIdsSql = implode(',', $teacherIds);

    if ($type === 'assignment') {
        $stmt = $conn->prepare("SELECT file_path FROM assignments WHERE id = ? AND teacher_id IN ({$teacherIdsSql}) LIMIT 1");
        if (!$stmt) {
            assignment_download_fail(500, 'Unable to process request.');
        }

        $stmt->bind_param('i', $recordId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        $relativePath = (string) ($row['file_path'] ?? '');
    }

    if ($type === 'submission') {
        $stmt = $conn->prepare("SELECT sub.file_path
            FROM assignment_submissions sub
            INNER JOIN assignments a ON a.id = sub.assignment_id
            WHERE sub.id = ?
              AND a.teacher_id IN ({$teacherIdsSql})
            LIMIT 1");
        if (!$stmt) {
            assignment_download_fail(500, 'Unable to process request.');
        }

        $stmt->bind_param('i', $recordId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        $relativePath = (string) ($row['file_path'] ?? '');
    }
}

if ($role === 'student') {
    require_once __DIR__ . '/student/auth.php';

    $studentIds = array_map('intval', student_auth_student_ids());
    $studentIds = array_values(array_filter(array_unique($studentIds), function ($value) {
        return $value > 0;
    }));
    if (empty($studentIds)) {
        assignment_download_fail(403, 'Student profile not found.');
    }

    if ($type === 'submission') {
        $submissionFilter = student_auth_student_id_filter_sql('sub.student_id');
        $sql = "SELECT sub.file_path
            FROM assignment_submissions sub
            WHERE sub.id = ?
              AND {$submissionFilter['sql']}
            LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            assignment_download_fail(500, 'Unable to process request.');
        }

        $types = 'i' . (string) $submissionFilter['types'];
        $params = array_merge([$recordId], (array) $submissionFilter['params']);
        if (!student_auth_bind_dynamic_params($stmt, $types, $params)) {
            $stmt->close();
            assignment_download_fail(500, 'Unable to process request.');
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        $relativePath = (string) ($row['file_path'] ?? '');
    }

    if ($type === 'assignment') {
        $studentClassIds = [];
        $studentClassLabels = [];
        $studentIdsSql = implode(',', $studentIds);

        if (school_erp_table_exists($conn, 'students')) {
            $classQuery = "SELECT class_id, class FROM students WHERE id IN ({$studentIdsSql})";
            if (school_erp_column_exists($conn, 'students', 'user_id')) {
                $classQuery .= " OR user_id IN ({$studentIdsSql})";
            }

            $classResult = $conn->query($classQuery);
            while ($classResult && ($classRow = $classResult->fetch_assoc())) {
                $classId = (int) ($classRow['class_id'] ?? 0);
                if ($classId > 0) {
                    $studentClassIds[] = $classId;
                }

                $classLabel = trim((string) ($classRow['class'] ?? ''));
                if ($classLabel !== '') {
                    $studentClassLabels[] = strtolower(str_replace([' ', '-'], '', $classLabel));
                }
            }
        }

        $studentClassIds = array_values(array_unique(array_filter(array_map('intval', $studentClassIds), function ($value) {
            return $value > 0;
        })));
        $studentClassLabels = array_values(array_unique(array_filter($studentClassLabels, function ($value) {
            return $value !== '';
        })));

        $classConditions = [];
        if (!empty($studentClassIds) && school_erp_column_exists($conn, 'assignments', 'class_id')) {
            $classConditions[] = 'a.class_id IN (' . implode(',', $studentClassIds) . ')';
        }

        if (school_erp_table_exists($conn, 'classes') && !empty($studentClassLabels)) {
            $classExpr = "CONCAT('Class ', c.id)";
            if (school_erp_column_exists($conn, 'classes', 'name') && school_erp_column_exists($conn, 'classes', 'class_name')) {
                $classExpr = "COALESCE(NULLIF(c.name, ''), c.class_name, CONCAT('Class ', c.id))";
            } elseif (school_erp_column_exists($conn, 'classes', 'name')) {
                $classExpr = 'c.name';
            } elseif (school_erp_column_exists($conn, 'classes', 'class_name')) {
                $classExpr = 'c.class_name';
            }

            $normalizedExpr = "LOWER(REPLACE(REPLACE(TRIM({$classExpr}), ' ', ''), '-', ''))";
            foreach ($studentClassLabels as $classLabel) {
                $safeClassLabel = $conn->real_escape_string($classLabel);
                $classConditions[] = "{$normalizedExpr} = '{$safeClassLabel}'";
            }
        }

        if (empty($classConditions)) {
            assignment_download_fail(403, 'Student class information is missing.');
        }

        $classWhere = '(' . implode(' OR ', $classConditions) . ')';
        $sql = "SELECT a.file_path
            FROM assignments a
            LEFT JOIN classes c ON a.class_id = c.id
            WHERE a.id = ?
              AND {$classWhere}
            LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            assignment_download_fail(500, 'Unable to process request.');
        }

        $stmt->bind_param('i', $recordId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        $relativePath = (string) ($row['file_path'] ?? '');
    }
}

$absolutePath = assignment_file_absolute_path($relativePath);
if ($absolutePath === null || !is_file($absolutePath)) {
    assignment_download_fail(404, 'Requested file was not found.');
}

$downloadName = assignment_file_download_name($relativePath);
assignment_download_stream_file($absolutePath, $downloadName);
