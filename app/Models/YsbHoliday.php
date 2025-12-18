<?php

namespace App\Models;

use App\Traits\KeyGenerate;
use Illuminate\Foundation\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class YsbHoliday extends Model
{
    use HasFactory, KeyGenerate;

    protected $primaryKey   = 'id';

    protected $table = 'ysb_holidays';

    public $incrementing    = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'ysb_branch_id',
        'ysb_school_id',
        'ysb_teacher_id',
        'full_name',
        'holiday_name',
        'holiday_date',
        'holiday_date_end',
        'holiday_weekday',
        'holiday_type_id',
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

    public function ysb_school(): HasOne
    {
        return $this->hasOne(YsbSchool::class, 'school_code', 'ysb_school_id')->where('ysb_schools.state', true);
    }

    public function ysb_holiday_type(): HasOne
    {
        return $this->hasOne(YsbHolidayType::class, 'holiday_index', 'holiday_type_id')->where('ysb_holiday_types.state', true);
    }
}

