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

    // Department actions
    'department_created' => 'Département Créé',
    'department_updated' => 'Département Mis à Jour',
    'department_deleted' => 'Département Supprimé',

    // Service actions
    'service_created' => 'Service Créé',
    'service_updated' => 'Service Mis à Jour',
    'service_deleted' => 'Service Supprimé',
    'service_force_deleted' => 'Service Supprimé Définitivement',

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

    // Leave type actions
    'leave_type_created' => 'Type de Congé Créé',
    'leave_type_updated' => 'Type de Congé Mis à Jour',
    'leave_type_deleted' => 'Type de Congé Supprimé',
    'leave_type_imported' => 'Type de Congé Importé',
    'leave_type_exported' => 'Type de Congé Exporté',

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

    // Action Perform Messages

    // Login/Logout
    'login_successful' => 'Connexion réussie depuis l\'IP :ip',
    'logout_successful' => 'Déconnexion réussie depuis l\'IP :ip',
    'login_contract_expired' => 'Tentative de connexion depuis l\'IP :ip mais le contrat a expiré !',
    'login_account_banned' => 'Tentative de connexion depuis l\'IP :ip mais le compte est banni !',

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
];
