<?php
/**
 * NewSaiige Gift Cards Admin Panel
 * Interface d'administration pour gérer les cartes cadeaux
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajouter le menu d'administration
 */
function newsaiige_gift_cards_admin_menu() {
    add_menu_page(
        'Cartes Cadeaux',
        'Cartes Cadeaux',
        'manage_options',
        'newsaiige-gift-cards',
        'newsaiige_gift_cards_admin_page',
        'dashicons-tickets-alt',
        30
    );
    
    add_submenu_page(
        'newsaiige-gift-cards',
        'Toutes les cartes',
        'Toutes les cartes',
        'manage_options',
        'newsaiige-gift-cards',
        'newsaiige_gift_cards_admin_page'
    );
    
    add_submenu_page(
        'newsaiige-gift-cards',
        'Valider une carte',
        'Valider une carte',
        'manage_options',
        'newsaiige-gift-cards-validate',
        'newsaiige_gift_cards_validate_page'
    );
    
    add_submenu_page(
        'newsaiige-gift-cards',
        'Statistiques',
        'Statistiques',
        'manage_options',
        'newsaiige-gift-cards-stats',
        'newsaiige_gift_cards_stats_page'
    );
}

add_action('admin_menu', 'newsaiige_gift_cards_admin_menu');

/**
 * Page principale d'administration des cartes cadeaux
 */
