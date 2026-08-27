<?php

declare(strict_types=1);

$showReminder =
    ($showPaidAadhaarReminder ?? false)
    === true;

$status =
    mb_strtoupper(
        trim(
            (string) (
                $paidAadhaarReminderStatus
                ?? ''
            )
        )
    );

if (!$showReminder) {
    return;
}

$message =
    $status === 'REJECTED'
    ? 'Your Aadhaar verification was not approved. '
    . 'Please submit your Aadhaar again to complete verification.'
    : 'Your paid membership is active. '
    . 'Please submit your Aadhaar to complete profile verification.';
?>

<div class="container pt-3">

    <div
        class="
            alert
            alert-warning
            border
            d-flex
            flex-column
            flex-md-row
            align-items-md-center
            justify-content-between
            gap-3
            mb-0
        "
        role="alert">

        <div
            class="
                d-flex
                align-items-start
                gap-2
            ">

            <i
                class="
                    ri-shield-check-line
                    fs-20
                "
                aria-hidden="true">
            </i>

            <div>

                <div class="fw-semibold">
                    Complete Aadhaar Verification
                </div>

                <div class="fs-13 mt-1">
                    <?= esc($message) ?>
                </div>

            </div>

        </div>

        <a
            href="<?= route_to(
                        'web.account.settings.section',
                        'aadhaar-verification'
                    ) ?>"
            class="
                btn
                btn-outline-danger
                btn-sm
                flex-shrink-0
            ">

            <i
                class="ri-upload-2-line me-1"
                aria-hidden="true">
            </i>

            Add Aadhaar
        </a>

    </div>

</div>