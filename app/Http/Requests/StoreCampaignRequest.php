<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'campaign_name' => ['required', 'string', 'max:200'],
            'subject' => ['required_if:action,send', 'nullable', 'string', 'max:255'],
            'message' => ['required_if:action,send', 'nullable', 'string'],
            'recipients' => ['array'],
            'recipients.*' => ['integer', 'exists:recipients,id'],
            'group_id' => ['nullable', 'integer', 'exists:recipient_groups,id'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,png,jpg,jpeg,gif,txt,csv', 'max:10240'],
            'action' => ['required', 'in:draft,send'],
        ];
    }
}
