<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TicketController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        Gate::authorize('viewAny', Ticket::class);

        $user = $request->user();

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:open,in_progress,resolved,closed'],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'category' => ['nullable', 'integer', 'exists:ticket_categories,id'],
        ]);

        $tickets = Ticket::query()
            ->with(['requester', 'assignedAgent', 'category'])

            // Pembatasan berdasarkan role
            ->when($user->isAgent(), function ($query) use ($user) {
                $query->where(function ($query) use ($user) {
                    $query->where('assigned_to', $user->id)
                        ->orWhereNull('assigned_to');
                });
            })
            ->when($user->isRequester(), function ($query) use ($user) {
                $query->where('requester_id', $user->id);
            })

            // Pencarian nomor tiket dan subject
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('ticket_number', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })

            // Filter status
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })

            // Filter priority
            ->when($filters['priority'] ?? null, function ($query, $priority) {
                $query->where('priority', $priority);
            })

            // Filter kategori
            ->when($filters['category'] ?? null, function ($query, $category) {
                $query->where('category_id', $category);
            })

            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $tickets->getCollection()->each(function ($ticket) use ($user) {
            $ticket->setAttribute(
                'can_update',
                $user->can('update', $ticket)
            );
        });

        return Inertia::render('tickets/index', [
            'tickets' => $tickets,
            'filters' => $filters,
            'can_create' => $user->can('create', Ticket::class),
            'categories' => TicketCategory::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(StoreTicketRequest $request)
    {
        $ticket = DB::transaction(function () use ($request) {
            $ticket = Ticket::create([
                'ticket_number' => 'TEMP-' . Str::random(20),
                'requester_id' => $request->user()->id,
                'assigned_to' => null,
                ...$request->validated(),
                'status' => 'open',
            ]);

            $ticket->update([
                'ticket_number' => sprintf(
                    'HLV-%s-%05d',
                    now()->format('Y'),
                    $ticket->id
                ),
            ]);

            $ticket->histories()->create([
                'user_id' => $request->user()->id,
                'action' => 'created',
                'old_value' => null,
                'new_value' => 'open',
            ]);

            return $ticket;
        });

        return Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Tiket berhasil dibuat.',
        ])->back();
    }

    public function show(Ticket $ticket): InertiaResponse
    {
        Gate::authorize('view', $ticket);

        $user = request()->user();

        $ticket->load([
            'requester',
            'assignedAgent',
            'category',

            'comments' => function ($query) use ($user) {
                if ($user->isRequester()) {
                    $query->where('is_internal', false);
                }

                $query->with([
                    'user',
                    'attachments.uploader',
                ]);
            },

            'attachments' => function ($query) use ($user) {
                if ($user->isRequester()) {
                    $query->where(function ($query) {
                        $query->whereNull('comment_id')
                            ->orWhereHas('comment', function ($query) {
                                $query->where('is_internal', false);
                            });
                    });
                }

                $query->with('uploader');
            },

            'histories.user',
        ]);

        return Inertia::render('tickets/show', [
            'ticket' => $ticket,
        ]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
        DB::transaction(function () use ($request, $ticket) {
            $oldValues = $ticket->only([
                'category_id',
                'subject',
                'description',
                'priority',
            ]);

            $newValues = $request->validated();

            $ticket->update($newValues);

            foreach ($newValues as $field => $newValue) {
                $oldValue = $oldValues[$field];

                if ((string) $oldValue === (string) $newValue) {
                    continue;
                }

                $ticket->histories()->create([
                    'user_id' => $request->user()->id,
                    'action' => 'updated',
                    'old_value' => "{$field}: {$oldValue}",
                    'new_value' => "{$field}: {$newValue}",
                ]);
            }
        });

        return Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Tiket berhasil diperbarui.',
        ])->back();
    }
}
