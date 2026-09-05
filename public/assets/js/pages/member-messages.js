'use strict';

document.addEventListener('DOMContentLoaded', () => {
    document
        .querySelectorAll('[data-message-form]')
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

            if (
                !input
                || !counter
            ) {
                return;
            }

            const maximumLength =
                Number(
                    input.getAttribute(
                        'maxlength'
                    )
                ) || 200;

            const updateCounter = () => {
                counter.textContent =
                    `${input.value.length}/${maximumLength}`;
            };

            input.addEventListener(
                'input',
                updateCounter
            );

            updateCounter();
        });

    const search =
        document.querySelector(
            '[data-message-search]'
        );

    if (search) {
        search.addEventListener(
            'input',
            () => {
                const query =
                    search.value
                        .trim()
                        .toLowerCase();

                document
                    .querySelectorAll(
                        '[data-conversation-item]'
                    )
                    .forEach((item) => {
                        const value =
                            (
                                item.dataset
                                    .conversationSearch
                                || ''
                            ).toLowerCase();

                        item.classList.toggle(
                            'd-none',
                            query !== ''
                            && !value.includes(
                                query
                            )
                        );
                    });
            }
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
                    const filter =
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

                    document
                        .querySelectorAll(
                            '[data-conversation-item]'
                        )
                        .forEach((item) => {
                            const unread =
                                item.dataset
                                    .conversationUnread
                                === '1';

                            item.classList
                                .toggle(
                                    'd-none',
                                    filter === 'unread'
                                    && !unread
                                );
                        });
                }
            );
        });
});