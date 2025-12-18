<?php

namespace App\Models;

use App\Traits\KeyGenerate;
use Illuminate\Foundation\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YsbPeriod extends Model
{
    use HasFactory, KeyGenerate;

    protected $primaryKey   = 'id';

    protected $table = 'ysb_periods';

    public $incrementing    = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'period_title',
        'year',
        'month',
        'period_start',
        'period_start_weekday',
        'period_end',
        'period_end_weekday',
        'in_time',
        'out_time',
        'days',
        'alazhar_title',
        'alazhar_pic',
        //puasa
        'period_start_puasa',
        'period_end_puasa',
        'in_time_puasa',
        'out_time_puasa',
        'fg_active',
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

