'use strict';

/**
 * Client behaviour for the standalone pre-launch profile form.
 *
 * Field Officer verification in JavaScript is for user experience only.
 * The server remains authoritative and verifies the code and hidden ID
 * again during profile creation.
 */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById(
        'prelaunch-profile-form'
    );

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const codeInput = document.getElementById(
        'field_officer_code'
    );

    const verifiedIdInput = document.getElementById(
        'verified_field_officer_id'
    );

    const verifyButton = document.getElementById(
        'verify-field-officer'
    );

    const verifyLabel = document.getElementById(
        'verify-field-officer-label'
    );

    const verifyLoading = document.getElementById(
        'verify-field-officer-loading'
    );

    const resultContainer = document.getElementById(
        'field-officer-result'
    );

    const officerName = document.getElementById(
        'verified-field-officer-name'
    );

    const officerCode = document.getElementById(
        'verified-field-officer-code'
    );

    const officerLocation = document.getElementById(
        'verified-field-officer-location'
    );

    const submitButton = document.getElementById(
        'save-prelaunch-profile'
    );

    if (
        !(codeInput instanceof HTMLInputElement)
        || !(verifiedIdInput instanceof HTMLInputElement)
        || !(verifyButton instanceof HTMLButtonElement)
        || !(verifyLabel instanceof HTMLElement)
        || !(verifyLoading instanceof HTMLElement)
        || !(resultContainer instanceof HTMLElement)
        || !(officerName instanceof HTMLElement)
        || !(officerCode instanceof HTMLElement)
        || !(officerLocation instanceof HTMLElement)
        || !(submitButton instanceof HTMLButtonElement)
    ) {
        return;
    }

    /**
     * Return the current CI4 CSRF hidden field.
     *
     * The name can change when CSRF token regeneration is enabled.
     */
    const getCsrfInput = () => {
        const csrfInput = form.querySelector(
            'input[type="hidden"][name^="csrf"]'
        );

        return csrfInput instanceof HTMLInputElement
            ? csrfInput
            : null;
    };

    const setLoading = (loading) => {
        verifyButton.disabled = loading;

        verifyLabel.classList.toggle(
            'd-none',
            loading
        );

        verifyLoading.classList.toggle(
            'd-none',
            !loading
        );
    };

    const resetVerification = () => {
        verifiedIdInput.value = '';
        submitButton.disabled = true;

        codeInput.classList.remove(
            'is-valid'
        );

        resultContainer.classList.add(
            'd-none'
        );

        resultContainer.classList.remove(
            'alert-success',
            'alert-danger'
        );

        resultContainer.classList.add(
            'alert-secondary'
        );

        officerName.textContent = '';
        officerCode.textContent = '';
        officerLocation.textContent = '';
    };

    const showError = (message) => {
        verifiedIdInput.value = '';
        submitButton.disabled = true;

        codeInput.classList.remove(
            'is-valid'
        );

        officerName.textContent = message;
        officerCode.textContent = '';
        officerLocation.textContent = '';

        resultContainer.classList.remove(
            'd-none',
            'alert-secondary',
            'alert-success'
        );

        resultContainer.classList.add(
            'alert-danger'
        );
    };

    const showVerifiedOfficer = (fieldOfficer) => {
        verifiedIdInput.value = String(
            fieldOfficer.id
        );

        codeInput.value =
            fieldOfficer.officerCode;

        codeInput.classList.remove(
            'is-invalid'
        );

        codeInput.classList.add(
            'is-valid'
        );

        officerName.textContent =
            fieldOfficer.fullName;

        officerCode.textContent =
            `Code: ${fieldOfficer.officerCode}`;

        officerLocation.textContent =
            fieldOfficer.location
                ? `Location: ${fieldOfficer.location}`
                : 'Location not available';

        resultContainer.classList.remove(
            'd-none',
            'alert-secondary',
            'alert-danger'
        );

        resultContainer.classList.add(
            'alert-success'
        );

        submitButton.disabled = false;
    };

    codeInput.addEventListener(
        'input',
        () => {
            codeInput.value = codeInput.value
                .toUpperCase()
                .replace(
                    /[^A-Z0-9-]/g,
                    ''
                );

            resetVerification();
        }
    );

    verifyButton.addEventListener(
        'click',
        async () => {
            resetVerification();

            const enteredCode =
                codeInput.value.trim();

            const verificationUrl =
                verifyButton.dataset.verificationUrl;

            if (
                enteredCode.length < 4
                || enteredCode.length > 20
                || !/^[A-Z0-9-]+$/.test(
                    enteredCode
                )
            ) {
                codeInput.classList.add(
                    'is-invalid'
                );

                showError(
                    'Please enter a valid Field Officer code.'
                );

                codeInput.focus();
                return;
            }

            if (!verificationUrl) {
                showError(
                    'Field Officer verification is currently unavailable.'
                );

                return;
            }

            codeInput.classList.remove(
                'is-invalid'
            );

            setLoading(true);

            const formData = new FormData();

            formData.append(
                'field_officer_code',
                enteredCode
            );

            const csrfInput = getCsrfInput();

            if (csrfInput !== null) {
                formData.append(
                    csrfInput.name,
                    csrfInput.value
                );
            }

            try {
                const response = await fetch(
                    verificationUrl,
                    {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With':
                                'XMLHttpRequest',
                        },
                    }
                );

                let payload;

                try {
                    payload = await response.json();
                } catch {
                    throw new Error(
                        'The verification server returned an invalid response.'
                    );
                }

                if (
                    csrfInput !== null
                    && typeof payload.csrfName
                    === 'string'
                    && typeof payload.csrfHash
                    === 'string'
                ) {
                    csrfInput.name =
                        payload.csrfName;

                    csrfInput.value =
                        payload.csrfHash;
                }

                if (
                    !response.ok
                    || payload.successful !== true
                    || !payload.fieldOfficer
                ) {
                    throw new Error(
                        payload.message
                        || 'The Field Officer could not be verified.'
                    );
                }

                showVerifiedOfficer(
                    payload.fieldOfficer
                );
            } catch (error) {
                showError(
                    error instanceof Error
                        ? error.message
                        : 'The Field Officer could not be verified.'
                );
            } finally {
                setLoading(false);
            }
        }
    );

    form.addEventListener(
        'submit',
        (event) => {
            if (
                verifiedIdInput.value.trim() === ''
            ) {
                event.preventDefault();
                event.stopPropagation();

                showError(
                    'Please verify the Field Officer before saving the profile.'
                );

                codeInput.focus();
                return;
            }

            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();

                form.classList.add(
                    'was-validated'
                );
            }
        }
    );
});