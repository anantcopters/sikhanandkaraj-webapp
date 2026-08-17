<?php

declare(strict_types=1);

namespace App\Services\Account;

use App\Models\MemberProfileReportModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use DomainException;
use RuntimeException;

/**
 * Owns profile-safety reports submitted by members.
 */
final class MemberProfileReportService
{
    public function __construct(
        private readonly UserModel $userModel,
        private readonly MemberProfileReportModel
        $reportModel
    ) {}

    public function report(
        int $reporterUserId,
        string $reportedProfileReference,
        string $description
    ): void {
        $description = preg_replace(
            '/\s+/u',
            ' ',
            trim($description)
        ) ?? '';

        if (
            mb_strlen($description) < 10
            || mb_strlen($description) > 1000
        ) {
            throw new DomainException(
                'The report description must contain '
                    . 'between 10 and 1000 characters.'
            );
        }

        $reportedMember = $this
            ->userModel
            ->findActiveByProfileReference(
                mb_strtoupper(
                    trim(
                        $reportedProfileReference
                    )
                )
            );

        if (!is_array($reportedMember)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $reportedUserId = (int) (
            $reportedMember['id']
            ?? 0
        );

        if (
            $reportedUserId <= 0
            || $reportedUserId === $reporterUserId
        ) {
            throw new DomainException(
                'You cannot report your own profile.'
            );
        }

        if (
            $this->reportModel
            ->hasOpenReport(
                $reporterUserId,
                $reportedUserId
            )
        ) {
            throw new DomainException(
                'You have already reported this profile. '
                    . 'The report is awaiting administrator review.'
            );
        }

        $inserted = $this
            ->reportModel
            ->insert(
                [
                    'reporter_user_id' =>
                    $reporterUserId,

                    'reported_user_id' =>
                    $reportedUserId,

                    'description' =>
                    $description,

                    'status' =>
                    MemberProfileReportModel::STATUS_OPEN,
                ],
                true
            );

        if (!is_numeric($inserted)) {
            throw new RuntimeException(
                'The profile report could not be saved.'
            );
        }
    }
}
