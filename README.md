# LOTUS Hotel - Premium Luxury Website

A comprehensive 5-star luxury hotel website built with modern web technologies featuring advanced 3D animations, responsive design, and complete booking management system.

## 🏨 Project Overview

**LOTUS** is a full-featured premium hotel website with:
- **9-Page Interactive UI/UX**: Home, Rooms, Booking, Payment, Gallery, Services, About, Contact, Newsletter
- **Gold/Black Luxury Design**: Slate-gray backgrounds coordinated with gold accents
- **3D Animations**: Advanced WebGL and CSS 3D animations throughout
- **Payment Integration**: Credit Card, Debit Card, Net Banking, Digital Wallets, UPI
- **Pre-Booking System**: Reserve rooms with estimated pricing
- **Responsive Design**: Mobile-first approach optimized for all devices
- **Premium Features**: Chatbot, room filtering, gallery lightbox, animations

## 🎨 Color Scheme & Design

### Primary Colors
- **Gold**: #D4AF37 (Primary accent)
- **Slate-Gray**: #2C323E (Main background)
- **Matte Black**: #1A1A1A (Dark background)
- **White**: #FFFFFF (Text/Content)

### Typography
- **Primary Font**: Playfair Display (Headings)
- **Secondary Font**: Poppins (Body text)

## 📁 Project Structure

```
HOTEL-WEBSITE/
├── index.html              # Home page (at root for easy server entry)
├── rooms.html              # Room listing
├── booking.html            # Room booking
├── payment.html            # Payment processing
├── gallery.html            # Image gallery
├── services.html           # Hotel services
├── about.html              # About LOTUS
├── contact.html            # Contact & inquiries
├── newsletter.html         # Newsletter signup
├── room-details.html       # Individual room details
├── css/
│   ├── premium.css            # Main premium styling
│   └── style.css              # Legacy styles
├── js/
│   └── scripts.js              # Enhanced JavaScript
├── php/
│   ├── config.php              # Database configuration
│   ├── api-rooms.php           # Room management API
│   ├── api-payment.php         # Payment processing API
│   └── api-contact.php         # Contact form API
├── assets/
│   └── images/                 # Hotel images & logo
└── database_setup.sql          # MySQL database setup

```

## 🚀 Installation & Setup

### Prerequisites
- PHP 7.4+
- MySQL 5.7+
- Node.js (for serving static files)
- Modern web browser

### Step 1: Database Setup

```bash
# Connect to MySQL
mysql -u root -p

# Run the database setup script
source database_setup.sql;

# Or import through command line
mysql -u root -p lotus_hotel < database_setup.sql
```

### Step 2: PHP Configuration

Edit `php/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', 'your_password');
define('DB_NAME', 'lotus_hotel');
```

### Step 3: Start Web Server

#### Using Python (Recommended for development)
```bash
cd HOTEL-WEBSITE
python -m http.server 8000
```

#### Using PHP Built-in Server
```bash
php -S localhost:8000
```

#### Access the website
```
http://localhost:8000/index.html
```

## 🎯 Core Features

### 1. **Premium UI/UX**
- Gold/black luxury color scheme
- Slate-gray backgrounds for modern look
- Smooth gradient overlays
- Glass-morphism effects
- Premium typography with Playfair Display

### 2. **3D Animations**
```css
/* Available 3D animations */
- float-3d: Floating effect
- rotateX-3d: 3D rotation
- scaleIn-3d: Scale with depth
- slideInLeft-3d: Slide from left with 3D
- slideInRight-3d: Slide from right with 3D
- glow-pulse: Golden glow effect
```

### 3. **Room Management**
- Browse rooms by type (Deluxe, Executive, Presidential)
- Real-time availability checking
- Room filtering by price and amenities
- Detailed room information with images
- Special requests option

### 4. **Booking System**
- Check-in/Check-out date selection
- Guest capacity selection
- Real-time price calculation
- Special requests field
- Instant booking confirmation

### 5. **Payment Processing**
Multiple payment methods:
- Credit Card (Visa, MasterCard, American Express)
- Debit Card
- Net Banking (Bank Transfer)
- Digital Wallets (Google Pay, Apple Pay)
- UPI (Unified Payments Interface)

### 6. **Pre-Booking / Wishlist**
- Save room preferences
- Set price alerts
- Automatic reminders
- Quick booking from wishlist

### 7. **Interactive Features**
- Real-time chatbot for inquiries
- Room filtering and search
- Gallery lightbox viewer
- Contact form with validation
- Newsletter subscription

### 8. **Responsive Design**
- Mobile-first approach
- Tablet optimization
- Desktop enhancement
- Touch-friendly interfaces
- Adaptive layouts

## 📱 Pages & Features

### 1. **Home (index.html)**
- Hero section with 3D animations
- Quick booking form
- Featured rooms showcase
- Services highlights
- Photo gallery preview
- Testimonials/Reviews
- Trust badges

### 2. **Rooms (rooms.html)**
- Room listing grid
- Filter by room type
- Filter by price range
- Sorting options
- Availability calendar
- Quick book button

### 3. **Booking (booking.html)**
- Comprehensive booking form
- Room selection
- Date picker
- Guest information
- Special requests
- Price breakdown
- Confirmation

