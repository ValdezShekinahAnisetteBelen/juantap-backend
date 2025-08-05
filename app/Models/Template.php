<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $fillable = [
        'name',
        'description',
        'thumbnail_url',
        'is_premium',
    ];

    public function profiles()
    {
        return $this->hasMany(Profile::class);
    }

    public function paymentProofs()
    {
        return $this->hasMany(PaymentProof::class);
    }

    public function templateUnlocks()
    {
        return $this->hasMany(TemplateUnlock::class);
    }
}
