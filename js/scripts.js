// ===== LOTUS HOTEL - JAVASCRIPT =====
// Comprehensive JavaScript with animations, forms, and interactive features

document.addEventListener('DOMContentLoaded', function () {
    // Initialize all features
    initializeNavigation();
    initializeSmoothScrolling();
    initializeMobileMenu();
    initializeAnimations();
    initializeScrollEffects();
    initializePerspective();
    initializeCounters();
    initializeGallery();
    initializeRoomFilters();
    initializePaymentSystem();
    initializePreBooking();
    initializeForms();
    initializeNotifications();
    initializePackageToggles();
});

// ===== NAVIGATION FUNCTIONS =====
function initializeNavigation() {
    const navbar = document.querySelector('.navbar');
    let lastScrollTop = 0;

    window.addEventListener('scroll', function () {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        if (scrollTop > lastScrollTop && scrollTop > 100) {
            // Scrolling down
            navbar.style.transform = 'translateY(-100%)';
        } else {
            // Scrolling up
            navbar.style.transform = 'translateY(0)';
        }

        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;

        // Add background blur on scroll
        if (scrollTop > 50) {
            navbar.style.backdropFilter = 'blur(20px)';
        } else {
            navbar.style.backdropFilter = 'blur(10px)';
        }
    });
}

function initializeSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

function initializeMobileMenu() {
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');

    if (navbarToggler && navbarCollapse) {
        navbarToggler.addEventListener('click', function () {
            navbarCollapse.classList.toggle('show');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function (e) {
            if (!navbarCollapse.contains(e.target) && !navbarToggler.contains(e.target)) {
                navbarCollapse.classList.remove('show');
            }
        });
    }
}

// ===== ANIMATION FUNCTIONS =====
function initializeAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
            }
        });
    }, observerOptions);

    document.querySelectorAll('.fade-in-section').forEach(section => {
        observer.observe(section);
    });
}

function initializeScrollEffects() {
    const elements = document.querySelectorAll('.float-3d, .rotateX-3d, .scaleIn-3d, .slideInLeft-3d, .slideInRight-3d');

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
            }
        });
    });

    elements.forEach(element => {
        element.style.animationPlayState = 'paused';
        observer.observe(element);
    });
}

