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
        $user_id = $request->input('user_id');
        $price = $request->input('price');

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


        $highestBid = Bid::max('price');
        $validator->after(function ($validator) use ($price, $highestBid) {
            if ($highestBid !== null && $price <= $highestBid) {
                $validator->errors()->add('price', 'The bid price cannot lower than ' . $highestBid + 1);
            }
        });

        $validator->validate();

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

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
