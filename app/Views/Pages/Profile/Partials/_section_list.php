<?php

declare(strict_types=1);

/**
 * @var array<int, array<string, string>> $upcomingSections
 */

$sections = is_array($upcomingSections ?? null)
    ? $upcomingSections
    : [];
?>

<?php if ($sections !== []): ?>
    <div class="row g-3 mt-1">
        <?php foreach ($sections as $section): ?>
            <div class="col-12 col-md-6">
                <article
                    class="card border border-danger border-opacity-25 shadow-none h-100">
                    <div class="card-body p-3">
                        <div
                            class="d-flex align-items-start
                                justify-content-between gap-3">
                            <div
                                class="d-flex align-items-start
                                    gap-3">
                                <div
                                    class="avatar-sm flex-shrink-0"
                                    aria-hidden="true">
                                    <span
                                        class="avatar-title
                                            rounded-circle
                                            bg-light text-muted fs-20">
                                        <i
                                            class="<?= esc(
                                                        $section['icon']
                                                            ?? 'ri-file-line',
                                                        'attr'
                                                    ) ?>"></i>
                                    </span>
                                </div>

                                <div>
                                    <h3
                                        class="fs-15 fw-semibold mb-1">
                                        <?= esc(
                                            $section['title']
                                                ?? ''
                                        ) ?>
                                    </h3>

                                    <p
                                        class="text-muted
                                            fs-13 mb-0">
                                        <?= esc(
                                            $section['description']
                                                ?? ''
                                        ) ?>
                                    </p>
                                </div>
                            </div>

                            <span
                                class="badge bg-light p-2
                                    text-muted border">
                                Coming next
                            </span>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>