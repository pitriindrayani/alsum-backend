<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogActivity extends Model
{
    use HasFactory;
    public $incrementing    = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'subject', 'url', 'method', 'ip', 'agent', 'id_user'
    ];

    protected $appends = [
        'user_data'
    ];

    public function getUserDataAttribute()
    {
        return User::find($this->id_user);
    }
}
