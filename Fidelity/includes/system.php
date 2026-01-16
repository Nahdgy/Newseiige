<?php
/**
 * Système de fidélité NewSaiige - Version corrigée et sécurisée
 * Évite les conflits WordPress et WooCommerce
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe principale du système de fidélité - Version sécurisée
 */
class NewsaiigeLoyaltySystemSafe {
    
    private $points_table;
    private $tiers_table;
    private $vouchers_table;
    private $user_tiers_table;
    private $settings_table;
    private $conversion_rules_table;
    private static $instance = null;
    
    /**
     * Instance unique (Singleton)
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructeur privé (Singleton)
     */
    private function __construct() {
        global $wpdb;
        
        // Initialiser les noms de tables seulement si WordPress est chargé
        if (isset($wpdb) && $wpdb instanceof wpdb) {
            $this->points_table = $wpdb->prefix . 'newsaiige_loyalty_points';
            $this->tiers_table = $wpdb->prefix . 'newsaiige_loyalty_tiers';
            $this->vouchers_table = $wpdb->prefix . 'newsaiige_loyalty_vouchers';
            $this->user_tiers_table = $wpdb->prefix . 'newsaiige_loyalty_user_tiers';
            $this->settings_table = $wpdb->prefix . 'newsaiige_loyalty_settings';
            $this->conversion_rules_table = $wpdb->prefix . 'newsaiige_loyalty_conversion_rules';
            
            // Initialiser les hooks de manière sécurisée
            $this->init_safe_hooks();
        }
    }
    
    /**
     * Initialiser les hooks de manière sécurisée
     */
    private function init_safe_hooks() {
        // Hooks WordPress sécurisés uniquement
        add_action('wp_ajax_loyalty_get_user_stats', array($this, 'ajax_get_user_stats'));
        add_action('wp_ajax_loyalty_convert_points', array($this, 'ajax_convert_points'));
        
        // Hook pour les tâches quotidiennes (sécurisé)
        add_action('newsaiige_daily_birthday_check', array($this, 'check_user_birthdays'));
        add_action('newsaiige_daily_cleanup', array($this, 'cleanup_expired_data'));
        add_action('newsaiige_daily_subscription_check', array($this, 'daily_subscription_points_check'));
        
        // WooCommerce hooks - SEULEMENT si WooCommerce est disponible et qu'on n'est pas en conflit
        if (class_exists('WooCommerce') && !$this->is_processing_wc_action()) {
            add_action('woocommerce_order_status_completed', array($this, 'process_order_points'), 10, 1);
        }
    }
    
    /**
     * Vérifier si on est déjà en train de traiter une action WooCommerce (éviter les boucles)
     */
    private function is_processing_wc_action() {
        return defined('NEWSAIIGE_PROCESSING_WC') || did_action('woocommerce_cart_calculate_fees') > 0;
    }
    
