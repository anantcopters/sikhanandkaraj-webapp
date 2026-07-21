(function (window, document) {
    'use strict';

    const modalElement = document.getElementById(
        'appFeedbackModal'
    );

    if (
        !modalElement
        || typeof bootstrap === 'undefined'
    ) {
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(
        modalElement,
        {
            backdrop: 'static',
            keyboard: true
        }
    );

    const titleElement = document.getElementById(
        'appFeedbackModalTitle'
    );

    const messageElement = document.getElementById(
        'appFeedbackModalMessage'
    );

    const iconElement = document.getElementById(
        'appFeedbackModalIcon'
    );

    const buttonElement = document.getElementById(
        'appFeedbackModalButton'
    );

    if (
        !titleElement
        || !messageElement
        || !iconElement
        || !buttonElement
    ) {
        return;
    }

    const supportedTypes = [
        'info',
        'success',
        'warning',
        'error'
    ];

    const iconClasses = {
        info: 'mdi-information-outline',
        success: 'mdi-check-circle-outline',
        warning: 'mdi-alert-outline',
        error: 'mdi-alert-circle-outline'
    };

    let closeCallback = null;

    /**
     * @param {string} type
     * @returns {string}
     */
    function normalizeType(type) {
        return supportedTypes.includes(type)
            ? type
            : 'info';
    }

    /**
     * @param {string} type
     */
    function setType(type) {
        const normalizedType = normalizeType(type);

        supportedTypes.forEach(function (supportedType) {
            modalElement.classList.remove(
                'app-feedback-modal--' + supportedType
            );
        });

        modalElement.classList.add(
            'app-feedback-modal--' + normalizedType
        );

        const icon = iconElement.querySelector('i');

        if (!icon) {
            return;
        }

        Object.values(iconClasses).forEach(
            function (iconClass) {
                icon.classList.remove(iconClass);
            }
        );

        icon.classList.add(
            iconClasses[normalizedType]
        );
    }

    /**
     * @param {{
     *     type?: string,
     *     title?: string,
     *     message?: string,
     *     buttonText?: string,
     *     onClose?: Function
     * }} options
     */
    function show(options) {
        const settings = options || {};

        titleElement.textContent =
            settings.title || 'Information';

        messageElement.textContent =
            settings.message || '';

        buttonElement.textContent =
            settings.buttonText || 'Okay';

        closeCallback =
            typeof settings.onClose === 'function'
                ? settings.onClose
                : null;

        setType(settings.type || 'info');

        modal.show();
    }

    modalElement.addEventListener(
        'hidden.bs.modal',
        function () {
            if (
                typeof closeCallback !== 'function'
            ) {
                return;
            }

            const callback = closeCallback;

            closeCallback = null;

            callback();
        }
    );

    window.AppFeedbackModal = {
        show: show,

        hide: function () {
            modal.hide();
        }
    };
})(window, document);