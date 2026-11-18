<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgent()) {
            return $ticket->assigned_to === $user->id
                || $ticket->assigned_to === null;
        }

        if ($user->isRequester()) {
            return $ticket->requester_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->is_active && $user->isRequester();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgent()) {
            return $ticket->assigned_to === $user->id;
        }

        if ($user->isRequester()) {
            return $ticket->requester_id === $user->id
                && $ticket->status === 'open';
        }

        return false;
    }

    /**
     * Determine whether the user can assign the ticket.
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    /**
     * Determine whether the user can claim the ticket.
     */
    public function claim(User $user, Ticket $ticket): bool
    {
        if (! $user->is_active || ! $user->isAgent()) {
            return false;
        }

        return $ticket->assigned_to === null
            && $ticket->status === 'open';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        return false;
    }
}
