<?php

namespace App\Models;

use App\Traits\KeyGenerate;
use Illuminate\Foundation\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YsbSchedule extends Model
{
    use HasFactory, KeyGenerate;

    protected $primaryKey   = 'id';

    protected $table = 'ysb_schedules';

    public $incrementing    = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'ysb_school_id',
        'ysb_position_id',
        'ysb_position_code',
        'schedule_code',
        'in_time',
        'out_time',
        'day_1',
        'day_2',
        'day_3',
        'day_4',
        'day_5',
        'day_6',
        'day_7',
        'fg_school_default',
        'holiday_type', 
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