function newsaiige_gift_cards_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_gift_cards';
    
    // Traitement des actions
    if (isset($_POST['action'])) {
        newsaiige_handle_gift_card_actions();
    }
    
    // Pagination
    $per_page = 20;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    // Filtres
    $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    
    // Construire la requête
    $where_conditions = array('1=1');
    $where_values = array();
    
    if ($status_filter) {
        $where_conditions[] = 'status = %s';
        $where_values[] = $status_filter;
    }
    
    if ($search) {
        $where_conditions[] = '(code LIKE %s OR buyer_name LIKE %s OR recipient_name LIKE %s OR buyer_email LIKE %s OR recipient_email LIKE %s)';
        $search_term = '%' . $wpdb->esc_like($search) . '%';
        $where_values = array_merge($where_values, array($search_term, $search_term, $search_term, $search_term, $search_term));
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Compter le total
    $total_query = "SELECT COUNT(*) FROM $table_name WHERE $where_clause";
    if ($where_values) {
        $total_items = $wpdb->get_var($wpdb->prepare($total_query, $where_values));
    } else {
        $total_items = $wpdb->get_var($total_query);
    }
    
    // Récupérer les cartes cadeaux
    $query = "SELECT * FROM $table_name WHERE $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
    $query_values = array_merge($where_values, array($per_page, $offset));
    $gift_cards = $wpdb->get_results($wpdb->prepare($query, $query_values));
    
    // Calculer le nombre total de pages
    $total_pages = ceil($total_items / $per_page);
    
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Cartes Cadeaux NewSaiige</h1>
        
        <!-- Statistiques rapides -->
        <div class="newsaiige-stats-cards" style="display: flex; gap: 20px; margin: 20px 0;">
            <?php
            $stats = newsaiige_get_gift_cards_stats();
            ?>
            <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); flex: 1;">
                <h3 style="margin: 0 0 10px 0; color: #82897F;">Total des ventes</h3>
                <p style="font-size: 24px; font-weight: bold; margin: 0; color: #333;"><?php echo number_format($stats['total_revenue'], 2, ',', ' '); ?>€</p>
            </div>
            <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); flex: 1;">
                <h3 style="margin: 0 0 10px 0; color: #82897F;">Cartes actives</h3>
                <p style="font-size: 24px; font-weight: bold; margin: 0; color: #333;"><?php echo $stats['active_cards']; ?></p>
            </div>
            <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); flex: 1;">
                <h3 style="margin: 0 0 10px 0; color: #82897F;">Cartes utilisées</h3>
                <p style="font-size: 24px; font-weight: bold; margin: 0; color: #333;"><?php echo $stats['used_cards']; ?></p>
            </div>
        </div>
        
        <!-- Filtres et recherche -->
        <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <form method="get" style="display: flex; gap: 15px; align-items: end;">
                <input type="hidden" name="page" value="newsaiige-gift-cards">
                
                <div>
                    <label for="status-filter"><strong>Statut :</strong></label><br>
                    <select name="status" id="status-filter" style="width: 150px;">
                        <option value="">Tous les statuts</option>
                        <option value="pending" <?php selected($status_filter, 'pending'); ?>>En attente</option>
                        <option value="paid" <?php selected($status_filter, 'paid'); ?>>Payé</option>
                        <option value="sent" <?php selected($status_filter, 'sent'); ?>>Envoyé</option>
                        <option value="used" <?php selected($status_filter, 'used'); ?>>Utilisé</option>
                        <option value="expired" <?php selected($status_filter, 'expired'); ?>>Expiré</option>
                    </select>
                </div>
                
                <div>
                    <label for="search-input"><strong>Rechercher :</strong></label><br>
                    <input type="text" name="s" id="search-input" value="<?php echo esc_attr($search); ?>" placeholder="Code, nom, email..." style="width: 200px;">
                </div>
                
                <div>
                    <input type="submit" class="button" value="Filtrer">
                    <?php if ($status_filter || $search): ?>
                        <a href="<?php echo admin_url('admin.php?page=newsaiige-gift-cards'); ?>" class="button">Réinitialiser</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Table des cartes cadeaux -->
        <form method="post">
            <?php wp_nonce_field('newsaiige_gift_cards_bulk_action', 'newsaiige_gift_cards_nonce'); ?>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="action">
                        <option value="">Actions groupées</option>
                        <option value="mark_sent">Marquer comme envoyé</option>
                        <option value="mark_used">Marquer comme utilisé</option>
                        <option value="resend_email">Renvoyer l'email</option>
                        <option value="delete">Supprimer</option>
                    </select>
                    <input type="submit" class="button action" value="Appliquer">
                </div>
                
                <?php if ($total_pages > 1): ?>
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo $total_items; ?> éléments</span>
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    ?>
                </div>
                <?php endif; ?>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all">
                        </td>
                        <th scope="col" class="manage-column">Code</th>
                        <th scope="col" class="manage-column">Montant</th>
                        <th scope="col" class="manage-column">Acheteur</th>
                        <th scope="col" class="manage-column">Destinataire</th>
                        <th scope="col" class="manage-column">Statut</th>
                        <th scope="col" class="manage-column">Date création</th>
                        <th scope="col" class="manage-column">Expire le</th>
                        <th scope="col" class="manage-column">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($gift_cards)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: #666;">
                                Aucune carte cadeau trouvée.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($gift_cards as $card): ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="gift_card_ids[]" value="<?php echo $card->id; ?>">
                                </th>
                                <td>
                                    <strong><?php echo esc_html($card->code); ?></strong>
                                </td>
                                <td>
                                    <strong><?php echo number_format($card->amount, 2, ',', ' '); ?>€</strong>
                                </td>
                                <td>
                                    <?php echo esc_html($card->buyer_name); ?><br>
                                    <small style="color: #666;"><?php echo esc_html($card->buyer_email); ?></small>
                                </td>
                                <td>
                                    <?php if ($card->recipient_type === 'other'): ?>
                                        <?php echo esc_html($card->recipient_name ?: 'Non spécifié'); ?><br>
                                        <small style="color: #666;"><?php echo esc_html($card->recipient_email); ?></small>
                                    <?php else: ?>
                                        <em>Pour soi-même</em><br>
                                        <small style="color: #666;"><?php echo esc_html($card->recipient_email); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo newsaiige_get_status_badge($card->status); ?>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y H:i', strtotime($card->created_at)); ?>
                                </td>
                                <td>
                                    <?php 
                                    $expires = date('d/m/Y', strtotime($card->expires_at));
                                    $is_expired = strtotime($card->expires_at) < time();
                                    echo '<span style="color: ' . ($is_expired ? 'red' : 'inherit') . '">' . $expires . '</span>';
                                    ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <a href="#" onclick="viewGiftCard(<?php echo $card->id; ?>)" class="button button-small">Voir</a>
                                        <?php if ($card->status === 'paid'): ?>
                                            <a href="#" onclick="resendEmail(<?php echo $card->id; ?>)" class="button button-small">Renvoyer</a>
                                        <?php endif; ?>
                                        <?php if (in_array($card->status, ['sent', 'paid'])): ?>
                                            <a href="#" onclick="markAsUsed(<?php echo $card->id; ?>)" class="button button-small">Utiliser</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
        
        <!-- Pagination du bas -->
        <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $current_page
                ));
                ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal pour voir les détails -->
    <div id="gift-card-modal" style="display: none;">
        <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000;">
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 8px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
                <h2>Détails de la carte cadeau</h2>
                <div id="gift-card-details"></div>
                <div style="text-align: right; margin-top: 20px;">
                    <button onclick="closeModal()" class="button button-primary">Fermer</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Gestion des cases à cocher
    document.getElementById('cb-select-all').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="gift_card_ids[]"]');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
    
    // Voir les détails d'une carte cadeau
    function viewGiftCard(id) {
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_gift_card_details&id=' + id + '&nonce=<?php echo wp_create_nonce('newsaiige_gift_card_details'); ?>'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('gift-card-details').innerHTML = data.data;
                document.getElementById('gift-card-modal').style.display = 'block';
            }
        });
    }
    
    // Fermer le modal
    function closeModal() {
        document.getElementById('gift-card-modal').style.display = 'none';
    }
    
    // Renvoyer un email
    function resendEmail(id) {
        if (confirm('Êtes-vous sûr de vouloir renvoyer l\'email ?')) {
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=resend_gift_card_email&id=' + id + '&nonce=<?php echo wp_create_nonce('newsaiige_resend_email'); ?>'
            })
            .then(response => response.json())
            .then(data => {
                alert(data.success ? 'Email renvoyé avec succès !' : 'Erreur : ' + data.data);
                if (data.success) location.reload();
            });
        }
    }
    
    // Marquer comme utilisé
    function markAsUsed(id) {
        if (confirm('Marquer cette carte comme utilisée ?')) {
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=mark_gift_card_used&id=' + id + '&nonce=<?php echo wp_create_nonce('newsaiige_mark_used'); ?>'
            })
            .then(response => response.json())
            .then(data => {
                alert(data.success ? 'Carte marquée comme utilisée !' : 'Erreur : ' + data.data);
                if (data.success) location.reload();
            });
        }
    }
    </script>
    
    <style>
    .newsaiige-stats-cards h3 {
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .wp-list-table th,
    .wp-list-table td {
        padding: 12px 8px;
    }
    
    .button-small {
        padding: 2px 8px;
        font-size: 11px;
        line-height: 1.4;
        height: auto;
    }
    </style>
    <?php
}

