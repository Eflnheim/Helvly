import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';

import { dashboard } from '@/routes';

import type { BreadcrumbItem } from '@/types/navigation';
import type {
    PaginatedData,
    TicketCategory,
} from '@/types/ticket-category';
import { cn } from '@/lib/utils';

type Props = {
    categories: PaginatedData<TicketCategory>;
};

export default function TicketCategoriesIndex({ categories }: Props) {
    const [isDialogOpen, setIsDialogOpen] = useState(false);

    const [selectedCategory, setSelectedCategory] =
        useState<TicketCategory | null>(null);

    const [categoryToDeactivate, setCategoryToDeactivate] =
        useState<TicketCategory | null>(null);

    const { data, setData, post, put, patch, processing, errors, reset, clearErrors } = useForm({
        name: '',
        description: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (selectedCategory) {
            put(`/ticket-categories/${selectedCategory.id}`, {
                onSuccess: () => {
                    reset();
                    setSelectedCategory(null);
                    setIsDialogOpen(false);
                },
            });
        } else {
            post('/ticket-categories', {
                onSuccess: () => {
                    reset();
                    setIsDialogOpen(false);
                },
            });
        }
    };

    const handleEdit = (category: TicketCategory) => {
        clearErrors();
        setSelectedCategory(category);

        setData({
            name: category.name,
            description: category.description ?? '',
        });

        setIsDialogOpen(true);
    };

    const handleDialogOpenChange = (open: boolean) => {
        setIsDialogOpen(open);

        if (!open) {
            clearErrors();
        }
    };

    const handleDeactivate = () => {
        if (!categoryToDeactivate) return;

        patch(
            `/ticket-categories/${categoryToDeactivate.id}/deactivate`,
            {
                onSuccess: () => {
                    setCategoryToDeactivate(null);
                },
            },
        );
    };

    return (
        <>
            <Head title="Kategori Tiket" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Page Header */}
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div className="space-y-1">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Kategori Tiket
                        </h1>

                        <p className="text-sm text-muted-foreground">
                            Kelola kategori tiket untuk mengelompokkan
                            permintaan bantuan.
                        </p>
                    </div>
                    <Button
                        onClick={() => {
                            clearErrors();
                            setSelectedCategory(null);
                            reset();
                            setIsDialogOpen(true);
                        }}
                    >
                        Tambah Kategori
                    </Button>
                </div>

                {/* Category Table */}
                <div className="overflow-hidden rounded-lg border border-sidebar-border/70">
                    <div className="overflow-x-auto md:overflow-x-visible">
                        <Table>
                            <TableHeader className="bg-muted">
                                <TableRow className="hover:bg-transparent">
                                    <TableHead className="pl-6 pr-1 py-4 font-semibold">
                                        #
                                    </TableHead>
                                    <TableHead className="px-4 font-semibold">
                                        Nama Kategori
                                    </TableHead>
                                    <TableHead className="px-4 font-semibold">
                                        Deskripsi
                                    </TableHead>
                                    <TableHead className="px-4 font-semibold">
                                        Status
                                    </TableHead>
                                    <TableHead className="pr-4 pl-1 text-center font-semibold">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                {categories.data.length > 0 ? (
                                    categories.data.map((category, index) => (
                                        <TableRow key={category.id}>
                                            <TableCell className="pl-6 py-2 text-muted-foreground">
                                                {index + 1}
                                            </TableCell>

                                            <TableCell className="px-4 py-2 font-medium">
                                                {category.name}
                                            </TableCell>

                                            <TableCell className="max-w-125 truncate px-4 py-2 text-muted-foreground">
                                                {category.description || '-'}
                                            </TableCell>

                                            <TableCell className="px-4 py-2">
                                                <span
                                                    className={cn(
                                                        'inline-flex rounded-full px-2.5 py-1 text-xs font-medium',
                                                        category.is_active
                                                            ? 'bg-primary text-primary-foreground'
                                                            : 'bg-secondary text-secondary-foreground'
                                                    )}
                                                >
                                                    {category.is_active ? 'Aktif' : 'Nonaktif'}
                                                </span>
                                            </TableCell>

                                            <TableCell className="pr-6 pl-2 py-2 text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => handleEdit(category)}
                                                    >
                                                        Edit
                                                    </Button>

                                                    {category.is_active && (
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            onClick={() =>
                                                                setCategoryToDeactivate(category)
                                                            }
                                                        >
                                                            Nonaktifkan
                                                        </Button>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="h-24 text-center text-muted-foreground"
                                        >
                                            Tidak ada kategori tiket yang ditemukan.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>


                    {/* Pagination */}
                    <div className="flex flex-col gap-4 border-t border-sidebar-border/70 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-sm text-muted-foreground">
                            Menampilkan {categories.from ?? 0}–{categories.to ?? 0} dari{' '}
                            {categories.total} kategori
                        </p>

                        {categories.last_page > 1 && (
                            <Pagination className="mx-0 w-auto justify-end">
                                <PaginationContent>
                                    {categories.links.map((link, index) => {
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

            {/* Create/Edit Category Dialog */}
            <Dialog
                open={isDialogOpen}
                onOpenChange={handleDialogOpenChange}
            >
                <DialogContent
                    onOpenAutoFocus={(event) => {
                        event.preventDefault();
                    }}>
                    <DialogHeader>
                        <DialogTitle>
                            {selectedCategory ? 'Edit Kategori' : 'Tambah Kategori'}
                        </DialogTitle>
                        <DialogDescription>
                            {selectedCategory
                                ? 'Perbarui informasi kategori tiket.'
                                : 'Tambah kategori baru untuk mengelompokkan tiket bantuan.'}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Nama Kategori</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder="Contoh : Hardware"
                            />
                            {errors.name && (
                                <p className="text-sm text-destructive">
                                    {errors.name}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="description">Deskripsi</Label>
                            <Textarea
                                id="description"
                                value={data.description}
                                onChange={(e) =>
                                    setData('description', e.target.value)
                                }
                                placeholder="Deskripsi kategori (opsional)"
                                rows={3}
                            />
                            {errors.description && (
                                <p className="text-sm text-destructive">
                                    {errors.description}
                                </p>
                            )}
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsDialogOpen(false)}
                            >
                                Batal
                            </Button>

                            <Button type="submit" disabled={processing}>
                                {processing
                                    ? (
                                        <>
                                            <Spinner />
                                            Menyimpan
                                        </>
                                    )
                                    : selectedCategory
                                        ? 'Simpan Perubahan'
                                        : 'Tambah Kategori'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Deactivate Category Dialog */}
            <Dialog
                open={categoryToDeactivate !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setCategoryToDeactivate(null);
                    }
                }}
            >
                <DialogContent
                    onOpenAutoFocus={(event) => {
                        event.preventDefault();
                    }}
                >
                    <DialogHeader>
                        <DialogTitle>Nonaktifkan Kategori?</DialogTitle>

                        <DialogDescription>
                            Apakah Anda yakin ingin menonaktifkan kategori{' '}
                            <span className="font-semibold text-foreground">
                                {categoryToDeactivate?.name}
                            </span>
                            ? Kategori ini tidak akan tersedia untuk penggunaan baru.
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setCategoryToDeactivate(null)}
                        >
                            Batal
                        </Button>

                        <Button
                            variant="destructive"
                            onClick={handleDeactivate}
                            disabled={processing}
                        >
                            {processing ? (
                                <>
                                    <Spinner />
                                    Memproses
                                </>
                            ) : 'Nonaktifkan'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

TicketCategoriesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Kategori Tiket',
            href: '/ticket-categories',
        },
    ] satisfies BreadcrumbItem[],
};
