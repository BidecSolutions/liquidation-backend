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
        $subject = $this->role === 'buyer' ? 'Listing Purchased - Ma3rood' : 'Your Listing Was Sold - Ma3rood';
        
        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notifications.listing-bought', [
                'notifiable' => $notifiable,
                'listing' => $this->listing,
                'role' => $this->role,
                'subject' => $subject
            ])
            ->greeting('') // Remove default greeting
            ->salutation(''); // Remove default salutation
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
