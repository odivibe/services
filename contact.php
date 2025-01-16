<?php

session_start();

require_once  'include/config.php';
require_once 'include/header.php';
?>

<main>
    <div class="contact-us-container">
        <div class="contact-us-text-container">
            <h1>Contact Us</h1>
            <p>
                We’d love to hear from you! Whether you have questions about our services, need support, or want to provide feedback, feel free to reach out to us.
            </p>
            <hr>
            <h2>Contact Information</h2>
            <ul>
                <li><strong>Email:</strong> support@goservice.com.ng</li>
                <li><strong>Phone:</strong> +123-456-7890</li>
                <li><strong>Address:</strong> 123 Main Street, City, Country</li>
            </ul>
            <hr>
            <p><a href="<?php echo BASE_URL; ?>faq.php">FAQ Page</a></p>
        </div>

        <div class="form-container">
            <h1>Send Us a Message</h1>
            <form method="POST" action="contact.php">
                <!-- Name -->
                <div class="form-group">
                    <label for="name">Full Name:</label>
                    <input type="text" name="name" id="name" placeholder="Enter your full name" required>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email Address:</label>
                    <input type="email" name="email" id="email" placeholder="Enter your email address" required>
                </div>

                <!-- Subject -->
                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" name="subject" id="subject" placeholder="Enter subject" required>
                </div>

                <!-- Message -->
                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea name="message" id="message" rows="5" placeholder="Write your message" required></textarea>
                </div>

                <!-- Submit -->
                <div class="form-group">
                    <button type="submit" name="submit">Send Message</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once 'include/footer.php'; ?>