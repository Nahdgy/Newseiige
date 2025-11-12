// JavaScript pour le système de fidélité Newsaiige
(function($) {
    'use strict';
    
    // Objet principal du système de fidélité
    const NewsaiigeLoyalty = {
        
        // Initialisation
        init: function() {
            this.bindEvents();
            this.initAnimations();
            this.loadUserStats();
            this.initTooltips();
        },
        
        // Liaison des événements
        bindEvents: function() {
            // Conversion de points
            $('#conversionForm').on('submit', this.handlePointsConversion.bind(this));
            
            // Copie des codes de bons d'achat
            $(document).on('click', '.copy-btn', this.copyVoucherCode);
            
            // Actualisation des données
            $('.refresh-loyalty-data').on('click', this.refreshData.bind(this));
            
            // Fermeture des notifications
            $(document).on('click', '.loyalty-notification .close-btn', this.closeNotification);
            
            // Gestion des modales
            $(document).on('click', '.loyalty-modal-trigger', this.openModal);
            $(document).on('click', '.loyalty-modal-close, .loyalty-modal-overlay', this.closeModal);
            
            // Calculateur de conversion en temps réel
            $('#points_to_convert').on('input', this.updateConversionPreview);
        },
        
        // Initialisation des animations
        initAnimations: function() {
            // Animer les barres de progression
            $('.tier-progress-bar').each(function() {
                const $bar = $(this);
                const targetWidth = $bar.data('width') || $bar.css('width');
                $bar.css('width', '0').animate({ width: targetWidth }, 1000);
            });
            
            // Animer l'apparition des cartes
            $('.loyalty-card').each(function(index) {
                $(this).css('opacity', '0').delay(index * 100).animate({ opacity: 1 }, 500);
            });
            
            // Effet de compteur pour les nombres
            $('.card-value').each(function() {
                const $this = $(this);
                const targetNumber = parseInt($this.text().replace(/[^0-9]/g, ''));
                
                if (targetNumber > 0) {
                    $this.text('0');
                    $({ counter: 0 }).animate({ counter: targetNumber }, {
                        duration: 1500,
                        easing: 'swing',
                        step: function() {
                            $this.text(Math.ceil(this.counter).toLocaleString());
                        }
                    });
                }
            });
        },
        
        // Chargement des statistiques utilisateur
        loadUserStats: function() {
            if (typeof newsaiige_loyalty_ajax === 'undefined') return;
            
            $.ajax({
                url: newsaiige_loyalty_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'loyalty_get_user_stats',
                    nonce: newsaiige_loyalty_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        NewsaiigeLoyalty.updateUserInterface(response.data);
                    }
                },
                error: function() {
                    console.log('Erreur lors du chargement des statistiques de fidélité');
                }
            });
        },
        
        // Mise à jour de l'interface utilisateur
        updateUserInterface: function(data) {
            // Mettre à jour les points disponibles
            $('.points-available-value').text(data.points_available.toLocaleString());
            $('.points-total-value').text(data.points_lifetime.toLocaleString());
            
            // Mettre à jour le palier actuel
            if (data.current_tier) {
                $('.current-tier-name').text(data.current_tier.tier_name);
                $('.current-tier-benefits').text(data.current_tier.benefits);
            }
            
            // Mettre à jour la barre de progression vers le prochain palier
            if (data.next_tier) {
                const progress = (data.points_lifetime / data.next_tier.points_required) * 100;
                $('.tier-progress-bar').css('width', Math.min(100, progress) + '%');
                $('.next-tier-name').text(data.next_tier.tier_name);
                $('.points-needed').text(data.next_tier.points_required - data.points_lifetime);
            }
            
            // Afficher l'état de l'abonnement
            if (data.has_subscription) {
                $('.subscription-status').addClass('active').text('Abonnement actif');
            } else {
                $('.subscription-status').removeClass('active').text('Pas d\'abonnement actif');
            }
        },
        
        // Gestion de la conversion de points
        handlePointsConversion: function(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const $submitBtn = $form.find('.convert-btn');
            const pointsToConvert = parseInt($('#points_to_convert').val());
            
            // Validation côté client
            if (pointsToConvert < 50) {
                this.showNotification('Minimum 50 points requis pour la conversion', 'error');
                return;
            }
            
            // Désactiver le bouton
            $submitBtn.prop('disabled', true).text('Conversion en cours...');
            
            // Requête AJAX
            $.ajax({
                url: newsaiige_loyalty_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'loyalty_convert_points',
                    nonce: newsaiige_loyalty_ajax.nonce,
                    points_to_convert: pointsToConvert
                },
                success: function(response) {
                    if (response.success) {
                        NewsaiigeLoyalty.showNotification('Bon d\'achat créé avec succès !', 'success');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        NewsaiigeLoyalty.showNotification(response.data || 'Erreur lors de la conversion', 'error');
                    }
                },
                error: function() {
                    NewsaiigeLoyalty.showNotification('Erreur de connexion', 'error');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text('Convertir');
                }
            });
        },
        
        // Copie des codes de bons d'achat
        copyVoucherCode: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const code = $btn.data('code') || $btn.closest('.voucher-item').find('.voucher-code').text();
            
            // Utiliser l'API Clipboard moderne si disponible
            if (navigator.clipboard) {
                navigator.clipboard.writeText(code).then(function() {
                    NewsaiigeLoyalty.showCopySuccess($btn, code);
                }).catch(function() {
                    NewsaiigeLoyalty.fallbackCopy(code, $btn);
                });
            } else {
                NewsaiigeLoyalty.fallbackCopy(code, $btn);
            }
        },
        
        // Méthode de copie de secours
        fallbackCopy: function(text, $btn) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            
            try {
                document.execCommand('copy');
                this.showCopySuccess($btn, text);
            } catch (err) {
                this.showNotification('Impossible de copier le code', 'error');
            }
            
            document.body.removeChild(textArea);
        },
        
        // Affichage du succès de copie
        showCopySuccess: function($btn, code) {
            const originalText = $btn.text();
            $btn.text('Copié !').css('background', '#28a745');
            
            setTimeout(() => {
                $btn.text(originalText).css('background', '');
            }, 2000);
            
            this.showNotification(`Code copié : ${code}`, 'success');
        },
        
        // Mise à jour de l'aperçu de conversion
        updateConversionPreview: function() {
            const points = parseInt($(this).val()) || 0;
            const euroValue = points * 0.02; // 0.02€ par point
            $('#voucher_preview').val(euroValue.toFixed(2) + '€');
            
            // Validation visuelle
            if (points >= 50) {
                $(this).removeClass('error').addClass('valid');
            } else {
                $(this).removeClass('valid').addClass('error');
            }
        },
        
        // Actualisation des données
        refreshData: function(e) {
            e.preventDefault();
            
            const $btn = $(e.target);
            $btn.addClass('loading').prop('disabled', true);
            
            this.loadUserStats();
            
            setTimeout(() => {
                $btn.removeClass('loading').prop('disabled', false);
                this.showNotification('Données actualisées', 'info');
            }, 1000);
        },
        
        // Affichage des notifications
        showNotification: function(message, type = 'info') {
            const $notification = $(`
                <div class="loyalty-notification ${type}">
                    <span>${message}</span>
                    <button class="close-btn">&times;</button>
                </div>
            `);
            
            $('body').prepend($notification);
            
            // Auto-suppression après 5 secondes
            setTimeout(() => {
                $notification.fadeOut(() => $notification.remove());
            }, 5000);
        },
        
        // Fermeture des notifications
        closeNotification: function() {
            $(this).closest('.loyalty-notification').fadeOut(function() {
                $(this).remove();
            });
        },
        
        // Ouverture des modales
        openModal: function(e) {
            e.preventDefault();
            
            const target = $(this).data('target');
            const $modal = $(target);
            
            if ($modal.length) {
                $modal.fadeIn();
                $('body').addClass('modal-open');
            }
        },
        
        // Fermeture des modales
        closeModal: function(e) {
            if ($(e.target).hasClass('loyalty-modal-overlay') || $(e.target).hasClass('loyalty-modal-close')) {
                $(this).closest('.loyalty-modal-overlay').fadeOut();
                $('body').removeClass('modal-open');
            }
        },
        
        // Initialisation des tooltips
        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                $(this).addClass('loyalty-tooltip');
            });
        },
        
        // Fonction utilitaire pour formater les nombres
        formatNumber: function(num) {
            return num.toLocaleString();
        },
        
        // Fonction utilitaire pour formater la monnaie
        formatCurrency: function(amount) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR'
            }).format(amount);
        }
    };
    
    // Utilitaires pour les animations
    const LoyaltyAnimations = {
        
        // Animation de secousse pour les erreurs
        shake: function($element) {
            $element.addClass('shake');
            setTimeout(() => $element.removeClass('shake'), 500);
        },
        
        // Animation de pulsation pour attirer l'attention
        pulse: function($element) {
            $element.addClass('pulse');
            setTimeout(() => $element.removeClass('pulse'), 1000);
        },
        
        // Effet de brillance pour les succès
        glow: function($element) {
            $element.addClass('glow');
            setTimeout(() => $element.removeClass('glow'), 2000);
        }
    };
    
    // Extensions jQuery personnalisées
    $.fn.loyaltyNotification = function(message, type = 'info') {
        NewsaiigeLoyalty.showNotification(message, type);
        return this;
    };
    
    $.fn.loyaltyShake = function() {
        LoyaltyAnimations.shake(this);
        return this;
    };
    
    $.fn.loyaltyPulse = function() {
        LoyaltyAnimations.pulse(this);
        return this;
    };
    
    $.fn.loyaltyGlow = function() {
        LoyaltyAnimations.glow(this);
        return this;
    };
    
    // Styles CSS pour les animations JavaScript
    const animationStyles = `
        <style>
            .shake { animation: shake 0.5s; }
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
            
            .pulse { animation: pulse 1s; }
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
            
            .glow { animation: glow 2s; }
            @keyframes glow {
                0%, 100% { box-shadow: 0 0 0 rgba(130, 137, 127, 0); }
                50% { box-shadow: 0 0 20px rgba(130, 137, 127, 0.5); }
            }
            
            .loading { pointer-events: none; opacity: 0.6; }
            
            .form-input.valid { border-color: #28a745; }
            .form-input.error { border-color: #dc3545; }
            
            .modal-open { overflow: hidden; }
        </style>
    `;
    
    // Injection des styles
    $('head').append(animationStyles);
    
    // Initialisation au chargement du DOM
    $(document).ready(function() {
        NewsaiigeLoyalty.init();
        
        // Debug mode
        if (window.location.hash === '#loyalty-debug') {
            window.NewsaiigeLoyalty = NewsaiigeLoyalty;
            window.LoyaltyAnimations = LoyaltyAnimations;
            console.log('Mode debug activé pour le système de fidélité');
        }
    });
    
    // Gestion des erreurs globales
    $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
        if (settings.url && settings.url.indexOf('loyalty') !== -1) {
            console.error('Erreur AJAX dans le système de fidélité:', thrownError);
            NewsaiigeLoyalty.showNotification('Une erreur est survenue', 'error');
        }
    });
    
})(jQuery);