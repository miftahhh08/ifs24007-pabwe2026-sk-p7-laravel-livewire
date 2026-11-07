<?php

namespace Tests\Unit\Middleware;

use App\Features\Stock\Models\Stock;
use App\Http\Middleware\EnsureStockOwnership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureStockOwnershipMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function callMiddleware(?User $user, mixed $stockParam, bool $withParam = true)
    {
        $request = Request::create('/stocks/1');
        $route = new Route('GET', '/stocks/{stock}', fn () => null);
        $route->bind($request);

        if ($withParam) {
            $route->setParameter('stock', $stockParam);
        } else {
            $route->forgetParameter('stock');
        }

        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => $user);

        return (new EnsureStockOwnership)->handle($request, fn () => response('ok'));
    }

    private function assertAborts(int $status, callable $callback): void
    {
        try {
            $callback();
            $this->fail("Seharusnya abort {$status}.");
        } catch (HttpException $e) {
            $this->assertSame($status, $e->getStatusCode());
        }
    }

    public function test_passes_when_route_has_no_stock_parameter(): void
    {
        $response = $this->callMiddleware(User::factory()->make(), null, withParam: false);

        $this->assertSame('ok', $response->getContent());
    }

    public function test_owner_passes_with_model_parameter(): void
    {
        $owner = User::factory()->create();
        $stock = Stock::factory()->create(['user_id' => $owner->id]);

        $this->assertSame('ok', $this->callMiddleware($owner, $stock)->getContent());
    }

    public function test_owner_passes_with_raw_id_parameter(): void
    {
        $owner = User::factory()->create();
        $stock = Stock::factory()->create(['user_id' => $owner->id]);

        $this->assertSame('ok', $this->callMiddleware($owner, (string) $stock->id)->getContent());
    }

    public function test_missing_stock_is_404(): void
    {
        $owner = User::factory()->create();

        $this->assertAborts(404, fn () => $this->callMiddleware($owner, 999999));
    }

    public function test_other_users_stock_is_403(): void
    {
        $stock = Stock::factory()->create();
        $intruder = User::factory()->create();

        $this->assertAborts(403, fn () => $this->callMiddleware($intruder, $stock));
    }

    public function test_guest_is_403(): void
    {
        $stock = Stock::factory()->create();

        $this->assertAborts(403, fn () => $this->callMiddleware(null, $stock));
    }
}
