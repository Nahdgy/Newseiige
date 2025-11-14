<?php
/**
 * Intégration WooCommerce NewSaiige - Version corrigée et sécurisée
 * Évite les conflits avec le système WooCommerce
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe d'intégration WooCommerce - Version sécurisée
 */
class NewsaiigeLoyaltyWooCommerceSafe {
    
    private static $instance = null;
    private $loyalty_system = null;
    private $processing_fee = false; // Éviter les boucles
    
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
        // Vérifier que WooCommerce est disponible
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        // Initialiser seulement si pas de conflit en cours
        if (!$this->is_wc_conflict()) {
            $this->init_wc_hooks();
        }
    }
    
    /**
     * Vérifier s'il y a un conflit WooCommerce en cours
     */
    private function is_wc_conflict() {
        return defined('NEWSAIIGE_PROCESSING_WC') || 
               did_action('woocommerce_cart_calculate_fees') > 1 ||
               $this->processing_fee;
    }
    
    /**
     * Initialiser les hooks WooCommerce de manière sécurisée
     */
    private function init_wc_hooks() {
        // Hook pour les frais de panier (avec protection)
        add_action('woocommerce_cart_calculate_fees', array($this, 'safe_calculate_fees'), 20);
        
        // Hooks pour les coupons et vouchers
        add_action('woocommerce_applied_coupon', array($this, 'track_voucher_usage'));
        
        // Hook pour l'affichage des points dans le panier
        add_action('woocommerce_cart_totals_after_order_total', array($this, 'display_cart_points'));
        
        // Hook pour l'affichage des points sur la page produit
        add_action('woocommerce_single_product_summary', array($this, 'display_product_points'), 25);
        
        // Hooks AJAX pour la gestion des vouchers
        add_action('wp_ajax_apply_loyalty_voucher', array($this, 'ajax_apply_voucher'));
        add_action('wp_ajax_remove_loyalty_voucher', array($this, 'ajax_remove_voucher'));
        
        // Scripts et styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Calculer les frais de manière sécurisée (éviter les boucles infinies)
     */
    public function safe_calculate_fees() {
        // Protection contre les boucles infinies
        if ($this->processing_fee || defined('NEWSAIIGE_PROCESSING_FEE')) {
            return;
        }
        
        // Marquer le début du traitement
        $this->processing_fee = true;
        define('NEWSAIIGE_PROCESSING_FEE', true);
        
        try {
            $this->calculate_loyalty_fees();
        } finally {
            // Toujours remettre à zéro même en cas d'erreur
            $this->processing_fee = false;
        }
    }
    
    /**
     * Calculer les frais/réductions de fidélité
     */
    private function calculate_loyalty_fees() {
        if (!WC()->cart || WC()->cart->is_empty()) {
            return;
        }
        
        // Vérifier s'il y a un voucher appliqué en session
        $voucher_discount = WC()->session->get('newsaiige_voucher_discount', 0);
        
        if ($voucher_discount > 0) {
            $voucher_code = WC()->session->get('newsaiige_voucher_code', '');
            
            // Ajouter une réduction negative fee
            WC()->cart->add_fee(
                sprintf('Voucher fidélité (%s)', $voucher_code),
                -$voucher_discount,
                false
            );
        }
    }
    
    /**
     * Afficher les points gagnables sur la page produit
     */
    public function display_product_points() {
        if (!is_user_logged_in()) {
            return;
        }
        
        global $product;
        if (!$product) return;
        
        // Vérifier si l'utilisateur est éligible
        if (!$this->user_eligible_for_points()) {
            return;
        }
        
        $price = $product->get_price();
        if (!$price) return;
        
        $points_per_euro = $this->get_loyalty_setting('points_per_euro', 1);
        $points = floor($price * $points_per_euro);
        
        if ($points > 0) {
            echo '<div class="newsaiige-product-points" style="margin: 10px 0; padding: 10px; background: #f8f9fa; border-left: 3px solid #007cba; font-size: 14px;">';
            echo '<span class="points-icon">🎯</span> ';
            echo sprintf(
                'Gagnez <strong>%d points</strong> de fidélité en achetant ce produit !', 
                $points
            );
            echo '</div>';
        }
    }
    
    /**
     * Afficher les points dans le panier
     */
    public function display_cart_points() {
        if (!is_user_logged_in()) {
            return;
        }
        
        if (!$this->user_eligible_for_points()) {
            echo '<tr class="newsaiige-cart-notice">';
            echo '<th>Programme de fidélité</th>';
            echo '<td><em>Abonnement requis pour gagner des points</em></td>';
            echo '</tr>';
            return;
        }
        
        $cart_total = WC()->cart->get_subtotal();
        $points_per_euro = $this->get_loyalty_setting('points_per_euro', 1);
        $points = floor($cart_total * $points_per_euro);
        
        $current_points = $this->get_user_current_points();
        
        ?>
        <tr class="newsaiige-cart-points">
            <th>Points de fidélité</th>
            <td data-title="Points">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="points-earned" style="color: #28a745;">
                        +<?php echo $points; ?> points avec cette commande
                    </span>
                    <span class="points-current" style="color: #6c757d; font-size: 0.9em;">
                        (Solde actuel: <?php echo $current_points; ?> points)
                    </span>
                </div>
                <?php if ($current_points >= 100): ?>
                <button type="button" class="button alt" id="use-loyalty-points" style="margin-top: 5px; font-size: 0.9em;">
                    Utiliser mes points
                </button>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Vérifier si l'utilisateur est éligible pour les points
     */
    private function user_eligible_for_points() {
        $user_id = get_current_user_id();
        if (!$user_id) return false;
        
        // Vérifier si un abonnement est requis
        $subscription_required = $this->get_loyalty_setting('subscription_required', '1');
        
        if ($subscription_required === '1') {
            return $this->has_subscription_purchase($user_id);
        }
        
        return true;
    }
    
    /**
     * Vérifier si l'utilisateur a acheté un abonnement (produit de la catégorie soins)
     */
    private function has_subscription_purchase($user_id) {
        $subscription_category = $this->get_loyalty_setting('subscription_category_slug', 'soins');
        
        if (empty($subscription_category)) {
            return true;
        }
        
        // Cache pour éviter les requêtes répétées
        $cache_key = "newsaiige_subscription_{$user_id}";
        $cached_result = wp_cache_get($cache_key);
        
        if ($cached_result !== false) {
            return $cached_result === 'yes';
        }
        
        // Vérifier les commandes récentes
        $recent_orders = wc_get_orders(array(
            'customer' => $user_id,
            'status' => 'completed',
            'date_created' => '>=' . (time() - (365 * 24 * 60 * 60)), // Dernière année
            'limit' => 20
        ));
        
        $has_subscription = false;
        
        foreach ($recent_orders as $order) {
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if ($product && has_term($subscription_category, 'product_cat', $product->get_id())) {
                    $has_subscription = true;
                    break 2;
                }
            }
        }
        
        // Mettre en cache pour 1 heure
        wp_cache_set($cache_key, $has_subscription ? 'yes' : 'no', '', 3600);
        
        return $has_subscription;
    }
    
    /**
     * Obtenir les points actuels de l'utilisateur
     */
    private function get_user_current_points() {
        $user_id = get_current_user_id();
        if (!$user_id) return 0;
        
        $loyalty_system = $this->get_loyalty_system();
        if ($loyalty_system && method_exists($loyalty_system, 'get_user_points')) {
            return $loyalty_system->get_user_points($user_id);
        }
        
        return 0;
    }
    
    /**
     * Obtenir une référence au système de fidélité
     */
    private function get_loyalty_system() {
        if (!$this->loyalty_system) {
            global $newsaiige_loyalty;
            $this->loyalty_system = $newsaiige_loyalty;
        }
        
        return $this->loyalty_system;
    }
    
    /**
     * Obtenir une configuration du système de fidélité
     */
    private function get_loyalty_setting($key, $default = '') {
        $loyalty_system = $this->get_loyalty_system();
        
        if ($loyalty_system && method_exists($loyalty_system, 'get_setting')) {
            return $loyalty_system->get_setting($key, $default);
        }
        
        return $default;
    }
    
    /**
     * AJAX - Appliquer un voucher
     */
    public function ajax_apply_voucher() {
        check_ajax_referer('newsaiige_loyalty_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Connexion requise');
        }
        
        $points_to_use = intval($_POST['points'] ?? 0);
        $user_id = get_current_user_id();
        
        if ($points_to_use < 100 || $points_to_use % 100 !== 0) {
            wp_send_json_error('Minimum 100 points, par multiples de 100');
        }
        
        $current_points = $this->get_user_current_points();
        
        if ($points_to_use > $current_points) {
            wp_send_json_error('Points insuffisants');
        }
        
        // Calculer la réduction (1 euro = 100 points par défaut)
        $points_per_euro = intval($this->get_loyalty_setting('points_per_euro', 1));
        $discount_amount = $points_to_use / (100 * $points_per_euro);
        
        // Limiter la réduction au total du panier
        $cart_total = WC()->cart->get_subtotal();
        $discount_amount = min($discount_amount, $cart_total * 0.8); // Max 80% du panier
        
        // Stocker en session pour l'appliquer dans calculate_fees
        WC()->session->set('newsaiige_voucher_discount', $discount_amount);
        WC()->session->set('newsaiige_voucher_code', "POINTS{$points_to_use}");
        WC()->session->set('newsaiige_voucher_points_used', $points_to_use);
        
        // Recalculer les totaux
        WC()->cart->calculate_totals();
        
        wp_send_json_success(array(
            'message' => sprintf('%d points utilisés pour %.2f€ de réduction', $points_to_use, $discount_amount),
            'discount' => $discount_amount
        ));
    }
    
    /**
     * AJAX - Retirer un voucher
     */
    public function ajax_remove_voucher() {
        check_ajax_referer('newsaiige_loyalty_nonce', 'nonce');
        
        // Supprimer de la session
        WC()->session->__unset('newsaiige_voucher_discount');
        WC()->session->__unset('newsaiige_voucher_code');
        WC()->session->__unset('newsaiige_voucher_points_used');
        
        // Recalculer les totaux
        WC()->cart->calculate_totals();
        
        wp_send_json_success('Voucher retiré');
    }
    
    /**
     * Suivre l'utilisation des vouchers
     */
    public function track_voucher_usage($coupon_code) {
        // Logique pour suivre l'usage des coupons de fidélité
        if (strpos($coupon_code, 'POINTS') === 0) {
            // C'est un de nos vouchers de points
            do_action('newsaiige_loyalty_voucher_used', $coupon_code);
        }
    }
    
    /**
     * Charger les scripts et styles
     */
    public function enqueue_scripts() {
        if (!is_cart() && !is_checkout()) {
            return;
        }
        
        // CSS inline pour éviter un fichier séparé
        wp_add_inline_style('woocommerce-general', '
            .newsaiige-product-points {
                margin: 10px 0;
                padding: 10px;
                background: #f8f9fa;
                border-left: 3px solid #007cba;
                font-size: 14px;
            }
            .newsaiige-cart-points {
                background-color: #f8f9fa;
            }
            .points-earned {
                font-weight: 600;
                color: #28a745;
            }
            .points-current {
                color: #6c757d;
                font-size: 0.9em;
            }
            #use-loyalty-points {
                font-size: 0.9em;
                margin-top: 5px;
            }
        ');
        
        // JavaScript inline pour les interactions
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                $("#use-loyalty-points").on("click", function(e) {
                    e.preventDefault();
                    
                    var points = prompt("Combien de points voulez-vous utiliser ? (par multiples de 100)");
                    if (!points || points < 100) return;
                    
                    points = Math.floor(points / 100) * 100; // Arrondir aux centaines
                    
                    $.ajax({
                        url: wc_checkout_params.ajax_url,
                        type: "POST",
                        data: {
                            action: "apply_loyalty_voucher",
                            points: points,
                            nonce: "' . wp_create_nonce('newsaiige_loyalty_nonce') . '"
                        },
                        success: function(response) {
                            if (response.success) {
                                alert(response.data.message);
                                $("body").trigger("update_checkout");
                            } else {
                                alert("Erreur: " + response.data);
                            }
                        }
                    });
                });
            });
        ');
    }
}

// Initialiser l'intégration WooCommerce seulement si WooCommerce est actif
function newsaiige_woocommerce_init() {
    if (class_exists('WooCommerce')) {
        return NewsaiigeLoyaltyWooCommerceSafe::get_instance();
    }
}

// Démarrer l'intégration après que tous les plugins soient chargés
add_action('plugins_loaded', 'newsaiige_woocommerce_init', 25);

// Variable globale pour compatibilité
global $newsaiige_loyalty_woocommerce;
$newsaiige_loyalty_woocommerce = NewsaiigeLoyaltyWooCommerceSafe::get_instance();
?>