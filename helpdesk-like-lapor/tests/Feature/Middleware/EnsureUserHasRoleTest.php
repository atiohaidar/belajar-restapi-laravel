<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnsureUserHasRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Tests\TestCase; // Use the base TestCase
use Illuminate\Support\Facades\Auth;


class EnsureUserHasRoleTest extends TestCase
{
    public function test_allows_user_with_required_role(): void
    {
        // Mock authenticated user with 'Admin' role
        $user = User::factory()->make(['role' => 'Admin']); // Use make(), no DB needed
        Auth::shouldReceive('user')->once()->andReturn($user); // Mock Auth facade

        $middleware = new EnsureUserHasRole();
        $request = Request::create('/test', 'GET');

        // Mock the $next closure
        $next = function ($request) {
            return response('Allowed', 200);
        };

        $response = $middleware->handle($request, $next, 'Admin');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Allowed', $response->getContent());
    }

    public function test_allows_user_with_one_of_multiple_required_roles(): void
    {
        $user = User::factory()->make(['role' => 'Agency Manager']);
        Auth::shouldReceive('user')->once()->andReturn($user);

        $middleware = new EnsureUserHasRole();
        $request = Request::create('/test', 'GET');
        $next = fn ($req) => response('OK', 200);

        $response = $middleware->handle($request, $next, 'Admin', 'Agency Manager'); // Allowed roles

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_blocks_user_without_required_role(): void
    {
        $user = User::factory()->make(['role' => 'Reporter']);
        Auth::shouldReceive('user')->once()->andReturn($user);

        $middleware = new EnsureUserHasRole();
        $request = Request::create('/test', 'GET');
        $next = fn ($req) => response('Should not reach here', 500);

        $this->expectException(HttpResponseException::class);
        //$this->expectExceptionMessage('This action is unauthorized.'); // Check specific message if needed

        try {
             $middleware->handle($request, $next, 'Admin');
         } catch (HttpResponseException $e) {
             $this->assertEquals(403, $e->getResponse()->getStatusCode());
             $this->assertStringContainsString('Required role(s): Admin', $e->getResponse()->getContent());
             throw $e; // Re-throw to satisfy PHPUnit's expectation
         }
    }

    public function test_throws_authentication_exception_if_user_not_logged_in(): void
    {
        Auth::shouldReceive('user')->once()->andReturn(null); // No user logged in

        $middleware = new EnsureUserHasRole();
        $request = Request::create('/test', 'GET');
        $next = fn ($req) => response('Should not reach here', 500);

        $this->expectException(AuthenticationException::class);

        $middleware->handle($request, $next, 'Admin');
    }
}