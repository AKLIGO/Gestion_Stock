<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    // Relation Many-to-Many vers User
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
