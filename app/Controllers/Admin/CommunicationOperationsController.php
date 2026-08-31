<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Communication\CommunicationOperationsService;
use Throwable;

final class CommunicationOperationsController extends BaseController
{
    /**
     * Read-only communication operations screen.
     *
     * Route authorization is restricted to SUPER_ADMIN, following the
     * existing Email Preview Centre security boundary.
     */
    public function index(): string
    {
        $status =
            trim(
                (string) $this
                    ->request
                    ->getGet(
                        'status'
                    )
            );

        $referenceType =
            trim(
                (string) $this
                    ->request
                    ->getGet(
                        'reference_type'
                    )
            );

        $search =
            trim(
                (string) $this
                    ->request
                    ->getGet(
                        'search'
                    )
            );

        $page =
            max(
                1,
                (int) (
                    $this
                    ->request
                    ->getGet(
                        'page'
                    )
                    ?? 1
                )
            );

        try {
            /** @var CommunicationOperationsService $service */
            $service =
                service(
                    'communicationOperationsService'
                );

            $operations =
                $service
                ->emailQueue(
                    $status,
                    $referenceType,
                    $search,
                    $page
                );

            return view(
                'Admin/CommunicationOperations/Index',
                [
                    'pageTitle' =>
                    'Communication Operations',

                    'operations' =>
                    $operations,
                ]
            );
        } catch (Throwable $exception) {
            service(
                'applicationErrorLogger'
            )->exception(
                $exception
            );

            return view(
                'Admin/CommunicationOperations/Index',
                [
                    'pageTitle' =>
                    'Communication Operations',

                    'operations' => [
                        'rows' =>
                        [],

                        'summary' => [
                            'total' =>
                            0,

                            'PENDING' =>
                            0,

                            'PROCESSING' =>
                            0,

                            'SENT' =>
                            0,

                            'FAILED' =>
                            0,
                        ],

                        'health' => [
                            'readyNow' =>
                            0,

                            'retryPending' =>
                            0,

                            'staleProcessing' =>
                            0,

                            'failed' =>
                            0,

                            'oldestPendingAt' =>
                            '',

                            'oldestPendingMinutes' =>
                            null,
                        ],

                        'filters' => [
                            'status' =>
                            '',

                            'referenceType' =>
                            '',

                            'search' =>
                            '',
                        ],

                        'pagination' => [
                            'page' =>
                            1,

                            'perPage' =>
                            25,

                            'total' =>
                            0,

                            'totalPages' =>
                            1,
                        ],

                        'statusOptions' =>
                        [],

                        'referenceTypeOptions' =>
                        [],
                    ],

                    'formAlert' => [
                        'type' =>
                        'danger',

                        'title' =>
                        'Communication data unavailable',

                        'message' =>
                        'Communication operations could not '
                            . 'be loaded. Please try again.',
                    ],
                ]
            );
        }
    }
}
