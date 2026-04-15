<?php

include 'hader.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Cafe4</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f5dc 0%, #fffacd 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .hero {
            text-align: center;
            padding: 100px 20px;
            color: #5c4033;
        }
        .hero h1 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .hero p {
            font-size: 20px;
            margin-bottom: 40px;
        }
        .btn-custom {
            background-color: #8b4513;
            border: none;
            color: white;
            padding: 15px 30px;
            border-radius: 5px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin: 10px;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            background-color: #5c4033;
            color: white;
            text-decoration: none;
        }
        .coffee-section {
            background: white;
            padding: 60px 0;
            border-radius: 10px;
            margin: 40px 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .coffee-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .coffee-card:hover {
            transform: translateY(-5px);
        }
        .coffee-icon {
            font-size: 48px;
            color: #8b4513;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="hero">
        <h1>Welcome to Cafe</h1>
        <p>Experience the perfect blend of exceptional coffee, delicious pastries, and a cozy atmosphere that makes every moment special.</p>
        <a href="login.php" class="btn-custom">Login</a>
        <a href="regisration.php" class="btn-custom">Register</a>
        <a href="menu.php" class="btn-custom">View Menu</a>
    </div>

    <!-- About Section -->
    <div class="coffee-section">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h2 class="text-center mb-4" style="color: #8b4513;">About Cafe4</h2>
                    <p>At Cafe4, we believe that great coffee is more than just a drink—it's an experience. Established in 2020, our cafe has become a beloved gathering place for coffee enthusiasts, students, professionals, and families alike.</p>
                    <p>We source our beans from sustainable farms around the world, ensuring every cup delivers rich, authentic flavors. Our skilled baristas craft each beverage with passion and precision, using traditional techniques combined with modern innovation.</p>
                    <p>Beyond exceptional coffee, we offer a curated selection of artisanal pastries, fresh sandwiches, and light meals made with locally sourced ingredients. Our cozy interior, complete with comfortable seating and free Wi-Fi, makes Cafe4 the perfect spot for work, study, or simply relaxing with friends.</p>
                </div>
                <div class="col-md-6 text-center">
                    <i class="fas fa-coffee coffee-icon"></i>
                    <p class="lead">Crafted with passion, served with love</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Specialties Section -->
    <div class="coffee-section">
        <div class="container">
            <h2 class="text-center mb-5" style="color: #8b4513;">Our Specialties</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="coffee-card card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-mug-hot coffee-icon"></i>
                            <h5 class="card-title">Premium Coffee</h5>
                            <p class="card-text">From classic espresso to innovative specialty drinks, our coffee menu features carefully selected beans roasted to perfection.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="coffee-card card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-birthday-cake coffee-icon"></i>
                            <h5 class="card-title">Fresh Pastries</h5>
                            <p class="card-text">Indulge in our daily baked selection of croissants, muffins, cakes, and cookies, all made fresh in-house every morning.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="coffee-card card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-utensils coffee-icon"></i>
                            <h5 class="card-title">Healthy Options</h5>
                            <p class="card-text">Enjoy nutritious salads, sandwiches, and wraps made with fresh, organic ingredients and wholesome alternatives.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Testimonials Section -->
    <div class="coffee-section">
        <div class="container">
            <h2 class="text-center mb-5" style="color: #8b4513;">What Our Customers Say</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="coffee-card card h-100">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                            </div>
                            <p class="card-text">"The best coffee I've ever had! The atmosphere is perfect for both work and relaxation. I come here almost every day."</p>
                            <footer class="blockquote-footer">Sarah Johnson</footer>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="coffee-card card h-100">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                            </div>
                            <p class="card-text">"Amazing pastries and friendly staff. The croissants are to die for! This place has become my go-to spot for breakfast."</p>
                            <footer class="blockquote-footer">Mike Chen</footer>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="coffee-card card h-100">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                            </div>
                            <p class="card-text">"Perfect place for meetings or just catching up with friends. Great Wi-Fi, comfortable seating, and excellent service."</p>
                            <footer class="blockquote-footer">Emma Davis</footer>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Location & Hours Section -->
    <div class="coffee-section">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h2 class="mb-4" style="color: #8b4513;">Visit Us</h2>
                    <div class="mb-3">
                        <i class="fas fa-map-marker-alt mr-2" style="color: #8b4513;"></i>
                        <strong>Address:</strong> 123 Coffee Street, Downtown District<br>
                        City, State 12345
                    </div>
                    <div class="mb-3">
                        <i class="fas fa-phone mr-2" style="color: #8b4513;"></i>
                        <strong>Phone:</strong> (555) 123-4567
                    </div>
                    <div class="mb-3">
                        <i class="fas fa-envelope mr-2" style="color: #8b4513;"></i>
                        <strong>Email:</strong> info@cafe4.com
                    </div>
                </div>
                <div class="col-md-6">
                    <h2 class="mb-4" style="color: #8b4513;">Opening Hours</h2>
                    <div class="mb-2"><strong>Monday - Friday:</strong> 6:00 AM - 8:00 PM</div>
                    <div class="mb-2"><strong>Saturday:</strong> 7:00 AM - 9:00 PM</div>
                    <div class="mb-2"><strong>Sunday:</strong> 8:00 AM - 6:00 PM</div>
                    <div class="mt-4">
                        <a href="contact.php" class="btn-custom">Contact Us</a>
                        <a href="about_us.php" class="btn-custom">Learn More</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include 'footer.php'; ?>