// ============================================
// FORM VALIDATION
// ============================================
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#e74c3c';
            isValid = false;
        } else {
            input.style.borderColor = '#27ae60';
        }
    });
    
    return isValid;
}

// ============================================
// DATE VALIDATION
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const checkIn = document.getElementById('check_in');
    const checkOut = document.getElementById('check_out');
    
    if (checkIn && checkOut) {
        const today = new Date().toISOString().split('T')[0];
        checkIn.setAttribute('min', today);
        checkOut.setAttribute('min', today);
        
        checkIn.addEventListener('change', function() {
            checkOut.setAttribute('min', this.value);
            if (checkOut.value && checkOut.value < this.value) {
                checkOut.value = this.value;
            }
        });
    }
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});

// ============================================
// PRICE FORMATTING
// ============================================
function formatPrice(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

function calculateTotal(pricePerNight, checkIn, checkOut) {
    if (!checkIn || !checkOut) return 0;
    const start = new Date(checkIn);
    const end = new Date(checkOut);
    const nights = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
    return nights * pricePerNight;
}

// ============================================
// PASSWORD TOGGLE
// ============================================
function togglePassword(fieldId, iconId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(iconId);
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// ============================================
// API CALLS
// ============================================
async function apiCall(endpoint, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        }
    };
    
    if (data) {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(endpoint, options);
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, message: 'Network error occurred' };
    }
}

// ============================================
// LOAD HOTELS VIA API (example usage)
// ============================================
async function loadHotels(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const result = await apiCall('api/get_hotels.php');
    
    if (result.success) {
        container.innerHTML = result.data.map(hotel => `
            <div class="card">
                <div class="card-body">
                    <h3>${hotel.name}</h3>
                    <p>${'★'.repeat(hotel.star_rating)}</p>
                    <p><i class="fas fa-map-marker-alt"></i> ${hotel.location}</p>
                    <p>${hotel.description.substring(0, 100)}...</p>
                    <a href="hotel-detail.php?id=${hotel.id}" class="btn btn-primary">View Details</a>
                </div>
            </div>
        `).join('');
    }
}

// ============================================
// BOOKING FORM SUBMIT (AJAX)
// ============================================
async function submitBooking(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    const data = {
        room_id: formData.get('room_id'),
        check_in: formData.get('check_in'),
        check_out: formData.get('check_out')
    };
    
    const result = await apiCall('api/make_booking.php', 'POST', data);
    
    const messageDiv = document.getElementById('booking-message');
    if (result.success) {
        messageDiv.innerHTML = `<div class="alert alert-success">${result.message}</div>`;
        setTimeout(() => window.location.href = 'my-bookings.php', 2000);
    } else {
        messageDiv.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
    }
}

// ============================================
// MOBILE NAV TOGGLE
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.querySelector('.nav-toggle');
    const navLinks = document.querySelector('.nav-links');
    
    if (toggle && navLinks) {
        toggle.addEventListener('click', function() {
            navLinks.classList.toggle('active');
        });
    }
});