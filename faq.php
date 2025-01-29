<?php

session_start();

require_once  'include/config.php';
require_once 'include/db.php';;
require_once 'include/header.php';
?>

<main class="body-container">
    <div class="faq-container">
        <h1>Frequently Asked Questions (FAQ)</h1>

        <div class="faq-item">
            <div class="faq-question">How do I post an ad?</div>
            <div class="faq-answer">You can post an ad by clicking the 'Post Ad' button on the top right corner of the website and filling out the required details.</div>
        </div>

        <div class="faq-item">
            <div class="faq-question">Is it free to post an ad?</div>
            <div class="faq-answer">Yes, posting an ad is free for basic listings. However, premium listings may incur additional charges.</div>
        </div>

        <div class="faq-item">
            <div class="faq-question">How do I edit or delete my ad?</div>
            <div class="faq-answer">Log in to your account, go to 'My Ads' section, and select the ad you want to edit or delete.</div>
        </div>

        <div class="faq-item">
            <div class="faq-question">What categories are supported?</div>
            <div class="faq-answer">We support categories such as Electronics, Real Estate, Vehicles, Services, Jobs, and more.</div>
        </div>

        <div class="faq-item">
            <div class="faq-question">How can I contact the seller?</div>
            <div class="faq-answer">You can contact the seller using the provided phone number or email available on the ad details page.</div>
        </div>
    </div>
</main>

<?php require_once 'include/footer.php'; ?>