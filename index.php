<?php
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=2.0">
    <title>YouTube Watch & Earn - Watch Videos & Earn Money</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" href="includes/favicon.png" type="image/png">
    <script src="https://unpkg.com/alpinejs" defer></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        }
        .hover-scale {
            transition: transform 0.3s ease;
        }
        .hover-scale:hover {
            transform: scale(1.05);
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .parallax {
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }
        .hero-overlay {
            background: linear-gradient(135deg, rgba(30, 64, 175, 0.9) 0%, rgba(59, 130, 246, 0.9) 100%);
        }
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
        .glow {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
        }
        .glow:hover {
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.8);
        }
        .testimonial-card {
            transition: all 0.3s ease;
        }
        .testimonial-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Enhanced Navigation -->
<nav class="fixed w-full z-50 bg-white/80 backdrop-blur-md shadow-lg">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between h-20">
            <div class="flex items-center">
                <div class="flex-shrink-0 flex items-center">
                    <span class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-blue-400 bg-clip-text text-transparent">
                        YT Watch & Earn
                    </span>
                </div>
                <div class="hidden sm:ml-10 sm:flex sm:space-x-8">
                    <a href="index.php" class="border-transparent text-gray-500 hover:text-blue-600 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors duration-200">
                        Home
                    </a>
                    <a href="about.php" class="border-transparent text-gray-500 hover:text-blue-600 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors duration-200">
                        About Us
                    </a>
                    <a href="how-it-works.php" class="border-transparent text-gray-500 hover:text-blue-600 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors duration-200">
                        How It Works
                    </a>
                    <a href="contact.php" class="border-transparent text-gray-500 hover:text-blue-600 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors duration-200">
                        Contact Us
                    </a>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="text-gray-700 font-medium">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="dashboard.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition-colors duration-200 shadow-lg hover:shadow-xl">
                        Dashboard
                    </a>
                    <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded-lg transition-colors duration-200 shadow-lg hover:shadow-xl">
                        Logout
                    </a>
                <?php else: ?>
                    <a href="login.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition-colors duration-200 shadow-lg hover:shadow-xl">
                        Login
                    </a>
                    <a href="register.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg transition-colors duration-200 shadow-lg hover:shadow-xl">
                        Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

    <!-- Enhanced Hero Section with Parallax -->
    <div class="relative h-screen overflow-hidden">
        <div class="parallax absolute inset-0" style="background-image: url('https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1074&q=80');"></div>
        <div class="hero-overlay absolute inset-0"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full flex items-center">
            <div class="text-center w-full">
                <h1 class="text-4xl tracking-tight font-extrabold text-white sm:text-5xl md:text-6xl">
                    <span class="block">Watch YouTube Videos</span>
                    <span class="block text-blue-200">Earn Real Money</span>
                </h1>
                <p class="mt-3 max-w-md mx-auto text-base text-blue-100 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
                    Join thousands of users who are earning money by watching YouTube videos. Start earning today!
                </p>
                <div class="mt-5 max-w-md mx-auto sm:flex sm:justify-center md:mt-8">
                    <div class="rounded-md shadow-lg glow">
                        <a href="register.php" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-blue-700 bg-white hover:bg-gray-50 md:py-4 md:text-lg md:px-10 transition-all duration-300">
                            Get Started
                        </a>
                    </div>
                    <div class="mt-3 rounded-md shadow-lg glow sm:mt-0 sm:ml-3">
                        <a href="how-it-works.php" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 md:py-4 md:text-lg md:px-10 transition-all duration-300">
                            Learn More
                        </a>
                    </div>
                </div>
                <div class="mt-8 flex justify-center">
                    <div class="floating">
                        <i class="fas fa-chevron-down text-white text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="bg-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-white rounded-xl shadow-lg p-6 hover-scale">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                            <i class="fas fa-users text-blue-600 text-2xl"></i>
                        </div>
                        <div class="ml-5">
                            <dt class="text-lg font-medium text-gray-900 truncate">Active Users</dt>
                            <dd class="mt-1 text-3xl font-semibold text-blue-600">10,000+</dd>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 hover-scale">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                            <i class="fas fa-money-bill-wave text-green-600 text-2xl"></i>
                        </div>
                        <div class="ml-5">
                            <dt class="text-lg font-medium text-gray-900 truncate">Total Earnings</dt>
                            <dd class="mt-1 text-3xl font-semibold text-green-600">PKR 500K+</dd>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 hover-scale">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-purple-100 rounded-md p-3">
                            <i class="fas fa-video text-purple-600 text-2xl"></i>
                        </div>
                        <div class="ml-5">
                            <dt class="text-lg font-medium text-gray-900 truncate">Videos Watched</dt>
                            <dd class="mt-1 text-3xl font-semibold text-purple-600">50K+</dd>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 hover-scale">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-yellow-100 rounded-md p-3">
                            <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                        </div>
                        <div class="ml-5">
                            <dt class="text-lg font-medium text-gray-900 truncate">Watch Time</dt>
                            <dd class="mt-1 text-3xl font-semibold text-yellow-600">10K+ hrs</dd>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="bg-gray-50 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:text-center">
                <h2 class="text-base text-blue-600 font-semibold tracking-wide uppercase">Features</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Why Choose Our Platform
                </p>
                <p class="mt-4 max-w-2xl text-xl text-gray-500 lg:mx-auto">
                    We provide the best platform for earning money by watching YouTube videos.
                </p>
            </div>

            <div class="mt-10">
                <div class="space-y-10 md:space-y-0 md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-10">
                    <div class="relative bg-white p-6 rounded-xl shadow-lg hover-scale">
                        <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                            <i class="fas fa-money-bill-wave text-xl"></i>
                        </div>
                        <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Earn Money</p>
                        <p class="mt-2 ml-16 text-base text-gray-500">
                            Get paid 5 PKR for each video you watch. Earn up to 300 PKR in total.
                        </p>
                    </div>

                    <div class="relative bg-white p-6 rounded-xl shadow-lg hover-scale">
                        <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                            <i class="fas fa-shield-alt text-xl"></i>
                        </div>
                        <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Secure Platform</p>
                        <p class="mt-2 ml-16 text-base text-gray-500">
                            Advanced security features to protect your earnings and account.
                        </p>
                    </div>

                    <div class="relative bg-white p-6 rounded-xl shadow-lg hover-scale">
                        <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                        <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Flexible Timing</p>
                        <p class="mt-2 ml-16 text-base text-gray-500">
                            Watch videos at your convenience, anytime, anywhere.
                        </p>
                    </div>

                    <div class="relative bg-white p-6 rounded-xl shadow-lg hover-scale">
                        <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                            <i class="fas fa-headset text-xl"></i>
                        </div>
                        <p class="ml-16 text-lg leading-6 font-medium text-gray-900">24/7 Support</p>
                        <p class="mt-2 ml-16 text-base text-gray-500">
                            Our support team is always ready to help you with any issues.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Testimonials Section with Carousel -->
    <div class="bg-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:text-center">
                <h2 class="text-base text-blue-600 font-semibold tracking-wide uppercase">Testimonials</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    What Our Users Say
                </p>
            </div>

            <div class="mt-10" x-data="{ activeSlide: 0 }">
                <div class="relative">
                    <div class="overflow-hidden">
                        <div class="flex transition-transform duration-500" :style="'transform: translateX(-' + (activeSlide * 100) + '%)'">
                            <!-- Testimonial 1 -->
                            <div class="w-full flex-shrink-0 px-4">
                                <div class="bg-gray-50 p-6 rounded-xl shadow-lg testimonial-card">
                                    <div class="flex items-center">
                                        <img class="h-12 w-12 rounded-full" src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="User">
                                        <div class="ml-4">
                                            <h3 class="text-lg font-medium text-gray-900">John Doe</h3>
                                            <p class="text-sm text-gray-500">Earned PKR 2,500</p>
                                        </div>
                                    </div>
                                    <p class="mt-4 text-gray-500">
                                        "I've been using this platform for 3 months and have already earned over 2,500 PKR. The process is simple and payments are always on time."
                                    </p>
                                </div>
                            </div>

                            <!-- Testimonial 2 -->
                            <div class="w-full flex-shrink-0 px-4">
                                <div class="bg-gray-50 p-6 rounded-xl shadow-lg testimonial-card">
                                    <div class="flex items-center">
                                        <img class="h-12 w-12 rounded-full" src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="User">
                                        <div class="ml-4">
                                            <h3 class="text-lg font-medium text-gray-900">Jane Smith</h3>
                                            <p class="text-sm text-gray-500">Earned PKR 1,800</p>
                                        </div>
                                    </div>
                                    <p class="mt-4 text-gray-500">
                                        "As a student, this platform has been a great way to earn some extra money. I can watch videos in my free time and get paid for it."
                                    </p>
                                </div>
                            </div>

                            <!-- Testimonial 3 -->
                            <div class="w-full flex-shrink-0 px-4">
                                <div class="bg-gray-50 p-6 rounded-xl shadow-lg testimonial-card">
                                    <div class="flex items-center">
                                        <img class="h-12 w-12 rounded-full" src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="User">
                                        <div class="ml-4">
                                            <h3 class="text-lg font-medium text-gray-900">Mike Johnson</h3>
                                            <p class="text-sm text-gray-500">Earned PKR 3,200</p>
                                        </div>
                                    </div>
                                    <p class="mt-4 text-gray-500">
                                        "The best part about this platform is the flexibility. I can watch videos whenever I want and the earnings add up quickly."
                                    </p>
                                </div>
                            </div>

                            <!-- Testimonial 4 -->
                            <div class="w-full flex-shrink-0 px-4">
                                <div class="bg-gray-50 p-6 rounded-xl shadow-lg testimonial-card">
                                    <div class="flex items-center">
                                        <img class="h-12 w-12 rounded-full" src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="User">
                                        <div class="ml-4">
                                            <h3 class="text-lg font-medium text-gray-900">Sarah Williams</h3>
                                            <p class="text-sm text-gray-500">Earned PKR 4,500</p>
                                        </div>
                                    </div>
                                    <p class="mt-4 text-gray-500">
                                        "I love how easy it is to use this platform. The support team is amazing and always helps with any questions I have."
                                    </p>
                                </div>
                            </div>

                            <!-- Testimonial 5 -->
                            <div class="w-full flex-shrink-0 px-4">
                                <div class="bg-gray-50 p-6 rounded-xl shadow-lg testimonial-card">
                                    <div class="flex items-center">
                                        <img class="h-12 w-12 rounded-full" src="https://images.unsplash.com/photo-1519244703995-f4e0f30006d5?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="User">
                                        <div class="ml-4">
                                            <h3 class="text-lg font-medium text-gray-900">David Brown</h3>
                                            <p class="text-sm text-gray-500">Earned PKR 5,000</p>
                                        </div>
                                    </div>
                                    <p class="mt-4 text-gray-500">
                                        "This platform has helped me earn a significant amount of money while doing something I enjoy. Highly recommended!"
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <button @click="activeSlide = (activeSlide - 1 + 5) % 5" class="absolute left-0 top-1/2 transform -translate-y-1/2 bg-white rounded-full p-2 shadow-lg hover:bg-gray-100 transition-colors duration-200">
                        <i class="fas fa-chevron-left text-blue-600"></i>
                    </button>
                    <button @click="activeSlide = (activeSlide + 1) % 5" class="absolute right-0 top-1/2 transform -translate-y-1/2 bg-white rounded-full p-2 shadow-lg hover:bg-gray-100 transition-colors duration-200">
                        <i class="fas fa-chevron-right text-blue-600"></i>
                    </button>

                    <!-- Indicators -->
                    <div class="flex justify-center mt-4 space-x-2">
                        <template x-for="i in 5">
                            <button @click="activeSlide = i - 1" class="w-2 h-2 rounded-full" :class="activeSlide === i - 1 ? 'bg-blue-600' : 'bg-gray-300'"></button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- How It Works Section -->
    <div class="bg-gray-50 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:text-center">
                <h2 class="text-base text-blue-600 font-semibold tracking-wide uppercase">Process</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Start Earning in 3 Simple Steps
                </p>
            </div>

            <div class="mt-10">
                <div class="space-y-10 md:space-y-0 md:grid md:grid-cols-3 md:gap-x-8 md:gap-y-10">
                    <div class="relative bg-white p-6 rounded-xl shadow-lg hover-scale">
                        <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                            <span class="text-xl font-bold">1</span>
                        </div>
                        <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Create Your Account</p>
                        <p class="mt-2 ml-16 text-base text-gray-500">
                            Sign up for free with your email address and create a secure password.
                        </p>
                    </div>

                    <div class="relative bg-white p-6 rounded-xl shadow-lg hover-scale">
                        <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                            <span class="text-xl font-bold">2</span>
                        </div>
                        <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Watch Videos</p>
                        <p class="mt-2 ml-16 text-base text-gray-500">
                            Browse our curated collection of YouTube videos and watch them completely.
                        </p>
                    </div>

                    <div class="relative bg-white p-6 rounded-xl shadow-lg hover-scale">
                        <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                            <span class="text-xl font-bold">3</span>
                        </div>
                        <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Get Paid</p>
                        <p class="mt-2 ml-16 text-base text-gray-500">
                            Earn money for each video you watch and withdraw your earnings.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="bg-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:text-center">
                <h2 class="text-base text-blue-600 font-semibold tracking-wide uppercase">FAQ</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Frequently Asked Questions
                </p>
            </div>

            <div class="mt-10 max-w-3xl mx-auto">
                <div class="space-y-6">
                    <div class="bg-gray-50 p-6 rounded-xl shadow-lg hover-scale">
                        <h3 class="text-lg font-medium text-gray-900">
                            How much can I earn per video?
                        </h3>
                        <p class="mt-2 text-base text-gray-500">
                            You can earn 5 PKR for each video you watch completely.
                        </p>
                    </div>

                    <div class="bg-gray-50 p-6 rounded-xl shadow-lg hover-scale">
                        <h3 class="text-lg font-medium text-gray-900">
                            How do I withdraw my earnings?
                        </h3>
                        <p class="mt-2 text-base text-gray-500">
                            You can withdraw your earnings through various payment methods including bank transfer, mobile wallets, and more.
                        </p>
                    </div>

                    <div class="bg-gray-50 p-6 rounded-xl shadow-lg hover-scale">
                        <h3 class="text-lg font-medium text-gray-900">
                            Is there a minimum withdrawal amount?
                        </h3>
                        <p class="mt-2 text-base text-gray-500">
                            Yes, the minimum withdrawal amount is 100 PKR.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="gradient-bg">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-8 lg:flex lg:items-center lg:justify-between">
            <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                <span class="block">Ready to start earning?</span>
                <span class="block text-blue-200">Join us today and start watching videos.</span>
            </h2>
            <div class="mt-8 flex lg:mt-0 lg:flex-shrink-0">
                <div class="inline-flex rounded-md shadow">
                    <a href="register.php" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50">
                        Get Started
                    </a>
                </div>
                <div class="ml-3 inline-flex rounded-md shadow">
                    <a href="how-it-works.php" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        Learn More
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Footer -->
    <footer class="bg-gray-900">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-white text-sm font-semibold tracking-wider uppercase">About</h3>
                    <ul class="mt-4 space-y-4">
                        <li><a href="about.php" class="text-base text-gray-300 hover:text-white transition-colors duration-200">About Us</a></li>
                        <li><a href="how-it-works.php" class="text-base text-gray-300 hover:text-white transition-colors duration-200">How It Works</a></li>
                        <li><a href="privacy.php" class="text-base text-gray-300 hover:text-white transition-colors duration-200">Privacy Policy</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-white text-sm font-semibold tracking-wider uppercase">Support</h3>
                    <ul class="mt-4 space-y-4">
                        <li><a href="contact.php" class="text-base text-gray-300 hover:text-white transition-colors duration-200">Contact Us</a></li>
                        <li><a href="faq.php" class="text-base text-gray-300 hover:text-white transition-colors duration-200">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-white text-sm font-semibold tracking-wider uppercase">Legal</h3>
                    <ul class="mt-4 space-y-4">
                        <li><a href="terms.php" class="text-base text-gray-300 hover:text-white transition-colors duration-200">Terms of Service</a></li>
                        <li><a href="privacy.php" class="text-base text-gray-300 hover:text-white transition-colors duration-200">Privacy Policy</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-white text-sm font-semibold tracking-wider uppercase">Connect</h3>
                    <ul class="mt-4 space-y-4">
                        <li class="flex items-center">
                            <a href="#" class="text-gray-300 hover:text-white transition-colors duration-200">
                                <i class="fab fa-facebook-f mr-2"></i> Facebook
                            </a>
                        </li>
                        <li class="flex items-center">
                            <a href="#" class="text-gray-300 hover:text-white transition-colors duration-200">
                                <i class="fab fa-twitter mr-2"></i> Twitter
                            </a>
                        </li>
                        <li class="flex items-center">
                            <a href="#" class="text-gray-300 hover:text-white transition-colors duration-200">
                                <i class="fab fa-instagram mr-2"></i> Instagram
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="mt-8 border-t border-gray-800 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p class="text-base text-gray-400">
                        &copy; 2024 YouTube Watch & Earn. All rights reserved.
                    </p>
                    <div class="mt-4 md:mt-0 flex space-x-6">
                        <a href="#" class="text-gray-400 hover:text-gray-300">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-gray-300">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-gray-300">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</body>
</html> 