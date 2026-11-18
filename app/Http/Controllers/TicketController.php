<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketAttachment;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
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

    public function create(): InertiaResponse
    {
        Gate::authorize('create', Ticket::class);

        return Inertia::render('tickets/create', [
            'categories' => TicketCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $attachments = $validated['attachments'] ?? [];

        unset($validated['attachments']);

        $storedFiles = [];

        try {
            $ticket = DB::transaction(function () use (
                $request,
                $validated,
                $attachments,
                &$storedFiles
            ) {
                $ticket = Ticket::create([
                    'ticket_number' => 'TEMP-' . Str::random(20),
                    'requester_id' => $request->user()->id,
                    'assigned_to' => null,
                    ...$validated,
                    'status' => 'open',
                ]);

                $ticket->update([
                    'ticket_number' => sprintf(
                        'HLV-%s-%05d',
                        now()->format('Y'),
                        $ticket->id
                    ),
                ]);

                foreach ($attachments as $file) {
                    $path = $file->store('ticket-attachments', 'local');

                    if ($path === false) {
                        throw new \RuntimeException('Gagal menyimpan attachment.');
                    }

                    $storedFiles[] = $path;

                    TicketAttachment::create([
                        'ticket_id' => $ticket->id,
                        'comment_id' => null,
                        'uploaded_by' => $request->user()->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }

                $ticket->histories()->create([
                    'user_id' => $request->user()->id,
                    'action' => 'created',
                    'old_value' => null,
                    'new_value' => 'open',
                ]);

                return $ticket;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedFiles);

            throw $exception;
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Tiket berhasil dibuat.'
        ]);
        
        return to_route('tickets.index');
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
