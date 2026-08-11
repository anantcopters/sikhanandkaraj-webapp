<?php

declare(strict_types=1);

namespace App\Filters;

use App\Models\FieldOfficerModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

final class FieldOfficerAuthFilter
implements FilterInterface
{
    public function before(
        RequestInterface $request,
        $arguments = null
    ) {
        $fieldOfficerId =
            session(
                'fo_field_officer_id'
            );

        if (
            session(
                'fo_is_authenticated'
            ) !== true
            || !is_numeric(
                $fieldOfficerId
            )
        ) {
            return $this
                ->deny(
                    'Login required',
                    'Please log in to access '
                        . 'the SAK Volunteer portal.'
                );
        }

        try {
            $fieldOfficer =
                (
                    new FieldOfficerModel()
                )->findActiveById(
                    (int) $fieldOfficerId
                );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Unable to validate SAK Volunteer session: {message}',
                [
                    'message' =>
                    $exception
                        ->getMessage(),
                ]
            );

            return $this->deny(
                'Session unavailable',
                'Your SAK Volunteer session '
                    . 'could not be validated.'
            );
        }

        if (!is_array($fieldOfficer)) {
            return $this->deny(
                'Account unavailable',
                'Your SAK Volunteer account '
                    . 'is no longer active.'
            );
        }

        /*
         * Refresh trusted values from DB.
         */
        session()->set([
            'fo_field_officer_name' =>
            (string) (
                $fieldOfficer['full_name'] ?? ''
            ),

            'fo_field_officer_code' =>
            (string) (
                $fieldOfficer['officer_code'] ?? ''
            ),
        ]);

        return null;
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
        return null;
    }

    private function deny(
        string $title,
        string $message
    ) {
        session()->remove([
            'fo_is_authenticated',
            'fo_field_officer_id',
            'fo_field_officer_name',
            'fo_field_officer_code',
            'fo_authenticated_at',
        ]);

        session()->regenerate(
            true
        );

        return redirect()
            ->to(
                route_to(
                    'field-officer.login'
                )
            )
            ->with(
                'formAlert',
                [
                    'type' =>
                    'danger',

                    'title' =>
                    $title,

                    'message' =>
                    $message,
                ]
            );
    }
}
