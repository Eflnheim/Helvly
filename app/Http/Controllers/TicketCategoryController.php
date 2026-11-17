<?php

namespace App\Http\Controllers;

use App\Models\TicketCategory;
use App\Http\Requests\StoreTicketCategoryRequest;
use App\Http\Requests\UpdateTicketCategoryRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TicketCategoryController extends Controller
{
    public function index(): Response
    {
        $categories = TicketCategory::query()
            ->latest()
            ->paginate(10);

        return Inertia::render('ticket-categories/index', [
            'categories' => $categories,
        ]);
    }

    public function store(StoreTicketCategoryRequest $request): RedirectResponse
    {
        TicketCategory::create($request->validated());

        return Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kategori tiket berhasil ditambahkan.',
        ])->back();
    }

    public function update(
        UpdateTicketCategoryRequest $request,
        TicketCategory $ticketCategory
    ): RedirectResponse {
        $ticketCategory->update($request->validated());

        return Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kategori tiket berhasil diperbarui.',
        ])->back();
    }

    public function deactivate(TicketCategory $ticketCategory): RedirectResponse
    {
        $ticketCategory->update([
            'is_active' => false,
        ]);

        return Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kategori tiket berhasil dinonaktifkan.',
        ])->back();
    }
}
