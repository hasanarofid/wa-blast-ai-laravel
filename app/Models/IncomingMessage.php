<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomingMessage extends Model
{
    use HasFactory;

    // protected $guarded = []; // artinya semua field boleh

    protected $fillable = [
        'from',
        'message',
        'type',
        'raw',
    ];

}
