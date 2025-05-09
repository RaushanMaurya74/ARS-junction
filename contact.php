<?php
$page_title = "Contact Us";
require_once 'includes/header.php';

$success = false;
$error = '';

// Handle contact form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = clean_input($_POST['name']);
    $email = clean_input($_POST['email']);
    $subject = clean_input($_POST['subject']);
    $message = clean_input($_POST['message']);
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // In a real application, you would send an email here
        // For now, we'll just simulate success
        $success = true;
    }
}
?>

<!-- Contact Hero -->
<section class="section-bg mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h1 class="mb-4">Contact Us</h1>
                <p class="lead">We'd Love to Hear From You</p>
                <p>Have questions, suggestions, or feedback? Our team is here to help! Get in touch with us using any of the methods below, and we'll get back to you as soon as possible.</p>
            </div>
            <div class="col-lg-6">
                <img src="https://pixabay.com/get/g7d4a4c89c18b48b42196c9d0932d3be2a2da3e2284193887f3dc6a2cc9b3d34b065c8bbc1e757368f8458dbf6d4d821078ad1638359ee65563322540a5bfe082_1280.jpg" alt="Food Delivery" class="img-fluid rounded">
            </div>
        </div>
    </div>
</section>

<!-- Contact Information -->
<section class="mb-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <div class="display-4 text-primary mb-3">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h4>Our Location</h4>
                        <p>AT - PIRO, BHOJPUR,<br> BIHAR, INDIA-802207</p>
                        <a href="https://goo.gl/maps/yourlocationlink" target="_blank" class="btn btn-outline-primary">Get Directions</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <div class="display-4 text-primary mb-3">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <h4>Phone Number</h4>
                        <p>Call us for any inquiries or to place an order by phone.</p>
                        <a href="tel:+917979730721" class="btn btn-outline-primary">+91 7979730721</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <div class="display-4 text-primary mb-3">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h4>Email Address</h4>
                        <p>Send us an email and we'll get back to you within 24 hours.</p>
                        <a href="mailto:arsjunction79793@gmail.com" class="btn btn-outline-primary">arsjunction79793@gmail.com</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Form -->
<section class="mb-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="section-bg">
                    <h2 class="mb-4 text-center">Send Us a Message</h2>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i> Thank you for your message! We'll get back to you soon.
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form action="contact.php" method="post" <?php if ($success) echo 'style="display: none;"'; ?>>
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="name" class="form-label">Your Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Your Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="6" required></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="mb-5">
    <div class="container">
        <div class="section-bg">
            <h2 class="mb-4 text-center">Find Us on the Map</h2>
            <div class="embed-responsive embed-responsive-21by9">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d14399.37835508978!2d84.38894725541992!3d25.33023684378424!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x398d11efc7faacc7%3A0x93dc7cac95aafce5!2sPiro%2C%20Bihar%20802207!5e0!3m2!1sen!2sin!4v1650123456789!5m2!1sen!2sin" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>
</section>

<!-- FAQs Section -->
<section class="mb-5">
    <div class="container">
        <div class="section-bg">
            <h2 class="mb-4 text-center">Frequently Asked Questions</h2>
            
            <div class="accordion" id="faqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq1">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse1" aria-expanded="true" aria-controls="faqCollapse1">
                            How do I place an order?
                        </button>
                    </h2>
                    <div id="faqCollapse1" class="accordion-collapse collapse show" aria-labelledby="faq1" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            To place an order, simply browse our restaurants, select the items you want, add them to your cart, and proceed to checkout. You'll need to create an account if you don't already have one.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq2">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2">
                            What are the delivery hours?
                        </button>
                    </h2>
                    <div id="faqCollapse2" class="accordion-collapse collapse" aria-labelledby="faq2" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Delivery hours depend on the restaurant's operating hours. Most restaurants on our platform are open from 10:00 AM to 10:00 PM, but this may vary. You can check each restaurant's hours on their page.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq3">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse3" aria-expanded="false" aria-controls="faqCollapse3">
                            How can I track my order?
                        </button>
                    </h2>
                    <div id="faqCollapse3" class="accordion-collapse collapse" aria-labelledby="faq3" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Once your order is confirmed, you can track its status in real-time from the "My Orders" section in your profile. You'll receive updates as your order is prepared, picked up, and delivered.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq4">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse4" aria-expanded="false" aria-controls="faqCollapse4">
                            What payment methods do you accept?
                        </button>
                    </h2>
                    <div id="faqCollapse4" class="accordion-collapse collapse" aria-labelledby="faq4" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            We accept cash on delivery, credit/debit cards, and digital wallets. You can choose your preferred payment method during checkout.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq5">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse5" aria-expanded="false" aria-controls="faqCollapse5">
                            How can I cancel my order?
                        </button>
                    </h2>
                    <div id="faqCollapse5" class="accordion-collapse collapse" aria-labelledby="faq5" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            You can cancel your order within 5 minutes of placing it through the "My Orders" section. After this time, please contact our customer support team to assist you with cancellation.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once 'includes/footer.php';
?>
