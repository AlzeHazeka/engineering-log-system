<?php

namespace App\Rules;

use App\Models\Feature;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FeatureBelongsToSystem implements ValidationRule
{
    public function __construct(private readonly ?int $systemId)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value || !$this->systemId) {
            return;
        }

        $valid = Feature::query()
            ->whereKey($value)
            ->where('system_id', $this->systemId)
            ->exists();

        if (!$valid) {
            $fail('The selected feature does not belong to the selected system.');
        }
    }
}

