<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bookmyhotel');

function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isHotelStaff() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'hotel_staff';
}

function isMaintenanceStaff() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'maintenance_staff';
}

function isStaffOrAdmin() {
    return isAdmin() || isHotelStaff() || isMaintenanceStaff();
}

function isCustomer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}

function isMaintenanceOrAdmin() {
    return isAdmin() || isMaintenanceStaff();
}

// Language support
function getLang() {
    return $_SESSION['lang'] ?? 'en';
}

function setLang($lang) {
    $_SESSION['lang'] = $lang;
}

// ============================================
// COMPLETE TRANSLATION SYSTEM
// ============================================
function t($key, $replacements = []) {
    $lang = getLang();
    
    $translations = [
        // ========== ENGLISH ==========
        'en' => [
            // General Navigation
            'home' => 'Home',
            'hotels' => 'Hotels',
            'search' => 'Search',
            'contact' => 'Contact',
            'my_bookings' => 'My Bookings',
            'dashboard' => 'Dashboard',
            'logout' => 'Logout',
            'login' => 'Login',
            'register' => 'Register',
            'profile' => 'Profile',
            'welcome' => 'Welcome',
            'back' => 'Back',
            'all_hotels' => 'All Hotels',
            'featured_hotels' => 'Featured Hotels',
            
            // Buttons
            'book_now' => 'Book Now',
            'view_details' => 'View Details',
            'submit' => 'Submit',
            'cancel' => 'Cancel',
            'confirm' => 'Confirm',
            'update' => 'Update',
            'delete' => 'Delete',
            'edit' => 'Edit',
            'save' => 'Save',
            'apply' => 'Apply',
            'search_hotels' => 'Search Hotels',
            'view_all' => 'View All',
            'read_more' => 'Read More',
            'send_message' => 'Send Message',
            'confirm_booking' => 'Confirm Booking',
            'complete' => 'Complete',
            'generate' => 'Generate',
            
            // Status
            'status' => 'Status',
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
            'in_progress' => 'In Progress',
            'available' => 'Available',
            'sold_out' => 'Sold Out',
            'no_results' => 'No results found',
            'loading' => 'Loading...',
            'success' => 'Success',
            'error' => 'Error',
            'active' => 'Active',
            'no_action' => 'No action',
            'resolved' => 'Resolved',
            'new' => 'New',
            
            // Booking
            'booking' => 'Booking',
            'check_in' => 'Check-in',
            'check_out' => 'Check-out',
            'guests' => 'Guests',
            'guest' => 'Guest',
            'night' => 'night',
            'nights' => 'nights',
            'total' => 'Total',
            'subtotal' => 'Subtotal',
            'discount' => 'Discount',
            'promotion_code' => 'Promotion Code',
            'payment_method' => 'Payment Method',
            'cancellation_policy' => 'Cancellation Policy',
            'free_cancellation' => 'Free cancellation up to 48 hours before check-in',
            'price_per_night' => 'Price per night',
            'no_bookings' => 'You have no bookings yet.',
            'browse_hotels' => 'Browse Hotels',
            'booking_confirmation' => 'Booking Confirmation',
            'booking_created' => 'Booking created successfully! Your booking is pending confirmation.',
            'booking_confirmed_instant' => 'Booking confirmed instantly! Your room is reserved.',
            'redirecting' => 'Redirecting to',
            'price_breakdown' => 'Price Breakdown',
            'enter_promo' => 'Enter promo code (e.g. WELCOME10)',
            'promotion_applied' => 'Promotion applied!',
            'invalid_promotion' => 'Invalid or expired promotion code.',
            'processing' => 'Processing',
            'payment_processing' => 'Processing payment... Please wait.',
            'select_dates' => 'Please select check-in and check-out dates.',
            'invalid_dates' => 'Check-out must be after check-in.',
            'booking_failed' => 'Booking failed. Please try again.',
            'room_not_found' => 'Room not found.',
            'dates' => 'Dates',
            'availability_calendar' => 'Availability Calendar',
            'room_availability' => 'Room Availability',
            
            // Room Details
            'rooms' => 'Rooms',
            'room_type' => 'Room Type',
            'max_occupancy' => 'Max Occupancy',
            'facilities' => 'Facilities',
            'amenities' => 'Amenities',
            'bedding' => 'Bedding',
            'bathroom' => 'Bathroom',
            'internet' => 'Internet',
            'entertainment' => 'Entertainment',
            'services' => 'Services',
            'reviews' => 'Reviews',
            'write_review' => 'Write a Review',
            'rating' => 'Rating',
            'comment' => 'Comment',
            'be_first_review' => 'No reviews yet. Be the first to review!',
            'room_features' => 'Room Features',
            'review_submitted' => 'Review submitted successfully!',
            'review_failed' => 'Failed to submit review. Please try again.',
            'review_requires_booking' => 'You can only review hotels you have a confirmed booking with.',
            'excellent' => 'Excellent',
            'good' => 'Good',
            'average' => 'Average',
            'poor' => 'Poor',
            'terrible' => 'Terrible',
            'share_experience' => 'Share your experience...',
            
            // Admin/Staff
            'admin_panel' => 'Admin Panel',
            'staff_panel' => 'Staff Panel',
            'pending_bookings' => 'Pending Bookings',
            'confirm_booking' => 'Confirm Booking',
            'maintenance_tickets' => 'Maintenance Tickets',
            'create_ticket' => 'Create Ticket',
            'room_number' => 'Room Number',
            'issue' => 'Issue',
            'resolution' => 'Resolution',
            'total_bookings' => 'Total Bookings',
            'total_revenue' => 'Total Revenue',
            'total_hotels' => 'Total Hotels',
            'total_users' => 'Total Users',
            'room_management' => 'Room Management',
            'update_availability' => 'Update Availability',
            'low_stock_alert' => '⚠️ Low stock alert! Only ',
            'rooms_left' => ' rooms left',
            'create_new_ticket' => 'Create New Ticket',
            'select_hotel' => 'Select Hotel',
            'ticket_created' => 'Maintenance ticket created successfully!',
            'ticket_completed' => 'Ticket marked as completed!',
            'ticket_updated' => 'Ticket updated successfully!',
            'booking_confirmed' => 'Booking confirmed successfully!',
            'booking_cancelled' => 'Booking cancelled!',
            'availability_updated' => 'Room availability updated successfully!',
            'no_pending_bookings' => 'No pending bookings.',
            'no_tickets' => 'No maintenance tickets found.',
            'waiting_confirmation' => 'Waiting for staff confirmation',
            'confirm_cancel' => 'Are you sure you want to cancel this booking?',
            'booking_cancelled_refund' => 'Booking cancelled successfully. Refund will be processed within 5-7 business days.',
            'cancel_48_hours' => 'Cancellations must be made at least 48 hours before check-in.',
            'manage_customers' => 'Manage Customers',
            'customer_management' => 'Customer Management',
            'all_customers' => 'All Customers',
            'customers_found' => 'customers found',
            'customer_profile' => 'Customer Profile',
            'update_profile' => 'Update Profile',
            'reset_password' => 'Reset Password',
            'delete_account' => 'Delete Account',
            'customer_bookings' => 'Customer Bookings',
            'no_customer_bookings' => 'This customer has no bookings yet.',
            'select_customer' => 'Select a customer from the list',
            'click_to_manage' => 'Click on any customer to view and manage their account',
            'customer_updated' => 'Customer account updated successfully!',
            'password_reset' => 'Password reset successfully!',
            'customer_deleted' => 'Customer account deleted successfully!',
            'email_in_use' => 'Email already in use by another account.',
            'password_min_6' => 'Password must be at least 6 characters.',
            'enter_new_password' => 'Enter new password (min 6 chars)',
            'confirm_delete_customer' => 'Are you sure you want to permanently delete this customer account? This action cannot be undone.',
            'view_edit_customers' => 'View & edit customer accounts',
            'customer_tickets' => 'Customer Tickets',
            
            // Hotel Management
            'hotel_management' => 'Hotel Management',
            'hotel_added' => 'Hotel added successfully!',
            'hotel_updated' => 'Hotel updated successfully!',
            'hotel_deleted' => 'Hotel deleted successfully!',
            'hotel_add_failed' => 'Failed to add hotel. Please try again.',
            'add_new_hotel' => 'Add New Hotel',
            'edit_hotel' => 'Edit Hotel',
            'hotel_name' => 'Hotel Name',
            'hotels_found' => 'hotels found',
            'no_hotels' => 'No hotels found.',
            'delete_failed' => 'Delete failed. Please try again.',
            'confirm_delete_hotel' => 'Are you sure you want to delete this hotel? This will also delete all associated rooms and bookings.',
            'comma_separated' => 'Comma separated values',
            'image_url' => 'Image URL',
            
            // Promotions
            'promotion_management' => 'Promotion Management',
            'generate_promo_code' => 'Generate Promo Code',
            'promo_created' => 'Promotion code created successfully!',
            'promo_deleted' => 'Promotion code deleted successfully!',
            'promo_create_failed' => 'Failed to create promotion. Please try again.',
            'invalid_promo_data' => 'Please enter a valid code and discount percentage.',
            'active_promotions' => 'Active Promotions',
            'valid' => 'Valid',
            'used' => 'Used',
            'no_promotions' => 'No promotions created yet.',
            'confirm_delete_promo' => 'Are you sure you want to delete this promotion code?',
            'discount_percent' => 'Discount Percent',
            'usage_limit' => 'Usage Limit',
            'promo_description' => 'Promotion description',
            
            // Profile
            'my_profile' => 'My Profile',
            'full_name' => 'Full Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'address' => 'Address',
            'change_password' => 'Change Password',
            'current_password' => 'Current Password',
            'new_password' => 'New Password',
            'confirm_new_password' => 'Confirm New Password',
            'keep_blank' => 'Leave blank to keep current password',
            'profile_updated' => 'Profile updated successfully!',
            'email_cannot_change' => 'Email cannot be changed',
            'password_mismatch' => 'New password must be at least 6 characters and match confirmation.',
            'current_password_incorrect' => 'Current password is incorrect.',
            'update_failed' => 'Update failed. Please try again.',
            'enter_address' => 'Enter your address',
            
            // Register
            'create_account' => 'Create Account',
            'already_have_account' => 'Already have an account?',
            'login_here' => 'Login here',
            'dont_have_account' => 'Don\'t have an account?',
            'register_here' => 'Register here',
            'password_min' => 'Minimum 6 characters',
            'role' => 'Role',
            'customer' => 'Customer',
            'admin' => 'Admin',
            'hotel_staff' => 'Hotel Staff',
            'maintenance_staff' => 'Maintenance Staff',
            'email_exists' => 'Email already registered. Please login.',
            'registration_failed' => 'Registration failed. Please try again.',
            'staff_account_created' => 'Staff account created successfully!',
            'fill_all_fields' => 'Please fill in all required fields.',
            'valid_email' => 'Please enter a valid email address.',
            
            // Login
            'welcome_back' => 'Welcome back!',
            'login_to_manage' => 'Login to manage your bookings',
            'invalid_credentials' => 'Invalid email or password.',
            'account_not_found' => 'Account not found. Please register or check your email.',
            'enter_password' => 'Enter your password',
            'not_specified' => 'Not specified',
            
            // Contact / Tickets
            'contact_us' => 'Contact Us',
            'have_questions' => 'Have questions? We\'d love to hear from you.',
            'your_name' => 'Your Name',
            'your_email' => 'Your Email',
            'your_message' => 'Your Message',
            'thank_you_message' => 'Thank you for your message! We will get back to you soon.',
            'subject' => 'Subject',
            'enter_subject' => 'Enter subject',
            'describe_issue' => 'Describe your issue in detail',
            'ticket_submitted' => 'Your message has been sent. We will get back to you soon!',
            'ticket_submit_failed' => 'Failed to send message. Please try again.',
            'from' => 'From',
            'message' => 'Message',
            'created' => 'Created',
            'created_by' => 'Created by',
            'resolved_by' => 'Resolved by',
            'resolution_notes' => 'Resolution notes',
            'no_tickets' => 'No tickets found.',
            
            // Hotel Listing
            'choose_hotel' => 'Choose from our premium selection of hotels across Asia and Europe.',
            'from' => 'From',
            'per_night' => '/night',
            'star_rating' => 'Star Rating',
            'location' => 'Location',
            'description' => 'Description',
            'no_hotels_found' => 'No hotels found matching your criteria.',
            'showing_results' => 'Showing results for',
            'search_results' => 'Search Results',
            'city_hotel' => 'City or hotel name',
            
            // Payment
            'payment' => 'Payment',
            'payment_methods' => 'Payment Methods',
            'credit_card' => 'Credit Card',
            'debit_card' => 'Debit Card',
            'paypal' => 'PayPal',
            'apple_pay' => 'Apple Pay',
            'google_pay' => 'Google Pay',
            'bank_transfer' => 'Bank Transfer',
            'payment_illustration' => 'This is an illustration. No real payment will be processed.',
            'payment_status' => 'Payment Status',
            'paid' => 'Paid',
            'refunded' => 'Refunded',
            'pending_payment' => 'Pending',
            
            // Refund
            'refund_info' => 'Refund will be processed within 5-7 business days.',
            'no_payment_processed' => 'No payment was processed for this booking.',
            
            // Sustainability
            'sustainable_practices' => 'This hotel supports sustainable practices and eco-friendly initiatives.',
            'eco_friendly' => 'Eco-Friendly',
            
            // Errors
            'please_login' => 'Please login first.',
            'unauthorized' => 'Unauthorized access.',
            
            // Promo Messages
            'best_price_guarantee' => 'Best Price Guarantee',
            'support_24_7' => '24/7 Support',
            
            // Action
            'action' => 'Action',
            'hotel' => 'Hotel',
            'all_hotels' => 'All Hotels',
        ],
        
        // ========== SPANISH ==========
        'es' => [
            // General Navigation
            'home' => 'Inicio',
            'hotels' => 'Hoteles',
            'search' => 'Buscar',
            'contact' => 'Contacto',
            'my_bookings' => 'Mis Reservas',
            'dashboard' => 'Panel',
            'logout' => 'Cerrar Sesión',
            'login' => 'Iniciar Sesión',
            'register' => 'Registrarse',
            'profile' => 'Perfil',
            'welcome' => 'Bienvenido',
            'back' => 'Volver',
            'all_hotels' => 'Todos los Hoteles',
            'featured_hotels' => 'Hoteles Destacados',
            
            // Buttons
            'book_now' => 'Reservar Ahora',
            'view_details' => 'Ver Detalles',
            'submit' => 'Enviar',
            'cancel' => 'Cancelar',
            'confirm' => 'Confirmar',
            'update' => 'Actualizar',
            'delete' => 'Eliminar',
            'edit' => 'Editar',
            'save' => 'Guardar',
            'apply' => 'Aplicar',
            'search_hotels' => 'Buscar Hoteles',
            'view_all' => 'Ver Todos',
            'read_more' => 'Leer Más',
            'send_message' => 'Enviar Mensaje',
            'confirm_booking' => 'Confirmar Reserva',
            'complete' => 'Completar',
            'generate' => 'Generar',
            
            // Status
            'status' => 'Estado',
            'pending' => 'Pendiente',
            'confirmed' => 'Confirmado',
            'cancelled' => 'Cancelado',
            'completed' => 'Completado',
            'in_progress' => 'En Progreso',
            'available' => 'Disponible',
            'sold_out' => 'Agotado',
            'no_results' => 'No se encontraron resultados',
            'loading' => 'Cargando...',
            'success' => 'Éxito',
            'error' => 'Error',
            'active' => 'Activo',
            'no_action' => 'Sin acción',
            'resolved' => 'Resuelto',
            'new' => 'Nuevo',
            
            // Booking
            'booking' => 'Reserva',
            'check_in' => 'Entrada',
            'check_out' => 'Salida',
            'guests' => 'Huéspedes',
            'guest' => 'Huésped',
            'night' => 'noche',
            'nights' => 'noches',
            'total' => 'Total',
            'subtotal' => 'Subtotal',
            'discount' => 'Descuento',
            'promotion_code' => 'Código de Promoción',
            'payment_method' => 'Método de Pago',
            'cancellation_policy' => 'Política de Cancelación',
            'free_cancellation' => 'Cancelación gratuita hasta 48 horas antes del check-in',
            'price_per_night' => 'Precio por noche',
            'no_bookings' => 'No tienes reservas aún.',
            'browse_hotels' => 'Explorar Hoteles',
            'booking_confirmation' => 'Confirmación de Reserva',
            'booking_created' => '¡Reserva creada exitosamente! Tu reserva está pendiente de confirmación.',
            'booking_confirmed_instant' => '¡Reserva confirmada instantáneamente! Tu habitación está reservada.',
            'redirecting' => 'Redirigiendo a',
            'price_breakdown' => 'Desglose de Precios',
            'enter_promo' => 'Ingresa código de promoción (ej. WELCOME10)',
            'promotion_applied' => '¡Promoción aplicada!',
            'invalid_promotion' => 'Código de promoción inválido o expirado.',
            'processing' => 'Procesando',
            'payment_processing' => 'Procesando pago... Por favor espera.',
            'select_dates' => 'Por favor, selecciona las fechas de entrada y salida.',
            'invalid_dates' => 'La fecha de salida debe ser después de la fecha de entrada.',
            'booking_failed' => 'La reserva falló. Por favor, intenta de nuevo.',
            'room_not_found' => 'Habitación no encontrada.',
            'dates' => 'Fechas',
            'availability_calendar' => 'Calendario de Disponibilidad',
            'room_availability' => 'Disponibilidad de Habitaciones',
            
            // Room Details
            'rooms' => 'Habitaciones',
            'room_type' => 'Tipo de Habitación',
            'max_occupancy' => 'Ocupación Máxima',
            'facilities' => 'Instalaciones',
            'amenities' => 'Servicios',
            'bedding' => 'Ropa de Cama',
            'bathroom' => 'Baño',
            'internet' => 'Internet',
            'entertainment' => 'Entretenimiento',
            'services' => 'Servicios',
            'reviews' => 'Reseñas',
            'write_review' => 'Escribir una Reseña',
            'rating' => 'Calificación',
            'comment' => 'Comentario',
            'be_first_review' => 'No hay reseñas aún. ¡Sé el primero en reseñar!',
            'room_features' => 'Características de la Habitación',
            'review_submitted' => '¡Reseña enviada exitosamente!',
            'review_failed' => 'Error al enviar la reseña. Por favor, intenta de nuevo.',
            'review_requires_booking' => 'Solo puedes reseñar hoteles con los que tengas una reserva confirmada.',
            'excellent' => 'Excelente',
            'good' => 'Bueno',
            'average' => 'Regular',
            'poor' => 'Malo',
            'terrible' => 'Terrible',
            'share_experience' => 'Comparte tu experiencia...',
            
            // Admin/Staff
            'admin_panel' => 'Panel de Administración',
            'staff_panel' => 'Panel de Personal',
            'pending_bookings' => 'Reservas Pendientes',
            'confirm_booking' => 'Confirmar Reserva',
            'maintenance_tickets' => 'Tickets de Mantenimiento',
            'create_ticket' => 'Crear Ticket',
            'room_number' => 'Número de Habitación',
            'issue' => 'Problema',
            'resolution' => 'Solución',
            'total_bookings' => 'Total de Reservas',
            'total_revenue' => 'Ingresos Totales',
            'total_hotels' => 'Total de Hoteles',
            'total_users' => 'Total de Usuarios',
            'room_management' => 'Gestión de Habitaciones',
            'update_availability' => 'Actualizar Disponibilidad',
            'low_stock_alert' => '⚠️ ¡Alerta de stock bajo! Solo quedan ',
            'rooms_left' => ' habitaciones',
            'create_new_ticket' => 'Crear Nuevo Ticket',
            'select_hotel' => 'Seleccionar Hotel',
            'ticket_created' => '¡Ticket de mantenimiento creado exitosamente!',
            'ticket_completed' => '¡Ticket marcado como completado!',
            'ticket_updated' => '¡Ticket actualizado exitosamente!',
            'booking_confirmed' => '¡Reserva confirmada exitosamente!',
            'booking_cancelled' => '¡Reserva cancelada!',
            'availability_updated' => '¡Disponibilidad de habitación actualizada exitosamente!',
            'no_pending_bookings' => 'No hay reservas pendientes.',
            'no_tickets' => 'No se encontraron tickets de mantenimiento.',
            'waiting_confirmation' => 'Esperando confirmación del personal',
            'confirm_cancel' => '¿Estás seguro de que quieres cancelar esta reserva?',
            'booking_cancelled_refund' => 'Reserva cancelada exitosamente. El reembolso se procesará en 5-7 días hábiles.',
            'cancel_48_hours' => 'Las cancelaciones deben realizarse al menos 48 horas antes del check-in.',
            'manage_customers' => 'Gestionar Clientes',
            'customer_management' => 'Gestión de Clientes',
            'all_customers' => 'Todos los Clientes',
            'customers_found' => 'clientes encontrados',
            'customer_profile' => 'Perfil del Cliente',
            'update_profile' => 'Actualizar Perfil',
            'reset_password' => 'Restablecer Contraseña',
            'delete_account' => 'Eliminar Cuenta',
            'customer_bookings' => 'Reservas del Cliente',
            'no_customer_bookings' => 'Este cliente no tiene reservas aún.',
            'select_customer' => 'Selecciona un cliente de la lista',
            'click_to_manage' => 'Haz clic en cualquier cliente para ver y gestionar su cuenta',
            'customer_updated' => '¡Cuenta de cliente actualizada exitosamente!',
            'password_reset' => '¡Contraseña restablecida exitosamente!',
            'customer_deleted' => '¡Cuenta de cliente eliminada exitosamente!',
            'email_in_use' => 'El correo ya está en uso por otra cuenta.',
            'password_min_6' => 'La contraseña debe tener al menos 6 caracteres.',
            'enter_new_password' => 'Ingresa nueva contraseña (mín 6 caracteres)',
            'confirm_delete_customer' => '¿Estás seguro de que quieres eliminar permanentemente esta cuenta de cliente? Esta acción no se puede deshacer.',
            'view_edit_customers' => 'Ver y editar cuentas de clientes',
            'customer_tickets' => 'Tickets de Clientes',
            
            // Hotel Management
            'hotel_management' => 'Gestión de Hoteles',
            'hotel_added' => '¡Hotel agregado exitosamente!',
            'hotel_updated' => '¡Hotel actualizado exitosamente!',
            'hotel_deleted' => '¡Hotel eliminado exitosamente!',
            'hotel_add_failed' => 'Error al agregar el hotel. Por favor, intenta de nuevo.',
            'add_new_hotel' => 'Agregar Nuevo Hotel',
            'edit_hotel' => 'Editar Hotel',
            'hotel_name' => 'Nombre del Hotel',
            'hotels_found' => 'hoteles encontrados',
            'no_hotels' => 'No se encontraron hoteles.',
            'delete_failed' => 'Error al eliminar. Por favor, intenta de nuevo.',
            'confirm_delete_hotel' => '¿Estás seguro de que quieres eliminar este hotel? Esto también eliminará todas las habitaciones y reservas asociadas.',
            'comma_separated' => 'Valores separados por comas',
            'image_url' => 'URL de la Imagen',
            
            // Promotions
            'promotion_management' => 'Gestión de Promociones',
            'generate_promo_code' => 'Generar Código de Promoción',
            'promo_created' => '¡Código de promoción creado exitosamente!',
            'promo_deleted' => '¡Código de promoción eliminado exitosamente!',
            'promo_create_failed' => 'Error al crear la promoción. Por favor, intenta de nuevo.',
            'invalid_promo_data' => 'Por favor, ingresa un código y porcentaje de descuento válidos.',
            'active_promotions' => 'Promociones Activas',
            'valid' => 'Válido',
            'used' => 'Usado',
            'no_promotions' => 'No se han creado promociones aún.',
            'confirm_delete_promo' => '¿Estás seguro de que quieres eliminar este código de promoción?',
            'discount_percent' => 'Porcentaje de Descuento',
            'usage_limit' => 'Límite de Uso',
            'promo_description' => 'Descripción de la promoción',
            
            // Profile
            'my_profile' => 'Mi Perfil',
            'full_name' => 'Nombre Completo',
            'email' => 'Correo Electrónico',
            'phone' => 'Teléfono',
            'address' => 'Dirección',
            'change_password' => 'Cambiar Contraseña',
            'current_password' => 'Contraseña Actual',
            'new_password' => 'Nueva Contraseña',
            'confirm_new_password' => 'Confirmar Nueva Contraseña',
            'keep_blank' => 'Dejar en blanco para mantener la contraseña actual',
            'profile_updated' => '¡Perfil actualizado exitosamente!',
            'email_cannot_change' => 'El correo electrónico no se puede cambiar',
            'password_mismatch' => 'La nueva contraseña debe tener al menos 6 caracteres y coincidir con la confirmación.',
            'current_password_incorrect' => 'La contraseña actual es incorrecta.',
            'update_failed' => 'La actualización falló. Por favor, intenta de nuevo.',
            'enter_address' => 'Ingresa tu dirección',
            
            // Register
            'create_account' => 'Crear Cuenta',
            'already_have_account' => '¿Ya tienes una cuenta?',
            'login_here' => 'Inicia sesión aquí',
            'dont_have_account' => '¿No tienes una cuenta?',
            'register_here' => 'Regístrate aquí',
            'password_min' => 'Mínimo 6 caracteres',
            'role' => 'Rol',
            'customer' => 'Cliente',
            'admin' => 'Administrador',
            'hotel_staff' => 'Personal del Hotel',
            'maintenance_staff' => 'Personal de Mantenimiento',
            'email_exists' => 'El correo ya está registrado. Por favor, inicia sesión.',
            'registration_failed' => 'El registro falló. Por favor, intenta de nuevo.',
            'staff_account_created' => '¡Cuenta de personal creada exitosamente!',
            'fill_all_fields' => 'Por favor, completa todos los campos requeridos.',
            'valid_email' => 'Por favor, ingresa un correo electrónico válido.',
            
            // Login
            'welcome_back' => '¡Bienvenido de nuevo!',
            'login_to_manage' => 'Inicia sesión para gestionar tus reservas',
            'invalid_credentials' => 'Correo o contraseña inválidos.',
            'account_not_found' => 'Cuenta no encontrada. Regístrate o verifica tu correo.',
            'enter_password' => 'Ingresa tu contraseña',
            'not_specified' => 'No especificado',
            
            // Contact / Tickets
            'contact_us' => 'Contáctanos',
            'have_questions' => '¿Tienes preguntas? Nos encantaría saber de ti.',
            'your_name' => 'Tu Nombre',
            'your_email' => 'Tu Correo',
            'your_message' => 'Tu Mensaje',
            'thank_you_message' => '¡Gracias por tu mensaje! Te responderemos pronto.',
            'subject' => 'Asunto',
            'enter_subject' => 'Ingresa el asunto',
            'describe_issue' => 'Describe tu problema en detalle',
            'ticket_submitted' => '¡Tu mensaje ha sido enviado! Te responderemos pronto.',
            'ticket_submit_failed' => 'Error al enviar el mensaje. Por favor, intenta de nuevo.',
            'from' => 'De',
            'message' => 'Mensaje',
            'created' => 'Creado',
            'created_by' => 'Creado por',
            'resolved_by' => 'Resuelto por',
            'resolution_notes' => 'Notas de resolución',
            'no_tickets' => 'No se encontraron tickets.',
            
            // Hotel Listing
            'choose_hotel' => 'Elige de nuestra selección premium de hoteles en Asia y Europa.',
            'from' => 'Desde',
            'per_night' => '/noche',
            'star_rating' => 'Calificación por Estrellas',
            'location' => 'Ubicación',
            'description' => 'Descripción',
            'no_hotels_found' => 'No se encontraron hoteles que coincidan con tus criterios.',
            'showing_results' => 'Mostrando resultados para',
            'search_results' => 'Resultados de Búsqueda',
            'city_hotel' => 'Ciudad o nombre del hotel',
            
            // Payment
            'payment' => 'Pago',
            'payment_methods' => 'Métodos de Pago',
            'credit_card' => 'Tarjeta de Crédito',
            'debit_card' => 'Tarjeta de Débito',
            'paypal' => 'PayPal',
            'apple_pay' => 'Apple Pay',
            'google_pay' => 'Google Pay',
            'bank_transfer' => 'Transferencia Bancaria',
            'payment_illustration' => 'Esto es una ilustración. No se procesará ningún pago real.',
            'payment_status' => 'Estado del Pago',
            'paid' => 'Pagado',
            'refunded' => 'Reembolsado',
            'pending_payment' => 'Pendiente',
            
            // Refund
            'refund_info' => 'El reembolso se procesará en 5-7 días hábiles.',
            'no_payment_processed' => 'No se procesó ningún pago para esta reserva.',
            
            // Sustainability
            'sustainable_practices' => 'Este hotel apoya prácticas sostenibles e iniciativas ecológicas.',
            'eco_friendly' => 'Ecológico',
            
            // Errors
            'please_login' => 'Por favor, inicia sesión primero.',
            'unauthorized' => 'Acceso no autorizado.',
            
            // Promo Messages
            'best_price_guarantee' => 'Garantía de Mejor Precio',
            'support_24_7' => 'Soporte 24/7',
            
            // Action
            'action' => 'Acción',
            'hotel' => 'Hotel',
            'all_hotels' => 'Todos los Hoteles',
        ]
    ];
    
    // Get translation or fallback to key
    $text = $translations[$lang][$key] ?? $translations['en'][$key] ?? $key;
    
    // Apply replacements if any
    if (!empty($replacements)) {
        foreach ($replacements as $search => $replace) {
            $text = str_replace('{' . $search . '}', $replace, $text);
        }
    }
    
    return $text;
}

