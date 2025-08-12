<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserUsedTemplate extends Model
{
    protected $fillable = ['user_id', 'template_id'];

    // Enable timestamps (make sure DB table has created_at, updated_at)
    public $timestamps = true;

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
