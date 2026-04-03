<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function profile()
    {
        return view('admin.settings.profile', [
            'user' => Auth::user(),
        ]);
    }

    public function updateProfile(ProfileUpdateRequest $request)
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return back()->with('success', 'Profil wurde aktualisiert.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Passwort wurde geändert.');
    }

    public function appearance()
    {
        return view('admin.settings.appearance');
    }

    public function system()
    {
        $faviconPath = Setting::get('favicon_path');
        $cursorEnabled = Setting::get('favicon_cursor', false);

        return view('admin.settings.system', compact('faviconPath', 'cursorEnabled'));
    }

    public function updateSystem(Request $request)
    {
        $request->validate([
            'favicon' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp,ico|max:2048',
        ]);

        if ($request->hasFile('favicon')) {
            // Delete old favicon
            $oldPath = Setting::get('favicon_path');
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }

            // Store new favicon
            $file = $request->file('favicon');
            $path = $file->storeAs('branding', 'favicon.' . $file->getClientOriginalExtension(), 'public');
            Setting::set('favicon_path', $path);

            // Generate 32x32 cursor version if GD available
            if (function_exists('imagecreatefrompng') || function_exists('imagecreatefromjpeg')) {
                $this->generateCursorImage($path);
            }
        }

        if ($request->has('remove_favicon')) {
            $oldPath = Setting::get('favicon_path');
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
            $cursorPath = 'branding/cursor.png';
            if (Storage::disk('public')->exists($cursorPath)) {
                Storage::disk('public')->delete($cursorPath);
            }
            Setting::set('favicon_path', null);
            Setting::set('favicon_cursor', false);
        }

        Setting::set('favicon_cursor', (bool) $request->input('favicon_cursor'));

        return back()->with('success', 'Systemeinstellungen gespeichert.');
    }

    private function generateCursorImage(string $faviconPath): void
    {
        $sourcePath = Storage::disk('public')->path($faviconPath);
        $mime = mime_content_type($sourcePath);

        $source = match (true) {
            str_contains($mime, 'png') => @imagecreatefrompng($sourcePath),
            str_contains($mime, 'jpeg'), str_contains($mime, 'jpg') => @imagecreatefromjpeg($sourcePath),
            str_contains($mime, 'webp') => @imagecreatefromwebp($sourcePath),
            default => null,
        };

        if (!$source) return;

        $size = 32;
        $origW = imagesx($source);
        $origH = imagesy($source);

        $cursor = imagecreatetruecolor($size, $size);
        imagesavealpha($cursor, true);
        $transparent = imagecolorallocatealpha($cursor, 0, 0, 0, 127);
        imagefill($cursor, 0, 0, $transparent);

        // Scale proportionally
        $scale = min($size / $origW, $size / $origH);
        $newW = (int) ($origW * $scale);
        $newH = (int) ($origH * $scale);
        $x = (int) (($size - $newW) / 2);
        $y = (int) (($size - $newH) / 2);

        imagecopyresampled($cursor, $source, $x, $y, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($source);

        $cursorFullPath = Storage::disk('public')->path('branding/cursor.png');
        Storage::disk('public')->makeDirectory('branding');
        imagepng($cursor, $cursorFullPath);
        imagedestroy($cursor);
    }
}
