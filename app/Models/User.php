<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_image',
        'is_admin',
        'firstname',
        'lastname',
        'display_name',
        'username',
        'gcash_account',
        'paymaya_account',
        'bpi_account',
        'bdo_account',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

     protected $appends = ['profile_image_url']; // add this line

    public function getProfileImageUrlAttribute()
    {
        // If using storage folder, make sure it's publicly accessible
        return $this->profile_image
            ? asset('storage/' . $this->profile_image)
            : asset('defaults/avatar.png');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function template()
    {
        return $this->hasOne(Template::class);
    }

    public function paymentProofs()
    {
        return $this->hasMany(PaymentProof::class);
    }

    public function templateUnlocks()
    {
        return $this->hasMany(TemplateUnlock::class);
    }

    public function adminLogs()
    {
        return $this->hasMany(AdminLog::class, 'admin_id');
    }
    // User.php
    // app/Models/User.php
    public function savedTemplates()
    {
        return $this->belongsToMany(Template::class, 'user_saved_templates', 'user_id', 'template_id')
            ->withTimestamps()
            ->withPivot('saved_at');
    }

      public function usedTemplates()
{
    return $this->hasMany(UserUsedTemplate::class, 'user_id', 'id');
}
}
