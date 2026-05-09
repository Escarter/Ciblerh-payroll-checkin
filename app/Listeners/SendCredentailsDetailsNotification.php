<?php

namespace App\Listeners;

use App\Events\EmployeeCreated;
use App\Models\CredentialToken;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Notifications\SendCredentialsNotification;

class SendCredentailsDetailsNotification
{
   
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\EmployeeCreated  $event
     * @return void
     */
    public function handle(EmployeeCreated $event)
    {

        $validator = Validator::make(['email' => $event->employee->email ], [
            'email' => 'required|email',
        ]);

        if($validator->passes()){
            try {
                // Generate password if not provided (common for file imports)
                $password = $event->password;
                $passwordGenerated = false;
                
                if (empty($password)) {
                    $password = $this->generateSecurePassword();
                    $passwordGenerated = true;
                    
                    // Update the user with the generated password
                    $event->employee->update([
                        'password' => bcrypt($password),
                    ]);
                    
                    Log::info('Auto-generated password for employee', [
                        'employee_id' => $event->employee->id,
                        'email' => $event->employee->email,
                        'import_job_id' => $event->importJobId,
                    ]);
                }
                
                // Create credential token with the password
                $token = CredentialToken::createForUser($event->employee, $password, 24, $event->importJobId);
                
                // Queue notification with token ID (secure, not the actual password)
                // Use sendNow() only for immediate dispatch, or queue it properly via Notification::send()
                // Notification::send() will respect ShouldQueue if the notification implements it
                $event->employee->notify(new SendCredentialsNotification($token->id));
                
                Log::info('Credentials notification queued for employee', [
                    'employee_id' => $event->employee->id,
                    'email' => $event->employee->email,
                    'token_id' => $token->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send credentials notification', [
                    'employee_id' => $event->employee->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Generate a secure random password for employees
     */
    private function generateSecurePassword(): string
    {
        // Generate a 12-character password with mixed case, numbers, and symbols
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*';
        
        $allChars = $uppercase . $lowercase . $numbers . $symbols;
        
        // Ensure at least one character from each category
        $password = [
            $uppercase[random_int(0, strlen($uppercase) - 1)],
            $lowercase[random_int(0, strlen($lowercase) - 1)],
            $numbers[random_int(0, strlen($numbers) - 1)],
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];
        
        // Fill the rest with random characters
        for ($i = 4; $i < 12; $i++) {
            $password[] = $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Shuffle and return as string
        shuffle($password);
        return implode('', $password);
    }
}
