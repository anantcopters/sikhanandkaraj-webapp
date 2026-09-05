<?php

declare(strict_types=1);

$modalId =
    trim(
        (string) (
            $modalId
            ?? 'messaging-upgrade-modal'
        )
    );

$plansUrl =
    route_to(
        'web.account.settings.section',
        'plans'
    );
?>

<div
    class="modal fade"
    id="<?= esc(
            $modalId,
            'attr'
        ) ?>"
    tabindex="-1"
    aria-labelledby="<?= esc(
                            $modalId,
                            'attr'
                        ) ?>-title"
    aria-hidden="true">

    <div
        class="modal-dialog
            modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="<?= esc(
                            $modalId,
                            'attr'
                        ) ?>-title">

                    <i
                        class="ri-message-3-line
                            text-primary
                            me-1"
                        aria-hidden="true">
                    </i>

                    Member Messaging

                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close">
                </button>

            </div>

            <div class="modal-body">

                <p class="mb-0">
                    Messaging is available with membership.
                    You can receive and read messages from members.
                    Upgrade to start conversations and reply.
                </p>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-light"
                    data-bs-dismiss="modal">

                    Cancel

                </button>

                <a
                    href="<?= $plansUrl ?>"
                    class="btn btn-primary">

                    View Plans

                </a>

            </div>

        </div>

    </div>

</div>