
<?php

use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Models\TicketComment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'inertia.testing.ensure_pages_exist' => false,
    ]);
});

test('admin can view all tickets', function () {
    $adminRole = Role::create([
        'name' => 'admin',
    ]);

    $requesterRole = Role::create([
        'name' => 'requester',
    ]);

    $category = TicketCategory::create([
        'name' => 'Perangkat Keras',
        'description' => 'Masalah perangkat keras',
        'is_active' => true,
    ]);

    $admin = User::factory()->create([
        'role_id' => $adminRole->id,
        'is_active' => true,
    ]);

    $requesterOne = User::factory()->create([
        'role_id' => $requesterRole->id,
        'is_active' => true,
    ]);

    $requesterTwo = User::factory()->create([
        'role_id' => $requesterRole->id,
        'is_active' => true,
    ]);

    $ticketOne = Ticket::create([
        'ticket_number' => 'HLV-2026-00001',
        'requester_id' => $requesterOne->id,
        'category_id' => $category->id,
        'subject' => 'Komputer tidak bisa menyala',
        'description' => 'Komputer tidak merespons saat tombol power ditekan.',
        'priority' => 'high',
        'status' => 'open',
    ]);

    $ticketTwo = Ticket::create([
        'ticket_number' => 'HLV-2026-00002',
        'requester_id' => $requesterTwo->id,
        'category_id' => $category->id,
        'subject' => 'Printer tidak terdeteksi',
        'description' => 'Printer tidak muncul pada daftar perangkat.',
        'priority' => 'medium',
        'status' => 'open',
    ]);

    $this->actingAs($admin)
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tickets/index')
            ->has('tickets.data', 2)
            ->where('tickets.data.0.id', $ticketTwo->id)
            ->where('tickets.data.1.id', $ticketOne->id)
        );
});


test('agent can only view assigned and unassigned tickets', function () {
    $agentRole = Role::create([
        'name' => 'agent',
    ]);

    $requesterRole = Role::create([
        'name' => 'requester',
    ]);

    $agentOne = User::factory()->create([
        'role_id' => $agentRole->id,
        'is_active' => true,
    ]);

    $agentTwo = User::factory()->create([
        'role_id' => $agentRole->id,
        'is_active' => true,
    ]);

    $requester = User::factory()->create([
        'role_id' => $requesterRole->id,
        'is_active' => true,
    ]);

    $category = TicketCategory::create([
        'name' => 'Jaringan',
        'description' => 'Masalah jaringan',
        'is_active' => true,
    ]);

    $assignedToAgentOne = Ticket::create([
        'ticket_number' => 'HLV-2026-00001',
        'requester_id' => $requester->id,
        'assigned_to' => $agentOne->id,
        'category_id' => $category->id,
        'subject' => 'Internet lambat',
        'description' => 'Koneksi internet sangat lambat.',
        'priority' => 'high',
        'status' => 'in_progress',
    ]);

    $assignedToAgentTwo = Ticket::create([
        'ticket_number' => 'HLV-2026-00002',
        'requester_id' => $requester->id,
        'assigned_to' => $agentTwo->id,
        'category_id' => $category->id,
        'subject' => 'WiFi terputus',
        'description' => 'Koneksi WiFi sering terputus.',
        'priority' => 'medium',
        'status' => 'in_progress',
    ]);

    $unassignedTicket = Ticket::create([
        'ticket_number' => 'HLV-2026-00003',
        'requester_id' => $requester->id,
        'assigned_to' => null,
        'category_id' => $category->id,
        'subject' => 'Tidak bisa mengakses server',
        'description' => 'Server tidak dapat diakses.',
        'priority' => 'urgent',
        'status' => 'open',
    ]);

    $this->actingAs($agentOne)
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tickets/index')
            ->has('tickets.data', 2)
            ->where('tickets.data.0.id', $unassignedTicket->id)
            ->where('tickets.data.1.id', $assignedToAgentOne->id)
            ->missing('tickets.data.2')
        );
});


test('requester can only view their own tickets', function () {
    $requesterRole = Role::create([
        'name' => 'requester',
    ]);

    $category = TicketCategory::create([
        'name' => 'Aplikasi',
        'description' => 'Masalah aplikasi',
        'is_active' => true,
    ]);

    $requesterOne = User::factory()->create([
        'role_id' => $requesterRole->id,
        'is_active' => true,
    ]);

    $requesterTwo = User::factory()->create([
        'role_id' => $requesterRole->id,
        'is_active' => true,
    ]);

    $ownTicket = Ticket::create([
        'ticket_number' => 'HLV-2026-00001',
        'requester_id' => $requesterOne->id,
        'category_id' => $category->id,
        'subject' => 'Aplikasi tidak bisa dibuka',
        'description' => 'Aplikasi menampilkan halaman kosong.',
        'priority' => 'high',
        'status' => 'open',
    ]);

    Ticket::create([
        'ticket_number' => 'HLV-2026-00002',
        'requester_id' => $requesterTwo->id,
        'category_id' => $category->id,
        'subject' => 'Aplikasi mengalami error',
        'description' => 'Aplikasi menampilkan pesan error.',
        'priority' => 'medium',
        'status' => 'open',
    ]);

    $this->actingAs($requesterOne)
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tickets/index')
            ->has('tickets.data', 1)
            ->where('tickets.data.0.id', $ownTicket->id)
        );
});


