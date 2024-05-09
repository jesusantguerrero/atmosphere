<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\Feature\Helpers\AppBase;

class ApiLoginTest extends AppBase
{
    /**
     * A basic feature test example.
     */
    public function test_login_mobile(): void
    {
        $response = $this->post('/api/sanctum/token', [
            'email' => $this->user->email,
            'password' => 'password',
            'device_name' => 'android'
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('personal_access_tokens', [
            "name" => 'android',
            "tokenable_type" => User::class,
            'tokenable_id' => $this->user->id
        ]);

        $response->assertStatus(200);
    }

    public function test_user_data(): void
    {
        $token = $this->user->createToken("android")->plainTextToken;

        $response = $this->get('/api/user', [
            "Authorization" => "Bearer ". $token
        ]);
        
        $response->assertSuccessful();
        $response->assertJson( fn ($json) => 
            $json->where('email', $this->user->email)
            ->missing("password")
            ->etc()
        );

    }
}
