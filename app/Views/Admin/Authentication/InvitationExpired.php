<?php

declare(strict_types=1);

$this->extend('Admin/Layouts/Main');
$this->section('content');
?>

<section class="admin-auth-section">
    <div class="container">
        <div
            class="row justify-content-center
                align-items-center min-vh-100 py-4">

            <div
                class="col-12 col-sm-10
                    col-md-7 col-lg-5">

                <div
                    class="admin-auth-card text-center">

                    <div
                        class="admin-auth-icon
                            admin-auth-icon--danger">
                        <i class="ri-link-unlink-m"></i>
                    </div>

                    <h1 class="fs-24 mb-3">
                        Invitation Expired
                    </h1>

                    <p class="text-muted mb-4">
                        This invitation has expired, has already
                        been used, or is no longer valid.
                    </p>

                    <p class="mb-0">
                        Contact the super administrator to receive
                        a new invitation.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>
