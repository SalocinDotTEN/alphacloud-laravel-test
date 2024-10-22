<?php

namespace App\Events;

use App\Models\Bid;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BidSaved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The bid instance.
     *
     * @var \App\Models\Bid
     */
    public $bid;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Bid $bid)
    {
        $this->bid = $bid;
        $this->notifyUsers();
    }

    /**
     * Notify users about the bid.
     *
     * @return void
     */
    public function notifyUsers()
    {
        $latestBidPrice = $this->bid->price;

        $notifications = [];

        User::chunk(1000, function ($users) use ($latestBidPrice, &$notifications) {

            foreach ($users as $user) {
                $userLastBid = Bid::where('user_id', $user->id)->orderBy('created_at', 'desc')->first();
                $userLastBidPrice = $userLastBid ? $userLastBid->price : 0.00;

                $data = [
                    'latest_bid_price' => number_format($latestBidPrice, 2, '.', ''),
                    'user_last_bid_price' => number_format($userLastBidPrice, 2, '.', '')
                ];

                $notifications[] = [
                    'notifiable_id' => $user->id,
                    'data' => json_encode($data),
                    'latest_bid_price' => $latestBidPrice,
                    'user_last_bid_price' => $userLastBidPrice,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            Notification::insert($notifications);

            $notifications = []; //Clear out the notificiation cache.
        });
    }
}
