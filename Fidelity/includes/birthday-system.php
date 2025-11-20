<?php
/**
 * Système d'anniversaire pour le programme de fidélité
 * Gère l'envoi automatique de bons d'achat selon le palier
 */

if (!defined('ABSPATH')) {
    exit;
}

class NewsaiigeBirthdaySystem {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Hook pour le cron quotidien
        add_action('newsaiige_daily_birthday_check', array($this, 'check_birthdays'));
        
        // Enregistrer le cron si ce n'est pas déjà fait
        if (!wp_next_scheduled('newsaiige_daily_birthday_check')) {
            wp_schedule_event(time(), 'daily', 'newsaiige_daily_birthday_check');
        }
        
        // Hook pour sauvegarder la date d'anniversaire
        add_action('user_register', array($this, 'save_birthday_on_register'), 10, 1);
        
        // AJAX pour mise à jour de la date d'anniversaire
        add_action('wp_ajax_update_birthday', array($this, 'ajax_update_birthday'));
    }
    
    /**
     * Sauvegarder la date d'anniversaire lors de l'inscription
     */
    public function save_birthday_on_register($user_id) {
        if (isset($_POST['birthday']) && !empty($_POST['birthday'])) {
            $birthday = sanitize_text_field($_POST['birthday']);
            update_user_meta($user_id, 'birthday', $birthday);
        }
    }
    
    /**
     * AJAX - Mise à jour de la date d'anniversaire
     */
    public function ajax_update_birthday() {
        check_ajax_referer('update_birthday_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Non connecté');
        }
        
        $birthday = sanitize_text_field($_POST['birthday']);
        
        // Valider le format de date
        if (!$this->validate_date($birthday)) {
            wp_send_json_error('Format de date invalide');
        }
        
        update_user_meta($user_id, 'birthday', $birthday);
        wp_send_json_success('Date d\'anniversaire mise à jour');
    }
    
    /**
     * Valider le format de date (YYYY-MM-DD)
     */
    private function validate_date($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Vérifier les anniversaires du jour
     */
    public function check_birthdays() {
        global $wpdb;
        
        // Date du jour (mois-jour uniquement)
        $today = date('m-d');
        
        error_log('=== Vérification des anniversaires du ' . date('Y-m-d') . ' ===');
        
        // Récupérer tous les utilisateurs
        $users = get_users(array(
            'meta_key' => 'birthday',
            'meta_compare' => 'EXISTS'
        ));
        
        error_log('Nombre d\'utilisateurs avec date d\'anniversaire: ' . count($users));
        
        foreach ($users as $user) {
            $birthday = get_user_meta($user->ID, 'birthday', true);
            
            if (empty($birthday)) {
                error_log('User ' . $user->ID . ': Pas de date d\'anniversaire');
                continue;
            }
            
            // Extraire mois-jour de la date d'anniversaire
            $birthday_md = date('m-d', strtotime($birthday));
            
            error_log('User ' . $user->ID . ' (' . $user->user_email . '): Birthday=' . $birthday . ' -> ' . $birthday_md . ' vs Today=' . $today);
            
            // Vérifier si c'est aujourd'hui
            if ($birthday_md === $today) {
                // Vérifier si on a déjà envoyé un bon cette année
                $last_sent = get_user_meta($user->ID, 'birthday_coupon_last_sent', true);
                $current_year = date('Y');
                
                error_log('🎂 C\'est l\'anniversaire de User ' . $user->ID . '! Last sent: ' . $last_sent . ' vs ' . $current_year);
                
                if ($last_sent !== $current_year) {
                    error_log('Envoi du coupon d\'anniversaire à User ' . $user->ID);
                    $this->send_birthday_coupon($user->ID);
                    update_user_meta($user->ID, 'birthday_coupon_last_sent', $current_year);
                } else {
                    error_log('Coupon déjà envoyé cette année pour User ' . $user->ID);
                }
            }
        }
        
        error_log('=== Fin de la vérification des anniversaires ===');
    }
    
    /**
     * Envoyer le bon d'anniversaire selon le palier
     */
    public function send_birthday_coupon($user_id) {
        // Récupérer le palier actuel de l'utilisateur
        $tier = $this->get_user_tier($user_id);
        
        // Définir les réductions par palier
        $discount_by_tier = array(
            'bronze' => 0,      // Pas de réduction
            'argent' => 15,     // 15%
            'or' => 15,         // 15%
            'platine' => 30     // 30%
        );
        
        $discount = isset($discount_by_tier[$tier]) ? $discount_by_tier[$tier] : 0;
        
        // Si pas de réduction (bronze), on envoie quand même un email de voeux
        if ($discount === 0) {
            $this->send_birthday_email_no_coupon($user_id);
            return;
        }
        
        // Créer le coupon WooCommerce
        $coupon_code = $this->create_birthday_coupon($user_id, $discount);
        
        if ($coupon_code) {
            // Envoyer l'email avec le bon
            $this->send_birthday_email_with_coupon($user_id, $coupon_code, $discount, $tier);
        }
    }
    
    /**
     * Récupérer le palier actuel de l'utilisateur
     */
    private function get_user_tier($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'newsaiige_loyalty_user_tiers';
        
        $tier = $wpdb->get_var($wpdb->prepare(
            "SELECT tier_name FROM $table WHERE user_id = %d ORDER BY achieved_at DESC LIMIT 1",
            $user_id
        ));
        
        $result = $tier ? strtolower($tier) : 'bronze';
        error_log('User ' . $user_id . ' tier: ' . $result . ' (from DB: ' . ($tier ? $tier : 'NULL') . ')');
        
        return $result;
    }
    
    /**
     * Créer un coupon WooCommerce pour l'anniversaire
     */
    private function create_birthday_coupon($user_id, $discount) {
        $user = get_userdata($user_id);
        
        // Code unique du coupon
        $coupon_code = 'BIRTHDAY' . date('Y') . '_' . $user_id . '_' . wp_generate_password(6, false);
        
        // Créer le coupon
        $coupon = array(
            'post_title' => $coupon_code,
            'post_content' => 'Bon d\'anniversaire automatique - ' . $discount . '%',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'shop_coupon'
        );
        
        $new_coupon_id = wp_insert_post($coupon);
        
        if ($new_coupon_id) {
            // Date d'expiration : 7 jours
            $expiry_date = date('Y-m-d', strtotime('+7 days'));
            
            // Métadonnées du coupon
            update_post_meta($new_coupon_id, 'discount_type', 'percent');
            update_post_meta($new_coupon_id, 'coupon_amount', $discount);
            update_post_meta($new_coupon_id, 'individual_use', 'yes');
            update_post_meta($new_coupon_id, 'usage_limit', '1');
            update_post_meta($new_coupon_id, 'usage_limit_per_user', '1');
            update_post_meta($new_coupon_id, 'date_expires', strtotime($expiry_date));
            update_post_meta($new_coupon_id, 'free_shipping', 'no');
            update_post_meta($new_coupon_id, 'customer_email', array($user->user_email));
            update_post_meta($new_coupon_id, 'newsaiige_birthday_coupon', 'yes');
            update_post_meta($new_coupon_id, 'newsaiige_user_id', $user_id);
            
            return $coupon_code;
        }
        
        return false;
    }
    
    /**
     * Envoyer l'email avec le bon d'achat
     */
    private function send_birthday_email_with_coupon($user_id, $coupon_code, $discount, $tier) {
        $user = get_userdata($user_id);
        $first_name = get_user_meta($user_id, 'first_name', true);
        
        // Fallback si le prénom est vide
        if (empty($first_name)) {
            $first_name = !empty($user->display_name) ? $user->display_name : $user->user_login;
        }
        
        $tier_names = array(
            'argent' => 'Argent',
            'or' => 'Or',
            'platine' => 'Platine'
        );
        
        $tier_display = isset($tier_names[$tier]) ? $tier_names[$tier] : ucfirst($tier);
        
        $to = $user->user_email;
        $subject = '🎉 Joyeux anniversaire ' . $first_name . ' ! Votre cadeau vous attend';
        
        $message = '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #82897F 0%, #6d7569 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .coupon-box { background: white; border: 3px dashed #82897F; padding: 20px; margin: 20px 0; text-align: center; border-radius: 10px; }
                .coupon-code { font-size: 28px; font-weight: bold; color: #82897F; letter-spacing: 2px; margin: 15px 0; }
                .discount { font-size: 48px; font-weight: bold; color: #82897F; }
                .tier-badge { display: inline-block; background: #82897F; color: white; padding: 8px 20px; border-radius: 20px; font-weight: bold; margin: 10px 0; }
                .expiry { color: #e74c3c; font-weight: bold; margin: 10px 0; }
                .button { display: inline-block; background: #82897F; color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; margin: 20px 0; font-weight: bold; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🎂 Joyeux Anniversaire !</h1>
                    <p>Cher(e) ' . esc_html($first_name) . ',</p>
                </div>
                <div class="content">
                    <p>Toute l\'équipe NewSaiige vous souhaite un très joyeux anniversaire ! 🎉</p>
                    
                    <p>En tant que membre <span class="tier-badge">' . esc_html($tier_display) . '</span> de notre programme de fidélité, nous sommes ravis de vous offrir un cadeau spécial :</p>
                    
                    <div class="coupon-box">
                        <div class="discount">-' . $discount . '%</div>
                        <p style="font-size: 18px; margin: 10px 0;">sur votre prochaine commande !</p>
                        
                        <p style="margin-top: 20px;"><strong>Votre code promo :</strong></p>
                        <div class="coupon-code">' . esc_html($coupon_code) . '</div>
                        
                        <p class="expiry">⏰ Valable pendant 7 jours</p>
                        <p style="font-size: 13px; color: #666;">Expire le ' . date('d/m/Y', strtotime('+7 days')) . '</p>
                    </div>
                    
                    <p style="text-align: center;">
                        <a href="' . home_url('/shop') . '" class="button">Profiter de mon cadeau</a>
                    </p>
                    
                    <p style="margin-top: 30px; font-size: 14px; color: #666;">
                        Ce bon est personnel et utilisable une seule fois. Il ne peut pas être cumulé avec d\'autres promotions.
                    </p>
                    
                    <p>Encore un très bel anniversaire ! 🎈</p>
                    
                    <p style="margin-top: 20px;">
                        Avec toute notre affection,<br>
                        <strong>L\'équipe NewSaiige</strong>
                    </p>
                </div>
                <div class="footer">
                    <p>© ' . date('Y') . ' NewSaiige - Tous droits réservés</p>
                    <p><a href="' . home_url() . '" style="color: #82897F;">Visiter notre boutique</a></p>
                </div>
            </div>
        </body>
        </html>';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: NewSaiige <noreply@newsaiige.com>'
        );
        
        wp_mail($to, $subject, $message, $headers);
        
        // Logger l'envoi
        error_log('Birthday coupon sent to user ' . $user_id . ' - Code: ' . $coupon_code);
    }
    
    /**
     * Envoyer un email d'anniversaire sans bon (pour Bronze)
     */
    private function send_birthday_email_no_coupon($user_id) {
        $user = get_userdata($user_id);
        $first_name = get_user_meta($user_id, 'first_name', true);
        
        // Fallback si le prénom est vide
        if (empty($first_name)) {
            $first_name = !empty($user->display_name) ? $user->display_name : $user->user_login;
        }
        
        $to = $user->user_email;
        $subject = '🎉 Joyeux anniversaire ' . $first_name . ' !';
        
        $message = '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #82897F 0%, #6d7569 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; background: #82897F; color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; margin: 20px 0; font-weight: bold; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🎂 Joyeux Anniversaire !</h1>
                    <p>Cher(e) ' . esc_html($first_name) . ',</p>
                </div>
                <div class="content">
                    <p>Toute l\'équipe NewSaiige vous souhaite un très joyeux anniversaire ! 🎉</p>
                    
                    <p>Nous sommes ravis de vous compter parmi nos clients fidèles.</p>
                    
                    <p>Pour profiter d\'avantages exclusifs à votre anniversaire, nous vous invitons à progresser dans notre programme de fidélité ! À partir du palier Argent, vous recevrez un bon d\'achat spécial chaque année.</p>
                    
                    <p style="text-align: center;">
                        <a href="' . home_url('/mon-compte') . '" class="button">Voir mes points de fidélité</a>
                    </p>
                    
                    <p>Encore un très bel anniversaire ! 🎈</p>
                    
                    <p style="margin-top: 20px;">
                        Avec toute notre affection,<br>
                        <strong>L\'équipe NewSaiige</strong>
                    </p>
                </div>
                <div class="footer">
                    <p>© ' . date('Y') . ' NewSaiige - Tous droits réservés</p>
                </div>
            </div>
        </body>
        </html>';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: NewSaiige <noreply@newsaiige.com>'
        );
        
        wp_mail($to, $subject, $message, $headers);
    }
}

// Initialiser le système d'anniversaire
new NewsaiigeBirthdaySystem();