/**
 * Page de validation des cartes cadeaux
 */
function newsaiige_gift_cards_validate_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_gift_cards';
    
    $message = '';
    $gift_card = null;
    
    // Traitement de la validation
    if (isset($_POST['validate_code'])) {
        $code = sanitize_text_field($_POST['gift_card_code']);
        
        if (empty($code)) {
            $message = '<div class="notice notice-error"><p>Veuillez saisir un code de carte cadeau.</p></div>';
        } else {
            $gift_card = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE code = %s",
                $code
            ));
            
            if (!$gift_card) {
                $message = '<div class="notice notice-error"><p>Code de carte cadeau invalide.</p></div>';
            } elseif ($gift_card->status === 'used') {
                $message = '<div class="notice notice-warning"><p>Cette carte cadeau a déjà été utilisée le ' . date('d/m/Y à H:i', strtotime($gift_card->used_at)) . '.</p></div>';
            } elseif (strtotime($gift_card->expires_at) < time()) {
                $message = '<div class="notice notice-error"><p>Cette carte cadeau a expiré le ' . date('d/m/Y', strtotime($gift_card->expires_at)) . '.</p></div>';
            } elseif (!in_array($gift_card->status, ['sent', 'paid'])) {
                $message = '<div class="notice notice-warning"><p>Cette carte cadeau n\'est pas encore active (statut: ' . $gift_card->status . ').</p></div>';
            } else {
                $message = '<div class="notice notice-success"><p>Carte cadeau valide ! Vous pouvez l\'utiliser.</p></div>';
            }
        }
    }
    
    // Traitement de l'utilisation
    if (isset($_POST['use_card']) && $gift_card && in_array($gift_card->status, ['sent', 'paid'])) {
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => 'used',
                'used_at' => current_time('mysql')
            ),
            array('id' => $gift_card->id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result) {
            $gift_card->status = 'used';
            $gift_card->used_at = current_time('mysql');
            $message = '<div class="notice notice-success"><p>Carte cadeau utilisée avec succès !</p></div>';
        } else {
            $message = '<div class="notice notice-error"><p>Erreur lors de l\'utilisation de la carte cadeau.</p></div>';
        }
    }
    
    ?>
    <div class="wrap">
        <h1>Valider une Carte Cadeau</h1>
        
        <?php echo $message; ?>
        
        <div style="background: #fff; padding: 30px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-width: 600px;">
            <h2>Vérifier un code de carte cadeau</h2>
            
            <form method="post" style="margin-bottom: 30px;">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="gift_card_code">Code de la carte cadeau</label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="gift_card_code" 
                                   id="gift_card_code" 
                                   class="regular-text" 
                                   placeholder="NSGG-XXXX-XXXX"
                                   value="<?php echo isset($_POST['gift_card_code']) ? esc_attr($_POST['gift_card_code']) : ''; ?>"
                                   style="text-transform: uppercase; letter-spacing: 2px; font-family: monospace;">
                            <p class="description">Saisissez le code de la carte cadeau à valider</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="validate_code" class="button button-primary" value="Vérifier le code">
                </p>
            </form>
            
            <?php if ($gift_card && in_array($gift_card->status, ['sent', 'paid'])): ?>
            <div style="background: #f0f8ff; border: 2px solid #0073aa; border-radius: 8px; padding: 20px; margin: 20px 0;">
                <h3 style="margin-top: 0; color: #0073aa;">Détails de la carte cadeau</h3>
                
                <table class="form-table">
                    <tr>
                        <th>Code :</th>
                        <td><strong style="font-family: monospace; font-size: 16px;"><?php echo esc_html($gift_card->code); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Montant :</th>
                        <td><strong style="font-size: 18px; color: #82897F;"><?php echo number_format($gift_card->amount, 2, ',', ' '); ?>€</strong></td>
                    </tr>
                    <tr>
                        <th>Acheteur :</th>
                        <td><?php echo esc_html($gift_card->buyer_name); ?></td>
                    </tr>
                    <tr>
                        <th>Destinataire :</th>
                        <td>
                            <?php if ($gift_card->recipient_type === 'other'): ?>
                                <?php echo esc_html($gift_card->recipient_name ?: 'Non spécifié'); ?>
                            <?php else: ?>
                                <em>Achat pour soi-même</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Date d'expiration :</th>
                        <td><?php echo date('d/m/Y', strtotime($gift_card->expires_at)); ?></td>
                    </tr>
                    <?php if ($gift_card->personal_message): ?>
                    <tr>
                        <th>Message personnel :</th>
                        <td><em>"<?php echo esc_html($gift_card->personal_message); ?>"</em></td>
                    </tr>
                    <?php endif; ?>
                </table>
                
                <form method="post" style="margin-top: 20px;">
                    <input type="hidden" name="gift_card_code" value="<?php echo esc_attr($gift_card->code); ?>">
                    <input type="submit" name="use_card" class="button button-secondary button-large" value="Utiliser cette carte cadeau" 
                           onclick="return confirm('Êtes-vous sûr de vouloir utiliser cette carte cadeau ? Cette action est irréversible.');">
                </form>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Historique récent -->
        <div style="background: #fff; padding: 30px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <h2>Dernières cartes utilisées</h2>
            
            <?php
            $recent_used = $wpdb->get_results(
                "SELECT * FROM $table_name WHERE status = 'used' ORDER BY used_at DESC LIMIT 10"
            );
            ?>
            
            <?php if (empty($recent_used)): ?>
                <p style="color: #666;">Aucune carte utilisée récemment.</p>
            <?php else: ?>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Montant</th>
                            <th>Utilisée le</th>
                            <th>Acheteur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_used as $card): ?>
                        <tr>
                            <td style="font-family: monospace;"><?php echo esc_html($card->code); ?></td>
                            <td><strong><?php echo number_format($card->amount, 2, ',', ' '); ?>€</strong></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($card->used_at)); ?></td>
                            <td><?php echo esc_html($card->buyer_name); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    // Auto-format du code de carte cadeau
    document.getElementById('gift_card_code').addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^A-Z0-9]/g, '').toUpperCase();
        if (value.length > 4 && value.length <= 8) {
            value = value.slice(0, 4) + '-' + value.slice(4);
        } else if (value.length > 8) {
            value = value.slice(0, 4) + '-' + value.slice(4, 8) + '-' + value.slice(8, 12);
        }
        e.target.value = value;
    });
    </script>
    <?php
}

