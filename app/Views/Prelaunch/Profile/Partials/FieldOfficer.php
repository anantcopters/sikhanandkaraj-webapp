<?php

declare(strict_types=1);

/**
 * Production-only SAK Volunteer verification.
 *
 * @var array<string, string>|null $validationErrors
 */

$errorBag = is_array(
    $validationErrors ?? null
)
    ? $validationErrors
    : [];

$fieldOfficerCode = mb_strtoupper(
    trim(
        (string) old(
            'field_officer_code',
            ''
        )
    )
);

$fieldOfficerCodeError = trim(
    (string) (
        $errorBag['field_officer_code']
        ?? ''
    )
);

$verifiedOfficerError = trim(
    (string) (
        $errorBag['verified_field_officer_id']
        ?? ''
    )
);

$fieldOfficerDisplayError =
    $fieldOfficerCodeError !== ''
    ? $fieldOfficerCodeError
    : $verifiedOfficerError;

$fieldOfficerClass =
    $fieldOfficerDisplayError !== ''
    ? 'is-invalid'
    : '';

$verificationUrl = url_to(
    'prelaunch.field-officer.verify'
);
?>

<div
    class="card border border-danger border-opacity-25 shadow-sm mb-3">

    <div class="card-body p-3 p-md-4">

        <div class="d-flex align-items-start gap-3 mb-3">

            <div class="fs-3 text-primary">
                <i
                    class="ri-shield-user-line"
                    aria-hidden="true">
                </i>
            </div>

            <div>
                <h5 class="mb-1 fs-14 fw-semibold">
                    SAK Volunteer verification
                </h5>

                <p class="text-muted mb-0 fs-12">
                    Enter and verify the SAK Volunteer code
                    before saving the profile.
                </p>
            </div>
        </div>

        <hr class="my-2 mb-3">

        <div class="row g-3">

            <div class="col-12 col-md-8">

                <label
                    for="field_officer_code"
                    class="form-label">

                    SAK Volunteer ID
                </label>

                <input
                    type="text"
                    id="field_officer_code"
                    name="field_officer_code"
                    class="form-control text-uppercase <?= esc(
                                                            $fieldOfficerClass,
                                                            'attr'
                                                        ) ?>"
                    value="<?= esc(
                                $fieldOfficerCode,
                                'attr'
                            ) ?>"
                    placeholder="Example: FOSAK000123"
                    maxlength="11"
                    pattern="FOSAK[0-9]{6}"
                    autocomplete="off"
                    required
                    aria-describedby="field_officer_codeError"
                    data-error-required="Please enter the SAK Volunteer ID."
                    data-error-pattern="Please enter a valid SAK Volunteer ID."
                    data-error-maxlength="Please enter a valid SAK Volunteer ID.">

                <input
                    type="hidden"
                    id="verified_field_officer_id"
                    name="verified_field_officer_id"
                    value="">

                <div
                    id="field_officer_codeError"
                    class="invalid-feedback"
                    data-validation-error="field_officer_code">

                    <?= esc(
                        $fieldOfficerDisplayError
                    ) ?>
                </div>
            </div>

            <div class="col-12 col-md-4">

                <label
                    class="form-label d-none d-md-block"
                    aria-hidden="true">
                    &nbsp;
                </label>

                <button
                    type="button"
                    id="verify-field-officer"
                    class="btn btn-primary w-100"
                    data-verification-url="<?= esc(
                                                $verificationUrl,
                                                'attr'
                                            ) ?>">

                    <span
                        id="verify-field-officer-label">

                        <i
                            class="ri-shield-check-line me-1"
                            aria-hidden="true">
                        </i>

                        Verify
                    </span>

                    <span
                        id="verify-field-officer-loading"
                        class="d-none"
                        aria-hidden="true">

                        <span
                            class="spinner-border spinner-border-sm me-1"
                            aria-hidden="true">
                        </span>

                        Verifying...
                    </span>
                </button>

            </div>

            <div
                id="field-officer-result-column"
                class="col-12 d-none">

                <div
                    id="field-officer-result"
                    class="alert alert-success mb-0"
                    role="status"
                    aria-live="polite">

                    <div
                        class="d-flex align-items-center gap-2">

                        <i
                            id="field-officer-result-icon"
                            class="ri-checkbox-circle-line fs-20"
                            aria-hidden="true">
                        </i>

                        <div>
                            <span class="small d-block">
                                Verified SAK Volunteer
                            </span>

                            <strong
                                id="field-officer-result-message">
                            </strong>

                            <span
                                id="verified-field-officer-code"
                                class="small d-block">
                            </span>

                            <span
                                id="verified-field-officer-location"
                                class="small text-muted d-block">
                            </span>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>