    /**
     * Vérifier qu'une table existe avant de l'utiliser
     */
    private function table_exists($table_name) {
        global $wpdb;
        
        if (!isset($wpdb)) return false;
        
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s", 
            $table_name
        ));
        
        return $table_exists === $table_name;
    }
    
    /**
     * Obtenir une configuration - Version sécurisée
     */
    public function get_setting($key, $default = '') {
        if (!$this->table_exists($this->settings_table)) {
            return $default;
        }
        
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM {$this->settings_table} WHERE setting_key = %s",
            $key
        ));
        
        return $result !== null ? $result : $default;
    }
    
    /**
     * Ajouter des points - Version sécurisée avec logs détaillés
     */
    public function add_points($user_id, $points, $order_id = null, $action_type = 'manual', $description = '') {
        global $wpdb;
        
        // Vérification 1: Points valides
        if ($points <= 0) {
            error_log("add_points: ÉCHEC - Points invalides ({$points}) pour user {$user_id}");
            return false;
        }
        
        // Vérification 2: Table existe
        if (!$this->table_exists($this->points_table)) {
            error_log("add_points: ÉCHEC - Table {$this->points_table} inexistante pour user {$user_id}");
            return false;
        }
        
        // Vérification 3: L'utilisateur existe dans WordPress
        $user = get_userdata($user_id);
        if (!$user) {
            error_log("add_points: ÉCHEC - Utilisateur #{$user_id} n'existe pas dans wp_users");
            return false;
        }
        
        // Calculer l'expiration (6 mois par défaut)
        $expiry_days = intval($this->get_setting('points_expiry_days', 183));
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_days} days"));
        
        error_log("add_points: Tentative insertion - User: {$user_id} ({$user->user_email}) | Points: {$points} | Order: {$order_id} | Action: {$action_type}");
        
        $result = $wpdb->insert(
            $this->points_table,
            array(
                'user_id' => $user_id,
                'points_earned' => $points,
                'points_available' => $points,
                'order_id' => $order_id,
                'action_type' => $action_type,
                'description' => $description,
                'expires_at' => $expires_at,
                'is_active' => 1
            ),
            array('%d', '%d', '%d', '%d', '%s', '%s', '%s', '%d')
        );
        
        if ($result === false) {
            // Log de l'erreur SQL détaillée
            error_log("add_points: ÉCHEC SQL - User: {$user_id} | Erreur: {$wpdb->last_error}");
            error_log("add_points: Dernière requête: {$wpdb->last_query}");
            return false;
        }
        
        $insert_id = $wpdb->insert_id;
        error_log("add_points: ✓✓✓ SUCCÈS - {$points} points ajoutés (ID: {$insert_id}) pour user {$user_id} ({$user->user_email})");
        
        // Vérifier si l'utilisateur mérite une promotion de palier
        $tier_upgrade_result = $this->check_tier_upgrade($user_id);
        if ($tier_upgrade_result) {
            error_log("add_points: Palier mis à jour pour user {$user_id}");
        }
        
        // Hook pour les actions personnalisées
        do_action('newsaiige_points_added', $user_id, $points, $order_id);
        
        return true;
    }
    
    /**
     * Obtenir les points disponibles d'un utilisateur - Version sécurisée
     */
    public function get_user_points($user_id) {
        if (!$this->table_exists($this->points_table)) {
            return 0;
        }
        
        global $wpdb;
        
        $points = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(points_available) 
             FROM {$this->points_table} 
             WHERE user_id = %d 
             AND is_active = 1 
             AND (expires_at IS NULL OR expires_at > NOW())",
            $user_id
        ));
        
        return intval($points);
    }
    
    /**
     * Obtenir le total des points gagnés (à vie) - Version sécurisée
     */
    public function get_user_lifetime_points($user_id) {
        if (!$this->table_exists($this->points_table)) {
            return 0;
        }
        
        global $wpdb;
        
        $points = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(points_earned) FROM {$this->points_table} WHERE user_id = %d",
            $user_id
        ));
        
        return intval($points);
    }
    
    /**
     * Traiter les points d'une commande - Version sécurisée et compatible HPOS
     * @param int $order_id ID de la commande
     * @param bool $force Forcer le retraitement même si déjà traitée (pour retraitement manuel)
     */
    public function process_order_points($order_id, $force = false) {
        // Charger la commande en premier
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log("process_order_points: Commande #$order_id introuvable");
            return false;
        }
        
        // Éviter les traitements multiples (sauf si force = true)
        if (!$force && $order->get_meta('_newsaiige_loyalty_processed', true)) {
            error_log("process_order_points: Commande #$order_id déjà traitée (utilisez force=true pour retraiter)");
            return false;
        }
        
        // Si force=true, supprimer les anciens points pour cette commande
        if ($force) {
            global $wpdb;
            $deleted = $wpdb->delete(
                $this->points_table,
                array('order_id' => $order_id),
                array('%d')
            );
            if ($deleted > 0) {
                error_log("process_order_points: FORCE MODE - {$deleted} ancien(s) enregistrement(s) de points supprimé(s) pour commande #{$order_id}");
            }
        }
        
        $user_id = $order->get_user_id();
        if (!$user_id) {
            error_log("process_order_points: Commande #$order_id sans utilisateur");
            return false;
        }
        
        // Déterminer le type de commande
        $order_type = $order->get_type();
        $order_status = $order->get_status();
        
        error_log("process_order_points: Traitement commande #$order_id - Type: $order_type - Statut: $order_status - User: $user_id");
        
        // LOGIQUE D'ATTRIBUTION DES POINTS
        $should_receive_points = false;
        
        // CAS 1 : C'est un abonnement WPS → Attribution automatique
        if ($order_type === 'wps_subscription' || $order_type === 'wps_subscriptions') {
            error_log("process_order_points: ✓ Abonnement WPS détecté - Attribution automatique des points");
            $should_receive_points = true;
        }
        // CAS 2 : C'est une commande normale → Vérifier si l'utilisateur a un abonnement actif
        else if ($order_type === 'shop_order') {
            error_log("process_order_points: Commande shop_order - Vérification de l'abonnement actif...");
            
            if ($this->has_active_subscription($user_id)) {
                error_log("process_order_points: ✓ Utilisateur a un abonnement actif - Attribution des points");
                $should_receive_points = true;
            } else {
                error_log("process_order_points: ✗ Utilisateur SANS abonnement actif - Commande #$order_id ignorée");
                return false;
            }
        }
        // CAS 3 : Autre type de commande → Ignorer
        else {
            error_log("process_order_points: ✗ Type de commande '$order_type' non géré - Commande #$order_id ignorée");
            return false;
        }
        
        // Si on arrive ici, les points doivent être attribués
        if (!$should_receive_points) {
            error_log("process_order_points: ✗ Conditions non remplies pour commande #$order_id");
            return false;
        }
        
        // Calculer les points (1 point par euro dépensé par défaut)
        $order_total = $order->get_total();
        $points_per_euro = floatval($this->get_setting('points_per_euro', 1));
        $points_earned = floor($order_total * $points_per_euro);
        
        error_log("process_order_points: Montant commande: {$order_total}€ × {$points_per_euro} = {$points_earned} points");
        
        if ($points_earned > 0) {
            $description = sprintf('Points gagnés pour la commande #%s (%s)', $order_id, $order_type);
            
            if ($this->add_points($user_id, $points_earned, $order_id, 'order', $description)) {
                // Marquer la commande comme traitée (compatible HPOS)
                $order->update_meta_data('_newsaiige_loyalty_processed', time());
                $order->save();
                
                // Ajouter une note à la commande
                $order->add_order_note(
                    sprintf('✓ Programme de fidélité : %d points ajoutés au compte client.', $points_earned)
                );
                
                error_log("process_order_points: ✓✓✓ {$points_earned} points ATTRIBUÉS à user {$user_id} pour commande #{$order_id}");
                return true;
            } else {
                error_log("process_order_points: ✗ ÉCHEC attribution points pour commande #$order_id");
                return false;
            }
        } else {
            error_log("process_order_points: Commande #$order_id - Aucun point à attribuer (total: {$order_total}€)");
            return false;
        }
    }
    
    /**
     * Vérifier si un utilisateur a un abonnement WPS Subscriptions actif
     * CORRECTION : Les abonnements WPS ont customer_id=0, on utilise billing_email
     */
    public function has_active_subscription($user_id) {
        global $wpdb;
        
        // Récupérer l'email de l'utilisateur
        $user = get_userdata($user_id);
        if (!$user) {
            error_log("has_active_subscription: ✗ User {$user_id} n'existe pas");
            return false;
        }
        
        $user_email = $user->user_email;
        
        // MÉTHODE 1 : Vérifier dans wc_orders par BILLING_EMAIL (car customer_id = 0)
        // Les abonnements WPS ont customer_id=0 mais billing_email correct
        $hpos_subscription = $wpdb->get_row($wpdb->prepare(
            "SELECT id, status, billing_email
             FROM {$wpdb->prefix}wc_orders
             WHERE type = 'wps_subscriptions'
             AND billing_email = %s
             AND status NOT IN ('auto-draft', 'trash', 'wc-cancelled', 'wc-expired', 'wc-failed')
             LIMIT 1",
            $user_email
        ));
        
        if ($hpos_subscription) {
            error_log("has_active_subscription: ✓ User {$user_id} ({$user_email}) a un abonnement WPS actif (ID:{$hpos_subscription->id}, statut:{$hpos_subscription->status}) trouvé par EMAIL");
            return true;
        }
        
        // MÉTHODE 2 : Fallback par customer_id (au cas où certains sont bien remplis)
        $hpos_by_id = $wpdb->get_row($wpdb->prepare(
            "SELECT id, status
             FROM {$wpdb->prefix}wc_orders
             WHERE type = 'wps_subscriptions'
             AND customer_id = %d
             AND status NOT IN ('auto-draft', 'trash', 'wc-cancelled', 'wc-expired', 'wc-failed')
             LIMIT 1",
            $user_id
        ));
        
        if ($hpos_by_id) {
            error_log("has_active_subscription: ✓ User {$user_id} a un abonnement WPS actif (ID:{$hpos_by_id->id}, statut:{$hpos_by_id->status}) trouvé par customer_id");
            return true;
        }
        
        // MÉTHODE 3 : Vérifier dans wp_posts
        $post_subscription = $wpdb->get_var($wpdb->prepare(
            "SELECT p.ID
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE (p.post_type = 'pms-subscription' OR p.post_type LIKE '%%wps%%subscription%%')
             AND pm.meta_key = '_customer_user'
             AND pm.meta_value = %d
             AND p.post_status NOT IN ('trash', 'auto-draft', 'wc-cancelled', 'wc-expired', 'cancelled', 'expired')
             LIMIT 1",
            $user_id
        ));
        
        if ($post_subscription) {
            error_log("has_active_subscription: ✓ User {$user_id} a un abonnement actif (wp_posts ID:{$post_subscription})");
            return true;
        }
        
        // Aucun abonnement trouvé
        error_log("has_active_subscription: ✗ User {$user_id} ({$user_email}) n'a AUCUN abonnement actif (cherché par email + customer_id)");
        return false;
    }
    
    /**
     * Vérifier les promotions de palier - Version sécurisée (basé sur points disponibles)
     */
    public function check_tier_upgrade($user_id) {
        if (!$this->table_exists($this->tiers_table) || !$this->table_exists($this->user_tiers_table)) {
            error_log("check_tier_upgrade: Tables manquantes pour user {$user_id}");
            return false;
        }
        
        $available_points = $this->get_user_points($user_id);
        error_log("check_tier_upgrade: User {$user_id} a {$available_points} points disponibles");
        
        error_log("check_tier_upgrade: User {$user_id} a {$available_points} points disponibles");
        
        global $wpdb;
        
        // Trouver le palier approprié basé sur les points disponibles
        $new_tier = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tiers_table} 
             WHERE points_required <= %d 
             AND is_active = 1 
             ORDER BY points_required DESC 
             LIMIT 1",
            $available_points
        ));
        
        if (!$new_tier) {
            error_log("check_tier_upgrade: Aucun palier trouvé pour {$available_points} points (user {$user_id})");
            return false;
        }
        
        error_log("check_tier_upgrade: Palier trouvé pour user {$user_id}: {$new_tier->tier_name} (ID: {$new_tier->id})");
        
        // Vérifier le palier actuel
        $current_tier_id = $wpdb->get_var($wpdb->prepare(
            "SELECT tier_id FROM {$this->user_tiers_table} 
             WHERE user_id = %d AND is_current = 1",
            $user_id
        ));
        
        error_log("check_tier_upgrade: Palier actuel user {$user_id}: " . ($current_tier_id ? $current_tier_id : 'AUCUN'));
        
        if ($current_tier_id != $new_tier->id) {
            // Désactiver l'ancien palier
            $wpdb->update(
                $this->user_tiers_table,
                array('is_current' => 0),
                array('user_id' => $user_id)
            );
            
            // Activer le nouveau palier
            $result = $wpdb->insert(
                $this->user_tiers_table,
                array(
                    'user_id' => $user_id,
                    'tier_id' => $new_tier->id,
                    'is_current' => 1,
                    'achieved_at' => current_time('mysql')
                )
            );
            
            if ($result) {
                error_log("✅ check_tier_upgrade: User {$user_id} promu à {$new_tier->tier_name} (ID: {$new_tier->id})");
                
                // Hook personnalisé pour la promotion
                do_action('newsaiige_tier_upgrade', $user_id, $new_tier);
                
                return true;
            } else {
                error_log("❌ check_tier_upgrade: ERREUR lors de l'insertion du palier pour user {$user_id}: " . $wpdb->last_error);
            }
        } else {
            error_log("check_tier_upgrade: User {$user_id} déjà au bon palier ({$new_tier->tier_name})");
        }
        
        return false;
    }
    
    /**
     * AJAX pour récupérer les statistiques utilisateur
     */
    public function ajax_get_user_stats() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'newsaiige_loyalty_nonce') || !is_user_logged_in()) {
            wp_send_json_error('Accès non autorisé');
        }
        
        $user_id = get_current_user_id();
        
        $data = array(
            'points_available' => $this->get_user_points($user_id),
            'points_lifetime' => $this->get_user_lifetime_points($user_id),
            'current_tier' => $this->get_user_tier($user_id),
            'vouchers' => $this->get_user_vouchers($user_id)
        );
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX pour convertir des points
     */
    public function ajax_convert_points() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'newsaiige_loyalty_nonce') || !is_user_logged_in()) {
            wp_send_json_error('Accès non autorisé');
        }
        
        $user_id = get_current_user_id();
        $points_to_convert = intval($_POST['points'] ?? 0);
        
        if ($points_to_convert <= 0) {
            wp_send_json_error('Nombre de points invalide');
        }
        
        $available_points = $this->get_user_points($user_id);
        
        if ($points_to_convert > $available_points) {
            wp_send_json_error('Points insuffisants');
        }
        
        // Ici on ajouterait la logique de conversion
        wp_send_json_success('Conversion réussie');
    }
    
    /**
     * Nettoyer les données expirées - Version sécurisée
     */
    public function cleanup_expired_data() {
        if (!$this->table_exists($this->points_table)) {
            return;
        }
        
        global $wpdb;
        
        // Désactiver UNIQUEMENT les points expirés (pas tous les points actifs)
        $affected_rows = $wpdb->query(
            "UPDATE {$this->points_table} 
             SET is_active = 0 
             WHERE expires_at IS NOT NULL 
             AND expires_at < NOW() 
             AND is_active = 1"
        );
        
        if ($affected_rows > 0) {
            error_log("cleanup_expired_data: {$affected_rows} points expirés désactivés");
        }
    }
    
    /**
     * Vérification automatique quotidienne des paiements d'abonnement
     * CORRECTION : Utilise billing_email car les abonnements WPS ont customer_id=0
     */
    public function daily_subscription_points_check() {
        global $wpdb;
        
        error_log("daily_subscription_points_check: Démarrage de la vérification quotidienne");
        
        // Récupérer les abonnements WPS des dernières 48h sans points attribués
        // CORRECTION : Ne plus filtrer par customer_id > 0 car les abonnements WPS ont customer_id = 0
        $recent_orders = $wpdb->get_results("
            SELECT DISTINCT
                o.id as order_id,
                o.customer_id,
                o.billing_email,
                o.type,
                o.status,
                o.total_amount as total,
                o.date_created_gmt as date_created
            FROM {$wpdb->prefix}wc_orders o
            WHERE o.type IN ('wps_subscription', 'wps_subscriptions')
            AND o.status NOT IN ('auto-draft', 'trash', 'wc-cancelled', 'wc-expired', 'wc-failed')
            AND o.total_amount > 0
            AND o.billing_email IS NOT NULL
            AND o.billing_email != ''
            AND o.date_created_gmt >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->prefix}newsaiige_loyalty_points p
                WHERE p.order_id = o.id
            )
            ORDER BY o.date_created_gmt DESC
        ");
        
        if (empty($recent_orders)) {
            error_log("daily_subscription_points_check: Aucun paiement récent à traiter");
            return;
        }
        
        error_log("daily_subscription_points_check: " . count($recent_orders) . " abonnement(s) trouvé(s) sans points");
        
        $processed_count = 0;
        $error_count = 0;
        $skipped_no_user = 0;
        
        foreach ($recent_orders as $order_data) {
            // Trouver l'utilisateur WordPress par son email
            $user = get_user_by('email', $order_data->billing_email);
            
            if (!$user) {
                error_log("daily_subscription_points_check: ⚠ Abonnement #{$order_data->order_id} - email {$order_data->billing_email} n'existe PAS dans wp_users");
                $skipped_no_user++;
                continue;
            }
            
            $user_id = $user->ID;
            
            // Charger la commande via WooCommerce
            $order = wc_get_order($order_data->order_id);
            
            if (!$order) {
                error_log("daily_subscription_points_check: Abonnement #{$order_data->order_id} introuvable via wc_get_order()");
                $error_count++;
                continue;
            }
            
            // Mettre à jour le user_id de la commande si c'est 0
            if ($order_data->customer_id == 0) {
                $order->set_customer_id($user_id);
                $order->save();
                error_log("daily_subscription_points_check: Customer_id mis à jour pour abonnement #{$order_data->order_id}: 0 → {$user_id}");
            }
            
            error_log("daily_subscription_points_check: Traitement abonnement #{$order_data->order_id} - User: {$user_id} ({$order_data->billing_email}) - Montant: {$order_data->total}€");
            
            if ($this->process_order_points($order_data->order_id)) {
                $processed_count++;
                error_log("daily_subscription_points_check: ✓✓✓ Points attribués pour abonnement #{$order_data->order_id}");
            } else {
                $error_count++;
                error_log("daily_subscription_points_check: ✗✗✗ ÉCHEC attribution points abonnement #{$order_data->order_id}");
            }
        }
        
        error_log("daily_subscription_points_check: === RÉSUMÉ === Traitées: {$processed_count} | Erreurs: {$error_count} | Sans utilisateur: {$skipped_no_user}");
        
        // Envoyer une notification admin si des points ont été attribués
        if ($processed_count > 0) {
            do_action('newsaiige_daily_points_attributed', $processed_count, $error_count);
        }
        
        // Alerte si des utilisateurs n'existent pas
        if ($skipped_no_user > 0) {
            error_log("daily_subscription_points_check: ⚠⚠⚠ ALERTE: {$skipped_no_user} abonnement(s) avec email inconnu dans wp_users");
        }
    }
    
    /**
     * Méthodes stub pour compatibilité
     */
    public function get_user_tier($user_id) { return null; }
    public function get_user_vouchers($user_id) { return array(); }
    public function check_user_birthdays() { return true; }
}

// Initialiser le système de manière sécurisée
function newsaiige_loyalty_system_init() {
    return NewsaiigeLoyaltySystemSafe::get_instance();
}

// Démarrer le système
add_action('plugins_loaded', 'newsaiige_loyalty_system_init', 20);

// Variable globale pour compatibilité
global $newsaiige_loyalty;
$newsaiige_loyalty = NewsaiigeLoyaltySystemSafe::get_instance();
?>