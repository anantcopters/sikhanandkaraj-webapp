<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\EmailQueueModel;
use CodeIgniter\Database\BaseConnection;
use App\Models\SmsDeliveryLogModel;

/**
 * Read-only operational presentation for the communication subsystem.
 *
 * IMPORTANT:
 *
 * This service does not:
 *
 * - send email;
 * - retry email;
 * - change queue state;
 * - expose email view_data;
 * - expose full recipient email addresses.
 *
 * It provides safe operational visibility over the existing durable
 * email_queue.
 *
 * Manual retry is deliberately kept outside this phase because retry is an
 * administrative state-changing operation and must be permission-controlled
 * and audited.
 */
final class CommunicationOperationsService
{
    private const DEFAULT_PER_PAGE =
    25;

    private const MAXIMUM_PER_PAGE =
    100;

    private const MAXIMUM_SEARCH_LENGTH =
    100;

    /**
     * EmailQueueService already recovers stale PROCESSING records.
     *
     * Keep the operational warning threshold aligned with that queue behaviour.
     */
    private const STALE_PROCESSING_MINUTES =
    10;

    /**
     * OTP abuse monitoring.
     *
     * The existing OTP services remain authoritative for enforcement.
     * Communication Operations only presents operational severity.
     *
     * 5-9 requests / rolling 24 hours:
     *     WARNING
     *
     * 10+ requests / rolling 24 hours:
     *     CRITICAL
     */
    private const OTP_WARNING_REQUESTS =
    5;

    private const OTP_CRITICAL_REQUESTS =
    10;

    /**
     * SMS operational health.
     *
     * A failure-rate alert should only be generated after a meaningful sample
     * exists. This avoids alarming Super Admin because one of the first few SMS
     * requests happened to fail.
     */
    private const SMS_FAILURE_SAMPLE_MINIMUM =
    10;

    private const SMS_FAILURE_WARNING_PERCENT =
    20.0;

    private const SMS_FAILURE_CRITICAL_PERCENT =
    50.0;

    /**
     * Provider errors which normally require administrative action rather than a
     * member simply retrying an OTP.
     *
     * These are based on the mTalkz API status contract currently used by the
     * application.
     */
    private const SMS_CRITICAL_PROVIDER_CODES = [
        'AZQ02', // Invalid API Key.
        'AZQ03', // Invalid/deactivated user.
        'AZQ05', // Invalid sender ID.
        'AZQ10', // Insufficient credit.
    ];

    public function __construct(
        private readonly EmailQueueModel
        $emailQueueModel,

        private readonly SmsDeliveryLogModel
        $smsDeliveryLogModel,

        private readonly BaseConnection
        $database
    ) {}

