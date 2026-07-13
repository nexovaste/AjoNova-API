<?php

namespace App\Http\Controllers\v1\Setup;

use App\Http\Controllers\Controller;
use App\Http\Resources\Setup\PaymentChannelTypeResource;
use App\Models\Setup\PaymentChannelType;
use Illuminate\Support\Facades\Cache;


class PaymentChannelTypeController extends Controller
{
    public function index()
    {
        try {

            $cacheKey = "payment_channel_list";
            $paymentchannel = Cache::tags('payment_channel_list')->rememberForever($cacheKey, function () {
                return PaymentChannelType::orderBy('payment_channel_type_name', 'asc')->get();
            });

            return response()->json([
                'success' => true,
                'message' => 'Payment channel  fetched successfully.',
                'data' => PaymentChannelTypeResource::collection($paymentchannel)
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching payment channel: ' . $e->getMessage(),
            ], 500);
        }
    }
}
