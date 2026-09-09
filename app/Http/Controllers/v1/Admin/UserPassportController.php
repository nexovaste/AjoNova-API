<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserPassportController extends Controller
{

    public function update(Request $request, string $id)
    {
        $request->validate([
            'passport' => 'required|image|dimensions:min_width=200,min_height=200|max:2048',
        ]);

        try {
            $admin = Staff::findOrFail($id);

            if (
                $admin->passport && $admin->passport !== User::DEFAULT_PASSPORT &&
                Storage::disk('public')->exists("passports/userPictures/{$admin->passport}")
            ) {
                Storage::disk('public')->delete("passports/userPictures/{$admin->passport}");
            }

            $file = $request->file('passport');
            $fileName = $admin->staff_id . Str::uuid() . '.' . $file->extension();

            $file->storeAs('passports/userPictures', $fileName, 'public');

            $admin->passport = $fileName;
            $admin->save();
            Cache::tags('user_list')->flush();
            Cache::forget("user_profile_{$id}");
            return response()->json([
                'success' => true,
                'message' => 'Passport updated successfully',
                'passportUrl' => Storage::url('passports/userPictures/' . $fileName),
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
