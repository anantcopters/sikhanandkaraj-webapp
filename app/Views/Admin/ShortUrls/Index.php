<?php

declare(strict_types=1);

use App\Support\DateDisplay;

/**
 * @var list<array<string, mixed>>  $shortUrls
 * @var array<string, string>      $validationErrors
 * @var array<string, string>|null $formAlert
 */

$shortUrls =
    isset($shortUrls)
    && is_array($shortUrls)
    ? $shortUrls
    : [];

$validationErrors =
    isset($validationErrors)
    && is_array($validationErrors)
    ? $validationErrors
    : [];

$this->extend(
    'Admin/Layouts/Main'
);

$this->section(
    'content'
);
?>

<div class="container-fluid">

    <div class="row">
        <div class="col-12">

            <div
                class="page-title-box
                    d-sm-flex
                    align-items-center
                    justify-content-between">

                <div>
                    <h4 class="mb-sm-0">
                        Short URLs
                    </h4>

                    <p class="text-muted mb-0 mt-1">
                        Create SikhanandKaraj short URLs for DLT and SMS
                        communication.
                    </p>
                </div>

            </div>
        </div>
    </div>

    <?= view(
        'Components/Alerts/FormAlert',
        [
            'alert' =>
            $formAlert
                ?? null,
        ]
    ) ?>

    <div class="row">

        <div class="col-xl-8">

            <div class="card">

                <div class="card-header">
                    <h5 class="card-title mb-1">
                        Create Short URL
                    </h5>

                    <p class="text-muted mb-0">
                        Enter a SikhanandKaraj application URL.
                        If it has already been shortened, the existing
                        short URL will be returned.
                    </p>
                </div>

                <div class="card-body">

                    <form
                        method="post"
                        action="<?= route_to(
                                    'admin.short-urls.store'
                                ) ?>"
                        data-short-url-form
                        data-submit-loader
                        novalidate>

                        <?= csrf_field() ?>

                        <div class="mb-3">

                            <label
                                for="destinationUrl"
                                class="form-label">

                                Destination URL
                            </label>

                            <input
                                type="url"
                                class="form-control
                                    <?= isset(
                                        $validationErrors['destination_url']
                                    )
                                        ? 'is-invalid'
                                        : '' ?>"
                                id="destinationUrl"
                                name="destination_url"
                                maxlength="2048"
                                required
                                placeholder="<?= esc(
                                                    base_url(
                                                        'field-officer/login'
                                                    ),
                                                    'attr'
                                                ) ?>"
                                value="<?= esc(
                                            old(
                                                'destination_url'
                                            ),
                                            'attr'
                                        ) ?>">

                            <?php if (
                                isset(
                                    $validationErrors['destination_url']
                                )
                            ): ?>

                                <div class="invalid-feedback">
                                    <?= esc(
                                        $validationErrors['destination_url']
                                    ) ?>
                                </div>

                            <?php else: ?>

                                <div class="form-text color-pink">
                                    Only URLs belonging to this
                                    SikhanandKaraj environment are allowed.
                                </div>

                            <?php endif; ?>

                        </div>

                        <div
                            class="d-flex
                                justify-content-end">

                            <button
                                type="submit"
                                class="btn
                                    registration-form__submit
                                    fs-14
                                    fw-medium
                                    text-uppercase w-25"
                                data-submit-button>

                                <span
                                    class="registration-submit__idle"
                                    data-submit-idle>

                                    <i
                                        class="ri-links-line
                                            fs-20
                                            align-middle
                                            me-1"
                                        aria-hidden="true"></i>

                                    Create Short URL
                                </span>

                                <span
                                    class="registration-submit__loading
                                        d-none"
                                    data-submit-loading>

                                    <span
                                        class="spinner-border
                                            spinner-border-sm
                                            me-1"
                                        role="status"
                                        aria-hidden="true"></span>

                                    Creating...
                                </span>

                            </button>

                        </div>

                    </form>

                </div>
            </div>

        </div>

    </div>

    <div class="row">

        <div class="col-12">

            <div class="card">

                <div class="card-header">

                    <h5 class="card-title mb-1">
                        Recent Short URLs
                    </h5>

                    <p class="text-muted mb-0">
                        Most recently created short URLs.
                    </p>

                </div>

                <div class="card-body">

                    <?php if ($shortUrls === []): ?>

                        <div
                            class="text-center
                                text-muted
                                py-4">

                            No short URLs have been created yet.

                        </div>

                    <?php else: ?>

                        <div class="table-responsive">

                            <table
                                class="table
                                    table-nowrap
                                    align-middle
                                    mb-0">

                                <thead>
                                    <tr>
                                        <th>Short URL</th>
                                        <th>Destination</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <?php foreach (
                                        $shortUrls
                                        as $shortUrl
                                    ): ?>

                                        <?php
                                        $generatedUrl =
                                            base_url(
                                                'ISAK/'
                                                    . (string) $shortUrl['short_code']
                                            );

                                        $displayCreatedAt =
                                            DateDisplay::formatUtcDateTime(
                                                $shortUrl['created_at'] ?? null
                                            );
                                        ?>

                                        <tr>

                                            <td>
                                                <div
                                                    class="d-flex
            align-items-center
            gap-2">

                                                    <a
                                                        href="<?= esc(
                                                                    $generatedUrl,
                                                                    'attr'
                                                                ) ?>"
                                                        target="_blank"
                                                        rel="noopener noreferrer">

                                                        <?= esc(
                                                            $generatedUrl
                                                        ) ?>

                                                    </a>

                                                    <button
                                                        type="button"
                                                        class="btn
                btn-sm
                btn-outline-secondary"
                                                        data-copy-short-url
                                                        data-copy-value="<?= esc(
                                                                                $generatedUrl,
                                                                                'attr'
                                                                            ) ?>"
                                                        title="Copy Short URL"
                                                        aria-label="Copy Short URL">

                                                        <i
                                                            class="ri-file-copy-line"
                                                            aria-hidden="true"></i>

                                                    </button>

                                                </div>
                                            </td>

                                            <td>
                                                <div
                                                    class="text-break"
                                                    style="max-width: 560px;">

                                                    <?= esc(
                                                        (
                                                            (string)
                                                            $shortUrl['destination_url']
                                                        )
                                                    ) ?>

                                                </div>
                                            </td>

                                            <td class="text-muted">
                                                <?= esc(
                                                    $displayCreatedAt
                                                ) ?>
                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>

