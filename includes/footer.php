<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Footer Preview</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
 <link href="css/footer-styles.css" rel="stylesheet">
</head>
<body data-theme="light">
    <div class="demo-container">
        <div class="demo-content">
            <div class="text-center">
                <h1>Dashboard with Enhanced Footer</h1>
                <p class="text-muted">Scroll down to see the footer with theme support</p>
                <p class="mt-4">
                    <button class="btn btn-primary me-2" onclick="switchTheme('light')"><i class="fas fa-sun"></i> Light</button>
                    <button class="btn btn-dark me-2" onclick="switchTheme('dark')"><i class="fas fa-moon"></i> Dark</button>
                    <button class="btn me-2" style="background: #0277bd; color: white;" onclick="switchTheme('ocean')"><i class="fas fa-water"></i> Ocean</button>
                    <button class="btn me-2" style="background: #2e7d32; color: white;" onclick="switchTheme('forest')"><i class="fas fa-leaf"></i> Forest</button>
                </p>
            </div>
        </div>

        <!-- Enhanced Footer -->
        <footer class="footer">
            <div class="container">
                <!-- Support AI Section -->
                <div class="support-ai-section">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 style="margin-bottom: 10px;">
                                <i class="fas fa-robot"></i> Need Help? Chat with our AI Assistant
                            </h5>
                            <p style="margin: 0; opacity: 0.9;">Get instant answers to your questions about Church Management</p>
                        </div>
                        <div class="col-md-4 text-md-end text-start mt-3 mt-md-0">
                            <button class="support-btn" onclick="openAIChat()">
                                <i class="fas fa-comments"></i> Start Chat
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Footer Content -->
                <div class="row">
                    <!-- Quick Links -->
                    <div class="col-md-3">
                        <div class="footer-section">
                            <h5><i class="fas fa-link"></i> Quick Links</h5>
                            <ul>
                                <li><a href="#"><i class="fas fa-home"></i> Dashboard</a></li>
                                <li><a href="#"><i class="fas fa-book"></i> Documentation</a></li>
                                <li><a href="#"><i class="fas fa-question-circle"></i> FAQ</a></li>
                                <li><a href="#"><i class="fas fa-map"></i> Site Map</a></li>
                            </ul>
                        </div>
                    </div>

                    <!-- Support -->
                    <div class="col-md-3">
                        <div class="footer-section">
                            <h5><i class="fas fa-headset"></i> Support</h5>
                            <ul>
                                <li><a href="#"><i class="fas fa-envelope"></i> Email Support</a></li>
                                <li><a href="#"><i class="fas fa-phone"></i> +254 (0) 123 456 789</a></li>
                                <li><a href="#"><i class="fas fa-ticket-alt"></i> Submit Ticket</a></li>
                                <li><a href="#"><i class="fas fa-clock"></i> Hours: 24/7</a></li>
                            </ul>
                        </div>
                    </div>

                    <!-- Company -->
                    <div class="col-md-3">
                        <div class="footer-section">
                            <h5><i class="fas fa-building"></i> Company</h5>
                            <ul>
                                <li><a href="#"><i class="fas fa-info-circle"></i> About Us</a></li>
                                <li><a href="#"><i class="fas fa-file-contract"></i> Terms of Service</a></li>
                                <li><a href="#"><i class="fas fa-shield-alt"></i> Privacy Policy</a></li>
                                <li><a href="#"><i class="fas fa-balance-scale"></i> Legal</a></li>
                            </ul>
                        </div>
                    </div>

                    <!-- Follow Us -->
                    <div class="col-md-3">
                        <div class="footer-section">
                            <h5><i class="fas fa-share-alt"></i> Follow Us</h5>
                            <p style="margin-bottom: 15px; opacity: 0.9;">Connect with our community</p>
                            <div class="social-links">
                                <a href="#" title="Facebook" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" title="Twitter" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                                <a href="#" title="Instagram" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                                <a href="#" title="LinkedIn" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#" title="WhatsApp" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                            </div>

                            <!-- Theme Selector -->
                            <div class="theme-selector">
                                <button class="light-theme active" onclick="switchTheme('light')" title="Light Theme"><i class="fas fa-sun"></i></button>
                                <button class="dark-theme" onclick="switchTheme('dark')" title="Dark Theme"><i class="fas fa-moon"></i></button>
                                <button class="ocean-theme" onclick="switchTheme('ocean')" title="Ocean Theme"><i class="fas fa-water"></i></button>
                                <button class="forest-theme" onclick="switchTheme('forest')" title="Forest Theme"><i class="fas fa-leaf"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Bottom -->
                <div class="footer-bottom">
                    <p style="margin: 0;">
                        <span>&copy; 2024 Church Management System</span>
                        <span class="footer-divider">|</span>
                        <span>All rights reserved</span>
                        <span class="footer-divider">|</span>
                        <span><i class="fas fa-globe"></i> Made in Kenya with <i class="fas fa-heart" style="color: #ff6b6b;"></i></span>
                    </p>
                </div>
            </div>
        </footer>
    </div>

    <!-- AI Chat Modal -->
    <div class="ai-chat-modal" id="aiChatModal">
        <div class="ai-chat-panel">
            <div class="ai-chat-header">
                <h6 style="margin: 0;"><i class="fas fa-robot"></i> AI Support Assistant</h6>
                <button style="background: none; border: none; color: white; font-size: 20px; cursor: pointer;" onclick="closeAIChat()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="ai-chat-messages" id="chatMessages">
                <div class="ai-message">
                    <strong>AI Assistant:</strong> Hello! 👋 How can I help you today? You can ask me about:
                    <ul style="margin-top: 10px; margin-bottom: 0;">
                        <li>System features</li>
                        <li>User management</li>
                        <li>Reports and analytics</li>
                        <li>Account settings</li>
                    </ul>
                </div>
            </div>
            <div class="ai-chat-input">
                <input type="text" id="chatInput" placeholder="Type your question..." onkeypress="handleChatInput(event)">
                <button style="background: #667eea; color: white; border: none; border-radius: 6px; padding: 10px 15px; cursor: pointer;" onclick="sendMessage()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>


    <script src="js/footer.js"></script>
</body>
</html>