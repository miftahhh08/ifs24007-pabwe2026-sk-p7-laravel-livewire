<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnsureRole;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureRoleMiddlewareTest extends TestCase
{
    private function callMiddleware(?User $user, string ...$roles): Response
    {
        $request = Request::create('/x');
        $request->setUserResolver(fn () => $user);

        return (new EnsureRole)->handle($request, fn () => response('ok'), ...$roles);
    }

    public function test_guest_is_forbidden(): void
    {
        $this->expectException(HttpException::class);
        $this->callMiddleware(null, 'member');
    }

    public function test_wrong_role_is_forbidden(): void
    {
        try {
            $this->callMiddleware(User::factory()->make(['role' => 'member']), 'admin');
            $this->fail('Seharusnya 403.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_matching_role_passes(): void
    {
        $response = $this->callMiddleware(User::factory()->make(['role' => 'admin']), 'admin');

        $this->assertSame('ok', $response->getContent());
    }

    public function test_any_of_multiple_roles_passes(): void
    {
        $response = $this->callMiddleware(User::factory()->make(['role' => 'member']), 'admin', 'member');

        $this->assertSame(200, $response->getStatusCode());
    }
}
