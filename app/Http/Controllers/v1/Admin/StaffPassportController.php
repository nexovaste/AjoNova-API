<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class StaffPassportController extends Controller
{
    public function update(Request $request, string $id)
    {
        $request->validate([
            'passport' => 'required|file|image|mimes:jpeg,png,jpg,webp|max:3072',
        ]);

        try {
            // 1. Locate the staff member by staff_id
            $admin = Staff::where('staff_id', $id)->first();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => "Staff record with ID '{$id}' was not found.",
                ], 404);
            }

            if (!$request->hasFile('passport') || !$request->file('passport')->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Uploaded file is missing or invalid.',
                ], 422);
            }

            // 2. Prepare target directory
            $destinationPath = storage_path('app/public/passports/staffPictures');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            // 3. Remove old file safely
            $currentPassport = basename(trim((string) ($admin->passport ?? '')));
            $ignoredDefaults = ['avatar.jpg', 'avatar.png', 'default.jpg', 'default.png', ''];

            if (!empty($currentPassport) && !in_array(strtolower($currentPassport), $ignoredDefaults)) {
                $oldFilePath = $destinationPath . DIRECTORY_SEPARATOR . $currentPassport;
                if (file_exists($oldFilePath) && is_file($oldFilePath)) {
                    @unlink($oldFilePath);
                }
            }

            // 4. Save new passport image
            $file = $request->file('passport');
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $fileName = $admin->staff_id . '_' . time() . '_' . Str::random(6) . '.' . $extension;

            $file->move($destinationPath, $fileName);

            // 5. Update database
            $admin->passport = $fileName;
            $admin->save();

            // 6. Invalidate all cache references used by fetchProfile & admin queries
            Cache::forget("staff_profile_{$admin->staff_id}");
            Cache::forget("staff_profile_" . trim($admin->staff_id));
            Cache::forget("staff_profile_{$id}");
            Cache::forget("staff_profile_" . trim($id));

            if (Cache::supportsTags()) {
                Cache::tags(['staff_list', 'staff_profile'])->flush();
            }

            return response()->json([
                'success' => true,
                'message' => 'Passport updated successfully',
                'passportUrl' => '/storage/passports/staffPictures/' . $fileName,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Failed to update passport',
            ], 500);
        }
    }
}