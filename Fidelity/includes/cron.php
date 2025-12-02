<?php
/**
 * Configuration des tâches automatiques (Cron Jobs)
 * Pour le système de fidélité Newsaiige
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Activer les tâches planifiées lors de l'activation du plugin
 */
function newsaiige_loyalty_schedule_events() {
    // Vérification quotidienne des paiements d'abonnement (tous les jours à 02h00)
    if (!wp_next_scheduled('newsaiige_daily_subscription_check')) {
        wp_schedule_event(strtotime('tomorrow 02:00:00'), 'daily', 'newsaiige_daily_subscription_check');
        error_log("newsaiige_loyalty: Tâche quotidienne 'subscription_check' programmée pour 02h00");
    }
    
    // Nettoyage des points expirés (tous les jours à 03h00)
    if (!wp_next_scheduled('newsaiige_daily_cleanup')) {
        wp_schedule_event(strtotime('tomorrow 03:00:00'), 'daily', 'newsaiige_daily_cleanup');
        error_log("newsaiige_loyalty: Tâche quotidienne 'cleanup' programmée pour 03h00");
    }
    
    // Vérification des anniversaires (tous les jours à 08h00)
    if (!wp_next_scheduled('newsaiige_daily_birthday_check')) {
        wp_schedule_event(strtotime('tomorrow 08:00:00'), 'daily', 'newsaiige_daily_birthday_check');
        error_log("newsaiige_loyalty: Tâche quotidienne 'birthday_check' programmée pour 08h00");
    }
}

/**
 * Désactiver les tâches planifiées lors de la désactivation du plugin
 */
function newsaiige_loyalty_unschedule_events() {
    $timestamp = wp_next_scheduled('newsaiige_daily_subscription_check');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'newsaiige_daily_subscription_check');
        error_log("newsaiige_loyalty: Tâche 'subscription_check' supprimée");
    }
    
    $timestamp = wp_next_scheduled('newsaiige_daily_cleanup');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'newsaiige_daily_cleanup');
        error_log("newsaiige_loyalty: Tâche 'cleanup' supprimée");
    }
    
    $timestamp = wp_next_scheduled('newsaiige_daily_birthday_check');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'newsaiige_daily_birthday_check');
        error_log("newsaiige_loyalty: Tâche 'birthday_check' supprimée");
    }
}

// Programmer les événements au chargement du plugin
add_action('init', 'newsaiige_loyalty_schedule_events');

// Hook de désactivation (à ajouter dans le fichier principal du plugin)
// register_deactivation_hook(__FILE__, 'newsaiige_loyalty_unschedule_events');

/**
 * Afficher l'état des tâches planifiées dans l'admin
 */
