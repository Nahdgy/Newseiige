<?php
/**
 * NewSaiige Gift Card HTML Generator
 * Génère des cartes cadeaux HTML accessibles via un lien dans l'email.
 */

// Empêcher l'accès direct (sauf pour les tests)
if (!defined('ABSPATH') && !defined('NEWSAIIGE_TESTING')) {
    exit;
}

/**
 * Obtenir le dossier de sortie des cartes cadeaux dans uploads.
 *
 * @return string|false
 */
function newsaiige_get_gift_cards_output_dir() {
    $upload_dir = wp_upload_dir();

    if (empty($upload_dir['basedir'])) {
        error_log('newsaiige_get_gift_cards_output_dir: basedir uploads introuvable');
        return false;
    }

    $gift_cards_dir = rtrim($upload_dir['basedir'], '/\\') . '/gift-cards/';

    if (!file_exists($gift_cards_dir) && !wp_mkdir_p($gift_cards_dir)) {
        error_log('newsaiige_get_gift_cards_output_dir: Impossible de creer le dossier ' . $gift_cards_dir);
        return false;
    }

    if (!is_writable($gift_cards_dir)) {
        error_log('newsaiige_get_gift_cards_output_dir: Dossier non accessible en ecriture ' . $gift_cards_dir);
        return false;
    }

    return $gift_cards_dir;
}

/**
 * Générer une carte cadeau HTML (point d'entrée principal).
 *
 * @param object $gift_card Données de la carte cadeau
 * @return string|false Chemin du fichier HTML généré
 */
function newsaiige_generate_gift_card_pdf_simple($gift_card) {
    return newsaiige_generate_pdf_as_html($gift_card);
}

/**
 * Générer la carte cadeau HTML et retourner son URL publique.
 * Cette URL est incluse dans l'email pour que le destinataire puisse l'ouvrir et l'imprimer.
 *
 * @param object $gift_card Données de la carte cadeau
 * @return string|false URL publique du fichier HTML, ou false en cas d'erreur
 */
function newsaiige_get_gift_card_html_url($gift_card) {
    $filepath = newsaiige_generate_pdf_as_html($gift_card);

    if (empty($filepath) || !file_exists($filepath)) {
        error_log('newsaiige_get_gift_card_html_url: Fichier HTML introuvable pour la carte ' . ($gift_card->code ?? 'inconnue'));
        return false;
    }

    $upload_dir = wp_upload_dir();
    if (empty($upload_dir['baseurl'])) {
        error_log('newsaiige_get_gift_card_html_url: baseurl uploads introuvable');
        return false;
    }

    $filename = 'gift-card-' . $gift_card->code . '.html';
    $url = rtrim($upload_dir['baseurl'], '/') . '/gift-cards/' . $filename;

    error_log('newsaiige_get_gift_card_html_url: URL generee - ' . $url);
    return $url;
}



/**
 * Générer un fichier HTML de carte cadeau
 * Le destinataire peut l'ouvrir dans un navigateur et l'imprimer en PDF (Ctrl+P)
 */
function newsaiige_generate_pdf_as_html($gift_card) {
    try {
        $html = newsaiige_get_gift_card_html($gift_card);

        $gift_cards_dir = newsaiige_get_gift_cards_output_dir();
        if ($gift_cards_dir === false) {
            return false;
        }

        $filename = 'gift-card-' . $gift_card->code . '.html';
        $filepath = $gift_cards_dir . $filename;

        $written = file_put_contents($filepath, $html);
        if ($written === false || $written <= 0 || !file_exists($filepath)) {
            error_log("newsaiige_generate_gift_card_html: Echec ecriture HTML - $filepath");
            return false;
        }
        
        error_log("newsaiige_generate_gift_card_html: Carte cadeau HTML générée - $filepath");
        
        return $filepath;
        
    } catch (Throwable $e) {
        error_log("newsaiige_generate_gift_card_html: ERREUR - " . $e->getMessage());
        return false;
    }
}

/**
 * Recuperer l'image de fond en data URI pour une meilleure compatibilite PDF.
 *
 * @return string
 */
function newsaiige_get_gift_card_background_data_uri() {
    $background_file = plugin_dir_path(__FILE__) . 'assets/gift-card-background.png';

    if (file_exists($background_file) && is_readable($background_file)) {
        $image_data = file_get_contents($background_file);
        if ($image_data !== false) {
            return 'data:image/png;base64,' . base64_encode($image_data);
        }
    }

    return plugins_url('assets/gift-card-background.png', __FILE__);
}

/**
 * Obtenir le HTML de la carte cadeau
 */
