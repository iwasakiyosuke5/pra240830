<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fragment extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'vector',
        'file_path', // Add this line

    ];
}