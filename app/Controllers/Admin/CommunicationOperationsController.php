<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Communication\CommunicationOperationsService;
use Throwable;

final class CommunicationOperationsController extends BaseController
{
    /**
     * Read-only Communication Operations.
     *
     * The existing SUPER_ADMIN route remains authoritative.
     */
    public function index(): string
    {
        $channel =
            mb_strtolower(
                trim(
                    (string) $this
                        ->request
                        ->getGet(
                            'channel'
                        )
                )
            );

        if (
            !in_array(
                $channel,
                [
                    'email',
                    'sms',
                ],
                true
            )
        ) {
            $channel =
                'email';
        }

        $status =
            trim(
                (string) $this
                    ->request
                    ->getGet(
                        'status'
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

            /*
             * Phase 6A:
             *
             * Combined communication health is independent of the currently
             * selected Email/SMS tab so Super Admin always sees the state of
             * both channels.
             */
            $communicationHealth =
                $service
                ->communicationHealth();

            if ($channel === 'sms') {
                $messageType =
                    trim(
                        (string) $this
                            ->request
                            ->getGet(
                                'message_type'
                            )
                    );

                $operations =
                    $service
                    ->smsDelivery(
                        $status,
                        $messageType,
                        $search,
                        $page
                    );
            } else {
                $referenceType =
                    trim(
                        (string) $this
                            ->request
                            ->getGet(
                                'reference_type'
                            )
                    );

                $operations =
                    $service
                    ->emailQueue(
                        $status,
                        $referenceType,
                        $search,
                        $page
                    );
            }

            return view(
                'Admin/CommunicationOperations/Index',
                [
                    'pageTitle' =>
                    'Communication Operations',

                    'channel' =>
                    $channel,

                    'communicationHealth' =>
                    $communicationHealth,

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

                    'channel' =>
                    $channel,

                    /*
                     * Keep the health section fail-safe. The View will render
                     * unavailable rather than incorrectly reporting Healthy.
                     */
                    'communicationHealth' => [
                        'status' =>
                        'UNAVAILABLE',

                        'email' => [
                            'status' =>
                            'UNAVAILABLE',

                            'message' =>
                            'Email health is unavailable.',
                        ],

                        'sms' => [
                            'status' =>
                            'UNAVAILABLE',

                            'message' =>
                            'SMS health is unavailable.',
                        ],
                    ],

                    'operations' => [
                        'rows' =>
                        [],

                        'summary' =>
                        [],

                        'health' =>
                        [],

                        'smsHealth' =>
                        [],

                        'operationalAlerts' =>
                        [],

                        'otpAlerts' =>
                        [],

                        'filters' =>
                        [],

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

                        'messageTypeOptions' =>
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
