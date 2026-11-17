<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->is_active
            && $user->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    { {
            return [
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('ticket_categories', 'name')
                        ->ignore($this->route('ticket_category')),
                ],
                'description' => ['nullable', 'string'],
            ];
        }
    }
}
