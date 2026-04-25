<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoanRepaymentScheduleController extends Controller
{
    // Display a listing of the resource.
    public function index(Request $request)
    {
        try {
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
    