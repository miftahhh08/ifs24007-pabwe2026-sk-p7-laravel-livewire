<?php

namespace Tests\Unit\Providers;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\Services\OpenAiStockGeneratorService;
use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Repositories\StockRepository;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_service_bindings(): void
    {
        $this->assertInstanceOf(StockRepository::class, app(StockRepositoryInterface::class));
        $this->assertInstanceOf(OpenAiStockGeneratorService::class, app(AiClientInterface::class));
    }

    public function test_force_https_enabled(): void
    {
        config(['app.force_https' => true]);

        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('https://', URL::to('/uji'));
    }

    public function test_force_https_disabled_by_default(): void
    {
        $this->assertFalse((bool) config('app.force_https'));
    }

    public function test_rate_limiter_is_1000_per_second_keyed_by_ip_for_guests(): void
    {
        $request = Request::create('/uji', 'GET', server: ['REMOTE_ADDR' => '10.1.2.3']);

        $limit = RateLimiter::limiter('1000-per-second')($request);

        $this->assertSame(1000, $limit->maxAttempts);
        $this->assertSame(1, $limit->decaySeconds);
        $this->assertSame('10.1.2.3', $limit->key);
    }

    public function test_rate_limiter_is_keyed_by_user_id_when_authenticated(): void
    {
        $user = User::factory()->make(['id' => 42]);
        $request = Request::create('/uji');
        $request->setUserResolver(fn () => $user);

        $limit = RateLimiter::limiter('1000-per-second')($request);

        $this->assertSame(42, $limit->key);
    }
}
