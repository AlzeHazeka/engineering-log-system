<?php

namespace App\Http\Requests;

use App\Rules\FeatureBelongsToSystem;
use App\Models\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

class LogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $nullable = ['feature_id', 'impact', 'status'];

        foreach ($nullable as $key) {
            if ($this->input($key) === '') {
                $this->merge([$key => null]);
            }
        }
    }

    public function rules(): array
    {
        $type = (string) $this->input('type');

        return [
            'system_id' => ['required', 'integer', 'exists:systems,id'],
            'feature_id' => [
                'nullable',
                'integer',
                'exists:features,id',
                new FeatureBelongsToSystem((int) $this->input('system_id')),
            ],
            'reference_ids' => Rule::when(
                $type === 'fix',
                ['required', 'array', 'min:1'],
                ['nullable', 'array']
            ),
            'reference_ids.*' => ['integer', 'distinct', 'exists:logs,id'],

            'type' => ['required', Rule::in([
                'progress',
                'bug',
                'fix',
                'deployment',
                'maintenance',
                'decision',
                'idea',
            ])],
            'impact' => [
                'nullable',
                Rule::requiredIf($type === 'bug'),
                Rule::in(['low', 'medium', 'high', 'critical']),
            ],
            'status' => [
                'nullable',
                Rule::requiredIf(in_array($type, ['bug', 'fix'], true)),
                Rule::in(['open', 'resolved', 'ignored', 'on_progress', 'done']),
            ],

            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'logged_at' => ['required', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = (string) $this->input('type');
            $status = $this->input('status');

            if (!$this->validateStatusByType($type, $status)) {
                $validator->errors()->add('status', 'Invalid status for the selected type.');
            }

            $referenceIds = $this->input('reference_ids', []);
            if (!is_array($referenceIds) || count($referenceIds) === 0) {
                // Prevent unlinking a resolved fix from its bug(s) when editing.
                $existing = $this->route('log');
                if (
                    $existing instanceof Log &&
                    $type === 'fix' &&
                    $existing->type === 'fix' &&
                    $existing->status === 'resolved' &&
                    $existing->references()->count() > 0
                ) {
                    $validator->errors()->add('reference_ids', 'Resolved fix logs cannot clear references.');
                }

                return;
            }

            $systemId = (int) $this->input('system_id');
            $featureId = $this->input('feature_id');
            $featureId = $featureId === null ? null : (int) $featureId;

            $refs = Log::query()
                ->select(['id', 'system_id', 'feature_id', 'type', 'status'])
                ->whereIn('id', $referenceIds)
                ->get()
                ->keyBy('id');

            foreach ($referenceIds as $refId) {
                $ref = $refs->get((int) $refId);
                if (!$ref) {
                    continue;
                }

                if ((int) $ref->system_id !== $systemId) {
                    $validator->errors()->add('reference_ids', 'Reference logs must belong to the same system.');
                    break;
                }

                if ($featureId !== null && (int) $ref->feature_id !== $featureId) {
                    $validator->errors()->add('reference_ids', 'Reference logs must belong to the same feature.');
                    break;
                }

                // For fix logs, we only allow referencing open bugs.
                if ($type === 'fix') {
                    if ($featureId === null && $ref->feature_id !== null) {
                        $validator->errors()->add('reference_ids', 'Fix logs without a feature can only reference global logs.');
                        break;
                    }

                    if ($ref->type !== 'bug' || $ref->status !== 'open') {
                        $validator->errors()->add('reference_ids', 'Fix logs can only reference open bug logs.');
                        break;
                    }
                }
            }
        });
    }

    private function validateStatusByType(string $type, mixed $status): bool
    {
        if ($status === null) {
            return true;
        }

        $status = (string) $status;

        return match ($type) {
            'bug' => in_array($status, ['open', 'resolved', 'ignored'], true),
            'fix' => in_array($status, ['open', 'resolved'], true),
            'progress' => in_array($status, ['on_progress', 'done'], true),
            default => false,
        };
    }
}
