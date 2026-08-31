<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\EmailQueueModel;
use CodeIgniter\Database\BaseConnection;

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

    public function __construct(
        private readonly EmailQueueModel
        $emailQueueModel,

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
