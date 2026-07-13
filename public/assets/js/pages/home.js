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
     * Initialize the profile-created-for and gender interaction.
     *
     * @returns {void}
     */
    function initializeGenderVisibility() {
        const profileCreatedFor = document.getElementById(
            'profileCreatedFor'
        );

        const genderContainer = document.getElementById(
            'genderContainer'
        );

        /**
         * The homepage form may change over time. Exit safely when either
         * required element is not present.
         */
        if (!profileCreatedFor || !genderContainer) {
            return;
        }

        const genderInputs = genderContainer.querySelectorAll(
            'input[name="gender"]'
        );

        /**
         * Show Gender only when the profile is being created for Self.
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
                /**
                 * Gender is required only while its section is visible.
                 */
                input.required = shouldShowGender;

                /**
                 * Do not retain an invisible value after the user switches
                 * from Self to another profile relationship.
                 */
                if (!shouldShowGender) {
                    input.checked = false;
                }
            });
        }

        /**
         * Choices.js updates the original select and triggers its native
         * change event, so no Choices-specific event is necessary here.
         */
        profileCreatedFor.addEventListener(
            'change',
            updateGenderVisibility
        );

        /**
         * Set the correct state after page refresh, old form data, or
         * server-side validation failure.
         */
        updateGenderVisibility();
    }

    document.addEventListener('DOMContentLoaded', function () {
        initializeGenderVisibility();
    });
})(document);

