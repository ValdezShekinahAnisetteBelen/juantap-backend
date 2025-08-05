<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateUnlock extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'template_id',
        'unlocked_at',
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
