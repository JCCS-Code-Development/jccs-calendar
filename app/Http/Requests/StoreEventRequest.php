<?php

namespace App\Http\Requests;

use App\Models\EventType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $eventType = EventType::find($this->input('event_type_id'));

        if (strtolower($eventType?->name ?? '') === 'reminder' && $this->filled('reminder_date')) {
            $this->merge([
                'start_datetime' => $this->input('reminder_date') . ' 00:00:00',
                'end_datetime' => null,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'event_type_id' => ['required', 'exists:event_types,id'],
            'event_subtype' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'array'],
            'details.engineer' => ['nullable', 'string', 'max:255'],
            'details.participants' => ['nullable', 'string'],
            'details.person' => ['nullable', 'string', 'max:255'],
            'details.company' => ['nullable', 'string', 'max:255'],
            'details.items' => ['nullable', 'array'],
            'details.items.*.name' => ['nullable', 'string', 'max:255'],
            'details.items.*.quantity' => ['nullable', 'string', 'max:255'],
            'details.workers' => ['nullable', 'string'],
            'details.project' => ['nullable', 'string', 'max:255'],
            'details.estimated_due_date' => ['nullable', 'date'],
            'details.logistics_location' => ['nullable', 'string', 'max:255'],
            'details.team_workers' => ['nullable', 'string'],
            'details.invoice_or_estimate' => ['nullable', 'in:Invoice,Estimate'],
            'details.number' => ['nullable', 'string', 'max:255'],
            'details.name' => ['nullable', 'string', 'max:255'],
            'details.payment_amount' => ['nullable', 'numeric', 'min:0'],
            'details.payment_date' => ['nullable', 'date'],
            'details.payment_document_number' => ['nullable', 'string', 'max:255'],
            'details.payment_name' => ['nullable', 'string', 'max:255'],
            'details.payment_method' => ['nullable', 'string', 'max:255'],
            'details.payment_status' => ['nullable', 'string', 'max:255'],
            'reminder_date' => ['nullable', 'date'],
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['nullable', 'date', 'after_or_equal:start_datetime'],
            'status' => ['required', 'string'],
            'priority' => ['required', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