function initializePerspective() {
    document.querySelectorAll('.premium-card').forEach(card => {
        card.addEventListener('mousemove', function (e) {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            const centerX = rect.width / 2;
            const centerY = rect.height / 2;

            const rotateX = (y - centerY) / 10;
            const rotateY = (centerX - x) / 10;

            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(10px)`;
        });

        card.addEventListener('mouseleave', function () {
            card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateZ(0px)';
        });
    });
}

function initializeCounters() {
    const counters = document.querySelectorAll('[data-counter]');

    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-counter'));
        const duration = 2000; // 2 seconds
        const step = target / (duration / 16); // 60fps
        let current = 0;

        const timer = setInterval(() => {
            current += step;
            if (current >= target) {
                counter.textContent = target;
                clearInterval(timer);
            } else {
                counter.textContent = Math.floor(current);
            }
        }, 16);
    });
}


// ===== GALLERY FUNCTIONS =====
function initializeGallery() {
    const galleryItems = document.querySelectorAll('.gallery-item');
    const filterButtons = document.querySelectorAll('.filter-btn');
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightboxImg');
    const lightboxClose = document.getElementById('lightboxClose');

    if (!lightbox) return;

    // Filter Logic
    filterButtons.forEach(button => {
        button.addEventListener('click', function () {
            const filter = this.getAttribute('data-filter');
            const parentSection = this.closest('section');
            const itemsToFilter = parentSection.querySelectorAll('.gallery-item');

            // Update active button within the same section
            const siblings = this.parentElement.querySelectorAll('.filter-btn');
            siblings.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            // Filter items with animation
            itemsToFilter.forEach(item => {
                const category = item.getAttribute('data-category');

                if (filter === 'all' || category === filter) {
                    item.style.display = 'block';
                    setTimeout(() => {
                        item.style.opacity = '1';
                        item.style.transform = 'scale(1)';
                    }, 10);
                } else {
                    item.style.opacity = '0';
                    item.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        item.style.display = 'none';
                    }, 400);
                }
            });
        });
    });

    // Lightbox Logic
    galleryItems.forEach(item => {
        item.addEventListener('click', function () {
            const img = this.querySelector('img');
            if (img) {
                lightboxImg.src = img.src;
                lightbox.classList.add('show');
            }
        });
    });

    if (lightboxClose) {
        lightboxClose.addEventListener('click', function () {
            lightbox.classList.remove('show');
        });
    }

    lightbox.addEventListener('click', function (e) {
        if (e.target === lightbox) {
            lightbox.classList.remove('show');
        }
    });
}

// ===== ROOM FILTERING =====
function initializeRoomFilters() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const roomCards = document.querySelectorAll('.room-card');

    filterButtons.forEach(button => {
        button.addEventListener('click', function () {
            const filter = this.getAttribute('data-filter');

            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            // Filter rooms
            roomCards.forEach(card => {
                const roomType = card.getAttribute('data-room-type');

                if (filter === 'all' || roomType === filter) {
                    card.style.display = 'block';
                    card.style.animation = 'scaleIn-3d 0.5s ease-out';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
}

// ===== PAYMENT SYSTEM =====
function initializePaymentSystem() {
    const paymentMethods = document.querySelectorAll('.payment-method');
    const paymentDetails = document.getElementById('payment-details');

    paymentMethods.forEach(method => {
        method.addEventListener('click', function () {
            const methodType = this.getAttribute('data-method');

            // Update active method
            paymentMethods.forEach(m => m.classList.remove('active'));
            this.classList.add('active');

            // Show payment details
            showPaymentDetails(methodType);
        });
    });
}

function showPaymentDetails(method) {
    const paymentDetails = document.getElementById('payment-details');
    if (!paymentDetails) return;

    let detailsHTML = '';

    switch (method) {
        case 'credit-card':
            detailsHTML = `
                <div class="payment-form">
                    <h4>Credit Card Payment</h4>
                    <div class="mb-3">
                        <label class="form-label">Cardholder Name</label>
                        <input type="text" class="form-control" id="cardholder" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Card Number</label>
                        <input type="text" class="form-control" id="cardNumber" placeholder="1234 5678 9012 3456" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="text" class="form-control" id="expiry" placeholder="MM/YY" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">CVV</label>
                            <input type="text" class="form-control" id="cvv" placeholder="123" required>
                        </div>
                    </div>
                </div>
            `;
            break;

        case 'debit-card':
            detailsHTML = `
                <div class="payment-form">
                    <h4>Debit Card Payment</h4>
                    <div class="mb-3">
                        <label class="form-label">Cardholder Name</label>
                        <input type="text" class="form-control" id="cardholder" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Card Number</label>
                        <input type="text" class="form-control" id="cardNumber" placeholder="1234 5678 9012 3456" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="text" class="form-control" id="expiry" placeholder="MM/YY" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">CVV</label>
                            <input type="text" class="form-control" id="cvv" placeholder="123" required>
                        </div>
                    </div>
                </div>
            `;
            break;

        case 'net-banking':
            detailsHTML = `
                <div class="payment-form">
                    <h4>Net Banking</h4>
                    <div class="mb-3">
                        <label class="form-label">Select Bank</label>
                        <select class="form-control" id="bank" required>
                            <option value="">Choose your bank</option>
                            <option value="sbi">State Bank of India</option>
                            <option value="hdfc">HDFC Bank</option>
                            <option value="icici">ICICI Bank</option>
                            <option value="axis">Axis Bank</option>
                            <option value="pnb">Punjab National Bank</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">User ID</label>
                        <input type="text" class="form-control" id="userId" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" required>
                    </div>
                </div>
            `;
            break;

        case 'digital-wallet':
            detailsHTML = `
                <div class="payment-form">
                    <h4>Digital Wallet</h4>
                    <div class="mb-3">
                        <label class="form-label">Select Wallet</label>
                        <select class="form-control" id="wallet" required>
                            <option value="">Choose wallet</option>
                            <option value="paytm">Paytm</option>
                            <option value="gpay">Google Pay</option>
                            <option value="phonepe">PhonePe</option>
                            <option value="amazonpay">Amazon Pay</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mobile Number</label>
                        <input type="tel" class="form-control" id="mobile" placeholder="Enter registered mobile number" required>
                    </div>
                </div>
            `;
            break;

        case 'upi':
            detailsHTML = `
                <div class="payment-form">
                    <h4>UPI Payment</h4>
                    <div class="mb-3">
                        <label class="form-label">UPI ID</label>
                        <input type="text" class="form-control" id="upiId" placeholder="yourname@upi" required>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted">Or scan QR code:</p>
                        <div class="text-center">
                            <img src="assets/images/qr-code.png" alt="UPI QR Code" style="max-width: 150px; border: 2px solid var(--color-gold); border-radius: 8px;">
                        </div>
                    </div>
                </div>
            `;
            break;
    }

    paymentDetails.innerHTML = detailsHTML;
}

// ===== PRE-BOOKING SYSTEM =====
function initializePreBooking() {
    const wishlistButtons = document.querySelectorAll('.add-to-wishlist');

    wishlistButtons.forEach(button => {
        button.addEventListener('click', function () {
            const roomId = this.getAttribute('data-room-id');
            const roomName = this.getAttribute('data-room-name');

            addToWishlist(roomId, roomName);
            this.classList.toggle('added');

            const icon = this.querySelector('i');
            if (this.classList.contains('added')) {
                icon.className = 'fas fa-heart';
                showNotification('Added to wishlist!', 'success');
            } else {
                icon.className = 'far fa-heart';
                showNotification('Removed from wishlist', 'info');
            }
        });
    });

    // Load wishlist from localStorage
    loadWishlist();
}

function addToWishlist(roomId, roomName) {
    let wishlist = JSON.parse(localStorage.getItem('lotusWishlist')) || [];

    const existingIndex = wishlist.findIndex(item => item.id === roomId);

    if (existingIndex === -1) {
        wishlist.push({
            id: roomId,
            name: roomName,
            dateAdded: new Date().toISOString()
        });
    } else {
        wishlist.splice(existingIndex, 1);
    }

    localStorage.setItem('lotusWishlist', JSON.stringify(wishlist));
}

function loadWishlist() {
    const wishlist = JSON.parse(localStorage.getItem('lotusWishlist')) || [];

    wishlist.forEach(item => {
        const button = document.querySelector(`[data-room-id="${item.id}"]`);
        if (button) {
            button.classList.add('added');
            const icon = button.querySelector('i');
            if (icon) icon.className = 'fas fa-heart';
        }
    });
}

// ===== FORM FUNCTIONS =====
function initializeForms() {
    // Contact form
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', handleContactSubmit);
    }

    // Booking form
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', handleBookingSubmit);
    }

    // Newsletter form
    const newsletterForm = document.getElementById('newsletterForm');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', handleNewsletterSubmit);
    }

    // Payment form
    const paymentForm = document.getElementById('paymentForm');
    if (paymentForm) {
        paymentForm.addEventListener('submit', handlePaymentSubmit);
    }

    // Real-time validation
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('blur', function () {
            validateField(this);
        });
    });
}

function validateField(field) {
    const value = field.value.trim();
    const fieldName = field.name || field.id;
    let isValid = true;
    let message = '';

    // Clear previous validation
    field.classList.remove('is-invalid');
    const feedback = field.parentNode.querySelector('.invalid-feedback');
    if (feedback) feedback.remove();

    // Validation rules
    switch (fieldName) {
        case 'email':
        case 'guestEmail':
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                message = 'Please enter a valid email address';
            }
            break;

        case 'phone':
        case 'guestPhone':
            const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
            if (!phoneRegex.test(value.replace(/[\s\-\(\)]/g, ''))) {
                isValid = false;
                message = 'Please enter a valid phone number';
            }
            break;

        case 'firstName':
        case 'lastName':
        case 'guestFirstName':
        case 'guestLastName':
            if (value.length < 2) {
                isValid = false;
                message = 'Name must be at least 2 characters';
            }
            break;

        case 'checkIn':
        case 'checkOut':
            if (!value) {
                isValid = false;
                message = 'Please select a date';
            }
            break;

        case 'cardNumber':
            const cardRegex = /^\d{4}\s?\d{4}\s?\d{4}\s?\d{4}$/;
            if (!cardRegex.test(value.replace(/\s/g, ''))) {
                isValid = false;
                message = 'Please enter a valid card number';
            }
            break;

        case 'cvv':
            if (!/^\d{3,4}$/.test(value)) {
                isValid = false;
                message = 'CVV must be 3-4 digits';
            }
            break;

        case 'expiry':
            const expiryRegex = /^(0[1-9]|1[0-2])\/\d{2}$/;
            if (!expiryRegex.test(value)) {
                isValid = false;
                message = 'Please enter valid expiry date (MM/YY)';
            }
            break;
    }

    if (field.hasAttribute('required') && !value) {
        isValid = false;
        message = 'This field is required';
    }

    if (!isValid) {
        field.classList.add('is-invalid');
        const feedbackDiv = document.createElement('div');
        feedbackDiv.className = 'invalid-feedback';
        feedbackDiv.textContent = message;
        field.parentNode.appendChild(feedbackDiv);
    }

    return isValid;
}

function handleContactSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    let isValid = true;

    // Validate all fields
    form.querySelectorAll('.form-control').forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });

    if (!isValid) {
        showNotification('Please correct the errors in the form', 'error');
        return;
    }

    // Submit form
    submitContactForm(Object.fromEntries(formData));
}

async function submitBooking(data) {
    try {
        showNotification('Checking availability...', 'info');

        const response = await fetch('php/api-rooms.php?action=book', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showNotification('Booking initiated!', 'success');
            // Store booking reference for payment
            sessionStorage.setItem('pendingBooking', JSON.stringify(result.data));
            // Redirect to payment page
            setTimeout(() => {
                window.location.href = 'payment.html';
            }, 1000);
        } else {
            showNotification(result.message || 'Booking failed', 'error');
        }
    } catch (error) {
        console.error('Booking error:', error);
        showNotification('Connection error. Please try again.', 'error');
    }
}

function handleBookingSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    let isValid = true;

    // Validate all fields
    form.querySelectorAll('.form-control').forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });

    if (!isValid) {
        showNotification('Please correct the errors in the form', 'error');
        return;
    }

    // Submit booking
    submitBooking(Object.fromEntries(formData));
}

function handleNewsletterSubmit(e) {
    e.preventDefault();

    const form = e.target;
    // Check if we came from a special package
    const urlParams = new URLSearchParams(window.location.search);
    const packageType = urlParams.get('package');

    // Simulate newsletter subscription
    showNotification('Thank you for subscribing! Your exclusive offers are on the way.', 'success');

    if (packageType) {
        showNotification('Redirecting to secure payment gateway...', 'info');
        setTimeout(() => {
            // Store package info for payment page if needed
            sessionStorage.setItem('selectedPackage', packageType);
            window.location.href = 'payment.html';
        }, 2000);
    } else {
        form.reset();
    }
}

// ===== SPECIAL PACKAGE TOGGLES =====
function initializePackageToggles() {
    const packageItems = document.querySelectorAll('.package-item');

    packageItems.forEach(item => {
        const learnMoreBtn = item.querySelector('.learn-more-btn');
        if (learnMoreBtn) {
            learnMoreBtn.addEventListener('click', function () {
                item.classList.toggle('active');
                if (item.classList.contains('active')) {
                    this.textContent = 'Show Less';
                    this.classList.replace('btn-outline-gold', 'btn-gold');
                } else {
                    this.textContent = 'Learn More';
                    this.classList.replace('btn-gold', 'btn-outline-gold');
                }
            });
        }
    });
}

async function handlePaymentSubmit(e) {
    e.preventDefault();

    const form = e.target;
    let isValid = true;

    // Validate all fields
    form.querySelectorAll('.form-control').forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });

    if (!isValid) {
        showNotification('Please correct the payment details', 'error');
        return;
    }

    const pendingBooking = JSON.parse(sessionStorage.getItem('pendingBooking'));
    if (!pendingBooking) {
        showNotification('No pending booking found', 'error');
        return;
    }

    const activeMethod = document.querySelector('.payment-method.active');
    const paymentMethod = activeMethod ? activeMethod.getAttribute('data-method') : 'credit-card';

    // Collect form data
    const formData = new FormData(form);
    const paymentData = {
        booking_reference: pendingBooking.booking_reference,
        payment_method: paymentMethod,
        amount: pendingBooking.total_amount,
        ...Object.fromEntries(formData)
    };

    // Simulated Real-time Processing
    showNotification('Connecting to payment gateway...', 'info');

    // Simulate different stages of payment
    const stages = [
        'Authenticating...',
        'Securing connection...',
        'Confirming with bank...',
        'Verifying transaction...'
    ];

    let stageIdx = 0;
    const stageInterval = setInterval(() => {
        if (stageIdx < stages.length) {
            showNotification(stages[stageIdx], 'info');
            stageIdx++;
        } else {
            clearInterval(stageInterval);
            processRealPayment(paymentData);
        }
    }, 1000);
}

async function processRealPayment(data) {
    try {
        const response = await fetch('php/api-payment.php?action=process', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showNotification('Payment successful! Transaction ID: ' + result.data.transaction_id, 'success');
            sessionStorage.removeItem('pendingBooking');
            sessionStorage.setItem('lastSuccessPayment', JSON.stringify(result.data));

            setTimeout(() => {
                window.location.href = 'booking-confirmation.html';
            }, 2500);
        } else {
            showNotification(result.message || 'Payment failed', 'error');
        }
    } catch (error) {
        console.error('Payment error:', error);
        showNotification('Payment processing error. Please contact support.', 'error');
    }
}

// ===== API FUNCTIONS =====
async function submitContactForm(data) {
    try {
        const response = await fetch('php/api-contact.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showNotification('Thank you for your message! We\'ll get back to you soon.', 'success');
            document.getElementById('contactForm').reset();
        } else {
            showNotification('Failed to send message. Please try again.', 'error');
        }
    } catch (error) {
        console.error('Contact form submission error:', error);
        showNotification('Network error. Please try again later.', 'error');
    }
}

async function submitBooking(data) {
    try {
        const response = await fetch('php/api-rooms.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'book',
                ...data
            })
        });

        const result = await response.json();

        if (result.success) {
            showNotification('Booking request submitted! Redirecting to payment...', 'success');
            setTimeout(() => {
                window.location.href = 'payment.html';
            }, 2000);
        } else {
            showNotification('Booking failed. Please try again.', 'error');
        }
    } catch (error) {
        console.error('Booking submission error:', error);
        showNotification('Network error. Please try again later.', 'error');
    }
}

// ===== NOTIFICATION SYSTEM =====
function initializeNotifications() {
    // Create notification container if it doesn't exist
    if (!document.getElementById('notification-container')) {
        const container = document.createElement('div');
        container.id = 'notification-container';
        document.body.appendChild(container);
    }
}

function showNotification(message, type = 'info', duration = 5000) {
    const container = document.getElementById('notification-container');

    const notification = document.createElement('div');
    notification.className = `notification ${type}`;

    const iconClass = type === 'success' ? 'fas fa-check-circle' :
        type === 'error' ? 'fas fa-exclamation-circle' :
            'fas fa-info-circle';

    notification.innerHTML = `
        <div class="notification-content">
            <i class="${iconClass}"></i>
            <div>
                <div>${message}</div>
            </div>
        </div>
    `;

    container.appendChild(notification);

    // Trigger animation
    setTimeout(() => notification.classList.add('show'), 100);

    // Auto remove
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, duration);
}

// ===== UTILITY FUNCTIONS =====
function loadRoomsData() {
    // Simulate loading rooms data
    // In a real application, this would fetch from the API
    console.log('Loading rooms data...');
}

function viewRoomDetails(roomId) {
    // Navigate to room details page
    window.location.href = `room-details.html?id=${roomId}`;
}

// ===== LOADING SCREEN =====
function showLoading() {
    let loading = document.querySelector('.loading');
    if (!loading) {
        loading = document.createElement('div');
        loading.className = 'loading';
        loading.innerHTML = '<div class="spinner"></div>';
        document.body.appendChild(loading);
    }
    loading.classList.add('active');
}

function hideLoading() {
    const loading = document.querySelector('.loading');
    if (loading) {
        loading.classList.remove('active');
        // Optional: remove from DOM after fade out if you add transitions
        // setTimeout(() => loading.remove(), 300);
    }
}

// ===== RESPONSIVE UTILITIES =====
function isMobile() {
    return window.innerWidth <= 768;
}

function isTablet() {
    return window.innerWidth > 768 && window.innerWidth <= 1024;
}

function isDesktop() {
    return window.innerWidth > 1024;
}

// ===== ERROR HANDLING =====
window.addEventListener('error', function (e) {
    console.error('JavaScript error:', e.error);
    showNotification('An error occurred. Please refresh the page.', 'error');
});

window.addEventListener('unhandledrejection', function (e) {
    console.error('Unhandled promise rejection:', e.reason);
    showNotification('Network error occurred.', 'error');
});

// ===== PERFORMANCE OPTIMIZATION =====
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ===== ACCESSIBILITY =====
function initializeAccessibility() {
    // Add keyboard navigation support
    document.addEventListener('keydown', function (e) {
        // ESC key handling
        if (e.key === 'Escape') {
            const lightbox = document.querySelector('.lightbox.show');
            if (lightbox) {
                lightbox.classList.remove('show');
            }

            const chatbot = document.querySelector('#chatbot.show');
            if (chatbot) {
                chatbot.classList.remove('show');
            }
        }
    });

    // Focus management
    const focusableElements = document.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');

    focusableElements.forEach(element => {
        element.addEventListener('focus', function () {
            this.style.outline = '2px solid var(--color-gold)';
        });

        element.addEventListener('blur', function () {
            this.style.outline = '';
        });
    });
}

// Initialize accessibility features
initializeAccessibility();
