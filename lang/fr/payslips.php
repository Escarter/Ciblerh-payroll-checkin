<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payslips Management Language Lines
    |--------------------------------------------------------------------------
    */

    'payslips' => 'Fiches de paie',
    'payslip' => 'Fiche de paie',
    'select_department' => 'Sélectionner un département',
    'total_payslips' => 'Total des fiches de paie',
    'payslip_process' => 'Processus de fiche de paie',
    'payslip_processes' => 'Processus de fiches de paie',
    'total_processes' => 'Total des processus',
    'payslips_management' => 'Gestion des fiches de paie',
    'send_payslips' => 'Envoyer les fiches de paie',
    'select_payslip' => 'Sélectionner la fiche de paie',
    'select_payslip_file' => 'Sélectionner le fichier de fiche de paie',
    'select_payslip_file_placeholder' => 'Sélectionner le fichier de fiche de paie',
    'start_processing' => 'Commencer le traitement',
    'select_company' => '--Sélectionner une société--',
    'manage_payslips_processes' => 'Créer nouveau, mettre à jour et supprimer tout groupe sur la plateforme',

    // Payslip actions
    'download_payslip' => 'Télécharger la fiche de paie',
    'resend_payslip' => 'Renvoyer la fiche de paie',
    'view_details_download_payslips' => 'Voir les détails et télécharger les fiches de paie',
    'resend_email' => 'Renvoyer l\'email',
    'resend_sms' => 'Renvoyer le SMS',
    'payslip_not_found' => 'Fiche de paie introuvable.',
    'error_deleting_payslip' => 'Erreur lors de la suppression de la fiche de paie: ',

    // Process management
    'delete_payslip_process' => 'Supprimer le processus de fiche de paie pour ',
    'payslip_process_moved_to_trash' => 'Processus de fiche de paie déplacé vers la corbeille avec succès!',
    'payslip_process_restored' => 'Processus de fiche de paie restauré avec succès!',
    'permanently_delete_payslip_process' => 'Supprimer définitivement le processus de fiche de paie pour ',
    'payslip_process_permanently_deleted' => 'Processus de fiche de paie supprimé définitivement!',
    'bulk_delete_payslip_process' => 'Suppression en masse du processus de fiche de paie pour ',
    'selected_payslip_processes_moved_to_trash' => 'Processus de fiches de paie sélectionnés déplacés vers la corbeille avec succès!',
    'selected_payslip_processes_restored' => 'Processus de fiches de paie sélectionnés restaurés avec succès!',
    'bulk_permanently_delete_payslip_process' => 'Suppression définitive en masse du processus de fiche de paie pour ',
    'selected_payslip_processes_permanently_deleted' => 'Processus de fiches de paie sélectionnés supprimés définitivement!',

    // Bulk operations
    'employee_payslip_resent_successfully' => 'Fiche de paie de l\'employé renvoyée avec succès',
    'email_resent_successfully' => 'Email renvoyé avec succès!',
    'failed_to_resent_email' => 'Échec de renvoi de l\'email',
    'sms_sent_successfully' => 'SMS envoyé à :user avec succès!',
    'insufficient_sms_balance' => 'Solde SMS insuffisant',

    // Process status
    'successful' => 'Réussi',
    'failed' => 'Échoué',
    'pending' => 'En attente',
    'processing' => 'Traitement en cours...',
    'disabled' => 'Désactivé',

    // Process results
    'process_completed_with_failures' => 'Processus terminé avec :failed fiches de paie sur :total qui n\'ont pas pu être envoyées',
    'unmatched_employees' => ':unmatched employés sur :total n\'ont pas pu être associés aux fichiers PDF',
    'unmatched_employees_title' => 'Employés non associés',
    'unmatched_employees_description' => 'Employés dont les fiches de paie n\'ont pas pu être trouvées dans les fichiers PDF',
    'no_unmatched_employees_found' => 'Aucun employé non associé trouvé',
    'view_unmatched_employees' => 'Voir les employés non associés',

    // Settings and requirements
    'smtp_setting_required' => 'Configuration SMTP requise!!',
    'sms_setting_required' => 'Configuration SMS requise!',
    'sms_smtp_settings_required' => 'Configurations SMS et SMTP requises!!',
    'insufficient_sms_balance_refill' => 'Le solde SMS n\'est pas suffisant, rechargez le SMS pour continuer',
    'file_upload_max_pages' => 'Le fichier téléversé doit avoir :max pages maximum',
    'file_upload_page_limit' => 'Le fichier téléversé doit avoir ',
    'file_upload_page_limit_suffix' => ' pages maximum',
    'job_processing_status' => 'Tâche démarrée pour traiter la liste et vérifier le fichier téléversé sur la table!',

    // Encryption and sending
    'encryption_failed_email_sms_skipped' => 'Email/SMS ignoré: Échec du cryptage. ',
    'failed_to_send_email_recipient' => 'Échec d\'envoi d\'email. Destinataire: :email',
    'email_error' => 'Erreur email: :error',
    'email_rfc_error' => 'Erreur RFC email: :error',
    'no_valid_email_address' => 'Aucune adresse email valide pour l\'utilisateur',
    'no_errors' => 'Aucune erreur',
    'failure_reason' => 'Raison de l\'échec',

    // Retry logic
    'failed_to_send_email_retry_scheduled' => 'Échec d\'envoi d\'email. Destinataire: :email. Nouvelle tentative :retry/:max programmée',
    'failed_to_send_email_after_max_retries' => 'Échec d\'envoi d\'email après :max tentatives. Destinataire: :email',
    'email_error_retry_scheduled' => 'Erreur email: :error. Nouvelle tentative :retry/:max programmée',
    'email_error_after_max_retries' => 'Erreur email après :max nouvelles tentatives: :error',
    'email_rfc_error_retry_scheduled' => 'Erreur RFC email: :error. Nouvelle tentative :retry/:max programmée',
    'email_rfc_error_after_max_retries' => 'Erreur RFC email après :max nouvelles tentatives: :error',
    'retry_failed_payslip_not_found' => 'Nouvelle tentative échouée: Fichier de fiche de paie introuvable',
    'retry_failed_no_valid_email' => 'Nouvelle tentative échouée: Aucune adresse email valide',
    'retry_attempt_failed_email_delivery' => 'Tentative de nouvelle tentative échouée: Échec de livraison email',
    'retry_attempt_failed_with_next' => 'Tentative :retry échouée: Échec de livraison email. Nouvelle tentative :next/:max programmée',
    'retry_attempt_failed_after_max' => 'Tentative échouée après :max nouvelles tentatives: Échec de livraison email',
    'retry_error' => 'Erreur de nouvelle tentative: :error',
    'retry_rfc_error' => 'Erreur RFC de nouvelle tentative: :error',
    'email_automatic_retry_scheduled' => 'Échec d\'envoi d\'email. Nouvelle tentative automatique programmée.',
    'email_automatic_retry_scheduled_if_enabled' => 'Échec d\'envoi d\'email. Nouvelle tentative automatique programmée si activée.',
    'email_notifications_disabled' => 'Notifications email désactivées pour cet employé',

    // Email bounce handling
    'email_previously_bounced' => 'Email précédemment rejeté: :reason',
    'email_bounced_update_address' => 'L\'adresse email a été rejetée précédemment. Veuillez mettre à jour l\'adresse email de l\'employé.',
    'email_bounced' => 'Email rejeté: :reason',
    'email_invalid_or_does_not_exist' => 'L\'adresse email est invalide ou n\'existe pas',

    // Bulk resend
    'resend_all_failed' => 'Renvoyer tous les échoués',
    'confirm_resend_all_failed' => 'Êtes-vous sûr de vouloir renvoyer toutes les fiches de paie échouées?',
    'resend_all_failed_count' => 'Cela tentera de renvoyer :count fiches de paie échouées.',
    'bulk_resend_completed' => 'Renvoi en masse terminé: :resend réussis, :skipped ignorés',
    'no_failed_payslips_to_resend' => 'Aucune fiche de paie échouée à renvoyer.',

    // Raisons d'échec
    'no_valid_email_address' => 'Aucune adresse email valide pour l\'utilisateur',
    'failed_sending_email_sms' => 'Échec d\'envoi d\'email et SMS',
    'failed_to_resent_email' => 'Échec de renvoi d\'email',

    // Historique des fiches de paie employé spécifiques
    'payslip_file_not_found' => 'Fichier de fiche de paie introuvable. Veuillez contacter votre administrateur.',
    'unable_to_download_payslip' => 'Impossible de télécharger la fiche de paie. Veuillez contacter votre administrateur.',
    'payslip_successfully_moved_to_trash' => 'Fiche de paie déplacée vers la corbeille avec succès!',
    'payslip_successfully_restored' => 'Fiche de paie restaurée avec succès!',
    'payslip_permanently_deleted' => 'Fiche de paie supprimée définitivement!',
    'selected_payslips_moved_to_trash' => 'Fiches de paie sélectionnées déplacées vers la corbeille!',
    'selected_payslips_restored' => 'Fiches de paie sélectionnées restaurées!',
    'selected_payslips_permanently_deleted' => 'Fiches de paie sélectionnées supprimées définitivement!',
    'failed_to_send_email' => 'Échec d\'envoi d\'email',
    'failed_to_send_email_recipient_retry_scheduled' => 'Échec d\'envoi d\'email. Destinataire: :email. Nouvelle tentative automatique programmée',

    // Employee payslip history UI labels
    'for_this_employee' => 'pour cet employé',
    'that_are_active' => 'qui sont actives!',
    'that_are_deleted' => 'qui sont supprimées!',
    'payslip_history_for_employee' => 'Bulletins de salaire de :name',
    'view_all_employee_payslip_history' => 'Voir tout l\'historique des bulletins de salaire de :name',

    // Status labels
    'not_recorded' => 'Non enregistré',
    'pending_status' => 'En attente...',

    // Action titles
    'download_payslip_title' => 'Télécharger le bulletin de salaire',
    'resend_email_title' => 'Renvoyer l\'email',
    'resend_sms_title' => 'Renvoyer le SMS',
    'delete_title' => 'Supprimer',

    // Additional UI labels
    'all_payslips_processed' => 'Toutes les fiches de paie traitées',
    'payslips_processed' => 'Fiches de paie traitées',
    'view_all_payslips_processed' => 'Voir toutes les fiches de paie traitées',
    'resend_all_failed_payslips' => 'Renvoyer toutes les fiches de paie échouées',
    'are_you_sure_resend_failed_payslips' => 'Êtes-vous sûr de vouloir renvoyer toutes les fiches de paie échouées?',
    'process_details' => 'Détails du processus',
    'company_with_departments' => ' - avec :count départements',
    'departments' => 'départements',

    // Missing translation keys
    'move_selected_payslip_processes_to_trash' => 'Déplacer les processus de fiches de paie sélectionnés vers la corbeille',
    'department_deleted' => 'Département supprimé!',
    'start_processing_payslip_to_see_the_outcome_here' => 'Commencer le traitement des fiches de paie pour voir le résultat ici',
    'view_past_history' => 'Voir l\'historique passé',
    'unknown_error' => 'Erreur inconnue',
    'employees_whose_payslips_could_not_be_found_in_the_pdf_files' => 'Employés dont les fiches de paie n\'ont pas pu être trouvées dans les fichiers PDF',
    'phone' => 'Téléphone',
    'failure_reason' => 'Raison de l\'échec',
    'created_at' => 'Créé le',
    'employee_info' => 'Informations employé',
    'timeline' => 'Chronologie',
    'encryption_status' => 'Statut de cryptage',
    'email_status' => 'Statut email',
    'sms_status' => 'Statut SMS',
    'period' => 'Période',
    'created' => 'Créé',
    'not_recorded' => 'Non enregistré',

    // Additional missing keys
    'category' => 'Catégorie',
    'single' => 'Individuel',
    'bulk' => 'En masse',
    'permanently_delete_selected_payslips' => 'Supprimer définitivement les fiches de paie sélectionnées',
    'restore_selected_payslips' => 'Restaurer les fiches de paie sélectionnées',
    'move_selected_payslips_to_trash' => 'Déplacer les fiches de paie sélectionnées vers la corbeille',
];
