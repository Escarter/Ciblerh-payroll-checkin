<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Services Management Language Lines
    |--------------------------------------------------------------------------
    */

    'services' => 'Services',
    'service' => 'Service',
    'total_services' => 'Total des services',
    'create_service' => 'Créer un service',
    'edit_service' => 'Modifier le service',
    'service_created_successfully' => 'Service créé avec succès!',
    'service_updated_successfully' => 'Service mis à jour avec succès!',
    'service_deleted_successfully' => 'Service supprimé avec succès!',
    'service_restored_successfully' => 'Service restauré avec succès!',
    'service_permanently_deleted' => 'Service supprimé définitivement!',
    'selected_services_moved_to_trash' => 'Services sélectionnés déplacés vers la corbeille!',
    'selected_services_restored' => 'Services sélectionnés restaurés!',
    'selected_services_permanently_deleted' => 'Services sélectionnés supprimés définitivement!',

    // Additional Success Messages
    'service_successfully_moved_to_trash' => 'Service déplacé vers la corbeille avec succès!',
    'service_successfully_restored' => 'Service restauré avec succès!',
    'services_successfully_imported' => 'Services importés avec succès!',
    'imported_excel_file_for_services' => 'Fichier Excel importé pour les services du département ',
    'exported_excel_file_for_services' => 'Fichier Excel exporté pour les services du département ',

    // Error Messages
    'cannot_permanently_delete_service' => 'Impossible de supprimer définitivement le service. Il a des enregistrements de pointage liés.',

    // UI Labels
    'inactive' => 'Inactif',
    'inactive!' => 'inactifs!',
    'in_for_this_department' => 'dans ce département',
    'that_are_deleted' => 'qui sont supprimés!',

    // Table Headers
    'id' => 'ID',

    // Button Titles
    'edit_service' => 'Modifier le service',
    'restore_service' => 'Restaurer le service',
    'permanently_delete' => 'Supprimer définitivement',
    'add_service' => 'Ajouter un service',

    // Bulk Operations
    'restore_selected_services' => 'Restaurer les services sélectionnés',
    'permanently_delete_selected_services' => 'Supprimer définitivement les services sélectionnés',

    // Service fields
    'name' => 'Nom',
    'service_name' => 'Nom du service',
    'service_code' => 'Code service',
    'department' => 'Département',
    'description' => 'Description',
    'manager' => 'Manager',
    'supervisor' => 'Superviseur',
    'is_active' => 'Est actif?',
    'start_date' => 'Date de début',
    'end_date' => 'Date de fin',
    'budget' => 'Budget',
    'cost_center' => 'Centre de coût',

    // Status
    'active' => 'Actif',
    'inactive' => 'Inactif',

    // Messages
    'manage_services' => 'Gérer les services et leurs affectations',
    'all_services' => 'Tous les services',
    'select_service' => 'Sélectionner un service',
    'no_service_selected' => 'Aucun service sélectionné',
    'department_required_for_service_import' => 'Le département est requis pour l\'import de service',
    'department_must_belong_to_company' => 'Le département doit appartenir à une société pour l\'import de service',
    'department_context_required' => 'Le contexte du département est requis pour l\'import de service',
    'import_completed' => 'Importation terminée avec succès',

    // Permissions
    'cannot_permanently_delete_service' => 'Impossible de supprimer définitivement le service. Il a des enregistrements de pointage liés.',
    'cannot_permanently_delete_services' => 'Impossible de supprimer définitivement les services suivants car ils ont des enregistrements de pointage liés: ',
    'related_records_protection' => 'Si cet élément a des enregistrements liés, la suppression sera empêchée pour maintenir l\'intégrité des données.',
    'manage_services_for_department' => 'Gérer les services pour le',
    'create_new_service_to_manage' => 'Créer un nouveau service à gérer',
    'for_these_departments' => 'pour ces départements',
    'edit_service_details' => 'Modifier les détails du service',
    'in_for_this_department' => 'pour ce département',
    'for_these_departments' => 'pour ces départements',
    'new' => 'Nouveau',

    // Import validation messages
    'name_required' => 'Le nom du service est requis',
    'name_already_exists' => 'Le nom du service existe déjà dans ce département',
];
