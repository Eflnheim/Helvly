export type TicketStatus = 'open' | 'in_progress' | 'resolved' | 'closed';

export type TicketPriority = 'low' | 'medium' | 'high' | 'urgent';

export type TicketUser = {
    id: number;
    name: string;
    email: string;
};

export type TicketCategory = {
    id: number;
    name: string;
};

export type Ticket = {
    id: number;
    ticket_number: string;
    subject: string;
    description: string;
    priority: TicketPriority;
    status: TicketStatus;
    requester: TicketUser;
    assigned_agent: TicketUser | null;
    category: TicketCategory;
    created_at: string;
    can_update: boolean;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type PaginatedData<T> = {
    data: T[];
    current_page: number;
    from: number | null;
    to: number | null;
    total: number;
    last_page: number;
    links: PaginationLink[];
};

export type TicketFilters = {
    search?: string;
    status?: TicketStatus;
    priority?: TicketPriority;
    category?: string;
};

export type TicketCategoryOption = {
    id: number;
    name: string;
};

export type TicketIndexProps = {
    tickets: PaginatedData<Ticket>;
    filters: TicketFilters;
    categories: TicketCategoryOption[];
    can_create: boolean;
};