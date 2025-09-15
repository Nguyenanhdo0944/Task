<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'username',
        'email',
        'phone',
        'birthday',
        'password',
        'is_admin',
        'avatar',        // thêm mới
        'avatar_crop'    // thêm mới
    ];

    public $timestamps = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
}
