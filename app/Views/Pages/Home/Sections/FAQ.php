<?php

declare(strict_types=1);
?>

<section class="py-5 bg-white">
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-12 col-lg-8 text-center">
                <span
                    class="badge
                        bg-danger-subtle
                        text-danger
                        text-uppercase
                        mb-3">

                    Frequently Asked Questions
                </span>

                <h2 class="fw-bold mb-0">
                    Common questions
                </h2>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-12 col-lg-9">
                <div
                    class="accordion"
                    id="homeFaqAccordion">

                    <div class="accordion-item">
                        <h3
                            class="accordion-header"
                            id="homeFaqHeadingOne">

                            <button
                                type="button"
                                class="accordion-button"
                                data-bs-toggle="collapse"
                                data-bs-target="
                                    #homeFaqCollapseOne"
                                aria-expanded="true"
                                aria-controls="
                                    homeFaqCollapseOne">

                                Who can create a profile?
                            </button>
                        </h3>

                        <div
                            id="homeFaqCollapseOne"
                            class="accordion-collapse
                                collapse show"
                            aria-labelledby="
                                homeFaqHeadingOne"
                            data-bs-parent="
                                #homeFaqAccordion">

                            <div class="accordion-body">
                                Members can create profiles for
                                themselves or eligible close family
                                members using the available options.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h3
                            class="accordion-header"
                            id="homeFaqHeadingTwo">

                            <button
                                type="button"
                                class="accordion-button collapsed"
                                data-bs-toggle="collapse"
                                data-bs-target="
                                    #homeFaqCollapseTwo"
                                aria-expanded="false"
                                aria-controls="
                                    homeFaqCollapseTwo">

                                Is my information protected?
                            </button>
                        </h3>

                        <div
                            id="homeFaqCollapseTwo"
                            class="accordion-collapse collapse"
                            aria-labelledby="
                                homeFaqHeadingTwo"
                            data-bs-parent="
                                #homeFaqAccordion">

                            <div class="accordion-body">
                                The platform uses controlled access,
                                privacy settings and secure media
                                delivery to protect information.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>