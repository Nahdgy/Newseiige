<?php
/**
 * Interface d'administration pour le système de fidélité Newsaiige
 * Gestion des paliers, paramètres et statistiques
 */

// Ajouter le menu d'administration
add_action('admin_menu', 'newsaiige_loyalty_admin_menu');

function newsaiige_loyalty_admin_menu() {
    add_menu_page(
        'Programme de Fidélité',
        'Fidélité',
        'manage_options',
        'newsaiige-loyalty',
        'newsaiige_loyalty_admin_page',
        'dashicons-heart',
        56
    );
    
    add_submenu_page(
        'newsaiige-loyalty',
        'Paliers',
        'Paliers',
        'manage_options',
        'newsaiige-loyalty-tiers',
        'newsaiige_loyalty_tiers_page'
    );
    
    add_submenu_page(
        'newsaiige-loyalty',
        'Règles de Conversion',
        'Conversions',
        'manage_options',
        'newsaiige-loyalty-conversions',
        'newsaiige_loyalty_conversions_page'
    );
    
    add_submenu_page(
        'newsaiige-loyalty',
        'Utilisateurs',
        'Utilisateurs',
        'manage_options',
        'newsaiige-loyalty-users',
        'newsaiige_loyalty_users_page'
    );
    
    add_submenu_page(
        'newsaiige-loyalty',
        'Paramètres',
        'Paramètres',
        'manage_options',
        'newsaiige-loyalty-settings',
        'newsaiige_loyalty_settings_page'
    );
    
    add_submenu_page(
        'newsaiige-loyalty',
        'Recalculer les paliers',
        '🔄 Recalcul Paliers',
        'manage_options',
        'newsaiige-loyalty-recalculate',
        'newsaiige_loyalty_recalculate_page'
    );
    
    add_submenu_page(
        'newsaiige-loyalty',
        'Importer Points Historiques',
        '📥 Import Historique',
        'manage_options',
        'newsaiige-loyalty-import-history',
        'newsaiige_loyalty_import_history_page'
    );
    
    add_submenu_page(
        'newsaiige-loyalty',
        'Réactiver les Points',
        '🔓 Réactiver Points',
        'manage_options',
        'newsaiige-loyalty-reactivate-points',
        'newsaiige_loyalty_reactivate_points_page'
    );
    
    add_submenu_page(
        'newsaiige-loyalty',
        'Retraiter les Points',
        '♻️ Retraiter Points',
        'manage_options',
        'newsaiige-loyalty-reprocess-points',
        'newsaiige_loyalty_reprocess_points_page'
    );
}

