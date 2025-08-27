<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateUnlock extends Model
{
    public $timestamps = false;

    // Allow mass assignment for these fields
    protected $fillable = [
        'user_id',
        'template_id',
        'unlocked_at',
        'receipt_img',
        'payment_method',
        'reference_number',
        'notes',
        'is_approved',
        'submitted_at',
        'status',
    ];

    // Cast timestamps and booleans properly
    protected $casts = [
        'unlocked_at' => 'datetime',
        'submitted_at' => 'datetime',
        'is_approved' => 'boolean',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }
}
