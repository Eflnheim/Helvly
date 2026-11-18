<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Ticket::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('ticket_categories', 'id')
                    ->where('is_active', true),
            ],
            'subject' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'required',
                'string',
            ],
            'priority' => [
                'required',
                Rule::in(['low', 'medium', 'high', 'urgent']),
            ],
            'attachments' => [
                'nullable',
                'array',
                'max:5',
            ],
            'attachments.*' => [
                'file',
                'max:5120',
                'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'attachments.array' => 'Format file lampiran tidak valid.',
            'attachments.max' => 'Maksimal 5 file yang dapat diunggah.',
            'attachments.*.file' => 'File lampiran tidak valid.',
            'attachments.*.max' => 'Ukuran setiap file maksimal 5 MB.',
            'attachments.*.mimes' => 'Format file harus JPG, JPEG, PNG, PDF, DOC, DOCX, XLS, atau XLSX.',
        ];
    }
}