### 4. **Payment (payment.html)**
- Multiple payment gateways
- Secure SSL checkout
- Payment method selection
- Order review
- Transaction receipt
- Booking confirmation

### 5. **Gallery (gallery.html)**
- Image grid layout
- Lightbox viewer
- Category filtering
- Zoom functionality
- Download option

### 6. **Services (services.html)**
- Spa & Wellness
- Fine Dining
- Conference Facilities
- Fitness Center
- Concierge Service
- Room Service

### 7. **About (about.html)**
- Hotel history
- Mission & vision
- Awards & recognition
- Team introduction
- Quality assurance

### 8. **Contact (contact.html)**
- Contact form
- Location map
- Phone/Email
- Operating hours
- Social media links

### 9. **Newsletter (newsletter.html)**
- Email subscription
- Promotional offers
- Travel tips
- Exclusive deals

## 🔧 API Endpoints

### Room Management
```
GET /php/api-rooms.php?action=list          # Get all rooms
GET /php/api-rooms.php?action=details&id=1  # Room details
GET /php/api-rooms.php?action=availability  # Check availability
POST /php/api-rooms.php?action=book         # Create booking
```

### Payment Processing
```
POST /php/api-payment.php?action=process    # Process payment
POST /php/api-payment.php?action=validate   # Validate payment method
GET /php/api-payment.php?action=status      # Payment status
```

### Contact & Inquiries
```
POST /php/api-contact.php?action=submit     # Submit contact form
```

## 🎨 CSS Classes & Utilities

### Premium Components
```html
<!-- Glass Effect Card -->
<div class="glass-effect">Content</div>

<!-- Premium Card -->
<div class="premium-card">
  <img class="card-image" src="image.jpg">
  <p>Description</p>
</div>

<!-- Gradient Text -->
<h1 class="gradient-text">Title</h1>

<!-- Animation on Scroll -->
<div class="animate-on-scroll">Content</div>

<!-- Gold Button -->
<button class="btn btn-gold">Book Now</button>

<!-- Outline Gold Button -->
<button class="btn btn-outline-gold">Learn More</button>
```

## 🎬 JavaScript Enhancements

### Animation Initialization
```javascript
// Initialize all animations
document.addEventListener('DOMContentLoaded', function() {
    initializeAnimations();
    initializeScroll();
    initializeForm();
    initializeChatbot();
});
```

### Custom Functions
```javascript
// Form validation
validateField(element);

// Payment processing
processPayment(data);

// Chatbot interaction
getBotResponse(message);

// Scroll animations
startScrollAnimation();
```

## 📊 Database Schema

### Key Tables
- **rooms**: Room inventory
- **bookings**: Reservation records
- **payments**: Transaction history
- **users**: Guest profiles
- **contact_inquiries**: Support tickets
- **pre_bookings**: Wishlist/pre-reservations
- **reviews**: Guest reviews
- **services**: Additional services
- **activity_logs**: System audit trail

## 🔐 Security Features

- Input validation & sanitization
- CORS headers configuration
- Prepared statements for SQL
- Password hashing
- Session management
- HTTPS recommended
- Rate limiting on APIs
- XSS prevention

## 📈 Performance Optimization

- CSS minification
- JavaScript bundling
- Image optimization
- Lazy loading
- Caching strategies
- CDN usage for libraries
- Efficient DOM queries
- Hardware acceleration

## 🧪 Testing

```bash
# Run Python tests
python py/test_website.py
python py/test_javascript_new.py

# Check all tests passed
cat json/js_test_results.json
```

## 💳 Payment Gateway Integration

### Stripe Integration
```php
require_vendor/stripe-php/init.php;
```

### Razorpay Integration
```php
// Configure Razorpay keys in config.php
define('RAZORPAY_KEY', 'your_key');
```

### PayPal Integration
```php
// Configure PayPal in API
define('PAYPAL_CLIENT_ID', 'your_id');
```

## 📱 Responsive Breakpoints

- **Mobile**: < 480px
- **Tablet**: 481px - 768px
- **Laptop**: 769px - 1024px
- **Desktop**: > 1024px

## 🌐 Deployment

### Local Development
```bash
python -m http.server 8000
```

### Production (Apache/Nginx)
1. Upload files to web server
2. Configure database connection
3. Set up SSL certificate
4. Configure payment gateway keys
5. Set appropriate file permissions
6. Enable .htaccess for URL rewriting

## 🤝 Contributing

To enhance the website:
1. Create feature branch
2. Make improvements
3. Test thoroughly
4. Submit pull request

## 📞 Support & Contact

- **Email**: info@lotushotel.com
- **Phone**: +1 234 567 890
- **Address**: 123 Luxury Ave, City, Country

## 📝 License

This project is proprietary to LOTUS Hotel. All rights reserved.

## 🎯 Future Enhancements

- [ ] Mobile app (iOS/Android)
- [ ] AI-powered recommendations
- [ ] Virtual room tours (360°)
- [ ] Advanced loyalty program
- [ ] Multi-language support
- [ ] Payment plan options
- [ ] Group booking system
- [ ] Event management
- [ ] Guest feedback system
- [ ] Integration with booking aggregators

---

**Last Updated**: March 2026
**Version**: 1.0.0
**Status**: Active Development

**Created with ❤️ by GitHub Copilot**
