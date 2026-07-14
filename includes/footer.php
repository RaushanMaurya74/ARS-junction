</div>
<!-- End of Main Content Container -->

<!-- Footer -->
<footer class="footer bg-dark text-light mt-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4 mb-md-0">
                <h5 class="mb-3"><span class="text-warning">ARS</span> JUNCTION</h5>
                <p>Your one-stop destination for delicious food from the best restaurants around you. Order online and enjoy a hassle-free delivery experience.</p>
                <div class="social-icons">
                    <a href="#" class="text-light me-2"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-light me-2"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-light me-2"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-light"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="col-md-4 mb-4 mb-md-0">
                <h5 class="mb-3">Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="index.php" class="text-light text-decoration-none"><i class="fas fa-chevron-right me-2"></i>Home</a></li>
                    <li><a href="restaurants.php" class="text-light text-decoration-none"><i class="fas fa-chevron-right me-2"></i>Restaurants</a></li>
                    <li><a href="menu.php" class="text-light text-decoration-none"><i class="fas fa-chevron-right me-2"></i>Menu</a></li>
                    <li><a href="about.php" class="text-light text-decoration-none"><i class="fas fa-chevron-right me-2"></i>About Us</a></li>
                    <li><a href="contact.php" class="text-light text-decoration-none"><i class="fas fa-chevron-right me-2"></i>Contact Us</a></li>
                    <?php if (is_logged_in()): ?>
                        <li><a href="profile.php" class="text-light text-decoration-none"><i class="fas fa-chevron-right me-2"></i>My Profile</a></li>
                        <li><a href="order_tracking.php" class="text-light text-decoration-none"><i class="fas fa-chevron-right me-2"></i>Track Orders</a></li>
                    <?php else: ?>
                        <li><a href="login.php" class="text-light text-decoration-none"><i class="fas fa-chevron-right me-2"></i>Login</a></li>
                        <li><a href="register.php" class="text-light text-decoration-none"><i class="fas fa-chevron-right me-2"></i>Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="col-md-4">
                <h5 class="mb-3">Contact Information</h5>
                <ul class="list-unstyled contact-info">
                    <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars(get_site_setting('site_location', 'AT - PIRO, BHOJPUR, BIHAR, INDIA-802207')); ?></li>
                    <li class="mb-2"><i class="fas fa-phone me-2"></i>+91 <?php echo htmlspecialchars(get_site_setting('site_phone', '7979730721')); ?></li>
                    <li class="mb-2"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars(get_site_setting('site_email', 'arsjunction79793@gmail.com')); ?></li>
                    <li class="mb-2"><i class="fas fa-clock me-2"></i>Open: 24/7</li>
                </ul>
            </div>
        </div>
        <hr class="my-4 bg-light">
        <div class="row">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> ARS JUNCTION. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="mb-0">
                    <a href="#" class="text-light text-decoration-none">Terms of Service</a> | 
                    <a href="#" class="text-light text-decoration-none">Privacy Policy</a>
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Custom JS -->
<script src="js/main.js"></script>
<?php if (isset($extra_js)): echo $extra_js; endif; ?>

<?php include_once 'includes/chatbot.php'; ?>

</body>
</html>
