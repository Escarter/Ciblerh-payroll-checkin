<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Le following language lines contain Le default error messages used by
    | Le validator class. Some of these rules have multiple versions such
    | as Le size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'Le champ :attribute doit être accepté.',
    'active_url' => 'Le champ :attribute n\'est pas une URL valide.',
    'after' => 'Le champ :attribute doit être une date après :date.',
    'after_or_equal' => 'Le champ :attribute doit être une date après ou égal à :date.',
    'alpha' => 'Le champ :attribute ne peut contenir que des lettres.',
    'alpha_dash' => 'Le champ :attribute ne peut contenir que des lettres, nombres, tirets et soulignés.',
    'alpha_num' => 'Le champ :attribute ne peut contenir que des lettres et nombres.',
    'array' => 'Le champ :attribute doit être un array.',
    'before' => 'Le champ :attribute doit être une date avant :date.',
    'before_or_equal' => 'Le champ :attribute doit être une date avant ou égal à :date.',
    'between' => [
        'numeric' => 'Le champ :attribute doit être entre :min et :max.',
        'file' => 'Le champ :attribute doit être entre :min et :max kilobytes.',
        'string' => 'Le champ :attribute doit être entre :min et :max characters.',
        'array' => 'Le champ :attribute doit avoir entre :min et :max articles.',
    ],
    'boolean' => 'Le champ :attribute le champ doit être vrai ou faux.',
    'confirmed' => 'Le champ :attribute la confirmation ne correspond pas.',
    'date' => 'Le champ :attribute ce n\'est pas une date valide.',
    'date_equals' => 'Le champ :attribute doit être une date égale à :date.',
    'date_format' => 'Le champ :attribute ne correspond pas au format :format.',
    'different' => 'Le champ :attribute et :other doit être différent.',
    'digits' => 'Le champ :attribute doit être :digits chiffres.',
    'digits_between' => 'Le champ :attribute doit être entre :min et :max chiffres.',
    'dimensions' => 'Le champ :attribute a des dimensions d\'image non valides.',
    'distinct' => 'Le champ :attribute le champ a une valeur en double.',
    'email' => 'Le champ :attribute doit être une adresse email valide.',
    'ends_with' => 'Le champ :attribute doit se terminer par un des following: :values',
    'exists' => 'Le choisie :attribute est invalide.',
    'file' => 'Le champ :attribute doit être a file.',
    'filled' => 'Le champ :attribute le champ doit avoir une valeur.',
    'gt' => [
        'numeric' => 'Le champ :attribute doit être plus grand que :value.',
        'file' => 'Le champ :attribute doit être plus grand que :value kilobytes.',
        'string' => 'Le champ :attribute doit être plus grand que :value characters.',
        'array' => 'Le champ :attribute doit avoir plus de :value items.',
    ],
    'gte' => [
        'numeric' => 'Le champ :attribute doit être plus grand que ou égal :value.',
        'file' => 'Le champ :attribute doit être plus grand que ou égal :value kilobytes.',
        'string' => 'Le champ :attribute doit être plus grand que ou égal :value characters.',
        'array' => 'Le champ :attribute doit avoir :value articles ou plus.',
    ],
    'image' => 'Le champ :attribute doit être une image.',
    'in' => 'Le selected :attribute est invalide.',
    'in_array' => 'Le champ :attribute fle champ n\'existe pas dans :other.',
    'integer' => 'Le champ :attribute doit être un nombre entier.',
    'ip' => 'Le champ :attribute doit être une adresse IP valide.',
    'ipv4' => 'Le champ :attribute doit être une adresse IPv4 valide.',
    'ipv6' => 'Le champ :attribute doit être une adresse IPv6 valide.',
    'json' => 'Le champ :attribute doit être une chaîne JSON valide.',
    'lt' => [
        'numeric' => 'Le champ :attribute doit être moins que :value.',
        'file' => 'Le champ :attribute doit être moins que :value kilobytes.',
        'string' => 'Le champ :attribute doit être moins que :value characters.',
        'array' => 'Le champ :attribute doit avoir moins que :value items.',
    ],
    'lte' => [
        'numeric' => 'Le champ :attribute doit être moins que ou égal :value.',
        'file' => 'Le champ :attribute doit être moins que ou égal :value kilobytes.',
        'string' => 'Le champ :attribute doit être moins que ou égal :value characters.',
        'array' => 'Le champ :attribute ne doit pas avoir plus de :value items.',
    ],
    'max' => [
        'numeric' => 'Le champ :attribute n\'est peut être pas plus grand que :max.',
        'file' => 'Le champ :attribute n\'est peut être pas plus grand que :max kilobytes.',
        'string' => 'Le champ :attribute n\'est peut être pas plus grand que :max characters.',
        'array' => 'Le champ :attribute peut ne pas avoir plus de :max items.',
    ],
    'mimes' => 'Le champ :attribute doit être un dossier de type: :values.',
    'mimetypes' => 'Le champ :attribute doit être un dossier de type: :values.',
    'min' => [
        'numeric' => 'Le champ :attribute doit être au moins :min.',
        'file' => 'Le champ :attribute doit être au moins :min kilobytes.',
        'string' => 'Le champ :attribute doit être au moins :min characters.',
        'array' => 'Le champ :attribute doit avoir au moins :min items.',
    ],
    'not_in' => 'Le selected :attribute est invalide.',
    'not_regex' => 'Le champ :attribute format est invalide.',
    'numeric' => 'Le champ :attribute doit être un numéro.',
    'present' => 'Le champ :attribute field doit être présent.',
    'regex' => 'Le champ :attribute format est invalide.',
    'required' => 'Le champ :attribute requis .',
    'required_if' => 'Le champ :attribute requis quand :other est :value.',
    'required_unless' => 'Le champ :attribute requis unless :other est dans :values.',
    'required_with' => 'Le champ :attribute requis quand :values est présent.',
    'required_with_all' => 'Le champ :attribute requis quand :values sont présent.',
    'required_without' => 'Le champ :attribute requis quand :values n\'est pas présent.',
    'required_without_all' => 'Le champ :attribute requis quand aucun de :values sont présent.',
    'same' => 'Le champ :attribute et :other must match.',
    'size' => [
        'numeric' => 'Le champ :attribute doit être :size.',
        'file' => 'Le champ :attribute doit être :size kilobytes.',
        'string' => 'Le champ :attribute doit être :size characters.',
        'array' => 'Le champ :attribute doit contenir :size items.',
    ],
    'starts_with' => 'Le champ :attribute doit commencer par l\'un des following: :values',
    'string' => 'Le champ :attribute doit être un string.',
    'timezone' => 'Le champ :attribute doit être une zone valide.',
    'unique' => 'Le :attribute a déjà été pris.',
    'uploaded' => 'Le champ :attribute échec du téléchargement.',
    'url' => 'Le format du champ :attribute est invalide.',
    'uuid' => 'Le champ :attribute doit être un UUID valide.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name Le lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        // 'start_time' => [
        //     'required' => 'Le Champ "Heure de début" requis',
        // ],
        // 'end_time' => [
        //     'required' => 'Le Champ "Heure de fin" requis',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | Le following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'name' => 'nom',
        'start_time' => 'heure de début',
        'end_time' => 'heure de fin',
        'start_day' => 'jour de début',
        'end_day' => 'jour de fin',
        'absence_date' => 'date absence',
        'absence_reason' => 'motif d\'absence',
        'amount' => 'montant',
        'reason' => 'raison',
        'repayment_from_month' => 'remboursement_à_partir_du_mois',
        'repayment_to_month' => 'rembourse_au_mois',
        'beneficiary_name' => 'Nom du bénéficiaire',
        'beneficiary_mobile_money_number' => 'Numéro de mobile money du bénéficiaire',
        'beneficiary_id_card_number' => 'Numéro de carte d\'identité du bénéficiaire',
        'first_name' => 'prénom',
        'last_name' => 'nom de famille',
        'phone_number' => 'numéro de téléphone',
        'net_salary' => 'salaire net',
        'position' => 'poste',
        'salary_grade' => 'échelon salarial',
        'service_id' => 'service',
        'work_start_time' => 'heure_début_travail',
        'work_end_time' => 'heure de fin de travail',
        'current_password'=> 'mot de passe actuel',
        'password'=> 'mot de passe',
        'password_confirmation'=> 'Confirmation mot de passe',
        'approval_status' => 'statut validation',
        'approval_reason' => 'raison validation',
        'supervisor_approval_status' => 'statut_de_validation_superviseur',
        'supervisor_approval_reason' => 'raison_de_validation_superviseur',
        'manager_approval_status' => 'statut_de_validation_du_gestionnaire',
        'manager_approval_reason' => 'raison_de_validation_du_gestionnaire',
        'sector' => 'secteur',
        'description' => 'la description',
        'supervisor_id' => 'superviseur',
        'department_id' => 'département',
        'company_id' => 'entreprise',
        'department_file' => 'fichier_département',
        'company_file' => 'fichier_entreprise',
        'employee_file' => 'fichier_employé',
        'service_file' => 'fichier_service',
        'period' => 'période',
        'selectedDepartmentId' => 'département',
        'selectedCompanyId' => 'entreprise',
    ],

];
