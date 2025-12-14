<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Settings Management Language Lines
    |--------------------------------------------------------------------------
    */

    'settings' => 'Paramètres',
    'system_settings' => 'Paramètres système',
    'application_settings' => 'Paramètres de l\'application',
    'general_settings' => 'Paramètres généraux',
    'email_settings' => 'Paramètres email',
    'sms_settings' => 'Paramètres SMS',
    'notification_settings' => 'Paramètres de notification',
    'security_settings' => 'Paramètres de sécurité',
    'integration_settings' => 'Paramètres d\'intégration',

    // Email Settings
    'email_provider' => 'Fournisseur d\'email',
    'smtp_configuration' => 'Configuration SMTP',
    'smtp' => 'SMTP',
    'mailgun' => 'Mailgun',
    'ses' => 'Amazon SES',
    'postmark' => 'Postmark',
    'sendmail' => 'Sendmail',
    'mailpit' => 'Mailpit',
    'log' => 'Journal',
    'array' => 'Tableau',

    // SMTP Settings
    'smtp_host' => 'Hôte SMTP',
    'smtp_port' => 'Port SMTP',
    'smtp_username' => 'Nom d\'utilisateur SMTP',
    'smtp_password' => 'Mot de passe SMTP',
    'smtp_encryption' => 'Chiffrement SMTP',

    // Email Settings
    'from_email' => 'Email expéditeur',
    'from_name' => 'Nom expéditeur',
    'reply_to_email' => 'Email de réponse',
    'reply_to_name' => 'Nom de réponse',

    'test_email' => 'Email de test',
    'send_test_email' => 'Envoyer un email de test',
    'setting_for_smtp_successfully_added' => 'Paramètres email enregistrés avec succès!',
    'test_email_sent_successfully' => 'Email de test envoyé avec succès!',
    'setting_for_smtp_required' => 'Les paramètres email sont requis pour envoyer des emails de test',

    // Provider-specific information
    'smtp_limitations' => 'Limitations SMTP',
    'smtp_webhook_note' => 'SMTP ne prend pas en charge les webhooks pour la confirmation de livraison. Le statut de livraison des emails sera suivi uniquement via la gestion des rebonds.',
    'transactional_webhook_note' => 'Ce fournisseur prend en charge les webhooks pour la confirmation de livraison en temps réel.',
    'development_driver_note' => 'Ceci est un pilote de développement/test et ne doit pas être utilisé en production.',

    // SMS Settings
    'sms_provider' => 'Fournisseur SMS',
    'sms_api_key' => 'Clé API SMS',
    'sms_api_secret' => 'Secret API SMS',
    'sms_sender_id' => 'ID expéditeur SMS',
    'sms_balance' => 'Solde SMS',
    'test_sms' => 'SMS de test',
    'send_test_sms' => 'Envoyer un SMS de test',
    'sms_settings_saved' => 'Paramètres SMS enregistrés avec succès!',
    'sms_test_sent' => 'SMS de test envoyé avec succès!',

    // Company Settings
    'company_name' => 'Nom de la société',
    'company_logo' => 'Logo de la société',
    'company_address' => 'Adresse de la société',
    'company_phone' => 'Téléphone de la société',
    'company_email' => 'Email de la société',
    'company_website' => 'Site web de la société',
    'fiscal_year_start' => 'Début de l\'année fiscale',
    'timezone' => 'Fuseau horaire',
    'currency' => 'Devise',
    'language' => 'Langue',
    'date_format' => 'Format de date',
    'time_format' => 'Format d\'heure',

    // Security Settings
    'password_policy' => 'Politique de mot de passe',
    'minimum_password_length' => 'Longueur minimale du mot de passe',
    'password_requires_uppercase' => 'Exiger des lettres majuscules',
    'password_requires_lowercase' => 'Exiger des lettres minuscules',
    'password_requires_numbers' => 'Exiger des chiffres',
    'password_requires_symbols' => 'Exiger des symboles',
    'session_timeout' => 'Délai d\'expiration de session',
    'two_factor_authentication' => 'Authentification à deux facteurs',
    'login_attempts_limit' => 'Limite de tentatives de connexion',
    'account_lockout_duration' => 'Durée de blocage du compte',

    // Notification Settings
    'email_notifications' => 'Notifications email',
    'sms_notifications' => 'Notifications SMS',
    'push_notifications' => 'Notifications push',
    'notification_email' => 'Email de notification',
    'notification_sms' => 'SMS de notification',
    'notify_on_payslip_generation' => 'Notifier lors de la génération des fiches de paie',
    'notify_on_leave_request' => 'Notifier lors des demandes de congé',
    'notify_on_overtime_request' => 'Notifier lors des demandes d\'heures supplémentaires',

    // Integration Settings
    'api_settings' => 'Paramètres API',
    'webhook_settings' => 'Paramètres webhook',
    'webhook_configuration' => 'Configuration webhook',
    'third_party_integrations' => 'Intégrations tierces',
    'api_key' => 'Clé API',
    'api_secret' => 'Secret API',
    'webhook_url' => 'URL webhook',
    'webhook_secret' => 'Secret webhook',
    'webhook_setup_required' => 'Configuration webhook requise',
    'webhook_setup_instructions' => 'Configurez les webhooks dans le tableau de bord de votre fournisseur d\'email pour recevoir des notifications de livraison en temps réel.',
    'webhook_url_help' => 'Copiez cette URL et collez-la dans la configuration webhook de votre fournisseur d\'email.',

    // Mailgun Webhook Setup
    'mailgun_webhook_setup' => 'Configuration webhook Mailgun',
    'mailgun_webhook_step_1' => 'Allez dans votre tableau de bord Mailgun → Webhooks',
    'mailgun_webhook_step_2' => 'Créez un nouveau webhook pour les événements: Livré, Rebondi, Plainte, Désabonné',
    'mailgun_webhook_step_3' => 'Collez l\'URL webhook ci-dessus dans le champ URL',

    // SES Webhook Setup
    'ses_webhook_setup' => 'Configuration webhook Amazon SES',
    'ses_webhook_step_1' => 'Allez dans la console Amazon SES → Ensembles de configuration',
    'ses_webhook_step_2' => 'Créez ou modifiez un ensemble de configuration avec un sujet SNS pour les notifications',
    'ses_webhook_step_3' => 'Configurez le sujet SNS pour envoyer des webhooks à l\'URL ci-dessus',

    // Postmark Webhook Setup
    'postmark_webhook_setup' => 'Configuration webhook Postmark',
    'postmark_webhook_step_1' => 'Allez dans le tableau de bord Postmark → Webhooks',
    'postmark_webhook_step_2' => 'Créez un nouveau webhook pour les événements: Livré, Rebondi, Plainte spam',
    'postmark_webhook_step_3' => 'Collez l\'URL webhook ci-dessus dans le champ URL',

    // Provider Information
    'get_credentials' => 'Obtenir les identifiants',
    'setup' => 'Configuration',
    'pricing' => 'Tarification',
    'support' => 'Support',
    'documentation' => 'Documentation',
    'best_for' => 'Idéal pour',
    'webhook_support' => 'Support webhook',
    'yes' => 'Oui',
    'no' => 'Non',

    // NEXAH SMS Provider
    'nexah_info_title' => 'NEXAH - Fournisseur SMS local africain',
    'nexah_description' => 'NEXAH est un fournisseur SMS leader en Afrique, offrant une livraison locale fiable avec des prix compétitifs.',
    'nexah_step_1' => 'Visitez nexah.net et créez un compte',
    'nexah_step_2' => 'Complétez la vérification et financez votre compte',
    'nexah_step_3' => 'Copiez vos identifiants API depuis le tableau de bord',
    'nexah_pricing' => 'Paiement à l\'usage, à partir de 0,02 $/SMS',
    'nexah_support' => 'Support local disponible',

    // Twilio SMS Provider
    'twilio_info_title' => 'Twilio - Fournisseur SMS mondial',
    'twilio_description' => 'Twilio fournit une livraison SMS mondiale avec des fonctionnalités avancées comme la messagerie programmable et le suivi de livraison.',
    'twilio_step_1' => 'Inscrivez-vous sur twilio.com',
    'twilio_step_2' => 'Achetez un numéro de téléphone et vérifiez votre compte',
    'twilio_step_3' => 'Obtenez votre Account SID et Auth Token depuis la console',
    'twilio_pricing' => 'Paiement à l\'usage, 0,0075-0,05 $/SMS selon la destination',
    'twilio_docs' => 'Voir la documentation SMS Twilio',

    // AWS SNS Provider
    'aws_sns_info_title' => 'AWS SNS - Service SMS cloud',
    'aws_sns_description' => 'Amazon SNS fournit une livraison SMS évolutive intégrée à l\'écosystème AWS et autres services cloud.',
    'aws_sns_step_1' => 'Créez un compte AWS sur aws.amazon.com',
    'aws_sns_step_2' => 'Configurez un utilisateur IAM avec les permissions SNS',
    'aws_sns_step_3' => 'Obtenez votre Access Key ID et Secret Access Key',
    'aws_sns_pricing' => 'Paiement à l\'usage, 0,00645-0,09 $/SMS selon la destination',
    'aws_sns_docs' => 'Voir la documentation AWS SNS',

    // SMTP Provider
    'smtp_info_title' => 'SMTP - Serveur email standard',
    'smtp_description' => 'Connectez-vous à n\'importe quel serveur SMTP incluant Gmail, Outlook, ou votre propre serveur email.',
    'smtp_step_1' => 'Contactez votre fournisseur email ou département IT',
    'smtp_step_2' => 'Demandez les détails du serveur SMTP et les identifiants',
    'smtp_step_3' => 'Note: Peut nécessiter des mots de passe d\'application pour Gmail/Outlook',
    'smtp_best_for' => 'Infrastructure email existante',

    // Mailgun Provider
    'mailgun_info_title' => 'Mailgun - Service email transactionnel',
    'mailgun_description' => 'Mailgun fournit un email transactionnel puissant avec webhooks, analyses, et taux de délivrabilité élevés.',
    'mailgun_step_1' => 'Inscrivez-vous sur mailgun.com',
    'mailgun_step_2' => 'Vérifiez votre domaine et configurez les enregistrements DNS',
    'mailgun_step_3' => 'Obtenez votre clé API et domaine depuis le tableau de bord',
    'mailgun_pricing' => 'Niveau gratuit: 5 000 emails/mois, puis 0,80 $/1 000 emails',
    'mailgun_docs' => 'Voir la documentation Mailgun',

    // Amazon SES Provider
    'ses_info_title' => 'Amazon SES - Service email cloud',
    'ses_description' => 'Amazon SES offre une livraison email évolutive avec analyses avancées et intégration aux services AWS.',
    'ses_step_1' => 'Configurez un compte AWS et allez dans la console SES',
    'ses_step_2' => 'Vérifiez vos domaines ou adresses email',
    'ses_step_3' => 'Créez des identifiants IAM avec permissions SES',
    'ses_pricing' => 'Niveau gratuit: 62 000 emails/mois, puis 0,10 $/1 000 emails',
    'ses_docs' => 'Voir la documentation Amazon SES',

    // Postmark Provider
    'postmark_info_title' => 'Postmark - Email axé sur la délivrabilité',
    'postmark_description' => 'Postmark se spécialise dans l\'email transactionnel avec une délivrabilité exceptionnelle et analyses détaillées.',
    'postmark_step_1' => 'Inscrivez-vous sur postmarkapp.com',
    'postmark_step_2' => 'Vérifiez votre domaine et créez un serveur',
    'postmark_step_3' => 'Obtenez votre Server API Token depuis le tableau de bord',
    'postmark_pricing' => 'Niveau gratuit: 100 emails/jour, puis 1,50 $/1 000 emails',
    'postmark_docs' => 'Voir la documentation Postmark',

    // Sendmail Provider
    'sendmail_info_title' => 'Sendmail - Email serveur local',
    'sendmail_description' => 'Sendmail est une solution serveur email locale pour les systèmes avec leur propre infrastructure mail.',
    'sendmail_step_1' => 'Assurez-vous que sendmail est installé sur votre serveur',
    'sendmail_step_2' => 'Configurez sendmail pour votre domaine',
    'sendmail_best_for' => 'Serveurs auto-hébergés avec configuration mail locale',

    // Development Providers
    'development_provider_title' => 'Fournisseur développement/test',
    'development_provider_description' => 'Ces fournisseurs sont conçus pour les environnements de développement et test. Ne pas utiliser en production.',

    // Additional UI Text
    'verify_sms_setup' => 'Vérifiez que votre configuration SMS fonctionne correctement',
    'email_configuration_guide' => 'Guide de configuration email',
    'email_setup_overview' => 'Guide complet pour configurer la livraison email',
    'general_setup_steps' => 'Étapes générales de configuration',
    'select_provider_and_get_credentials' => 'Sélectionnez votre fournisseur email et obtenez les identifiants API',
    'configure_provider_settings' => 'Configurez les paramètres spécifiques au fournisseur dans le formulaire ci-dessus',
    'setup_webhooks_optional' => 'Configurez les webhooks (recommandé pour le suivi de livraison)',
    'test_configuration' => 'Testez votre configuration en utilisant le formulaire ci-dessous',
    'configure_email_templates' => 'Configurez les modèles d\'email et la messagerie',
    'pro_tip' => 'Astuce pro',
    'webhook_recommendation' => 'Utilisez des fournisseurs avec support webhook (Mailgun, SES, Postmark) pour des notifications de livraison en temps réel et un meilleur suivi.',
    'test_email_configuration' => 'Tester la configuration email',
    'test_email_setup_instructions' => 'Envoyez un email de test pour vérifier votre configuration',

    // Provider Information Section
    'provider_information' => 'Informations sur les fournisseurs',
    'sms_providers' => 'Fournisseurs SMS',
    'email_providers' => 'Fournisseurs email',
    'how_to_get_sms_credentials' => 'Comment obtenir les identifiants des fournisseurs SMS',
    'how_to_get_email_credentials' => 'Comment obtenir les identifiants des fournisseurs email',
    'sms_providers_guide' => 'Guide des fournisseurs SMS',
    'email_providers_guide' => 'Guide des fournisseurs email',
    'available_sms_providers' => 'Fournisseurs SMS disponibles',
    'available_email_providers' => 'Fournisseurs email disponibles',
    'recommended' => 'Recommandé',
    'credentials' => 'Identifiants',
    'guide' => 'Guide',

    // Actions
    'save_settings' => 'Enregistrer les paramètres',
    'reset_settings' => 'Réinitialiser les paramètres',
    'test_connection' => 'Tester la connexion',
    'settings_saved_successfully' => 'Paramètres enregistrés avec succès!',
    'settings_reset_successfully' => 'Paramètres réinitialisés avec succès!',
    'connection_test_successful' => 'Test de connexion réussi!',
    'connection_test_failed' => 'Échec du test de connexion!',

    // SMS Settings
    'sms_package_configuration' => 'Configuration du package SMS',
    'sms_provider' => 'Fournisseur SMS',
    'nexah' => 'NEXAH',
    'twilio' => 'Twilio',
    'aws_sns' => 'AWS SNS',
    'username_or_token' => 'Nom d\'utilisateur ou jeton',
    'password_or_secret' => 'Mot de passe ou secret',
    'senderid' => 'SenderId',
    'payslip_sms_message_config' => 'Configuration du message SMS des fiches de paie',
    'enter_sms_content_english' => 'Saisir le contenu SMS en anglais',
    'enter_sms_content_french' => 'Saisir le contenu SMS en français',
    'do_not_remove_placeholders' => 'Ne pas supprimer ou modifier les valeurs de',
    'placeholders_note' => 'car ceux-ci sont utilisés comme espaces réservés',
    'birthday_sms_message_config' => 'Configuration du message SMS d\'anniversaire',
    'enter_birthday_sms_english' => 'Saisir le SMS d\'anniversaire en anglais',
    'save_sms_config' => 'Enregistrer la configuration SMS',
    'test_sms_configuration' => 'Tester la configuration SMS',
    'smtp_configuration' => 'Configuration SMTP',
    'enter_phone_number' => 'Saisir le numéro de téléphone',
    'test_sms_message' => 'Message SMS de test',
    'setting_for_sms_successfully_added' => 'Paramètre SMS ajouté avec succès!',
    'setting_for_smtp_successfully_added' => 'Paramètre SMTP ajouté avec succès!',
    'setting_for_smtp_required' => 'Paramètre SMTP requis!',
    'test_email_sent_successfully' => 'Email de test envoyé avec succès!',
    'setting_for_sms_required' => 'Paramètre SMS requis!',
    'test_sms_sent_successfully' => 'SMS de test envoyé avec succès!',
    'test_sms_failed' => 'Échec de l\'envoi du SMS de test!',

    // Email configuration keys
    'from_email' => 'Email expéditeur',
    'enter_email_address' => 'Saisir l\'adresse email',
    'enter_email_message' => 'Saisir le message email',
    'save_mail_config' => 'Sauvegarder la configuration email',
    'enter_email_content_english' => 'Saisir le contenu de l\'email en anglais',
    'enter_email_content_french' => 'Saisir le contenu de l\'email en français',
    'save_welcome_email_config' => 'Sauvegarder la configuration de l\'email de bienvenue',
    'payslips_mail_configuration' => 'Configuration de l\'email des fiches de paie',

    // Additional missing keys
    'enter_birthday_sms_french' => 'Saisir le SMS d\'anniversaire en français',
    'you_can_check_sms_details' => 'Vous pouvez vérifier les détails des SMS envoyés dans la section de gestion des SMS',
    'smtp_providers_only_supported' => 'Pour l\'instant, seuls les fournisseurs SMTP sont pris en charge',
    'login_to_provider_portal' => 'Connectez-vous au portail de votre fournisseur et créez un utilisateur SMTP',
    'create_smtp_password' => 'Créez également un mot de passe pour l\'utilisateur donné',
    'copy_smtp_details' => 'Copiez l\'hôte SMTP et le port fourni par votre fournisseur',
    'configure_smtp_values' => 'Maintenant, mettez ces valeurs dans la configuration des champs et sauvegardez',
    'use_test_form' => 'Utilisez le formulaire ci-dessous pour tester les configurations email.',
    'enter_email_subject_english' => 'Saisir le sujet de l\'email en anglais',
    'enter_email_subject_french' => 'Saisir le sujet de l\'email en français',
    'do_not_remove_email_placeholders' => 'Ne pas supprimer ou modifier les valeurs de',
    'email_placeholder_note' => 'car ceci est utilisé comme espaces réservés',
    'welcome_email_configuration' => 'Configuration de l\'email de bienvenue',
    'enter_welcome_email_subject_english' => 'Saisir le sujet de l\'email de bienvenue en anglais',
    'enter_welcome_email_content_english' => 'Saisir le contenu de l\'email de bienvenue en anglais',
    'enter_welcome_email_subject_french' => 'Saisir le sujet de l\'email de bienvenue en français',
    'enter_welcome_email_content_french' => 'Saisir le contenu de l\'email de bienvenue en français',
    'do_not_remove_welcome_placeholders' => 'Ne pas supprimer ou modifier les valeurs de',
    'welcome_placeholders_note' => 'car ceux-ci sont utilisés comme espaces réservés',
];
