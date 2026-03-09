<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Misafir kullanıcı ana sayfadan giriş sayfasına yönlendirilir.
     */
    public function test_guest_is_redirected_to_login_from_root(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }
}
