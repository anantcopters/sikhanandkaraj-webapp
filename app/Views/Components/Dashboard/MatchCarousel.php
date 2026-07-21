<?php

declare(strict_types=1);

/**
 * @var string $sectionTitle
 * @var string|null $sectionDescription
 * @var string|null $viewAllUrl
 * @var array<int, array<string, mixed>> $profiles
 */

$resolvedProfiles = isset($profiles) && is_array($profiles)
    ? $profiles
    : [];

$resolvedViewAllUrl = isset($viewAllUrl)
    && is_string($viewAllUrl)
    && $viewAllUrl !== ''
    ? $viewAllUrl
    : '#';
?>

<section class="member-match-section">
    <div class="member-match-section__header">
        <div>
            <h2 class="member-dashboard__section-title">
                <?= esc($sectionTitle ?? 'Matches') ?>
            </h2>

            <?php if (
                isset($sectionDescription)
                && is_string($sectionDescription)
                && $sectionDescription !== ''
            ): ?>
                <p class="member-dashboard__section-description">
                    <?= esc($sectionDescription) ?>
                </p>
            <?php endif; ?>
        </div>

        <a
            href="<?= esc($resolvedViewAllUrl, 'attr') ?>"
            class="member-match-section__view-all">
            View all
            <i class="ri-arrow-right-line" aria-hidden="true"></i>
        </a>
    </div>

    <?php if ($resolvedProfiles !== []): ?>
        <div
            class="member-profile-strip"
            role="list"
            aria-label="<?= esc(
                            $sectionTitle ?? 'Match profiles',
                            'attr'
                        ) ?>">

            <?php foreach ($resolvedProfiles as $profile): ?>
                <?php
                $profileImage = $profile['image'] ?? null;
                $profileName = trim(
                    (string) ($profile['name'] ?? 'Member')
                );
                ?>
                <article
                    class="member-profile-card"
                    role="listitem">

                    <a
                        href="<?= esc(
                                    (string) (
                                        $profile['profileUrl']
                                        ?? '#'
                                    ),
                                    'attr'
                                ) ?>"
                        class="member-profile-card__link">

                        <div class="member-profile-card__image-wrap">
                            <?php if (
                                is_string($profileImage)
                                && $profileImage !== ''
                            ): ?>
                                <img
                                    src="<?= esc(
                                                base_url($profileImage),
                                                'attr'
                                            ) ?>"
                                    alt="<?= esc(
                                                $profileName,
                                                'attr'
                                            ) ?>"
                                    class="member-profile-card__image"
                                    loading="lazy">
                            <?php else: ?>
                                <div
                                    class="member-profile-card__placeholder"
                                    aria-hidden="true">
                                    <i class="ri-user-3-line"></i>
                                </div>
                            <?php endif; ?>

                            <span class="member-profile-card__online"></span>
                        </div>

                        <div class="member-profile-card__body">
                            <h3 class="member-profile-card__name">
                                <?= esc($profileName) ?>
                            </h3>

                            <p class="member-profile-card__meta">
                                <?= esc(
                                    (string) (
                                        $profile['age']
                                        ?? ''
                                    )
                                ) ?>
                                years
                                <span aria-hidden="true">•</span>
                                <?= esc(
                                    (string) (
                                        $profile['height']
                                        ?? ''
                                    )
                                ) ?>
                            </p>

                            <?php if (
                                !empty($profile['referenceId'])
                            ): ?>
                                <p class="member-profile-card__reference">
                                    <?= esc(
                                        (string) $profile['referenceId']
                                    ) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="member-dashboard__empty-state">
            <i class="ri-user-search-line"></i>
            <p>No profiles are available yet.</p>
        </div>
    <?php endif; ?>
</section>