@extends('emails.notifications.layout')

@section('content')
    <div class="greeting">Hello {{ $notifiable->name }},</div>
    
    <div class="message">
        <p>Your offer has expired for the following listing:</p>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin: 0 0 10px 0; color: #333;">{{ $offer->listing->title }}</h3>
            <p style="margin: 0; color: #666;">Expired offer: <span class="currency">$</span>{{ number_format($offer->amount, 2) }}</p>
        </div>
        
        <p>Don't worry! You can always place a new offer on this listing if it's still available.</p>
    </div>
    
    <a href="{{ config('app.frontend_url') }}/listings/{{ $offer->listing->slug }}" class="button">Place New Offer</a>
    
    <div class="message">
        <p>Thank you for using Ma3rood!</p>
        
        <p>Best regards,<br>The Ma3rood Team</p>
    </div>
@endsection

