import { Head, Link, useForm } from '@inertiajs/react';
import { File as FileIcon } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

import { dashboard } from '@/routes';

import type { BreadcrumbItem } from '@/types/navigation';

type Category = {
    id: number;
    name: string;
};

type CreateTicketProps = {
    categories: Category[];
};

type TicketForm = {
    category_id: string;
    subject: string;
    description: string;
    priority: 'low' | 'medium' | 'high' | 'urgent';
    attachments: File[];
};

export default function CreateTicket({ categories }: CreateTicketProps) {
    const { data, setData, post, processing, errors, clearErrors } = useForm<TicketForm>({
        category_id: '',
        subject: '',
        description: '',
        priority: 'medium',
        attachments: [],
    });

    const previewableTypes = [
        'image/jpeg',
        'image/png',
        'application/pdf',
    ];

    const previewFile = (file: File) => {
        if (!previewableTypes.includes(file.type)) {
            return;
        }

        const fileUrl = URL.createObjectURL(file);

        window.open(fileUrl, '_blank', 'noopener,noreferrer');

        setTimeout(() => URL.revokeObjectURL(fileUrl), 60_000);
    };

    return (
        <>
            <Head title="Buat Tiket" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Buat Tiket Baru
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Sampaikan permasalahan yang dialami agar tim support dapat membantu.
                    </p>
                </div>

                <Card className="pt-0">
                    <CardHeader className="border-b bg-muted/50 px-6 py-5">
                        <CardTitle>Informasi Tiket</CardTitle>
                        <CardDescription>
                            Lengkapi informasi berikut untuk membuat tiket bantuan.
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <form
                            onSubmit={(event) => {
                                event.preventDefault();
                                post('/tickets', {
                                    forceFormData: true,
                                });
                            }}
                            className="space-y-6"
                        >
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-4">
                                {/* Subjek */}
                                <Field
                                    data-invalid={!!errors.subject}
                                    className="col-span-1 gap-2 md:col-span-2"
                                >
                                    <FieldLabel htmlFor="subject">Subjek Tiket</FieldLabel>
                                    <Input
                                        id="subject"
                                        value={data.subject}
                                        onChange={(event) => {
                                            setData('subject', event.target.value);
                                            clearErrors('subject');
                                        }}
                                        placeholder="Contoh: Tidak dapat login ke aplikasi"
                                        aria-invalid={!!errors.subject}
                                    />
                                    <FieldError>{errors.subject}</FieldError>
                                </Field>

                                {/* Kategori */}
                                <Field
                                    data-invalid={!!errors.category_id}
                                    className="col-span-1 gap-2 md:col-span-1"
                                >
                                    <FieldLabel htmlFor="category_id">Kategori</FieldLabel>
                                    <Select
                                        value={data.category_id}
                                        onValueChange={(value) => {
                                            setData('category_id', value);
                                            clearErrors('category_id');
                                        }}
                                    >
                                        <SelectTrigger
                                            id="category_id"
                                            className={`w-full ${errors.category_id ? 'border-destructive focus-visible:ring-destructive' : ''}`}
                                            aria-invalid={!!errors.category_id}
                                        >
                                            <SelectValue placeholder="Pilih kategori tiket" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {categories.map((category) => (
                                                <SelectItem
                                                    key={category.id}
                                                    value={String(category.id)}
                                                >
                                                    {category.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <FieldError>{errors.category_id}</FieldError>
                                </Field>

                                {/* Prioritas */}
                                <Field
                                    data-invalid={!!errors.priority}
                                    className="col-span-1 gap-2 md:col-span-1"
                                >
                                    <FieldLabel htmlFor="priority">Prioritas</FieldLabel>
                                    <Select
                                        value={data.priority}
                                        onValueChange={(value) => {
                                            setData(
                                                'priority',
                                                value as TicketForm['priority'],
                                            );
                                            clearErrors('priority');
                                        }}
                                    >
                                        <SelectTrigger
                                            id="priority"
                                            className="w-full"
                                            aria-invalid={!!errors.priority}
                                        >
                                            <SelectValue placeholder="Pilih prioritas" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="low">Rendah</SelectItem>
                                            <SelectItem value="medium">Sedang</SelectItem>
                                            <SelectItem value="high">Tinggi</SelectItem>
                                            <SelectItem value="urgent">Mendesak</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <FieldError>{errors.priority}</FieldError>
                                </Field>
                            </div>

                            {/* Deskripsi */}
                            <Field data-invalid={!!errors.description} className="gap-2">
                                <FieldLabel htmlFor="description">
                                    Deskripsi Masalah
                                </FieldLabel>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(event) => {
                                        setData('description', event.target.value);
                                        clearErrors('description');
                                    }}
                                    placeholder="Jelaskan masalah secara detail"
                                    rows={6}
                                    aria-invalid={!!errors.description}
                                />
                                <FieldError>{errors.description}</FieldError>
                            </Field>

                            {/* Attachment */}
                            <Field
                                data-invalid={
                                    !!errors.attachments || !!errors['attachments.0']
                                }
                                className="gap-2"
                            >
                                <FieldLabel htmlFor="attachments">
                                    Lampiran (Opsional)
                                </FieldLabel>

                                <Input
                                    id="attachments"
                                    type="file"
                                    multiple
                                    accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx"
                                    onChange={(event) => {
                                        const selectedFiles = Array.from(
                                            event.target.files ?? [],
                                        );

                                        setData(
                                            'attachments',
                                            [...data.attachments, ...selectedFiles].slice(0, 5),
                                        );

                                        clearErrors('attachments', 'attachments.0');
                                        event.target.value = '';
                                    }}
                                    aria-invalid={
                                        !!errors.attachments || !!errors['attachments.0']
                                    }
                                />

                                <FieldDescription className="text-xs text-muted-foreground">
                                    Maksimal 5 file, ukuran setiap file 5 MB.
                                    Format: JPG, PNG, PDF, DOC, DOCX, XLS, XLSX.
                                </FieldDescription>

                                <FieldError>
                                    {errors.attachments || errors['attachments.0']}
                                </FieldError>

                                {data.attachments.length > 0 && (
                                    <div className="space-y-2">
                                        {data.attachments.map((file, index) => {
                                            const canPreview = previewableTypes.includes(file.type);

                                            return (
                                                <div
                                                    key={`${file.name}-${index}`}
                                                    className="flex items-center justify-between gap-3 rounded-md border px-3 py-2.5 text-sm"
                                                >
                                                    <div className="flex min-w-0 items-center gap-3">
                                                        <div className="flex size-9 shrink-0 items-center justify-center rounded-md bg-muted">
                                                            <FileIcon className="size-4 text-muted-foreground" />
                                                        </div>

                                                        <div className="min-w-0">
                                                            {canPreview ? (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => previewFile(file)}
                                                                    className="truncate text-left font-medium text-primary hover:underline"
                                                                    title="Preview file"
                                                                >
                                                                    {file.name}
                                                                </button>
                                                            ) : (
                                                                <p className="truncate font-medium">
                                                                    {file.name}
                                                                </p>
                                                            )}

                                                            <p className="text-xs text-muted-foreground">
                                                                {(file.size / (1024 * 1024)).toFixed(2)} MB
                                                                {canPreview && ' · Klik untuk preview'}
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => {
                                                            setData(
                                                                'attachments',
                                                                data.attachments.filter(
                                                                    (_, fileIndex) => fileIndex !== index,
                                                                ),
                                                            );

                                                            clearErrors('attachments', 'attachments.0');
                                                        }}
                                                    >
                                                        Hapus
                                                    </Button>
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}
                            </Field>

                            {/* Tombol */}
                            <div className="flex justify-end gap-3 border-t pt-4">
                                <Link
                                    href="/tickets"
                                    className="inline-flex h-9 items-center justify-center rounded-md border px-4 text-sm font-medium hover:bg-accent"
                                >
                                    Batal
                                </Link>

                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner className="mr-2 size-4" />}
                                    Buat Tiket
                                </Button>
                            </div>
                        </form>

                    </CardContent>
                </Card>
            </div>
        </>
    );
}
CreateTicket.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Tiket',
            href: '/tickets',
        },
        {
            title: 'Buat Tiket',
            href: '/tickets/create',
        },
    ] satisfies BreadcrumbItem[],
};