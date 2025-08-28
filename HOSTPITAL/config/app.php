<?php
// Adjust if your project folder name is different
if (!defined('BASE_PATH')) {
    // Try to auto-detect base by taking first segment after root
    // Fallback to '/HR4'
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/HR4/index.php');
    $parts = explode('/', trim($scriptName, '/'));
    $base = '/' . ($parts[0] ?? 'HR4');
    define('BASE_PATH', $base === '/' ? '/HR4' : $base);
}
?>
