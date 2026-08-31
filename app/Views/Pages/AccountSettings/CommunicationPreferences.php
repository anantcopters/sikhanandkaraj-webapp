<?php

/**
 * @var array $communicationPreferences
 */

$matrimonialActivity =
    $communicationPreferences['matrimonial_activity']
    ?? [];

$engagement =
    $communicationPreferences['engagement']
    ?? [];
?>

<div class="card">
    <div class="card-body">

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
                Essential communication
                ----------------------------------------------------------
                These are intentionally informational rather than switches.

                Security, verification, membership transactions,
                moderation decisions and active support communication
                cannot be disabled because they are required to operate
                the member's account safely.
            -->
            <div class="mb-4">

                <h6 class="mb-2">
                    Essential communication
                </h6>

                <p class="text-muted mb-0">
                    Account security, verification, membership,
                    moderation and support emails are sent when required
                    and cannot be disabled.
                </p>

            </div>

            <hr>

            <!-- Matrimonial Activity -->
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

                <div class="form-check form-switch flex-shrink-0">

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

            <!-- Engagement -->
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

                <div class="form-check form-switch flex-shrink-0">

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

            <!--
                Existing standardized submit-button structure.

                This intentionally follows the registration/save-button
                pattern already standardized across the project.
            -->
            <div class="text-end mt-4">

                <button
                    type="submit"
                    class="
                        btn
                        registration-form__submit
                        fs-14
                        fw-medium
                        text-uppercase
                    "
                    data-submit-button>

                    <span
                        class="registration-submit__idle"
                        data-submit-idle>
                        <i
                            class="
                                mdi
                                mdi-cloud-upload-outline
                                fs-20
                            "></i>

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
                            role="status"
                            aria-hidden="true"></span>

                        Saving...
                    </span>

                </button>

            </div>

        </form>

    </div>
</div>