/**
 * Page de statistiques
 */
function newsaiige_gift_cards_stats_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_gift_cards';
    
    // Récupérer les statistiques
    $stats = newsaiige_get_gift_cards_detailed_stats();
    
    ?>
    <div class="wrap">
        <h1>Statistiques des Cartes Cadeaux</h1>
        
        <!-- Métriques principales -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
            <div style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;">
                <h3 style="margin: 0 0 10px 0; color: #82897F;">Chiffre d'affaires total</h3>
                <p style="font-size: 32px; font-weight: bold; margin: 0; color: #333;"><?php echo number_format($stats['total_revenue'], 2, ',', ' '); ?>€</p>
                <small style="color: #666;">Depuis le début</small>
            </div>
            
            <div style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;">
                <h3 style="margin: 0 0 10px 0; color: #82897F;">Cartes vendues</h3>
                <p style="font-size: 32px; font-weight: bold; margin: 0; color: #333;"><?php echo $stats['total_sold']; ?></p>
                <small style="color: #666;">Total des ventes</small>
            </div>
            
            <div style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;">
                <h3 style="margin: 0 0 10px 0; color: #82897F;">Taux d'utilisation</h3>
                <p style="font-size: 32px; font-weight: bold; margin: 0; color: #333;"><?php echo $stats['usage_rate']; ?>%</p>
                <small style="color: #666;"><?php echo $stats['used_cards']; ?> / <?php echo $stats['active_cards']; ?> cartes</small>
            </div>
            
            <div style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;">
                <h3 style="margin: 0 0 10px 0; color: #82897F;">Montant moyen</h3>
                <p style="font-size: 32px; font-weight: bold; margin: 0; color: #333;"><?php echo number_format($stats['average_amount'], 0, ',', ' '); ?>€</p>
                <small style="color: #666;">Par carte cadeau</small>
            </div>
        </div>
        
        <!-- Graphiques et détails -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
            <!-- Répartition par statut -->
            <div style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3>Répartition par statut</h3>
                <?php foreach ($stats['by_status'] as $status => $count): ?>
                <div style="display: flex; justify-content: space-between; margin: 10px 0; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                    <span><?php echo newsaiige_get_status_label($status); ?></span>
                    <strong><?php echo $count; ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Top montants -->
            <div style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3>Montants les plus populaires</h3>
                <?php foreach ($stats['popular_amounts'] as $amount_data): ?>
                <div style="display: flex; justify-content: space-between; margin: 10px 0; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                    <span><?php echo number_format($amount_data->amount, 0, ',', ' '); ?>€</span>
                    <strong><?php echo $amount_data->count; ?> cartes</strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Évolution mensuelle -->
        <div style="background: #fff; padding: 25px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <h3>Évolution mensuelle (6 derniers mois)</h3>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Mois</th>
                        <th>Cartes vendues</th>
                        <th>Chiffre d'affaires</th>
                        <th>Cartes utilisées</th>
                        <th>Taux d'utilisation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['monthly_evolution'] as $month_data): ?>
                    <tr>
                        <td><?php echo $month_data->month_name; ?></td>
                        <td><?php echo $month_data->sold; ?></td>
                        <td><?php echo number_format($month_data->revenue, 2, ',', ' '); ?>€</td>
                        <td><?php echo $month_data->used; ?></td>
                        <td><?php echo $month_data->sold > 0 ? round(($month_data->used / $month_data->sold) * 100, 1) : 0; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

