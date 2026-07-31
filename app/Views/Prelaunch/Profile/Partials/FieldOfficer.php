<?php

declare(strict_types=1);

/**
 * Field Officer verification card.
 *
 * All view variables are prepared before HTML rendering.
 *
 * @var array<string, string>|null $validationErrors
 */

$errorBag = is_array(
    $validationErrors
        ?? null
)
    ? $validationErrors
    : [];

$fieldOfficerCode = (string) old(
    'field_officer_code',
    ''
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

$verificationPath = parse_url(
    $verificationUrl,
    PHP_URL_PATH
);

if (
    !is_string($verificationPath)
    || trim($verificationPath) === ''
) {
    $verificationPath =
        '/prelaunch/field-officer/verify';
}
?>

<div class="card border border-danger border-opacity-25 shadow-sm mb-3">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex align-items-start gap-3 mb-3">
            <div class="fs-3 text-primary">
                <i
                    class="ri-shield-user-line"
                    aria-hidden="true"></i>
            </div>

            <div>
                <h5 class="mb-1 fs-14 fw-semibold">
                    Field Officer verification
                </h5>

                <p class="text-muted mb-0 fs-12">
                    Enter and verify the Field Officer code before
                    saving the profile.
                </p>
            </div>
        </div>

        <hr class="my-2 mb-3">

        <div class="border rounded p-3 p-md-4 bg-danger bg-opacity-10 pt-2 mb-3">
            <div class="row g-3">
                <div class="col-12 col-lg-8">
                    <label
                        for="field_officer_code"
                        class="form-label">
                        Field Officer code
                    </label>

                    <div class="input-group">
                        <span class="input-group-text">
                            <i
                                class="ri-user-search-line"
                                aria-hidden="true"></i>
                        </span>

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
                            minlength="4"
                            maxlength="20"
                            pattern="[A-Za-z0-9\-]+"
                            autocomplete="off"
                            data-error-required="Please enter the Field Officer code."
                            data-error-pattern="Field Officer code may contain letters, numbers and hyphens only."
                            data-error-minlength="Field Officer code must contain at least 4 characters."
                            data-error-maxlength="Field Officer code must not exceed 20 characters."
                            required>
                    </div>

                    <input
                        type="hidden"
                        id="verified_field_officer_id"
                        name="verified_field_officer_id"
                        value="">

                    <div
                        id="field_officer_codeError"
                        class="invalid-feedback"
                        data-validation-error="field_officer_code">
                        <?= esc($fieldOfficerDisplayError) ?>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <label
                        class="form-label d-none d-lg-block"
                        aria-hidden="true">
                        &nbsp;
                    </label>
                    <button
                        type="button"
                        id="verify-field-officer"
                        class="btn btn-primary w-100"
                        data-verification-url="<?= esc(
                                                    $verificationPath,
                                                    'attr'
                                                ) ?>">
                        <span id="verify-field-officer-label">
                            <i
                                class="ri-shield-check-line me-1"
                                aria-hidden="true"></i>

                            Verify Field Officer
                        </span>

                        <span
                            id="verify-field-officer-loading"
                            class="d-none"
                            aria-hidden="true">
                            <span
                                class="spinner-border spinner-border-sm me-1"
                                aria-hidden="true"></span>

                            Verifying...
                        </span>
                    </button>
                    <!--
                        Matches the validation space below the textbox.
                        This prevents row-height changes from shifting the button.
                    -->
                    <div
                        class="field-validation-space d-none d-lg-block"
                        aria-hidden="true"></div>
                </div>

                <div
                    id="field-officer-result-column"
                    class="col-12 d-none">
                    <div
                        id="field-officer-result"
                        class="alert alert-success border mb-0"
                        role="status"
                        aria-live="polite">
                        <div class="d-flex align-items-start gap-3">
                            <div class="fs-4">
                                <i
                                    id="field-officer-result-icon"
                                    class="ri-checkbox-circle-line"
                                    aria-hidden="true"></i>
                            </div>

                            <div>
                                <strong
                                    id="field-officer-result-message"
                                    class="d-block"></strong>

                                <div
                                    id="verified-field-officer-code"
                                    class="small"></div>

                                <div
                                    id="verified-field-officer-location"
                                    class="small text-muted"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>