<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class UserAuth extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'users';

    protected $fillable = [
      'id', 'email', 'password',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected $hidden = [
      'password',
    ];
}