test('only requester can create tickets', function () {
    $adminRole = Role::create([
        'name' => 'admin',
    ]);

    $agentRole = Role::create([
        'name' => 'agent',
    ]);

    $requesterRole = Role::create([
        'name' => 'requester',
    ]);

    $admin = User::factory()->create([
        'role_id' => $adminRole->id,
        'is_active' => true,
    ]);

    $agent = User::factory()->create([
        'role_id' => $agentRole->id,
        'is_active' => true,
    ]);

    $requester = User::factory()->create([
        'role_id' => $requesterRole->id,
        'is_active' => true,
    ]);

    $category = TicketCategory::create([
        'name' => 'Perangkat Lunak',
        'description' => 'Masalah perangkat lunak',
        'is_active' => true,
    ]);

    $ticketData = [
        'category_id' => $category->id,
        'subject' => 'Aplikasi mengalami error',
        'description' => 'Aplikasi tidak dapat dibuka.',
        'priority' => 'high',
    ];

    $this->actingAs($admin)
        ->post(route('tickets.store'), $ticketData)
        ->assertForbidden();

    $this->actingAs($agent)
        ->post(route('tickets.store'), $ticketData)
        ->assertForbidden();

    $this->actingAs($requester)
        ->post(route('tickets.store'), $ticketData)
        ->assertRedirect();

    expect(Ticket::count())->toBe(1);
});


test('ticket number is generated automatically when requester creates a ticket', function () {
    $requesterRole = Role::create([
        'name' => 'requester',
    ]);

    $requester = User::factory()->create([
        'role_id' => $requesterRole->id,
        'is_active' => true,
    ]);

    $category = TicketCategory::create([
        'name' => 'Jaringan',
        'description' => 'Masalah jaringan',
        'is_active' => true,
    ]);

    $this->actingAs($requester)
        ->post(route('tickets.store'), [
            'category_id' => $category->id,
            'subject' => 'Internet tidak terhubung',
            'description' => 'Komputer tidak dapat terhubung ke internet.',
            'priority' => 'high',
            'ticket_number' => 'HLV-9999-99999',
        ])
        ->assertRedirect();

    $ticket = Ticket::first();

    expect($ticket)->not->toBeNull()
        ->and($ticket->ticket_number)->toBe(
            'HLV-' . now()->format('Y') . '-00001'
        );
});


test('new ticket always has open status', function () {
    $requesterRole = Role::create([
        'name' => 'requester',
    ]);

    $requester = User::factory()->create([
        'role_id' => $requesterRole->id,
        'is_active' => true,
    ]);

    $category = TicketCategory::create([
        'name' => 'Perangkat Keras',
        'description' => 'Masalah perangkat keras',
        'is_active' => true,
    ]);

    $this->actingAs($requester)
        ->post(route('tickets.store'), [
            'category_id' => $category->id,
            'subject' => 'Keyboard tidak berfungsi',
            'description' => 'Beberapa tombol keyboard tidak merespons.',
            'priority' => 'medium',
            'status' => 'closed',
        ])
        ->assertRedirect();

    $ticket = Ticket::first();

    expect($ticket)->not->toBeNull()
        ->and($ticket->status)->toBe('open');
});


test('requester cannot view another users ticket', function () {
    $requesterRole = Role::create([
        'name' => 'requester',
    ]);

    $category = TicketCategory::create([
        'name' => 'Aplikasi',
        'description' => 'Masalah aplikasi',
        'is_active' => true,
    ]);

    $requesterOne = User::factory()->create([
        'role_id' => $requesterRole->id,
        'is_active' => true,
    ]);

    $requesterTwo = User::factory()->create([
        'role_id' => $requesterRole->id,
        'is_active' => true,
    ]);

    $ticket = Ticket::create([
        'ticket_number' => 'HLV-2026-00001',
        'requester_id' => $requesterTwo->id,
        'category_id' => $category->id,
        'subject' => 'Komputer mengalami kerusakan',
        'description' => 'Komputer tidak dapat digunakan.',
        'priority' => 'high',
        'status' => 'open',
    ]);

    $this->actingAs($requesterOne)
        ->get(route('tickets.show', $ticket))
        ->assertForbidden();
});


