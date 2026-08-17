<?php

declare(strict_types=1);

namespace App\Services\Account;

use App\Models\MemberProfileReportModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use DomainException;
use RuntimeException;

final class MemberProfileReportService
{
    public function __construct(
        private readonly UserModel $userModel,
        private readonly MemberProfileReportModel
        $reportModel
    ) {}

    /**
     * Return reports raised by the authenticated member.
     *
     * @return list<array<string, mixed>>
     */
    public function historyForReporter(
        int $reporterUserId
    ): array {
        if ($reporterUserId <= 0) {
            return [];
        }

        return $this
            ->reportModel
            ->historyForReporter(
                $reporterUserId
            );
    }

    public function hasReportedProfile(
        int $reporterUserId,
        string $profileReference
    ): bool {
        if ($reporterUserId <= 0) {
            return false;
        }

        $reportedMember = $this
            ->findReportedMember(
                $profileReference
            );

        if (!is_array($reportedMember)) {
            return false;
        }

        $reportedUserId = (int) (
            $reportedMember['id']
            ?? 0
        );

        return $reportedUserId > 0
            && $this
            ->reportModel
            ->hasReport(
                $reporterUserId,
                $reportedUserId
            );
    }

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
            ->findReportedMember(
                $reportedProfileReference
            );

        if (!is_array($reportedMember)) {
            throw PageNotFoundException
                ::forPageNotFound();
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
            ->hasReport(
                $reporterUserId,
                $reportedUserId
            )
        ) {
            throw new DomainException(
                'You have already reported this profile.'
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
                    MemberProfileReportModel
                    ::STATUS_OPEN,
                ],
                true
            );

        if (!is_numeric($inserted)) {
            /*
             * The database unique index also protects against two
             * simultaneous submissions by the same reporter.
             */
            if (
                $this->reportModel
                ->hasReport(
                    $reporterUserId,
                    $reportedUserId
                )
            ) {
                throw new DomainException(
                    'You have already reported this profile.'
                );
            }

            throw new RuntimeException(
                'The profile report could not be saved.'
            );
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findReportedMember(
        string $profileReference
    ): ?array {
        $profileReference = mb_strtoupper(
            trim($profileReference)
        );

        if ($profileReference === '') {
            return null;
        }

        $member = $this
            ->userModel
            ->findActiveByProfileReference(
                $profileReference
            );

        return is_array($member)
            ? $member
            : null;
    }
}
