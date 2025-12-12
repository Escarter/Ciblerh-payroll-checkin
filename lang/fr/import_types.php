<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Import Types Language Lines
    |--------------------------------------------------------------------------
    */

    'permission_denied' => 'Vous n\'avez pas la permission d\'effectuer cet import.',
    'file_not_found' => 'Fichier d\'import introuvable.',
    'auto_create_departments_services' => 'Créer automatiquement les départements et services manquants',
    'auto_create_missing_entities' => 'Créer automatiquement les entités manquantes',
    'department_override_description' => 'Remplacer l\'affectation de département du fichier d\'import. Laisser vide pour utiliser le département du fichier.',
    'service_override_description' => 'Remplacer l\'affectation de service du fichier d\'import. Laisser vide pour utiliser le service du fichier.',

    // Import type descriptions
    'employees_description' => 'Importer les données des employés incluant les informations personnelles, les détails d\'emploi et les affectations.',
    'departments_description' => 'Importer les informations des départements et la structure organisationnelle.',
    'companies_description' => 'Importer les informations des entreprises et les détails de base.',
    'services_description' => 'Importer les définitions de services et les affectations de département.',
    'leave_types_description' => 'Importer les définitions des types de congé et les politiques.',
    'supervisors_managers_description' => 'Importer les comptes utilisateurs des superviseurs et gestionnaires avec leurs rôles et permissions.',
];