function newsaiige_get_gift_card_html($gift_card) {
    $recipient_name = !empty($gift_card->recipient_name) ? esc_html($gift_card->recipient_name) : 'Un être cher';
    $message = !empty($gift_card->personal_message) ? nl2br(esc_html($gift_card->personal_message)) : '';
    $amount = number_format($gift_card->amount, 2, ',', ' ');
    $buyer_name = esc_html($gift_card->buyer_name);
    $code = esc_html($gift_card->code);
    $expiry_date = date('d/m/Y', strtotime($gift_card->expires_at));
    
    // Image de fond via data URI (compatible navigateurs et evite les problemes CORS).
    $background_image = newsaiige_get_gift_card_background_data_uri();
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Carte Cadeau NewSaiige - <?php echo $code; ?></title>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap');
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Montserrat', sans-serif;
                width: 210mm;
                height: 297mm;
                margin: 0;
                padding: 0;
                background: white;
            }
            
            .gift-card {
                width: 210mm;
                height: 297mm;
                position: relative;
                display: flex;
                flex-direction: column;
                overflow: hidden;
                background: white;
                align-items: center;

            }
            
            .top-image {
                width: 40%;
                height: 91mm;
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                flex-shrink: 0;
                margin-top: 20mm;
            }
            
            .top-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
            .content-wrapper {
                flex: 1;
                display: flex;
                align-items: center;
                padding: 10mm 15mm;
            }
            
            .left-section {
                flex: 1;
                position: relative;
                z-index: 1;
            }
            
            .left-section .tagline {
                font-size: 20pt;
                line-height: 1.8;
                max-width: 55mm;
                color: #333;
            }
            
            .left-section .tagline strong {
                color: #82897F;
                font-weight: 700;
            }
            
            .right-section {
                width: 50%;
                position: relative;
                z-index: 1;
            }
            
            .field {
                margin-bottom: 8mm;
            }
            
            .field-label {
                font-size: 9pt;
                font-weight: 700;
                color: #555;
                margin-bottom: 2mm;
                display: block;
            }
            
            .field-value {
                font-size: 10pt;
                color: #333;
                line-height: 1.4;
                min-height: 5mm;
            }
            
            .field-value.large {
                font-size: 14pt;
                font-weight: 600;
                color: #82897F;
            }
            
            .field-value.code {
                font-size: 11pt;
                font-weight: 700;
                color: #82897F;
                letter-spacing: 1px;
                background: #f8f9fa;
                padding: 3mm;
                border-radius: 5px;
                text-align: center;
            }
            
            .field.message .field-value {
                font-size: 8pt;
                font-style: italic;
                max-height: 15mm;
                overflow: hidden;
            }
            
            .footer {
                width: 100%;
                text-align: center;
                font-size: 7pt;
                font-weight: 700;
                color: #666;
                padding: 5mm 0;
                border-top: 1px solid #e0e0e0;
                margin-top: auto;
            }
            
            .footer div {
                margin: 1mm 0;
            }
            
            @media print {
                body {
                    margin: 0;
                }
                .gift-card {
                    page-break-after: avoid;
                }
            }
        </style>
    </head>
    <body>
        <div class="gift-card">
            <div class="top-image">
                <img src="<?php echo $background_image; ?>" alt="NewSaiige Gift Card">
            </div>
            
            <div class="content-wrapper">
                <div class="left-section">
                    <div class="tagline">
                        NewSaiige c'est comme les chips, quand on commence on en devient ACCRO !<br>
                        <strong>#NWSAIIGE</strong>
                    </div>
                </div>
                
                <div class="right-section">
                <div class="field">
                    <span class="field-label">Pour :</span>
                    <div class="field-value"><?php echo $recipient_name; ?></div>
                </div>
                
                <div class="field message">
                    <span class="field-label">Un Petit Mot d'❤️ :</span>
                    <div class="field-value"><?php echo $message; ?></div>
                </div>
                
                <div class="field">
                    <span class="field-label">Bon pour un Soin :</span>
                    <div class="field-value large"><?php echo $amount; ?> EUR</div>
                </div>
                
                <div class="field">
                    <span class="field-label">De la part de :</span>
                    <div class="field-value"><?php echo $buyer_name; ?></div>
                </div>
                
                <div class="field">
                    <span class="field-label">N° Bon Cadeau :</span>
                    <div class="field-value code"><?php echo $code; ?></div>
                </div>
                
                <div class="field">
                    <span class="field-label">Valable jusqu'au :</span>
                    <div class="field-value"><?php echo $expiry_date; ?></div>
                </div>
            </div>
            </div>
            
            <div class="footer">
                <div><strong>NEWSAIIGE</strong> - 175 av. Frédéric Mistral - 83150 La Garde</div>
                <div>Réservation sur Planity - 06 64 77 97 33</div>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

?>