    /**
     * Return the combined communication health used by Communication Operations.
     *
     * Phase 6A deliberately reuses the existing Email and SMS operational-health
     * calculations. It does not maintain another health table or another source
     * of truth.
     *
     * Status meaning:
     *
     * HEALTHY
     *     No current Email/SMS condition requires operational attention.
     *
     * WARNING
     *     Communication is working, but an operational condition should be
     *     reviewed.
     *
     * CRITICAL
     *     A condition exists which may prevent or materially affect
     *     communication.
     *
     * @return array{
     *     status:string,
     *     email:array{
     *         status:string,
     *         readyNow:int,
     *         retryPending:int,
     *         staleProcessing:int,
     *         failed:int,
     *         message:string
     *     },
     *     sms:array{
     *         status:string,
     *         totalLast24Hours:int,
     *         acceptedLast24Hours:int,
     *         failedLast24Hours:int,
     *         failureRate:float,
     *         alertCount:int,
     *         criticalAlertCount:int,
     *         warningAlertCount:int,
     *         message:string
     *     }
     * }
     */
    public function communicationHealth(): array
    {
        /*
     * Reuse the existing Email queue-health calculation.
     */
        $emailHealth =
            $this
            ->emailQueueHealth();

        $emailReadyNow =
            max(
                0,
                (int) (
                    $emailHealth['readyNow']
                    ?? 0
                )
            );

        $emailRetryPending =
            max(
                0,
                (int) (
                    $emailHealth['retryPending']
                    ?? 0
                )
            );

        $emailStaleProcessing =
            max(
                0,
                (int) (
                    $emailHealth['staleProcessing']
                    ?? 0
                )
            );

        $emailFailed =
            max(
                0,
                (int) (
                    $emailHealth['failed']
                    ?? 0
                )
            );

        /*
     * A stale PROCESSING record is the strongest Email operational signal
     * because the queue worker should normally recover these records.
     *
     * Terminal FAILED records require review, but do not necessarily mean
     * the Email channel itself is unavailable.
     */
        if ($emailStaleProcessing > 0) {
            $emailStatus =
                'CRITICAL';

            $emailMessage =
                number_format(
                    $emailStaleProcessing
                )
                . ' stale processing record'
                . (
                    $emailStaleProcessing === 1
                    ? ''
                    : 's'
                )
                . ' require attention.';
        } elseif ($emailFailed > 0) {
            $emailStatus =
                'WARNING';

            $emailMessage =
                number_format(
                    $emailFailed
                )
                . ' failed email record'
                . (
                    $emailFailed === 1
                    ? ''
                    : 's'
                )
                . ' require review.';
        } elseif ($emailRetryPending > 0) {
            $emailStatus =
                'WARNING';

            $emailMessage =
                number_format(
                    $emailRetryPending
                )
                . ' email'
                . (
                    $emailRetryPending === 1
                    ? ' is'
                    : 's are'
                )
                . ' waiting for automatic retry.';
        } else {
            $emailStatus =
                'HEALTHY';

            $emailMessage =
                $emailReadyNow > 0
                ? number_format(
                    $emailReadyNow
                )
                . ' email'
                . (
                    $emailReadyNow === 1
                    ? ' is'
                    : 's are'
                )
                . ' ready for normal worker pickup.'
                : 'No Email issue requires attention.';
        }

        /*
     * Reuse the existing Phase 4E/4G SMS calculations.
     *
     * This ensures that the combined health summary and the detailed SMS tab
     * cannot apply different operational thresholds.
     */
        $otpAlerts =
            $this
            ->otpLimitAlerts();

        $smsHealth =
            $this
            ->smsOperationalHealth();

        $smsAlerts =
            $this
            ->smsOperationalAlerts(
                $otpAlerts,
                $smsHealth
            );

        $criticalSmsAlerts =
            count(
                array_filter(
                    $smsAlerts,
                    static fn(
                        array $alert
                    ): bool =>
                    mb_strtoupper(
                        trim(
                            (string) (
                                $alert['severity']
                                ?? ''
                            )
                        )
                    ) === 'CRITICAL'
                )
            );

        $warningSmsAlerts =
            count(
                array_filter(
                    $smsAlerts,
                    static fn(
                        array $alert
                    ): bool =>
                    mb_strtoupper(
                        trim(
                            (string) (
                                $alert['severity']
                                ?? ''
                            )
                        )
                    ) === 'WARNING'
                )
            );

        if ($criticalSmsAlerts > 0) {
            $smsStatus =
                'CRITICAL';

            $smsMessage =
                number_format(
                    $criticalSmsAlerts
                )
                . ' critical SMS operational alert'
                . (
                    $criticalSmsAlerts === 1
                    ? ''
                    : 's'
                )
                . ' require attention.';
        } elseif ($warningSmsAlerts > 0) {
            $smsStatus =
                'WARNING';

            $smsMessage =
                number_format(
                    $warningSmsAlerts
                )
                . ' SMS operational warning'
                . (
                    $warningSmsAlerts === 1
                    ? ''
                    : 's'
                )
                . ' require review.';
        } else {
            $smsStatus =
                'HEALTHY';

            $smsMessage =
                'No SMS issue requires attention.';
        }

        /*
     * Overall health follows the most severe channel state.
     */
        if (
            $emailStatus === 'CRITICAL'
            || $smsStatus === 'CRITICAL'
        ) {
            $overallStatus =
                'CRITICAL';
        } elseif (
            $emailStatus === 'WARNING'
            || $smsStatus === 'WARNING'
        ) {
            $overallStatus =
                'WARNING';
        } else {
            $overallStatus =
                'HEALTHY';
        }

        return [
            'status' =>
            $overallStatus,

            'email' => [
                'status' =>
                $emailStatus,

                'readyNow' =>
                $emailReadyNow,

                'retryPending' =>
                $emailRetryPending,

                'staleProcessing' =>
                $emailStaleProcessing,

                'failed' =>
                $emailFailed,

                'message' =>
                $emailMessage,
            ],

            'sms' => [
                'status' =>
                $smsStatus,

                'totalLast24Hours' =>
                max(
                    0,
                    (int) (
                        $smsHealth['totalLast24Hours']
                        ?? 0
                    )
                ),

                'acceptedLast24Hours' =>
                max(
                    0,
                    (int) (
                        $smsHealth['acceptedLast24Hours']
                        ?? 0
                    )
                ),

                'failedLast24Hours' =>
                max(
                    0,
                    (int) (
                        $smsHealth['failedLast24Hours']
                        ?? 0
                    )
                ),

                'failureRate' =>
                max(
                    0.0,
                    (float) (
                        $smsHealth['failureRate']
                        ?? 0.0
                    )
                ),

                'alertCount' =>
                count(
                    $smsAlerts
                ),

                'criticalAlertCount' =>
                $criticalSmsAlerts,

                'warningAlertCount' =>
                $warningSmsAlerts,

                'message' =>
                $smsMessage,
            ],
        ];
    }

    /**
     * Return the operational email queue listing.
     *
     * @return array{
     *     rows:array<int, array<string, mixed>>,
     *     summary:array<string, int>,
     *     filters:array{
     *         status:string,
     *         referenceType:string,
     *         search:string
     *     },
     *     pagination:array{
     *         page:int,
     *         perPage:int,
     *         total:int,
     *         totalPages:int
     *     },
     *     statusOptions:list<string>,
     *     referenceTypeOptions:list<string>
     * }
     */
    public function emailQueue(
        string $status = '',
        string $referenceType = '',
        string $search = '',
        int $page = 1,
        int $perPage = self::DEFAULT_PER_PAGE
    ): array {
        $status =
            $this
            ->normaliseStatus(
                $status
            );

        $referenceType =
            $this
            ->normaliseReferenceType(
                $referenceType
            );

        $search =
            mb_substr(
                trim(
                    $search
                ),
                0,
                self::MAXIMUM_SEARCH_LENGTH
            );

        $page =
            max(
                1,
                $page
            );

        $perPage =
            max(
                1,
                min(
                    self::MAXIMUM_PER_PAGE,
                    $perPage
                )
            );

        $total =
            $this
            ->countEmailQueue(
                $status,
                $referenceType,
                $search
            );

        $totalPages =
            max(
                1,
                (int) ceil(
                    $total
                        / $perPage
                )
            );

        if ($page > $totalPages) {
            $page =
                $totalPages;
        }

        $rows =
            $this
            ->findEmailQueue(
                $status,
                $referenceType,
                $search,
                $page,
                $perPage
            );

        return [
            'rows' =>
            array_map(
                fn(array $row): array =>
                $this
                    ->presentEmailQueueRow(
                        $row
                    ),
                $rows
            ),

            'summary' =>
            $this
                ->emailQueueSummary(),

            'health' =>
            $this
                ->emailQueueHealth(),

            'filters' => [
                'status' =>
                $status,

                'referenceType' =>
                $referenceType,

                'search' =>
                $search,
            ],

            'pagination' => [
                'page' =>
                $page,

                'perPage' =>
                $perPage,

                'total' =>
                $total,

                'totalPages' =>
                $totalPages,
            ],

            'statusOptions' => [
                EmailQueueModel
                ::STATUS_PENDING,

                EmailQueueModel
                ::STATUS_PROCESSING,

                EmailQueueModel
                ::STATUS_SENT,

                EmailQueueModel
                ::STATUS_FAILED,
            ],

            'referenceTypeOptions' =>
            $this
                ->emailReferenceTypes(),
        ];
    }

