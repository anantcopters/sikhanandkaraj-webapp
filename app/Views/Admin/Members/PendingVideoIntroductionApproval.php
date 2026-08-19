<?php

declare(strict_types=1);

/**
 * @var list<array<string, mixed>> $videos
 */

$videos =
    isset($videos)
    && is_array($videos)
    ? $videos
    : [];

$this->extend('Admin/Layouts/Main');

$this->section('content');
?>

<section class="py-3 py-lg-4">
    <div class="container-fluid px-3 px-lg-4">
        <?= view(
            'Components/Alerts/FormAlert',
            [
                'alert' => $formAlert ?? null,
            ]
        ) ?>

        <div class="mb-4">
            <h1 class="fs-24 fw-semibold mb-1">
                Video Introduction Approvals
            </h1>

            <p class="text-muted mb-0">
                Review processed member introductions
                before profile display.
            </p>
        </div>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Profile ID</th>
                            <th>Member</th>
                            <th>Duration</th>
                            <th>Submitted</th>
                            <th class="text-end">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($videos === []): ?>
                            <tr>
                                <td
                                    colspan="5"
                                    class="text-center
                                        text-muted py-5">

                                    No Video Introductions
                                    are awaiting review.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($videos as $video): ?>
                                <tr>
                                    <td>
                                        <?= esc(
                                            (string) (
                                                $video['profile_ref_number']
                                                ?? ''
                                            )
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= esc(
                                            (string) (
                                                $video['full_name']
                                                ?? 'Member'
                                            )
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= esc(
                                            number_format(
                                                (float) (
                                                    $video['duration_seconds']
                                                    ?? 0
                                                ),
                                                1
                                            )
                                        ) ?>s
                                    </td>

                                    <td>
                                        <?= esc(
                                            (string) (
                                                $video['submitted_at']
                                                ?? ''
                                            )
                                        ) ?>
                                    </td>

                                    <td class="text-end">
                                        <a
                                            class="btn btn-sm
                                                btn-outline-primary"
                                            href="<?= route_to(
                                                        'admin.members.video-introductions.review',
                                                        (string) $video['public_id']
                                                    ) ?>">

                                            <i
                                                class="ri-eye-line me-1"
                                                aria-hidden="true">
                                            </i>

                                            Review
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>