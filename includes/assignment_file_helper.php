<?php

if (!function_exists('assignment_file_root_directory')) {
    function assignment_file_root_directory()
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'assignments';
    }
}

if (!function_exists('assignment_file_allowed_extensions')) {
    function assignment_file_allowed_extensions()
    {
        return [
            'pdf',
            'doc',
            'docx',
            'ppt',
            'pptx',
            'xls',
            'xlsx',
            'txt',
            'jpg',
            'jpeg',
            'png',
            'zip',
            'rar',
        ];
    }
}

if (!function_exists('assignment_file_ensure_directory')) {
    function assignment_file_ensure_directory($directory = null)
    {
        $target = $directory ?: assignment_file_root_directory();
        if (is_dir($target)) {
            return true;
        }

        return mkdir($target, 0775, true);
    }
}

if (!function_exists('assignment_file_normalize_name')) {
    function assignment_file_normalize_name($name)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 'file';
        }

        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
        $name = preg_replace('/_+/', '_', $name);
        $name = trim((string) $name, '._-');

        if ($name === '') {
            return 'file';
        }

        return substr($name, 0, 120);
    }
}

if (!function_exists('assignment_file_create_name')) {
    function assignment_file_create_name($uploadedName, $category, $ownerId, $assignmentId)
    {
        $normalizedCategory = assignment_file_normalize_name(strtolower((string) $category));
        $safeName = assignment_file_normalize_name((string) $uploadedName);
        try {
            $token = substr(bin2hex(random_bytes(8)), 0, 16);
        } catch (Throwable $e) {
            $token = substr(md5((string) microtime(true) . '_' . (string) mt_rand()), 0, 16);
        }
        $timestamp = date('YmdHis');

        return $timestamp
            . '_' . $normalizedCategory
            . '_' . max(0, (int) $ownerId)
            . '_' . max(0, (int) $assignmentId)
            . '_' . $token
            . '_' . $safeName;
    }
}

if (!function_exists('assignment_file_validate_upload')) {
    function assignment_file_validate_upload(array $file)
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            return 'Invalid upload request.';
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'File upload failed. Please try again.';
        }

        $size = (int) ($file['size'] ?? 0);
        $maxSizeBytes = 15 * 1024 * 1024;
        if ($size <= 0) {
            return 'Uploaded file is empty.';
        }

        if ($size > $maxSizeBytes) {
            return 'File size must be 15 MB or less.';
        }

        $originalName = (string) ($file['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension === '' || !in_array($extension, assignment_file_allowed_extensions(), true)) {
            return 'Unsupported file type. Allowed: ' . implode(', ', assignment_file_allowed_extensions());
        }

        return null;
    }
}

if (!function_exists('assignment_file_save_upload')) {
    function assignment_file_save_upload(array $file, $category, $ownerId, $assignmentId)
    {
        $validationError = assignment_file_validate_upload($file);
        if ($validationError !== null) {
            return [
                'ok' => false,
                'error' => $validationError,
            ];
        }

        $uploadDir = assignment_file_root_directory();
        if (!assignment_file_ensure_directory($uploadDir)) {
            return [
                'ok' => false,
                'error' => 'Unable to create upload directory.',
            ];
        }

        $targetFileName = assignment_file_create_name((string) ($file['name'] ?? 'file'), $category, $ownerId, $assignmentId);
        $targetAbsolutePath = $uploadDir . DIRECTORY_SEPARATOR . $targetFileName;

        if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $targetAbsolutePath)) {
            return [
                'ok' => false,
                'error' => 'Unable to save uploaded file.',
            ];
        }

        return [
            'ok' => true,
            'relative_path' => 'uploads/assignments/' . $targetFileName,
            'stored_name' => $targetFileName,
        ];
    }
}

if (!function_exists('assignment_file_absolute_path')) {
    function assignment_file_absolute_path($relativePath)
    {
        $relativePath = str_replace('\\', '/', trim((string) $relativePath));
        $prefix = 'uploads/assignments/';
        if ($relativePath === '' || strpos($relativePath, $prefix) !== 0) {
            return null;
        }

        $fileName = basename($relativePath);
        $baseDirectory = realpath(assignment_file_root_directory());
        if ($baseDirectory === false) {
            return null;
        }

        return $baseDirectory . DIRECTORY_SEPARATOR . $fileName;
    }
}

if (!function_exists('assignment_file_delete')) {
    function assignment_file_delete($relativePath)
    {
        $absolutePath = assignment_file_absolute_path($relativePath);
        if ($absolutePath === null || !is_file($absolutePath)) {
            return false;
        }

        return unlink($absolutePath);
    }
}

if (!function_exists('assignment_file_download_name')) {
    function assignment_file_download_name($relativePath)
    {
        $fileName = basename((string) $relativePath);
        $parts = explode('_', $fileName, 6);
        if (count($parts) === 6 && trim($parts[5]) !== '') {
            return $parts[5];
        }

        return $fileName;
    }
}