    /**
     * Return read-only SMS delivery operations.
     *
     * @return array<string, mixed>
     */
    public function smsDelivery(
        string $status = '',
        string $messageType = '',
        string $search = '',
        int $page = 1,
        int $perPage = self::DEFAULT_PER_PAGE
    ): array {
        $status =
            mb_strtoupper(
                trim(
                    $status
                )
            );

        if (
            !in_array(
                $status,
                [
                    '',
                    SmsDeliveryLogModel
                    ::STATUS_SENT,
                    SmsDeliveryLogModel
                    ::STATUS_FAILED,
                ],
                true
            )
        ) {
            $status = '';
        }

        $messageType =
            mb_strtoupper(
                mb_substr(
                    trim(
                        $messageType
                    ),
                    0,
                    50
                )
            );

        $search =
            mb_substr(
                trim(
                    $search
                ),
                0,
                self::MAXIMUM_SEARCH_LENGTH
            );

        $page =
            max(
                1,
                $page
            );

        $perPage =
            max(
                1,
                min(
                    self::MAXIMUM_PER_PAGE,
                    $perPage
                )
            );

        $builder =
            $this
            ->smsDeliveryLogModel
            ->builder();

        $this->applySmsFilters(
            $builder,
            $status,
            $messageType,
            $search
        );

        $countRow =
            $builder
            ->select(
                'COUNT(*) AS total',
                false
            )
            ->get()
            ->getRowArray();

        $total =
            max(
                0,
                (int) (
                    $countRow['total']
                    ?? 0
                )
            );

        $totalPages =
            max(
                1,
                (int) ceil(
                    $total
                        / $perPage
                )
            );

        if ($page > $totalPages) {
            $page =
                $totalPages;
        }

        /*
     * Create a fresh builder after the COUNT query.
     */
        $builder =
            $this
            ->smsDeliveryLogModel
            ->builder();

        $this->applySmsFilters(
            $builder,
            $status,
            $messageType,
            $search
        );

        $rows =
            $builder
            ->select(
                [
                    'id',
                    'message_type',
                    'recipient_mobile',
                    'provider',
                    'provider_message_id',
                    'status',
                    'error_message',
                    'created_at',
                    'sent_at',
                    'failed_at',
                ]
            )
            ->orderBy(
                'id',
                'DESC'
            )
            ->limit(
                $perPage,
                ($page - 1)
                    * $perPage
            )
            ->get()
            ->getResultArray();

        $otpAlerts =
            $this
            ->otpLimitAlerts();

        $smsHealth =
            $this
            ->smsOperationalHealth();

        $operationalAlerts =
            $this
            ->smsOperationalAlerts(
                $otpAlerts,
                $smsHealth
            );

        return [
            'rows' =>
            array_map(
                fn(array $row): array =>
                $this
                    ->presentSmsRow(
                        $row
                    ),
                $rows
            ),

            'summary' =>
            $this
                ->smsSummary(),

            /*
 * Phase 4E:
 *
 * OTP abuse visibility is derived from the authoritative
 * contact_verifications records. No second OTP counter is maintained.
 */
            'otpAlerts' =>
            $otpAlerts,

            /*
 * Phase 4G:
 *
 * Operational health and alerts are derived from existing SMS delivery
 * records. This remains read-only and does not create another alert queue.
 */
            'smsHealth' =>
            $smsHealth,

            'operationalAlerts' =>
            $operationalAlerts,

            'filters' => [
                'status' =>
                $status,

                'messageType' =>
                $messageType,

                'search' =>
                $search,
            ],

            'pagination' => [
                'page' =>
                $page,

                'perPage' =>
                $perPage,

                'total' =>
                $total,

                'totalPages' =>
                $totalPages,
            ],

            'statusOptions' => [
                SmsDeliveryLogModel
                ::STATUS_SENT,

                SmsDeliveryLogModel
                ::STATUS_FAILED,
            ],

            'messageTypeOptions' =>
            $this
                ->smsMessageTypes(),
        ];
    }

    /**
     * @param \CodeIgniter\Database\BaseBuilder $builder
     */
    private function applySmsFilters(
        $builder,
        string $status,
        string $messageType,
        string $search
    ): void {
        if ($status !== '') {
            $builder
                ->where(
                    'status',
                    $status
                );
        }

        if ($messageType !== '') {
            $builder
                ->where(
                    'message_type',
                    $messageType
                );
        }

        if ($search !== '') {
            /*
         * Search is allowed against normalized mobile and provider reference.
         *
         * SMS body is not stored and therefore cannot accidentally become
         * searchable through Admin.
         */
            $builder
                ->groupStart()
                ->like(
                    'recipient_mobile',
                    $search,
                    'both',
                    true,
                    true
                )
                ->orLike(
                    'provider_message_id',
                    $search,
                    'both',
                    true,
                    true
                )
                ->groupEnd();
        }
    }

