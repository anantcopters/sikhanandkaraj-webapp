<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Custom validation rules related to age and date of birth.
 */
final class AgeRules
{
    /**
     * Validate that a date of birth belongs to a person who has
     * reached the required minimum age.
     *
     * Rule usage:
     *
     * minimum_age[18]
     *
     * @param mixed                        $value
     * @param string                       $minimumAge
     * @param array<string, mixed>         $data
     * @param string|null                  $error
     * @param string                       $field
     */
    public function minimum_age(
        mixed $value,
        string $minimumAge,
        array $data,
        ?string &$error = null,
        string $field = ''
    ): bool {
        $dateOfBirth = trim(
            (string) $value
        );

        $requiredAge = filter_var(
            $minimumAge,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                    'max_range' => 150,
                ],
            ]
        );

        if (
            $dateOfBirth === ''
            || $requiredAge === false
        ) {
            $error =
                'Please enter a valid date of birth.';

            return false;
        }

        $birthDate =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $dateOfBirth
            );

        $dateErrors =
            DateTimeImmutable::getLastErrors();

        /*
         * getLastErrors() returns false when parsing produced no
         * warnings or errors.
         */
        $hasDateErrors =
            is_array($dateErrors)
            && (
                ($dateErrors['warning_count'] ?? 0) > 0
                || ($dateErrors['error_count'] ?? 0) > 0
            );

        if (
            !($birthDate instanceof DateTimeInterface)
            || $hasDateErrors
            || $birthDate->format('Y-m-d')
            !== $dateOfBirth
        ) {
            $error =
                'Please enter a valid date of birth.';

            return false;
        }

        /*
         * The member is eligible when DOB is on or before the date
         * obtained by subtracting the minimum age from today.
         */
        $today = new DateTimeImmutable('today');

        $latestEligibleBirthDate =
            $today->modify(
                sprintf(
                    '-%d years',
                    $requiredAge
                )
            );

        if ($birthDate > $latestEligibleBirthDate) {
            $error = sprintf(
                'The member must be at least %d years old.',
                $requiredAge
            );

            return false;
        }

        return true;
    }
}
