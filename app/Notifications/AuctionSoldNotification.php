<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Listing;

class AuctionSoldNotification extends Notification
{
    use Queueable;

    public $listing;

    public function __construct(Listing $listing)
    {
        $this->listing = $listing;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $winningBid = $this->listing->bids()->orderByDesc('amount')->first();

        return (new MailMessage)
            ->subject('Your Listing Has Been Sold!')
            ->line("Your listing '{$this->listing->title}' has been sold.")
            ->line("Winning bid: \${$winningBid->amount} by {$winningBid->user->name}")
            ->action('View Listing', url("/listings/{$this->listing->slug}"))
            ->line('Please contact the buyer or arrange delivery.');
    }

    public function toDatabase($notifiable): array
    {
        $winningBid = $this->listing->bids()->orderByDesc('amount')->first();

        return [
            'title' => 'Your listing was sold!',
            'message' => "Listing '{$this->listing->title}' sold for \${$winningBid->amount} to {$winningBid->user->name}.",
            'listing_id' => $this->listing->id,
            'amount' => $winningBid->amount,
        ];
    }
}
