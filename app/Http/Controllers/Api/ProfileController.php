<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SocialLink;
use App\Models\User;
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
    public function me(Request $request)
    {
        $user = $request->user()->load([
            'profile.socialLinks' // eager load profile + social links
        ]);

        return response()->json([
            'id'           => $user->id,
            'name'         => $user->name,
            'firstname'    => $user->firstname,
            'lastname'     => $user->lastname,
            'display_name' => $user->display_name,
            'username'     => $user->username,
            'email'        => $user->email,
            'profile_image' => $user->profile_image,
            'is_admin'     => $user->is_admin,
            'profile'      => [
                'bio'               => $user->profile->bio ?? '',
                'phone'             => $user->profile->phone ?? '',
                'website'           => $user->profile->website ?? '',
                'location'          => $user->profile->location ?? '',
                'template_id'       => $user->profile->template_id,
                'background_type'   => $user->profile->background_type,
                'background_value'  => $user->profile->background_value,
                'font_style'        => $user->profile->font_style,
                'font_size'         => $user->profile->font_size,
                'button_style'      => $user->profile->button_style,
                'accent_color'      => $user->profile->accent_color,
                'nfc_redirect_url'  => $user->profile->nfc_redirect_url,
                'is_published'      => $user->profile->is_published,
                'socialLinks' => $user->profile->socialLinks->map(function ($link) {
                    return [
                        'id'        => $link->id,           
                        'platform'  => $link->platform,      
                        'username'  => $link->display_name,
                        'url'       => $link->url,
                        'isVisible' => (bool) $link->is_visible, 
                    ];
                }),

            ]
        ], 200);
    }
    public function show($username)
    {
        $user = User::where('username', $username)
            ->with(['profile.socialLinks']) // eager load profile + social links
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        return response()->json([
            'id'            => $user->id,
            'name'          => $user->name,
            'firstname'     => $user->firstname,
            'lastname'      => $user->lastname,
            'display_name'  => $user->display_name,
            'username'      => $user->username,
            'email'         => $user->email,
            'profile_image' => $user->profile_image,
            'is_admin'      => $user->is_admin,
            'profile'       => [
                'bio'               => $user->profile->bio ?? '',
                'phone'             => $user->profile->phone ?? '',
                'website'           => $user->profile->website ?? '',
                'location'          => $user->profile->location ?? '',
                'template_id'       => $user->profile->template_id,
                'background_type'   => $user->profile->background_type,
                'background_value'  => $user->profile->background_value,
                'font_style'        => $user->profile->font_style,
                'font_size'         => $user->profile->font_size,
                'button_style'      => $user->profile->button_style,
                'accent_color'      => $user->profile->accent_color,
                'nfc_redirect_url'  => $user->profile->nfc_redirect_url,
                'is_published'      => $user->profile->is_published,
                'socialLinks' => $user->profile->socialLinks->map(function ($link) {
                    return [
                        'id'        => $link->id,
                        'platform'  => $link->platform,
                        'username'  => $link->display_name,
                        'url'       => $link->url,
                        'isVisible' => (bool) $link->is_visible,
                    ];
                }),

            ]
        ], 200);
    }



}
