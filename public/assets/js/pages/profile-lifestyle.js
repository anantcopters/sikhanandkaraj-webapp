'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('lifestyleForm');

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const optionInputs = Array.from(
        form.querySelectorAll(
            '[data-lifestyle-option]'
        )
    );

    /**
     * Update the plus/check icon for one option.
     *
     * @param {HTMLInputElement} input
     */
    const updateOptionIcon = (input) => {
        const label = form.querySelector(
            `label[for="${CSS.escape(input.id)}"]`
        );

        if (!(label instanceof HTMLLabelElement)) {
            return;
        }

        const icon = label.querySelector('i');

        if (!(icon instanceof HTMLElement)) {
            return;
        }

        icon.classList.toggle(
            'ri-check-line',
            input.checked
        );

        icon.classList.toggle(
            'ri-add-line',
            !input.checked
        );
    };

    /**
     * Update tab and panel counts for one category.
     *
     * @param {string} categoryId
     */
    const updateCategoryCount = (categoryId) => {
        const selector = [
            '[data-lifestyle-option]',
            `[data-category-id="${CSS.escape(categoryId)}"]`,
            ':checked',
        ].join('');

        const selectedCount = form
            .querySelectorAll(selector)
            .length;

        const tabCounter = form.querySelector(
            `[data-tab-selected-count="${CSS.escape(categoryId)
            }"]`
        );

        if (tabCounter instanceof HTMLElement) {
            tabCounter.textContent =
                String(selectedCount);
        }

        const panelCounter = form.querySelector(
            `[data-panel-selected-count="${CSS.escape(categoryId)
            }"]`
        );

        if (panelCounter instanceof HTMLElement) {
            panelCounter.textContent =
                selectedCount === 1
                    ? '1 selected'
                    : `${selectedCount} selected`;
        }
    };

    /*
     * Initialise icons and gather unique categories.
     */
    const categoryIds = new Set();

    optionInputs.forEach((input) => {
        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        updateOptionIcon(input);

        const categoryId = input.dataset.categoryId;

        if (categoryId) {
            categoryIds.add(categoryId);
        }

        input.addEventListener('change', () => {
            updateOptionIcon(input);

            if (categoryId) {
                updateCategoryCount(categoryId);
            }
        });
    });

    /*
     * Initialise all displayed counts after the DOM is ready.
     */
    categoryIds.forEach((categoryId) => {
        updateCategoryCount(categoryId);
    });

    form.addEventListener('submit', (event) => {
        const submitButton = document.getElementById(
            'saveLifestyleButton'
        );

        if (!(submitButton instanceof HTMLButtonElement)) {
            return;
        }

        if (form.dataset.submitting === 'true') {
            event.preventDefault();

            return;
        }

        event.preventDefault();

        form.dataset.submitting = 'true';
        submitButton.disabled = true;

        submitButton
            .querySelector('.registration-submit__label')
            ?.classList.add('d-none');

        submitButton
            .querySelector('.registration-submit__loading')
            ?.classList.remove('d-none');

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                form.submit();
            });
        });
    });
});