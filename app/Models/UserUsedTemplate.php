<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserUsedTemplate extends Model
{
    protected $fillable = ['user_id', 'template_id'];

    public function template()
    {
        return $this->belongsTo(Template::class);
    }
}
