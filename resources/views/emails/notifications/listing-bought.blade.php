@extends('emails.notifications.layout')

@section('content')
    <div class="greeting">Hello {{ $notifiable->name }},</div>
    
    <div class="message">
        @if($role === 'buyer')
            <p>🎉 Congratulations! You have successfully purchased the following listing:</p>
        @else
            <p>🎉 Great news! Your listing has been sold:</p>
        @endif
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin: 0 0 10px 0; color: #333;">{{ $listing->title }}</h3>
            <p style="margin: 0; color: #666;">Price: <img src="http://ma3rood.datainovate.com/backend/public/images/RialSign.png" 
            alt="SAR" 
            width="14" 
            height="14" 
            style="vertical-align:middle;">{{ number_format($listing->buy_now_price, 2) }}</p>
        </div>
        
        @if($role === 'buyer')
            <p>The seller will contact you soon to arrange payment and delivery details.</p>
        @else
            <p>The buyer will contact you soon to arrange payment and delivery details.</p>
        @endif
    </div>
    
    <a href="{{ config('app.frontend_url') }}/listings/{{ $listing->slug }}" class="button">View Listing</a>
    
    <div class="message">
        <p>Thank you for using Ma3rood!</p>
        
        <p>Best regards,<br>The Ma3rood Team</p>
    </div>
@endsection

