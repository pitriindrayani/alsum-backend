<?php

namespace App\Kis;

use Illuminate\Support\Facades\DB;

class LinkService
{
    //** call user in db Core */
    public static function userCoreByEmail($email)
    {
        $explode = explode('@', $email);
        return DB::connection(env('DB_CONNECTION_TITANIUM_CORE'))->table('tbl_user')->where('username', '=', $explode[0])->where('nama','NOT LIKE','%Non Aktif%')->where('nama', 'NOT LIKE', '%OFF%')->first();
    }

    //** reset password user in db Core */
    public static function userResetPass($email)
    {
        $explode = explode('@',
            $email
        );
        return DB::connection(env('DB_CONNECTION_TITANIUM_CORE'))->table('tbl_user')->where('username', '=', $explode[0])->where('nama', 'NOT LIKE', '%Non Aktif%')->where('nama', 'NOT LIKE', '%OFF%')->update([
            'password' => md5('GRATIA'.date('Y'))
        ]);
    }
}
