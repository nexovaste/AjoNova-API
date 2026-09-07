<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class UserPassportController extends Controller
{
    public function update(Request $request, string $id)
    {
        $request->validate([
            'passport' => 'required|file|image|mimes:jpeg,png,jpg,webp|max:3072',
        ]);

        try {
            // 1. Locate the member by user_id column
            $user = User::where('user_id', $id)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => "Member record with ID '{$id}' was not found.",
                ], 404);
            }

            if (!$request->hasFile('passport') || !$request->file('passport')->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Uploaded file is missing or invalid.',
                ], 422);
            }

            // 2. Prepare target directory
            $destinationPath = storage_path('app/public/passports/userPictures');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            // 3. Remove old file safely
            $currentPassport = basename(trim((string) ($user->passport ?? '')));
            $ignoredDefaults = ['avatar.jpg', 'avatar.png', 'default.jpg', 'default.png', ''];

            if (!empty($currentPassport) && !in_array(strtolower($currentPassport), $ignoredDefaults)) {
                $oldFilePath = $destinationPath . DIRECTORY_SEPARATOR . $currentPassport;
                if (file_exists($oldFilePath) && is_file($oldFilePath)) {
                    @unlink($oldFilePath);
                }
            }

            // 4. Save new user passport image
            $file = $request->file('passport');
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $fileName = $user->user_id . '_' . time() . '_' . Str::random(6) . '.' . $extension;

            $file->move($destinationPath, $fileName);

            // 5. Update database
            $user->passport = $fileName;
            $user->save();

            // 6. Invalidate all cache references
            Cache::forget("user_profile_{$user->user_id}");
            Cache::forget("user_profile_" . trim($user->user_id));
            Cache::forget("user_profile_{$id}");
            Cache::forget("user_profile_" . trim($id));
            Cache::forget("member_profile_{$user->user_id}");
            Cache::forget("member_profile_{$id}");

            if (Cache::supportsTags()) {
                Cache::tags(['user_list', 'member_list'])->flush();
            }

            return response()->json([
                'success' => true,
                'message' => 'Passport updated successfully',
                'passportUrl' => '/storage/passports/userPictures/' . $fileName,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Failed to update passport',
            ], 500);
        }
    }
}