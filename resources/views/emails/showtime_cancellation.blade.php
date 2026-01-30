{{--
/**
 * Showtime Cancellation Email Template
 *
 * Sent when a showtime is cancelled by the cinema including:
 * - Showtime cancellation notice
 * - Automatic booking cancellation
 * - Automatic refund information
 * - Apology and alternative options
 */
--}}
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Showtime Cancelled - TCA Cine</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        line-height: 1.6;
        color: #333;
        background-color: #f4f4f4;
    }

    .container {
        max-width: 600px;
        margin: 0 auto;
        background: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .header {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 30px 20px;
        text-align: center;
    }

    .header .icon {
        font-size: 48px;
        margin-bottom: 10px;
    }

    .header h1 {
        font-size: 24px;
        margin-bottom: 5px;
    }

    .content {
        padding: 30px 25px;
    }

    .greeting {
        font-size: 18px;
        color: #333;
        margin-bottom: 15px;
    }

    .alert-box {
        background: #fff3cd;
        border: 2px solid #ffc107;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        text-align: center;
    }

    .alert-box h2 {
        color: #856404;
        margin-bottom: 10px;
        font-size: 22px;
    }

    .alert-box p {
        color: #856404;
        font-size: 16px;
    }

    .reason-box {
        background: #f8d7da;
        border-left: 4px solid #dc3545;
        padding: 15px;
        margin: 20px 0;
        border-radius: 4px;
    }

    .reason-box strong {
        color: #721c24;
        display: block;
        margin-bottom: 5px;
    }

    .reason-box p {
        color: #721c24;
    }

    .details-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
    }

    .details-section h3 {
        color: #333;
        margin-bottom: 15px;
        font-size: 18px;
        border-bottom: 2px solid #dee2e6;
        padding-bottom: 10px;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #dee2e6;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        color: #666;
        font-weight: 500;
    }

    .detail-value {
        color: #333;
        font-weight: 600;
        text-align: right;
    }

    .refund-box {
        background: linear-gradient(135deg, #28a745 0%, #218838 100%);
        color: white;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        text-align: center;
    }

    .refund-box h3 {
        margin-bottom: 10px;
        font-size: 20px;
    }

    .refund-amount {
        font-size: 32px;
        font-weight: 700;
        margin: 10px 0;
    }

    .refund-note {
        font-size: 14px;
        opacity: 0.9;
        margin-top: 10px;
    }

    .apology-section {
        background: #e7f3ff;
        border: 1px solid #b3d9ff;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        text-align: center;
    }

    .apology-section p {
        color: #004085;
        font-size: 16px;
        line-height: 1.8;
    }

    .cta-button {
        display: inline-block;
        background: #007bff;
        color: white;
        padding: 14px 30px;
        text-decoration: none;
        border-radius: 6px;
        margin: 20px 0;
        font-weight: 600;
        transition: background 0.3s;
    }

    .cta-button:hover {
        background: #0056b3;
    }

    .contact-section {
        background: #f1f3f5;
        padding: 20px;
        border-radius: 8px;
        margin-top: 20px;
    }

    .contact-section h4 {
        color: #333;
        margin-bottom: 10px;
    }

    .contact-info {
        color: #666;
        margin: 5px 0;
    }

    .footer {
        background: #343a40;
        color: white;
        text-align: center;
        padding: 20px;
        font-size: 14px;
    }

    .footer a {
        color: #ffc107;
        text-decoration: none;
    }

    @media only screen and (max-width: 600px) {
        .container {
            margin: 0;
            border-radius: 0;
        }

        .content {
            padding: 20px 15px;
        }

        .detail-row {
            flex-direction: column;
        }

        .detail-value {
            text-align: left;
            margin-top: 5px;
        }
    }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="icon">⚠️</div>
            <h1>Showtime Cancelled</h1>
            <p>Important Notice</p>
        </div>

        <!-- Content -->
        <div class="content">
            <p class="greeting">Dear {{ $booking->user->name }},</p>

            <!-- Alert Box -->
            <div class="alert-box">
                <h2>🎬 Showtime Has Been Cancelled</h2>
                <p>We regret to inform you that your scheduled showtime has been cancelled.</p>
            </div>

            <!-- Reason -->
            <div class="reason-box">
                <strong>Cancellation Reason:</strong>
                <p>{{ $reason }}</p>
            </div>

            <p class="message">
                We sincerely apologize for this inconvenience. Your booking has been automatically cancelled 
                and a full refund has been processed.
            </p>

            <!-- Cancelled Showtime Details -->
            <div class="details-section">
                <h3>📅 Cancelled Showtime Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Movie:</span>
                    <span class="detail-value">{{ $showtime->movie->title }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Original Date:</span>
                    <span class="detail-value">{{ $showtime->show_date->format('l, F j, Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Original Time:</span>
                    <span class="detail-value">{{ $showtime->show_time }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Room:</span>
                    <span class="detail-value">{{ $showtime->room->name }} ({{ $showtime->room->screenType->name }})</span>
                </div>
            </div>

            <!-- Booking Details -->
            <div class="details-section">
                <h3>🎫 Your Booking Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Booking ID:</span>
                    <span class="detail-value">#{{ $booking->id }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Seats:</span>
                    <span class="detail-value">
                        @foreach($booking->bookingSeats as $seat)
                            {{ $seat->seat->seat_code }}@if(!$loop->last), @endif
                        @endforeach
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Seats:</span>
                    <span class="detail-value">{{ $booking->bookingSeats->count() }} seat(s)</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Original Amount:</span>
                    <span class="detail-value">{{ number_format($booking->total_price, 0) }} VND</span>
                </div>
            </div>

            @if($refundAmount > 0)
            <!-- Refund Information -->
            <div class="refund-box">
                <h3>💰 Full Refund Will Be Processed</h3>
                <div class="refund-amount">{{ number_format($refundAmount, 0) }} VND</div>
                <p class="refund-note">
                    ✅ Your full refund is being processed.<br>
                    The amount will be returned to your original payment method within 5-7 business days.<br>
                    You will receive a confirmation once the refund is complete.
                </p>
            </div>
            @endif

            <!-- Apology -->
            <div class="apology-section">
                <p>
                    <strong>We deeply apologize for this disruption to your plans.</strong><br><br>
                    We understand how disappointing this is, and we're committed to providing you 
                    with the best cinema experience. Please browse our current showtimes for alternative options.
                </p>
            </div>

            <!-- CTA Button -->
            <div style="text-align: center;">
                <a href="{{ url('/movies') }}" class="cta-button">
                    Browse Current Movies & Showtimes
                </a>
            </div>

            <!-- Contact Section -->
            <div class="contact-section">
                <h4>Need Assistance?</h4>
                <p class="contact-info">📧 Email: support@tcacine.com</p>
                <p class="contact-info">📞 Phone: 1900-xxxx (9AM - 10PM daily)</p>
                <p class="contact-info">💬 Live Chat: Available on our website</p>
            </div>

            <p style="margin-top: 20px; color: #666; font-size: 14px;">
                Thank you for your understanding.<br>
                We hope to serve you again soon!
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; {{ date('Y') }} TCA Cine. All rights reserved.</p>
            <p>
                <a href="{{ url('/') }}">Visit Our Website</a> | 
                <a href="{{ url('/promotions') }}">Current Promotions</a>
            </p>
        </div>
    </div>
</body>

</html>
