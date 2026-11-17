
<?php

use App\Models\Role;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->adminRole = Role::create([
        'name' => 'admin',
    ]);

    $this->admin = User::factory()->create([
        'role_id' => $this->adminRole->id,
        'is_active' => true,
    ]);
});

test('admin can view ticket categories', function () {
    TicketCategory::create([
        'name' => 'Hardware',
        'description' => 'Masalah perangkat keras.',
    ]);

    $this->actingAs($this->admin)
        ->get(route('ticket-categories.index'))
        ->assertOk()
        ->assertInertia(
            fn(Assert $page) => $page
                ->component('ticket-categories/index', false)
                ->has('categories.data', 1)
                ->where('categories.data.0.name', 'Hardware')
        );
});

test('admin can create a ticket category', function () {
    $this->actingAs($this->admin)
        ->post(route('ticket-categories.store'), [
            'name' => 'Printer',
            'description' => 'Masalah yang berkaitan dengan printer.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('ticket_categories', [
        'name' => 'Printer',
        'description' => 'Masalah yang berkaitan dengan printer.',
        'is_active' => true,
    ]);
});

test('admin cannot create a ticket category with duplicate name', function () {
    TicketCategory::create([
        'name' => 'Hardware',
        'description' => 'Kategori perangkat keras.',
    ]);

    $this->actingAs($this->admin)
        ->post(route('ticket-categories.store'), [
            'name' => 'Hardware',
            'description' => 'Kategori baru.',
        ])
        ->assertSessionHasErrors('name');

    $this->assertDatabaseCount('ticket_categories', 1);
});

test('admin can update a ticket category', function () {
    $category = TicketCategory::create([
        'name' => 'Printer',
        'description' => 'Kategori printer.',
    ]);

    $this->actingAs($this->admin)
        ->put(route('ticket-categories.update', $category), [
            'name' => 'Printers',
            'description' => 'Masalah perangkat printer.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('ticket_categories', [
        'id' => $category->id,
        'name' => 'Printers',
        'description' => 'Masalah perangkat printer.',
    ]);
});

test('admin can deactivate a ticket category', function () {
    $category = TicketCategory::create([
        'name' => 'Printer',
        'description' => 'Kategori printer.',
    ]);

    $this->actingAs($this->admin)
        ->patch(route('ticket-categories.deactivate', $category))
        ->assertRedirect();

    $this->assertDatabaseHas('ticket_categories', [
        'id' => $category->id,
        'is_active' => false,
    ]);
});

test('requester cannot manage ticket categories', function () {
    $requesterRole = Role::create([
        'name' => 'requester',
    ]);

    $requester = User::factory()->create([
        'role_id' => $requesterRole->id,
        'is_active' => true,
    ]);

    $this->actingAs($requester)
        ->get(route('ticket-categories.index'))
        ->assertForbidden();

    $this->actingAs($requester)
        ->post(route('ticket-categories.store'), [
            'name' => 'Printer',
            'description' => 'Kategori printer.',
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('ticket_categories', 0);
});

test('agent cannot manage ticket categories', function () {
    $agentRole = Role::create([
        'name' => 'agent',
    ]);

    $agent = User::factory()->create([
        'role_id' => $agentRole->id,
        'is_active' => true,
    ]);

    $this->actingAs($agent)
        ->get(route('ticket-categories.index'))
        ->assertForbidden();

    $this->actingAs($agent)
        ->post(route('ticket-categories.store'), [
            'name' => 'Printer',
            'description' => 'Kategori printer.',
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('ticket_categories', 0);
});

test('admin cannot update a ticket category with duplicate name', function () {
    $hardware = TicketCategory::create([
        'name' => 'Hardware',
        'description' => 'Kategori perangkat keras.',
    ]);

    $software = TicketCategory::create([
        'name' => 'Software',
        'description' => 'Kategori perangkat lunak.',
    ]);

    $this->actingAs($this->admin)
        ->put(route('ticket-categories.update', $software), [
            'name' => 'Hardware',
            'description' => 'Deskripsi baru.',
        ])
        ->assertSessionHasErrors('name');

    $this->assertDatabaseHas('ticket_categories', [
        'id' => $software->id,
        'name' => 'Software',
    ]);
});

test('requester cannot deactivate a ticket category', function () {
    $category = TicketCategory::create([
        'name' => 'Printer',
        'description' => 'Kategori printer.',
    ]);

    $requesterRole = Role::create([
        'name' => 'requester',
    ]);

    $requester = User::factory()->create([
        'role_id' => $requesterRole->id,
        'is_active' => true,
    ]);

    $this->actingAs($requester)
        ->patch(route('ticket-categories.deactivate', $category))
        ->assertForbidden();

    $this->assertDatabaseHas('ticket_categories', [
        'id' => $category->id,
        'is_active' => true,
    ]);
});

test('inactive admin cannot manage ticket categories', function () {
    $inactiveAdmin = User::factory()->create([
        'role_id' => $this->adminRole->id,
        'is_active' => false,
    ]);

    $this->actingAs($inactiveAdmin)
        ->get(route('ticket-categories.index'))
        ->assertForbidden();
});