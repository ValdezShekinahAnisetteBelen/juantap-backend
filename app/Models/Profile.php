<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'username',
        'bio',
        'phone',
        'website',
        'location',
        'template_id',
        'background_type',
        'background_value',
        'font_style',
        'font_size',
        'button_style',
        'accent_color',
        'nfc_redirect_url',
        'is_published',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function socialLinks()
    {
        return $this->hasMany(SocialLink::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }
    
}
