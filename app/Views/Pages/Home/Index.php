<?php

declare(strict_types=1);

/**
 * @var string|null $pageTitle
 */
$resolvedPageTitle = isset($pageTitle) && is_string($pageTitle)
    ? $pageTitle
    : 'Sikh Anand Karaj';

$this->extend('Layouts/Main');
$this->section('content');
?>

<section class="home-registration-section">
    <div class="container">
        <div class="row align-items-center home-registration-section__row">

            <!--
                Reserved for promotional content or artwork.
                This column is intentionally blank for now.
            -->
            <div
                class="col-lg-7 d-none d-lg-block"
                aria-hidden="true"></div>

            <div class="col-12 col-lg-5">
                <div class="registration-card">

                    <div class="registration-card__header">
                        <h1 class="fs-22 fw-normal mb-0 text-white text-center lh-base">
                            Create a Matrimony Profile
                        </h1>
                    </div>

                    <div class="registration-card__body">

                        <h2 class="fs-20 fw-bold text-dark text-center lh-base mt-0 mb-4">
                            Find your perfect match
                        </h2>

                        <form
                            action="<?= site_url('register') ?>"
                            method="post"
                            class="registration-form"
                            autocomplete="off">
                            <?= csrf_field() ?>

                            <div class="mb-4">
                                <label
                                    for="profileCreatedFor"
                                    class="visually-hidden">
                                    Profile created for
                                </label>

                                <select
                                    id="profileCreatedFor"
                                    name="profile_created_for"
                                    class="form-select form-select-lg"
                                    required>
                                    <option value="" selected disabled>
                                        Profile created for
                                    </option>

                                    <option value="self">Self</option>
                                    <option value="son">Son</option>
                                    <option value="daughter">Daughter</option>
                                    <option value="brother">Brother</option>
                                    <option value="sister">Sister</option>
                                    <option value="relative">Relative</option>
                                    <option value="friend">Friend</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label
                                    for="fullName"
                                    class="visually-hidden">
                                    Full name
                                </label>

                                <input
                                    type="text"
                                    id="fullName"
                                    name="full_name"
                                    class="form-control form-control-lg"
                                    placeholder="Enter the name"
                                    maxlength="100"
                                    autocomplete="name"
                                    required>
                            </div>

                            <div class="mb-4">
                                <div class="input-group registration-mobile-group">

                                    <label
                                        for="countryCode"
                                        class="visually-hidden">
                                        Country code
                                    </label>

                                    <select
                                        id="countryCode"
                                        name="country_code"
                                        class="form-select form-select-lg registration-country-code"
                                        aria-label="Country code"
                                        required>
                                        <option value="+91" selected>
                                            +91
                                        </option>
                                    </select>

                                    <label
                                        for="mobileNumber"
                                        class="visually-hidden">
                                        Mobile number
                                    </label>

                                    <input
                                        type="tel"
                                        id="mobileNumber"
                                        name="mobile_number"
                                        class="form-control form-control-lg"
                                        placeholder="Enter Mobile Number"
                                        inputmode="numeric"
                                        pattern="[6-9][0-9]{9}"
                                        minlength="10"
                                        maxlength="10"
                                        autocomplete="tel"
                                        required>
                                </div>

                                <p class="registration-form__help fs-13 text-dark mb-0">
                                    OTP will be sent to this number
                                </p>
                            </div>

                            <button
                                type="submit"
                                class="btn registration-form__submit fs-16 fw-semibold text-uppercase">
                                <span>Register Free</span>

                                <span
                                    class="mdi mdi-arrow-right fs-20"
                                    aria-hidden="true"></span>
                            </button>

                            <p class="registration-form__terms fs-12 text-muted mb-0">
                                By clicking Register Free, I agree to the
                                <a href="<?= site_url('terms-and-conditions') ?>">
                                    T&amp;C
                                </a>
                                and
                                <a href="<?= site_url('privacy-policy') ?>">
                                    Privacy Policy
                                </a>.
                            </p>
                        </form>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>

<?php $this->endSection(); ?>