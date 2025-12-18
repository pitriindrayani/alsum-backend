<?php

namespace App\Models;

use App\Traits\KeyGenerate;
use Illuminate\Foundation\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class YsbWfh extends Model
{
    use HasFactory, KeyGenerate;

    protected $primaryKey   = 'id';

    protected $table = 'ysb_wfh';

    public $incrementing    = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'ysb_teacher_id',
        'ysb_branch_id',
        'ysb_school_id',
        'id_user_head_school',
        'full_name',
        'att_date',
        'att_clock_in',
        'att_clock_out',
        'schedule_in',
        'schedule_out',
        'approve_hr',
        'approve_head_school',
        'keterangan',
        'dokument',
        'approve_at_head',
        'approve_by_head',
        'approve_at_hr',
        'approve_by_hr',
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
    
    public function teacherDetail(): HasOne
    {
        return $this->hasOne(YsbTeacher::class, 'id', 'ysb_teacher_id')->where('ysb_teachers.state', true);
    }
}