/**
 * Obtenir les statistiques des cartes cadeaux
 */
function newsaiige_get_gift_cards_stats() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_gift_cards';
    
    $stats = $wpdb->get_row("
        SELECT 
            COUNT(*) as total_cards,
            SUM(CASE WHEN status IN ('paid', 'sent', 'used') THEN total_amount ELSE 0 END) as total_revenue,
            SUM(CASE WHEN status IN ('paid', 'sent') THEN 1 ELSE 0 END) as active_cards,
            SUM(CASE WHEN status = 'used' THEN 1 ELSE 0 END) as used_cards
        FROM $table_name
    ");
    
    return array(
        'total_cards' => $stats->total_cards ?: 0,
        'total_revenue' => $stats->total_revenue ?: 0,
        'active_cards' => $stats->active_cards ?: 0,
        'used_cards' => $stats->used_cards ?: 0
    );
}

/**
 * Obtenir les statistiques détaillées
 */
function newsaiige_get_gift_cards_detailed_stats() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_gift_cards';
    
    // Stats de base
    $basic_stats = newsaiige_get_gift_cards_stats();
    
    // Calculs supplémentaires
    $total_sold = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status IN ('paid', 'sent', 'used')");
    $average_amount = $wpdb->get_var("SELECT AVG(amount) FROM $table_name WHERE status IN ('paid', 'sent', 'used')");
    $usage_rate = $total_sold > 0 ? round(($basic_stats['used_cards'] / $total_sold) * 100, 1) : 0;
    
    // Répartition par statut
    $by_status = $wpdb->get_results("
        SELECT status, COUNT(*) as count 
        FROM $table_name 
        GROUP BY status 
        ORDER BY count DESC
    ", OBJECT_K);
    
    $status_array = array();
    foreach ($by_status as $status => $data) {
        $status_array[$status] = $data->count;
    }
    
    // Montants populaires
    $popular_amounts = $wpdb->get_results("
        SELECT amount, COUNT(*) as count 
        FROM $table_name 
        WHERE status IN ('paid', 'sent', 'used')
        GROUP BY amount 
        ORDER BY count DESC 
        LIMIT 5
    ");
    
    // Évolution mensuelle (6 derniers mois)
    $monthly_evolution = $wpdb->get_results("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            DATE_FORMAT(created_at, '%M %Y') as month_name,
            COUNT(*) as sold,
            SUM(total_amount) as revenue,
            SUM(CASE WHEN status = 'used' THEN 1 ELSE 0 END) as used
        FROM $table_name 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        AND status IN ('paid', 'sent', 'used')
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
    ");
    
    return array(
        'total_revenue' => $basic_stats['total_revenue'],
        'total_sold' => $total_sold,
        'active_cards' => $basic_stats['active_cards'],
        'used_cards' => $basic_stats['used_cards'],
        'usage_rate' => $usage_rate,
        'average_amount' => $average_amount ?: 0,
        'by_status' => $status_array,
        'popular_amounts' => $popular_amounts,
        'monthly_evolution' => $monthly_evolution
    );
}

/**
 * Obtenir un badge de statut stylisé
 */
function newsaiige_get_status_badge($status) {
    $colors = array(
        'pending' => '#f39c12',
        'paid' => '#3498db',
        'sent' => '#27ae60',
        'used' => '#9b59b6',
        'expired' => '#e74c3c'
    );
    
    $labels = array(
        'pending' => 'En attente',
        'paid' => 'Payé',
        'sent' => 'Envoyé',
        'used' => 'Utilisé',
        'expired' => 'Expiré'
    );
    
    $color = $colors[$status] ?? '#666';
    $label = $labels[$status] ?? $status;
    
    return '<span style="background: ' . $color . '; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase;">' . $label . '</span>';
}

/**
 * Obtenir le label d'un statut
 */
function newsaiige_get_status_label($status) {
    $labels = array(
        'pending' => 'En attente',
        'paid' => 'Payé',
        'sent' => 'Envoyé',
        'used' => 'Utilisé',
        'expired' => 'Expiré'
    );
    
    return $labels[$status] ?? $status;
}

/**
 * Traiter les actions groupées
 */
function newsaiige_handle_gift_card_actions() {
    if (!wp_verify_nonce($_POST['newsaiige_gift_cards_nonce'], 'newsaiige_gift_cards_bulk_action')) {
        wp_die('Erreur de sécurité');
    }
    
    $action = sanitize_text_field($_POST['action']);
    $gift_card_ids = array_map('intval', $_POST['gift_card_ids'] ?? array());
    
    if (empty($gift_card_ids)) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>Aucune carte sélectionnée.</p></div>';
        });
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_gift_cards';
    
    $processed = 0;
    
    foreach ($gift_card_ids as $id) {
        switch ($action) {
            case 'mark_sent':
                $result = $wpdb->update(
                    $table_name,
                    array('status' => 'sent', 'sent_at' => current_time('mysql')),
                    array('id' => $id),
                    array('%s', '%s'),
                    array('%d')
                );
                if ($result) $processed++;
                break;
                
            case 'mark_used':
                $result = $wpdb->update(
                    $table_name,
                    array('status' => 'used', 'used_at' => current_time('mysql')),
                    array('id' => $id),
                    array('%s', '%s'),
                    array('%d')
                );
                if ($result) $processed++;
                break;
                
            case 'resend_email':
                $gift_card = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
                if ($gift_card && newsaiige_send_gift_card_email($gift_card)) {
                    $processed++;
                }
                break;
                
            case 'delete':
                $result = $wpdb->delete($table_name, array('id' => $id), array('%d'));
                if ($result) $processed++;
                break;
        }
    }
    
    add_action('admin_notices', function() use ($processed, $action) {
        $messages = array(
            'mark_sent' => 'cartes marquées comme envoyées',
            'mark_used' => 'cartes marquées comme utilisées',
            'resend_email' => 'emails renvoyés',
            'delete' => 'cartes supprimées'
        );
        
        $message = $messages[$action] ?? 'éléments traités';
        echo '<div class="notice notice-success"><p>' . $processed . ' ' . $message . '.</p></div>';
    });
}