    /**
     * @return array<string, int>
     */
    private function smsSummary(): array
    {
        $row =
            $this
            ->database
            ->query(
                <<<'SQL'
            SELECT
                COUNT(*) AS total,

                COUNT(*) FILTER (
                    WHERE status = 'SENT'
                ) AS sent,

                COUNT(*) FILTER (
                    WHERE status = 'FAILED'
                ) AS failed,

                COUNT(*) FILTER (
                    WHERE
                        message_type = 'OTP'
                        AND created_at >= CURRENT_TIMESTAMP - INTERVAL '24 hours'
                ) AS otp_last_24_hours,

                COUNT(*) FILTER (
                    WHERE
                        status = 'SENT'
                        AND created_at >= CURRENT_DATE
                ) AS sent_today,

                COUNT(*) FILTER (
                    WHERE
                        status = 'FAILED'
                        AND created_at >= CURRENT_DATE
                ) AS failed_today

            FROM
                sms_delivery_logs
            SQL
            )
            ->getRowArray();

        return [
            'total' =>
            max(
                0,
                (int) (
                    $row['total']
                    ?? 0
                )
            ),

            'sent' =>
            max(
                0,
                (int) (
                    $row['sent']
                    ?? 0
                )
            ),

            'failed' =>
            max(
                0,
                (int) (
                    $row['failed']
                    ?? 0
                )
            ),

            'otpLast24Hours' =>
            max(
                0,
                (int) (
                    $row['otp_last_24_hours']
                    ?? 0
                )
            ),

            'sentToday' =>
            max(
                0,
                (int) (
                    $row['sent_today']
                    ?? 0
                )
            ),

            'failedToday' =>
            max(
                0,
                (int) (
                    $row['failed_today']
                    ?? 0
                )
            ),
        ];
    }

    /**
     * Return SMS provider health for the rolling 24-hour period.
     *
     * SENT means accepted by the configured provider. It does not represent
     * handset delivery because mTalkz does not currently provide the application
     * with a DLR callback contract.
     *
     * @return array<string, int|float|null>
     */
    private function smsOperationalHealth(): array
    {
        $row =
            $this
            ->database
            ->query(
                <<<'SQL'
            SELECT
                COUNT(*) AS total,

                COUNT(*) FILTER (
                    WHERE status = 'SENT'
                ) AS accepted,

                COUNT(*) FILTER (
                    WHERE status = 'FAILED'
                ) AS failed,

                MAX(failed_at) FILTER (
                    WHERE status = 'FAILED'
                ) AS last_failed_at

            FROM
                sms_delivery_logs

            WHERE
                created_at
                    >= CURRENT_TIMESTAMP - INTERVAL '24 hours'
            SQL
            )
            ->getRowArray();

        $total =
            max(
                0,
                (int) (
                    $row['total']
                    ?? 0
                )
            );

        $accepted =
            max(
                0,
                (int) (
                    $row['accepted']
                    ?? 0
                )
            );

        $failed =
            max(
                0,
                (int) (
                    $row['failed']
                    ?? 0
                )
            );

        $failureRate =
            $total > 0
            ? round(
                (
                    $failed
                    / $total
                )
                    * 100,
                1
            )
            : 0.0;

        return [
            'totalLast24Hours' =>
            $total,

            'acceptedLast24Hours' =>
            $accepted,

            'failedLast24Hours' =>
            $failed,

            'failureRate' =>
            $failureRate,

            'lastFailedAt' =>
            trim(
                (string) (
                    $row['last_failed_at']
                    ?? ''
                )
            ),
        ];
    }

