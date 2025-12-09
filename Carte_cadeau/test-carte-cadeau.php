<?php
/**
 * Test du système de génération de cartes cadeaux HTML
 * Ce script teste la génération sans avoir besoin de WordPress
 */

define('NEWSAIIGE_TESTING', true);

echo "\n";
echo "╔═══════════════════════════════════════════════════════╗\n";
echo "║   Test Carte Cadeau HTML - NewSaiige                ║\n";
echo "╚═══════════════════════════════════════════════════════╝\n\n";

// Simuler les fonctions WordPress
if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return __DIR__ . '/';
    }
}

if (!function_exists('plugins_url')) {
    function plugins_url($path = '', $plugin = '') {
        return 'file:///' . str_replace('\\', '/', __DIR__) . '/' . ltrim($path, '/');
    }
}

if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p($target) {
        if (!is_dir($target)) {
            return mkdir($target, 0755, true);
        }
        return true;
    }
}

if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir() {
        $upload_dir = __DIR__ . '/test-output';
        return array(
            'basedir' => $upload_dir,
            'baseurl' => 'file:///' . str_replace('\\', '/', $upload_dir)
        );
    }
}

// Charger le générateur
require_once(__DIR__ . '/gift-card-pdf-simple.php');

// Créer des données de test
$test_gift_card = (object) array(
    'code' => 'NSGG-TEST-' . strtoupper(substr(md5(time()), 0, 4)),
    'amount' => 50.00,
    'buyer_name' => 'Marie Dupont',
    'recipient_name' => 'Sophie Martin',
    'personal_message' => 'Joyeux anniversaire ! Profite bien de ce moment de détente que tu mérites tant. Bisous ❤️',
    'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year'))
);

echo "📋 Données de test:\n";
echo "   Code: {$test_gift_card->code}\n";
echo "   Pour: {$test_gift_card->recipient_name}\n";
echo "   De: {$test_gift_card->buyer_name}\n";
echo "   Montant: {$test_gift_card->amount}€\n\n";

echo "🔄 Génération de la carte cadeau HTML...\n";

// Générer la carte
$result = newsaiige_generate_gift_card_pdf_simple($test_gift_card);

if ($result && file_exists($result)) {
    $filesize = round(filesize($result) / 1024, 2);
    echo "✅ Carte cadeau générée avec succès!\n\n";
    echo "╔═══════════════════════════════════════════════════════╗\n";
    echo "║   RÉSULTAT                                          ║\n";
    echo "╚═══════════════════════════════════════════════════════╝\n\n";
    echo "📄 Fichier: $result\n";
    echo "📊 Taille: {$filesize} KB\n";
    echo "🔗 Type: HTML (prêt à imprimer en PDF)\n\n";
    
    echo "╔═══════════════════════════════════════════════════════╗\n";
    echo "║   COMMENT UTILISER                                  ║\n";
    echo "╚═══════════════════════════════════════════════════════╝\n\n";
    echo "1️⃣  Ouvrez le fichier HTML dans votre navigateur\n";
    echo "2️⃣  Appuyez sur Ctrl+P (ou Cmd+P sur Mac)\n";
    echo "3️⃣  Sélectionnez 'Enregistrer en PDF'\n";
    echo "4️⃣  Votre carte cadeau est prête!\n\n";
    
    echo "💡 Le fichier HTML peut être envoyé par email,\n";
    echo "   vos clients pourront l'ouvrir et l'imprimer facilement.\n\n";
    
    echo "🎉 Test réussi!\n\n";
    exit(0);
} else {
    echo "❌ Erreur lors de la génération\n";
    echo "Vérifiez les permissions du dossier test-output/\n\n";
    exit(1);
}
