<?php

declare(strict_types=1);

$this->extend(
    'Admin/Layouts/Main'
);

$this->section(
    'content'
);

$conversations =
    isset($conversations)
    && is_array($conversations)
    ? $conversations
    : [];
?>

<div class="container-fluid">

    <div
        class="page-title-box
            d-sm-flex
            align-items-center
            justify-content-between">

        <div>
            <h4 class="mb-sm-0">
                Member Conversations
            </h4>

            <p class="text-muted mb-0 mt-1">
                Private conversation access is for
                support, safety and investigation purposes.
            </p>
        </div>

        <a
            href="<?= route_to(
                        'admin.members.view',
                        $memberId
                    ) ?>"
            class="btn btn-light">

            <i
                class="ri-arrow-left-line me-1"
                aria-hidden="true">
            </i>

            Back to Member

        </a>

    </div>

    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' =>
            $formAlert
                ?? null,
        ]
    ) ?>

    <div
        class="alert
            alert-warning
            d-flex
            gap-2">

        <i
            class="ri-lock-2-line fs-18"
            aria-hidden="true">
        </i>

        <div>
            Private-message access is audited.
            Do not inspect conversations unless required
            for support, complaint investigation,
            fraud/safety review or operational troubleshooting.
        </div>

    </div>

    <div class="card">

        <div class="card-header">

            <h5 class="card-title mb-0">
                <i
                    class="ri-message-3-line me-1"
                    aria-hidden="true">
                </i>

                Conversations
            </h5>

        </div>

        <div class="card-body p-0">

            <?php if ($conversations === []): ?>

                <div
                    class="text-center
                        text-muted
                        p-5">

                    No conversations are available
                    for this member.

                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table
                        class="table
                            table-hover
                            align-middle
                            mb-0">

                        <thead class="table-light">

                            <tr>
                                <th>Conversation</th>
                                <th>Status</th>
                                <th>Created From</th>
                                <th>Last Message</th>
                                <th class="text-end">
                                    Action
                                </th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach (
                                $conversations
                                as $conversation
                            ): ?>

                                <tr>

                                    <td>
                                        #<?= esc(
                                                (string) (
                                                    $conversation['id']
                                                    ?? ''
                                                )
                                            ) ?>
                                    </td>

                                    <td>
                                        <span
                                            class="badge
                                                bg-primary-subtle
                                                text-primary">

                                            <?= esc(
                                                (string) (
                                                    $conversation['status']
                                                    ?? ''
                                                )
                                            ) ?>

                                        </span>
                                    </td>

                                    <td>
                                        <?= esc(
                                            (string) (
                                                $conversation['created_from']
                                                ?? ''
                                            )
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= esc(
                                            (string) (
                                                $conversation['last_message_at']
                                                ?? '—'
                                            )
                                        ) ?>
                                    </td>

                                    <td class="text-end">

                                        <a
                                            href="<?= route_to(
                                                        'admin.members.messages.conversation',
                                                        $memberId,
                                                        (int) (
                                                            $conversation['id']
                                                            ?? 0
                                                        )
                                                    ) ?>"
                                            class="btn
                                                btn-outline-primary
                                                btn-sm">

                                            View Conversation

                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<?php
$this->endSection();
