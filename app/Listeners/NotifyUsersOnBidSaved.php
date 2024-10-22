<?php

namespace App\Listeners;

use App\Events\BidSaved;
use App\Models\Bid;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyUsersOnBidSaved implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param \App\Events\BidSaved  $event
     * @return void
     */
    public function handle(BidSaved $event)
    {
        $bid = $event->bid;
        $latestBidPrice = $bid->price;

        // Get all users
        $users = User::all();

        foreach ($users as $user) {
            // Get the user's last bid price
            $userLastBid = Bid::where('user_id', $user->id)->orderBy('created_at', 'desc')->first();
            $userLastBidPrice = $userLastBid ? $userLastBid->price : 0.00;

            // Create the notification data
            $data = [
                'latest_bid_price' => number_format($latestBidPrice, 2, '.', ''),
                'user_last_bid_price' => number_format($userLastBidPrice, 2, '.', '')
            ];

            // Create the notification
            Notification::create([
                'notifiable_id' => $user->id,
                'data' => json_encode($data),
                'latest_bid_price' => $latestBidPrice,
                'user_last_bid_price' => $userLastBidPrice
            ]);
        }
    }
}
