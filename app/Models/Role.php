<?php

namespace App\Models;

use App\Traits\KeyGenerate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory, KeyGenerate;

    protected $primaryKey   = 'id';

    public $incrementing    = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'slug_name',
        'icon_name',
        'ysb_branch_id',
        'state',
        'create_by',
        'update_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'state'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at'        => 'datetime:Y-m-d H:i:s',
        'updated_at'        => 'datetime:Y-m-d H:i:s',
    ];


    // protected $appends = [
    //     'create_by_data',
    //     'update_by_data',
    // ];

    public function getCreateByDataAttribute()
    {
        return User::find($this->create_by);
    }

    public function getUpdateByDataAttribute()
    {
        return User::find($this->update_by);
    }
}