/**
 * Actions AJAX pour l'administration
 */

// Obtenir les détails d'une carte cadeau
add_action('wp_ajax_get_gift_card_details', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'newsaiige_gift_card_details')) {
        wp_send_json_error('Erreur de sécurité');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_gift_cards';
    
    $id = intval($_POST['id']);
    $gift_card = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    
    if (!$gift_card) {
        wp_send_json_error('Carte non trouvée');
    }
    
    ob_start();
    ?>
    <table class="form-table">
        <tr>
            <th>Code :</th>
            <td><strong style="font-family: monospace; font-size: 16px;"><?php echo esc_html($gift_card->code); ?></strong></td>
        </tr>
        <tr>
            <th>Montant :</th>
            <td><strong style="font-size: 18px; color: #82897F;"><?php echo number_format($gift_card->amount, 2, ',', ' '); ?>€</strong></td>
        </tr>
        <tr>
            <th>Quantité :</th>
            <td><?php echo $gift_card->quantity; ?></td>
        </tr>
        <tr>
            <th>Montant total :</th>
            <td><strong><?php echo number_format($gift_card->total_amount, 2, ',', ' '); ?>€</strong></td>
        </tr>
        <tr>
            <th>Acheteur :</th>
            <td>
                <?php echo esc_html($gift_card->buyer_name); ?><br>
                <a href="mailto:<?php echo esc_attr($gift_card->buyer_email); ?>"><?php echo esc_html($gift_card->buyer_email); ?></a>
            </td>
        </tr>
        <tr>
            <th>Destinataire :</th>
            <td>
                <?php if ($gift_card->recipient_type === 'other'): ?>
                    <?php echo esc_html($gift_card->recipient_name ?: 'Non spécifié'); ?><br>
                    <a href="mailto:<?php echo esc_attr($gift_card->recipient_email); ?>"><?php echo esc_html($gift_card->recipient_email); ?></a>
                <?php else: ?>
                    <em>Achat pour soi-même</em><br>
                    <a href="mailto:<?php echo esc_attr($gift_card->recipient_email); ?>"><?php echo esc_html($gift_card->recipient_email); ?></a>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Statut :</th>
            <td><?php echo newsaiige_get_status_badge($gift_card->status); ?></td>
        </tr>
        <tr>
            <th>Date de livraison :</th>
            <td><?php echo date('d/m/Y', strtotime($gift_card->delivery_date)); ?></td>
        </tr>
        <tr>
            <th>Date d'expiration :</th>
            <td><?php echo date('d/m/Y', strtotime($gift_card->expires_at)); ?></td>
        </tr>
        <tr>
            <th>Créé le :</th>
            <td><?php echo date('d/m/Y H:i', strtotime($gift_card->created_at)); ?></td>
        </tr>
        <?php if ($gift_card->sent_at): ?>
        <tr>
            <th>Envoyé le :</th>
            <td><?php echo date('d/m/Y H:i', strtotime($gift_card->sent_at)); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($gift_card->used_at): ?>
        <tr>
            <th>Utilisé le :</th>
            <td><?php echo date('d/m/Y H:i', strtotime($gift_card->used_at)); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($gift_card->personal_message): ?>
        <tr>
            <th>Message personnel :</th>
            <td><em>"<?php echo esc_html($gift_card->personal_message); ?>"</em></td>
        </tr>
        <?php endif; ?>
        <?php if ($gift_card->order_id): ?>
        <tr>
            <th>Commande WooCommerce :</th>
            <td><a href="<?php echo admin_url('post.php?post=' . $gift_card->order_id . '&action=edit'); ?>" target="_blank">#<?php echo $gift_card->order_id; ?></a></td>
        </tr>
        <?php endif; ?>
    </table>
    <?php
    wp_send_json_success(ob_get_clean());
});

