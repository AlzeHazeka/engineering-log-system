<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $nullable = ['progress', 'description', 'start_date', 'due_date', 'assigned_team'];

        foreach ($nullable as $key) {
            if ($this->input($key) === '') {
                $this->merge([$key => null]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['planned', 'in_progress', 'done', 'on_hold'])],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'category' => ['required', Rule::in(['feature', 'improvement', 'maintenance'])],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'assigned_team' => ['nullable', 'string', 'max:100'],
        ];
    }
}

