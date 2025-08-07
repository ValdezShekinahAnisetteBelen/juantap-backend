<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SocialLink;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function storeOrUpdate(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',

            // Profile-specific fields (stored in `profiles` table)
            'bio' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',

            'social_links' => 'array',
            'social_links.*.id' => 'nullable|integer|exists:social_links,id',
            'social_links.*.platform' => 'required|string|max:50',
            'social_links.*.url' => 'required|url|max:255',
            'social_links.*.display_name' => 'nullable|string|max:100',
            'social_links.*.is_visible' => 'boolean',

        ]);

        // ✅ Handle avatar upload
        if ($request->hasFile('avatar')) {
            if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->profile_image = $avatarPath;
        }

        // ✅ Update fields in users table
        $user->fill([
            'name' => $validated['name'] ?? $user->name,
            'username' => $validated['username'] ?? $user->username,
            'display_name' => $validated['display_name'] ?? $user->display_name,
        ])->save();

            $profile = $user->profile()->updateOrCreate([], [
                'bio' => $validated['bio'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'website' => $validated['website'] ?? null,
                'location' => $validated['location'] ?? null,
            ]);


        if (isset($validated['social_links'])) {
        $existingIds = [];

        foreach ($validated['social_links'] as $linkData) {
            if (isset($linkData['id'])) {
                $link = SocialLink::where('id', $linkData['id'])
                    ->where('profile_id', $profile->id)
                    ->first();

                if ($link) {
                    $link->update($linkData);
                    $existingIds[] = $link->id;
                }
            } else {
                $link = $profile->socialLinks()->create($linkData);
                $existingIds[] = $link->id;
            }
        }

        // Optionally delete removed links
        $profile->socialLinks()->whereNotIn('id', $existingIds)->delete();
    }

        return response()->json(['message' => 'Profile updated successfully.']);
    }
}
