<?php

namespace App\Http\Controllers\v1\Setup;

use App\Http\Controllers\Controller;
use App\Http\Resources\Setup\StateResource;
use App\Models\Setup\SetupState;
use Illuminate\Http\Request;

class StateController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'country_id' => 'required|exists:setup_countries,country_id',
        ]);

        try {
            $countryId = $request->country_id;

            $states = SetupState::with('country')
                ->where('country_id', $countryId)
                ->orderBy('state_name', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'States fetched successfully.',
                'data' => StateResource::collection($states),
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching states: ' . $e->getMessage(),
            ], 500);
        }
    }
}