    /**
     * Build lightweight SMS operational alerts from existing application data.
     *
     * No alert table is required in this phase. These alerts represent the
     * current operational state whenever Super Admin opens Communication
     * Operations.
     *
     * Alert sources:
     *
     * 1. Critical OTP request volume.
     * 2. High SMS provider failure rate.
     * 3. mTalkz provider errors requiring administrative action.
     *
     * @param array<int, array<string, mixed>> $otpAlerts
     * @param array<string, int|float|null> $smsHealth
     *
     * @return array<int, array<string, mixed>>
     */
    private function smsOperationalAlerts(
        array $otpAlerts,
        array $smsHealth
    ): array {
        $alerts = [];

        /*
     * OTP alerts.
     *
     * Keep each affected mobile/purpose visible because these are separate
     * operational incidents.
     */
        foreach ($otpAlerts as $otpAlert) {
            $severity =
                mb_strtoupper(
                    trim(
                        (string) (
                            $otpAlert['severity']
                            ?? 'WARNING'
                        )
                    )
                );

            $requestCount =
                max(
                    0,
                    (int) (
                        $otpAlert['requestCount']
                        ?? 0
                    )
                );

            $mobile =
                trim(
                    (string) (
                        $otpAlert['mobile']
                        ?? '—'
                    )
                );

            $purpose =
                trim(
                    (string) (
                        $otpAlert['purpose']
                        ?? ''
                    )
                );

            $alerts[] = [
                'severity' =>
                $severity,

                'type' =>
                'OTP_ABUSE',

                'title' =>
                $severity === 'CRITICAL'
                    ? 'Critical OTP request volume'
                    : 'OTP request limit reached',

                'message' =>
                $mobile
                    . (
                        $purpose !== ''
                        ? ' · '
                        . str_replace(
                            '_',
                            ' ',
                            $purpose
                        )
                        : ''
                    )
                    . ' · '
                    . number_format(
                        $requestCount
                    )
                    . ' requests in the last 24 hours',

                'occurredAt' =>
                trim(
                    (string) (
                        $otpAlert['lastRequestedAt']
                        ?? ''
                    )
                ),
            ];
        }

        /*
     * SMS provider failure rate.
     *
     * Do not raise a percentage-based alert until a meaningful sample exists.
     * Otherwise one failed request out of one request would incorrectly look
     * like a 100% provider outage.
     */
        $totalLast24Hours =
            max(
                0,
                (int) (
                    $smsHealth['totalLast24Hours']
                    ?? 0
                )
            );

        $failedLast24Hours =
            max(
                0,
                (int) (
                    $smsHealth['failedLast24Hours']
                    ?? 0
                )
            );

        $failureRate =
            max(
                0.0,
                (float) (
                    $smsHealth['failureRate']
                    ?? 0.0
                )
            );

        if (
            $totalLast24Hours
            >= self::SMS_FAILURE_SAMPLE_MINIMUM
            && $failureRate
            >= self::SMS_FAILURE_WARNING_PERCENT
        ) {
            $severity =
                $failureRate
                >= self::SMS_FAILURE_CRITICAL_PERCENT
                ? 'CRITICAL'
                : 'WARNING';

            $alerts[] = [
                'severity' =>
                $severity,

                'type' =>
                'SMS_FAILURE_RATE',

                'title' =>
                $severity === 'CRITICAL'
                    ? 'Critical SMS failure rate'
                    : 'High SMS failure rate',

                'message' =>
                number_format(
                    $failedLast24Hours
                )
                    . ' of '
                    . number_format(
                        $totalLast24Hours
                    )
                    . ' SMS requests failed during the last 24 hours ('
                    . number_format(
                        $failureRate,
                        1
                    )
                    . '%).',

                'occurredAt' =>
                trim(
                    (string) (
                        $smsHealth['lastFailedAt']
                        ?? ''
                    )
                ),
            ];
        }

        /*
     * Provider configuration/account failures.
     *
     * Only the latest occurrence of each critical mTalkz error code is
     * surfaced. The SMS history below still contains the individual failures.
     */
        foreach (
            $this->criticalProviderFailures()
            as $providerFailure
        ) {
            $alerts[] = [
                'severity' =>
                'CRITICAL',

                'type' =>
                'PROVIDER_ERROR',

                'title' =>
                'mTalkz provider action required',

                'message' =>
                trim(
                    (string) (
                        $providerFailure['error']
                        ?? 'mTalkz rejected the SMS request.'
                    )
                ),

                'occurredAt' =>
                trim(
                    (string) (
                        $providerFailure['occurredAt']
                        ?? ''
                    )
                ),
            ];
        }

        /*
     * Present CRITICAL before WARNING.
     *
     * Within the same severity, newest operational condition appears first.
     */
        usort(
            $alerts,
            static function (
                array $left,
                array $right
            ): int {
                $severityWeight = [
                    'CRITICAL' => 2,
                    'WARNING' => 1,
                ];

                $leftSeverity =
                    $severityWeight[(string) (
                        $left['severity']
                        ?? ''
                    )]
                    ?? 0;

                $rightSeverity =
                    $severityWeight[(string) (
                        $right['severity']
                        ?? ''
                    )]
                    ?? 0;

                if (
                    $leftSeverity
                    !== $rightSeverity
                ) {
                    return $rightSeverity
                        <=> $leftSeverity;
                }

                return strcmp(
                    (string) (
                        $right['occurredAt']
                        ?? ''
                    ),
                    (string) (
                        $left['occurredAt']
                        ?? ''
                    )
                );
            }
        );

        return $alerts;
    }

    /**
     * Return the latest occurrence of each mTalkz provider condition which
     * requires administrative action.
     *
     * The SMS provider already stores a safe error in the form:
     *
     * AZQ02 - Invalid Api Key
     *
     * Therefore this query can classify the documented provider status without
     * persisting or parsing raw mTalkz responses.
     *
     * @return array<int, array{
     *     error:string,
     *     occurredAt:string
     * }>
     */
    private function criticalProviderFailures(): array
    {
        $conditions = [];
        $parameters = [];

        foreach (
            self::SMS_CRITICAL_PROVIDER_CODES
            as $index => $code
        ) {
            $parameterName =
                'provider_code_'
                . $index;

            $conditions[] =
                'error_message LIKE :'
                . $parameterName
                . ':';

            $parameters[$parameterName] =
                $code
                . '%';
        }

        if ($conditions === []) {
            return [];
        }

        $sql =
            '
        SELECT DISTINCT ON (
            SUBSTRING(
                error_message
                FROM 1
                FOR 5
            )
        )
            error_message,
            failed_at

        FROM
            sms_delivery_logs

        WHERE
            status = \'FAILED\'

            AND created_at
                >= CURRENT_TIMESTAMP - INTERVAL \'24 hours\'

            AND (
                '
            . implode(
                "\n OR ",
                $conditions
            )
            . '
            )

        ORDER BY
            SUBSTRING(
                error_message
                FROM 1
                FOR 5
            ),
            failed_at DESC
        ';

        $rows =
            $this
            ->database
            ->query(
                $sql,
                $parameters
            )
            ->getResultArray();

        return array_map(
            static function (
                array $row
            ): array {
                return [
                    'error' =>
                    mb_substr(
                        trim(
                            (string) (
                                $row['error_message']
                                ?? ''
                            )
                        ),
                        0,
                        250
                    ),

                    'occurredAt' =>
                    trim(
                        (string) (
                            $row['failed_at']
                            ?? ''
                        )
                    ),
                ];
            },
            $rows
        );
    }

