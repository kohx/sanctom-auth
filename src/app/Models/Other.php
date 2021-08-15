<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Other extends Model
{
    use HasFactory;

    protected $table = 'others';

    protected $connection = 'mysql2';

    protected $fillable = [
        'id',
        'name',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
