'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const conversationItems = () =>
        Array.from(
            document.querySelectorAll(
                '[data-conversation-item]'
            )
        );

    const search =
        document.querySelector(
            '[data-message-search]'
        );

    const noUnreadMessage =
        document.querySelector(
            '[data-no-unread-message]'
        );

    let activeFilter = 'all';

    const applyConversationFilters = () => {
        const query =
            search
                ? search.value
                    .trim()
                    .toLowerCase()
                : '';

        let visibleUnread = 0;

        conversationItems()
            .forEach((item) => {
                const searchableValue =
                    (
                        item.dataset
                            .conversationSearch
                        || ''
                    ).toLowerCase();

                const unread =
                    item.dataset
                        .conversationUnread
                    === '1';

                const matchesSearch =
                    query === ''
                    || searchableValue
                        .includes(query);

                const matchesFilter =
                    activeFilter !== 'unread'
                    || unread;

                const visible =
                    matchesSearch
                    && matchesFilter;

                item.classList.toggle(
                    'd-none',
                    !visible
                );

                if (
                    visible
                    && unread
                ) {
                    visibleUnread += 1;
                }
            });

        if (noUnreadMessage) {
            noUnreadMessage
                .classList
                .toggle(
                    'd-none',
                    activeFilter !== 'unread'
                    || visibleUnread > 0
                );
        }
    };

    if (search) {
        search.addEventListener(
            'input',
            applyConversationFilters
        );
    }

    document
        .querySelectorAll(
            '[data-message-filter]'
        )
        .forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    activeFilter =
                        button.dataset
                            .messageFilter
                        || 'all';

                    document
                        .querySelectorAll(
                            '[data-message-filter]'
                        )
                        .forEach(
                            (filterButton) => {
                                filterButton
                                    .classList
                                    .toggle(
                                        'active',
                                        filterButton
                                        === button
                                    );
                            }
                        );

                    applyConversationFilters();
                }
            );
        });

    document
        .querySelectorAll(
            '[data-message-form]'
        )
        .forEach((form) => {
            const input =
                form.querySelector(
                    '[data-message-input]'
                );

            const counter =
                form.querySelector(
                    '[data-message-counter]'
                );

            const requestId =
                form.querySelector(
                    '[name="client_request_id"]'
                );

            const privacyWarning =
                form.querySelector(
                    '[data-message-privacy-warning]'
                );

            if (
                requestId
                && requestId.value === ''
            ) {
                if (
                    window.crypto
                    && typeof window.crypto.randomUUID
                    === 'function'
                ) {
                    requestId.value =
                        window.crypto.randomUUID();
                } else {
                    requestId.value =
                        `${Date.now()}-${Math.random()
                            .toString(36)
                            .slice(2)}-${Math.random()
                                .toString(36)
                                .slice(2)}`;
                }
            }

            if (!input) {
                return;
            }

            const maximumLength =
                Number(
                    input.getAttribute(
                        'maxlength'
                    )
                ) || 200;

            const updateCounter = () => {
                if (counter) {
                    counter.textContent =
                        `${input.value.length}/${maximumLength}`;
                }
            };

            /*
             * Guidance only.
             *
             * Product explicitly does not hard-block phone/email-like
             * content in V1.
             */
            const updatePrivacyWarning = () => {
                if (!privacyWarning) {
                    return;
                }

                const value =
                    input.value.trim();

                const containsEmail =
                    /[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i
                        .test(value);

                const containsPhone =
                    /(?:\+?\d[\d\s().-]{7,}\d)/
                        .test(value);

                privacyWarning
                    .classList
                    .toggle(
                        'd-none',
                        !containsEmail
                        && !containsPhone
                    );
            };

            input.addEventListener(
                'input',
                () => {
                    updateCounter();
                    updatePrivacyWarning();
                }
            );

            updateCounter();
            updatePrivacyWarning();
        });

    /*
     * Opening a conversation should show its newest messages.
     *
     * This is UI scrolling only. V1 continues loading the latest
     * bounded message history from the server.
     */
    const messageScroller =
        document.querySelector(
            '[data-message-scroll]'
        );

    if (messageScroller) {
        messageScroller.scrollTop =
            messageScroller.scrollHeight;
    }

    applyConversationFilters();
});