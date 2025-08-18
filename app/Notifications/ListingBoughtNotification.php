<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ListingBoughtNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public $listing, public $role) {}

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $message = $this->role === 'buyer' ? 'You bought a listing!' : 'Your listing was purchased!';
        return (new MailMessage)
            ->subject($message)
            ->line("Listing: {$this->listing->title}")
            ->line("Price: {$this->listing->buy_now_price}")
            ->action('View Listing', url("/listing/{$this->listing->slug}"));
    }

    public function toDatabase($notifiable)
    {
        return [
            'listing_id' => $this->listing->id,
            'title' => $this->listing->title,
            'amount' => $this->listing->buy_now_price,
            'role' => $this->role
        ];
    }



    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
