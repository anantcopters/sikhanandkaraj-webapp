<?php

declare(strict_types=1);

/**
 * @var array<string, mixed>|null $currentAccount
 */

$currentAccount =
    isset($currentAccount)
    && is_array($currentAccount)
    ? $currentAccount
    : [];

$currentPlanCode =
    mb_strtoupper(
        trim(
            (string) (
                $currentAccount['accountType']
                ?? 'FREE'
            )
        )
    );

$currentMembership =
    isset($currentAccount['membership'])
    && is_array($currentAccount['membership'])
    ? $currentAccount['membership']
    : null;

$isPaid =
    ($currentAccount['isPaid'] ?? false)
    === true;

$currentMembershipExpiry =
    $currentMembership !== null
    ? trim(
        (string) (
            $currentMembership['expiresAtDisplay']
            ?? ''
        )
    )
    : '';

?>

<?php if (
    $isPaid
    && $currentMembership !== null
): ?>

    <div
        class="
            alert
            alert-info
            border
            border-danger
            border-opacity-25
            mb-4
        ">

        <div
            class="
                d-flex
                align-items-center
                justify-content-between
                flex-wrap
                gap-3
            ">

            <div>

                <div
                    class="
                        fs-12
                        fw-semibold
                        text-danger
                        text-uppercase
                        mb-1
                    ">
                    Current Membership
                </div>

                <div
                    class="
                        d-flex
                        align-items-center
                        gap-2
                    ">

                    <?= view(
                        'Components/Membership/PlanLogo',
                        [
                            'planCode' =>
                            $currentPlanCode,

                            'width' =>
                            180,
                        ]
                    ) ?>

                </div>

            </div>

        </div>

        <?php if (
            $currentMembershipExpiry !== ''
        ): ?>

            <div class="fs-13 text-muted mt-2">

                Your current membership remains active until

                <strong class="text-body">
                    <?= esc(
                        $currentMembershipExpiry
                    ) ?>
                </strong>.

            </div>

        <?php endif; ?>

        <div class="fs-12 text-muted mt-2">

            An upgrade or renewal starts immediately after
            successful payment. Remaining days and unused
            allowances from the current membership are not
            carried forward.

        </div>

    </div>

<?php else: ?>

    <div
        class="
            alert
            alert-info
            border
            mb-4
        ">

        <div class="fw-semibold">
            Free Account
        </div>

        <div class="fs-13 text-muted mt-1">
            Choose a membership to unlock paid membership
            benefits.
        </div>

    </div>

<?php endif; ?>