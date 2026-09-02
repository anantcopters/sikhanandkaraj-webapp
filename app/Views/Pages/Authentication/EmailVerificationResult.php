<?php

declare(strict_types=1);

/**
 * @var bool $verificationSuccessful
 * @var string $verificationMessage
 */

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="email-verification-result">
    <div class="container">
        <div class="email-verification-result__card border border-danger border-opacity-25">

            <div
                class="email-verification-result__icon
                    <?= $verificationSuccessful
                        ? 'email-verification-result__icon--success'
                        : 'email-verification-result__icon--error'
                    ?>">

                <i
                    class="<?= $verificationSuccessful
                                ? 'ri-checkbox-circle-line'
                                : 'ri-error-warning-line'
                            ?>">
                </i>
            </div>

            <h1 class="email-verification-result__title">
                <?= $verificationSuccessful
                    ? 'Email Verified'
                    : 'Verification Unsuccessful'
                ?>
            </h1>

            <p class="email-verification-result__message">
                <?= esc($verificationMessage) ?>
            </p>

            <a
                href="<?= url_to(
                            session('is_authenticated') === true
                                ? 'web.dashboard'
                                : 'web.login'
                        ) ?>"
                class="btn email-verification-result__action">

                <?= session('is_authenticated') === true
                    ? 'Go to Dashboard'
                    : 'Go to Login'
                ?>
            </a>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>
