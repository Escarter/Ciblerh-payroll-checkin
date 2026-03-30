<?php

use App\Models\CredentialToken;
use App\Models\Role;
use App\Models\User;
use App\Notifications\SendCredentialsNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Messages\MailMessage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate([
        'name' => 'employee',
        'guard_name' => 'web',
    ]);
});

function renderMailMessage(MailMessage $mailMessage): string
{
    if (!empty($mailMessage->markdown)) {
        return app(Markdown::class)
            ->render($mailMessage->markdown, $mailMessage->data())
            ->toHtml();
    }

    if (!empty($mailMessage->view)) {
        return view($mailMessage->view, $mailMessage->data())->render();
    }

    return '';
}

test('credentials notification mail renders without message variable collision', function () {
    $user = User::factory()->create([
        'email' => 'employee1@example.com',
        'first_name' => 'Import',
        'last_name' => 'Employee',
    ]);

    $token = CredentialToken::createForUser($user, 'TempPass#123');

    $mailMessage = (new SendCredentialsNotification($token->id))->toMail($user);

    $html = renderMailMessage($mailMessage);

    expect($html)
        ->toContain('TempPass#123')
        ->and($html)->toContain('Import Employee');
});

test('credentials notification invalid token fallback renders safely', function () {
    $user = User::factory()->create([
        'email' => 'employee2@example.com',
    ]);

    $mailMessage = (new SendCredentialsNotification(999999))->toMail($user);

    $html = renderMailMessage($mailMessage);

    expect($html)->toContain('Error: Unable to retrieve credentials.');
});
