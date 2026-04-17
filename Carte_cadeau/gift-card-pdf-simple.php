<?php
/**
 * NewSaiige Gift Card HTML Generator
 * Génère des cartes cadeaux HTML élégantes et imprimables
 * Les destinataires peuvent ouvrir le HTML et l'imprimer en PDF depuis leur navigateur
 */

// Empêcher l'accès direct (sauf pour les tests)
if (!defined('ABSPATH') && !defined('NEWSAIIGE_TESTING')) {
    exit;
}

/**
 * Générer une carte cadeau HTML personnalisée
 * Le destinataire peut ouvrir le HTML dans un navigateur et l'imprimer en PDF
 * 
 * @param object $gift_card Données de la carte cadeau
 * @return string|false Chemin du fichier HTML généré
 */
function newsaiige_generate_gift_card_pdf_simple($gift_card) {
    try {
        // Générer le HTML de la carte cadeau
        return newsaiige_generate_pdf_as_html($gift_card);
        
    } catch (Exception $e) {
        error_log("newsaiige_generate_gift_card_pdf_simple: ERREUR - " . $e->getMessage());
        return false;
    }
}

/**
 * Generer la piece jointe la plus compatible pour l'email.
 * Priorite: ZIP contenant le HTML (moins susceptible d'etre bloque) puis fallback HTML.
 *
 * @param object $gift_card Donnees de la carte cadeau
 * @return string|false Chemin du fichier a joindre
 */
function newsaiige_generate_gift_card_email_attachment($gift_card) {
    $html_path = newsaiige_generate_gift_card_pdf_simple($gift_card);

    if (empty($html_path) || !file_exists($html_path)) {
        error_log('newsaiige_generate_gift_card_email_attachment: HTML introuvable pour la carte ' . ($gift_card->code ?? 'inconnue'));
        return false;
    }

    $zip_path = dirname($html_path) . '/gift-card-' . $gift_card->code . '.zip';

    // Methode 1: extension ZipArchive si disponible
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFile($html_path, basename($html_path));
            $zip->close();

            if (file_exists($zip_path) && filesize($zip_path) > 0) {
                error_log('newsaiige_generate_gift_card_email_attachment: ZIP genere via ZipArchive - ' . $zip_path);
                return $zip_path;
            }
        }
    }

    // Methode 2: fallback PclZip (lib incluse WordPress)
    if (defined('ABSPATH')) {
        $pclzip_file = ABSPATH . 'wp-admin/includes/class-pclzip.php';
        if (file_exists($pclzip_file)) {
            require_once $pclzip_file;

            if (class_exists('PclZip')) {
                $archive = new PclZip($zip_path);
                $result = $archive->create($html_path, PCLZIP_OPT_REMOVE_PATH, dirname($html_path));

                if ($result !== 0 && file_exists($zip_path) && filesize($zip_path) > 0) {
                    error_log('newsaiige_generate_gift_card_email_attachment: ZIP genere via PclZip - ' . $zip_path);
                    return $zip_path;
                }
            }
        }
    }

    // Fallback final: retourner le HTML brut
    error_log('newsaiige_generate_gift_card_email_attachment: ZIP indisponible, fallback HTML - ' . $html_path);
    return $html_path;
}



/**
 * Générer un fichier HTML de carte cadeau
 * Le destinataire peut l'ouvrir dans un navigateur et l'imprimer en PDF (Ctrl+P)
 */
function newsaiige_generate_pdf_as_html($gift_card) {
    try {
        $html = newsaiige_get_gift_card_html($gift_card);
        
        // Définir le répertoire de sortie
        $upload_dir = wp_upload_dir();
        $gift_cards_dir = $upload_dir['basedir'] . '/gift-cards/';
        
        if (!file_exists($gift_cards_dir)) {
            wp_mkdir_p($gift_cards_dir);
        }
        
        $filename = 'gift-card-' . $gift_card->code . '.html';
        $filepath = $gift_cards_dir . $filename;
        
        file_put_contents($filepath, $html);
        
        error_log("newsaiige_generate_gift_card_html: Carte cadeau HTML générée - $filepath");
        
        return $filepath;
        
    } catch (Exception $e) {
        error_log("newsaiige_generate_gift_card_html: ERREUR - " . $e->getMessage());
        return false;
    }
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
    
    // Chemin de l'image de fond
    $plugin_url = plugins_url('', __FILE__);
    $background_image = $plugin_url . '/assets/gift-card-background.png';
    
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
