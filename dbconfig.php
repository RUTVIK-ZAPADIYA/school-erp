<?php

require_once __DIR__ . '/includes/db_connect.php';

const DB_HOST = '127.0.0.1';
const DB_NAME = 'school_erp';
const DB_USER = 'root';
const DB_PASS = '';

// Keep backward compatibility for admin pages that use $connection.
$connection = $conn;

// Must be called after every $stmt->execute() that uses CALL ProcedureName().
function flush_stored_results($con)
{
    while ($con->more_results() && $con->next_result()) {
        $extra = $con->use_result();
        if ($extra instanceof mysqli_result) {
            $extra->free();
        }
    }
}
