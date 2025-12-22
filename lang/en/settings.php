<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Settings Management Language Lines
    |--------------------------------------------------------------------------
    */

    'settings' => 'Settings',
    'system_settings' => 'System Settings',
    'application_settings' => 'Application Settings',
    'general_settings' => 'General Settings',
    'email_settings' => 'Email Settings',
    'sms_settings' => 'SMS Settings',
    'notification_settings' => 'Notification Settings',
    'security_settings' => 'Security Settings',
    'integration_settings' => 'Integration Settings',

    // Email Settings
    'email_provider' => 'Email Provider',
    'email_configuration' => 'Email Configuration',
    'smtp_configuration' => 'SMTP Configuration',
    'smtp' => 'SMTP',
    'mailgun' => 'Mailgun',
    'ses' => 'Amazon SES',
    'postmark' => 'Postmark',
    'sendmail' => 'Sendmail',
    'mailpit' => 'Mailpit',
    'log' => 'Log',
    'array' => 'Array',

    // Provider Categories
    'basic_smtp_provider' => 'Basic SMTP Server',
    'transactional_email_provider' => 'Transactional Email Service',
    'cloud_email_provider' => 'Cloud Email Service',
    'deliverability_focused_provider' => 'Deliverability-Focused Service',
    'local_server_provider' => 'Local Server',
    'recommended_local_provider' => 'Recommended Local Provider',
    'global_sms_provider' => 'Global SMS Provider',
    'cloud_sms_provider' => 'Cloud SMS Provider',

    // SMTP Settings
    'smtp_host' => 'SMTP Host',
    'smtp_port' => 'SMTP Port',
    'smtp_username' => 'SMTP Username',
    'smtp_password' => 'SMTP Password',
    'smtp_encryption' => 'SMTP Encryption',

    // Email Settings
    'from_email' => 'From Email',
    'from_name' => 'From Name',
    'reply_to_email' => 'Reply To Email',
    'reply_to_name' => 'Reply To Name',

    'test_email' => 'Test Email',
    'send_test_email' => 'Send Test Email',
    'setting_for_smtp_successfully_added' => 'Email settings saved successfully!',
    'test_email_sent_successfully' => 'Test email sent successfully!',
    'setting_for_smtp_required' => 'Email settings are required to send test emails',

    // Provider-specific information
    'smtp_limitations' => 'SMTP Limitations',
    'smtp_webhook_note' => 'SMTP does not support webhooks for delivery confirmation. Email delivery status will be tracked via bounce handling only.',
    'transactional_webhook_note' => 'This provider supports webhooks for real-time delivery confirmation.',
    'development_driver_note' => 'This is a development/testing driver and should not be used in production.',

    // SMS Settings
    'sms_provider' => 'SMS Provider',
    'sms_api_key' => 'SMS API Key',
    'sms_api_secret' => 'SMS API Secret',
    'sms_sender_id' => 'SMS Sender ID',
    'sms_balance' => 'SMS Balance',
    'test_sms' => 'Test SMS',
    'send_test_sms' => 'Send Test SMS',
    'sms_settings_saved' => 'SMS settings saved successfully!',
    'sms_test_sent' => 'Test SMS sent successfully!',

    // Company Settings
    'company_name' => 'Company Name',
    'company_logo' => 'Company Logo',
    'company_address' => 'Company Address',
    'company_phone' => 'Company Phone',
    'company_email' => 'Company Email',
    'company_website' => 'Company Website',
    'fiscal_year_start' => 'Fiscal Year Start',
    'timezone' => 'Timezone',
    'currency' => 'Currency',
    'language' => 'Language',
    'date_format' => 'Date Format',
    'time_format' => 'Time Format',

    // Security Settings
    'password_policy' => 'Password Policy',
    'minimum_password_length' => 'Minimum Password Length',
    'password_requires_uppercase' => 'Require Uppercase Letters',
    'password_requires_lowercase' => 'Require Lowercase Letters',
    'password_requires_numbers' => 'Require Numbers',
    'password_requires_symbols' => 'Require Symbols',
    'session_timeout' => 'Session Timeout',
    'two_factor_authentication' => 'Two-Factor Authentication',
    'login_attempts_limit' => 'Login Attempts Limit',
    'account_lockout_duration' => 'Account Lockout Duration',

    // Notification Settings
    'email_notifications' => 'Email Notifications',
    'sms_notifications' => 'SMS Notifications',
    'push_notifications' => 'Push Notifications',
    'notification_email' => 'Notification Email',
    'notification_sms' => 'Notification SMS',
    'notify_on_payslip_generation' => 'Notify on Payslip Generation',
    'notify_on_leave_request' => 'Notify on Leave Request',
    'notify_on_overtime_request' => 'Notify on Overtime Request',

    // Integration Settings
    'api_settings' => 'API Settings',
    'webhook_settings' => 'Webhook Settings',
    'webhook_configuration' => 'Webhook Configuration',
    'third_party_integrations' => 'Third Party Integrations',
    'api_key' => 'API Key',
    'api_secret' => 'API Secret',
    'webhook_url' => 'Webhook URL',
    'webhook_secret' => 'Webhook Secret',
    'webhook_setup_required' => 'Webhook Setup Required',
    'webhook_setup_instructions' => 'Configure webhooks in your email provider dashboard to receive real-time delivery notifications.',
    'webhook_url_help' => 'Copy this URL and paste it in your email provider\'s webhook configuration.',

    // Mailgun Webhook Setup
    'mailgun_webhook_setup' => 'Mailgun Webhook Setup',
    'mailgun_webhook_step_1' => 'Go to your Mailgun dashboard → Webhooks',
    'mailgun_webhook_step_2' => 'Create a new webhook for events: Delivered, Bounced, Complained, Unsubscribed',
    'mailgun_webhook_step_3' => 'Paste the webhook URL above in the URL field',

    // SES Webhook Setup
    'ses_webhook_setup' => 'Amazon SES Webhook Setup',
    'ses_webhook_step_1' => 'Go to Amazon SES console → Configuration Sets',
    'ses_webhook_step_2' => 'Create or edit a configuration set with SNS topic for notifications',
    'ses_webhook_step_3' => 'Configure SNS topic to send webhooks to the URL above',

    // Postmark Webhook Setup
    'postmark_webhook_setup' => 'Postmark Webhook Setup',
    'postmark_webhook_step_1' => 'Go to Postmark dashboard → Webhooks',
    'postmark_webhook_step_2' => 'Create a new webhook for events: Delivered, Bounced, Spam Complaint',
    'postmark_webhook_step_3' => 'Paste the webhook URL above in the URL field',

    // Provider Information
    'get_credentials' => 'Get Credentials',
    'setup' => 'Setup',
    'pricing' => 'Pricing',
    'support' => 'Support',
    'documentation' => 'Documentation',
    'best_for' => 'Best For',
    'webhook_support' => 'Webhook Support',
    'yes' => 'Yes',
    'no' => 'No',

    // NEXAH SMS Provider
    'nexah_info_title' => 'NEXAH - Local African SMS Provider',
    'nexah_description' => 'NEXAH is a leading SMS provider in Africa, offering reliable local delivery with competitive pricing.',
    'nexah_step_1' => 'Visit nexah.net and create an account',
    'nexah_step_2' => 'Complete verification and fund your account',
    'nexah_step_3' => 'Copy your API credentials from the dashboard',
    'nexah_pricing' => 'Pay-per-use, starting from $0.02/SMS',
    'nexah_support' => 'Local support available',

    // Twilio SMS Provider
    'twilio_info_title' => 'Twilio - Global SMS Provider',
    'twilio_description' => 'Twilio provides global SMS delivery with advanced features like programmable messaging and delivery tracking.',
    'twilio_step_1' => 'Sign up at twilio.com',
    'twilio_step_2' => 'Purchase a phone number and verify your account',
    'twilio_step_3' => 'Get your Account SID and Auth Token from the console',
    'twilio_pricing' => 'Pay-per-use, $0.0075-$0.05/SMS depending on destination',
    'twilio_docs' => 'View Twilio SMS Documentation',

    // AWS SNS Provider
    'aws_sns_info_title' => 'AWS SNS - Cloud SMS Service',
    'aws_sns_description' => 'Amazon SNS provides scalable SMS delivery integrated with AWS ecosystem and other cloud services.',
    'aws_sns_step_1' => 'Create an AWS account at aws.amazon.com',
    'aws_sns_step_2' => 'Set up IAM user with SNS permissions',
    'aws_sns_step_3' => 'Get your Access Key ID and Secret Access Key',
    'aws_sns_pricing' => 'Pay-per-use, $0.00645-$0.09/SMS depending on destination',
    'aws_sns_docs' => 'View AWS SNS Documentation',

    // SMTP Provider
    'smtp_info_title' => 'SMTP - Standard Email Server',
    'smtp_description' => 'Connect to any SMTP server including Gmail, Outlook, or your own email server.',
    'smtp_step_1' => 'Contact your email provider or IT department',
    'smtp_step_2' => 'Request SMTP server details and credentials',
    'smtp_step_3' => 'Note: May require app passwords for Gmail/Outlook',
    'smtp_best_for' => 'Existing email infrastructure',

    // Mailgun Provider
    'mailgun_info_title' => 'Mailgun - Transactional Email Service',
    'mailgun_description' => 'Mailgun provides powerful transactional email with webhooks, analytics, and high deliverability rates.',
    'mailgun_step_1' => 'Sign up at mailgun.com',
    'mailgun_step_2' => 'Verify your domain and set up DNS records',
    'mailgun_step_3' => 'Get your API key and domain from the dashboard',
    'mailgun_pricing' => 'Free tier: 5,000 emails/month, then $0.80/1,000 emails',
    'mailgun_docs' => 'View Mailgun Documentation',

    // Amazon SES Provider
    'ses_info_title' => 'Amazon SES - Cloud Email Service',
    'ses_description' => 'Amazon SES offers scalable email delivery with advanced analytics and integration with AWS services.',
    'ses_step_1' => 'Set up AWS account and navigate to SES console',
    'ses_step_2' => 'Verify your domain or email addresses',
    'ses_step_3' => 'Create IAM credentials with SES permissions',
    'ses_pricing' => 'Free tier: 62,000 emails/month, then $0.10/1,000 emails',
    'ses_docs' => 'View Amazon SES Documentation',

    // Postmark Provider
    'postmark_info_title' => 'Postmark - Deliverability-Focused Email',
    'postmark_description' => 'Postmark specializes in transactional email with exceptional deliverability and detailed analytics.',
    'postmark_step_1' => 'Sign up at postmarkapp.com',
    'postmark_step_2' => 'Verify your domain and create a server',
    'postmark_step_3' => 'Get your Server API Token from the dashboard',
    'postmark_pricing' => 'Free tier: 100 emails/day, then $1.50/1,000 emails',
    'postmark_docs' => 'View Postmark Documentation',

    // Sendmail Provider
    'sendmail_info_title' => 'Sendmail - Local Server Email',
    'sendmail_description' => 'Sendmail is a local email server solution for systems with their own mail infrastructure.',
    'sendmail_step_1' => 'Ensure sendmail is installed on your server',
    'sendmail_step_2' => 'Configure sendmail for your domain',
    'sendmail_best_for' => 'Self-hosted servers with local mail setup',

    // Development Providers
    'development_provider_title' => 'Development/Testing Provider',
    'development_provider_description' => 'These providers are designed for development and testing environments. Do not use in production.',

    // Additional UI Text
    'verify_sms_setup' => 'Verify your SMS configuration works correctly',
    'email_configuration_guide' => 'Email Configuration Guide',
    'email_setup_overview' => 'Complete guide to setting up email delivery',
    'general_setup_steps' => 'General Setup Steps',
    'select_provider_and_get_credentials' => 'Select your email provider and obtain API credentials',
    'configure_provider_settings' => 'Configure provider-specific settings in the form above',
    'setup_webhooks_optional' => 'Set up webhooks (recommended for delivery tracking)',
    'test_configuration' => 'Test your configuration using the form below',
    'configure_email_templates' => 'Configure email templates and messaging',
    'pro_tip' => 'Pro Tip',
    'webhook_recommendation' => 'Use providers with webhook support (Mailgun, SES, Postmark) for real-time delivery notifications and better tracking.',
    'test_email_configuration' => 'Test Email Configuration',
    'test_email_setup_instructions' => 'Send a test email to verify your configuration',

    // Provider Information Section
    'provider_information' => 'Provider Information',
    'sms_providers' => 'SMS Providers',
    'email_providers' => 'Email Providers',
    'how_to_get_sms_credentials' => 'How to Get SMS Provider Credentials',
    'how_to_get_email_credentials' => 'How to Get Email Provider Credentials',
    'sms_providers_guide' => 'SMS Providers Guide',
    'email_providers_guide' => 'Email Providers Guide',
    'available_sms_providers' => 'Available SMS Providers',
    'available_email_providers' => 'Available Email Providers',
    'recommended' => 'Recommended',
    'credentials' => 'Credentials',
    'guide' => 'Guide',

    // Actions
    'save_settings' => 'Save Settings',
    'reset_settings' => 'Reset Settings',
    'test_connection' => 'Test Connection',
    'settings_saved_successfully' => 'Settings saved successfully!',
    'settings_reset_successfully' => 'Settings reset successfully!',
    'connection_test_successful' => 'Connection test successful!',
    'connection_test_failed' => 'Connection test failed!',

    // SMS Settings
    'sms_package_configuration' => 'SMS Package Configuration',
    'sms_provider' => 'SMS Provider',
    'nexah' => 'NEXAH',
    'twilio' => 'Twilio',
    'aws_sns' => 'AWS SNS',
    'username_or_token' => 'Username or Token',
    'password_or_secret' => 'Password or secret',
    'senderid' => 'SenderId',
    'payslip_sms_message_config' => 'Payslip SMS Message config',
    'enter_sms_content_english' => 'Enter sms Content English',
    'enter_sms_content_french' => 'Enter sms Content French',
    'do_not_remove_placeholders' => 'Do not remove or change the values of',
    'placeholders_note' => 'as these are used as placeholders',
    'birthday_sms_message_config' => 'Birthday SMS Message config',
    'enter_birthday_sms_english' => 'Enter birthday sms English',
    'save_sms_config' => 'Save SMS Config',
    'test_sms_configuration' => 'Test SMS configuration',
    'smtp_configuration' => 'SMTP Configuration',
    'enter_phone_number' => 'Enter Phone number',
    'test_sms_message' => 'Test SMS Message',
    'setting_for_sms_successfully_added' => 'Setting for SMS successfully added!',
    'setting_for_smtp_successfully_added' => 'Setting for SMTP successfully added!',
    'setting_for_smtp_required' => 'Setting for SMTP required!',
    'test_email_sent_successfully' => 'Test Email sent successfully!',
    'setting_for_sms_required' => 'Setting for SMS required!',
    'test_sms_sent_successfully' => 'Test sms was sent successfully!',
    'test_sms_failed' => 'Test Sms Failed!',

    // Email configuration keys
    'from_email' => 'From Email',
    'enter_email_address' => 'Enter Email Address',
    'enter_email_message' => 'Enter Email Message',
    'save_mail_config' => 'Save Mail Config',
    'enter_email_content_english' => 'Enter Email Content English',
    'enter_email_content_french' => 'Enter Email Content French',
    'save_welcome_email_config' => 'Save Welcome Email Config',
    'payslips_mail_configuration' => 'Payslips Mail Configuration',

    // Additional missing keys
    'enter_birthday_sms_french' => 'Enter birthday sms French',
    'you_can_check_sms_details' => 'You can check the detail of SMS sent on sms management section',
    'smtp_providers_only_supported' => 'As of now only SMTP providers are supported',
    'login_to_provider_portal' => 'Login to your providers portal and create an smtp user',
    'create_smtp_password' => 'Create also password for the given user',
    'copy_smtp_details' => 'Copy the smtp host and port provider by your provider',
    'configure_smtp_values' => 'Now put these values in the fields configuration and save',
    'use_test_form' => 'Use below form to test email configurations.',
    'enter_email_subject_english' => 'Enter Email subject English',
    'enter_email_subject_french' => 'Enter Email subject French',
    'do_not_remove_email_placeholders' => 'Don not remove or change the values of',
    'email_placeholder_note' => 'as this is used as placeholders',
    'welcome_email_configuration' => 'Welcome Email configuration',
    'enter_welcome_email_subject_english' => 'Enter welcome email subject English',
    'enter_welcome_email_content_english' => 'Enter welcome email content English',
    'enter_welcome_email_subject_french' => 'Enter welcome email subject French',
    'enter_welcome_email_content_french' => 'Enter welcome email content French',
    'do_not_remove_welcome_placeholders' => 'Don not remove or change the values of',
    'welcome_placeholders_note' => 'as these are used as placeholders',

    // Provider field labels
    'account_sid' => 'Account SID',
    'auth_token' => 'Auth Token',
    'phone_number' => 'Phone Number',
    'access_key_id' => 'Access Key ID',
    'secret_access_key' => 'Secret Access Key',
    'region' => 'Region',
    'configuration' => 'Configuration',
    'domain' => 'Domain',
    'api_secret' => 'API Secret',
    'endpoint' => 'Endpoint',
    'endpoint_optional' => 'Endpoint (Optional)',
    'scheme' => 'Scheme',
    'scheme_optional' => 'Scheme (Optional)',
    'server_token' => 'Server Token',
    'sender_id_optional' => 'Sender ID (Optional)',
    'mailgun_configuration' => 'Mailgun Configuration',
    'amazon_ses_configuration' => 'Amazon SES Configuration',
    'postmark_configuration' => 'Postmark Configuration',
];