    /**
     * OTP abuse visibility.
     *
     * contact_verifications remains authoritative for OTP issuance and rate
     * limiting. Communication Operations only derives a severity from those
     * existing records.
     *
     * WARNING:
     *     5-9 requests during the rolling 24-hour period.
     *
     * CRITICAL:
     *     10 or more requests during the rolling 24-hour period.
     *
     * DELIVERY_FAILED is deliberately excluded so an SMS provider outage does
     * not incorrectly make a member appear abusive.
     *
     * @return array<int, array<string, mixed>>
     */
    private function otpLimitAlerts(): array
    {
        $rows =
            $this
            ->database
            ->query(
                <<<'SQL'
            SELECT
                uc.id AS contact_id,
                uc.contact_value AS mobile,
                cv.purpose,
                COUNT(*) AS request_count,
                MAX(cv.created_at) AS last_requested_at

            FROM
                contact_verifications cv

            INNER JOIN
                user_contacts uc
                    ON uc.id = cv.user_contact_id

            WHERE
                cv.created_at
                    >= CURRENT_TIMESTAMP - INTERVAL '24 hours'

                AND cv.status <> 'DELIVERY_FAILED'

            GROUP BY
                uc.id,
                uc.contact_value,
                cv.purpose

            HAVING
                COUNT(*) >= 5

            ORDER BY
                request_count DESC,
                last_requested_at DESC

            LIMIT 25
            SQL
            )
            ->getResultArray();

        return array_map(
            function (
                array $row
            ): array {
                $requestCount =
                    max(
                        0,
                        (int) (
                            $row['request_count']
                            ?? 0
                        )
                    );

                /*
             * Severity is presentation/operations metadata only.
             *
             * It does not change the existing OTP enforcement rules.
             */
                $severity =
                    $requestCount
                    >= self::OTP_CRITICAL_REQUESTS
                    ? 'CRITICAL'
                    : 'WARNING';

                return [
                    'mobile' =>
                    $this
                        ->maskMobile(
                            (string) (
                                $row['mobile']
                                ?? ''
                            )
                        ),

                    'purpose' =>
                    mb_strtoupper(
                        trim(
                            (string) (
                                $row['purpose']
                                ?? ''
                            )
                        )
                    ),

                    'requestCount' =>
                    $requestCount,

                    'severity' =>
                    $severity,

                    'lastRequestedAt' =>
                    trim(
                        (string) (
                            $row['last_requested_at']
                            ?? ''
                        )
                    ),
                ];
            },
            $rows
        );
    }

