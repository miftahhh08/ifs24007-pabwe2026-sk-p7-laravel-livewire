<?php

namespace Tests\Unit\Policies;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Policies\StockPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class StockPolicyTest extends TestCase
{
    use RefreshDatabase;

    private StockPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new StockPolicy;
    }

    public function test_policy_is_registered_for_stock(): void
    {
        $this->assertInstanceOf(StockPolicy::class, Gate::getPolicyFor(Stock::class));
    }

    public function test_only_members_can_view_any_and_create(): void
    {
        $member = User::factory()->member()->make();
        $admin = User::factory()->admin()->make();

        $this->assertTrue($this->policy->viewAny($member));
        $this->assertTrue($this->policy->create($member));
        $this->assertFalse($this->policy->viewAny($admin));
        $this->assertFalse($this->policy->create($admin));
    }

    public function test_owner_member_can_do_everything_on_own_stock(): void
    {
        $owner = User::factory()->member()->create();
        $stock = Stock::factory()->create(['user_id' => $owner->id]);

        foreach (['view', 'update', 'delete', 'adjustQuantity'] as $ability) {
            $this->assertTrue($this->policy->{$ability}($owner, $stock), $ability);
        }
    }

    public function test_other_member_cannot_touch_stock(): void
    {
        $stock = Stock::factory()->create();
        $other = User::factory()->member()->create();

        foreach (['view', 'update', 'delete', 'adjustQuantity'] as $ability) {
            $this->assertFalse($this->policy->{$ability}($other, $stock), $ability);
        }
    }

    public function test_admin_is_read_only_even_for_own_stock_record(): void
    {
        $admin = User::factory()->admin()->create();
        $stock = Stock::factory()->create(['user_id' => $admin->id]);

        foreach (['view', 'update', 'delete', 'adjustQuantity'] as $ability) {
            $this->assertFalse($this->policy->{$ability}($admin, $stock), $ability);
        }
    }
}
