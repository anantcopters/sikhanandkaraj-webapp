<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $communicationPreferences
 */

$matrimonialActivity =
    isset(
        $communicationPreferences['matrimonial_activity']
    )
    && is_array(
        $communicationPreferences['matrimonial_activity']
    )
    ? $communicationPreferences['matrimonial_activity']
    : [];

$engagement =
    isset(
        $communicationPreferences['engagement']
    )
    && is_array(
        $communicationPreferences['engagement']
    )
    ? $communicationPreferences['engagement']
    : [];
?>

<div class="mb-4">
    <h5 class="mb-1">
        Communication Preferences
    </h5>

    <p class="text-muted mb-0">
        Choose which optional email updates you would like
        to receive from SikhanandKaraj.
    </p>
</div>

<form
    method="post"
    action="<?= route_to(
                'web.account.settings.communication-preferences'
            ) ?>"
    data-form-validator>

    <?= csrf_field() ?>

    <!--
        Essential communication is intentionally informational.

        These categories are server-controlled and cannot be disabled
        through member preferences.
    -->
    <div class="mb-4">
        <h6 class="mb-2">
            Essential Communication
        </h6>

        <p class="text-muted mb-0">
            Account security, verification, membership,
            moderation and support emails are sent when required
            and cannot be disabled.
        </p>
    </div>

    <hr>

    <div
        class="
            d-flex
            align-items-center
            justify-content-between
            gap-3
            py-3
        ">

        <div>
            <h6 class="mb-1">
                Matrimonial Activity
            </h6>

            <p class="text-muted mb-0">
                Receive email updates for important Interest
                activity such as received, accepted or declined
                Interests.
            </p>
        </div>

        <div
            class="
                form-check
                form-switch
                flex-shrink-0
            ">

            <input
                class="form-check-input"
                type="checkbox"
                role="switch"
                id="matrimonialActivityEmail"
                name="matrimonial_activity_email"
                value="1"
                <?= !empty($matrimonialActivity['enabled'])
                    ? 'checked'
                    : '' ?>>

            <label
                class="form-check-label"
                for="matrimonialActivityEmail">
                Email
            </label>

        </div>

    </div>

    <hr>

    <div
        class="
            d-flex
            align-items-center
            justify-content-between
            gap-3
            py-3
        ">

        <div>
            <h6 class="mb-1">
                Matches & Recommendations
            </h6>

            <p class="text-muted mb-0">
                Receive optional match and recommendation emails
                when these communications are introduced.
            </p>
        </div>

        <div
            class="
                form-check
                form-switch
                flex-shrink-0
            ">

            <input
                class="form-check-input"
                type="checkbox"
                role="switch"
                id="engagementEmail"
                name="engagement_email"
                value="1"
                <?= !empty($engagement['enabled'])
                    ? 'checked'
                    : '' ?>>

            <label
                class="form-check-label"
                for="engagementEmail">
                Email
            </label>

        </div>

    </div>
    <div class="d-flex justify-content-end">
        <button
            type="submit"
            class="btn
                registration-form__submit
                fs-14
                fw-semibold w-25 text-uppercase"
            data-submit-button>

            <span data-submit-idle>
                <i
                    class="mdi
                        mdi-cloud-upload-outline me-1 fw-medium"
                    aria-hidden="true">
                </i>

                Save Preferences
            </span>

            <span
                class="registration-submit__loading d-none"
                data-submit-loading>

                <span
                    class="spinner-border spinner-border-sm"
                    aria-hidden="true">
                </span>

                Saving...
            </span>
        </button>
    </div>

</form>