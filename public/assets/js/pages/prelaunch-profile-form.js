'use strict';

/**
 * Client behaviour for the standalone pre-launch profile form.
 *
 * Server validation remains authoritative. This script improves the
 * user experience but cannot approve or persist a Field Officer itself.
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

    const result = document.getElementById(
        'field-officer-result'
    );

    const submitButton = document.getElementById(
        'save-prelaunch-profile'
    );

    if (
        !(codeInput instanceof HTMLInputElement)
        || !(verifiedIdInput instanceof HTMLInputElement)
        || !(verifyButton instanceof HTMLButtonElement)
        || !(result instanceof HTMLElement)
        || !(submitButton instanceof HTMLButtonElement)
    ) {
        return;
    }

    const resetVerification = () => {
        verifiedIdInput.value = '';
        submitButton.disabled = true;

        result.classList.add('d-none');
        result.classList.remove(
            'alert-success',
            'alert-danger'
        );
        result.classList.add('alert-secondary');
        result.textContent = '';
    };

    codeInput.addEventListener('input', () => {
        codeInput.value = codeInput.value
            .toUpperCase()
            .replace(/[^A-Z0-9-]/g, '');

        resetVerification();
    });

    verifyButton.addEventListener(
        'click',
        async () => {
            resetVerification();

            const code = codeInput.value.trim();
            const url = verifyButton.dataset.url;

            if (code === '' || !url) {
                result.textContent =
                    'Please enter a Field Officer code.';
                result.classList.remove(
                    'd-none',
                    'alert-secondary'
                );
                result.classList.add('alert-danger');
                return;
            }

            verifyButton.disabled = true;
            verifyButton.textContent = 'Verifying...';

            const formData = new FormData();
            formData.append(
                'field_officer_code',
                code
            );

            const csrfInput = form.querySelector(
                'input[type="hidden"][name^="csrf"]'
            );

            if (csrfInput instanceof HTMLInputElement) {
                formData.append(
                    csrfInput.name,
                    csrfInput.value
                );
            }

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const payload = await response.json();

                if (
                    csrfInput instanceof HTMLInputElement
                    && typeof payload.csrfHash === 'string'
                ) {
                    csrfInput.value = payload.csrfHash;
                }

                if (
                    !response.ok
                    || payload.successful !== true
                ) {
                    throw new Error(
                        payload.message
                        || 'Field Officer verification failed.'
                    );
                }

                const officer = payload.fieldOfficer;

                verifiedIdInput.value = String(
                    officer.id
                );

                result.textContent =
                    `${officer.full_name} — `
                    + `${officer.city_name}, `
                    + `${officer.state_name}`;

                result.classList.remove(
                    'd-none',
                    'alert-secondary',
                    'alert-danger'
                );
                result.classList.add('alert-success');

                submitButton.disabled = false;
            } catch (error) {
                result.textContent =
                    error instanceof Error
                        ? error.message
                        : 'Field Officer verification failed.';

                result.classList.remove(
                    'd-none',
                    'alert-secondary',
                    'alert-success'
                );
                result.classList.add('alert-danger');
            } finally {
                verifyButton.disabled = false;
                verifyButton.textContent =
                    'Verify Field Officer';
            }
        }
    );

    form.addEventListener('submit', (event) => {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }

        if (verifiedIdInput.value === '') {
            event.preventDefault();

            result.textContent =
                'Please verify the Field Officer before saving.';
            result.classList.remove(
                'd-none',
                'alert-secondary',
                'alert-success'
            );
            result.classList.add('alert-danger');
        }

        form.classList.add('was-validated');
    });
});