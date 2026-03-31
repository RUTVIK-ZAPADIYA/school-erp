<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!function_exists('admin_table_exists')) {
    function admin_table_exists($connection, $tableName)
    {
        $safeTable = $connection->real_escape_string( $tableName);
        $result = $connection->query( "SHOW TABLES LIKE '{$safeTable}'");

        return $result && $result->num_rows > 0;
    }
}

if (!function_exists('admin_column_exists')) {
    function admin_column_exists($connection, $tableName, $columnName)
    {
        if (!admin_table_exists($connection, $tableName)) {
            return false;
        }

        $safeTable = $connection->real_escape_string( $tableName);
        $safeColumn = $connection->real_escape_string( $columnName);
        $result = $connection->query( "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");

        return $result && $result->num_rows > 0;
    }
}

if (!function_exists('admin_bind_dynamic_params')) {
    function admin_bind_dynamic_params($stmt, $types, array &$params)
    {
        if ($types === '') {
            return true;
        }

        $bindArgs = [$types];
        foreach ($params as $index => &$value) {
            $bindArgs[] = &$value;
        }

        return call_user_func_array([$stmt, 'bind_param'], $bindArgs);
    }
}

if (!function_exists('admin_scalar_value')) {
    function admin_scalar_value($connection, $sql, $defaultValue = 0)
    {
        $result = $connection->query( $sql);
        if (!$result) {
            return $defaultValue;
        }

        $row = $result->fetch_row();
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
            $connection->query( "ALTER TABLE `{$tableName}` ADD COLUMN `{$columnName}` {$definition}");
        }
    }
}

if (!function_exists('admin_make_column_nullable')) {
    function admin_make_column_nullable($connection, $tableName, $columnName)
    {
        if (!admin_column_exists($connection, $tableName, $columnName)) {
            return false;
        }

        $safeTable = $connection->real_escape_string( $tableName);
        $safeColumn = $connection->real_escape_string( $columnName);
        $metaResult = $connection->query( "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
        if (!$metaResult || $metaResult->num_rows === 0) {
            return false;
        }

        $meta = $metaResult->fetch_assoc();
        if (!$meta) {
            return false;
        }

        if (strtoupper((string) ($meta['Null'] ?? '')) === 'YES') {
            return true;
        }

        $type = (string) ($meta['Type'] ?? '');
        if ($type === '') {
            return false;
        }

        $extra = trim((string) ($meta['Extra'] ?? ''));
        $alterSql = "ALTER TABLE `{$safeTable}` MODIFY COLUMN `{$safeColumn}` {$type} NULL";
        if ($extra !== '' && stripos($extra, 'auto_increment') === false) {
            $alterSql .= ' ' . $extra;
        }

        return (bool) $connection->query( $alterSql);
    }
}

if (!function_exists('admin_clear_reference')) {
    function admin_clear_reference($connection, $tableName, $columnName, $idValue, $allowDeleteFallback = false, &$errorDetails = '')
    {
        $errorDetails = '';

        if (!admin_table_exists($connection, $tableName) || !admin_column_exists($connection, $tableName, $columnName)) {
            return true;
        }

        // Best-effort upgrade for legacy schemas where FK columns were created NOT NULL.
        admin_make_column_nullable($connection, $tableName, $columnName);

        $clearStmt = $connection->prepare( "UPDATE `{$tableName}` SET `{$columnName}` = NULL WHERE `{$columnName}` = ?");
        if ($clearStmt) {
            $referenceId = (int) $idValue;
            $clearStmt->bind_param( 'i', $referenceId);
            $clearExecuted = $clearStmt->execute();
            $clearError = trim((string) $clearStmt->error);
            $clearStmt->close();

            if ($clearExecuted) {
                return true;
            }

            $errorDetails = $clearError !== '' ? $clearError : (string) $connection->error;
        } else {
            $errorDetails = (string) $connection->error;
        }

        if (!$allowDeleteFallback) {
            return false;
        }

        // Legacy schemas can enforce restrictive FKs; fallback to deleting dependents.
        $deleteStmt = $connection->prepare( "DELETE FROM `{$tableName}` WHERE `{$columnName}` = ?");
        if (!$deleteStmt) {
            $fallbackError = trim((string) $connection->error);
            if ($fallbackError !== '') {
                $errorDetails .= ($errorDetails !== '' ? ' | ' : '') . $fallbackError;
            }
            return false;
        }

        $referenceId = (int) $idValue;
        $deleteStmt->bind_param( 'i', $referenceId);
        $deleteExecuted = $deleteStmt->execute();
        $deleteError = trim((string) $deleteStmt->error);
        $deleteStmt->close();

        if (!$deleteExecuted) {
            if ($deleteError !== '') {
                $errorDetails .= ($errorDetails !== '' ? ' | ' : '') . $deleteError;
            }
            return false;
        }

        return true;
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
