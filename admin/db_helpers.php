<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!function_exists('admin_table_exists')) {
    function admin_table_exists($connection, $tableName)
    {
        $safeTable = mysqli_real_escape_string($connection, $tableName);
        $result = mysqli_query($connection, "SHOW TABLES LIKE '{$safeTable}'");

        return $result && mysqli_num_rows($result) > 0;
    }
}

if (!function_exists('admin_column_exists')) {
    function admin_column_exists($connection, $tableName, $columnName)
    {
        if (!admin_table_exists($connection, $tableName)) {
            return false;
        }

        $safeTable = mysqli_real_escape_string($connection, $tableName);
        $safeColumn = mysqli_real_escape_string($connection, $columnName);
        $result = mysqli_query($connection, "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");

        return $result && mysqli_num_rows($result) > 0;
    }
}

if (!function_exists('admin_bind_dynamic_params')) {
    function admin_bind_dynamic_params($stmt, $types, array &$params)
    {
        if ($types === '') {
            return true;
        }

        $bindArgs = [$stmt, $types];
        foreach ($params as $index => &$value) {
            $bindArgs[] = &$value;
        }

        return call_user_func_array('mysqli_stmt_bind_param', $bindArgs);
    }
}

if (!function_exists('admin_scalar_value')) {
    function admin_scalar_value($connection, $sql, $defaultValue = 0)
    {
        $result = mysqli_query($connection, $sql);
        if (!$result) {
            return $defaultValue;
        }

        $row = mysqli_fetch_row($result);
        if (!$row || !isset($row[0])) {
            return $defaultValue;
        }

        return $row[0];
    }
}

if (!function_exists('admin_first_existing_column')) {
    function admin_first_existing_column($connection, $tableName, array $columnCandidates)
    {
        foreach ($columnCandidates as $columnName) {
            if (admin_column_exists($connection, $tableName, $columnName)) {
                return $columnName;
            }
        }

        return null;
    }
}

if (!function_exists('admin_first_non_empty_value')) {
    function admin_first_non_empty_value(array $row, array $keys, $defaultValue = '')
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
        }

        return $defaultValue;
    }
}

if (!function_exists('admin_ensure_column')) {
    function admin_ensure_column($connection, $tableName, $columnName, $definition)
    {
        if (function_exists('ensure_school_erp_column')) {
            ensure_school_erp_column($connection, $tableName, $columnName, $definition);
            return;
        }

        if (!admin_column_exists($connection, $tableName, $columnName)) {
            mysqli_query($connection, "ALTER TABLE `{$tableName}` ADD COLUMN `{$columnName}` {$definition}");
        }
    }
}

if (!function_exists('admin_set_flash')) {
    function admin_set_flash($type, $message)
    {
        $_SESSION['admin_flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }
}

if (!function_exists('admin_pull_flash')) {
    function admin_pull_flash()
    {
        if (!isset($_SESSION['admin_flash'])) {
            return null;
        }

        $flash = $_SESSION['admin_flash'];
        unset($_SESSION['admin_flash']);

        return $flash;
    }
}

if (!function_exists('admin_normalize_status')) {
    function admin_normalize_status($status, $defaultStatus = 'Active')
    {
        $normalized = strtolower(trim((string) $status));
        if ($normalized === 'active') {
            return 'Active';
        }
        if ($normalized === 'inactive') {
            return 'Inactive';
        }
        if ($normalized === 'paid') {
            return 'Paid';
        }
        if ($normalized === 'pending') {
            return 'Pending';
        }
        if ($normalized === 'partial') {
            return 'Partial';
        }
        if ($normalized === 'overdue') {
            return 'Overdue';
        }

        return $defaultStatus;
    }
}
