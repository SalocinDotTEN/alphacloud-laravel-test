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
        $groupError = [];

        // Validate input
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'price' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/'
        ], [
            'user_id.required' => 'The user ID field is required.',
            'user_id.exists' => 'The selected user ID is invalid.',
            'price.required' => 'The bid price is required.',
            'price.numeric' => 'The bid price must be a number.',
            'price.regex' => 'The bid price must have up to 2 decimal places.'
        ]);

        if ($validator->fails()) {
            $groupError['message'] = array_merge($groupError, $validator->errors()->all());
            // return response()->json(['error' => $validator->errors()], 422);
        }

        $user_id = $request->input('user_id');
        $price = $request->input('price');

        // Check if price is the highest
        $highestBid = Bid::max('price');
        if ($highestBid !== null && $price <= $highestBid) {
            $groupError['errors']['price'] = 'The bid price cannot be lower than ' . $highestBid;
            // return response()->json([
            //     'error' => 'The bid price cannot be lower than ' . $highestBid
            // ], 422);
        }

        if (count($groupError) > 0) {
            return response()->json([
                $groupError
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
        $formatPrice = number_format($price, 2);

        return response()->json([
            'message' => 'Success',
            'bid' => [
                'full_name' => $fullName,
                'price' => $formatPrice
            ]
        ], 201);
    }
}
