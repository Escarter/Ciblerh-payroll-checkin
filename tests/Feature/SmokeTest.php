<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_returns_successful_response()
    {
        $response = $this->get('/');
        
        // Should redirect to login since user is not authenticated
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_login_page_is_accessible()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_database_connection_and_seeding_works()
    {
        // Run the seeders
        $this->artisan('db:seed');
        
        // Check if admin user exists
        $adminUser = User::where('email', 'admin@app.com')->first();
        
        $this->assertNotNull($adminUser);
        $this->assertEquals('admin@app.com', $adminUser->email);
        $this->assertTrue($adminUser->hasRole('admin'));
    }

    public function test_authenticated_user_can_access_portal_dashboard()
    {
        // Run seeders to get admin user
        $this->artisan('db:seed');
        
        $adminUser = User::where('email', 'admin@app.com')->first();
        
        $response = $this->actingAs($adminUser)->get('/portal/dashboard');
        
        // Should be able to access dashboard
        $response->assertStatus(200);
    }
}