// JavaScript pour l'administration du système de fidélité
(function($) {
    'use strict';
    
    // Objet principal de l'administration
    const LoyaltyAdmin = {
        
        // Initialisation
        init: function() {
            this.bindEvents();
            this.initCharts();
            this.initFormValidation();
            this.loadDashboardData();
        },
        
        // Liaison des événements
        bindEvents: function() {
            // Gestion des formulaires de paliers
            $('.loyalty-tier-form').on('submit', this.handleTierForm.bind(this));
            
            // Gestion des actions en lot
            $('#loyalty-bulk-action-form').on('submit', this.handleBulkActions.bind(this));
            
            // Filtres et recherche
            $('.loyalty-filter-input').on('input', this.debounce(this.handleFilters.bind(this), 300));
            $('.loyalty-filter-select').on('change', this.handleFilters.bind(this));
            
            // Modales d'administration
            $('.loyalty-modal-trigger').on('click', this.openModal.bind(this));
            $(document).on('click', '.loyalty-admin-modal-close', this.closeModal);
            $(document).on('click', '.loyalty-admin-modal', function(e) {
                if (e.target === this) {
                    LoyaltyAdmin.closeModal.call(this);
                }
            });
            
            // Actions rapides
            $('.loyalty-quick-action').on('click', this.handleQuickAction.bind(this));
            
            // Actualisation des données
            $('.refresh-dashboard').on('click', this.refreshDashboard.bind(this));
            
            // Export de données
            $('.export-data').on('click', this.handleExport.bind(this));
        },
        
        // Initialisation des graphiques
        initCharts: function() {
            // Graphiques en barres simples
            $('.loyalty-chart-bar-fill').each(function() {
                const $bar = $(this);
                const targetWidth = $bar.data('percentage') || 0;
                
                $bar.css('width', '0%').animate({
                    width: targetWidth + '%'
                }, 1000, 'easeOutQuart');
            });
            
            // Animation des compteurs
            $('.stat-value').each(function() {
                const $this = $(this);
                const targetValue = parseInt($this.text().replace(/[^\d]/g, '')) || 0;
                
                if (targetValue > 0) {
                    $this.text('0');
                    $({ counter: 0 }).animate({ counter: targetValue }, {
                        duration: 2000,
                        easing: 'swing',
                        step: function() {
                            $this.text(Math.ceil(this.counter).toLocaleString());
                        }
                    });
                }
            });
        },
        
        // Validation des formulaires
        initFormValidation: function() {
            // Validation en temps réel
            $('input[type="number"]').on('input', function() {
                const $field = $(this);
                const min = parseInt($field.attr('min')) || 0;
                const max = parseInt($field.attr('max')) || Infinity;
                const value = parseInt($field.val()) || 0;
                
                if (value < min || value > max) {
                    $field.addClass('loyalty-field-error').removeClass('loyalty-field-valid');
                } else {
                    $field.addClass('loyalty-field-valid').removeClass('loyalty-field-error');
                }
            });
            
            // Validation des champs de texte
            $('input[type="text"][required]').on('blur', function() {
                const $field = $(this);
                
                if ($field.val().trim().length === 0) {
                    $field.addClass('loyalty-field-error').removeClass('loyalty-field-valid');
                } else {
                    $field.addClass('loyalty-field-valid').removeClass('loyalty-field-error');
                }
            });
        },
        
        // Gestion des formulaires de paliers
        handleTierForm: function(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const $submitBtn = $form.find('input[type="submit"]');
            const formData = new FormData($form[0]);
            
            // Validation côté client
            if (!this.validateTierForm($form)) {
                return;
            }
            
            // Désactiver le bouton
            $submitBtn.prop('disabled', true).val('Traitement...');
            
            // Soumission AJAX
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    LoyaltyAdmin.showNotification('Palier sauvegardé avec succès', 'success');
                    setTimeout(() => location.reload(), 1500);
                },
                error: function() {
                    LoyaltyAdmin.showNotification('Erreur lors de la sauvegarde', 'error');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).val('Sauvegarder');
                }
            });
        },
        
        // Validation du formulaire de palier
        validateTierForm: function($form) {
            let isValid = true;
            
            // Vérifier les champs requis
            $form.find('[required]').each(function() {
                const $field = $(this);
                
                if ($field.val().trim().length === 0) {
                    $field.addClass('loyalty-field-error');
                    isValid = false;
                } else {
                    $field.removeClass('loyalty-field-error');
                }
            });
            
            // Vérifier que les points requis sont positifs
            const pointsRequired = parseInt($form.find('[name="points_required"]').val()) || 0;
            if (pointsRequired < 0) {
                $form.find('[name="points_required"]').addClass('loyalty-field-error');
                isValid = false;
            }
            
            if (!isValid) {
                this.showNotification('Veuillez corriger les erreurs dans le formulaire', 'error');
            }
            
            return isValid;
        },
        
        // Gestion des actions en lot
        handleBulkActions: function(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const action = $form.find('[name="bulk_action"]').val();
            const selectedItems = $form.find('input[name="selected_items[]"]:checked');
            
            if (!action) {
                this.showNotification('Veuillez sélectionner une action', 'warning');
                return;
            }
            
            if (selectedItems.length === 0) {
                this.showNotification('Veuillez sélectionner au moins un élément', 'warning');
                return;
            }
            
            const confirmMessage = `Êtes-vous sûr de vouloir appliquer l'action "${action}" à ${selectedItems.length} élément(s) ?`;
            
            if (confirm(confirmMessage)) {
                this.executeBulkAction(action, selectedItems);
            }
        },
        
        // Exécution des actions en lot
        executeBulkAction: function(action, selectedItems) {
            const itemIds = selectedItems.map(function() {
                return $(this).val();
            }).get();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'loyalty_bulk_action',
                    bulk_action: action,
                    item_ids: itemIds,
                    nonce: $('#loyalty_admin_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        LoyaltyAdmin.showNotification(`Action "${action}" appliquée avec succès`, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        LoyaltyAdmin.showNotification(response.data || 'Erreur lors de l\'action', 'error');
                    }
                },
                error: function() {
                    LoyaltyAdmin.showNotification('Erreur de connexion', 'error');
                }
            });
        },
        
        // Gestion des filtres
        handleFilters: function() {
            const filters = {};
            
            $('.loyalty-filter-input, .loyalty-filter-select').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                const value = $field.val();
                
                if (value && value.trim() !== '') {
                    filters[name] = value;
                }
            });
            
            this.applyFilters(filters);
        },
        
        // Application des filtres
        applyFilters: function(filters) {
            // Construire l'URL avec les paramètres de filtre
            const currentUrl = new URL(window.location.href);
            
            // Supprimer les anciens filtres
            for (const key of currentUrl.searchParams.keys()) {
                if (key.startsWith('filter_')) {
                    currentUrl.searchParams.delete(key);
                }
            }
            
            // Ajouter les nouveaux filtres
            for (const [key, value] of Object.entries(filters)) {
                currentUrl.searchParams.set('filter_' + key, value);
            }
            
            // Rediriger avec les nouveaux filtres
            window.location.href = currentUrl.toString();
        },
        
        // Ouverture des modales
        openModal: function(e) {
            e.preventDefault();
            
            const $trigger = $(e.target);
            const targetModal = $trigger.data('target');
            const $modal = $(targetModal);
            
            if ($modal.length) {
                $modal.fadeIn();
                $('body').addClass('modal-open');
                
                // Charger le contenu si nécessaire
                const contentUrl = $trigger.data('content-url');
                if (contentUrl) {
                    this.loadModalContent($modal, contentUrl);
                }
            }
        },
        
        // Chargement du contenu de modale
        loadModalContent: function($modal, url) {
            const $content = $modal.find('.loyalty-admin-modal-content');
            
            $content.html('<div class="loyalty-admin-loading"></div>');
            
            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    $content.html(response);
                },
                error: function() {
                    $content.html('<p>Erreur lors du chargement du contenu</p>');
                }
            });
        },
        
        // Fermeture des modales
        closeModal: function() {
            $('.loyalty-admin-modal').fadeOut();
            $('body').removeClass('modal-open');
        },
        
        // Actions rapides
        handleQuickAction: function(e) {
            e.preventDefault();
            
            const $btn = $(e.target);
            const action = $btn.data('action');
            const itemId = $btn.data('item-id');
            const confirmMessage = $btn.data('confirm');
            
            if (confirmMessage && !confirm(confirmMessage)) {
                return;
            }
            
            $btn.prop('disabled', true).html('<span class="loyalty-admin-loading"></span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'loyalty_quick_action',
                    quick_action: action,
                    item_id: itemId,
                    nonce: $('#loyalty_admin_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        LoyaltyAdmin.showNotification('Action effectuée avec succès', 'success');
                        
                        // Actions spécifiques selon le type
                        if (action === 'delete') {
                            $btn.closest('tr').fadeOut();
                        } else {
                            setTimeout(() => location.reload(), 1000);
                        }
                    } else {
                        LoyaltyAdmin.showNotification(response.data || 'Erreur lors de l\'action', 'error');
                    }
                },
                error: function() {
                    LoyaltyAdmin.showNotification('Erreur de connexion', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).html($btn.data('original-text') || 'Action');
                }
            });
        },
        
        // Chargement des données du tableau de bord
        loadDashboardData: function() {
            if ($('.loyalty-admin-dashboard').length === 0) return;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'loyalty_dashboard_data',
                    nonce: $('#loyalty_admin_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        LoyaltyAdmin.updateDashboard(response.data);
                    }
                }
            });
        },
        
        // Mise à jour du tableau de bord
        updateDashboard: function(data) {
            // Mettre à jour les statistiques
            if (data.stats) {
                for (const [key, value] of Object.entries(data.stats)) {
                    $(`.stat-value[data-stat="${key}"]`).text(value.toLocaleString());
                }
            }
            
            // Mettre à jour les graphiques
            if (data.charts) {
                this.updateCharts(data.charts);
            }
        },
        
        // Mise à jour des graphiques
        updateCharts: function(chartData) {
            for (const [chartId, data] of Object.entries(chartData)) {
                const $chart = $(`#${chartId}`);
                
                if ($chart.length && data.length > 0) {
                    data.forEach((item, index) => {
                        const $bar = $chart.find('.loyalty-chart-bar-fill').eq(index);
                        $bar.css('width', item.percentage + '%');
                        
                        const $value = $chart.find('.loyalty-chart-value').eq(index);
                        $value.text(item.value);
                    });
                }
            }
        },
        
        // Actualisation du tableau de bord
        refreshDashboard: function(e) {
            e.preventDefault();
            
            const $btn = $(e.target);
            $btn.addClass('loyalty-admin-loading').prop('disabled', true);
            
            this.loadDashboardData();
            
            setTimeout(() => {
                $btn.removeClass('loyalty-admin-loading').prop('disabled', false);
                this.showNotification('Tableau de bord actualisé', 'info');
            }, 1500);
        },
        
        // Export de données
        handleExport: function(e) {
            e.preventDefault();
            
            const $btn = $(e.target);
            const exportType = $btn.data('export');
            const format = $btn.data('format') || 'csv';
            
            const exportUrl = new URL(window.location.href);
            exportUrl.searchParams.set('action', 'loyalty_export');
            exportUrl.searchParams.set('type', exportType);
            exportUrl.searchParams.set('format', format);
            exportUrl.searchParams.set('nonce', $('#loyalty_admin_nonce').val());
            
            // Ouvrir dans une nouvelle fenêtre pour le téléchargement
            window.open(exportUrl.toString(), '_blank');
            
            this.showNotification('Export en cours...', 'info');
        },
        
        // Affichage des notifications
        showNotification: function(message, type = 'info') {
            const $notification = $(`
                <div class="loyalty-admin-alert ${type}">
                    ${message}
                    <button class="notice-dismiss" type="button">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);
            
            $('.wrap h1').after($notification);
            
            // Auto-suppression après 5 secondes
            setTimeout(() => {
                $notification.fadeOut(() => $notification.remove());
            }, 5000);
            
            // Suppression manuelle
            $notification.find('.notice-dismiss').on('click', function() {
                $notification.fadeOut(() => $notification.remove());
            });
        },
        
        // Fonction utilitaire de debounce
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        // Formatage des nombres
        formatNumber: function(num) {
            return new Intl.NumberFormat('fr-FR').format(num);
        },
        
        // Formatage de la monnaie
        formatCurrency: function(amount) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR'
            }).format(amount);
        }
    };
    
    // Utilitaires pour les tableaux
    const TableUtils = {
        
        // Tri des colonnes
        initSortable: function() {
            $('.admin-table th[data-sortable]').on('click', function() {
                const $th = $(this);
                const column = $th.data('sortable');
                const currentSort = $th.data('sort') || 'asc';
                const newSort = currentSort === 'asc' ? 'desc' : 'asc';
                
                // Mettre à jour les indicateurs visuels
                $('.admin-table th').removeClass('sorted-asc sorted-desc');
                $th.addClass('sorted-' + newSort).data('sort', newSort);
                
                // Rediriger avec le nouveau tri
                const url = new URL(window.location.href);
                url.searchParams.set('orderby', column);
                url.searchParams.set('order', newSort);
                
                window.location.href = url.toString();
            });
        },
        
        // Sélection de toutes les lignes
        initSelectAll: function() {
            $('#select-all').on('change', function() {
                const isChecked = $(this).is(':checked');
                $('input[name="selected_items[]"]').prop('checked', isChecked);
                
                TableUtils.updateBulkActions();
            });
            
            $('input[name="selected_items[]"]').on('change', function() {
                TableUtils.updateBulkActions();
                
                // Mettre à jour l'état de "Tout sélectionner"
                const totalItems = $('input[name="selected_items[]"]').length;
                const checkedItems = $('input[name="selected_items[]"]:checked').length;
                
                $('#select-all').prop('checked', totalItems === checkedItems);
            });
        },
        
        // Mise à jour des actions en lot
        updateBulkActions: function() {
            const selectedCount = $('input[name="selected_items[]"]:checked').length;
            
            if (selectedCount > 0) {
                $('.loyalty-bulk-actions').show();
                $('.selected-count').text(selectedCount);
            } else {
                $('.loyalty-bulk-actions').hide();
            }
        }
    };
    
    // Initialisation au chargement du DOM
    $(document).ready(function() {
        LoyaltyAdmin.init();
        TableUtils.initSortable();
        TableUtils.initSelectAll();
        
        // Mode debug pour l'administration
        if (window.location.hash === '#admin-debug') {
            window.LoyaltyAdmin = LoyaltyAdmin;
            window.TableUtils = TableUtils;
            console.log('Mode debug admin activé');
        }
    });
    
})(jQuery);