<?php
if (!function_exists('app_base_path')) {
    function app_base_path(): string {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

        foreach (['/controllers/', '/views/'] as $segment) {
            $pos = strpos($scriptName, $segment);
            if ($pos !== false) {
                return rtrim(substr($scriptName, 0, $pos), '/');
            }
        }

        return '';
    }
}

if (!function_exists('app_url')) {
    function app_url(string $path): string {
        $base = app_base_path();
        return $base . '/' . ltrim($path, '/');
    }
}
?>