    /**
     * @return list<string>
     */
    private function smsMessageTypes(): array
    {
        $rows =
            $this
            ->database
            ->query(
                <<<'SQL'
            SELECT DISTINCT
                message_type

            FROM
                sms_delivery_logs

            WHERE
                TRIM(message_type) <> ''

            ORDER BY
                message_type ASC
            SQL
            )
            ->getResultArray();

        return array_values(
            array_filter(
                array_map(
                    static fn(
                        array $row
                    ): string =>
                    trim(
                        (string) (
                            $row['message_type']
                            ?? ''
                        )
                    ),
                    $rows
                )
            )
        );
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function presentSmsRow(
        array $row
    ): array {
        return [
            'id' =>
            (int) (
                $row['id']
                ?? 0
            ),

            'messageType' =>
            trim(
                (string) (
                    $row['message_type']
                    ?? ''
                )
            ),

            'recipient' =>
            $this
                ->maskMobile(
                    (string) (
                        $row['recipient_mobile']
                        ?? ''
                    )
                ),

            'provider' =>
            trim(
                (string) (
                    $row['provider']
                    ?? ''
                )
            ),

            'providerMessageId' =>
            trim(
                (string) (
                    $row['provider_message_id']
                    ?? ''
                )
            ),

            'status' =>
            mb_strtoupper(
                trim(
                    (string) (
                        $row['status']
                        ?? ''
                    )
                )
            ),

            'error' =>
            mb_substr(
                trim(
                    (string) (
                        $row['error_message']
                        ?? ''
                    )
                ),
                0,
                250
            ),

            'createdAt' =>
            trim(
                (string) (
                    $row['created_at']
                    ?? ''
                )
            ),

            'sentAt' =>
            trim(
                (string) (
                    $row['sent_at']
                    ?? ''
                )
            ),

            'failedAt' =>
            trim(
                (string) (
                    $row['failed_at']
                    ?? ''
                )
            ),
        ];
    }

    private function maskMobile(
        string $mobile
    ): string {
        $digits =
            preg_replace(
                '/\D+/',
                '',
                $mobile
            );

        if (
            !is_string(
                $digits
            )
            || $digits === ''
        ) {
            return '—';
        }

        $visible =
            min(
                3,
                strlen(
                    $digits
                )
            );

        return str_repeat(
            'X',
            max(
                0,
                strlen(
                    $digits
                )
                    - $visible
            )
        )
            . substr(
                $digits,
                -$visible
            );
    }

    /**
     * Queue counts are intentionally calculated independently from the
     * current listing filter so the cards always describe overall queue
     * health.
     *
     * @return array<string, int>
     */
    private function emailQueueSummary(): array
    {
        $rows =
            $this
            ->database
            ->query(
                '
                SELECT
                    status,
                    COUNT(*) AS total
                FROM
                    email_queue
                GROUP BY
                    status
                '
            )
            ->getResultArray();

        $summary = [
            'total' =>
            0,

            EmailQueueModel
            ::STATUS_PENDING =>
            0,

            EmailQueueModel
            ::STATUS_PROCESSING =>
            0,

            EmailQueueModel
            ::STATUS_SENT =>
            0,

            EmailQueueModel
            ::STATUS_FAILED =>
            0,
        ];

        foreach ($rows as $row) {
            $status =
                mb_strtoupper(
                    trim(
                        (string) (
                            $row['status']
                            ?? ''
                        )
                    )
                );

            $count =
                max(
                    0,
                    (int) (
                        $row['total']
                        ?? 0
                    )
                );

            $summary['total'] +=
                $count;

            if (
                array_key_exists(
                    $status,
                    $summary
                )
            ) {
                $summary[$status] =
                    $count;
            }
        }

        return $summary;
    }

    /**
     * Return current queue-health information.
     *
     * Unlike the lifetime status summary, these values describe work which
     * requires or may require operational attention now.
     *
     * @return array{
     *     readyNow:int,
     *     retryPending:int,
     *     staleProcessing:int,
     *     failed:int,
     *     oldestPendingAt:string,
     *     oldestPendingMinutes:int|null
     * }
     */
    private function emailQueueHealth(): array
    {
        $row =
            $this
            ->database
            ->query(
                '
            SELECT
                COUNT(*) FILTER (
                    WHERE
                        status = ?
                        AND available_at <= CURRENT_TIMESTAMP
                        AND attempts < max_attempts
                ) AS ready_now,

                COUNT(*) FILTER (
                    WHERE
                        status = ?
                        AND attempts > 0
                        AND attempts < max_attempts
                ) AS retry_pending,

                COUNT(*) FILTER (
                    WHERE
                        status = ?
                        AND locked_at IS NOT NULL
                        AND locked_at
                            <= CURRENT_TIMESTAMP
                            - (? * INTERVAL \'1 minute\')
                ) AS stale_processing,

                COUNT(*) FILTER (
                    WHERE
                        status = ?
                ) AS failed,

                MIN(created_at) FILTER (
                    WHERE
                        status = ?
                ) AS oldest_pending_at

            FROM
                email_queue
            ',
                [
                    EmailQueueModel
                    ::STATUS_PENDING,

                    EmailQueueModel
                    ::STATUS_PENDING,

                    EmailQueueModel
                    ::STATUS_PROCESSING,

                    self
                    ::STALE_PROCESSING_MINUTES,

                    EmailQueueModel
                    ::STATUS_FAILED,

                    EmailQueueModel
                    ::STATUS_PENDING,
                ]
            )
            ->getRowArray();

        $oldestPendingAt =
            trim(
                (string) (
                    $row['oldest_pending_at']
                    ?? ''
                )
            );

        return [
            'readyNow' =>
            max(
                0,
                (int) (
                    $row['ready_now']
                    ?? 0
                )
            ),

            'retryPending' =>
            max(
                0,
                (int) (
                    $row['retry_pending']
                    ?? 0
                )
            ),

            'staleProcessing' =>
            max(
                0,
                (int) (
                    $row['stale_processing']
                    ?? 0
                )
            ),

            'failed' =>
            max(
                0,
                (int) (
                    $row['failed']
                    ?? 0
                )
            ),

            'oldestPendingAt' =>
            $oldestPendingAt,

            'oldestPendingMinutes' =>
            $this
                ->pendingAgeMinutes(
                    $oldestPendingAt
                ),
        ];
    }

    /**
     * Calculate operational age from the database's UTC timestamp.
     *
     * This value is used only for queue-health presentation. Member-facing
     * date formatting continues to use DateDisplay in the View.
     */
    private function pendingAgeMinutes(
        string $createdAt
    ): ?int {
        if ($createdAt === '') {
            return null;
        }

        $row =
            $this
            ->database
            ->query(
                '
            SELECT
                GREATEST(
                    0,
                    FLOOR(
                        EXTRACT(
                            EPOCH FROM (
                                CURRENT_TIMESTAMP
                                - ?::timestamp
                            )
                        ) / 60
                    )
                ) AS age_minutes
            ',
                [
                    $createdAt,
                ]
            )
            ->getRowArray();

        if (
            !isset(
                $row['age_minutes']
            )
            || !is_numeric(
                $row['age_minutes']
            )
        ) {
            return null;
        }

        return max(
            0,
            (int) $row['age_minutes']
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function findEmailQueue(
        string $status,
        string $referenceType,
        string $search,
        int $page,
        int $perPage
    ): array {
        $builder =
            $this
            ->emailQueueModel
            ->builder();

        $builder
            ->select(
                [
                    'id',
                    'recipient_email',
                    'recipient_name',
                    'subject',
                    'status',
                    'priority',
                    'attempts',
                    'max_attempts',
                    'available_at',
                    'sent_at',
                    'failed_at',
                    'last_error',
                    'reference_type',
                    'reference_id',
                    'created_at',
                    'updated_at',
                ]
            );

        $this
            ->applyEmailQueueFilters(
                $builder,
                $status,
                $referenceType,
                $search
            );

        return $builder
            ->orderBy(
                'id',
                'DESC'
            )
            ->limit(
                $perPage,
                ($page - 1)
                    * $perPage
            )
            ->get()
            ->getResultArray();
    }

    private function countEmailQueue(
        string $status,
        string $referenceType,
        string $search
    ): int {
        $builder =
            $this
            ->emailQueueModel
            ->builder();

        $this
            ->applyEmailQueueFilters(
                $builder,
                $status,
                $referenceType,
                $search
            );

        $row =
            $builder
            ->select(
                'COUNT(*) AS total',
                false
            )
            ->get()
            ->getRowArray();

        return max(
            0,
            (int) (
                $row['total']
                ?? 0
            )
        );
    }

    /**
     * @param \CodeIgniter\Database\BaseBuilder $builder
     */
    private function applyEmailQueueFilters(
        $builder,
        string $status,
        string $referenceType,
        string $search
    ): void {
        if ($status !== '') {
            $builder
                ->where(
                    'status',
                    $status
                );
        }

        if ($referenceType !== '') {
            $builder
                ->where(
                    'reference_type',
                    $referenceType
                );
        }

        if ($search !== '') {
            /*
             * Operational search intentionally uses only:
             *
             * - recipient email;
             * - recipient name;
             * - subject.
             *
             * view_data is excluded because it may contain application
             * presentation data that should not become searchable through
             * an administration queue screen.
             */
            $builder
                ->groupStart()
                ->like(
                    'recipient_email',
                    $search,
                    'both',
                    true,
                    true
                )
                ->orLike(
                    'recipient_name',
                    $search,
                    'both',
                    true,
                    true
                )
                ->orLike(
                    'subject',
                    $search,
                    'both',
                    true,
                    true
                )
                ->groupEnd();
        }
    }

    /**
     * @return list<string>
     */
    private function emailReferenceTypes(): array
    {
        $rows =
            $this
            ->database
            ->query(
                '
                SELECT DISTINCT
                    reference_type
                FROM
                    email_queue
                WHERE
                    reference_type IS NOT NULL
                    AND TRIM(reference_type) <> \'\'
                ORDER BY
                    reference_type ASC
                '
            )
            ->getResultArray();

        return array_values(
            array_filter(
                array_map(
                    static fn(array $row): string =>
                    trim(
                        (string) (
                            $row['reference_type']
                            ?? ''
                        )
                    ),
                    $rows
                ),
                static fn(string $value): bool =>
                $value !== ''
            )
        );
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function presentEmailQueueRow(
        array $row
    ): array {
        return [
            'id' =>
            (int) (
                $row['id']
                ?? 0
            ),

            'recipient' =>
            $this
                ->maskEmail(
                    (string) (
                        $row['recipient_email']
                        ?? ''
                    )
                ),

            'recipientName' =>
            trim(
                (string) (
                    $row['recipient_name']
                    ?? ''
                )
            ),

            'subject' =>
            trim(
                (string) (
                    $row['subject']
                    ?? ''
                )
            ),

            'status' =>
            mb_strtoupper(
                trim(
                    (string) (
                        $row['status']
                        ?? ''
                    )
                )
            ),

            'priority' =>
            (int) (
                $row['priority']
                ?? 0
            ),

            'attempts' =>
            max(
                0,
                (int) (
                    $row['attempts']
                    ?? 0
                )
            ),

            'maxAttempts' =>
            max(
                0,
                (int) (
                    $row['max_attempts']
                    ?? 0
                )
            ),

            'referenceType' =>
            trim(
                (string) (
                    $row['reference_type']
                    ?? ''
                )
            ),

            'referenceId' =>
            isset(
                $row['reference_id']
            )
                && $row['reference_id'] !== null
                ? (int) $row['reference_id']
                : null,

            'createdAt' =>
            (string) (
                $row['created_at']
                ?? ''
            ),

            'availableAt' =>
            (string) (
                $row['available_at']
                ?? ''
            ),

            'sentAt' =>
            (string) (
                $row['sent_at']
                ?? ''
            ),

            'failedAt' =>
            (string) (
                $row['failed_at']
                ?? ''
            ),

            'lastError' =>
            $this
                ->safeError(
                    (string) (
                        $row['last_error']
                        ?? ''
                    )
                ),
        ];
    }

    private function normaliseStatus(
        string $status
    ): string {
        $status =
            mb_strtoupper(
                trim(
                    $status
                )
            );

        if (
            !in_array(
                $status,
                [
                    EmailQueueModel
                    ::STATUS_PENDING,

                    EmailQueueModel
                    ::STATUS_PROCESSING,

                    EmailQueueModel
                    ::STATUS_SENT,

                    EmailQueueModel
                    ::STATUS_FAILED,
                ],
                true
            )
        ) {
            return '';
        }

        return $status;
    }

    private function normaliseReferenceType(
        string $referenceType
    ): string {
        return mb_substr(
            mb_strtoupper(
                trim(
                    $referenceType
                )
            ),
            0,
            100
        );
    }

    /**
     * Operational screens should identify the destination sufficiently for
     * troubleshooting without unnecessarily displaying the full address.
     */
    private function maskEmail(
        string $email
    ): string {
        $email =
            mb_strtolower(
                trim(
                    $email
                )
            );

        if (
            $email === ''
            || !str_contains(
                $email,
                '@'
            )
        ) {
            return '';
        }

        [
            $localPart,
            $domain,
        ] =
            explode(
                '@',
                $email,
                2
            );

        if ($localPart === '') {
            return '***@'
                . $domain;
        }

        $visible =
            mb_substr(
                $localPart,
                0,
                1
            );

        return $visible
            . '***@'
            . $domain;
    }

    /**
     * Error messages may originate from providers or infrastructure.
     *
     * Keep the operational value useful but bounded and single-line.
     */
    private function safeError(
        string $error
    ): string {
        $error =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    $error
                )
            )
            ?? '';

        return mb_substr(
            $error,
            0,
            300
        );
    }
}