</div>

<script>
    document.addEventListener(
        'DOMContentLoaded',
        function() {
            const form =
                document.querySelector(
                    '[data-short-url-form]'
                );

            if (form) {
                form.addEventListener(
                    'submit',
                    function(event) {
                        const input =
                            form.querySelector(
                                '[name="destination_url"]'
                            );

                        if (!input) {
                            return;
                        }

                        const value =
                            input.value.trim();

                        input.value = value;

                        if (
                            value === '' ||
                            !input.checkValidity()
                        ) {
                            event.preventDefault();
                            event.stopPropagation();

                            input.classList.add(
                                'is-invalid'
                            );

                            input.reportValidity();

                            return;
                        }

                        input.classList.remove(
                            'is-invalid'
                        );
                    }
                );
            }

            document
                .querySelectorAll(
                    '[data-copy-short-url]'
                )
                .forEach(
                    function(button) {
                        button.addEventListener(
                            'click',
                            async function() {
                                const value =
                                    button.getAttribute(
                                        'data-copy-value'
                                    );

                                if (!value) {
                                    return;
                                }

                                try {
                                    await navigator
                                        .clipboard
                                        .writeText(
                                            value
                                        );

                                    const icon =
                                        button.querySelector(
                                            'i'
                                        );

                                    if (!icon) {
                                        return;
                                    }

                                    icon.className =
                                        'ri-check-line';

                                    window.setTimeout(
                                        function() {
                                            icon.className =
                                                'ri-file-copy-line';
                                        },
                                        1500
                                    );
                                } catch (error) {
                                    return;
                                }
                            }
                        );
                    }
                );
        }
    );
</script>

<?= $this->endSection() ?>