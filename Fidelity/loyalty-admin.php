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
    global $wpdb;
    
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
        HAVING total_points > 0 OR available_points > 0
        ORDER BY total_points DESC
        LIMIT 50
    ");
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
                        <?php foreach ($users_data as $user_data): ?>
                        <tr>
                            <td><strong><?php echo esc_html($user_data->display_name); ?></strong></td>
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
?>