test('requester cannot view internal comments', function () {
    $requesterRole = Role::create([
        'name' => 'requester',
    ]);

    $agentRole = Role::create([
        'name' => 'agent',
    ]);

    $requester = User::factory()->create([
        'role_id' => $requesterRole->id,
        'is_active' => true,
    ]);

    $agent = User::factory()->create([
        'role_id' => $agentRole->id,
        'is_active' => true,
    ]);

    $category = TicketCategory::create([
        'name' => 'Jaringan',
        'description' => 'Masalah jaringan',
        'is_active' => true,
    ]);

    $ticket = Ticket::create([
        'ticket_number' => 'HLV-2026-00001',
        'requester_id' => $requester->id,
        'category_id' => $category->id,
        'subject' => 'Internet tidak stabil',
        'description' => 'Koneksi internet sering terputus.',
        'priority' => 'high',
        'status' => 'open',
    ]);

    TicketComment::create([
        'ticket_id' => $ticket->id,
        'user_id' => $agent->id,
        'message' => 'Informasi yang boleh dilihat requester.',
        'is_internal' => false,
    ]);

    TicketComment::create([
        'ticket_id' => $ticket->id,
        'user_id' => $agent->id,
        'message' => 'Catatan internal untuk tim teknis.',
        'is_internal' => true,
    ]);

    $this->actingAs($requester)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tickets/show')
            ->has('ticket.comments', 1)
            ->where(
                'ticket.comments.0.message',
                'Informasi yang boleh dilihat requester.'
            )
        );
});


test('requester cannot update another users ticket', function () {
    $requesterRole = Role::create([
        'name' => 'requester',
    ]);

    $category = TicketCategory::create([
        'name' => 'Aplikasi',
        'description' => 'Masalah aplikasi',
        'is_active' => true,
    ]);

    $requesterOne = User::factory()->create([
        'role_id' => $requesterRole->id,
        'is_active' => true,
    ]);

    $requesterTwo = User::factory()->create([
        'role_id' => $requesterRole->id,
        'is_active' => true,
    ]);

    $ticket = Ticket::create([
        'ticket_number' => 'HLV-2026-00001',
        'requester_id' => $requesterTwo->id,
        'category_id' => $category->id,
        'subject' => 'Aplikasi tidak bisa dibuka',
        'description' => 'Aplikasi menampilkan halaman kosong.',
        'priority' => 'medium',
        'status' => 'open',
    ]);

    $this->actingAs($requesterOne)
        ->put(route('tickets.update', $ticket), [
            'category_id' => $category->id,
            'subject' => 'Subject diubah oleh user lain',
            'description' => 'Deskripsi diubah tanpa izin.',
            'priority' => 'urgent',
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'subject' => 'Aplikasi tidak bisa dibuka',
        'priority' => 'medium',
    ]);
});

test('admin can update ticket and records changes in history', function () {
    $adminRole = Role::create([
        'name' => 'admin',
    ]);

    $requesterRole = Role::create([
        'name' => 'requester',
    ]);

    $admin = User::factory()->create([
        'role_id' => $adminRole->id,
        'is_active' => true,
    ]);

    $requester = User::factory()->create([
        'role_id' => $requesterRole->id,
        'is_active' => true,
    ]);

    $category = TicketCategory::create([
        'name' => 'Aplikasi',
        'description' => 'Masalah aplikasi',
        'is_active' => true,
    ]);

    $ticket = Ticket::create([
        'ticket_number' => 'HLV-2026-00001',
        'requester_id' => $requester->id,
        'category_id' => $category->id,
        'subject' => 'Aplikasi tidak bisa dibuka',
        'description' => 'Aplikasi menampilkan halaman kosong.',
        'priority' => 'medium',
        'status' => 'open',
    ]);

    $newDescription = str_repeat('Deskripsi masalah yang diperbarui. ', 10);

    $newDescription = trim($newDescription);

    $this->actingAs($admin)
        ->put(route('tickets.update', $ticket), [
            'category_id' => $category->id,
            'subject' => 'Aplikasi mengalami error',
            'description' => $newDescription,
            'priority' => 'urgent',
        ])
        ->assertRedirect();

    $ticket->refresh();

    expect($ticket->subject)->toBe('Aplikasi mengalami error')
        ->and($ticket->description)->toBe($newDescription)
        ->and($ticket->priority)->toBe('urgent');

    $this->assertDatabaseHas('ticket_histories', [
        'ticket_id' => $ticket->id,
        'user_id' => $admin->id,
        'action' => 'updated',
        'old_value' => 'subject: Aplikasi tidak bisa dibuka',
        'new_value' => 'subject: Aplikasi mengalami error',
    ]);

    $this->assertDatabaseHas('ticket_histories', [
        'ticket_id' => $ticket->id,
        'user_id' => $admin->id,
        'action' => 'updated',
        'old_value' => 'priority: medium',
        'new_value' => 'priority: urgent',
    ]);

    $this->assertDatabaseHas('ticket_histories', [
        'ticket_id' => $ticket->id,
        'user_id' => $admin->id,
        'action' => 'updated',
        'old_value' => 'description: Aplikasi menampilkan halaman kosong.',
        'new_value' => "description: {$newDescription}",
    ]);
});