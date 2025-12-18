<?php

namespace App\Kis;
use Request;
use App\Models\LogActivity as TblLog;

class LogActivity
{
    public static function addToLog($subject,$idUser)
    {
        TblLog::create([
            'id_user' => $idUser,
            'subject' => $subject,
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'ip' => Request::ip(),
            'agent' => Request::header('user-agent')
        ]);
    } 

    public static function getList()
    {
        return TblLog::latest()->get();
    }
}
