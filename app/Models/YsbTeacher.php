<?php

namespace App\Models;

use App\Traits\KeyGenerate;
use Illuminate\Foundation\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class YsbTeacher extends Model
{
    use HasFactory, KeyGenerate;

    protected $primaryKey = 'id';
    protected $table = 'ysb_teachers';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'nip_ypi',
        // 'nip_ypi_karyawan', // Tidak perlu diisi manual kalau virtual
        'nik_ysb',
        'join_date_ypi',
        'join_date_ysb',
        'full_name',
        'nik_ktp',
        'birthplace',
        'birthdate',
        'gender',
        'employment_status',
        'ysb_branch_id',
        'edu_stage',
        'ysb_school_id',
        'ysb_position_id',
        'ysb_schedule_id',
        'bidang',
        'ysb_teacher_group_id',
        'religion',
        'addrees',
        'dom_address',
        'marriage',
        'npwp',
        'ptkp',
        'university',
        'major',
        'degree',
        'mobile',
        'email',
        'bank',
        'nama_rekening',
        'no_rekening',
        'contact_name',
        'contact_relation',
        'contact_number',
        'nuptk',
        'user_id',
        'zakat',
        'fg_active',
        'finger_id',
        'state',
        'create_by',
        'update_by'
    ];

    protected $hidden = ['state'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $appends = [
        'nip_ypi_karyawan',
        // 'create_by_data',
        // 'update_by_data',
    ];

    // Relasi ke tabel status record terbaru
    public function latestStatusRecord()
    {
        return $this->hasOne(YsbTeacherStatusRecord::class, 'ysb_id_teacher')
            ->where('state', true)
            ->latest('date');
    }

    // Accessor untuk nip_ypi_karyawan
    public function getNipYpiKaryawanAttribute()
    {
        return $this->latestStatusRecord?->nip_ypi;
    }

    public function getCreateByDataAttribute()
    {
        return User::find($this->create_by);
    }

    public function getUpdateByDataAttribute()
    {
        return User::find($this->update_by);
    }

    /**
     * Get the user associated with the UserPicture
     */
    public function users(): HasOne
    {
        return $this->hasOne(User::class, 'id_teacher', 'id')
            ->where('users.state', true)
            ->select(['id_teacher','username','email','level','id_role','ysb_branch_id','ysb_school_id','nik_ysb']);
    }
}
