<?php

namespace App\Notifications;

use App\Models\ListingOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class OfferExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $offer;

    public function __construct(ListingOffer $offer)
    {
        $this->offer = $offer;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Offer Has Expired')
            ->line("Your offer on listing '{$this->offer->listing->title}' has expired after 24 hours.")
            ->action('View Listing', url('/listing/' . $this->offer->listing->slug))
            ->line('Feel free to place a new offer anytime.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Offer Expired',
            'message' => "Your offer on '{$this->offer->listing->title}' has expired.",
            'listing_id' => $this->offer->listing_id,
            'offer_id' => $this->offer->id,
        ];
    }
}
