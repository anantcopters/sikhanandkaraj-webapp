<?php

declare(strict_types=1);

use App\Services\Email\EmailDefinition;

/**
 * @var array<string, EmailDefinition> $definitions
 */

$definitions =
    isset($definitions)
    && is_array($definitions)
    ? $definitions
    : [];

$this->extend(
    'Admin/Layouts/Main'
);

$this->section('content');
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
                        Email Preview Centre
                    </h4>

                    <p class="text-muted mb-0 mt-1">
                        Review the exact email templates
                        used by the application and send
                        controlled test emails.
                    </p>
                </div>

            </div>

        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">

            <div class="table-responsive">

                <table
                    class="table table-hover
                    table-nowrap align-middle mb-0">

                    <thead class="bg-info-subtle">
                        <tr>
                            <th>Email</th>
                            <th>Category</th>
                            <th>Subject</th>
                            <th class="text-end">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach (
                            $definitions as $definition
                        ): ?>

                            <tr>

                                <td>
                                    <div class="fw-medium">
                                        <?= esc(
                                            $definition
                                                ->name
                                        ) ?>
                                    </div>

                                    <div
                                        class="text-muted
                                        fs-12">
                                        <?= esc(
                                            $definition
                                                ->key
                                        ) ?>
                                    </div>
                                </td>

                                <td>
                                    <span
                                        class="badge
                                        bg-primary-subtle
                                        text-body p-2">
                                        <?= esc(
                                            $definition
                                                ->category
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= esc(
                                        $definition
                                            ->subject
                                    ) ?>
                                </td>

                                <td
                                    class="text-end">

                                    <a
                                        href="<?= route_to(
                                                    'admin.email-preview.preview',
                                                    $definition->key
                                                ) ?>"
                                        class="btn btn-sm
                                        btn-soft-primary">

                                        <i
                                            class="ri-eye-line
                                            align-middle me-1">
                                        </i>

                                        Preview
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>
    </div>

</div>

<?php $this->endSection(); ?>