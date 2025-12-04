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

    // Service fields
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

    // Permissions
    'cannot_permanently_delete_service' => 'Impossible de supprimer définitivement le service. Il a des enregistrements de pointage liés.',
    'cannot_permanently_delete_services' => 'Impossible de supprimer définitivement les services suivants car ils ont des enregistrements de pointage liés: ',
    'related_records_protection' => 'Si cet élément a des enregistrements liés, la suppression sera empêchée pour maintenir l\'intégrité des données.',
    'manage_services_for_department' => 'Gérer les services pour le',
    'create_new_service_to_manage' => 'Créer un nouveau service à gérer',
    'edit_service_details' => 'Modifier les détails du service',
    'in_for_this_department' => 'pour ce département',
    'new' => 'Nouveau',
];