function newsaiige_loyalty_cron_status_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Accès refusé');
    }
    
    // Forcer l'exécution manuelle si demandé
    if (isset($_POST['run_subscription_check']) && check_admin_referer('newsaiige_run_cron')) {
        global $newsaiige_loyalty;
        if ($newsaiige_loyalty) {
            echo '<div class="notice notice-info"><p>Exécution manuelle de la vérification des abonnements...</p></div>';
            $newsaiige_loyalty->daily_subscription_points_check();
            echo '<div class="notice notice-success"><p>✓ Vérification terminée ! Consultez les logs pour les détails.</p></div>';
        }
    }
    
    $subscription_check = wp_next_scheduled('newsaiige_daily_subscription_check');
    $cleanup = wp_next_scheduled('newsaiige_daily_cleanup');
    $birthday = wp_next_scheduled('newsaiige_daily_birthday_check');
    
    ?>
    <div class="wrap">
        <h1>🕐 Tâches Automatiques</h1>
        <p>État des tâches planifiées pour le système de fidélité.</p>
        
        <table class="widefat fixed striped" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th style="width: 40%;">Tâche</th>
                    <th style="width: 20%;">État</th>
                    <th style="width: 40%;">Prochaine exécution</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>🔍 Vérification des paiements d'abonnement</strong><br>
                        <small>Attribue automatiquement les points pour les paiements effectués la veille</small>
                    </td>
                    <td><?php echo $subscription_check ? '<span style="color: green;">✓ Actif</span>' : '<span style="color: red;">✗ Inactif</span>'; ?></td>
                    <td><?php echo $subscription_check ? date('d/m/Y à H:i:s', $subscription_check) : 'Non programmé'; ?></td>
                </tr>
                <tr>
                    <td><strong>🗑️ Nettoyage des points expirés</strong><br>
                        <small>Désactive les points expirés depuis plus de 6 mois</small>
                    </td>
                    <td><?php echo $cleanup ? '<span style="color: green;">✓ Actif</span>' : '<span style="color: red;">✗ Inactif</span>'; ?></td>
                    <td><?php echo $cleanup ? date('d/m/Y à H:i:s', $cleanup) : 'Non programmé'; ?></td>
                </tr>
                <tr>
                    <td><strong>🎂 Vérification des anniversaires</strong><br>
                        <small>Attribue des points bonus pour les anniversaires</small>
                    </td>
                    <td><?php echo $birthday ? '<span style="color: green;">✓ Actif</span>' : '<span style="color: red;">✗ Inactif</span>'; ?></td>
                    <td><?php echo $birthday ? date('d/m/Y à H:i:s', $birthday) : 'Non programmé'; ?></td>
                </tr>
            </tbody>
        </table>
        
        <div style="margin-top: 30px; padding: 20px; background: #f0f8ff; border-left: 4px solid #0073aa;">
            <h3>⚙️ Actions manuelles</h3>
            <p>Vous pouvez forcer l'exécution des tâches manuellement pour tester.</p>
            
            <form method="POST" style="margin-top: 15px;">
                <?php wp_nonce_field('newsaiige_run_cron'); ?>
                <button type="submit" name="run_subscription_check" class="button button-primary">
                    ▶️ Exécuter la vérification des abonnements maintenant
                </button>
            </form>
            
            <p style="margin-top: 15px;">
                <small>
                    <strong>Note :</strong> Les logs de ces tâches sont visibles dans 
                    <code>/wp-content/debug.log</code> si WP_DEBUG_LOG est activé.
                </small>
            </p>
        </div>
        
        <div style="margin-top: 20px; padding: 20px; background: #fff8dc; border-left: 4px solid #f39c12;">
            <h3>📖 Comment ça fonctionne ?</h3>
            
            <h4>🔍 Vérification des paiements (02h00)</h4>
            <ol>
                <li>Cherche les paiements d'abonnement effectués dans les dernières 48h</li>
                <li>Vérifie si des points ont déjà été attribués</li>
                <li>Attribue automatiquement les points manquants</li>
                <li>Exemple : Paiement le 1er → Points attribués le 2 à 02h00</li>
            </ol>
            
            <h4>🗑️ Nettoyage des points (03h00)</h4>
            <ol>
                <li>Désactive les points qui ont expiré</li>
                <li>Ne touche PAS aux points actifs</li>
                <li>Durée de vie par défaut : 6 mois</li>
            </ol>
            
            <h4>🎂 Anniversaires (08h00)</h4>
            <ol>
                <li>Vérifie les utilisateurs dont c'est l'anniversaire</li>
                <li>Attribue un bonus de points (configurable)</li>
                <li>Envoie éventuellement un email de félicitations</li>
            </ol>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;">
            <h3>🔧 Dépannage</h3>
            
            <h4>Les tâches ne s'exécutent pas ?</h4>
            <ul>
                <li>Vérifiez que le WP-Cron n'est pas désactivé (<code>DISABLE_WP_CRON</code> dans wp-config.php)</li>
                <li>Assurez-vous que votre site reçoit du trafic (le cron WordPress est déclenché par les visites)</li>
                <li>Pour les sites à faible trafic, configurez un vrai cron serveur :
                    <pre style="background: #f5f5f5; padding: 10px; margin-top: 5px;">*/15 * * * * wget -q -O - https://votresite.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1</pre>
                </li>
            </ul>
            
            <h4>Comment vérifier que ça fonctionne ?</h4>
            <ol>
                <li>Activez WP_DEBUG_LOG dans wp-config.php</li>
                <li>Consultez /wp-content/debug.log</li>
                <li>Cherchez les lignes contenant "daily_subscription_points_check"</li>
                <li>Utilisez le bouton "Exécuter maintenant" ci-dessus pour tester</li>
            </ol>
        </div>
    </div>
    <?php
}

// Ajouter la page dans le menu admin
add_action('admin_menu', function() {
    add_submenu_page(
        'newsaiige-loyalty',
        'Tâches Automatiques',
        '🕐 Tâches Auto',
        'manage_options',
        'newsaiige-loyalty-cron',
        'newsaiige_loyalty_cron_status_page'
    );
}, 100);
?>
