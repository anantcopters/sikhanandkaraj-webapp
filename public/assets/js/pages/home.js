/**
 * ==========================================================================
 * Homepage registration form
 * ==========================================================================
 *
 * Contains JavaScript behaviour used only by the public homepage.
 */
(function (document) {
    'use strict';

    /**
     * Initialize profile relationship, gender and mobile recommendation
     * interaction.
     *
     * @returns {void}
     */
    function initializeRegistrationGender() {
        const profileCreatedFor = document.getElementById(
            'profileCreatedFor'
        );

        const genderContainer = document.getElementById(
            'genderContainer'
        );

        const mobileRecommendation = document.getElementById(
            'femaleMobileRecommendation'
        );

        if (
            !(profileCreatedFor instanceof HTMLSelectElement)
            || !(genderContainer instanceof HTMLElement)
        ) {
            return;
        }

        const genderInputs = Array.from(
            genderContainer.querySelectorAll(
                'input[name="gender"]'
            )
        ).filter(function (input) {
            return input instanceof HTMLInputElement;
        });

        /**
         * Return the currently selected gender-radio value.
         *
         * @returns {string}
         */
        function selectedGender() {
            const selectedInput = genderInputs.find(
                function (input) {
                    return input.checked;
                }
            );

            return selectedInput
                ? selectedInput.value
                : '';
        }

        /**
         * Determine whether the form currently represents
         * a female member.
         *
         * Daughter and Sister imply Female even though the
         * explicit gender radio group is hidden.
         *
         * @returns {boolean}
         */
        function isFemaleMember() {
            const profileType =
                profileCreatedFor.value;

            if (
                profileType === 'daughter'
                || profileType === 'sister'
            ) {
                return true;
            }

            return (
                profileType === 'self'
                && selectedGender() === 'F'
            );
        }

        /**
         * Show or hide the parent's-mobile recommendation.
         *
         * @returns {void}
         */
        function updateMobileRecommendation() {
            if (
                !(
                    mobileRecommendation
                    instanceof HTMLElement
                )
            ) {
                return;
            }

            mobileRecommendation.classList.toggle(
                'd-none',
                !isFemaleMember()
            );
        }

        /**
         * Show Gender only when the profile is created for Self.
         *
         * @returns {void}
         */
        function updateGenderVisibility() {
            const shouldShowGender =
                profileCreatedFor.value === 'self';

            genderContainer.classList.toggle(
                'd-none',
                !shouldShowGender
            );

            genderInputs.forEach(function (input) {
                input.required = shouldShowGender;

                /*
                 * Do not retain an invisible gender-radio value when
                 * profile relationship changes away from Self.
                 */
                if (!shouldShowGender) {
                    input.checked = false;
                }
            });

            //updateMobileRecommendation();
        }

        profileCreatedFor.addEventListener(
            'change',
            updateGenderVisibility
        );

        genderInputs.forEach(function (input) {
            input.addEventListener(
                'change',
                updateMobileRecommendation
            );
        });

        /*
         * Initialize after refresh, old input or server-side
         * validation failure.
         */
        updateGenderVisibility();
    }

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            initializeRegistrationGender();
        }
    );
})(document);