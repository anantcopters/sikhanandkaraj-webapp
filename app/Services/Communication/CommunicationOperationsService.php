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

    public function __construct(
        private readonly EmailQueueModel
        $emailQueueModel,

        private readonly SmsDeliveryLogModel
        $smsDeliveryLogModel,

        private readonly BaseConnection
        $database
    ) {}

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
         * Basic Phase 4E:
         *
         * Reuse authoritative contact_verifications rather than maintaining
         * another OTP counter in sms_delivery_logs.
         */
            'otpAlerts' =>
            $this
                ->otpLimitAlerts(),

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
     * Basic OTP abuse visibility.
     *
     * The existing contact_verifications table remains authoritative for OTP
     * issuance/rate limiting. This query only exposes contacts which have reached
     * the existing five-request threshold during the rolling 24-hour period.
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
                    max(
                        0,
                        (int) (
                            $row['request_count']
                            ?? 0
                        )
                    ),

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
