<?php

namespace Tests\Feature\Admin;

use App\Features\Admin\Livewire\AdminUserStockList;
use App\Features\Admin\Livewire\UserList;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Exceptions\MethodNotFoundException;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_user_list_shows_users_with_stock_counts(): void
    {
        $member = User::factory()->create(['name' => 'Budi Penjual']);
        Stock::factory()->count(3)->create(['user_id' => $member->id]);

        Livewire::test(UserList::class)
            ->assertSee('Budi Penjual')
            ->assertSeeInOrder(['Budi Penjual', '3']);
    }

    public function test_user_list_search(): void
    {
        User::factory()->create(['name' => 'Cari Aku', 'username' => 'cariaku']);
        User::factory()->create(['name' => 'Orang Lain', 'username' => 'lain']);

        Livewire::test(UserList::class)
            ->set('search', 'cariaku')
            ->assertSee('Cari Aku')
            ->assertDontSee('Orang Lain')
            ->set('search', '')
            ->assertSee('Orang Lain');
    }

    public function test_user_list_paginates(): void
    {
        User::factory()->count(12)->create();

        $component = Livewire::test(UserList::class)->call('setPage', 2);
        $component->set('search', 'zzz-tidak-ada');

        $this->assertSame(1, $component->instance()->getPage());
    }

    public function test_user_stock_list_shows_only_that_users_stocks(): void
    {
        $member = User::factory()->create();
        Stock::factory()->create(['user_id' => $member->id, 'name' => 'Milik Member']);
        Stock::factory()->create(['name' => 'Milik Orang Lain']);

        Livewire::test(AdminUserStockList::class, ['user' => $member])
            ->assertSee('Milik Member')
            ->assertDontSee('Milik Orang Lain');
    }

    public function test_user_stock_list_search_and_status_filter(): void
    {
        $member = User::factory()->create();
        Stock::factory()->create(['user_id' => $member->id, 'name' => 'Kabel', 'quantity' => 0, 'status' => 'out_of_stock']);
        Stock::factory()->create(['user_id' => $member->id, 'name' => 'Mouse', 'quantity' => 40, 'status' => 'available']);

        Livewire::test(AdminUserStockList::class, ['user' => $member])
            ->set('search', 'Kabel')
            ->assertSee('Kabel')
            ->assertDontSee('Mouse')
            ->set('search', '')
            ->set('statusFilter', 'available')
            ->assertSee('Mouse')
            ->assertDontSee('Kabel');
    }

    public function test_user_stock_list_resets_page_on_filter_change(): void
    {
        $member = User::factory()->create();
        Stock::factory()->count(12)->create(['user_id' => $member->id]);

        $component = Livewire::test(AdminUserStockList::class, ['user' => $member])->call('setPage', 2);
        $component->set('search', 'a');
        $this->assertSame(1, $component->instance()->getPage());

        $component->call('setPage', 2)->set('statusFilter', 'available');
        $this->assertSame(1, $component->instance()->getPage());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('mutatingActions')]
    public function test_admin_stock_list_is_read_only(string $action): void
    {
        $member = User::factory()->create();
        $stock = Stock::factory()->create(['user_id' => $member->id]);

        $this->expectException(MethodNotFoundException::class);

        Livewire::test(AdminUserStockList::class, ['user' => $member])->call($action, $stock->id);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function mutatingActions(): array
    {
        return [
            'delete' => ['delete'],
            'adjust' => ['adjust'],
            'save' => ['save'],
            'openEdit' => ['openEdit'],
            'import' => ['import'],
        ];
    }
}
