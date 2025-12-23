<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audit Logs Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for audit log action types.
    | These translations will be displayed in the audit logs interface.
    |
    */

    // User actions
    'user_created' => 'Utilisateur Créé',
    'user_updated' => 'Utilisateur Mis à Jour',
    'user_deleted' => 'Utilisateur Supprimé',
    'user_login' => 'Connexion Utilisateur',
    'user_logout' => 'Déconnexion Utilisateur',

    // Company actions
    'company_created' => 'Entreprise Créée',
    'company_updated' => 'Entreprise Mise à Jour',
    'company_deleted' => 'Entreprise Supprimée',
    'company_imported' => 'Entreprise Importée',
    'company_exported' => 'Entreprise Exportée',
    'companies_imported' => 'Entreprises Importées',
    'companies_import_failed' => 'Échec de l\'Importation des Entreprises',

    // Department actions
    'department_created' => 'Département Créé',
    'department_updated' => 'Département Mis à Jour',
    'department_deleted' => 'Département Supprimé',
    'departments_imported' => 'Départements Importés',
    'departments_import_failed' => 'Échec de l\'Importation des Départements',

    // Service actions
    'service_created' => 'Service Créé',
    'service_updated' => 'Service Mis à Jour',
    'service_deleted' => 'Service Supprimé',
    'service_force_deleted' => 'Service Supprimé Définitivement',
    'services_imported' => 'Services Importés',
    'services_import_failed' => 'Échec de l\'Importation des Services',

    // Advance Salary actions
    'advanceSalary_created' => 'Avance Salaire Créée',
    'advanceSalary_updated' => 'Avance Salaire Mise à Jour',
    'advanceSalary_deleted' => 'Avance Salaire Supprimée',
    'advanceSalary_approved' => 'Avance Salaire Approuvée',
    'advanceSalary_rejected' => 'Avance Salaire Rejetée',

    // Absence actions
    'absence_created' => 'Absence Créée',
    'absence_updated' => 'Absence Mise à Jour',
    'absence_deleted' => 'Absence Supprimée',
    'absence_approved' => 'Absence Approuvée',
    'absence_rejected' => 'Absence Rejetée',

    // Overtime actions
    'overtime_created' => 'Heures Supplémentaires Créées',
    'overtime_updated' => 'Heures Supplémentaires Mises à Jour',
    'overtime_deleted' => 'Heures Supplémentaires Supprimées',
    'overtime_approved' => 'Heures Supplémentaires Approuvées',
    'overtime_rejected' => 'Heures Supplémentaires Rejetées',
    'overtime_exported' => 'Heures Supplémentaires Exportées',

    // Check-in actions
    'checkin_created' => 'Pointage Créé',
    'checkin_updated' => 'Pointage Mis à Jour',
    'checkin_deleted' => 'Pointage Supprimé',
    'checkin_approved' => 'Pointage Approuvé',
    'checkin_rejected' => 'Pointage Rejeté',

    // Payslip actions
    'payslip_sending' => 'Bulletin de Paie en Envoi',
    'payslip_sent' => 'Bulletin de Paie Envoyé',
    'payslip_failed' => 'Bulletin de Paie Échoué',
    'delete_payslip_process' => 'Supprimer Processus Bulletin de Paie',
    'force_delete_payslip_process' => 'Supprimer Définitivement Processus Bulletin de Paie',
    'bulk_delete_payslip_process' => 'Suppression en Masse Processus Bulletin de Paie',
    'bulk_force_delete_payslip_process' => 'Suppression Définitivement en Masse Processus Bulletin de Paie',
    'cancel_payslip_process' => 'Annuler Processus Bulletin de Paie',
    'send_sms' => 'Envoyer SMS',
    'send_email' => 'Envoyer Email',
    'employee_exported' => 'Employé Exporté',
    'employees_exported' => 'Employés Exportés',
    'employees_imported' => 'Employés Importés',
    'employees_import_failed' => 'Échec de l\'Importation des Employés',
    'service_exported' => 'Service Exporté',
    'department_exported' => 'Département Exporté',

    // Leave type actions
    'leave_type_created' => 'Type de Congé Créé',
    'leave_type_updated' => 'Type de Congé Mis à Jour',
    'leave_type_deleted' => 'Type de Congé Supprimé',
    'leave_type_force_deleted' => 'Type de Congé Supprimé Définitivement',
    'leave_type_imported' => 'Type de Congé Importé',
    'leave_type_exported' => 'Type de Congé Exporté',
    'leave_types_imported' => 'Types de Congé Importés',
    'leave_types_import_failed' => 'Échec de l\'Importation des Types de Congé',
    
    // Leave actions
    'leave_created' => 'Congé Créé',
    'leave_updated' => 'Congé Mis à Jour',
    'leave_deleted' => 'Congé Supprimé',
    'leave_force_deleted' => 'Congé Supprimé Définitivement',
    'leave_approved' => 'Congé Approuvé',
    'leave_rejected' => 'Congé Rejeté',

    // Report actions
    'report_generated' => 'Rapport Généré',
    'report_exported' => 'Rapport Exporté',
    'payslip_report' => 'Rapport Bulletin de Paie Généré',

    // Email/SMS actions
    'email_sent' => 'Email Envoyé',
    'sms_sent' => 'SMS Envoyé',

    // Role actions
    'role_created' => 'Rôle Créé',
    'role_updated' => 'Rôle Mis à Jour',
    'role_deleted' => 'Rôle Supprimé',
    'role_force_deleted' => 'Rôle Supprimé Définitivement',

    // Action Perform Messages

    // Login/Logout
    'login_successful' => 'Connexion réussie depuis l\'IP :ip',
    'logout_successful' => 'Déconnexion réussie depuis l\'IP :ip',
    'login_contract_expired' => 'Tentative de connexion depuis l\'IP :ip mais le contrat a expiré !',
    'login_account_banned' => 'Tentative de connexion depuis l\'IP :ip mais le compte est banni !',

    // Messages des observateurs
    'created_absence' => 'Absence créée avec la date :date',
    'updated_absence' => 'Absence mise à jour pour :user avec la date :date',
    'deleted_absence' => 'Absence supprimée pour :user avec la date :date',
    'approved_absence' => 'Absence approuvée pour :user avec la date :date',
    'rejected_absence' => 'Absence rejetée pour :user avec la date :date',

    'created_overtime' => 'Enregistrement d\'heures supplémentaires créé pour la date :date',
    'updated_overtime' => 'Heures supplémentaires mises à jour pour :user avec la date :date',
    'deleted_overtime' => 'Enregistrement d\'heures supplémentaires supprimé pour :user pour la date :date',
    'approved_overtime' => 'Heures supplémentaires approuvées pour :user avec la date :date',
    'rejected_overtime' => 'Heures supplémentaires rejetées pour :user avec la date :date',

    'created_checkin' => 'Enregistrement de pointage créé pour :user pour la date :date',
    'updated_checkin' => 'Pointage mis à jour pour :user pour la date :date',
    'deleted_checkin' => 'Enregistrement de pointage supprimé pour :user pour la date :date',
    'approved_checkin_supervisor' => 'Superviseur a approuvé le pointage pour :user pour la date :date',
    'rejected_checkin_supervisor' => 'Superviseur a rejeté le pointage pour :user pour la date :date',
    'approved_checkin_manager' => 'Manager a approuvé le pointage pour :user pour la date :date',
    'rejected_checkin_manager' => 'Manager a rejeté le pointage pour :user pour la date :date',

    'created_advance_salary' => 'Avance sur salaire créée d\'un montant :amount',
    'updated_advance_salary' => 'Avance sur salaire mise à jour par :user d\'un montant :amount',
    'deleted_advance_salary' => 'Avance sur salaire supprimée par :user d\'un montant :amount',
    'approved_advance_salary' => 'Avance sur salaire approuvée par :user d\'un montant :amount',
    'rejected_advance_salary' => 'Avance sur salaire rejetée par :user d\'un montant :amount',

    'updated_service' => 'Service mis à jour avec le nom :name',
    'deleted_service' => 'Service supprimé avec le nom :name',
    'permanently_deleted_service' => 'Service supprimé définitivement avec le nom :name',

    // CRUD Operations
    'created_entity' => ':entity créé avec le nom :name',
    'updated_entity' => ':entity mis à jour avec le nom :name',
    'deleted_entity' => ':entity supprimé avec le nom :name',
    'force_deleted_entity' => ':entity supprimé définitivement avec le nom :name',

    // Import/Export Operations
    'imported_entities' => 'Fichier Excel importé pour :entities',
    'imported_entities_for_company' => 'Fichier Excel importé pour :entities de l\'entreprise :company',
    'imported_entities_for_department' => 'Fichier Excel importé pour :entities du département :department',
    'exported_entities' => 'Fichier Excel exporté pour :entities',
    'exported_entities_for_company' => 'Fichier Excel exporté pour :entities de l\'entreprise :company',
    'exported_entities_for_department' => 'Fichier Excel exporté pour :entities du département :department',

    // Payslip Operations
    'payslip_process_deleted' => 'Processus bulletin de paie supprimé :month-:year @ :time',
    'payslip_process_bulk_deleted' => 'Suppression en masse des processus bulletins de paie',
    'payslip_process_bulk_force_deleted' => 'Suppression définitive en masse des processus bulletins de paie',
    'payslip_report_generated' => 'Rapport bulletin de paie généré',
    'bulk_delete_payslip_process_for' => 'Suppression en masse du processus bulletin de paie pour :month-:year @ :time',
    'bulk_permanently_delete_payslip_process_for' => 'Suppression définitive en masse du processus bulletin de paie pour :month-:year @ :time',
    'cancel_payslip_process_for' => 'Processus bulletin de paie annulé pour :month-:year @ :time',
    'payslip_sending_initiated' => 'L\'utilisateur :user a initié l\'envoi du bulletin de paie au département :department pour le mois de :month-:year :history_link',
    'send_email_to_employee' => 'L\'utilisateur :user a envoyé un email à :employee',
    'send_sms_to_employee' => 'L\'utilisateur :user a envoyé un SMS à :employee',
    'exported_overtime' => 'Fichier Excel exporté pour les heures supplémentaires',
    'exported_employees_for_company' => 'Fichier Excel exporté pour les employés de l\'entreprise :company',
    'exported_services_for_department' => 'Fichier Excel exporté pour les services du département :department',
    'exported_departments_for_company' => 'Fichier Excel exporté pour les départements de l\'entreprise :company',
    'report_generated_for_payslips' => ':user a généré un rapport pour les bulletins de paie',

    // Approval Operations
    'advance_salary_approved' => 'Avance salaire approuvée pour :user d\'un montant :amount',
    'advance_salary_rejected' => 'Avance salaire rejetée pour :user d\'un montant :amount',
    'advance_salary_updated' => 'Avance salaire mise à jour pour :user d\'un montant :amount',
    'advance_salary_deleted_amount' => 'Avance salaire supprimée pour :user d\'un montant :amount',

    'absence_approved' => 'Demande d\'absence approuvée',
    'absence_rejected' => 'Demande d\'absence rejetée',

    'overtime_approved' => 'Demande d\'heures supplémentaires approuvée',
    'overtime_rejected' => 'Demande d\'heures supplémentaires rejetée',

    'checkin_approved' => 'Demande de pointage approuvée',
    'checkin_rejected' => 'Demande de pointage rejetée',

    // Opérations en Masse (approbations déjà ci-dessus)
    'bulk_deleted_absences' => ':count absence(s) déplacée(s) en masse vers la corbeille',
    'bulk_restored_absences' => ':count absence(s) restaurée(s) en masse',
    'bulk_force_deleted_absences' => ':count absence(s) supprimée(s) définitivement en masse',

    // Opérations en Masse
    'bulk_approved_absences' => ':count absence(s) approuvée(s) en masse',
    'bulk_rejected_absences' => ':count absence(s) rejetée(s) en masse',
    'bulk_approved_overtimes' => ':count heure(s) supplémentaire(s) approuvée(s) en masse',
    'bulk_rejected_overtimes' => ':count heure(s) supplémentaire(s) rejetée(s) en masse',
    'bulk_deleted_overtimes' => ':count heure(s) supplémentaire(s) déplacée(s) en masse vers la corbeille',
    'bulk_restored_overtimes' => ':count heure(s) supplémentaire(s) restaurée(s) en masse',
    'bulk_force_deleted_overtimes' => ':count heure(s) supplémentaire(s) supprimée(s) définitivement en masse',
    'bulk_approved_leaves' => ':count congé(s) approuvé(s) en masse',
    'bulk_rejected_leaves' => ':count congé(s) rejeté(s) en masse',
    'bulk_deleted_leaves' => ':count congé(s) déplacé(s) en masse vers la corbeille',
    'bulk_restored_leaves' => ':count congé(s) restauré(s) en masse',
    'bulk_force_deleted_leaves' => ':count congé(s) supprimé(s) définitivement en masse',
    'bulk_approved_advance_salaries' => ':count avance(s) sur salaire approuvée(s) en masse',
    'bulk_rejected_advance_salaries' => ':count avance(s) sur salaire rejetée(s) en masse',
    'bulk_deleted_advance_salaries' => ':count avance(s) sur salaire déplacée(s) en masse vers la corbeille',
    'bulk_restored_advance_salaries' => ':count avance(s) sur salaire restaurée(s) en masse',
    'bulk_force_deleted_advance_salaries' => ':count avance(s) sur salaire supprimée(s) définitivement en masse',
    'bulk_deleted_employees' => ':count employé(s) déplacé(s) en masse vers la corbeille',
    'bulk_restored_employees' => ':count employé(s) restauré(s) en masse',
    'bulk_force_deleted_employees' => ':count employé(s) supprimé(s) définitivement en masse',
    'bulk_deleted_companies' => ':count entreprise(s) déplacée(s) en masse vers la corbeille',
    'bulk_restored_companies' => ':count entreprise(s) restaurée(s) en masse',
    'bulk_force_deleted_companies' => ':count entreprise(s) supprimée(s) définitivement en masse',
    'bulk_deleted_departments' => ':count département(s) déplacé(s) en masse vers la corbeille',
    'bulk_restored_departments' => ':count département(s) restauré(s) en masse',
    'bulk_force_deleted_departments' => ':count département(s) supprimé(s) définitivement en masse',
    'bulk_deleted_services' => ':count service(s) déplacé(s) en masse vers la corbeille',
    'bulk_restored_services' => ':count service(s) restauré(s) en masse',
    'bulk_force_deleted_services' => ':count service(s) supprimé(s) définitivement en masse',
    'bulk_deleted_roles' => ':count rôle(s) déplacé(s) en masse vers la corbeille',
    'bulk_restored_roles' => ':count rôle(s) restauré(s) en masse',
    'bulk_force_deleted_roles' => ':count rôle(s) supprimé(s) définitivement en masse',
    'bulk_deleted_leave_types' => ':count type(s) de congé déplacé(s) en masse vers la corbeille',
    'bulk_restored_leave_types' => ':count type(s) de congé restauré(s) en masse',
    'bulk_force_deleted_leave_types' => ':count type(s) de congé supprimé(s) définitivement en masse',
    'bulk_deleted_download_jobs' => ':count travail(s) de téléchargement déplacé(s) en masse vers la corbeille',
    'bulk_restored_download_jobs' => ':count travail(s) de téléchargement restauré(s) en masse',
    'bulk_force_deleted_download_jobs' => ':count travail(s) de téléchargement supprimé(s) définitivement en masse',
    'bulk_deleted_import_jobs' => ':count travail(s) d\'importation déplacé(s) en masse vers la corbeille',
    'bulk_restored_import_jobs' => ':count travail(s) d\'importation restauré(s) en masse',
    'bulk_force_deleted_import_jobs' => ':count travail(s) d\'importation supprimé(s) définitivement en masse',
    'bulk_deleted_payslip_processes' => ':count processus de bulletin de paie déplacé(s) en masse vers la corbeille',
    'bulk_restored_payslip_processes' => ':count processus de bulletin de paie restauré(s) en masse',
    'bulk_force_deleted_payslip_processes' => ':count processus de bulletin de paie supprimé(s) définitivement en masse',
    'bulk_deleted_payslips' => ':count bulletin(s) de paie déplacé(s) en masse vers la corbeille',
    'bulk_restored_payslips' => ':count bulletin(s) de paie restauré(s) en masse',
    'bulk_force_deleted_payslips' => ':count bulletin(s) de paie supprimé(s) définitivement en masse',
    'bulk_created_checklogs' => ':count pointage(s) créé(s) en masse',
    'bulk_approved_checklogs' => ':count pointage(s) approuvé(s) en masse',
    'bulk_rejected_checklogs' => ':count pointage(s) rejeté(s) en masse',
    'bulk_deleted_checklogs' => ':count pointage(s) déplacé(s) en masse vers la corbeille',
    'bulk_restored_checklogs' => ':count pointage(s) restauré(s) en masse',
    'bulk_force_deleted_checklogs' => ':count pointage(s) supprimé(s) définitivement en masse',
    'bulk_created_overtimes' => ':count heure(s) supplémentaire(s) créée(s) en masse',

    // Audit Log Permissions
    'view_own_logs_only' => 'Voir uniquement ses propres journaux',
    'read_all' => 'Lire Tout',
    'read_own_only' => 'Lire Uniquement les Siens',
    'audit_log' => 'Journal d\'Audit',
    
    // Messages de gestion des journaux d'audit
    'audit_log_not_found' => 'Journal d\'audit introuvable',
    'audit_log_moved_to_trash' => 'Journal d\'audit déplacé vers la corbeille avec succès',
    'audit_log_permanently_deleted' => 'Journal d\'audit supprimé définitivement',
    'audit_log_restored' => 'Journal d\'audit restauré avec succès',
    'selected_audit_logs_moved_to_trash' => 'Journaux d\'audit sélectionnés déplacés vers la corbeille avec succès',
    'selected_audit_logs_restored' => 'Journaux d\'audit sélectionnés restaurés avec succès !',
    'selected_audit_logs_permanently_deleted' => 'Journaux d\'audit sélectionnés supprimés définitivement !',
    'danger_deleting_audit_log' => 'Erreur lors de la suppression du journal d\'audit : ',

    // Action Filter Options
    'action_created' => 'Créé',
    'action_updated' => 'Mis à Jour',
    'action_deleted' => 'Supprimé',
    'action_login' => 'Connexion',
    'action_logout' => 'Déconnexion',
    'action_exported' => 'Exporté',
    'action_imported' => 'Importé',

    // Detail Modal
    'log_details' => 'Détails du Journal',
    'basic_information' => 'Informations de Base',
    'timestamp_info' => 'Informations de Date et Heure',
    'system' => 'Système',
    'model_information' => 'Informations sur le Modèle',
    'model_type' => 'Type de Modèle',
    'model_id' => 'ID du Modèle',
    'model_name' => 'Nom du Modèle',
    'changes' => 'Modifications',
    'field_changes' => 'modifications de champs',
    'field' => 'Champ',
    'old_value' => 'Ancienne Valeur',
    'new_value' => 'Nouvelle Valeur',
    'metadata' => 'Métadonnées',
    'ip_address' => 'Adresse IP',
    'url' => 'URL',
    'method' => 'Méthode HTTP',
    'n_a' => 'N/A',
    'logs_list' => 'Liste des Journaux',
    'total_logs_lowercase' => 'journaux au total',
    'no_logs_found' => 'Aucun journal trouvé',
    'try_adjusting_filters' => 'Essayez d\'ajuster vos filtres',
    'description' => 'Description',
    
    // Table Headers
    'user' => 'Utilisateur',
    'action' => 'Action',
    'model' => 'Modèle',
    'date' => 'Date',
    'actions' => 'Actions',
    
    // Title and Description
    'title' => 'Journaux d\'Audit',
    'description_page' => 'Voir et gérer tous les journaux d\'activité du système',
];
