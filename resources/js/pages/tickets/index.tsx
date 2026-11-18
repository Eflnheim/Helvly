import { Head, Link, router, usePage } from '@inertiajs/react';
import { Eye, Pencil, RotateCcw, Search, SlidersHorizontal } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import { cn } from '@/lib/utils';

import { dashboard } from '@/routes';

import type { Auth } from '@/types/auth';
import type { BreadcrumbItem } from '@/types/navigation';
import type { TicketIndexProps } from '@/types/ticket';

export default function TicketsIndex({
    tickets,
    can_create,
    filters,
    categories,
}: TicketIndexProps) {
    const { auth } = usePage<{ auth: Auth }>().props;

    const isRequester = auth.user.role.name === 'requester';

    // States
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? 'all');
    const [priority, setPriority] = useState(filters.priority ?? 'all');
    const [category, setCategory] = useState(filters.category ?? 'all');

    const searchTimeout = useRef<ReturnType<typeof setTimeout> | null>(null);

    // Cleanup search timeout
    useEffect(() => {
        return () => {
            if (searchTimeout.current) {
                clearTimeout(searchTimeout.current);
            }
        };
    }, []);

    // Search
    const handleSearch = (value: string) => {
        setSearch(value);

        if (searchTimeout.current) {
            clearTimeout(searchTimeout.current);
        }

        searchTimeout.current = setTimeout(() => {
            router.get(
                '/tickets',
                {
                    search: value || undefined,
                    status: status === 'all' ? undefined : status,
                    priority: priority === 'all' ? undefined : priority,
                    category: category === 'all' ? undefined : category,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
        }, 700);
    };

    // Filters
    const handleFilter = (
        key: 'status' | 'priority' | 'category',
        value: string,
    ) => {
        const nextFilters = {
            search: search || undefined,
            status: status === 'all' ? undefined : status,
            priority: priority === 'all' ? undefined : priority,
            category: category === 'all' ? undefined : category,
            [key]: value === 'all' ? undefined : value,
        };

        if (searchTimeout.current) {
            clearTimeout(searchTimeout.current);
        }

        if (key === 'status') setStatus(value as typeof status);
        if (key === 'priority') setPriority(value as typeof priority);
        if (key === 'category') setCategory(value);

        router.get('/tickets', nextFilters, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    // Reset filters
    const handleResetFilters = () => {
        if (searchTimeout.current) {
            clearTimeout(searchTimeout.current);
        }

        setSearch('');
        setStatus('all');
        setPriority('all');
        setCategory('all');

        router.get(
            '/tickets',
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };
    return (
        <>
            <Head title="Daftar Tiket" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Page Header */}
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div className="space-y-1">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Daftar Tiket
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Kelola dan pantau seluruh permintaan bantuan.
                        </p>
                    </div>

                    {can_create && (
                        <Button asChild>
                            <Link href="/tickets/create">Buat Tiket</Link>
                        </Button>
                    )}
                </div>

                {/* Search & Filters */}
                <div className="rounded-lg border border-sidebar-border/70 bg-muted/30 p-4">
                    <div className="mb-4 flex items-center gap-2">
                        <SlidersHorizontal className="size-4 text-muted-foreground" />
                        <h2 className="text-sm font-semibold">Filter Tiket</h2>
                    </div>

                    <div className="flex flex-col gap-4 xl:flex-row xl:items-end">
                        {/* Search */}
                        <div className="flex-1 space-y-2">
                            <Label htmlFor="ticket-search">Pencarian</Label>
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    id="ticket-search"
                                    value={search}
                                    onChange={(e) => handleSearch(e.target.value)}
                                    placeholder="Nomor tiket atau subjek..."
                                    className="w-full pl-9"
                                />
                            </div>
                        </div>

                        {/* Status */}
                        <div className="w-full space-y-2 xl:w-44">
                            <Label htmlFor="ticket-status">Status</Label>
                            <Select
                                value={status}
                                onValueChange={(value) => handleFilter('status', value)}
                            >
                                <SelectTrigger id="ticket-status" className="w-full">
                                    <SelectValue placeholder="Semua Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Status</SelectItem>
                                    <SelectItem value="open">Open</SelectItem>
                                    <SelectItem value="in_progress">In Progress</SelectItem>
                                    <SelectItem value="resolved">Resolved</SelectItem>
                                    <SelectItem value="closed">Closed</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {/* Priority */}
                        <div className="w-full space-y-2 xl:w-44">
                            <Label htmlFor="ticket-priority">Prioritas</Label>
                            <Select
                                value={priority}
                                onValueChange={(value) => handleFilter('priority', value)}
                            >
                                <SelectTrigger id="ticket-priority" className="w-full">
                                    <SelectValue placeholder="Semua Prioritas" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Prioritas</SelectItem>
                                    <SelectItem value="low">Low</SelectItem>
                                    <SelectItem value="medium">Medium</SelectItem>
                                    <SelectItem value="high">High</SelectItem>
                                    <SelectItem value="urgent">Urgent</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {/* Category */}
                        <div className="w-full space-y-2 xl:w-48">
                            <Label htmlFor="ticket-category">Kategori</Label>
                            <Select
                                value={category}
                                onValueChange={(value) => handleFilter('category', value)}
                            >
                                <SelectTrigger id="ticket-category" className="w-full">
                                    <SelectValue placeholder="Semua Kategori" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Kategori</SelectItem>
                                    {categories.map((item) => (
                                        <SelectItem key={item.id} value={String(item.id)}>
                                            {item.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        {/* Reset */}
                        <div>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={handleResetFilters}
                                className="w-full xl:w-auto sm:w-auto"
                            >
                                <RotateCcw className="size-4" />
                                Reset Filter
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Ticket Table */}
                <div className="overflow-hidden rounded-lg border border-sidebar-border/70">
                    <div className="overflow-x-auto lg:overflow-x-visible">
                        <Table>
                            <TableHeader className="bg-muted">
                                <TableRow className="hover:bg-transparent">
                                    <TableHead className="pl-4 pr-2 py-4 font-semibold">
                                        ID Tiket
                                    </TableHead>

                                    <TableHead className="px-2 font-semibold">
                                        Subjek
                                    </TableHead>

                                    <TableHead className="px-2 font-semibold">
                                        Kategori
                                    </TableHead>

                                    <TableHead className="px-2 font-semibold">
                                        Prioritas
                                    </TableHead>

                                    <TableHead className="px-2 font-semibold">
                                        Status
                                    </TableHead>

                                    {!isRequester && (
                                        <TableHead className="px-2 font-semibold">
                                            Requester
                                        </TableHead>
                                    )}

                                    <TableHead className="px-2 font-semibold">
                                        Agent
                                    </TableHead>

                                    <TableHead className="pr-4 pl-2 font-semibold text-center">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                {tickets.data.length > 0 ? (
                                    tickets.data.map((ticket) => (
                                        <TableRow key={ticket.id}>
                                            <TableCell className="pl-4 pr-2 py-2 font-medium">
                                                <Link
                                                    href={`/tickets/${ticket.id}`}
                                                    className="text-primary hover:underline"
                                                >
                                                    {ticket.ticket_number}
                                                </Link>
                                            </TableCell>

                                            <TableCell className="max-w-48 px-2 py-2">
                                                <Link
                                                    href={`/tickets/${ticket.id}`}
                                                    className="block truncate font-medium hover:underline"
                                                    title={ticket.subject}
                                                >
                                                    {ticket.subject}
                                                </Link>
                                            </TableCell>

                                            <TableCell className="px-2 py-2">
                                                {ticket.category.name}
                                            </TableCell>

                                            <TableCell className="px-2 py-2">
                                                <span
                                                    className={cn(
                                                        'inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize',
                                                        ticket.priority === 'low' && 'bg-secondary text-secondary-foreground',
                                                        ticket.priority === 'medium' && 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
                                                        ticket.priority === 'high' && 'bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-300',
                                                        ticket.priority === 'urgent' &&
                                                        'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300'
                                                    )}
                                                >
                                                    {ticket.priority}
                                                </span>
                                            </TableCell>

                                            <TableCell className="px-2 py-2">
                                                <span
                                                    className={cn(
                                                        'inline-flex rounded-full px-2.5 py-1 text-xs font-medium',
                                                        ticket.status === 'open' && 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
                                                        ticket.status === 'in_progress' && 'bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-300',
                                                        ticket.status === 'resolved' && 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300',
                                                        ticket.status === 'closed' && 'bg-secondary text-secondary-foreground',
                                                    )}
                                                >
                                                    {ticket.status.replace('_', ' ')}
                                                </span>
                                            </TableCell>

                                            {!isRequester && (
                                                <TableCell className="max-w-28 px-2 py-2">
                                                    <span className="block truncate" title={ticket.requester.name}>
                                                        {ticket.requester.name}
                                                    </span>
                                                </TableCell>
                                            )}

                                            <TableCell className="max-w-28 px-2 py-2">
                                                <span
                                                    className="block truncate"
                                                    title={ticket.assigned_agent?.name ?? '-'}
                                                >
                                                    {ticket.assigned_agent?.name ?? '-'}
                                                </span>
                                            </TableCell>

                                            <TableCell className="pr-4 pl-2 py-2">
                                                <div className="flex items-center justify-center gap-1">
                                                    <Button
                                                        variant="outline"
                                                        size="icon"
                                                        className="size-8 bg-muted/50 hover:bg-muted"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/tickets/${ticket.id}`}
                                                            title="Lihat tiket"
                                                            aria-label="Lihat tiket"
                                                        >
                                                            <Eye className="size-4" />
                                                        </Link>
                                                    </Button>

                                                    {ticket.can_update && (
                                                        <Button
                                                            variant="outline"
                                                            size="icon"
                                                            className="size-8 bg-muted/50 hover:bg-muted"
                                                            title="Edit tiket"
                                                            aria-label="Edit tiket"
                                                        >
                                                            <Pencil className="size-4" />
                                                        </Button>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell
                                            colSpan={isRequester ? 7 : 8}
                                            className="h-24 text-center text-muted-foreground"
                                        >
                                            Belum ada tiket.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Pagination */}
                    <div className="flex flex-col gap-4 border-t border-sidebar-border/70 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-sm text-muted-foreground">
                            Menampilkan {tickets.from ?? 0}–{tickets.to ?? 0} dari{' '}
                            {tickets.total} tiket
                        </p>

                        {tickets.last_page > 1 && (
                            <Pagination className="mx-0 w-auto justify-end">
                                <PaginationContent>
                                    {tickets.links.map((link, index) => {
                                        const isPrevious = link.label.includes('Previous');
                                        const isNext = link.label.includes('Next');

                                        return (
                                            <PaginationItem key={index}>
                                                {isPrevious ? (
                                                    <PaginationPrevious
                                                        href={link.url ?? '#'}
                                                        onClick={(event) => {
                                                            event.preventDefault();

                                                            if (link.url) {
                                                                router.visit(link.url, {
                                                                    preserveScroll: true,
                                                                });
                                                            }
                                                        }}
                                                        aria-disabled={!link.url}
                                                        className={!link.url ? 'pointer-events-none opacity-50' : ''}
                                                    />
                                                ) : isNext ? (
                                                    <PaginationNext
                                                        href={link.url ?? '#'}
                                                        onClick={(event) => {
                                                            event.preventDefault();

                                                            if (link.url) {
                                                                router.visit(link.url, {
                                                                    preserveScroll: true,
                                                                });
                                                            }
                                                        }}
                                                        aria-disabled={!link.url}
                                                        className={!link.url ? 'pointer-events-none opacity-50' : ''}
                                                    />
                                                ) : link.label.includes('...') ? (
                                                    <PaginationEllipsis />
                                                ) : (
                                                    <PaginationLink
                                                        href={link.url ?? '#'}
                                                        isActive={link.active}
                                                        onClick={(event) => {
                                                            event.preventDefault();

                                                            if (link.url) {
                                                                router.visit(link.url, {
                                                                    preserveScroll: true,
                                                                });
                                                            }
                                                        }}
                                                        aria-label={`Halaman ${link.label}`}
                                                    >
                                                        {link.label}
                                                    </PaginationLink>
                                                )}
                                            </PaginationItem>
                                        );
                                    })}
                                </PaginationContent>
                            </Pagination>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

TicketsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Daftar Tiket',
            href: '/tickets',
        },
    ] satisfies BreadcrumbItem[],
};
