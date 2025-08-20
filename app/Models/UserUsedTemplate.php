<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserUsedTemplate extends Model
{
    protected $table = 'user_used_templates'; // make sure this matches table name

    protected $fillable = ['user_id', 'template_id'];

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

