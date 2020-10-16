<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarbersAvailability extends Model
{
    use HasFactory;

    protected $table = 'barbersavailability';
    public $timestamps = false;
}