// Page principale d'administration
function newsaiige_loyalty_admin_page() {
    global $wpdb;
    
    $points_table = $wpdb->prefix . 'newsaiige_loyalty_points';
    $vouchers_table = $wpdb->prefix . 'newsaiige_loyalty_vouchers';
    $tiers_table = $wpdb->prefix . 'newsaiige_loyalty_tiers';
    
    // Statistiques globales
    $total_points_earned = $wpdb->get_var("SELECT SUM(points_earned) FROM $points_table");
    $total_points_used = $wpdb->get_var("SELECT SUM(points_used) FROM $points_table");
    $active_vouchers = $wpdb->get_var("SELECT COUNT(*) FROM $vouchers_table WHERE is_used = 0 AND expires_at > NOW()");
    $total_users = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $points_table");
    
    // Points par mois (derniers 6 mois)
    $points_by_month = $wpdb->get_results("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(points_earned) as points_earned,
            SUM(points_used) as points_used,
            COUNT(DISTINCT user_id) as active_users
        FROM $points_table 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
    ");
    
    // Top utilisateurs
    $top_users = $wpdb->get_results("
        SELECT 
            u.display_name,
            u.user_email,
            SUM(p.points_earned) as total_points,
            SUM(p.points_available) as available_points,
            t.tier_name
        FROM $points_table p
        JOIN {$wpdb->users} u ON p.user_id = u.ID
        LEFT JOIN {$wpdb->prefix}newsaiige_loyalty_user_tiers ut ON u.ID = ut.user_id AND ut.is_current = 1
        LEFT JOIN $tiers_table t ON ut.tier_id = t.id
        GROUP BY u.ID
        ORDER BY total_points DESC
        LIMIT 10
    ");
    ?>
    
    <div class="wrap">
        <h1>Programme de Fidélité - Tableau de bord</h1>
        
        <div class="loyalty-admin-dashboard">
            <style>
            .loyalty-admin-dashboard {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            
            .stat-card {
                background: white;
                border: 1px solid #e1e1e1;
                border-radius: 8px;
                padding: 20px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            
            .stat-card h3 {
                margin: 0 0 10px 0;
                color: #82897F;
                font-size: 14px;
                text-transform: uppercase;
                font-weight: 600;
            }
            
            .stat-value {
                font-size: 32px;
                font-weight: 700;
                color: #333;
                margin: 0;
            }
            
            .stat-description {
                color: #666;
                font-size: 12px;
                margin-top: 5px;
            }
            
            .admin-section {
                background: white;
                border: 1px solid #e1e1e1;
                border-radius: 8px;
                margin-bottom: 20px;
            }
            
            .admin-section-header {
                padding: 15px 20px;
                border-bottom: 1px solid #e1e1e1;
                background: #f8f9fa;
            }
            
            .admin-section-header h2 {
                margin: 0;
                font-size: 18px;
                color: #333;
            }
            
            .admin-section-content {
                padding: 20px;
            }
            
            .admin-table {
                width: 100%;
                border-collapse: collapse;
            }
            
            .admin-table th,
            .admin-table td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #e1e1e1;
            }
            
            .admin-table th {
                background: #f8f9fa;
                font-weight: 600;
                color: #333;
            }
            
            .tier-badge {
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
            }
            
            .tier-bronze { background: #cd7f32; color: white; }
            .tier-silver { background: #c0c0c0; color: white; }
            .tier-gold { background: #ffd700; color: #333; }
            .tier-platinum { background: #e5e4e2; color: #333; }
            </style>
            
            <!-- Statistiques globales -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Points Totaux Gagnés</h3>
                    <p class="stat-value"><?php echo number_format($total_points_earned ?: 0); ?></p>
                    <p class="stat-description">Depuis le lancement</p>
                </div>
                
                <div class="stat-card">
                    <h3>Points Utilisés</h3>
                    <p class="stat-value"><?php echo number_format($total_points_used ?: 0); ?></p>
                    <p class="stat-description">Convertis en bons d'achat</p>
                </div>
                
                <div class="stat-card">
                    <h3>Bons d'achat Actifs</h3>
                    <p class="stat-value"><?php echo $active_vouchers; ?></p>
                    <p class="stat-description">Non utilisés et valides</p>
                </div>
                
                <div class="stat-card">
                    <h3>Utilisateurs Actifs</h3>
                    <p class="stat-value"><?php echo $total_users; ?></p>
                    <p class="stat-description">Avec des points gagnés</p>
                </div>
            </div>
            
            <!-- Activité par mois -->
            <?php if (!empty($points_by_month)): ?>
            <div class="admin-section">
                <div class="admin-section-header">
                    <h2>Activité des derniers mois</h2>
                </div>
                <div class="admin-section-content">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Mois</th>
                                <th>Points Gagnés</th>
                                <th>Points Utilisés</th>
                                <th>Utilisateurs Actifs</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($points_by_month as $month_data): ?>
                            <tr>
                                <td><?php echo date('F Y', strtotime($month_data->month . '-01')); ?></td>
                                <td><?php echo number_format($month_data->points_earned); ?></td>
                                <td><?php echo number_format($month_data->points_used); ?></td>
                                <td><?php echo $month_data->active_users; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Top utilisateurs -->
            <?php if (!empty($top_users)): ?>
            <div class="admin-section">
                <div class="admin-section-header">
                    <h2>Top 10 des utilisateurs</h2>
                </div>
                <div class="admin-section-content">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Email</th>
                                <th>Points Totaux</th>
                                <th>Points Disponibles</th>
                                <th>Palier</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_users as $user): ?>
                            <tr>
                                <td><?php echo esc_html($user->display_name); ?></td>
                                <td><?php echo esc_html($user->user_email); ?></td>
                                <td><?php echo number_format($user->total_points); ?></td>
                                <td><?php echo number_format($user->available_points); ?></td>
                                <td>
                                    <?php if ($user->tier_name): ?>
                                        <span class="tier-badge tier-<?php echo strtolower($user->tier_name); ?>">
                                            <?php echo esc_html($user->tier_name); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="tier-badge tier-bronze">Aucun</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// Page de gestion des paliers
function newsaiige_loyalty_tiers_page() {
    global $wpdb;
    $tiers_table = $wpdb->prefix . 'newsaiige_loyalty_tiers';
    
    // Traitement des actions
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_tier' && wp_verify_nonce($_POST['_wpnonce'], 'loyalty_tier_action')) {
            $result = $wpdb->insert(
                $tiers_table,
                array(
                    'tier_name' => sanitize_text_field($_POST['tier_name']),
                    'tier_slug' => sanitize_title($_POST['tier_name']),
                    'points_required' => intval($_POST['points_required']),
                    'tier_order' => intval($_POST['tier_order']),
                    'benefits' => sanitize_textarea_field($_POST['benefits']),
                    'birthday_bonus_percentage' => intval($_POST['birthday_bonus_percentage'])
                )
            );
            
            if ($result) {
                echo '<div class="notice notice-success"><p>Palier ajouté avec succès !</p></div>';
            }
        }
        
        if ($_POST['action'] === 'update_tier' && wp_verify_nonce($_POST['_wpnonce'], 'loyalty_tier_action')) {
            $wpdb->update(
                $tiers_table,
                array(
                    'tier_name' => sanitize_text_field($_POST['tier_name']),
                    'points_required' => intval($_POST['points_required']),
                    'tier_order' => intval($_POST['tier_order']),
                    'benefits' => sanitize_textarea_field($_POST['benefits']),
                    'birthday_bonus_percentage' => intval($_POST['birthday_bonus_percentage']),
                    'is_active' => intval($_POST['is_active'])
                ),
                array('id' => intval($_POST['tier_id']))
            );
            
            echo '<div class="notice notice-success"><p>Palier mis à jour !</p></div>';
        }
    }
    
    if (isset($_GET['delete']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_tier')) {
        $wpdb->update(
            $tiers_table,
            array('is_active' => 0),
            array('id' => intval($_GET['delete']))
        );
        echo '<div class="notice notice-success"><p>Palier désactivé !</p></div>';
    }
    
    // Récupérer les paliers
    $tiers = $wpdb->get_results("SELECT * FROM $tiers_table ORDER BY tier_order ASC");
    ?>
    
    <div class="wrap">
        <h1>Gestion des Paliers de Fidélité</h1>
        
        <!-- Formulaire d'ajout -->
        <div class="loyalty-admin-form-card">
            <h2>Ajouter un nouveau palier</h2>
            <form method="post" action="">
                <?php wp_nonce_field('loyalty_tier_action'); ?>
                <input type="hidden" name="action" value="add_tier">
                
                <table class="form-table">
                    <tr>
                        <th><label for="tier_name">Nom du palier</label></th>
                        <td><input type="text" id="tier_name" name="tier_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="points_required">Points requis</label></th>
                        <td><input type="number" id="points_required" name="points_required" class="small-text" min="0" required></td>
                    </tr>
                    <tr>
                        <th><label for="tier_order">Ordre d'affichage</label></th>
                        <td><input type="number" id="tier_order" name="tier_order" class="small-text" min="1" required></td>
                    </tr>
                    <tr>
                        <th><label for="benefits">Avantages</label></th>
                        <td><textarea id="benefits" name="benefits" class="large-text" rows="3"></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="birthday_bonus_percentage">Bonus anniversaire (%)</label></th>
                        <td><input type="number" id="birthday_bonus_percentage" name="birthday_bonus_percentage" class="small-text" min="0" max="100" value="0"></td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="Ajouter le palier">
                </p>
            </form>
        </div>
        
        <!-- Liste des paliers existants -->
        <div class="loyalty-admin-table-card">
            <h2>Paliers existants</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Points requis</th>
                        <th>Ordre</th>
                        <th>Avantages</th>
                        <th>Bonus anniversaire</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tiers as $tier): ?>
                    <tr>
                        <td><strong><?php echo esc_html($tier->tier_name); ?></strong></td>
                        <td><?php echo number_format($tier->points_required); ?></td>
                        <td><?php echo $tier->tier_order; ?></td>
                        <td><?php echo esc_html(substr($tier->benefits, 0, 50)) . (strlen($tier->benefits) > 50 ? '...' : ''); ?></td>
                        <td><?php echo $tier->birthday_bonus_percentage; ?>%</td>
                        <td>
                            <span class="<?php echo $tier->is_active ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $tier->is_active ? 'Actif' : 'Inactif'; ?>
                            </span>
                        </td>
                        <td>
                            <a href="#edit-tier-<?php echo $tier->id; ?>" class="button button-small">Modifier</a>
                            <?php if ($tier->is_active): ?>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=newsaiige-loyalty-tiers&delete=' . $tier->id), 'delete_tier'); ?>" 
                               class="button button-small" onclick="return confirm('Êtes-vous sûr ?')">Désactiver</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <!-- Formulaire de modification (masqué) -->
                    <tr id="edit-tier-<?php echo $tier->id; ?>" style="display: none;">
                        <td colspan="7">
                            <form method="post" action="" style="padding: 20px; background: #f9f9f9;">
                                <?php wp_nonce_field('loyalty_tier_action'); ?>
                                <input type="hidden" name="action" value="update_tier">
                                <input type="hidden" name="tier_id" value="<?php echo $tier->id; ?>">
                                
                                <table class="form-table">
                                    <tr>
                                        <th><label>Nom du palier</label></th>
                                        <td><input type="text" name="tier_name" class="regular-text" value="<?php echo esc_attr($tier->tier_name); ?>" required></td>
                                    </tr>
                                    <tr>
                                        <th><label>Points requis</label></th>
                                        <td><input type="number" name="points_required" class="small-text" value="<?php echo $tier->points_required; ?>" required></td>
                                    </tr>
                                    <tr>
                                        <th><label>Ordre</label></th>
                                        <td><input type="number" name="tier_order" class="small-text" value="<?php echo $tier->tier_order; ?>" required></td>
                                    </tr>
                                    <tr>
                                        <th><label>Avantages</label></th>
                                        <td><textarea name="benefits" class="large-text" rows="3"><?php echo esc_textarea($tier->benefits); ?></textarea></td>
                                    </tr>
                                    <tr>
                                        <th><label>Bonus anniversaire (%)</label></th>
                                        <td><input type="number" name="birthday_bonus_percentage" class="small-text" value="<?php echo $tier->birthday_bonus_percentage; ?>" min="0" max="100"></td>
                                    </tr>
                                    <tr>
                                        <th><label>Statut</label></th>
                                        <td>
                                            <select name="is_active">
                                                <option value="1" <?php selected($tier->is_active, 1); ?>>Actif</option>
                                                <option value="0" <?php selected($tier->is_active, 0); ?>>Inactif</option>
                                            </select>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p>
                                    <input type="submit" class="button-primary" value="Mettre à jour">
                                    <a href="#" class="button" onclick="this.closest('tr').style.display='none'; return false;">Annuler</a>
                                </p>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion des formulaires de modification
        document.querySelectorAll('a[href^="#edit-tier"]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href').substring(1);
                const targetRow = document.getElementById(targetId);
                if (targetRow) {
                    targetRow.style.display = targetRow.style.display === 'none' ? 'table-row' : 'none';
                }
            });
        });
    });
    </script>
    
    <style>
    .status-active { color: #46b450; font-weight: bold; }
    .status-inactive { color: #dc3232; }
    .form-table th { width: 200px; }
    </style>
    <?php
}

// Page de gestion des utilisateurs
function newsaiige_loyalty_users_page() {
    global $wpdb, $newsaiige_loyalty;
    
    $points_table = $wpdb->prefix . 'newsaiige_loyalty_points';
    $tiers_table = $wpdb->prefix . 'newsaiige_loyalty_tiers';
    $vouchers_table = $wpdb->prefix . 'newsaiige_loyalty_vouchers';
    
    // Traitement des actions
    if (isset($_POST['action']) && $_POST['action'] === 'add_manual_points' && wp_verify_nonce($_POST['_wpnonce'], 'loyalty_user_action')) {
        $user_id = intval($_POST['user_id']);
        $points = intval($_POST['points']);
        $description = sanitize_text_field($_POST['description']);
        
        if ($user_id && $points > 0) {
            $expiry_days = 365; // Points manuels valables 1 an
            $expires_at = date('Y-m-d H:i:s', strtotime('+' . $expiry_days . ' days'));
            
            $wpdb->insert(
                $points_table,
                array(
                    'user_id' => $user_id,
                    'points_earned' => $points,
                    'points_available' => $points,
                    'action_type' => 'manual',
                    'description' => $description ?: 'Points ajoutés manuellement',
                    'expires_at' => $expires_at
                )
            );
            
            echo '<div class="notice notice-success"><p>Points ajoutés avec succès !</p></div>';
        }
    }
    
    // Vérifier un utilisateur spécifique
    if (isset($_POST['check_single_user']) && check_admin_referer('loyalty_check_subscriptions')) {
        $user_id = isset($_POST['subscription_user_id']) ? intval($_POST['subscription_user_id']) : 0;
        
        if ($user_id > 0) {
            $user = get_user_by('id', $user_id);
            
            if ($user) {
                // Diagnostic détaillé de l'abonnement WPS Subscriptions
                $debug_info = array();
                
                // PRIORITÉ 1 : Vérifier dans wc_orders (HPOS activé - WPS Subscriptions)
                $hpos_subscriptions = $wpdb->get_results($wpdb->prepare(
                    "SELECT id, status, type, date_created_gmt
                     FROM {$wpdb->prefix}wc_orders
                     WHERE type = 'wps_subscriptions'
                     AND customer_id = %d
                     ORDER BY date_created_gmt DESC",
                    $user_id
                ));
                
                if (!empty($hpos_subscriptions)) {
                    $debug_info[] = '✅ HPOS détecté - Abonnements WPS trouvés dans wc_orders : ' . count($hpos_subscriptions);
                    foreach ($hpos_subscriptions as $sub) {
                        $is_active = in_array($sub->status, array('wc-active', 'wc-pending-cancel', 'wc-wps_renewal', 'active'));
                        $status_icon = $is_active ? '✅' : '❌';
                        $debug_info[] = '&nbsp;&nbsp;' . $status_icon . ' Abonnement #' . $sub->id . ' - Statut : <strong>' . $sub->status . '</strong> (' . $sub->date_created_gmt . ')';
                    }
                } else {
                    $debug_info[] = '❌ Aucun abonnement trouvé dans wc_orders (HPOS)';
                }
                
                // PRIORITÉ 2 : Vérifier les commandes shop_order en cours (HPOS)
                $hpos_orders = $wpdb->get_results($wpdb->prepare(
                    "SELECT id, status, type, date_created_gmt
                     FROM {$wpdb->prefix}wc_orders
                     WHERE type = 'shop_order'
                     AND customer_id = %d
                     AND status = 'wc-processing'
                     ORDER BY date_created_gmt DESC
                     LIMIT 3",
                    $user_id
                ));
                
                if (!empty($hpos_orders)) {
                    $debug_info[] = '✅ Commandes en cours trouvées dans wc_orders : ' . count($hpos_orders);
                    foreach ($hpos_orders as $order) {
                        $debug_info[] = '&nbsp;&nbsp;✅ Commande #' . $order->id . ' - Statut : <strong>' . $order->status . '</strong> (' . $order->date_created_gmt . ')';
                    }
                }
                
                // PRIORITÉ 3 : Vérifier dans wp_posts (HPOS non activé)
                if (empty($hpos_subscriptions) && empty($hpos_orders)) {
                    $post_subscriptions = $wpdb->get_results($wpdb->prepare(
                        "SELECT p.ID, p.post_type, p.post_status, p.post_date
                         FROM {$wpdb->posts} p
                         INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                         WHERE p.post_type IN ('wps_subscriptions', 'shop_order')
                         AND pm.meta_key = '_customer_user'
                         AND pm.meta_value = %d
                         ORDER BY p.post_date DESC
                         LIMIT 5",
                        $user_id
                    ));
                    
                    if (!empty($post_subscriptions)) {
                        $debug_info[] = '✅ Éléments trouvés dans wp_posts : ' . count($post_subscriptions);
                        foreach ($post_subscriptions as $sub) {
                            $is_active = ($sub->post_type === 'wps_subscriptions' && in_array($sub->post_status, array('wc-active', 'wc-pending-cancel', 'wc-wps_renewal', 'active'))) 
                                      || ($sub->post_type === 'shop_order' && $sub->post_status === 'wc-processing');
                            $status_icon = $is_active ? '✅' : '❌';
                            $debug_info[] = '&nbsp;&nbsp;' . $status_icon . ' ' . ucfirst(str_replace('_', ' ', $sub->post_type)) . ' #' . $sub->ID . ' - Statut : <strong>' . $sub->post_status . '</strong>';
                        }
                    } else {
                        $debug_info[] = '❌ Aucun abonnement ou commande trouvé';
                        $debug_info[] = '🔍 L\'utilisateur n\'a aucun abonnement WPS Subscriptions ou commande en cours';
                    }
                }
                
                // Vérifier si l'utilisateur a un abonnement actif
                $has_subscription = $newsaiige_loyalty && $newsaiige_loyalty->has_active_subscription($user_id);
                
                if ($has_subscription) {
                    // Vérifier si l'utilisateur a déjà un palier
                    $existing_tier = $wpdb->get_var($wpdb->prepare("
                        SELECT COUNT(*)
                        FROM {$wpdb->prefix}newsaiige_loyalty_user_tiers
                        WHERE user_id = %d
                    ", $user_id));
                    
                    if ($existing_tier == 0) {
                        // Attribuer le palier Bronze par défaut
                        $bronze_tier = $wpdb->get_row("
                            SELECT id FROM {$tiers_table}
                            WHERE tier_slug = 'bronze'
                            LIMIT 1
                        ");
                        
                        if ($bronze_tier) {
                            $wpdb->insert(
                                $wpdb->prefix . 'newsaiige_loyalty_user_tiers',
                                array(
                                    'user_id' => $user_id,
                                    'tier_id' => $bronze_tier->id,
                                    'is_current' => 1
                                )
                            );
                            
                            echo '<div class="notice notice-success is-dismissible"><p>';
                            echo sprintf(
                                '<strong>✅ Succès !</strong><br>' .
                                'L\'utilisateur <strong>%s</strong> a un abonnement actif et a été ajouté au programme de fidélité avec le palier Bronze.',
                                esc_html($user->display_name)
                            );
                            echo '</p></div>';
                        }
                    } else {
                        echo '<div class="notice notice-info is-dismissible"><p>';
                        echo sprintf(
                            '<strong>ℹ️ Information :</strong><br>' .
                            'L\'utilisateur <strong>%s</strong> a un abonnement actif et fait déjà partie du programme de fidélité.',
                            esc_html($user->display_name)
                        );
                        echo '</p></div>';
                    }
                } else {
                    echo '<div class="notice notice-warning is-dismissible"><p>';
                    echo sprintf(
                        '<strong>⚠️ Attention :</strong><br>' .
                        'L\'utilisateur <strong>%s</strong> n\'a pas d\'abonnement actif détecté.<br><br>' .
                        '<strong>Diagnostic :</strong><br>%s',
                        esc_html($user->display_name),
                        implode('<br>', $debug_info)
                    );
                    echo '</p></div>';
                }
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>';
                echo '<strong>❌ Erreur :</strong> Utilisateur non trouvé.';
                echo '</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>';
            echo '<strong>❌ Erreur :</strong> Veuillez sélectionner un utilisateur.';
            echo '</p></div>';
        }
    }
    
    // Vérifier tous les abonnés
    if (isset($_POST['check_subscriptions']) && check_admin_referer('loyalty_check_subscriptions')) {
        $checked_users = 0;
        $added_users = 0;
        $subscribed_users = 0;
        
        // Récupérer tous les utilisateurs
        $all_users = get_users(array('number' => -1));
        
        foreach ($all_users as $user) {
            $checked_users++;
            
            // Vérifier si l'utilisateur a un abonnement actif
            if ($newsaiige_loyalty && $newsaiige_loyalty->has_active_subscription($user->ID)) {
                $subscribed_users++;
                
                // Vérifier si l'utilisateur a déjà des points ou un palier
                $existing_points = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*)
                    FROM {$points_table}
                    WHERE user_id = %d
                ", $user->ID));
                
                $existing_tier = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*)
                    FROM {$wpdb->prefix}newsaiige_loyalty_user_tiers
                    WHERE user_id = %d
                ", $user->ID));
                
                // Si l'utilisateur n'a ni points ni palier, l'ajouter
                if ($existing_points == 0 && $existing_tier == 0) {
                    // Attribuer le palier Bronze par défaut
                    $bronze_tier = $wpdb->get_row("
                        SELECT id FROM {$tiers_table}
                        WHERE tier_slug = 'bronze'
                        LIMIT 1
                    ");
                    
                    if ($bronze_tier) {
                        // Désactiver les anciens paliers
                        $wpdb->update(
                            $wpdb->prefix . 'newsaiige_loyalty_user_tiers',
                            array('is_current' => 0),
                            array('user_id' => $user->ID)
                        );
                        
                        // Ajouter le nouveau palier
                        $wpdb->insert(
                            $wpdb->prefix . 'newsaiige_loyalty_user_tiers',
                            array(
                                'user_id' => $user->ID,
                                'tier_id' => $bronze_tier->id,
                                'is_current' => 1
                            )
                        );
                        
                        $added_users++;
                    }
                }
            }
        }
        
        echo '<div class="notice notice-success is-dismissible"><p>';
        echo sprintf(
            '<strong>✅ Vérification terminée !</strong><br>' .
            '• %d utilisateurs vérifiés<br>' .
            '• %d utilisateurs avec abonnement actif<br>' .
            '• %d nouveaux utilisateurs ajoutés au programme',
            $checked_users,
            $subscribed_users,
            $added_users
        );
        echo '</p></div>';
    }
    
    // Nettoyer les utilisateurs sans abonnement WPS
    if (isset($_POST['cleanup_non_subscribers']) && check_admin_referer('loyalty_check_subscriptions')) {
        $removed_users = 0;
        $checked_users = 0;
        $kept_users = 0;
        
        // Récupérer tous les utilisateurs du programme de fidélité
        $loyalty_users = $wpdb->get_results("
            SELECT DISTINCT user_id 
            FROM {$wpdb->prefix}newsaiige_loyalty_user_tiers
        ");
        
        foreach ($loyalty_users as $loyalty_user) {
            $checked_users++;
            $user_id = $loyalty_user->user_id;
            $user = get_user_by('ID', $user_id);
            
            if (!$user) {
                continue;
            }
            
            // Vérifier si l'utilisateur a un abonnement WPS actif
            $has_wps_subscription = $newsaiige_loyalty && $newsaiige_loyalty->has_active_subscription($user_id);
            
            if (!$has_wps_subscription) {
                // Retirer l'utilisateur du programme de fidélité
                
                // 1. Supprimer le palier
                $wpdb->delete(
                    $wpdb->prefix . 'newsaiige_loyalty_user_tiers',
                    array('user_id' => $user_id)
                );
                
                // 2. Désactiver tous les points (ne pas supprimer pour garder l'historique)
                $wpdb->update(
                    $points_table,
                    array('is_active' => 0),
                    array('user_id' => $user_id)
                );
                
                $removed_users++;
                error_log("cleanup_non_subscribers: ✓ User {$user_id} ({$user->user_email}) retiré - Aucun abonnement WPS");
            } else {
                $kept_users++;
                error_log("cleanup_non_subscribers: ✓ User {$user_id} ({$user->user_email}) conservé - Abonnement WPS actif");
            }
        }
        
        echo '<div class="notice notice-success is-dismissible"><p>';
        echo sprintf(
            '<strong>🧹 Nettoyage terminé !</strong><br>' .
            '• %d utilisateurs vérifiés<br>' .
            '• %d utilisateurs conservés (avec abonnement WPS)<br>' .
            '• %d utilisateurs retirés (sans abonnement WPS)',
            $checked_users,
            $kept_users,
            $removed_users
        );
        echo '</p></div>';
    }
    
    // Recherche d'utilisateurs
    $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    $where_clause = '';
    if ($search) {
        $where_clause = $wpdb->prepare(
            "WHERE (u.display_name LIKE %s OR u.user_email LIKE %s)",
            '%' . $search . '%',
            '%' . $search . '%'
        );
    }
    
    // Récupérer les utilisateurs avec leurs points
    $users_data = $wpdb->get_results("
        SELECT 
            u.ID,
            u.display_name,
            u.user_email,
            u.user_registered,
            COALESCE(SUM(p.points_earned), 0) as total_points,
            COALESCE(SUM(CASE WHEN p.is_active = 1 AND (p.expires_at IS NULL OR p.expires_at > NOW()) THEN p.points_available ELSE 0 END), 0) as available_points,
            COUNT(DISTINCT v.id) as voucher_count,
            t.tier_name,
            t.tier_slug
        FROM {$wpdb->users} u
        LEFT JOIN $points_table p ON u.ID = p.user_id
        LEFT JOIN $vouchers_table v ON u.ID = v.user_id AND v.is_used = 0 AND v.expires_at > NOW()
        LEFT JOIN {$wpdb->prefix}newsaiige_loyalty_user_tiers ut ON u.ID = ut.user_id AND ut.is_current = 1
        LEFT JOIN $tiers_table t ON ut.tier_id = t.id
        $where_clause
        GROUP BY u.ID
        HAVING COALESCE(SUM(p.points_earned), 0) > 0 OR COALESCE(SUM(CASE WHEN p.is_active = 1 AND (p.expires_at IS NULL OR p.expires_at > NOW()) THEN p.points_available ELSE 0 END), 0) > 0
        ORDER BY COALESCE(SUM(p.points_earned), 0) DESC
        LIMIT 50
    ");
    
    // Ajouter les utilisateurs avec abonnement actif même sans points
    global $newsaiige_loyalty;
    if ($newsaiige_loyalty && !$search) {
        $all_users = get_users(array('number' => 500));
        $users_with_subscription = array();
        
        foreach ($all_users as $user) {
            // Vérifier si l'utilisateur a déjà été inclus
            $already_included = false;
            foreach ($users_data as $existing_user) {
                if ($existing_user->ID == $user->ID) {
                    $already_included = true;
                    break;
                }
            }
            
            // Si pas déjà inclus et a un abonnement actif, l'ajouter
            if (!$already_included && $newsaiige_loyalty->has_active_subscription($user->ID)) {
                $user_obj = new stdClass();
                $user_obj->ID = $user->ID;
                $user_obj->display_name = $user->display_name;
                $user_obj->user_email = $user->user_email;
                $user_obj->user_registered = $user->user_registered;
                $user_obj->total_points = 0;
                $user_obj->available_points = 0;
                $user_obj->voucher_count = 0;
                $user_obj->tier_name = null;
                $user_obj->tier_slug = null;
                $users_with_subscription[] = $user_obj;
            }
        }
        
        // Fusionner les deux listes
        $users_data = array_merge($users_data, $users_with_subscription);
    }
    ?>
    
    <div class="wrap">
        <h1>Gestion des Utilisateurs - Programme de Fidélité</h1>
        
        <!-- Recherche -->
        <div class="loyalty-admin-search-card">
            <form method="get" action="">
                <input type="hidden" name="page" value="newsaiige-loyalty-users">
                <p class="search-box">
                    <label class="screen-reader-text" for="user-search-input">Rechercher des utilisateurs:</label>
                    <input type="search" id="user-search-input" name="search" value="<?php echo esc_attr($search); ?>" placeholder="Nom ou email...">
                    <input type="submit" id="search-submit" class="button" value="Rechercher">
                </p>
            </form>
        </div>
        
        <!-- Ajouter des points manuellement -->
        <div class="loyalty-admin-form-card">
            <h2>Ajouter des points manuellement</h2>
            <form method="post" action="">
                <?php wp_nonce_field('loyalty_user_action'); ?>
                <input type="hidden" name="action" value="add_manual_points">
                
                <table class="form-table">
                    <tr>
                        <th><label for="user_id">Utilisateur</label></th>
                        <td>
                            <select id="user_id" name="user_id" required style="min-width: 300px;">
                                <option value="">Sélectionner un utilisateur...</option>
                                <?php
                                $all_users = get_users(array('orderby' => 'display_name'));
                                foreach ($all_users as $user) {
                                    echo '<option value="' . $user->ID . '">' . esc_html($user->display_name . ' (' . $user->user_email . ')') . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="points">Nombre de points</label></th>
                        <td><input type="number" id="points" name="points" class="small-text" min="1" required></td>
                    </tr>
                    <tr>
                        <th><label for="description">Description (optionnel)</label></th>
                        <td><input type="text" id="description" name="description" class="regular-text" placeholder="Raison de l'ajout de points"></td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="Ajouter les points">
                </p>
            </form>
        </div>
        
        <!-- Vérifier le statut d'abonné -->
        <div class="loyalty-admin-form-card">
            <h2>Vérifier et ajouter des abonnés au programme</h2>
            <form method="post" action="">
                <?php wp_nonce_field('loyalty_check_subscriptions'); ?>
                <input type="hidden" name="action" value="check_subscription">
                
                <table class="form-table">
                    <tr>
                        <th><label for="subscription_user_id">Vérifier un utilisateur</label></th>
                        <td>
                            <select id="subscription_user_id" name="subscription_user_id" style="min-width: 300px;">
                                <option value="">Sélectionner un utilisateur...</option>
                                <?php
                                $all_users_sub = get_users(array('orderby' => 'display_name', 'number' => -1));
                                foreach ($all_users_sub as $user) {
                                    echo '<option value="' . $user->ID . '">' . esc_html($user->display_name . ' (' . $user->user_email . ')') . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description">Vérifier si cet utilisateur a un abonnement actif et l'ajouter au programme si nécessaire</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" name="check_single_user" class="button button-secondary" style="margin-right: 10px;">
                        🔍 Vérifier cet utilisateur
                    </button>
                    <button type="submit" name="check_subscriptions" class="button button-primary" 
                            style="background: #82897F; border-color: #82897F;"
                            onclick="return confirm('🔍 Cette action va vérifier TOUS les utilisateurs avec des abonnements actifs et les ajouter au programme de fidélité s\'ils n\'en font pas déjà partie.\n\nCela peut prendre quelques minutes.\n\nContinuer ?');">
                        🔍 Vérifier TOUS les Abonnés
                    </button>
                    <button type="submit" name="cleanup_non_subscribers" class="button button-secondary" 
                            style="background: #dc3545; border-color: #dc3545; color: white; margin-left: 10px;"
                            onclick="return confirm('⚠️ ATTENTION : Cette action va retirer du programme de fidélité TOUS les utilisateurs qui n\'ont PAS d\'abonnement WPS actif.\n\nLes utilisateurs avec uniquement des commandes shop_order seront retirés.\n\nCette action est IRRÉVERSIBLE.\n\nContinuer ?');">
                        🧹 Nettoyer les non-abonnés
                    </button>
                </p>
            </form>
        </div>
        
        <!-- Liste des utilisateurs -->
        <div class="loyalty-admin-table-card">
            <h2>Utilisateurs du programme de fidélité</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Email</th>
                        <th>Date d'inscription</th>
                        <th>Palier</th>
                        <th>Points Totaux</th>
                        <th>Points Disponibles</th>
                        <th>Bons d'achat</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users_data)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <?php if ($search): ?>
                                Aucun utilisateur trouvé pour "<?php echo esc_html($search); ?>"
                            <?php else: ?>
                                Aucun utilisateur n'a encore de points de fidélité
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($users_data as $user_data): 
                            // Vérifier le statut de l'abonnement
                            $subscription_status = 'none';
                            
                            // Vérifier si c'est un abonnement WPS actif
                            $hpos_subscription = $wpdb->get_var($wpdb->prepare(
                                "SELECT status FROM {$wpdb->prefix}wc_orders 
                                 WHERE type = 'wps_subscriptions' 
                                 AND customer_id = %d 
                                 AND status IN ('wc-active', 'wc-pending-cancel', 'wc-wps_renewal', 'active')
                                 ORDER BY date_created_gmt DESC LIMIT 1",
                                $user_data->ID
                            ));
                            
                            if ($hpos_subscription) {
                                $subscription_status = 'active';
                            } else {
                                // Vérifier la dernière commande shop_order
                                $last_order = $wpdb->get_row($wpdb->prepare(
                                    "SELECT id, status FROM {$wpdb->prefix}wc_orders 
                                     WHERE type = 'shop_order' 
                                     AND customer_id = %d 
                                     ORDER BY date_created_gmt DESC LIMIT 1",
                                    $user_data->ID
                                ));
                                
                                if ($last_order) {
                                    if (in_array($last_order->status, array('wc-processing', 'wc-completed'))) {
                                        $subscription_status = 'active'; // wc-processing ou wc-completed = Abonné
                                    } elseif ($last_order->status === 'wc-failed') {
                                        $subscription_status = 'pending'; // wc-failed = Attente
                                    }
                                }
                            }
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($user_data->display_name); ?></strong>
                                <?php if ($subscription_status === 'active'): ?>
                                    <span class="subscription-active-badge" title="Abonnement actif ou commande en cours">
                                        ✓ Abonné
                                    </span>
                                <?php elseif ($subscription_status === 'pending'): ?>
                                    <span class="subscription-pending-badge" title="Paiement échoué">
                                        ⏳ Attente
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($user_data->user_email); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($user_data->user_registered)); ?></td>
                            <td>
                                <?php if ($user_data->tier_name): ?>
                                    <span class="tier-badge tier-<?php echo esc_attr($user_data->tier_slug); ?>">
                                        <?php echo esc_html($user_data->tier_name); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="tier-badge tier-bronze">Aucun</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo number_format($user_data->total_points); ?></td>
                            <td><?php echo number_format($user_data->available_points); ?></td>
                            <td><?php echo $user_data->voucher_count; ?></td>
                            <td>
                                <a href="<?php echo admin_url('user-edit.php?user_id=' . $user_data->ID); ?>" class="button button-small">
                                    Voir profil
                                </a>
                                <a href="#user-details-<?php echo $user_data->ID; ?>" class="button button-small view-details">
                                    Détails
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <style>
    .tier-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .tier-bronze { background: #cd7f32; color: white; }
    .tier-silver { background: #c0c0c0; color: white; }
    .tier-gold { background: #ffd700; color: #333; }
    .tier-platinum { background: #e5e4e2; color: #333; }
    
    .subscription-active-badge {
        display: inline-block;
        margin-left: 8px;
        padding: 3px 8px;
        background: rgba(76, 175, 80, 0.1);
        color: #2e7d32;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .subscription-pending-badge {
        display: inline-block;
        margin-left: 8px;
        padding: 3px 8px;
        background: rgba(255, 152, 0, 0.1);
        color: #e65100;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    </style>
    <?php
}

// Page des paramètres
function newsaiige_loyalty_settings_page() {
    global $wpdb;
    $settings_table = $wpdb->prefix . 'newsaiige_loyalty_settings';
    
    // Traitement de la sauvegarde
    if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'loyalty_settings')) {
        $settings_to_update = array(
            'points_per_euro',
            'points_expiry_days',
            'voucher_expiry_days',
            'min_points_conversion',
            'euro_per_point_conversion',
            'subscription_required',
            'subscription_category_slug',
            'email_notifications_enabled'
        );
        
        foreach ($settings_to_update as $setting_key) {
            if (isset($_POST[$setting_key])) {
                $value = sanitize_text_field($_POST[$setting_key]);
                
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $settings_table WHERE setting_key = %s",
                    $setting_key
                ));
                
                if ($existing) {
                    $wpdb->update(
                        $settings_table,
                        array('setting_value' => $value),
                        array('setting_key' => $setting_key)
                    );
                } else {
                    $wpdb->insert(
                        $settings_table,
                        array(
                            'setting_key' => $setting_key,
                            'setting_value' => $value,
                            'setting_type' => 'string'
                        )
                    );
                }
            }
        }
        
        echo '<div class="notice notice-success"><p>Paramètres sauvegardés !</p></div>';
    }
    
    // Récupérer les paramètres actuels
    function get_loyalty_setting($key, $default = '') {
        global $wpdb;
        $settings_table = $wpdb->prefix . 'newsaiige_loyalty_settings';
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM $settings_table WHERE setting_key = %s",
            $key
        ));
        return $result !== null ? $result : $default;
    }
    ?>
    
    <div class="wrap">
        <h1>Paramètres du Programme de Fidélité</h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('loyalty_settings'); ?>
            
            <div class="loyalty-admin-settings-card">
                <h2>Paramètres des Points</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="points_per_euro">Points par euro d'achat</label></th>
                        <td>
                            <input type="number" id="points_per_euro" name="points_per_euro" 
                                   class="small-text" min="1" step="1" 
                                   value="<?php echo esc_attr(get_loyalty_setting('points_per_euro', '1')); ?>">
                            <p class="description">Nombre de points gagnés pour chaque euro dépensé (partie entière seulement)</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="points_expiry_days">Durée de validité des points (jours)</label></th>
                        <td>
                            <input type="number" id="points_expiry_days" name="points_expiry_days" 
                                   class="small-text" min="30" 
                                   value="<?php echo esc_attr(get_loyalty_setting('points_expiry_days', '365')); ?>">
                            <p class="description">Nombre de jours après lesquels les points expirent depuis la dernière activité</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="min_points_conversion">Points minimum pour conversion</label></th>
                        <td>
                            <input type="number" id="min_points_conversion" name="min_points_conversion" 
                                   class="small-text" min="10" 
                                   value="<?php echo esc_attr(get_loyalty_setting('min_points_conversion', '50')); ?>">
                            <p class="description">Nombre minimum de points requis pour créer un bon d'achat</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="use_conversion_rules">Mode de conversion</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="use_conversion_rules" name="use_conversion_rules" value="1" 
                                       <?php checked(get_loyalty_setting('use_conversion_rules', '1'), '1'); ?>>
                                Utiliser les règles de conversion personnalisées
                            </label>
                            <p class="description">Si décoché, utilise une conversion fixe par point (paramètre ci-dessous)</p>
                        </td>
                    </tr>
                    <tr id="euro_per_point_row" style="<?php echo get_loyalty_setting('use_conversion_rules', '1') == '1' ? 'display:none;' : ''; ?>">
                        <th><label for="euro_per_point_conversion">Valeur de conversion (€ par point)</label></th>
                        <td>
                            <input type="number" id="euro_per_point_conversion" name="euro_per_point_conversion" 
                                   class="small-text" min="0.01" step="0.01" 
                                   value="<?php echo esc_attr(get_loyalty_setting('euro_per_point_conversion', '0.02')); ?>">
                            <p class="description">Valeur en euros d'un point lors de la conversion en bon d'achat (mode fixe uniquement)</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="loyalty-admin-settings-card">
                <h2>Paramètres des Bons d'achat</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="voucher_expiry_days">Durée de validité des bons d'achat (jours)</label></th>
                        <td>
                            <input type="number" id="voucher_expiry_days" name="voucher_expiry_days" 
                                   class="small-text" min="7" 
                                   value="<?php echo esc_attr(get_loyalty_setting('voucher_expiry_days', '90')); ?>">
                            <p class="description">Nombre de jours de validité des bons d'achat après leur création</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="loyalty-admin-settings-card">
                <h2>Conditions d'éligibilité</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="subscription_required">Abonnement requis</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="subscription_required" name="subscription_required" 
                                       value="1" <?php checked(get_loyalty_setting('subscription_required', '1'), '1'); ?>>
                                Exiger un abonnement actif pour gagner des points
                            </label>
                            <p class="description">Si activé, seuls les clients avec un abonnement récent peuvent gagner des points</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="subscription_category_slug">Catégorie d'abonnement</label></th>
                        <td>
                            <input type="text" id="subscription_category_slug" name="subscription_category_slug" 
                                   class="regular-text" 
                                   value="<?php echo esc_attr(get_loyalty_setting('subscription_category_slug', 'soins')); ?>">
                            <p class="description">Slug de la catégorie de produits considérée comme abonnement</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="loyalty-admin-settings-card">
                <h2>Notifications</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="email_notifications_enabled">Emails automatiques</label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="email_notifications_enabled" name="email_notifications_enabled" 
                                       value="1" <?php checked(get_loyalty_setting('email_notifications_enabled', '1'), '1'); ?>>
                                Envoyer des emails lors de l'atteinte de nouveaux paliers et des anniversaires
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
            
            <p class="submit">
                <input type="submit" name="submit" class="button-primary" value="Sauvegarder les paramètres">
            </p>
        </form>
        
        <!-- Actions de maintenance -->
        <div class="loyalty-admin-maintenance-card">
            <h2>Maintenance</h2>
            <p>
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=newsaiige-loyalty-settings&action=cleanup'), 'loyalty_cleanup'); ?>" 
                   class="button" onclick="return confirm('Cette action va nettoyer les données expirées. Continuer ?')">
                    Nettoyer les données expirées
                </a>
            </p>
            <p class="description">
                Supprime les points et bons d'achat expirés pour optimiser la base de données.
            </p>
        </div>
    </div>
    
    <?php
    // Action de nettoyage
    if (isset($_GET['action']) && $_GET['action'] === 'cleanup' && wp_verify_nonce($_GET['_wpnonce'], 'loyalty_cleanup')) {
        global $newsaiige_loyalty;
        if (isset($newsaiige_loyalty)) {
            $newsaiige_loyalty->cleanup_expired_data();
            echo '<div class="notice notice-success"><p>Nettoyage effectué avec succès !</p></div>';
        }
    }
}

/**
 * Page d'administration des règles de conversion
 */
function newsaiige_loyalty_conversions_page() {
    global $wpdb;
    $conversion_rules_table = $wpdb->prefix . 'newsaiige_loyalty_conversion_rules';
    
    // Traitement des actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($_POST['action'] === 'add_rule' && wp_verify_nonce($_POST['_wpnonce'], 'loyalty_conversion_action')) {
            $wpdb->insert(
                $conversion_rules_table,
                array(
                    'points_required' => intval($_POST['points_required']),
                    'voucher_amount' => floatval($_POST['voucher_amount']),
                    'rule_order' => intval($_POST['rule_order']),
                    'is_active' => 1
                )
            );
            echo '<div class="notice notice-success"><p>Règle de conversion ajoutée avec succès !</p></div>';
        }
        
        if ($_POST['action'] === 'update_rule' && wp_verify_nonce($_POST['_wpnonce'], 'loyalty_conversion_action')) {
            $wpdb->update(
                $conversion_rules_table,
                array(
                    'points_required' => intval($_POST['points_required']),
                    'voucher_amount' => floatval($_POST['voucher_amount']),
                    'rule_order' => intval($_POST['rule_order'])
                ),
                array('id' => intval($_POST['rule_id']))
            );
            echo '<div class="notice notice-success"><p>Règle de conversion mise à jour !</p></div>';
        }
    }
    
    // Suppression d'une règle
    if (isset($_GET['delete']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_rule')) {
        $wpdb->delete($conversion_rules_table, array('id' => intval($_GET['delete'])));
        echo '<div class="notice notice-success"><p>Règle supprimée avec succès !</p></div>';
    }
    
    // Toggle active/inactive
    if (isset($_GET['toggle']) && wp_verify_nonce($_GET['_wpnonce'], 'toggle_rule')) {
        $rule = $wpdb->get_row($wpdb->prepare("SELECT * FROM $conversion_rules_table WHERE id = %d", intval($_GET['toggle'])));
        if ($rule) {
            $new_status = $rule->is_active ? 0 : 1;
            $wpdb->update($conversion_rules_table, array('is_active' => $new_status), array('id' => $rule->id));
            echo '<div class="notice notice-success"><p>Statut de la règle mis à jour !</p></div>';
        }
    }
    
    // Récupérer les règles existantes
    $conversion_rules = $wpdb->get_results("SELECT * FROM $conversion_rules_table ORDER BY rule_order ASC, points_required ASC");
    ?>
    
<div class="wrap">
    <h1>Règles de Conversion Points → Bons d'Achat</h1>
    
    <div class="loyalty-admin-container">
        <div class="loyalty-admin-form-card">
            <h2>Ajouter une nouvelle règle</h2>
            <form method="post" class="loyalty-form">
                <?php wp_nonce_field('loyalty_conversion_action'); ?>
                <input type="hidden" name="action" value="add_rule">
                
                <table class="form-table">
                    <tr>
                        <th><label for="points_required">Points requis</label></th>
                        <td>
                            <input type="number" id="points_required" name="points_required" 
                                   class="small-text" min="1" step="1" required>
                            <p class="description">Nombre de points nécessaires pour cette conversion</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="voucher_amount">Montant du bon (€)</label></th>
                        <td>
                            <input type="number" id="voucher_amount" name="voucher_amount" 
                                   class="small-text" min="0.01" step="0.01" required>
                            <p class="description">Valeur en euros du bon d'achat généré</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="rule_order">Ordre d'affichage</label></th>
                        <td>
                            <input type="number" id="rule_order" name="rule_order" 
                                   class="small-text" min="0" step="1" value="0">
                            <p class="description">Ordre d'affichage dans l'interface (0 = premier)</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button-primary" value="Ajouter la règle">
                </p>
            </form>
        </div>
        
        <div class="loyalty-admin-table-card">
            <h2>Règles existantes</h2>
            
            <?php if (!empty($conversion_rules)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 15%;">Points requis</th>
                        <th style="width: 15%;">Montant du bon</th>
                        <th style="width: 15%;">Ratio (€/point)</th>
                        <th style="width: 10%;">Ordre</th>
                        <th style="width: 10%;">Statut</th>
                        <th style="width: 20%;">Date création</th>
                        <th style="width: 15%;">Actions</th>
                    </tr>
                </thead>
                <tbody id="conversion-rules-list">
                    <?php foreach ($conversion_rules as $rule): ?>
                        <?php $editing = isset($_GET['edit']) && $_GET['edit'] == $rule->id; ?>
                        <tr class="<?php echo !$rule->is_active ? 'inactive' : ''; ?>">
                            <?php if ($editing): ?>
                                <form method="post">
                                    <?php wp_nonce_field('loyalty_conversion_action'); ?>
                                    <input type="hidden" name="action" value="update_rule">
                                    <input type="hidden" name="rule_id" value="<?php echo $rule->id; ?>">
                                    
                                    <td><input type="number" name="points_required" class="small-text" value="<?php echo esc_attr($rule->points_required); ?>" required></td>
                                    <td><input type="number" name="voucher_amount" class="small-text" step="0.01" value="<?php echo esc_attr($rule->voucher_amount); ?>" required></td>
                                    <td><?php echo number_format($rule->voucher_amount / $rule->points_required, 4); ?>€</td>
                                    <td><input type="number" name="rule_order" class="small-text" value="<?php echo esc_attr($rule->rule_order); ?>"></td>
                                    <td><?php echo $rule->is_active ? '<span style="color: green;">Actif</span>' : '<span style="color: red;">Inactif</span>'; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($rule->created_at)); ?></td>
                                    <td>
                                        <input type="submit" class="button-primary" value="Sauvegarder">
                                        <a href="<?php echo admin_url('admin.php?page=newsaiige-loyalty-conversions'); ?>" class="button">Annuler</a>
                                    </td>
                                </form>
                            <?php else: ?>
                                <td><strong><?php echo number_format($rule->points_required); ?> points</strong></td>
                                <td><strong><?php echo number_format($rule->voucher_amount, 2); ?>€</strong></td>
                                <td><?php echo number_format($rule->voucher_amount / $rule->points_required, 4); ?>€</td>
                                <td><?php echo $rule->rule_order; ?></td>
                                <td><?php echo $rule->is_active ? '<span style="color: green;">✓ Actif</span>' : '<span style="color: red;">✗ Inactif</span>'; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($rule->created_at)); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=newsaiige-loyalty-conversions&edit=' . $rule->id); ?>" class="button">Modifier</a>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=newsaiige-loyalty-conversions&toggle=' . $rule->id), 'toggle_rule'); ?>" class="button">
                                        <?php echo $rule->is_active ? 'Désactiver' : 'Activer'; ?>
                                    </a>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=newsaiige-loyalty-conversions&delete=' . $rule->id), 'delete_rule'); ?>" 
                                       class="button button-link-delete" onclick="return confirm('Supprimer cette règle de conversion ?')">Supprimer</a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="conversion-rules-help">
                <h3>Informations importantes :</h3>
                <ul>
                    <li><strong>Ordre d'application :</strong> Les utilisateurs verront toutes les règles pour lesquelles ils ont suffisamment de points.</li>
                    <li><strong>Ratio :</strong> Plus le ratio €/point est élevé, plus la conversion est avantageuse pour l'utilisateur.</li>
                    <li><strong>Stratégie recommandée :</strong> Offrir de meilleurs ratios pour encourager l'accumulation de plus de points.</li>
                    <li><strong>Règles inactives :</strong> Ne sont pas proposées aux utilisateurs mais restent dans l'historique.</li>
                </ul>
            </div>
            
            <?php else: ?>
            <p>Aucune règle de conversion définie. Ajoutez-en une pour permettre aux utilisateurs de convertir leurs points.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.conversion-rules-help {
    margin-top: 20px;
    padding: 15px;
    background: #f0f8ff;
    border-left: 4px solid #82897F;
}

.conversion-rules-help h3 {
    margin-top: 0;
    color: #82897F;
}

.conversion-rules-help ul {
    margin-bottom: 0;
}

.conversion-rules-help li {
    margin-bottom: 8px;
}

tr.inactive {
    opacity: 0.6;
    background-color: #f9f9f9;
}

/* Styles généraux pour les cartes d'administration de fidélité */
.loyalty-admin-form-card,
.loyalty-admin-table-card,
.loyalty-admin-settings-card,
.loyalty-admin-search-card,
.loyalty-admin-maintenance-card {
    background: #ffffff;
    border: 1px solid #e1e1e1;
    border-radius: 8px;
    margin: 20px 0;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.loyalty-admin-form-card h2,
.loyalty-admin-table-card h2,
.loyalty-admin-settings-card h2,
.loyalty-admin-search-card h2,
.loyalty-admin-maintenance-card h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
    font-size: 18px;
    border-bottom: 2px solid #82897F;
    padding-bottom: 10px;
}

/* Styles spécifiques pour les cartes de formulaire */
.loyalty-admin-form-card .form-table {
    margin-bottom: 20px;
}

.loyalty-admin-form-card .form-table th {
    width: 200px;
    padding: 15px 10px 15px 0;
    color: #333;
    font-weight: 600;
}

.loyalty-admin-form-card .form-table td {
    padding: 15px 10px;
}

.loyalty-admin-form-card .button-primary {
    background-color: #82897F;
    border-color: #6c7367;
    padding: 8px 16px;
    font-weight: 600;
}

.loyalty-admin-form-card .button-primary:hover {
    background-color: #6c7367;
    border-color: #5a5e56;
}

/* Styles pour les cartes de tableau */
.loyalty-admin-table-card .wp-list-table {
    margin-top: 15px;
    border: 1px solid #e1e1e1;
    border-radius: 4px;
}

.loyalty-admin-table-card .wp-list-table th {
    background: #f8f9fa;
    color: #333;
    font-weight: 600;
    border-bottom: 2px solid #e1e1e1;
}

.loyalty-admin-table-card .wp-list-table tr:hover {
    background-color: #f8f9fa;
}

/* Styles pour les cartes de paramètres */
.loyalty-admin-settings-card .form-table th {
    width: 250px;
    color: #333;
    font-weight: 600;
}

.loyalty-admin-settings-card .description {
    color: #666;
    font-style: italic;
}

/* Styles pour la carte de recherche */
.loyalty-admin-search-card {
    padding: 15px;
}

.loyalty-admin-search-card .search-box {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.loyalty-admin-search-card input[type="search"] {
    width: 300px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.loyalty-admin-search-card .button {
    background-color: #82897F;
    border-color: #6c7367;
    color: white;
    padding: 8px 16px;
}

/* Styles pour la carte de maintenance */
.loyalty-admin-maintenance-card {
    background: #fffbf0;
    border-left: 4px solid #f39c12;
}

.loyalty-admin-maintenance-card .button {
    background-color: #f39c12;
    border-color: #e67e22;
    color: white;
}

.loyalty-admin-maintenance-card .button:hover {
    background-color: #e67e22;
    border-color: #d35400;
}

/* Container général */
.loyalty-admin-container {
    max-width: 1200px;
    margin: 0 auto;
}
</style>

<?php
}

/**
 * Page de recalcul des paliers utilisateurs
 */
function newsaiige_loyalty_recalculate_page() {
    global $wpdb, $newsaiige_loyalty;
    
    // Traiter le formulaire de recalcul
    if (isset($_POST['recalculate_tiers']) && check_admin_referer('loyalty_recalculate_tiers')) {
        $users_updated = 0;
        $users_checked = 0;
        
        // Récupérer tous les utilisateurs avec des points
        $users_with_points = $wpdb->get_results("
            SELECT user_id, SUM(points_available) as total_points
            FROM {$wpdb->prefix}newsaiige_loyalty_points
            WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())
            GROUP BY user_id
            HAVING total_points > 0
        ");
        
        foreach ($users_with_points as $user_data) {
            $users_checked++;
            
            // Récupérer le palier actuel
            $current_tier = $wpdb->get_var($wpdb->prepare("
                SELECT tier_id
                FROM {$wpdb->prefix}newsaiige_loyalty_user_tiers
                WHERE user_id = %d AND is_current = 1
            ", $user_data->user_id));
            
            // Vérifier et mettre à jour le palier
            $newsaiige_loyalty->check_tier_upgrade($user_data->user_id);
            
            // Vérifier si le palier a changé
            $new_tier = $wpdb->get_var($wpdb->prepare("
                SELECT tier_id
                FROM {$wpdb->prefix}newsaiige_loyalty_user_tiers
                WHERE user_id = %d AND is_current = 1
            ", $user_data->user_id));
            
            if ($current_tier != $new_tier) {
                $users_updated++;
            }
        }
        
        // Assigner le palier Bronze aux utilisateurs sans palier
        $users_without_tier = $wpdb->get_results("
            SELECT DISTINCT u.ID
            FROM {$wpdb->prefix}users u
            LEFT JOIN {$wpdb->prefix}newsaiige_loyalty_user_tiers ut ON u.ID = ut.user_id
            WHERE ut.user_id IS NULL
            AND u.ID IN (
                SELECT user_id FROM {$wpdb->prefix}newsaiige_loyalty_points
            )
        ");
        
        $bronze_tier = $wpdb->get_var("
            SELECT id FROM {$wpdb->prefix}newsaiige_loyalty_tiers
            WHERE tier_slug = 'bronze' AND is_active = 1
            ORDER BY points_required ASC
            LIMIT 1
        ");
        
        foreach ($users_without_tier as $user) {
            if ($bronze_tier) {
                $wpdb->insert(
                    $wpdb->prefix . 'newsaiige_loyalty_user_tiers',
                    array(
                        'user_id' => $user->ID,
                        'tier_id' => $bronze_tier,
                        'assigned_at' => current_time('mysql')
                    ),
                    array('%d', '%d', '%s')
                );
                $users_updated++;
            }
        }
        
        echo '<div class="notice notice-success"><p>';
        echo sprintf(
            'Recalcul terminé ! %d utilisateurs vérifiés, %d paliers mis à jour.',
            $users_checked,
            $users_updated
        );
        echo '</p></div>';
    }
    
    // Statistiques
    $total_users = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}newsaiige_loyalty_points");
    $users_with_tier = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}newsaiige_loyalty_user_tiers");
    $users_without_tier = $total_users - $users_with_tier;
    
    // Détecter les incohérences
    $inconsistencies = $wpdb->get_results("
        SELECT 
            u.ID as user_id,
            u.display_name,
            COALESCE(SUM(p.points_available), 0) as total_points,
            t.tier_name as current_tier,
            t.points_required as tier_min_points,
            (SELECT tier_name FROM {$wpdb->prefix}newsaiige_loyalty_tiers 
             WHERE points_required <= COALESCE(SUM(p.points_available), 0) AND is_active = 1
             ORDER BY points_required DESC LIMIT 1) as correct_tier
        FROM {$wpdb->prefix}users u
        LEFT JOIN {$wpdb->prefix}newsaiige_loyalty_points p ON u.ID = p.user_id 
            AND p.is_active = 1 AND (p.expires_at IS NULL OR p.expires_at > NOW())
        LEFT JOIN {$wpdb->prefix}newsaiige_loyalty_user_tiers ut ON u.ID = ut.user_id AND ut.is_current = 1
        LEFT JOIN {$wpdb->prefix}newsaiige_loyalty_tiers t ON ut.tier_id = t.id
        WHERE u.ID IN (SELECT DISTINCT user_id FROM {$wpdb->prefix}newsaiige_loyalty_points)
        GROUP BY u.ID
        HAVING (
            total_points < tier_min_points OR
            current_tier != correct_tier OR
            current_tier IS NULL
        )
        LIMIT 20
    ");
    
    ?>
    <div class="wrap">
        <h1>🔄 Recalcul des paliers de fidélité</h1>
        
        <?php if (count($inconsistencies) > 0): ?>
        <div class="notice notice-warning">
            <p><strong>⚠️ Attention :</strong> <?php echo count($inconsistencies); ?> utilisateur(s) ont un palier incorrect !</p>
        </div>
        <?php endif; ?>
        
        <div class="card" style="max-width: 800px;">
            <h2>📊 Statistiques</h2>
            <table class="widefat" style="margin-top: 15px;">
                <tbody>
                    <tr>
                        <td><strong>Total utilisateurs avec points :</strong></td>
                        <td><?php echo number_format($total_users); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Utilisateurs avec palier assigné :</strong></td>
                        <td><?php echo number_format($users_with_tier); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Utilisateurs sans palier :</strong></td>
                        <td style="<?php echo $users_without_tier > 0 ? 'color: red; font-weight: bold;' : ''; ?>">
                            <?php echo number_format($users_without_tier); ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Incohérences détectées :</strong></td>
                        <td style="<?php echo count($inconsistencies) > 0 ? 'color: red; font-weight: bold;' : ''; ?>">
                            <?php echo number_format(count($inconsistencies)); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php if (count($inconsistencies) > 0): ?>
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>⚠️ Utilisateurs avec incohérences (premiers 20)</h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Points disponibles</th>
                        <th>Palier actuel</th>
                        <th>Palier correct</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inconsistencies as $user): ?>
                    <tr>
                        <td><?php echo esc_html($user->display_name); ?></td>
                        <td><?php echo number_format($user->total_points); ?></td>
                        <td style="color: red;">
                            <?php echo $user->current_tier ? esc_html($user->current_tier) : '<em>Aucun</em>'; ?>
                        </td>
                        <td style="color: green; font-weight: bold;">
                            <?php echo esc_html($user->correct_tier); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="card" style="max-width: 800px; margin-top: 20px; background: #fffbf0; border-left: 4px solid #f39c12;">
            <h2>🔧 Action de recalcul</h2>
            <p>Cette action va :</p>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <li>Recalculer le palier de chaque utilisateur selon ses points disponibles</li>
                <li>Corriger les paliers incorrects</li>
                <li>Assigner le palier Bronze aux utilisateurs sans palier</li>
            </ul>
            
            <form method="post" style="margin-top: 20px;">
                <?php wp_nonce_field('loyalty_recalculate_tiers'); ?>
                <button type="submit" name="recalculate_tiers" class="button button-primary button-large" 
                        onclick="return confirm('Êtes-vous sûr de vouloir recalculer tous les paliers ?');">
                    🔄 Lancer le recalcul
                </button>
            </form>
        </div>
    </div>
    <?php
}

/**
 * Afficher une notification admin si des incohérences sont détectées
 */
add_action('admin_notices', function() {
    global $wpdb;
    
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'newsaiige-loyalty') === false) {
        return;
    }
    
    // Compter les incohérences
    $inconsistencies_count = $wpdb->get_var("
        SELECT COUNT(*) FROM (
            SELECT u.ID
            FROM {$wpdb->prefix}users u
            LEFT JOIN {$wpdb->prefix}newsaiige_loyalty_points p ON u.ID = p.user_id 
                AND p.is_active = 1 AND (p.expires_at IS NULL OR p.expires_at > NOW())
            LEFT JOIN {$wpdb->prefix}newsaiige_loyalty_user_tiers ut ON u.ID = ut.user_id AND ut.is_current = 1
            LEFT JOIN {$wpdb->prefix}newsaiige_loyalty_tiers t ON ut.tier_id = t.id
            WHERE u.ID IN (SELECT DISTINCT user_id FROM {$wpdb->prefix}newsaiige_loyalty_points)
            GROUP BY u.ID
            HAVING (
                COALESCE(SUM(p.points_available), 0) < COALESCE(MAX(t.points_required), 0) OR
                MAX(t.tier_name) != (SELECT tier_name FROM {$wpdb->prefix}newsaiige_loyalty_tiers 
                    WHERE points_required <= COALESCE(SUM(p.points_available), 0) AND is_active = 1
                    ORDER BY points_required DESC LIMIT 1) OR
                MAX(t.tier_name) IS NULL
            )
        ) as subquery
    ");
    
    if ($inconsistencies_count > 0) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong>⚠️ Système de fidélité :</strong> 
                <?php echo number_format($inconsistencies_count); ?> utilisateur(s) ont un palier incorrect.
                <a href="<?php echo admin_url('admin.php?page=newsaiige-loyalty-recalculate'); ?>" class="button button-small">
                    Recalculer les paliers
                </a>
            </p>
        </div>
        <?php
    }
});

/**
 * Page d'importation des points historiques
 */
function newsaiige_loyalty_import_history_page() {
    global $wpdb, $newsaiige_loyalty;
    
    // Traiter l'importation
    if (isset($_POST['import_historical_points']) && check_admin_referer('loyalty_import_history')) {
        $orders_processed = 0;
        $points_added = 0;
        $users_updated = 0;
        $errors = 0;
        
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        // Récupérer toutes les commandes complétées
        $args = array(
            'status' => 'completed',
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'ASC'
        );
        
        if ($date_from) {
            $args['date_created'] = '>=' . $date_from;
        }
        if ($date_to && $date_from) {
            $args['date_created'] = $date_from . '...' . $date_to;
        }
        
        $orders = wc_get_orders($args);
        
        foreach ($orders as $order) {
            $order_id = $order->get_id();
            
            // Vérifier si déjà traité
            if (get_post_meta($order_id, '_newsaiige_loyalty_processed', true)) {
                continue;
            }
            
            $user_id = $order->get_user_id();
            if (!$user_id) {
                continue;
            }
            
            // Calculer les points
            $order_total = $order->get_total();
            $points_per_euro = floatval($newsaiige_loyalty->get_setting('points_per_euro', 1));
            $points_earned = floor($order_total * $points_per_euro);
            
            if ($points_earned > 0) {
                $description = sprintf('Points historiques pour la commande #%s', $order_id);
                
                if ($newsaiige_loyalty->add_points($user_id, $points_earned, $order_id, 'historical_import', $description)) {
                    // Marquer comme traité
                    update_post_meta($order_id, '_newsaiige_loyalty_processed', time());
                    
                    $orders_processed++;
                    $points_added += $points_earned;
                    
                    // Vérifier et mettre à jour le palier
                    $newsaiige_loyalty->check_tier_upgrade($user_id);
                } else {
                    $errors++;
                }
            }
        }
        
        // Compter les utilisateurs uniques mis à jour
        $users_updated = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT user_id) 
            FROM {$wpdb->prefix}newsaiige_loyalty_points 
            WHERE action_type = %s
        ", 'historical_import'));
        
        echo '<div class="notice notice-success is-dismissible"><p>';
        echo sprintf(
            '<strong>✅ Importation terminée !</strong><br>' .
            '• %d commandes traitées<br>' .
            '• %s points ajoutés<br>' .
            '• %d utilisateurs mis à jour<br>' .
            '• %d erreurs',
            $orders_processed,
            number_format($points_added),
            $users_updated,
            $errors
        );
        echo '</p></div>';
    }
    
    // Statistiques des commandes historiques
    $total_orders = wc_get_orders(array(
        'status' => 'completed',
        'limit' => -1,
        'return' => 'ids'
    ));
    
    $processed_orders = $wpdb->get_var("
        SELECT COUNT(DISTINCT order_id) 
        FROM {$wpdb->prefix}newsaiige_loyalty_points 
        WHERE order_id IS NOT NULL
    ");
    
    $unprocessed_orders = count($total_orders) - $processed_orders;
    
    // Calculer le potentiel de points
    $args_unprocessed = array(
        'status' => 'completed',
        'limit' => -1,
        'meta_query' => array(
            array(
                'key' => '_newsaiige_loyalty_processed',
                'compare' => 'NOT EXISTS'
            )
        )
    );
    
    $unprocessed_order_objects = wc_get_orders($args_unprocessed);
    $potential_points = 0;
    $potential_users = array();
    
    foreach ($unprocessed_order_objects as $order) {
        $order_total = $order->get_total();
        $points_per_euro = floatval($newsaiige_loyalty->get_setting('points_per_euro', 1));
        $potential_points += floor($order_total * $points_per_euro);
        
        $user_id = $order->get_user_id();
        if ($user_id && !in_array($user_id, $potential_users)) {
            $potential_users[] = $user_id;
        }
    }
    
    ?>
    <div class="wrap">
        <h1>📥 Importation des Points Historiques</h1>
        
        <?php if ($unprocessed_orders > 0): ?>
        <div class="notice notice-warning">
            <p>
                <strong>⚠️ Attention :</strong> 
                <?php echo number_format($unprocessed_orders); ?> commande(s) complétée(s) n'ont pas encore généré de points !
            </p>
        </div>
        <?php endif; ?>
        
        <div class="card" style="max-width: 800px;">
            <h2>📊 Statistiques des Commandes</h2>
            <table class="widefat" style="margin-top: 15px;">
                <tbody>
                    <tr>
                        <td><strong>Total commandes complétées :</strong></td>
                        <td><?php echo number_format(count($total_orders)); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Commandes avec points attribués :</strong></td>
                        <td><?php echo number_format($processed_orders); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Commandes sans points :</strong></td>
                        <td style="<?php echo $unprocessed_orders > 0 ? 'color: red; font-weight: bold;' : ''; ?>">
                            <?php echo number_format($unprocessed_orders); ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Points potentiels à attribuer :</strong></td>
                        <td style="color: #82897F; font-weight: bold;">
                            <?php echo number_format($potential_points); ?> points
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Utilisateurs concernés :</strong></td>
                        <td><?php echo number_format(count($potential_users)); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php if ($unprocessed_orders > 0): ?>
        <div class="card" style="max-width: 800px; margin-top: 20px; background: #e8f5e9; border-left: 4px solid #4caf50;">
            <h2>🚀 Lancer l'Importation</h2>
            <p>
                Cette action va attribuer rétroactivement des points de fidélité pour toutes les commandes 
                complétées qui n'ont pas encore été traitées par le système de fidélité.
            </p>
            
            <form method="post" style="margin-top: 20px;">
                <?php wp_nonce_field('loyalty_import_history'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="date_from">Date de début (optionnel)</label>
                        </th>
                        <td>
                            <input type="date" id="date_from" name="date_from" class="regular-text">
                            <p class="description">Importer uniquement les commandes à partir de cette date</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="date_to">Date de fin (optionnel)</label>
                        </th>
                        <td>
                            <input type="date" id="date_to" name="date_to" class="regular-text">
                            <p class="description">Importer uniquement les commandes jusqu'à cette date</p>
                        </td>
                    </tr>
                </table>
                
                <p style="margin-top: 20px;">
                    <button type="submit" name="import_historical_points" class="button button-primary button-large" 
                            onclick="return confirm('⚠️ Cette action va traiter <?php echo number_format($unprocessed_orders); ?> commandes et attribuer environ <?php echo number_format($potential_points); ?> points.\n\nCette opération peut prendre plusieurs minutes.\n\nContinuer ?');">
                        📥 Importer les Points Historiques
                    </button>
                </p>
                
                <p class="description" style="margin-top: 10px;">
                    <strong>Note :</strong> Cette opération ne peut être annulée. Les points seront attribués 
                    et les paliers des utilisateurs seront automatiquement mis à jour.
                </p>
            </form>
        </div>
        <?php else: ?>
        <div class="card" style="max-width: 800px; margin-top: 20px; background: #f0f8ff; border-left: 4px solid #2196f3;">
            <h2>✅ Toutes les commandes sont à jour !</h2>
            <p>
                Toutes les commandes complétées ont déjà été traitées et les points correspondants 
                ont été attribués aux clients.
            </p>
            <p style="margin-top: 15px;">
                <strong>💡 Astuce :</strong> Les nouvelles commandes seront automatiquement traitées 
                lorsqu'elles passent au statut "Complétée".
            </p>
        </div>
        <?php endif; ?>
        
        <div class="card" style="max-width: 800px; margin-top: 20px; background: #fffbf0; border-left: 4px solid #ff9800;">
            <h2>ℹ️ Informations Importantes</h2>
            <ul style="list-style-type: disc; margin-left: 20px; line-height: 1.8;">
                <li><strong>Attribution automatique :</strong> Depuis l'installation du plugin, les points sont automatiquement attribués quand une commande passe à "Complétée"</li>
                <li><strong>Commandes historiques :</strong> Les commandes passées AVANT l'installation du plugin doivent être importées manuellement via cette page</li>
                <li><strong>Calcul des points :</strong> <?php echo $newsaiige_loyalty->get_setting('points_per_euro', 1); ?> point(s) par euro dépensé (configurable dans Paramètres)</li>
                <li><strong>Durée de validité :</strong> <?php echo $newsaiige_loyalty->get_setting('points_expiry_days', 365); ?> jours (configurable dans Paramètres)</li>
                <li><strong>Paliers :</strong> Les paliers sont automatiquement mis à jour après l'attribution des points</li>
                <li><strong>Doublons évités :</strong> Chaque commande ne peut être traitée qu'une seule fois</li>
            </ul>
        </div>
    </div>
    
    <style>
    .card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .card h2 {
        margin-top: 0;
        color: #333;
        border-bottom: 2px solid #82897F;
        padding-bottom: 10px;
    }
    
    .card ul {
        margin-bottom: 0;
    }
    
    .card ul li {
        margin-bottom: 10px;
    }
    </style>
    <?php
}

/**
 * Page de réactivation des points inactifs
 */
function newsaiige_loyalty_reactivate_points_page() {
    global $wpdb;
    
    $points_table = $wpdb->prefix . 'newsaiige_loyalty_points';
    
    // Traiter la réactivation
    if (isset($_POST['reactivate_points']) && check_admin_referer('loyalty_reactivate_points')) {
        $reactivate_type = sanitize_text_field($_POST['reactivate_type']);
        
        if ($reactivate_type === 'inactive') {
            // Réactiver tous les points inactifs
            $updated = $wpdb->query("
                UPDATE {$points_table}
                SET is_active = 1
                WHERE is_active = 0
                AND (expires_at IS NULL OR expires_at > NOW())
            ");
            
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo sprintf('<strong>✅ Succès !</strong> %d enregistrement(s) de points ont été réactivés.', $updated);
            echo '</p></div>';
            
        } elseif ($reactivate_type === 'extend_expiry') {
            // Étendre l'expiration de 6 mois
            $updated = $wpdb->query("
                UPDATE {$points_table}
                SET expires_at = DATE_ADD(expires_at, INTERVAL 6 MONTH)
                WHERE expires_at IS NOT NULL
                AND expires_at > NOW()
            ");
            
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo sprintf('<strong>✅ Succès !</strong> La date d\'expiration de %d enregistrement(s) a été prolongée de 6 mois.', $updated);
            echo '</p></div>';
            
        } elseif ($reactivate_type === 'reactivate_expired') {
            // Réactiver les points expirés et prolonger de 6 mois
            $updated = $wpdb->query("
                UPDATE {$points_table}
                SET is_active = 1,
                    expires_at = DATE_ADD(NOW(), INTERVAL 6 MONTH)
                WHERE expires_at IS NOT NULL
                AND expires_at <= NOW()
            ");
            
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo sprintf('<strong>✅ Succès !</strong> %d enregistrement(s) de points expirés ont été réactivés avec une nouvelle expiration.', $updated);
            echo '</p></div>';
        }
        
        // Recalculer les paliers après réactivation
        global $newsaiige_loyalty;
        $users_with_points = $wpdb->get_results("
            SELECT DISTINCT user_id
            FROM {$points_table}
            WHERE is_active = 1
        ");
        
        foreach ($users_with_points as $user_data) {
            $newsaiige_loyalty->check_tier_upgrade($user_data->user_id);
        }
        
        echo '<div class="notice notice-info is-dismissible"><p>';
        echo sprintf('<strong>ℹ️ Info :</strong> Les paliers de %d utilisateur(s) ont été recalculés.', count($users_with_points));
        echo '</p></div>';
    }
    
    // Statistiques des points
    $stats = $wpdb->get_row("
        SELECT 
            COUNT(*) as total_records,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_records,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_records,
            SUM(CASE WHEN expires_at IS NOT NULL AND expires_at <= NOW() THEN 1 ELSE 0 END) as expired_records,
            SUM(CASE WHEN is_active = 1 THEN points_available ELSE 0 END) as total_active_points,
            SUM(CASE WHEN is_active = 0 THEN points_available ELSE 0 END) as total_inactive_points,
            SUM(CASE WHEN expires_at IS NOT NULL AND expires_at <= NOW() THEN points_available ELSE 0 END) as total_expired_points
        FROM {$points_table}
    ");
    
    // Utilisateurs affectés
    $affected_users = $wpdb->get_results("
        SELECT 
            u.ID,
            u.display_name,
            u.user_email,
            SUM(CASE WHEN p.is_active = 1 AND (p.expires_at IS NULL OR p.expires_at > NOW()) THEN p.points_available ELSE 0 END) as active_points,
            SUM(CASE WHEN p.is_active = 0 AND (p.expires_at IS NULL OR p.expires_at > NOW()) THEN p.points_available ELSE 0 END) as inactive_points,
            SUM(CASE WHEN p.expires_at IS NOT NULL AND p.expires_at <= NOW() THEN p.points_available ELSE 0 END) as expired_points,
            SUM(p.points_available) as total_points
        FROM {$wpdb->users} u
        INNER JOIN {$points_table} p ON u.ID = p.user_id
        GROUP BY u.ID
        HAVING SUM(CASE WHEN p.is_active = 0 AND (p.expires_at IS NULL OR p.expires_at > NOW()) THEN p.points_available ELSE 0 END) > 0 
            OR SUM(CASE WHEN p.expires_at IS NOT NULL AND p.expires_at <= NOW() THEN p.points_available ELSE 0 END) > 0
        ORDER BY (SUM(CASE WHEN p.is_active = 0 AND (p.expires_at IS NULL OR p.expires_at > NOW()) THEN p.points_available ELSE 0 END) + SUM(CASE WHEN p.expires_at IS NOT NULL AND p.expires_at <= NOW() THEN p.points_available ELSE 0 END)) DESC
        LIMIT 20
    ");
    
    ?>
    <div class="wrap">
        <h1>🔓 Réactiver les Points Inactifs</h1>
        
        <?php if ($stats->inactive_records > 0 || $stats->expired_records > 0): ?>
        <div class="notice notice-warning">
            <p>
                <strong>⚠️ Attention :</strong> 
                <?php 
                $messages = array();
                if ($stats->inactive_records > 0) {
                    $messages[] = sprintf('%d enregistrement(s) inactif(s) avec %s points', 
                        $stats->inactive_records, 
                        number_format($stats->total_inactive_points)
                    );
                }
                if ($stats->expired_records > 0) {
                    $messages[] = sprintf('%d enregistrement(s) expiré(s) avec %s points', 
                        $stats->expired_records, 
                        number_format($stats->total_expired_points)
                    );
                }
                echo implode(' et ', $messages);
                ?>
            </p>
        </div>
        <?php endif; ?>
        
        <div class="card" style="max-width: 800px; background: white; padding: 20px; border-radius: 8px; margin-top: 20px;">
            <h2>📊 Statistiques des Points</h2>
            <table class="widefat" style="margin-top: 15px;">
                <tbody>
                    <tr>
                        <td><strong>Total d'enregistrements :</strong></td>
                        <td><?php echo number_format($stats->total_records); ?></td>
                    </tr>
                    <tr style="background: #e8f5e9;">
                        <td><strong>Enregistrements actifs :</strong></td>
                        <td><?php echo number_format($stats->active_records); ?> (<?php echo number_format($stats->total_active_points); ?> points)</td>
                    </tr>
                    <tr style="background: #fff3e0;">
                        <td><strong>Enregistrements inactifs :</strong></td>
                        <td style="<?php echo $stats->inactive_records > 0 ? 'color: #f57c00; font-weight: bold;' : ''; ?>">
                            <?php echo number_format($stats->inactive_records); ?> (<?php echo number_format($stats->total_inactive_points); ?> points)
                        </td>
                    </tr>
                    <tr style="background: #ffebee;">
                        <td><strong>Enregistrements expirés :</strong></td>
                        <td style="<?php echo $stats->expired_records > 0 ? 'color: #d32f2f; font-weight: bold;' : ''; ?>">
                            <?php echo number_format($stats->expired_records); ?> (<?php echo number_format($stats->total_expired_points); ?> points)
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php if (!empty($affected_users)): ?>
        <div class="card" style="max-width: 1200px; background: white; padding: 20px; border-radius: 8px; margin-top: 20px;">
            <h2>👥 Utilisateurs Affectés (Top 20)</h2>
            <table class="widefat" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Email</th>
                        <th>Points Actifs</th>
                        <th>Points Inactifs</th>
                        <th>Points Expirés</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($affected_users as $user): ?>
                    <tr>
                        <td><?php echo esc_html($user->display_name); ?></td>
                        <td><?php echo esc_html($user->user_email); ?></td>
                        <td style="color: #4caf50;"><?php echo number_format($user->active_points); ?></td>
                        <td style="<?php echo $user->inactive_points > 0 ? 'color: #f57c00; font-weight: bold;' : ''; ?>">
                            <?php echo number_format($user->inactive_points); ?>
                        </td>
                        <td style="<?php echo $user->expired_points > 0 ? 'color: #d32f2f; font-weight: bold;' : ''; ?>">
                            <?php echo number_format($user->expired_points); ?>
                        </td>
                        <td><strong><?php echo number_format($user->total_points); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <?php if ($stats->inactive_records > 0 || $stats->expired_records > 0): ?>
        <div class="card" style="max-width: 800px; background: #e8f5e9; border-left: 4px solid #4caf50; padding: 20px; margin-top: 20px;">
            <h2>🚀 Actions de Réactivation</h2>
            
            <form method="post" style="margin-top: 20px;">
                <?php wp_nonce_field('loyalty_reactivate_points'); ?>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 10px;">
                        <input type="radio" name="reactivate_type" value="inactive" required>
                        <strong>Réactiver les points inactifs uniquement</strong>
                        <p style="margin: 5px 0 0 25px; color: #666; font-size: 0.9em;">
                            Réactive <?php echo number_format($stats->inactive_records); ?> enregistrement(s) (<?php echo number_format($stats->total_inactive_points); ?> points) 
                            qui sont marqués comme inactifs mais pas encore expirés.
                        </p>
                    </label>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 10px;">
                        <input type="radio" name="reactivate_type" value="extend_expiry" required>
                        <strong>Prolonger la date d'expiration de 6 mois</strong>
                        <p style="margin: 5px 0 0 25px; color: #666; font-size: 0.9em;">
                            Étend la date d'expiration de tous les points actifs non expirés de 6 mois supplémentaires.
                        </p>
                    </label>
                </div>
                
                <?php if ($stats->expired_records > 0): ?>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 10px;">
                        <input type="radio" name="reactivate_type" value="reactivate_expired" required>
                        <strong>Réactiver les points expirés</strong>
                        <p style="margin: 5px 0 0 25px; color: #666; font-size: 0.9em;">
                            Réactive <?php echo number_format($stats->expired_records); ?> enregistrement(s) (<?php echo number_format($stats->total_expired_points); ?> points) 
                            qui ont déjà expiré et leur donne une nouvelle expiration de 6 mois.
                        </p>
                    </label>
                </div>
                <?php endif; ?>
                
                <p style="margin-top: 20px;">
                    <button type="submit" name="reactivate_points" class="button button-primary button-large" 
                            onclick="return confirm('⚠️ Cette action va modifier les enregistrements de points.\n\nLes paliers des utilisateurs seront automatiquement recalculés.\n\nContinuer ?');">
                        🔓 Réactiver les Points
                    </button>
                </p>
                
                <p class="description" style="margin-top: 10px;">
                    <strong>Note :</strong> Cette opération ne peut être annulée. Les points seront réactivés 
                    et les paliers des utilisateurs seront automatiquement mis à jour.
                </p>
            </form>
        </div>
        <?php else: ?>
        <div class="card" style="max-width: 800px; background: #f0f8ff; border-left: 4px solid #2196f3; padding: 20px; margin-top: 20px;">
            <h2>✅ Tout est en ordre !</h2>
            <p>
                Tous les points actifs sont correctement configurés. Aucune action de réactivation n'est nécessaire.
            </p>
        </div>
        <?php endif; ?>
        
        <div class="card" style="max-width: 800px; background: #fffbf0; border-left: 4px solid #ff9800; padding: 20px; margin-top: 20px;">
            <h2>ℹ️ Informations Importantes</h2>
            <ul style="list-style-type: disc; margin-left: 20px; line-height: 1.8;">
                <li><strong>Points inactifs :</strong> Points marqués comme inactifs (is_active = 0) mais pas encore expirés</li>
                <li><strong>Points expirés :</strong> Points dont la date d'expiration est dépassée</li>
                <li><strong>Calcul des points disponibles :</strong> Seuls les points actifs ET non expirés sont comptabilisés</li>
                <li><strong>Recalcul automatique :</strong> Les paliers sont recalculés après toute réactivation</li>
                <li><strong>Impact :</strong> Les utilisateurs verront leurs points disponibles augmenter immédiatement</li>
            </ul>
        </div>
    </div>
    
    <style>
    .card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .card h2 {
        margin-top: 0;
        color: #333;
        border-bottom: 2px solid #82897F;
        padding-bottom: 10px;
    }
    </style>
    <?php
}

/**
 * Page d'administration pour retraiter les points
 * Affiche les commandes qui n'ont pas eu de points attribués
 */
function newsaiige_loyalty_reprocess_points_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Accès refusé');
    }
    
    global $wpdb;
    $points_system = NewsaiigeLoyaltySystemSafe::get_instance();
    
    // Traiter l'action de retraitement
    $reprocess_count = 0;
    $error_count = 0;
    
    if (isset($_POST['newsaiige_reprocess_action']) && $_POST['newsaiige_reprocess_action'] === 'reprocess') {
        check_admin_referer('newsaiige_reprocess_nonce');
        
        if (isset($_POST['order_ids']) && is_array($_POST['order_ids'])) {
            foreach ($_POST['order_ids'] as $order_id) {
                $order_id = intval($order_id);
                try {
                    $points_system->process_order_points($order_id);
                    $reprocess_count++;
                } catch (Exception $e) {
                    error_log("Erreur retraitement points commande #$order_id: " . $e->getMessage());
                    $error_count++;
                }
            }
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo sprintf(__('%d commande(s) retraitée(s), %d erreur(s)', 'newsaiige-loyalty'), $reprocess_count, $error_count);
            echo '</p></div>';
        }
    }
    
    // Filtres de recherche
    $search_user_id = isset($_GET['search_user_id']) ? intval($_GET['search_user_id']) : 0;
    $search_months = isset($_GET['search_months']) ? intval($_GET['search_months']) : 12;
    
    // Récupérer les commandes sans points attribués
    // Stratégie: chercher les commandes qui n'ont PAS de points dans wp_newsaiige_loyalty_points
    $where_clauses = array(
        "o.customer_id IS NOT NULL",
        "o.customer_id > 0",
        "o.type IN ('shop_order', 'wps_subscription', 'wps_subscriptions')",
        "o.status IN ('wc-completed', 'wc-processing', 'wc-active')",
        "o.total_amount > 0"
    );
    
    // Filtre par utilisateur
    if ($search_user_id > 0) {
        $where_clauses[] = $wpdb->prepare("o.customer_id = %d", $search_user_id);
    }
    
    // Filtre par date
    if ($search_months > 0) {
        $where_clauses[] = $wpdb->prepare("o.date_created_gmt >= DATE_SUB(NOW(), INTERVAL %d MONTH)", $search_months);
    }
    
    $where_sql = implode(" AND ", $where_clauses);
    
    $missing_points_orders = $wpdb->get_results("
        SELECT DISTINCT
            o.id as order_id,
            o.customer_id,
            o.total_amount as total,
            o.status,
            o.type,
            o.date_created_gmt as date_created,
            u.display_name,
            u.user_email as email
        FROM {$wpdb->prefix}wc_orders o
        LEFT JOIN {$wpdb->users} u ON o.customer_id = u.ID
        WHERE {$where_sql}
        AND NOT EXISTS (
            SELECT 1 FROM {$wpdb->prefix}newsaiige_loyalty_points p 
            WHERE p.order_id = o.id
        )
        ORDER BY o.date_created_gmt DESC
        LIMIT 500
    ");
    
    echo '<div class="wrap">';
    echo '<h1>♻️ Retraiter Points</h1>';
    echo '<p>Affiche les commandes/abonnements qui n\'ont pas eu de points attribués.</p>';
    
    // Formulaire de recherche
    echo '<div class="card" style="max-width: 1200px; padding: 15px; margin-bottom: 20px; background: #f8f9fa;">';
    echo '<form method="GET" action="" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">';
    echo '<input type="hidden" name="page" value="newsaiige-loyalty-reprocess-points">';
    
    echo '<div>';
    echo '<label style="display: block; margin-bottom: 5px; font-weight: 600;">🔍 Rechercher par Utilisateur (ID)</label>';
    echo '<input type="number" name="search_user_id" value="' . esc_attr($search_user_id) . '" placeholder="Ex: 123" style="width: 150px;">';
    echo '</div>';
    
    echo '<div>';
    echo '<label style="display: block; margin-bottom: 5px; font-weight: 600;">📅 Période (mois)</label>';
    echo '<select name="search_months" style="width: 150px;">';
    echo '<option value="1"' . ($search_months == 1 ? ' selected' : '') . '>1 mois</option>';
    echo '<option value="3"' . ($search_months == 3 ? ' selected' : '') . '>3 mois</option>';
    echo '<option value="6"' . ($search_months == 6 ? ' selected' : '') . '>6 mois</option>';
    echo '<option value="12"' . ($search_months == 12 ? ' selected' : '') . '>12 mois</option>';
    echo '<option value="24"' . ($search_months == 24 ? ' selected' : '') . '>24 mois</option>';
    echo '<option value="0"' . ($search_months == 0 ? ' selected' : '') . '>Toutes</option>';
    echo '</select>';
    echo '</div>';
    
    echo '<div>';
    echo '<button type="submit" class="button button-primary" style="margin-top: 0;">Filtrer</button>';
    echo '<a href="?page=newsaiige-loyalty-reprocess-points" class="button" style="margin-left: 5px;">Réinitialiser</a>';
    echo '</div>';
    
    echo '<div style="margin-left: auto; text-align: right; font-size: 13px; color: #666;">';
    echo '<strong>Résultats :</strong> ' . count($missing_points_orders) . ' commande(s) trouvée(s)';
    echo '</div>';
    
    echo '</form>';
    echo '</div>';
    
    if (empty($missing_points_orders)) {
        echo '<div class="notice notice-info"><p>✓ Toutes les commandes dans cette période ont des points attribués!</p></div>';
    } else {
        echo '<form method="POST" style="margin-top: 20px;">';
        wp_nonce_field('newsaiige_reprocess_nonce');
        echo '<input type="hidden" name="newsaiige_reprocess_action" value="reprocess">';
        
        echo '<table class="widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th style="width: 30px;"><input type="checkbox" id="select-all-orders"></th>';
        echo '<th>Commande ID</th>';
        echo '<th>Client</th>';
        echo '<th>Type</th>';
        echo '<th>Statut</th>';
        echo '<th>Montant</th>';
        echo '<th>Points Estimés</th>';
        echo '<th>Date</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($missing_points_orders as $order_data) {
            $order = wc_get_order($order_data->order_id);
            if (!$order) continue;
            
            $estimated_points = floor($order_data->total * floatval($points_system->get_setting('points_per_euro', 1)));
            $customer_name = !empty($order_data->display_name) ? $order_data->display_name : 'Utilisateur #' . $order_data->customer_id;
            
            echo '<tr>';
            echo '<td><input type="checkbox" name="order_ids[]" value="' . $order_data->order_id . '"></td>';
            echo '<td><strong>#' . $order_data->order_id . '</strong></td>';
            echo '<td><strong>' . esc_html($customer_name) . '</strong> <small>(ID: ' . $order_data->customer_id . ')</small><br><small>' . esc_html($order_data->email) . '</small></td>';
            echo '<td><span style="' . ($order_data->type === 'wps_subscription' || $order_data->type === 'wps_subscriptions' ? 'color: green;' : '') . '">' . esc_html($order_data->type) . '</span></td>';
            echo '<td><span style="background: #' . ($order_data->status === 'wc-completed' || $order_data->status === 'wc-active' ? '21ba45' : 'ff9800') . '; color: white; padding: 3px 8px; border-radius: 3px;">' . esc_html($order_data->status) . '</span></td>';
            echo '<td>' . wc_price($order_data->total) . '</td>';
            echo '<td><strong>' . $estimated_points . ' pts</strong></td>';
            echo '<td>' . esc_html($order->get_date_created()->format('d/m/Y H:i')) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        
        echo '<div style="margin-top: 20px;">';
        echo '<button type="submit" class="button button-primary">Retraiter les points sélectionnés</button>';
        echo ' <span style="margin-left: 20px; color: #666;">Total: ' . count($missing_points_orders) . ' commande(s)</span>';
        echo '</div>';
        
        echo '</form>';
    }
    
    echo '<div class="card" style="max-width: 800px; background: #e3f2fd; border-left: 4px solid #2196f3; padding: 20px; margin-top: 30px;">';
    echo '<h3>ℹ️ À Propos du Retraitement</h3>';
    echo '<ul style="list-style-type: disc; margin-left: 20px;">';
    echo '<li><strong>Commandes WPS :</strong> Abonnements détectés mais points non attribués</li>';
    echo '<li><strong>Commandes Shop :</strong> Commandes régulières sans points</li>';
    echo '<li><strong>Calcul :</strong> Points = Montant × ' . floatval($points_system->get_setting('points_per_euro', 1)) . ' pts/€</li>';
    echo '<li><strong>Vérification :</strong> Les abonnements actifs vérifiés avant attribution</li>';
    echo '</ul>';
    echo '</div>';
    
    echo '</div>';
    
    // JavaScript pour sélection globale
    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        const selectAll = document.getElementById("select-all-orders");
        if (selectAll) {
            selectAll.addEventListener("change", function() {
                const checkboxes = document.querySelectorAll("input[name=\"order_ids[]\"]");
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
            });
        }
    });
    </script>';
}
?>