// Function to validate promotion code
function validatePromotion($conn, $code, $hotel_id = null) {
    $sql = "SELECT * FROM promotions WHERE code = ? AND valid_from <= CURDATE() AND valid_to >= CURDATE()";
    if ($hotel_id) {
        $sql .= " AND hotel_id = ?";
    }
    $stmt = $conn->prepare($sql);
    if ($hotel_id) {
        $stmt->bind_param("si", $code, $hotel_id);
    } else {
        $stmt->bind_param("s", $code);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to send email notification (simulated)
function sendNotification($email, $subject, $message) {
    error_log("Notification to $email: $subject - $message");
    return true;
}

// Function to check low room availability
function checkRoomAvailability($conn, $room_id) {
    $stmt = $conn->prepare("SELECT available_rooms, total_rooms, room_type FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $room = $result->fetch_assoc();
    
    if ($room) {
        $percentage = ($room['available_rooms'] / $room['total_rooms']) * 100;
        if ($percentage <= 20) {
            return ['alert' => true, 'message' => t('low_stock_alert') . $room['available_rooms'] . t('rooms_left'), 'available' => $room['available_rooms']];
        }
    }
    return ['alert' => false];
}

// Function to check room availability for a date range
function checkDateRangeAvailability($conn, $room_id, $check_in, $check_out) {
    $start = new DateTime($check_in);
    $end = new DateTime($check_out);
    $interval = DateInterval::createFromDateString('1 day');
    $period = new DatePeriod($start, $interval, $end);
    
    $max_needed = 0;
    $available = true;
    $message = '';
    
    foreach ($period as $date) {
        $date_str = $date->format('Y-m-d');
        $stmt = $conn->prepare("SELECT available_rooms FROM room_availability_log WHERE room_id = ? AND date = ?");
        $stmt->bind_param("is", $room_id, $date_str);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $avail = $row['available_rooms'];
            if ($avail <= 0) {
                $available = false;
                $message = "No rooms available on " . $date->format('M d, Y');
                break;
            }
            if ($avail < $max_needed || $max_needed == 0) {
                $max_needed = $avail;
            }
        } else {
            // If no record exists, check the room's total availability
            $stmt2 = $conn->prepare("SELECT available_rooms FROM rooms WHERE id = ?");
            $stmt2->bind_param("i", $room_id);
            $stmt2->execute();
            $room = $stmt2->get_result()->fetch_assoc();
            if ($room['available_rooms'] <= 0) {
                $available = false;
                $message = "No rooms available on " . $date->format('M d, Y');
                break;
            }
        }
    }
    
    return ['available' => $available, 'message' => $message, 'max_rooms' => $max_needed];
}

// Function to update room availability for a date range
function updateRoomAvailabilityForDates($conn, $room_id, $check_in, $check_out) {
    $start = new DateTime($check_in);
    $end = new DateTime($check_out);
    $interval = DateInterval::createFromDateString('1 day');
    $period = new DatePeriod($start, $interval, $end);
    
    foreach ($period as $date) {
        $date_str = $date->format('Y-m-d');
        // Check if record exists
        $stmt = $conn->prepare("SELECT id, available_rooms FROM room_availability_log WHERE room_id = ? AND date = ?");
        $stmt->bind_param("is", $room_id, $date_str);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $new_avail = $row['available_rooms'] - 1;
            $stmt2 = $conn->prepare("UPDATE room_availability_log SET available_rooms = ?, is_available = ? WHERE id = ?");
            $is_available = $new_avail > 0;
            $stmt2->bind_param("iii", $new_avail, $is_available, $row['id']);
            $stmt2->execute();
        } else {
            // Create new record
            $stmt2 = $conn->prepare("INSERT INTO room_availability_log (room_id, date, available_rooms, is_available) VALUES (?, ?, ?, ?)");
            $avail = 1;
            $is_available = true;
            $stmt2->bind_param("isii", $room_id, $date_str, $avail, $is_available);
            $stmt2->execute();
        }
    }
}

// Function to check if user has confirmed booking for hotel
function hasConfirmedBooking($conn, $user_id, $hotel_id) {
    $stmt = $conn->prepare("SELECT b.id FROM bookings b 
                           JOIN rooms r ON b.room_id = r.id 
                           WHERE b.user_id = ? AND r.hotel_id = ? 
                           AND b.status = 'confirmed' 
                           LIMIT 1");
    $stmt->bind_param("ii", $user_id, $hotel_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

// ============================================
// NOTIFICATION FUNCTIONS
// ============================================

// Create notification
function createNotification($conn, $user_id, $type, $message, $link_type = null, $link_id = null) {
    $title = getNotificationTitle($type);
    $link = $link_type ? $link_type . '.php?id=' . $link_id : null;
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $type, $title, $message, $link);
    return $stmt->execute();
}

// Get notification title
function getNotificationTitle($type) {
    $titles = [
        'booking_confirmed' => 'Booking Confirmed',
        'booking_cancelled' => 'Booking Cancelled',
        'booking_refunded' => 'Booking Refunded',
        'new_booking' => 'New Booking',
        'refund_processed' => 'Refund Processed',
        'ticket_created' => 'Maintenance Ticket Created',
        'ticket_completed' => 'Maintenance Ticket Completed'
    ];
    return $titles[$type] ?? 'Notification';
}

// Get unread notifications count
function getUnreadNotificationsCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['count'];
}

// Mark notification as read
function markNotificationRead($conn, $notification_id, $user_id) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $user_id);
    return $stmt->execute();
}

// Get user notifications
function getUserNotifications($conn, $user_id, $limit = 20) {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

?>