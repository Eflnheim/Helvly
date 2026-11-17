<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TicketCommentPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TicketComment $comment): bool
    {
        if (! $user->is_active) {
            return false;
        }

        $canViewTicket = app(TicketPolicy::class)
            ->view($user, $comment->ticket);

        if (! $canViewTicket) {
            return false;
        }

        if ($comment->is_internal) {
            return $user->isAdmin() || $user->isAgent();
        }

        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Ticket $ticket): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return app(TicketPolicy::class)->view($user, $ticket);
    }

    /**
     * Determine whether the user can create an internal comment.
     */
    public function createInternal(User $user, Ticket $ticket): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if (! $user->isAdmin() && ! $user->isAgent()) {
            return false;
        }

        return app(TicketPolicy::class)->view($user, $ticket);
    }
}
