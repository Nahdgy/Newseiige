<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
define('NEWSAIIGE_TESTING', true);

echo "Test démarré\n";

if (!function_exists('esc_html')) {
    function esc_html($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) { return __DIR__ . '/'; }
}
if (!function_exists('plugins_url')) {
    function plugins_url($path = '', $plugin = '') { return 'file:///' . __DIR__ . '/' . $path; }
}
if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p($target) { if (!is_dir($target)) return mkdir($target, 0755, true); return true; }
}
if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir() { return array('basedir' => __DIR__ . '/test-output', 'baseurl' => 'file:///' . __DIR__ . '/test-output'); }
}

echo "Chargement du générateur...\n";
require_once(__DIR__ . '/gift-card-pdf-simple.php');

echo "Création données test...\n";
$test = (object) array(
    'code' => 'NSGG-TEST-1234',
    'amount' => 50.00,
    'buyer_name' => 'Marie',
    'recipient_name' => 'Sophie',
    'personal_message' => 'Joyeux anniversaire!',
    'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year'))
);

echo "Génération...\n";
$result = newsaiige_generate_gift_card_pdf_simple($test);

if ($result && file_exists($result)) {
    echo "✓ Succès: $result\n";
} else {
    echo "✗ Échec\n";
}
