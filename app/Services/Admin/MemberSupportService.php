<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\MemberContactRequestModel;
use App\Models\MemberProfileReportModel;
use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Owns administrator review of profile reports and contact requests.
 */
final class MemberSupportService
{
    private const PER_PAGE = 20;

    public function __construct(
        private readonly MemberProfileReportModel
        $reportModel,

        private readonly MemberContactRequestModel
        $contactRequestModel,

        private readonly BaseConnection $database
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function reportPage(
        string $status,
        string $search
    ): array {
        $status = $this->normaliseReportFilter(
            $status
        );

        $search = $this->normaliseSearch(
            $search
        );

        $this->reportModel
            ->prepareAdminListing(
                $status,
                $search
            );

        $reports = $this
            ->reportModel
            ->paginate(
                self::PER_PAGE,
                'memberReports'
            );

        return [
            'reports' =>
            is_array($reports)
                ? $reports
                : [],

            'pager' =>
            $this->reportModel->pager,

            'status' =>
            $status,

            'search' =>
            $search,

            'perPage' =>
            self::PER_PAGE,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function contactPage(
        string $status,
        string $search
    ): array {
        $status = $this->normaliseContactFilter(
            $status
        );

        $search = $this->normaliseSearch(
            $search
        );

        $this->contactRequestModel
            ->prepareAdminListing(
                $status,
                $search
            );

        $requests = $this
            ->contactRequestModel
            ->paginate(
                self::PER_PAGE,
                'memberContactRequests'
            );

        return [
            'requests' =>
            is_array($requests)
                ? $requests
                : [],

            'pager' =>
            $this->contactRequestModel->pager,

            'status' =>
            $status,

            'search' =>
            $search,

            'perPage' =>
            self::PER_PAGE,
        ];
    }

    public function reviewReport(
        int $reportId,
        int $adminUserId,
        string $status,
        string $resolutionNote
    ): void {
        if (
            !in_array(
                $status,
                [
                    MemberProfileReportModel::STATUS_REVIEWED,
                    MemberProfileReportModel::STATUS_DISMISSED,
                    MemberProfileReportModel::STATUS_ACTION_TAKEN,
                ],
                true
            )
        ) {
            throw new DomainException(
                'The selected report status is invalid.'
            );
        }

        $resolutionNote = $this
            ->normaliseNote(
                $resolutionNote,
                1000
            );

        $this->database->transBegin();

        try {
            $report = $this
                ->reportModel
                ->where(
                    'id',
                    $reportId
                )
                ->where(
                    'status',
                    MemberProfileReportModel::STATUS_OPEN
                )
                ->first();

            if (!is_array($report)) {
                throw new DomainException(
                    'This report has already been reviewed '
                        . 'or is unavailable.'
                );
            }

            $updated = $this
                ->reportModel
                ->where(
                    'id',
                    $reportId
                )
                ->where(
                    'status',
                    MemberProfileReportModel::STATUS_OPEN
                )
                ->set([
                    'status' =>
                    $status,

                    'reviewed_by_admin_id' =>
                    $adminUserId,

                    'reviewed_at' =>
                    gmdate('Y-m-d H:i:s')
                        . '+00:00',

                    'resolution_note' =>
                    $resolutionNote,
                ])
                ->update();

            if (
                $updated !== true
                || $this->database->affectedRows() !== 1
            ) {
                throw new DomainException(
                    'This report has already been reviewed.'
                );
            }

            if (!$this->database->transStatus()) {
                throw new RuntimeException(
                    'The report review transaction failed.'
                );
            }

            $this->database->transCommit();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    public function reviewContactRequest(
        int $requestId,
        int $adminUserId,
        string $status,
        string $responseNote
    ): void {
        $allowedStatuses = [
            MemberContactRequestModel::STATUS_IN_PROGRESS,
            MemberContactRequestModel::STATUS_RESOLVED,
            MemberContactRequestModel::STATUS_CLOSED,
        ];

        if (
            !in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {
            throw new DomainException(
                'The selected request status is invalid.'
            );
        }

        $responseNote = $this
            ->normaliseNote(
                $responseNote,
                2000
            );

        $this->database->transBegin();

        try {
            $request = $this
                ->contactRequestModel
                ->where(
                    'id',
                    $requestId
                )
                ->whereIn(
                    'status',
                    [
                        MemberContactRequestModel::STATUS_OPEN,
                        MemberContactRequestModel::STATUS_IN_PROGRESS,
                    ]
                )
                ->first();

            if (!is_array($request)) {
                throw new DomainException(
                    'This request is already closed or unavailable.'
                );
            }

            $currentStatus = strtoupper(
                trim(
                    (string) (
                        $request['status']
                        ?? ''
                    )
                )
            );

            if (
                $currentStatus
                === MemberContactRequestModel::STATUS_IN_PROGRESS
                && $status
                === MemberContactRequestModel::STATUS_IN_PROGRESS
            ) {
                throw new DomainException(
                    'This request is already in progress.'
                );
            }

            $updated = $this
                ->contactRequestModel
                ->where(
                    'id',
                    $requestId
                )
                ->where(
                    'status',
                    $currentStatus
                )
                ->set([
                    'status' =>
                    $status,

                    'reviewed_by_admin_id' =>
                    $adminUserId,

                    'reviewed_at' =>
                    gmdate('Y-m-d H:i:s')
                        . '+00:00',

                    'response_note' =>
                    $responseNote,
                ])
                ->update();

            if (
                $updated !== true
                || $this->database->affectedRows() !== 1
            ) {
                throw new DomainException(
                    'This request was updated by another administrator.'
                );
            }

            if (!$this->database->transStatus()) {
                throw new RuntimeException(
                    'The request review transaction failed.'
                );
            }

            $this->database->transCommit();
        } catch (Throwable $exception) {
            $this->database->transRollback();

            throw $exception;
        }
    }

    private function normaliseReportFilter(
        string $status
    ): string {
        $status = mb_strtoupper(
            trim($status)
        );

        return in_array(
            $status,
            [
                'ALL',
                'OPEN',
                'REVIEWED',
                'DISMISSED',
                'ACTION_TAKEN',
            ],
            true
        )
            ? $status
            : 'OPEN';
    }

    private function normaliseContactFilter(
        string $status
    ): string {
        $status = mb_strtoupper(
            trim($status)
        );

        return in_array(
            $status,
            [
                'ALL',
                'OPEN',
                'IN_PROGRESS',
                'RESOLVED',
                'CLOSED',
            ],
            true
        )
            ? $status
            : 'OPEN';
    }

    private function normaliseSearch(
        string $search
    ): string {
        $search = preg_replace(
            '/\s+/u',
            ' ',
            trim($search)
        ) ?? '';

        return mb_substr(
            $search,
            0,
            100
        );
    }

    private function normaliseNote(
        string $note,
        int $maximumLength
    ): string {
        $note = preg_replace(
            '/\s+/u',
            ' ',
            trim($note)
        ) ?? '';

        if (
            mb_strlen($note) < 5
            || mb_strlen($note) > $maximumLength
        ) {
            throw new DomainException(
                'Please enter a valid review note.'
            );
        }

        return $note;
    }
}
