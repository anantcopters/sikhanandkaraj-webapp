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

$engagementFrequency =
    mb_strtoupper(
        trim(
            (string) (
                $engagement['frequency']
                ?? 'DAILY'
            )
        )
    );
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
    data-form-validator
    data-submit-loader>

    <?= csrf_field() ?>

    <!--
        Essential communication remains server-controlled and
        cannot be disabled through member preferences.
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
                Receive important Interest activity such as
                received, accepted or declined Interests.
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

    <div class="row align-items-center py-3">

        <div class="col-lg-8">

            <h6 class="mb-1">
                Matches & Recommendations
            </h6>

            <p class="text-muted mb-0">
                Choose how often you would like to receive
                profile activity, match and recommendation emails.
            </p>

        </div>

        <div class="col-lg-4 mt-3 mt-lg-0">

            <label
                class="form-label"
                for="engagementFrequency">
                Email Frequency
            </label>

            <select
                class="form-select"
                id="engagementFrequency"
                name="engagement_frequency"
                data-choice
                data-choice-search="false"
                required>

                <option
                    value="DAILY"
                    <?= $engagementFrequency === 'DAILY'
                        ? 'selected'
                        : '' ?>>
                    Daily Digest
                </option>

                <option
                    value="WEEKLY"
                    <?= $engagementFrequency === 'WEEKLY'
                        ? 'selected'
                        : '' ?>>
                    Weekly Digest
                </option>

                <option
                    value="OFF"
                    <?= $engagementFrequency === 'OFF'
                        ? 'selected'
                        : '' ?>>
                    Do Not Email
                </option>

            </select>

        </div>

    </div>

    <div class="d-flex justify-content-end mt-4">

        <button
            type="submit"
            class="
                btn
                registration-form__submit
                fs-14
                fw-semibold
                w-25
                text-uppercase
            "
            data-submit-button>

            <span data-submit-idle>

                <i
                    class="
                        mdi
                        mdi-cloud-upload-outline
                        me-1
                        fw-medium
                    "
                    aria-hidden="true">
                </i>

                Save Preferences

            </span>

            <span
                class="
                    registration-submit__loading
                    d-none
                "
                data-submit-loading>

                <span
                    class="
                        spinner-border
                        spinner-border-sm
                    "
                    aria-hidden="true">
                </span>

                Saving...

            </span>

        </button>

    </div>

</form>