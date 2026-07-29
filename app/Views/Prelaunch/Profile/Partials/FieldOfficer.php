<?php

declare(strict_types=1);

/**
 * @var array<string, string>|null $validationErrors
 */

$errorBag = is_array($validationErrors ?? null)
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

$verificationUrl = route_to(
    'prelaunch.field-officer.verify'
);
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
                    Enter the Field Officer code. Saving is enabled
                    only after successful verification.
                </p>
            </div>
        </div>
        <hr class="my-2 mb-3">
        </hr>
        <div class="border rounded p-3 p-md-4 bg-info-subtle pt-2">
            <div class="row g-3 align-items-end">
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
                            pattern="[A-Za-z0-9-]+"
                            autocomplete="off"
                            required>
                    </div>

                    <input
                        type="hidden"
                        id="verified_field_officer_id"
                        name="verified_field_officer_id"
                        value="">

                    <?php if (
                        $fieldOfficerDisplayError !== ''
                    ): ?>
                        <div class="text-danger small mt-1">
                            <?= esc(
                                $fieldOfficerDisplayError
                            ) ?>
                        </div>
                    <?php endif ?>
                </div>

                <div class="col-12 col-lg-4">
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
                                aria-hidden="true"></i>

                            Verify Field Officer
                        </span>

                        <span
                            id="verify-field-officer-loading"
                            class="d-none">
                            <span
                                class="spinner-border spinner-border-sm me-1"
                                aria-hidden="true"></span>

                            Verifying...
                        </span>
                    </button>
                </div>

                <div class="col-12">
                    <div
                        id="field-officer-result"
                        class="alert alert-light border d-none mb-0"
                        role="status"
                        aria-live="polite">
                        <div class="d-flex align-items-start gap-3">
                            <div class="fs-4">
                                <i
                                    class="ri-checkbox-circle-line"
                                    aria-hidden="true"></i>
                            </div>

                            <div>
                                <strong
                                    id="verified-field-officer-name"
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
    </div>
</div>