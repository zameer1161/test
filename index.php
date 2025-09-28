<?php
session_start();
require 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduTrack - Smart Attendance Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --accent: #4cc9f0;
            --light: #f8f9fa;
            --dark: #212529;
            --gradient: linear-gradient(135deg, #4361ee, #4cc9f0);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--dark);
        }

        /* Navigation */
        .navbar {
            background: white;
            box-shadow: 0 2px 20px rgba(67, 97, 238, 0.1);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary) !important;
            font-size: 1.5rem;
        }

        .nav-link {
            color: var(--dark) !important;
            font-weight: 500;
            margin: 0 10px;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary) !important;
        }

        .btn-primary-custom {
            background: var(--gradient);
            border: none;
            border-radius: 10px;
            padding: 10px 25px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.3);
            color: white;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #4361ee 0%, #4cc9f0 100%);
            color: white;
            padding: 100px 0;
            text-align: center;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        /* Features Section */
        .features {
            padding: 100px 0;
            background: var(--light);
        }

        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
            height: 100%;
            border: 1px solid rgba(67, 97, 238, 0.1);
        }

        .feature-card:hover {
            transform: translateY(-10px);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: rgba(67, 97, 238, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--primary);
            font-size: 2rem;
        }

        /* How It Works */
        .how-it-works {
            padding: 100px 0;
            background: white;
        }

        .step-card {
            text-align: center;
            padding: 30px;
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: var(--gradient);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 auto 20px;
        }

        /* Stats Section */
        .stats {
            padding: 80px 0;
            background: var(--gradient);
            color: white;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        /* CTA Section */
        .cta {
            padding: 100px 0;
            background: var(--light);
            text-align: center;
        }

        /* Footer */
        .footer {
            background: var(--dark);
            color: white;
            padding: 60px 0 30px;
        }

        .footer h5 {
            color: var(--accent);
            margin-bottom: 20px;
        }

        .footer a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: var(--accent);
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            margin-right: 10px;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero p {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-chalkboard-teacher me-2"></i>EduTrack
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                    <li class="nav-item"><a class="nav-link" href="#benefits">Benefits</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item">
                        <a class="btn btn-primary-custom ms-2" href="register.php">Get Started</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1>Smart Attendance Tracking Made Simple</h1>
                    <p>Streamline your educational institution's attendance management with our intuitive, efficient, and reliable tracking system.</p>
                    <div class="mt-4">
                        <a href="register.php" class="btn btn-light btn-lg me-3" style="border-radius: 10px; padding: 12px 30px;">
                            <i class="fas fa-rocket me-2"></i>Start Free
                        </a>
                        <a href="#features" class="btn btn-outline-light btn-lg" style="border-radius: 10px; padding: 12px 30px;">
                            <i class="fas fa-play-circle me-2"></i>Learn More
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Attendance System" class="img-fluid" style="max-height: 400px;">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold mb-3">Powerful Features</h2>
                <p class="lead">Everything you need for efficient attendance management</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <h4>Quick Marking</h4>
                        <p>Mark attendance quickly with our intuitive interface. Support for multiple status types including Present, Absent, Late, and Sick.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h4>Detailed Reports</h4>
                        <p>Generate comprehensive reports with visual charts and analytics. Track attendance patterns and identify trends.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <h4>Multi-Role Access</h4>
                        <p>Different access levels for Admins, Teachers, and Students. Secure role-based permissions system.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h4>Mobile Friendly</h4>
                        <p>Fully responsive design that works perfectly on all devices - desktop, tablet, and mobile.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-download"></i>
                        </div>
                        <h4>Export Data</h4>
                        <p>Export attendance records to Excel and PDF formats for offline analysis and record keeping.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4>Secure & Private</h4>
                        <p>Your data is protected with secure authentication and encrypted storage.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="how-it-works">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold mb-3">How It Works</h2>
                <p class="lead">Simple steps to manage attendance effectively</p>
            </div>
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h4>Register</h4>
                        <p>Create your account as Admin, Teacher, or Student with secure credentials.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h4>Manage Classes</h4>
                        <p>Admins and Teachers can set up classes and student profiles.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h4>Mark Attendance</h4>
                        <p>Teachers quickly mark daily attendance with our streamlined interface.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <h4>Analyze & Report</h4>
                        <p>Generate detailed reports and analyze attendance patterns.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="row text-center">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-number">99.9%</div>
                    <p>Uptime Reliability</p>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-number">500+</div>
                    <p>Active Users</p>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-number">50K+</div>
                    <p>Records Processed</p>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-number">24/7</div>
                    <p>Support Available</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section id="benefits" class="features">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold mb-3">Key Benefits</h2>
                <p class="lead">Why choose EduTrack for your institution</p>
            </div>
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock text-primary fs-1 me-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h4>Save Time</h4>
                            <p>Reduce manual paperwork and automate attendance tracking processes. Teachers can mark attendance in seconds instead of minutes.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-chart-line text-primary fs-1 me-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h4>Data-Driven Insights</h4>
                            <p>Get valuable insights into student attendance patterns and identify areas that need attention with comprehensive analytics.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-lock text-primary fs-1 me-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h4>Enhanced Security</h4>
                            <p>Secure user authentication and role-based access control ensure that sensitive attendance data remains protected.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-hand-holding-usd text-primary fs-1 me-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h4>Cost Effective</h4>
                            <p>Eliminate the need for expensive hardware and reduce administrative costs with our digital solution.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <h2 class="display-4 fw-bold mb-3">Ready to Transform Your Attendance Management?</h2>
            <p class="lead mb-4">Join hundreds of educational institutions already using EduTrack</p>
            <a href="register.php" class="btn btn-primary-custom btn-lg">
                <i class="fas fa-user-plus me-2"></i>Get Started Today
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5><i class="fas fa-chalkboard-teacher me-2"></i>EduTrack</h5>
                    <p>Revolutionizing attendance management for educational institutions with smart, efficient, and reliable tracking solutions.</p>
                    <div class="social-links mt-3">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#">Home</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#how-it-works">How It Works</a></li>
                        <li><a href="#benefits">Benefits</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Account</h5>
                    <ul class="list-unstyled">
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                        <li><a href="#">Dashboard</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h5>Contact Info</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i> support@edutrack.com</li>
                        <li><i class="fas fa-phone me-2"></i> +1 (555) 123-4567</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> 123 Education St, Learn City</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center">
                <p>&copy; 2024 EduTrack. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>