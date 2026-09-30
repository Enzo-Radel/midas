<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mercado extends Model
{
    public $timestamps = false;

    protected $fillable = ['nome'];
}
