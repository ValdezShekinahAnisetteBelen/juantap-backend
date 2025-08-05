<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentProof extends Model
{
    protected $fillable = [
        'user_id',
        'template_id',
        'screenshot_url',
        'status',
        'admin_id',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
