@extends('emails.notifications.layout')

@section('content')
    <div class="greeting">Hello {{ $notifiable->name }},</div>
    
    <div class="message">
        <p>🎉 Congratulations! Your listing has been sold at auction:</p>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin: 0 0 10px 0; color: #333;">{{ $listing->title }}</h3>
            <p style="margin: 0; color: #666;">Winning bid: ${{ number_format($winningBid->amount, 2) }}</p>
            <p style="margin: 0; color: #666;">Winner: {{ $winningBid->user->name }}</p>
        </div>
        
        <p>Please contact the buyer soon to arrange payment and delivery details.</p>
    </div>
    
    <a href="{{ config('app.frontend_url') }}/listings/{{ $listing->slug }}" class="button">View Listing</a>
    
    <div class="message">
        <p>Thank you for using Ma3rood!</p>
        
        <p>Best regards,<br>The Ma3rood Team</p>
    </div>
@endsection

