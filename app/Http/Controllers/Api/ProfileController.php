<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SocialLink;
use App\Models\User;
use App\Models\Template;

use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function storeOrUpdate(Request $request)
{
    $user = $request->user();

    try {
    $validated = $request->validate([
        'name' => 'nullable|string|max:255',
        'firstname' => 'nullable|string|max:255',
        'lastname' => 'nullable|string|max:255',
        'display_name' => 'nullable|string|max:255',
        'username' => 'nullable|string|max:255|unique:users,username,' . $user->id,
        'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',

        // Profile-specific fields
        'bio' => 'nullable|string',
        'phone' => 'nullable|string|max:20',
        'website' => 'nullable|string|max:255',
        'location' => 'nullable|string|max:255',

        'social_links' => 'array',
        'social_links.*.id' => 'nullable|integer|exists:social_links,id',
        'social_links.*.platform' => 'required|string|max:50',
        'social_links.*.url' => 'required|string|max:255',
        'social_links.*.display_name' => 'nullable|string|max:100',
        'social_links.*.is_visible' => 'boolean',

        // Payment accounts
        'gcash' => 'nullable|string|max:20',
        'paymaya' => 'nullable|string|max:20',
        'bpi' => 'nullable|string|max:20',
        'bdo' => 'nullable|string|max:20',
    ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
    return response()->json([
        'message' => 'Validation failed',
        'errors' => $e->errors(),
    ], 422);
}

    // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->profile_image && file_exists(public_path($user->profile_image))) {
                unlink(public_path($user->profile_image));
            }

            // Save new file into /public/avatars
            $filename = time() . '_' . $request->file('avatar')->getClientOriginalName();
            $request->file('avatar')->move(public_path('avatars'), $filename);

            $user->profile_image = 'avatars/' . $filename; // relative path
        }


    // Update user table including payment accounts
    $user->fill([
        'name' => $validated['name'] ?? $user->name,
        'username' => $validated['username'] ?? $user->username,
        'display_name' => $validated['display_name'] ?? $user->display_name,
        'gcash_account' => $validated['gcash'] ?? $user->gcash_account,
        'paymaya_account' => $validated['paymaya'] ?? $user->paymaya_account,
        'bpi_account' => $validated['bpi'] ?? $user->bpi_account,
        'bdo_account' => $validated['bdo'] ?? $user->bdo_account,
    ])->save();

    // Update or create profile
    $profile = $user->profile()->updateOrCreate([], [
        'bio' => $validated['bio'] ?? null,
        'phone' => $validated['phone'] ?? null,
        'website' => $validated['website'] ?? null,
        'location' => $validated['location'] ?? null,
    ]);

    // Update social links
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

        // Delete removed links
        $profile->socialLinks()->whereNotIn('id', $existingIds)->delete();
    }

    return response()->json(['message' => 'Profile updated successfully.']);
}

public function me(Request $request)
{
    $user = $request->user()->load(['profile.socialLinks']);

    // Get admin accounts
    $admin = User::where('is_admin', 1)->first();

    $paymentAccounts = $user->is_admin
        ? [
            'gcash'   => $user->gcash_account,
            'paymaya' => $user->paymaya_account,
            'bpi'     => $user->bpi_account,
            'bdo'     => $user->bdo_account,
        ]
        : [
            'gcash'   => $admin->gcash_account ?? null,
            'paymaya' => $admin->paymaya_account ?? null,
            'bpi'     => $admin->bpi_account ?? null,
            'bdo'     => $admin->bdo_account ?? null,
        ];

    $response = [
        'id'           => $user->id,
        'name'         => $user->name,
        'firstname'    => $user->firstname,
        'lastname'     => $user->lastname,
        'display_name' => $user->display_name,
        'username'     => $user->username,
        'email'        => $user->email,
       'avatar_url' => $user->profile_image
        ? asset($user->profile_image)
        : asset("avatars/default.png"),
        'is_admin'     => $user->is_admin,
        'profile'      => [
            'bio'              => $user->profile->bio ?? '',
            'phone'            => $user->profile->phone ?? '',
            'website'          => $user->profile->website ?? '',
            'location'         => $user->profile->location ?? '',
            'template_id'      => $user->profile->template_id,
            'background_type'  => $user->profile->background_type,
            'background_value' => $user->profile->background_value,
            'font_style'       => $user->profile->font_style,
            'font_size'        => $user->profile->font_size,
            'button_style'     => $user->profile->button_style,
            'accent_color'     => $user->profile->accent_color,
            'nfc_redirect_url' => $user->profile->nfc_redirect_url,
            'is_published'     => $user->profile->is_published,
            'socialLinks'      => $user->profile->socialLinks->map(function ($link) {
                return [
                    'id'        => $link->id,
                    'platform'  => $link->platform,
                    'username'  => $link->display_name,
                    'url'       => $link->url,
                    'isVisible' => (bool) $link->is_visible,
                ];
            }),
        ],
        'payment_accounts' => $paymentAccounts,
    ];

    return response()->json($response, 200);
}


public function show($username)
{
    $user = User::where('username', $username)->with('profile.socialLinks')->first();

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    // Load template via template_slug if exists
    $template = null;
    if ($user->template_slug) {
        $template = \App\Models\Template::where('slug', $user->template_slug)->first();
    }

    return response()->json([
        'id' => $user->id,
        'username' => $user->username,
        'display_name' => $user->display_name,
        'name' => $user->name,
        'email' => $user->email,
        'avatar_url' => $user->profile_image
            ? asset($user->profile_image)
            : asset("avatars/default.png"),
        'template' => $template,
        'profile' => [
            'bio' => $user->profile->bio ?? '',
            'phone' => $user->profile->phone ?? '',
            'website' => $user->profile->website ?? '',
            'location' => $user->profile->location ?? '',
            'socialLinks' => $user->profile->socialLinks->map(function($link) {
                return [
                    'id' => $link->id,
                    'platform' => $link->platform,
                    'username' => $link->display_name,
                    'url' => $link->url,
                    'isVisible' => (bool) $link->is_visible
                ];
            })
        ]
    ]);
    }

}
