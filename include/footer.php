        <footer class="footer">
            <!-- Subscribe to Newsletter Section -->
            <div class="newsletter">
                <h2>Subscribe to Our Newsletter</h2>
                <p>
                    Stay updated with the latest services and news from us! Enter your email below to subscribe.
                </p>
                <form class="newsletter-form" action="<?php htmlspecialchars('../services/newsletter.php', ENT_QUOTES, 'UTF-8'); ?>" method="post">

                    <input type="email" placeholder="Your Email" name="email" required>
                    <button type="submit" name="subscribe">Subscribe</button>

                    <!-- Display success or error message -->
                    <?php if (isset($success_message)): ?>
                        <div class="success-message"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error_message)): ?>
                        <div class="error-message"><?php echo $error_message; ?></div>
                    <?php endif; ?>

                </form>
            </div>

            <!-- footer links -->
            <div class="footer-links">
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>about.php">About Us</a></li>
                    <li><a href="<?php echo BASE_URL; ?>contact.php">Contact</a></li>
                    <li><a href="<?php echo BASE_URL; ?>faq.php">FAQ</a></li>
                    <li><a href="<?php echo BASE_URL; ?>privacy-policy.php">Privacy Policy</a></li>
                    <li><a href="<?php echo BASE_URL; ?>terms-of-service.php">Terms of Service</a></li>
                </ul>
            </div>
            
            <!-- Copyright -->
            <div class="footer-copyright">
                &copy; <?php echo date('Y'); ?> Service Classifieds. All Rights Reserved.
            </div>
        </footer>

        <!-- External js script -->
        <script src="<?php echo BASE_URL; ?>js/sticky-header.js"></script>
        <script src="<?php echo BASE_URL; ?>js/post-service-ajax.js"></script>
        <script src="<?php echo BASE_URL; ?>js/manage-account.js"></script>
        <script src="<?php echo BASE_URL; ?>js/faq-questions.js"></script>
        <script src="<?php echo BASE_URL; ?>js/resend-verification-email-ajax.js"></script>
    </body>
</html>