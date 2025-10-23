<?php
/**
 * Ajouter le champ téléphone dans la section "Informations de contact" du back-office WordPress
 * À ajouter dans functions.php
 */

// Ajouter le champ téléphone dans la section "Informations de contact" existante
add_filter('user_contactmethods', 'newsaiige_add_phone_contact_method');

function newsaiige_add_phone_contact_method($contact_methods) {
    // Ajouter le champ téléphone aux méthodes de contact WordPress
    $contact_methods['phone'] = 'Téléphone';
    
    return $contact_methods;
}

// Optionnel : Personnaliser l'affichage du champ téléphone avec du JavaScript
add_action('admin_footer-user-edit.php', 'newsaiige_customize_phone_field');
add_action('admin_footer-profile.php', 'newsaiige_customize_phone_field');

function newsaiige_customize_phone_field() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Modifier le type d'input pour le téléphone
        var phoneInput = $('#phone');
        if (phoneInput.length) {
            phoneInput.attr('type', 'tel');
            phoneInput.attr('placeholder', '06 01 02 03 04');
            phoneInput.attr('pattern', '[0-9+\\s\\-\\.\\(\\)]+');
            
            // Ajouter une description personnalisée
            if (!phoneInput.siblings('.description').length) {
                phoneInput.after('<p class="description">Format recommandé : 06 01 02 03 04</p>');
            }
            
            // Validation en temps réel
            phoneInput.on('blur', function() {
                var phone = $(this).val();
                if (phone && phone.length > 0) {
                    // Nettoyer le numéro
                    var cleanPhone = phone.replace(/[^0-9+]/g, '');
                    
                    // Vérification basique du format français
                    var isValid = /^(?:(?:\+|00)33|0)\s*[1-9](?:[0-9]\s*){8}$/.test(phone) || 
                                 /^[0-9]{10}$/.test(cleanPhone);
                    
                    if (!isValid) {
                        $(this).css('border-color', '#dc3232');
                        if (!$(this).siblings('.phone-error').length) {
                            $(this).after('<p class="phone-error description" style="color: #dc3232;">Format recommandé : 06 01 02 03 04 ou +33 6 01 02 03 04</p>');
                        }
                    } else {
                        $(this).css('border-color', '#46b450');
                        $(this).siblings('.phone-error').remove();
                    }
                } else {
                    $(this).css('border-color', '');
                    $(this).siblings('.phone-error').remove();
                }
            });
        }
    });
    </script>
    <?php
}

// Note: WordPress gère automatiquement la sauvegarde des champs de contact
// Le code ci-dessous est maintenu pour des fonctionnalités supplémentaires de validation

// Ajouter le champ téléphone dans la liste des utilisateurs (colonnes)
add_filter('manage_users_columns', 'newsaiige_add_phone_column');

function newsaiige_add_phone_column($columns) {
    $columns['phone'] = 'Téléphone';
    return $columns;
}

// Afficher le contenu de la colonne téléphone
add_filter('manage_users_custom_column', 'newsaiige_show_phone_column_content', 10, 3);

function newsaiige_show_phone_column_content($value, $column_name, $user_id) {
    if ($column_name == 'phone') {
        $phone = get_user_meta($user_id, 'phone', true);
        return $phone ? esc_html($phone) : '—';
    }
    return $value;
}

// Rendre la colonne téléphone triable
add_filter('manage_users_sortable_columns', 'newsaiige_make_phone_column_sortable');

function newsaiige_make_phone_column_sortable($columns) {
    $columns['phone'] = 'phone';
    return $columns;
}

// Gérer le tri de la colonne téléphone
add_action('pre_get_users', 'newsaiige_sort_users_by_phone');

function newsaiige_sort_users_by_phone($query) {
    if (!is_admin()) {
        return;
    }

    $orderby = $query->get('orderby');
    if ($orderby == 'phone') {
        $query->set('meta_key', 'phone');
        $query->set('orderby', 'meta_value');
    }
}

// Ajouter le champ téléphone dans les champs de recherche utilisateur
add_filter('user_search_columns', 'newsaiige_add_phone_to_search', 10, 3);

function newsaiige_add_phone_to_search($search_columns, $search, $wp_user_query) {
    $search_columns[] = 'phone';
    return $search_columns;
}

// Hook pour rechercher dans les métadonnées utilisateur
add_action('pre_user_query', 'newsaiige_search_phone_meta');

function newsaiige_search_phone_meta($user_query) {
    global $wpdb;
    
    if (isset($user_query->query_vars['search']) && !empty($user_query->query_vars['search'])) {
        $search = trim($user_query->query_vars['search'], '*');
        
        if (!empty($search)) {
            $user_query->query_from .= " LEFT JOIN {$wpdb->usermeta} um_phone ON {$wpdb->users}.ID = um_phone.user_id AND um_phone.meta_key = 'phone'";
            
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $user_query->query_where = str_replace(
                'WHERE 1=1 AND (',
                "WHERE 1=1 AND (um_phone.meta_value LIKE '{$search_term}' OR ",
                $user_query->query_where
            );
        }
    }
}

// Fonction utilitaire pour récupérer le téléphone d'un utilisateur (à utiliser dans vos templates)
function newsaiige_get_user_phone($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    return get_user_meta($user_id, 'phone', true);
}

// Fonction pour formater le numéro de téléphone
function newsaiige_format_phone($phone) {
    if (empty($phone)) {
        return '';
    }
    
    // Nettoyer le numéro
    $clean = preg_replace('/[^0-9]/', '', $phone);
    
    // Format français standard
    if (strlen($clean) === 10 && substr($clean, 0, 1) === '0') {
        return substr($clean, 0, 2) . ' ' . 
               substr($clean, 2, 2) . ' ' . 
               substr($clean, 4, 2) . ' ' . 
               substr($clean, 6, 2) . ' ' . 
               substr($clean, 8, 2);
    }
    
    return $phone; // Retourner tel quel si pas de format reconnu
}

// Ajouter des styles CSS pour améliorer l'affichage dans l'admin
add_action('admin_head', 'newsaiige_admin_phone_styles');

function newsaiige_admin_phone_styles() {
    ?>
    <style>
    .column-phone {
        width: 150px;
    }
    
    #phone {
        max-width: 300px;
    }
    
    .form-table th[scope="row"] {
        width: 150px;
    }
    </style>
    <?php
}

// Validation AJAX du numéro de téléphone (optionnel)
add_action('wp_ajax_validate_phone', 'newsaiige_validate_phone_ajax');
add_action('wp_ajax_nopriv_validate_phone', 'newsaiige_validate_phone_ajax');

function newsaiige_validate_phone_ajax() {
    check_ajax_referer('validate_phone_nonce', 'nonce');
    
    $phone = sanitize_text_field($_POST['phone']);
    $is_valid = false;
    $message = '';
    
    if (empty($phone)) {
        $is_valid = true;
        $message = 'Le champ téléphone peut être vide.';
    } else {
        $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
        
        if (preg_match('/^(?:(?:\+|00)33|0)\s*[1-9](?:[0-9]\s*){8}$/', $phone) || 
            preg_match('/^[0-9]{10}$/', $clean_phone)) {
            $is_valid = true;
            $message = 'Numéro de téléphone valide.';
        } else {
            $message = 'Format de téléphone incorrect. Utilisez le format : 06 01 02 03 04';
        }
    }
    
    wp_send_json(array(
        'valid' => $is_valid,
        'message' => $message
    ));
}
?>