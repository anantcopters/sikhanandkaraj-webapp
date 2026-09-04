'use strict';

document.addEventListener(
    'DOMContentLoaded',
    () => {
        const eligibility =
            document.querySelector(
                '[data-coupon-eligibility]'
            );

        const genderContainer =
            document.querySelector(
                '[data-coupon-gender-container]'
            );

        const membersContainer =
            document.querySelector(
                '[data-coupon-members-container]'
            );

        const gender =
            genderContainer?.querySelector(
                '[name="eligible_gender"]'
            );

        const members =
            membersContainer?.querySelector(
                '[name="member_ids[]"]'
            );

        const syncEligibility =
            () => {
                const value =
                    eligibility?.value ?? '';

                const isGender =
                    value === 'GENDER';

                const isSelected =
                    value === 'SELECTED';

                genderContainer
                    ?.classList
                    .toggle(
                        'd-none',
                        !isGender
                    );

                membersContainer
                    ?.classList
                    .toggle(
                        'd-none',
                        !isSelected
                    );

                if (gender) {
                    gender.required =
                        isGender;

                    if (!isGender) {
                        gender.value = '';
                    }
                }

                if (members) {
                    members.required =
                        isSelected;
                }
            };

        eligibility?.addEventListener(
            'change',
            syncEligibility
        );

        syncEligibility();

        const discountType =
            document.querySelector(
                '[data-coupon-discount-type]'
            );

        const discountValue =
            document.querySelector(
                '[name="discount_value"]'
            );

        const syncDiscount =
            () => {
                if (!discountValue) {
                    return;
                }

                if (
                    discountType?.value
                    === 'PERCENTAGE'
                ) {
                    discountValue.min =
                        '1';

                    discountValue.max =
                        '90';

                    discountValue.step =
                        '1';

                    return;
                }

                discountValue.min =
                    '0.01';

                discountValue.removeAttribute(
                    'max'
                );

                discountValue.step =
                    '0.01';
            };

        discountType?.addEventListener(
            'change',
            syncDiscount
        );

        syncDiscount();
    }
);