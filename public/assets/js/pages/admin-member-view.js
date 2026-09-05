(function () {
    'use strict';

    /**
     * Escape dynamically generated HTML.
     *
     * @param {unknown} value
     * @returns {string}
     */
    function escapeHtml(value) {
        const element = document.createElement(
            'div'
        );

        element.textContent = String(
            value ?? ''
        );

        return element.innerHTML;
    }

    /**
     * Configure the Block/Unblock modal.
     */
    function initializeStatusModal() {
        const modalElement = document.getElementById(
            'member-status-modal'
        );

        const form = document.getElementById(
            'member-status-form'
        );

        if (
            !modalElement
            || !(form instanceof HTMLFormElement)
            || typeof bootstrap === 'undefined'
        ) {
            return;
        }

        const modal =
            bootstrap.Modal.getOrCreateInstance(
                modalElement
            );

        const title = document.getElementById(
            'member-status-modal-title'
        );

        const identity = document.getElementById(
            'member-status-identity'
        );

        const nameElement = document.getElementById(
            'member-status-member-name'
        );

        const codeElement = document.getElementById(
            'member-status-member-code'
        );

        const message = document.getElementById(
            'member-status-message'
        );

        const reason = document.getElementById(
            'member-status-reason'
        );

        const submit = document.getElementById(
            'member-status-submit'
        );

        document.querySelectorAll(
            '[data-member-status]'
        ).forEach(function (button) {
            button.addEventListener(
                'click',
                function () {
                    const action = String(
                        button.dataset.action
                        ?? ''
                    ).toUpperCase();

                    const memberName = String(
                        button.dataset.memberName
                        ?? 'Member'
                    ).trim();

                    const memberCode = String(
                        button.dataset.memberCode
                        ?? ''
                    ).trim();

                    const formAction = String(
                        button.dataset.formAction
                        ?? ''
                    ).trim();

                    if (
                        formAction === ''
                        || ![
                            'BLOCK',
                            'UNBLOCK'
                        ].includes(action)
                    ) {
                        return;
                    }

                    form.action = formAction;

                    if (title) {
                        title.textContent =
                            action === 'BLOCK'
                                ? 'Block Member'
                                : 'Unblock Member';
                    }

                    if (identity) {
                        identity.textContent =
                            memberCode !== ''
                                ? memberName
                                + ' · '
                                + memberCode
                                : memberName;
                    }

                    if (nameElement) {
                        nameElement.textContent =
                            memberName;
                    }

                    if (codeElement) {
                        codeElement.textContent =
                            memberCode !== ''
                                ? 'Member Code: '
                                + memberCode
                                : 'Member Code: —';
                    }

                    if (message) {
                        message.textContent =
                            action === 'BLOCK'
                                ? 'Enter the reason for '
                                + 'blocking this member.'
                                : 'Enter the reason for '
                                + 'unblocking this member.';
                    }

                    if (submit) {
                        submit.textContent =
                            action === 'BLOCK'
                                ? 'Block Member'
                                : 'Unblock Member';

                        submit.classList.remove(
                            'btn-primary',
                            'btn-danger',
                            'btn-success'
                        );

                        submit.classList.add(
                            action === 'BLOCK'
                                ? 'btn-danger'
                                : 'btn-success'
                        );
                    }

                    if (
                        reason
                        instanceof HTMLInputElement
                    ) {
                        reason.value = '';

                        reason.classList.remove(
                            'is-invalid'
                        );
                    }

                    modal.show();
                }
            );
        });

        form.addEventListener(
            'submit',
            function (event) {
                if (
                    !(
                        reason
                        instanceof HTMLInputElement
                    )
                ) {
                    return;
                }

                const value = reason.value
                    .replace(/\s+/g, ' ')
                    .trim();

                reason.value = value;

                if (
                    value === ''
                    || value.length > 64
                ) {
                    event.preventDefault();

                    reason.classList.add(
                        'is-invalid'
                    );

                    reason.focus();
                }
            }
        );
    }

    /**
     * Render one local date-time value supplied by PHP.
     *
     * JavaScript must not parse or timezone-convert this value. PHP has
     * already converted it from UTC to the configured display timezone.
     *
     * @param {object} item
     * @returns {string}
     */
    function renderDateTime(item) {
        const changedAtDisplay = String(
            item.changedAtDisplay
            ?? '—'
        ).trim();

        const changedAtIso = String(
            item.changedAtIso
            ?? ''
        ).trim();

        if (changedAtIso === '') {
            return escapeHtml(
                changedAtDisplay !== ''
                    ? changedAtDisplay
                    : '—'
            );
        }

        return [
            '<time datetime="',
            escapeHtml(changedAtIso),
            '">',
            escapeHtml(
                changedAtDisplay !== ''
                    ? changedAtDisplay
                    : '—'
            ),
            '</time>'
        ].join('');
    }

    /**
     * Render member status history.
     *
     * @param {Array<object>} history
     * @returns {string}
     */
    function renderHistory(history) {
        if (
            !Array.isArray(history)
            || history.length === 0
        ) {
            return [
                '<div class="text-center ',
                'text-muted py-4">',
                '<i class="ri-history-line ',
                'fs-24 d-block mb-2" ',
                'aria-hidden="true"></i>',
                'No block or unblock history ',
                'is available.',
                '</div>'
            ].join('');
        }

        const rows = history.map(
            function (item) {
                const action = String(
                    item.action
                    ?? ''
                ).toUpperCase();

                const badgeClass =
                    action === 'BLOCK'
                        ? 'bg-danger-subtle '
                        + 'text-danger'
                        : 'bg-success-subtle '
                        + 'text-body p-2';

                return [
                    '<tr>',
                    '<td><span class="badge ',
                    badgeClass,
                    '">',
                    escapeHtml(action),
                    '</span></td>',
                    '<td>',
                    escapeHtml(
                        item.previousStatus
                        ?? '—'
                    ),
                    ' <i class="ri-arrow-right-line ',
                    'mx-1" aria-hidden="true"></i> ',
                    escapeHtml(
                        item.newStatus
                        ?? '—'
                    ),
                    '</td>',
                    '<td>',
                    escapeHtml(
                        item.reason
                        ?? '—'
                    ),
                    '</td>',
                    '<td>',
                    escapeHtml(
                        item.adminName
                        ?? 'Administrator'
                    ),
                    '<div class="small text-muted">',
                    escapeHtml(
                        item.adminRole
                        ?? ''
                    ),
                    '</div>',
                    '</td>',
                    '<td>',
                    renderDateTime(item),
                    '</td>',
                    '</tr>'
                ].join('');
            }
        ).join('');

        return [
            '<div class="table-responsive">',
            '<table class="table table-hover ',
            'table-nowrap align-middle mb-0">',
            '<thead class="bg-info-subtle">',
            '<tr>',
            '<th scope="col">Action</th>',
            '<th scope="col">Transition</th>',
            '<th scope="col">Reason</th>',
            '<th scope="col">Administrator</th>',
            '<th scope="col">Date</th>',
            '</tr>',
            '</thead>',
            '<tbody>',
            rows,
            '</tbody>',
            '</table>',
            '</div>'
        ].join('');
    }

    /**
     * Initialize the History button.
     */
    function initializeHistoryModal() {
        const modalElement = document.getElementById(
            'member-history-modal'
        );

        const title = document.getElementById(
            'member-history-modal-title'
        );

        const content = document.getElementById(
            'member-history-content'
        );

        if (
            !modalElement
            || !title
            || !content
            || typeof bootstrap === 'undefined'
        ) {
            return;
        }

        const modal =
            bootstrap.Modal.getOrCreateInstance(
                modalElement
            );

        document.querySelectorAll(
            '[data-member-history]'
        ).forEach(function (button) {
            button.addEventListener(
                'click',
                async function () {
                    const url = String(
                        button.dataset.historyUrl
                        ?? ''
                    ).trim();

                    if (url === '') {
                        return;
                    }

                    content.innerHTML = [
                        '<div class="text-center ',
                        'text-muted py-4">',
                        '<span class="spinner-border ',
                        'spinner-border-sm me-2" ',
                        'aria-hidden="true"></span>',
                        'Loading history...',
                        '</div>'
                    ].join('');

                    modal.show();
                    button.disabled = true;

                    try {
                        const response = await fetch(
                            url,
                            {
                                method: 'GET',

                                headers: {
                                    'Accept':
                                        'application/json',

                                    'X-Requested-With':
                                        'XMLHttpRequest'
                                },

                                credentials:
                                    'same-origin'
                            }
                        );

                        const payload =
                            await response.json();

                        if (
                            !response.ok
                            || payload.successful
                            !== true
                        ) {
                            throw new Error(
                                payload.message
                                ?? 'History could not '
                                + 'be loaded.'
                            );
                        }

                        const memberName = String(
                            payload.member?.name
                            ?? 'Member'
                        );

                        const reference = String(
                            payload.member?.reference
                            ?? ''
                        );

                        title.textContent =
                            reference !== ''
                                ? memberName
                                + ' ('
                                + reference
                                + ')'
                                : memberName;

                        content.innerHTML =
                            renderHistory(
                                payload.history
                            );
                    } catch (error) {
                        content.innerHTML = [
                            '<div class="alert ',
                            'alert-danger mb-0" ',
                            'role="alert">',
                            escapeHtml(
                                error instanceof Error
                                    ? error.message
                                    : 'History could not '
                                    + 'be loaded.'
                            ),
                            '</div>'
                        ].join('');
                    } finally {
                        button.disabled = false;
                    }
                }
            );
        });
    }

    /**
     * Initialize retained-photo modal previews.
     */
    function initializePhotoModal() {
        const modalElement = document.getElementById(
            'admin-photo-modal'
        );

        const title = document.getElementById(
            'admin-photo-modal-title'
        );

        const loading = document.getElementById(
            'admin-photo-loading'
        );

        const errorElement = document.getElementById(
            'admin-photo-error'
        );

        const image = document.getElementById(
            'admin-photo-image'
        );

        if (
            !modalElement
            || !title
            || !loading
            || !errorElement
            || !(image instanceof HTMLImageElement)
            || typeof bootstrap === 'undefined'
        ) {
            return;
        }

        const modal =
            bootstrap.Modal.getOrCreateInstance(
                modalElement
            );

        document.querySelectorAll(
            '[data-admin-photo]'
        ).forEach(function (button) {
            button.addEventListener(
                'click',
                async function () {
                    const endpoint = String(
                        button.dataset
                            .modalUrlEndpoint
                        ?? ''
                    ).trim();

                    const photoTitle = String(
                        button.dataset.photoTitle
                        ?? 'Member Photograph'
                    ).trim();

                    if (endpoint === '') {
                        return;
                    }

                    title.textContent = photoTitle;

                    loading.classList.remove(
                        'd-none'
                    );

                    errorElement.classList.add(
                        'd-none'
                    );

                    image.classList.add(
                        'd-none'
                    );

                    image.removeAttribute(
                        'src'
                    );

                    image.alt = photoTitle;

                    modal.show();
                    button.disabled = true;

                    try {
                        const response = await fetch(
                            endpoint,
                            {
                                method: 'GET',

                                headers: {
                                    'Accept':
                                        'application/json',

                                    'X-Requested-With':
                                        'XMLHttpRequest'
                                },

                                credentials:
                                    'same-origin'
                            }
                        );

                        const payload =
                            await response.json();

                        if (
                            !response.ok
                            || payload.successful
                            !== true
                        ) {
                            throw new Error(
                                payload.message
                                ?? 'The photograph '
                                + 'could not be loaded.'
                            );
                        }

                        const imageUrl = String(
                            payload.originalUrl
                            || payload.mediumUrl
                            || ''
                        ).trim();

                        if (imageUrl === '') {
                            throw new Error(
                                'The photograph is unavailable.'
                            );
                        }

                        image.src = imageUrl;

                        image.classList.remove(
                            'd-none'
                        );
                    } catch (error) {
                        errorElement.textContent =
                            error instanceof Error
                                ? error.message
                                : 'The photograph could '
                                + 'not be loaded.';

                        errorElement.classList.remove(
                            'd-none'
                        );
                    } finally {
                        loading.classList.add(
                            'd-none'
                        );

                        button.disabled = false;
                    }
                }
            );
        });

        modalElement.addEventListener(
            'hidden.bs.modal',
            function () {
                image.removeAttribute(
                    'src'
                );

                image.classList.add(
                    'd-none'
                );

                errorElement.classList.add(
                    'd-none'
                );

                loading.classList.remove(
                    'd-none'
                );
            }
        );
    }

    /**
 * Keep the offline payment amount aligned with the selected
 * authoritative membership plan.
 *
 * Coupon evaluation can change the expected payable amount.
 * Administrators may still override Amount Received, but a visible
 * warning is shown whenever it differs from the expected amount.
 */
    function initializeOfflinePaymentPlanAmount() {
        const planSelect = document.getElementById(
            'offlinePaymentPlan'
        );

        const amountInput = document.getElementById(
            'offlinePaymentAmount'
        );

        const planAmount = document.getElementById(
            'offlinePaymentPlanAmount'
        );

        const amountWarning =
            document.querySelector(
                '[data-payment-amount-warning]'
            );

        const couponBreakdown =
            document.querySelector(
                '[data-coupon-breakdown]'
            );

        const couponError =
            document.querySelector(
                '[data-coupon-error]'
            );

        const couponPlanPrice =
            document.querySelector(
                '[data-coupon-plan-price]'
            );

        const couponDiscount =
            document.querySelector(
                '[data-coupon-discount]'
            );

        const couponFinal =
            document.querySelector(
                '[data-coupon-final]'
            );

        const planAmountDisplay =
            planAmount
                ? planAmount.querySelector(
                    '[data-plan-amount-display]'
                )
                : null;

        if (
            !(planSelect instanceof HTMLSelectElement)
            || !(amountInput instanceof HTMLInputElement)
            || !planAmount
            || !planAmountDisplay
        ) {
            return;
        }

        /*
         * The expected payable is held only in JavaScript.
         *
         * It is NOT submitted to the server and therefore cannot become
         * authoritative pricing data.
         */
        let expectedPayable = null;

        /**
         * Remove a previously evaluated coupon from the UI.
         *
         * A coupon is plan-specific. Therefore changing the plan must
         * invalidate the previous coupon evaluation.
         */
        function clearCouponEvaluation() {
            expectedPayable = null;

            couponBreakdown?.classList.add(
                'd-none'
            );

            couponError?.classList.add(
                'd-none'
            );

            if (couponError) {
                couponError.textContent = '';
            }

            if (couponPlanPrice) {
                couponPlanPrice.textContent = '';
            }

            if (couponDiscount) {
                couponDiscount.textContent = '';
            }

            if (couponFinal) {
                couponFinal.textContent = '';
            }
        }

        /**
         * Compare actual amount received with the current expected payable.
         *
         * This is deliberately a warning rather than a validation failure,
         * because Superadmin may legitimately record a different amount.
         */
        function syncAmountWarning() {
            if (!amountWarning) {
                return;
            }

            const received =
                Number.parseFloat(
                    amountInput.value
                );

            const expected =
                expectedPayable !== null
                    ? expectedPayable
                    : Number.parseFloat(
                        String(
                            planSelect.options[
                                planSelect.selectedIndex
                            ]?.dataset.planPrice
                            ?? ''
                        )
                    );

            const differs =
                Number.isFinite(received)
                && Number.isFinite(expected)
                && Math.abs(
                    received - expected
                ) >= 0.01;

            amountWarning.classList.toggle(
                'd-none',
                !differs
            );
        }



        /**
         * Apply the selected plan's master price.
         *
         * @param {boolean} updateAmount
         */
        function applyPlanPrice(
            updateAmount
        ) {
            const selectedOption =
                planSelect.options[
                planSelect.selectedIndex
                ];

            const price = String(
                selectedOption?.dataset.planPrice
                ?? ''
            ).trim();

            const priceDisplay = String(
                selectedOption?.dataset
                    .planPriceDisplay
                ?? ''
            ).trim();

            expectedPayable =
                price !== ''
                    ? Number.parseFloat(price)
                    : null;

            if (
                price === ''
                || priceDisplay === ''
            ) {
                planAmount.classList.add(
                    'd-none'
                );

                planAmountDisplay.textContent =
                    '';

                if (updateAmount) {
                    amountInput.value = '';
                }

                syncAmountWarning();

                return;
            }

            planAmountDisplay.textContent =
                '₹' + priceDisplay;

            planAmount.classList.remove(
                'd-none'
            );

            if (updateAmount) {
                amountInput.value = price;
            }

            syncAmountWarning();
        }

        planSelect.addEventListener(
            'change',
            function () {
                /*
                 * IMPORTANT:
                 * A coupon evaluated for the previous plan is no longer valid.
                 */
                clearCouponEvaluation();

                applyPlanPrice(
                    true
                );
            }
        );

        /*
         * Re-evaluate the mismatch warning whenever Superadmin manually
         * changes Amount Received.
         */
        amountInput.addEventListener(
            'input',
            syncAmountWarning
        );

        /*
         * Allow the coupon handler below to publish the newly calculated
         * final payable without introducing a hidden authoritative field.
         */
        document.addEventListener(
            'offline-payment:coupon-applied',
            function (event) {
                const finalPayable =
                    Number.parseFloat(
                        String(
                            event.detail
                                ?.finalPayable
                            ?? ''
                        )
                    );

                expectedPayable =
                    Number.isFinite(
                        finalPayable
                    )
                        ? finalPayable
                        : null;

                /*
                 * A successfully evaluated coupon changes the calculated
                 * payable amount.
                 *
                 * Amount Received must initially follow that authoritative
                 * preview so Superadmin does not have to manually copy the
                 * Final Payable value.
                 *
                 * The field intentionally remains editable. Superadmin may
                 * subsequently change the actual amount received, in which
                 * case the existing mismatch warning is displayed.
                 */
                if (
                    expectedPayable !== null
                ) {
                    amountInput.value =
                        expectedPayable.toFixed(2);
                }

                syncAmountWarning();
            }
        );

        /*
         * On server-validation return, preserve the administrator's
         * submitted amount instead of overwriting it with plan price.
         */
        applyPlanPrice(
            amountInput.value.trim() === ''
        );
    }

    /**
 * Evaluate an optional coupon for the selected member and plan.
 *
 * This request is preview-only. The server performs the same authoritative
 * coupon validation again when the offline payment is finally saved.
 */
    function initializeOfflinePaymentCoupon() {
        const planSelect = document.getElementById(
            'offlinePaymentPlan'
        );

        const paymentDateInput =
            document.getElementById(
                'offlinePaymentDate'
            );

        const couponInput =
            document.querySelector(
                '[data-coupon-code]'
            );

        const applyButton =
            document.querySelector(
                '[data-apply-coupon]'
            );

        const errorElement =
            document.querySelector(
                '[data-coupon-error]'
            );

        const breakdown =
            document.querySelector(
                '[data-coupon-breakdown]'
            );

        const planPrice =
            document.querySelector(
                '[data-coupon-plan-price]'
            );

        const discount =
            document.querySelector(
                '[data-coupon-discount]'
            );

        const finalPayable =
            document.querySelector(
                '[data-coupon-final]'
            );

        if (
            !(planSelect instanceof HTMLSelectElement)
            || !(paymentDateInput instanceof HTMLInputElement)
            || !(couponInput instanceof HTMLInputElement)
            || !(applyButton instanceof HTMLButtonElement)
            || !errorElement
            || !breakdown
            || !planPrice
            || !discount
            || !finalPayable
        ) {
            return;
        }

        function clearResult() {
            errorElement.textContent = '';

            errorElement.classList.add(
                'd-none'
            );

            breakdown.classList.add(
                'd-none'
            );

            planPrice.textContent = '';
            discount.textContent = '';
            finalPayable.textContent = '';
        }

        applyButton.addEventListener(
            'click',
            async function () {
                clearResult();

                const planCode =
                    planSelect.value.trim();

                const paymentDate =
                    paymentDateInput.value.trim();

                const couponCode =
                    couponInput.value
                        .trim()
                        .toUpperCase();

                couponInput.value =
                    couponCode;

                if (planCode === '') {
                    errorElement.textContent =
                        'Please select a membership plan first.';

                    errorElement.classList.remove(
                        'd-none'
                    );

                    planSelect.focus();

                    return;
                }

                if (paymentDate === '') {
                    errorElement.textContent =
                        'Please select the payment date first.';

                    errorElement.classList.remove(
                        'd-none'
                    );

                    paymentDateInput.focus();

                    return;
                }

                if (couponCode === '') {
                    errorElement.textContent =
                        'Please enter a coupon code.';

                    errorElement.classList.remove(
                        'd-none'
                    );

                    couponInput.focus();

                    return;
                }

                const endpoint =
                    String(
                        applyButton.dataset
                            .couponUrl
                        ?? ''
                    ).trim();

                if (endpoint === '') {
                    return;
                }

                applyButton.disabled = true;

                try {
                    /*
                    * Apply Coupon uses POST and is protected by the same global
                    * CSRF filter as the parent offline-payment form.
                    *
                    * The token name comes from CodeIgniter rather than being
                    * hard-coded in JavaScript.
                    */
                    const paymentForm =
                        couponInput.closest(
                            'form'
                        );

                    const csrfName =
                        paymentForm instanceof HTMLFormElement
                            ? String(
                                paymentForm.dataset.csrfName
                                ?? ''
                            ).trim()
                            : '';

                    const csrfInput =
                        csrfName !== ''
                            && paymentForm instanceof HTMLFormElement
                            ? paymentForm.querySelector(
                                'input[name="'
                                + CSS.escape(csrfName)
                                + '"]'
                            )
                            : null;

                    const body =
                        new URLSearchParams();

                    body.set(
                        'plan_code',
                        planCode
                    );

                    body.set(
                        'payment_date',
                        paymentDate
                    );

                    body.set(
                        'coupon_code',
                        couponCode
                    );

                    if (
                        csrfInput instanceof HTMLInputElement
                        && csrfInput.value !== ''
                    ) {
                        body.set(
                            csrfName,
                            csrfInput.value
                        );
                    }

                    const response =
                        await fetch(
                            endpoint,
                            {
                                method: 'POST',

                                headers: {
                                    'Accept':
                                        'application/json',

                                    'Content-Type':
                                        'application/x-www-form-urlencoded;charset=UTF-8',

                                    'X-Requested-With':
                                        'XMLHttpRequest'
                                },

                                credentials:
                                    'same-origin',

                                body:
                                    body.toString()
                            }
                        );

                    const payload =
                        await response.json();

                    /*
 * CodeIgniter regenerates the CSRF token after every successfully
 * verified POST.
 *
 * Update the parent form before handling either success or validation
 * failure so repeated Apply Coupon requests and the final Record Payment
 * submission always use the latest token.
 */
                    const responseCsrfName =
                        String(
                            payload.csrf?.name
                            ?? ''
                        ).trim();

                    const responseCsrfHash =
                        String(
                            payload.csrf?.hash
                            ?? ''
                        ).trim();

                    if (
                        paymentForm instanceof HTMLFormElement
                        && responseCsrfName !== ''
                        && responseCsrfHash !== ''
                    ) {
                        const currentCsrfInput =
                            paymentForm.querySelector(
                                'input[name="'
                                + CSS.escape(
                                    responseCsrfName
                                )
                                + '"]'
                            );

                        if (
                            currentCsrfInput
                            instanceof HTMLInputElement
                        ) {
                            currentCsrfInput.value =
                                responseCsrfHash;
                        }

                        paymentForm.dataset.csrfName =
                            responseCsrfName;
                    }

                    if (
                        !response.ok
                        || payload.successful
                        !== true
                    ) {
                        throw new Error(
                            payload.message
                            ?? 'Coupon could not be applied.'
                        );
                    }

                    /*
                     * Use display values returned by the server.
                     */
                    planPrice.textContent =
                        payload.pricing
                            ?.planPriceDisplay
                        ?? '';

                    discount.textContent =
                        payload.pricing
                            ?.discountDisplay
                        ?? '';

                    finalPayable.textContent =
                        payload.pricing
                            ?.finalPayableDisplay
                        ?? '';

                    breakdown.classList.remove(
                        'd-none'
                    );

                    /*
                     * Tell the amount-warning logic what the current expected
                     * payable is. This remains presentation-only.
                     */
                    document.dispatchEvent(
                        new CustomEvent(
                            'offline-payment:coupon-applied',
                            {
                                detail: {
                                    finalPayable:
                                        payload.pricing
                                            ?.finalPayable
                                        ?? ''
                                }
                            }
                        )
                    );
                } catch (error) {
                    errorElement.textContent =
                        error instanceof Error
                            ? error.message
                            : 'Coupon could not be applied.';

                    errorElement.classList.remove(
                        'd-none'
                    );
                } finally {
                    applyButton.disabled = false;
                }
            }
        );

        /*
         * Editing the coupon after it has been evaluated invalidates the
         * displayed result until Apply Coupon is clicked again.
         */
        couponInput.addEventListener(
            'input',
            clearResult
        );

        paymentDateInput.addEventListener(
            'change',
            clearResult
        );
    }

    /**
     * Reopen the offline-payment modal after server validation failure.
     */
    function initializeOfflinePaymentModal() {
        const modalElement = document.getElementById(
            'offline-payment-modal'
        );

        if (
            !modalElement
            || typeof bootstrap === 'undefined'
        ) {
            return;
        }

        if (
            String(
                modalElement.dataset.openModal
                ?? '0'
            ) !== '1'
        ) {
            return;
        }

        bootstrap.Modal
            .getOrCreateInstance(
                modalElement
            )
            .show();
    }

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            initializeStatusModal();
            initializeHistoryModal();
            initializePhotoModal();
            initializeOfflinePaymentPlanAmount();
            initializeOfflinePaymentCoupon();
            initializeOfflinePaymentModal();
        }
    );
}());