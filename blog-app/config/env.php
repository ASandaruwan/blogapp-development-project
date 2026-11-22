<?php
// Lightweight Native .env Environment Loader

if (!function_exists('loadEnv')) {
    function loadEnv($filePath) {
        if (!file_exists($filePath)) {
            return false;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and blank lines
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Strip quotes if wrapped
                if ((strlen($value) >= 2) && 
                    (($value[0] === '"' && $value[strlen($value) - 1] === '"') || 
                     ($value[0] === "'" && $value[strlen($value) - 1] === "'"))) {
                    $value = substr($value, 1, -1);
                }

                // Handle boolean / null types
                $lowerVal = strtolower($value);
                if ($lowerVal === 'true') {
                    $value = true;
                } elseif ($lowerVal === 'false') {
                    $value = false;
                } elseif ($lowerVal === 'null') {
                    $value = null;
                }

                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                    putenv("{$key}=" . (is_bool($value) ? ($value ? 'true' : 'false') : (string)$value));
                }
            }
        }
        return true;
    }
}

if (!function_exists('env')) {
    function env($key, $default = null) {
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($val === false || $val === null) {
            return $default;
        }
        if ($val === 'true' || $val === true) return true;
        if ($val === 'false' || $val === false) return false;
        if ($val === 'null' || $val === null) return null;
        return $val;
    }
}
