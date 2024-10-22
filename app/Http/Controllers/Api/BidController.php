<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use App\Events\BidSaved;

class BidController extends Controller
{
    public function create(Request $request)
    {
        // $groupError = '';

        // Validate input
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'price' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/'
        ], [
            'user_id.required' => 'The user ID field is required.',
            'user_id.exists' => 'The selected user ID is invalid.',
            'price.required' => 'The bid price is required!',
            'price.numeric' => 'The price format is invalid.',
            'price.regex' => 'The price format is invalid.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                // 'message' => '....',
                'errors' => [
                    'price' => $validator->errors()->first()
                ]
            ], 422);
        }

        $user_id = $request->input('user_id');
        $price = $request->input('price');

        // Check if price is the highest
        $highestBid = Bid::max('price');
        if ($highestBid !== null && $price <= $highestBid) {
            return response()->json([
                // 'message' => '....',
                'errors' => [
                    'price' => 'The bid price cannot lower than ' . $highestBid
                ]
            ], 422);
        }

        // if (!empty($groupError)) {
        //     return response()->json([
        //         'message' => '....',
        //         'errors' => [
        //             'price' => $groupError
        //         ]
        //     ], 422);
        // }

        // Insert the bid
        $bid = new Bid();
        $bid->user_id = $user_id;
        $bid->price = $price;
        $bid->save();

        // Fire the event
        event(new BidSaved($bid));

        //Obtain user details
        $user = User::find($user_id);
        $fullName = $user->first_name . ' ' . $user->last_name;

        // Round price to 2 decimal.
        $formatPrice = number_format($price, 2, '.', '');

        return response()->json([
            'message' => 'Success',
            'data' => [
                'full_name' => $fullName,
                'price' => $formatPrice
            ]
        ], 201);
    }
}
