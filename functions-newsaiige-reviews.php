<?php
/**
 * NEWSAIIGE REVIEWS - Code à ajouter dans functions.php
 * Interface d'administration simplifiée pour WordPress
 */

// ===== SYSTÈME DE BASE =====

// 1. AJAX Handlers pour frontend
add_action('wp_ajax_submit_newsaiige_review', 'handle_submit_newsaiige_review');
add_action('wp_ajax_nopriv_submit_newsaiige_review', 'handle_submit_newsaiige_review');
add_action('wp_ajax_get_newsaiige_reviews', 'handle_get_newsaiige_reviews');
add_action('wp_ajax_nopriv_get_newsaiige_reviews', 'handle_get_newsaiige_reviews');

// 2. Menu admin
add_action('admin_menu', 'newsaiige_reviews_admin_menu');

// 3. AJAX admin
add_action('wp_ajax_newsaiige_admin_action', 'handle_newsaiige_admin_action');

// ===== FONCTIONS FRONTEND =====

function handle_submit_newsaiige_review() {
    // Vérification nonce
    if (!wp_verify_nonce($_POST['nonce'], 'newsaiige_review_nonce')) {
        wp_send_json_error('Erreur de sécurité');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_reviews';
    
    // Validation des données
    $customer_name = sanitize_text_field($_POST['customer_name']);
    $customer_email = sanitize_email($_POST['customer_email']);
    $rating = intval($_POST['rating']);
    $comment = sanitize_textarea_field($_POST['comment']);
    
    if (empty($customer_name) || empty($comment) || $rating < 1 || $rating > 5) {
        wp_send_json_error('Données invalides');
    }
    
    // Vérifier doublons (même email, dernières 24h)
    if (!empty($customer_email)) {
        $recent = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE customer_email = %s 
             AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            $customer_email
        ));
        
        if ($recent > 0) {
            wp_send_json_error('Vous avez déjà laissé un avis récemment.');
        }
    }
    
    // Insérer en base
    $result = $wpdb->insert(
        $table_name,
        array(
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'rating' => $rating,
            'comment' => $comment,
            'status' => 'pending',
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ),
        array('%s', '%s', '%d', '%s', '%s', '%s', '%s')
    );
    
    if ($result === false) {
        wp_send_json_error('Erreur lors de l\'enregistrement');
    }
    
    // Notification email admin
    $admin_email = get_option('admin_email');
    wp_mail($admin_email, '[NEWSAIIGE] Nouvel avis client', 
        "Nouvel avis de {$customer_name} ({$rating}/5 étoiles)\n\nModérer: " . admin_url('admin.php?page=newsaiige-reviews'));
    
    wp_send_json_success('Merci pour votre avis ! Il sera publié après modération.');
}

function handle_get_newsaiige_reviews() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_reviews';
    
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    
    // Récupérer avis approuvés
    $reviews = $wpdb->get_results($wpdb->prepare(
        "SELECT customer_name, rating, comment, created_at 
         FROM $table_name 
         WHERE status = 'approved' 
         ORDER BY created_at DESC 
         LIMIT %d",
        $limit
    ));
    
    // Statistiques
    $stats = $wpdb->get_row(
        "SELECT 
            COUNT(*) as total_reviews,
            AVG(rating) as average_rating
         FROM $table_name 
         WHERE status = 'approved'"
    );
    
    wp_send_json_success(array(
        'reviews' => $reviews,
        'stats' => $stats
    ));
}

// ===== INTERFACE ADMIN =====

function newsaiige_reviews_admin_menu() {
    add_menu_page(
        'Avis NEWSAIIGE',
        'Avis Clients',
        'manage_options',
        'newsaiige-reviews',
        'newsaiige_reviews_admin_page',
        'dashicons-star-filled',
        30
    );
}

