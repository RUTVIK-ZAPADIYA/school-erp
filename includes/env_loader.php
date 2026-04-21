<?php

if (!function_exists('school_erp_load_env')) {
    function school_erp_load_env($envFilePath)
    {
        static $loadedFiles = [];

        $path = (string) $envFilePath;
        if ($path === '' || isset($loadedFiles[$path]) || !is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return;
        }

        foreach ($lines as $line) {
            $trimmedLine = trim((string) $line);
            if ($trimmedLine === '' || strpos($trimmedLine, '#') === 0) {
                continue;
            }

            $equalPos = strpos($trimmedLine, '=');
            if ($equalPos === false) {
                continue;
            }

            $key = trim(substr($trimmedLine, 0, $equalPos));
            $value = trim(substr($trimmedLine, $equalPos + 1));

            if ($key === '') {
                continue;
            }

            $firstChar = $value !== '' ? substr($value, 0, 1) : '';
            $lastChar = $value !== '' ? substr($value, -1) : '';
            if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
                $value = substr($value, 1, -1);
            }

            if (getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }

        $loadedFiles[$path] = true;
    }
}
