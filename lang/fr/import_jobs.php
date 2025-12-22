<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Import Jobs Language Lines
    |--------------------------------------------------------------------------
    */

    'manage_import_jobs' => 'Gérer vos tâches d\'importation et suivre leur progression',
    'job_details' => 'Détails de la tâche d\'importation',
    'no_jobs_found' => 'Aucune tâche d\'importation trouvée',
    'no_jobs_message' => 'Vous n\'avez pas encore commencé de tâches d\'importation',
    'confirm_cancel_job' => 'Êtes-vous sûr de vouloir annuler cette tâche d\'importation ?',
    'confirm_bulk_cancel' => 'Êtes-vous sûr de vouloir annuler les tâches d\'importation sélectionnées ?',
    'job_cancelled_successfully' => 'Tâche d\'importation annulée avec succès',
    'unable_to_cancel_job' => 'Impossible d\'annuler la tâche d\'importation',
    'jobs_cancelled_successfully' => ':count tâche(s) d\'importation annulée(s) avec succès',
    'please_select_jobs_to_cancel' => 'Veuillez sélectionner les tâches d\'importation à annuler',
    'jobs_refreshed_successfully' => 'Tâches d\'importation actualisées avec succès',
    'job_cannot_be_cancelled' => 'Cette tâche d\'importation ne peut pas être annulée',
    'progress_statistics' => 'Statistiques de progression',
    'create_new_import' => 'Créer un nouvel import',
    'create_import_description' => 'Téléchargez un fichier et sélectionnez le type d\'import pour commencer l\'importation des données',
    'import_job_created_successfully' => 'Tâche d\'importation créée avec succès',
    'error_creating_import_job' => 'Erreur lors de la création de la tâche d\'importation',
    'start_import' => 'Démarrer l\'import',
    'import_configuration' => 'Configuration de l\'import',
    'download_template_description' => 'Téléchargez un fichier modèle pour vous assurer que vos données sont formatées correctement',

    'create_import_modal_title' => 'Créer un import',
    'create_import' => 'Créer un import',
    // Background import completion messages
    'background_import_completed' => 'Import en arrière-plan terminé avec succès. :count enregistrements importés.',
    'name' => 'Tâche d\'importation',

    // Notification messages
    'import_completed_subject' => 'Import :type terminé',
    'import_completed_greeting' => 'Bonjour :name,',
    'import_completed_message' => 'Votre import :type a été terminé. Total des enregistrements : :total, Réussi : :successful, Échec : :failed.',
    'import_completed_with_errors' => 'Certains enregistrements ont échoué à être importés. Veuillez vérifier les détails de l\'import pour plus d\'informations.',
    'import_completed_notification' => 'Import :type terminé : :successful sur :total enregistrements importés avec succès.',

    'import_failed_subject' => 'Import :type échoué',
    'import_failed_greeting' => 'Bonjour :name,',
    'import_failed_message' => 'Votre import :type a échoué avec l\'erreur suivante : :error',
    'import_failed_notification' => 'Import :type échoué. Veuillez vérifier les détails de l\'import.',

    // Import result messages
    'import_background_success' => 'Import en arrière-plan terminé avec succès pour :type. :count enregistrements importés.',
    'import_background_failed' => 'Import en arrière-plan échoué pour :type. Erreur : :error',
    'import_with_errors' => 'avec :errors erreurs',
    'import_partial_failures' => '(:failed enregistrements ont échoué)',
    'import_failed_detailed' => 'Import échoué : :error',

    // Toast notification messages
    'import_completed_title' => 'Import terminé',
    'import_failed_title' => 'Import échoué',
    'import_completed_toast' => 'Import :type terminé : :successful sur :total enregistrements importés avec succès.',
    'import_failed_toast' => 'Import :type échoué : :error',
    'import_with_errors_toast' => '(:errors enregistrements avaient des erreurs)',

    // Bulk actions
    'bulk_delete' => 'Supprimer la sélection',
    'bulk_restore' => 'Restaurer la sélection',
    'bulk_cancel' => 'Annuler la sélection',
    'bulk_retry' => 'Relancer la sélection',
    'confirm_bulk_delete' => 'Êtes-vous sûr de vouloir supprimer définitivement les tâches d\'importation sélectionnées ? Cette action ne peut pas être annulée.',
    'confirm_bulk_restore' => 'Êtes-vous sûr de vouloir restaurer les tâches d\'importation sélectionnées ?',
    'confirm_bulk_retry' => 'Êtes-vous sûr de vouloir relancer les tâches d\'importation échouées sélectionnées ?',
    'jobs_deleted_successfully' => ':count tâche(s) d\'importation supprimée(s) avec succès',
    'jobs_restored_successfully' => ':count tâche(s) d\'importation restaurée(s) avec succès',
    'jobs_retried_successfully' => ':count tâche(s) d\'importation relancée(s) avec succès',
    'please_select_jobs_to_delete' => 'Veuillez sélectionner les tâches d\'importation à supprimer',
    'please_select_jobs_to_restore' => 'Veuillez sélectionner les tâches d\'importation à restaurer',
    'please_select_jobs_to_retry' => 'Veuillez sélectionner les tâches d\'importation échouées à relancer',

    // Individual actions
    'delete_job' => 'Supprimer la tâche d\'importation',
    'restore_job' => 'Restaurer la tâche d\'importation',
    'retry_job' => 'Relancer la tâche d\'importation',
    'confirm_delete_job' => 'Êtes-vous sûr de vouloir supprimer définitivement cette tâche d\'importation ? Cette action ne peut pas être annulée.',
    'confirm_restore_job' => 'Êtes-vous sûr de vouloir restaurer cette tâche d\'importation ?',
    'confirm_retry_job' => 'Êtes-vous sûr de vouloir relancer cette tâche d\'importation échouée ?',
    'job_deleted_successfully' => 'Tâche d\'importation supprimée avec succès',
    'job_deleted_permanently' => 'Tâche d\'importation supprimée définitivement',
    'jobs_deleted_permanently' => ':count tâche(s) d\'importation supprimée(s) définitivement avec succès',
    'job_moved_to_trash_successfully' => 'Tâche d\'importation déplacée vers la corbeille avec succès',
    'jobs_moved_to_trash_successfully' => ':count tâche(s) d\'importation déplacée(s) vers la corbeille avec succès',
    'job_restored_successfully' => 'Tâche d\'importation restaurée avec succès',
    'job_retried_successfully' => 'Tâche d\'importation relancée avec succès',
    'error_retrying_job' => 'Erreur lors de la relance de la tâche d\'importation',
    'can_only_retry_failed_jobs' => 'Seules les tâches d\'importation échouées peuvent être relancées',
    'original_file_not_found' => 'Fichier original introuvable. Impossible de relancer la tâche d\'importation.',
    'job_not_found' => 'Tâche d\'importation introuvable',

    // Retry modal messages
    'retry_job_confirmation_title' => 'Relancer la tâche d\'importation',
    'restore_job_confirmation_title' => 'Restaurer la tâche d\'importation',
    'retry_job_confirmation_message' => 'Êtes-vous sûr de vouloir relancer cette tâche d\'importation échouée ? Cela créera une nouvelle tâche d\'importation avec les mêmes paramètres.',
    'bulk_retry_confirmation_title' => 'Relancer les tâches sélectionnées',
    'bulk_retry_confirmation_message' => 'Êtes-vous sûr de vouloir relancer :count tâche(s) d\'importation échouée(s) ? Cela créera de nouvelles tâches d\'importation avec les mêmes paramètres.',
    'bulk_retry_confirm' => 'Relancer les tâches',

    // UI elements
    'filters_and_search' => 'Filtres et recherche',
    'clear_all' => 'Tout effacer',
    'all_import_types' => 'Tous les types d\'import',
    'select_all' => 'Tout sélectionner',
    'deselect_all' => 'Tout désélectionner',
    'selected' => 'sélectionné(s)',
    'move_to_trash' => 'Déplacer vers la corbeille',
    'delete_forever' => 'Supprimer définitivement',
    'restore_selected' => 'Restaurer la sélection',
    'total_import_jobs' => 'Total des tâches d\'importation',
    'manage_import_jobs_details' => 'Créez, surveillez et gérez vos opérations d\'importation de données',

    // Étape d'aperçu
    'preview_data_step' => 'Aperçu des données d\'importation',
    'preview_data_description' => 'Examinez un échantillon de vos données avant de procéder à l\'importation.',

    // Messages d'aperçu
    'no_preview_data_available' => 'Aperçu non encore traité',
    'preview_data_unavailable_message' => 'Cliquez sur le bouton ci-dessous pour analyser votre fichier et générer un aperçu des données qui seront importées.',

    // Sous-titres des étapes
    'create_import_subtitle' => 'Configurez vos paramètres d\'importation et téléchargez votre fichier',
    'preview_subtitle' => 'Examinez et validez vos données avant l\'importation',
    'confirm_subtitle' => 'Révision finale et démarrage du processus d\'importation',

    // Étape de confirmation
    'confirm_import_step' => 'Confirmer l\'importation',
    'confirm_import_description' => 'Examinez vos paramètres d\'importation et démarrez le processus d\'importation.',

    // Éléments d'interface manquants
    'view_details' => 'Voir les détails',
    'cancel_job' => 'Annuler la tâche',
    'unknown_import_type' => 'Type d\'importation inconnu',
];