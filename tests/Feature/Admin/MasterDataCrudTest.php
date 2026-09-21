<?php

namespace Tests\Feature\Admin;

use App\Models\Country;
use App\Models\Currency;
use App\Models\IdentificationType;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MasterDataCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->country = Country::factory()->create();

        Permission::create(['name' => 'settings.view', 'guard_name' => 'web']);
        Permission::create(['name' => 'settings.update', 'guard_name' => 'web']);
        $this->user->givePermissionTo('settings.view');
        $this->user->givePermissionTo('settings.update');

        $this->actingAs($this->user);
    }

    // Countries
    public function test_user_can_view_countries_page(): void
    {
        $response = $this->get('/admin/master-data/countries');

        $response->assertStatus(200)
            ->assertViewIs('admin.master-data.countries');
    }

    public function test_user_can_create_country(): void
    {
        $response = $this->post('/admin/master-data/countries', [
            'name' => 'Test Country',
            'code' => 'TC',
            'currency_code' => 'TCD',
            'currency_symbol' => 'T$',
            'phone_code' => '+123',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/master-data/countries');
        $this->assertDatabaseHas('countries', [
            'name' => 'Test Country',
            'code' => 'TC',
        ]);
    }

    public function test_user_can_edit_country(): void
    {
        $response = $this->put('/admin/master-data/countries/'.$this->country->id, [
            'name' => 'Updated Country',
            'code' => $this->country->code,
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'phone_code' => '+1',
            'timezone' => 'America/New_York',
            'date_format' => 'm/d/Y',
            'time_format' => 'h:i A',
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/master-data/countries');
        $this->assertDatabaseHas('countries', [
            'id' => $this->country->id,
            'name' => 'Updated Country',
        ]);
    }

    public function test_user_can_delete_country(): void
    {
        $response = $this->delete('/admin/master-data/countries/'.$this->country->id);

        $response->assertRedirect('/admin/master-data/countries');
        $this->assertSoftDeleted('countries', [
            'id' => $this->country->id,
        ]);
    }

    // Currencies
    public function test_user_can_view_currencies_page(): void
    {
        $response = $this->get('/admin/master-data/currencies');

        $response->assertStatus(200)
            ->assertViewIs('admin.master-data.currencies');
    }

    public function test_user_can_create_currency(): void
    {
        $response = $this->post('/admin/master-data/currencies', [
            'name' => 'Test Currency',
            'code' => 'TST',
            'symbol' => 'T',
            'decimal_places' => '2',
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/master-data/currencies');
        $this->assertDatabaseHas('currencies', [
            'name' => 'Test Currency',
            'code' => 'TST',
        ]);
    }

    public function test_user_can_edit_currency(): void
    {
        $currency = Currency::factory()->create();

        $response = $this->put('/admin/master-data/currencies/'.$currency->id, [
            'name' => 'Updated Currency',
            'code' => $currency->code,
            'symbol' => 'U',
            'decimal_places' => '2',
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/master-data/currencies');
        $this->assertDatabaseHas('currencies', [
            'id' => $currency->id,
            'name' => 'Updated Currency',
        ]);
    }

    public function test_user_can_delete_currency(): void
    {
        $currency = Currency::factory()->create();

        $response = $this->delete('/admin/master-data/currencies/'.$currency->id);

        $response->assertRedirect('/admin/master-data/currencies');
        $this->assertSoftDeleted('currencies', [
            'id' => $currency->id,
        ]);
    }

    // Identification Types
    public function test_user_can_view_identification_types_page(): void
    {
        $response = $this->get('/admin/master-data/identification-types');

        $response->assertStatus(200)
            ->assertViewIs('admin.master-data.identification-types');
    }

    public function test_user_can_create_identification_type(): void
    {
        $response = $this->post('/admin/master-data/identification-types', [
            'name' => 'Test ID Type',
            'code' => 'TEST_ID',
            'description' => 'Test description',
            'issuing_authority' => 'Test Authority',
            'is_primary' => false,
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/master-data/identification-types');
        $this->assertDatabaseHas('identification_types', [
            'name' => 'Test ID Type',
            'code' => 'TEST_ID',
        ]);
    }

    public function test_user_can_edit_identification_type(): void
    {
        $type = IdentificationType::factory()->create();

        $response = $this->put('/admin/master-data/identification-types/'.$type->id, [
            'name' => 'Updated ID Type',
            'code' => $type->code,
            'description' => 'Updated description',
            'issuing_authority' => 'Updated Authority',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/master-data/identification-types');
        $this->assertDatabaseHas('identification_types', [
            'id' => $type->id,
            'name' => 'Updated ID Type',
        ]);
    }

    public function test_user_can_delete_identification_type(): void
    {
        $type = IdentificationType::factory()->create();

        $response = $this->delete('/admin/master-data/identification-types/'.$type->id);

        $response->assertRedirect('/admin/master-data/identification-types');
        $this->assertSoftDeleted('identification_types', [
            'id' => $type->id,
        ]);
    }

    // States
    public function test_user_can_view_states_page(): void
    {
        $response = $this->get('/admin/master-data/states');

        $response->assertStatus(200)
            ->assertViewIs('admin.master-data.all-states');
    }

    public function test_user_can_create_state(): void
    {
        $response = $this->post('/admin/master-data/states', [
            'country_id' => $this->country->id,
            'name' => 'Test State',
            'code' => 'TS',
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/master-data/states');
        $this->assertDatabaseHas('states', [
            'name' => 'Test State',
            'country_id' => $this->country->id,
        ]);
    }

    public function test_user_can_edit_state(): void
    {
        $state = State::factory()->create(['country_id' => $this->country->id]);

        $response = $this->put('/admin/master-data/states/'.$state->id, [
            'country_id' => $this->country->id,
            'name' => 'Updated State',
            'code' => 'US',
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/master-data/states');
        $this->assertDatabaseHas('states', [
            'id' => $state->id,
            'name' => 'Updated State',
        ]);
    }

    public function test_user_can_delete_state(): void
    {
        $state = State::factory()->create(['country_id' => $this->country->id]);

        $response = $this->delete('/admin/master-data/states/'.$state->id);

        $response->assertRedirect('/admin/master-data/states');
        $this->assertSoftDeleted('states', [
            'id' => $state->id,
        ]);
    }
}
