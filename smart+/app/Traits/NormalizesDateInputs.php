<?php

namespace App\Traits;

trait NormalizesDateInputs
{
    /**
     * Normalize DD-MM-YYYY (or DD/MM/YYYY) date inputs to Y-m-d before validation & DB persistence.
     */
    protected function normalizeDateFields(array $fields): void
    {
        $updates = [];

        foreach ($fields as $field) {
            $value = $this->input($field);

            if (!is_string($value)) {
                continue;
            }

            $trimmed = trim($value);

            if ($trimmed === '') {
                continue;
            }

            if (preg_match('/^(\d{2})[-\/](\d{2})[-\/](\d{4})$/', $trimmed, $matches)) {
                $day = (int) $matches[1];
                $month = (int) $matches[2];
                $year = (int) $matches[3];

                if (checkdate($month, $day, $year)) {
                    $updates[$field] = sprintf('%04d-%02d-%02d', $year, $month, $day);
                }
            }
        }

        if ($updates !== []) {
            $this->merge($updates);
        }
    }
}
