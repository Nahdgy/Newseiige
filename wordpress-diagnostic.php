<?php
/**
 * Script de diagnostic WordPress
 * À placer à la racine de WordPress et accéder via navigateur
 */

// Désactiver l'affichage des erreurs pour éviter les conflits
error_reporting(0);
ini_set('display_errors', 0);

echo "<h1>🔍 Diagnostic NewSaiige WordPress</h1>";

// 1. Vérifier si WordPress peut être chargé
echo "<h2>1. Test de chargement WordPress</h2>";
$wp_load_path = './wp-load.php';
if (file_exists($wp_load_path)) {
    echo "✅ wp-load.php trouvé<br>";
    
    // Tentative de chargement avec capture d'erreurs
    ob_start();
    $error = '';
    try {
        include_once $wp_load_path;
        echo "✅ WordPress chargé avec succès<br>";
        
        // Informations de base
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo "⚠️ WP_DEBUG est activé<br>";
        }
        
        if (function_exists('wp_get_theme')) {
            $theme = wp_get_theme();
            echo "📁 Thème actuel: " . $theme->get('Name') . "<br>";
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    } catch (Error $e) {
        $error = $e->getMessage();
    }
    $output = ob_get_clean();
    
    if ($error) {
        echo "❌ Erreur lors du chargement: " . htmlspecialchars($error) . "<br>";
    } else {
        echo $output;
    }
} else {
    echo "❌ wp-load.php non trouvé - Ce script doit être à la racine de WordPress<br>";
}

// 2. Vérifier les plugins actifs
echo "<h2>2. Plugins actifs</h2>";
if (function_exists('get_option')) {
    $active_plugins = get_option('active_plugins', array());
    if (empty($active_plugins)) {
        echo "ℹ️ Aucun plugin actif<br>";
    } else {
        foreach ($active_plugins as $plugin) {
            $status = file_exists(WP_PLUGIN_DIR . '/' . $plugin) ? '✅' : '❌';
            echo "$status $plugin<br>";
        }
    }
} else {
    echo "❌ Impossible de vérifier les plugins<br>";
}

// 3. Vérifier les fichiers de thème
echo "<h2>3. Fichiers de thème</h2>";
if (function_exists('get_template_directory')) {
    $theme_dir = get_template_directory();
    echo "📁 Répertoire thème: $theme_dir<br>";
    
    $important_files = array(
        'functions.php',
        'Fidelity/newsaiige-loyalty-plugin.php',
        'Carte cadeau/newsaiige-gift-cards.php'
    );
    
    foreach ($important_files as $file) {
        $full_path = $theme_dir . '/' . $file;
        if (file_exists($full_path)) {
            $size = filesize($full_path);
            echo "✅ $file ($size octets)<br>";
            
            // Vérifier si le fichier contient du code problématique
            $content = file_get_contents($full_path);
            if (strpos($content, 'CREATE TABLE') !== false) {
                echo "⚠️ $file contient CREATE TABLE<br>";
            }
            if (strpos($content, 'newsaiige_gift') !== false) {
                echo "⚠️ $file contient des références gift cards<br>";
            }
        } else {
            echo "❌ $file manquant<br>";
        }
    }
}

// 4. Test de base de données
echo "<h2>4. Test base de données</h2>";
if (defined('DB_NAME')) {
    echo "✅ Configuration DB trouvée: " . DB_NAME . "<br>";
    
    if (function_exists('wpdb')) {
        global $wpdb;
        
        // Vérifier les tables problématiques
        $tables_to_check = array(
            'wp_newsaiige_gift_cards',
            'wp_newsaiige_loyalty_points'
        );
        
        foreach ($tables_to_check as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            if ($exists) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
                echo "✅ $table existe ($count entrées)<br>";
            } else {
                echo "❌ $table n'existe pas<br>";
            }
        }
    }
}

// 5. Vérifier les logs d'erreur
echo "<h2>5. Dernières erreurs PHP</h2>";
$error_log_paths = array(
    './wp-content/debug.log',
    './error_log',
    '/tmp/error_log'
);

$found_errors = false;
foreach ($error_log_paths as $log_path) {
    if (file_exists($log_path)) {
        echo "📄 Log trouvé: $log_path<br>";
        $content = file_get_contents($log_path);
        $lines = explode("\n", $content);
        $recent_lines = array_slice($lines, -10); // 10 dernières lignes
        
        foreach ($recent_lines as $line) {
            if (trim($line) && (strpos($line, 'newsaiige') !== false || strpos($line, 'gift') !== false)) {
                echo "⚠️ " . htmlspecialchars(trim($line)) . "<br>";
                $found_errors = true;
            }
        }
    }
}

if (!$found_errors) {
    echo "ℹ️ Aucune erreur récente trouvée dans les logs<br>";
}

echo "<hr>";
echo "<p><strong>Diagnostic terminé.</strong> Partagez ces informations pour un diagnostic plus précis.</p>";
?>