// Renvoyer un email
add_action('wp_ajax_resend_gift_card_email', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'newsaiige_resend_email')) {
        wp_send_json_error('Erreur de sécurité');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_gift_cards';
    
    $id = intval($_POST['id']);
    $gift_card = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    
    if (!$gift_card) {
        wp_send_json_error('Carte non trouvée');
    }
    
    if (newsaiige_send_gift_card_email($gift_card)) {
        $wpdb->update(
            $table_name,
            array('sent_at' => current_time('mysql')),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
        wp_send_json_success('Email renvoyé avec succès');
    } else {
        wp_send_json_error('Erreur lors de l\'envoi de l\'email');
    }
});

// Marquer comme utilisé
add_action('wp_ajax_mark_gift_card_used', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'newsaiige_mark_used')) {
        wp_send_json_error('Erreur de sécurité');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_gift_cards';
    
    $id = intval($_POST['id']);
    
    $result = $wpdb->update(
        $table_name,
        array('status' => 'used', 'used_at' => current_time('mysql')),
        array('id' => $id),
        array('%s', '%s'),
        array('%d')
    );
    
    if ($result) {
        wp_send_json_success('Carte marquée comme utilisée');
    } else {
        wp_send_json_error('Erreur lors de la mise à jour');
    }
});

/**
 * Styles CSS pour l'administration
 */
function newsaiige_gift_cards_admin_styles() {
    $screen = get_current_screen();
    if (strpos($screen->id, 'newsaiige-gift-cards') !== false) {
        ?>
        <style>
        .newsaiige-stats-cards {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        
        .newsaiige-stats-cards > div {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            flex: 1;
            text-align: center;
        }
        
        .newsaiige-stats-cards h3 {
            margin: 0 0 10px 0;
            color: #82897F;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .newsaiige-stats-cards p {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            color: #333;
        }
        
        .wp-list-table .button-small {
            padding: 2px 8px;
            font-size: 11px;
            line-height: 1.4;
            height: auto;
        }
        
        @media (max-width: 768px) {
            .newsaiige-stats-cards {
                flex-direction: column;
            }
        }
        </style>
        <?php
    }
}
add_action('admin_head', 'newsaiige_gift_cards_admin_styles');

?>