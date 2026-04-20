<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StaffPassportController extends Controller
{

    public function update(Request $request, string $id)
    {
        $request->validate([
            'passport' => 'required|image|dimensions:min_width=200,min_height=200|max:2048',
        ]);

        try {
            $admin = Staff::findOrFail($id);

            if (
                $admin->passport && $admin->passport !== Staff::DEFAULT_PASSPORT &&
                Storage::disk('public')->exists("passports/staffPictures/{$admin->passport}")
            ) {
                Storage::disk('public')->delete("passports/staffPictures/{$admin->passport}");
            }

            $file = $request->file('passport');
            $fileName = $admin->staff_id . Str::uuid() . '.' . $file->extension();

            $file->storeAs('passports/staffPictures', $fileName, 'public');

            $admin->passport = $fileName;
            $admin->save();
            Cache::tags('staff_list')->flush();
            Cache::forget("staff_profile_{$id}");
            return response()->json([
                'success' => true,
                'message' => 'Passport updated successfully',
                'passportUrl' => Storage::url('passports/staffPictures/' . $fileName),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update passport',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