function newsaiige_reviews_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_reviews';
    
    // Traitement des actions
    if (isset($_POST['action']) && wp_verify_nonce($_POST['_wpnonce'], 'newsaiige_admin_nonce')) {
        $action = sanitize_text_field($_POST['action']);
        $review_id = intval($_POST['review_id']);
        
        switch ($action) {
            case 'approve':
                $wpdb->update($table_name, array('status' => 'approved'), array('id' => $review_id), array('%s'), array('%d'));
                break;
            case 'reject':
                $wpdb->update($table_name, array('status' => 'rejected'), array('id' => $review_id), array('%s'), array('%d'));
                break;
            case 'delete':
                $wpdb->delete($table_name, array('id' => $review_id), array('%d'));
                break;
        }
        echo '<div class="notice notice-success"><p>Action effectuée avec succès !</p></div>';
    }
    
    // Récupérer tous les avis
    $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
    $where = $status_filter !== 'all' ? $wpdb->prepare("WHERE status = %s", $status_filter) : '';
    
    $reviews = $wpdb->get_results("SELECT * FROM $table_name $where ORDER BY created_at DESC");
    
    // Compter par statut
    $counts = $wpdb->get_results("SELECT status, COUNT(*) as count FROM $table_name GROUP BY status");
    $status_counts = array('pending' => 0, 'approved' => 0, 'rejected' => 0);
    foreach ($counts as $count) {
        $status_counts[$count->status] = $count->count;
    }
    ?>
    
    <div class="wrap">
        <h1>Gestion des Avis Clients NEWSAIIGE</h1>
        
        <!-- Filtres -->
        <div class="tablenav top">
            <div class="alignleft actions">
                <select onchange="window.location.href='<?php echo admin_url('admin.php?page=newsaiige-reviews'); ?>&status=' + this.value">
                    <option value="all" <?php selected($status_filter, 'all'); ?>>Tous (<?php echo array_sum($status_counts); ?>)</option>
                    <option value="pending" <?php selected($status_filter, 'pending'); ?>>En attente (<?php echo $status_counts['pending']; ?>)</option>
                    <option value="approved" <?php selected($status_filter, 'approved'); ?>>Approuvés (<?php echo $status_counts['approved']; ?>)</option>
                    <option value="rejected" <?php selected($status_filter, 'rejected'); ?>>Rejetés (<?php echo $status_counts['rejected']; ?>)</option>
                </select>
            </div>
        </div>
        
        <!-- Tableau des avis -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Note</th>
                    <th>Commentaire</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reviews)): ?>
                    <tr><td colspan="6">Aucun avis trouvé.</td></tr>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($review->customer_name); ?></strong>
                                <?php if (!empty($review->customer_email)): ?>
                                    <br><small><?php echo esc_html($review->customer_email); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span style="color: <?php echo $i <= $review->rating ? '#FFD700' : '#ddd'; ?>;">★</span>
                                <?php endfor; ?>
                                <br><small><?php echo $review->rating; ?>/5</small>
                            </td>
                            <td>
                                <div style="max-width: 300px;">
                                    <?php echo esc_html(wp_trim_words($review->comment, 15)); ?>
                                    <?php if (str_word_count($review->comment) > 15): ?>
                                        <div class="full-comment-<?php echo $review->id; ?>" style="display: none;">
                                            <?php echo esc_html($review->comment); ?>
                                        </div>
                                        <a href="#" onclick="document.querySelector('.full-comment-<?php echo $review->id; ?>').style.display='block'; this.style.display='none'; return false;">Voir plus</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-<?php echo $review->status; ?>" style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; 
                                    <?php 
                                    echo $review->status === 'pending' ? 'background: #fff3cd; color: #856404;' : 
                                        ($review->status === 'approved' ? 'background: #d4edda; color: #155724;' : 'background: #f8d7da; color: #721c24;');
                                    ?>">
                                    <?php 
                                    echo $review->status === 'pending' ? 'En attente' : 
                                        ($review->status === 'approved' ? 'Approuvé' : 'Rejeté');
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php echo date_i18n('d/m/Y H:i', strtotime($review->created_at)); ?>
                            </td>
                            <td>
                                <?php if ($review->status === 'pending'): ?>
                                    <form method="POST" style="display: inline;">
                                        <?php wp_nonce_field('newsaiige_admin_nonce'); ?>
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="review_id" value="<?php echo $review->id; ?>">
                                        <button type="submit" class="button button-primary button-small">Approuver</button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <?php wp_nonce_field('newsaiige_admin_nonce'); ?>
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="review_id" value="<?php echo $review->id; ?>">
                                        <button type="submit" class="button button-small">Rejeter</button>
                                    </form>
                                <?php elseif ($review->status === 'approved'): ?>
                                    <form method="POST" style="display: inline;">
                                        <?php wp_nonce_field('newsaiige_admin_nonce'); ?>
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="review_id" value="<?php echo $review->id; ?>">
                                        <button type="submit" class="button button-small">Rejeter</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <?php wp_nonce_field('newsaiige_admin_nonce'); ?>
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="review_id" value="<?php echo $review->id; ?>">
                                        <button type="submit" class="button button-primary button-small">Approuver</button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer définitivement cet avis ?');">
                                    <?php wp_nonce_field('newsaiige_admin_nonce'); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="review_id" value="<?php echo $review->id; ?>">
                                    <button type="submit" class="button button-small button-link-delete">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Statistiques rapides -->
        <?php
        $stats = $wpdb->get_row("SELECT COUNT(*) as total, AVG(rating) as avg_rating FROM $table_name WHERE status = 'approved'");
        ?>
        <div style="margin-top: 20px; padding: 15px; background: white; border: 1px solid #ccd0d4; border-radius: 5px;">
            <h3>Statistiques</h3>
            <p><strong>Total avis approuvés :</strong> <?php echo number_format($stats->total); ?></p>
            <p><strong>Note moyenne :</strong> <?php echo number_format($stats->avg_rating, 1); ?>/5</p>
        </div>
    </div>
    
    <?php
}

// ===== SHORTCODE =====

function newsaiige_reviews_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 10
    ), $atts);
    
    // Enqueue les scripts
    wp_enqueue_script('jquery');
    wp_add_inline_script('jquery', '
        const newsaiige_ajax = {
            ajax_url: "' . admin_url('admin-ajax.php') . '",
            nonce: "' . wp_create_nonce('newsaiige_review_nonce') . '"
        };
    ');
    
    // Inclure le fichier reviews.php
    ob_start();
    include(get_template_directory() . '/reviews.php');
    return ob_get_clean();
}

add_shortcode('newsaiige_reviews', 'newsaiige_reviews_shortcode');

?>