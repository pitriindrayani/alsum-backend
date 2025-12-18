<?php

namespace App\Models;

use App\Traits\KeyGenerate;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\URL;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, KeyGenerate;

    protected $primaryKey   = 'id';

    public $incrementing    = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'level',
        'id_role',
        'id_teacher',
        'ysb_branch_id',
        'ysb_school_id',
        'nik_ysb',
        'extended_user',
        'state',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
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

    protected $appends = [
        // 'create_by_data',
        // 'update_by_data',
        'photo_profile'
    ];

    // public function getCreateByDataAttribute()
    // {
    //     return User::where('id', $this->create_by)->first();
    // }

    // public function getUpdateByDataAttribute()
    // {
    //     return User::where('id', $this->update_by)->first();
    // }

    public function getPhotoProfileAttribute()
    {
        $findPP = UserPicture::where(['id_user' => $this->id, 'set_as_pp' => true, 'state' => true])->first();
        if(!$findPP)
        {
            // return URL::to('/storage/images/no-profile.png');
            return env('STORAGE_SERVICE_BASE_URI').'/images/no-profile.png';
        }
        return $findPP->url_image;
        // return null;
    }

    /**
     * Get the user_detail associated with the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user_detail(): HasOne
    {
        return $this->hasOne(UserDetail::class, 'id_user', 'id');
    }

    /**
     * The menus that belong to the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'permissions', 'id_user', 'id_menu')->where('menus.state', true)->where('menus.show', true)->orderBy('menus.number_order', 'asc');
    }

    /**
     * Get all of the pictures for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pictures(): HasMany
    {
        return $this->hasMany(UserPicture::class, 'id_user', 'id')->where('user_pictures.state', true);
    }
    
    /**
     * The roles that belong to the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_has_roles', 'id_user', 'id_role')->where('user_has_roles.state', true);
    }

    /**
     * Get the user associated with the UserPicture
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    // public function teacher(): HasOne
    // {
    //     return $this->hasOne(YsbTeacher::class, 'id', 'id_teacher')->where('ysb_teachers.state', true);
    // }
    
}
