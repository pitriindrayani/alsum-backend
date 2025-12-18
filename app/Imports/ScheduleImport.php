<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Schedule;
use Maatwebsite\Excel\Concerns\ToModel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ScheduleImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $trans_timein = 'off';
        $trans_timeout = 'off';
        if ($row['time_checkin'] !== 'off') {
            $trans_timein = Date::excelToDateTimeObject($row['time_checkin'])->format('H:i');
            $trans_timeout = Date::excelToDateTimeObject($row['time_checkout'])->format('H:i');
        }
        $dateIn = intval($row['date_checkin']);
        $dateOut = intval($row['date_checkout']);
        $transform_dateIn = Date::excelToDateTimeObject($dateIn)->format('Y-m-d');
        $transform_dateOut = Date::excelToDateTimeObject($dateOut)->format('Y-m-d');

        $findUser = User::where(['email' => $row['email'], 'state' => true])->first();
        if($findUser)
        {
            $findUserSch = Schedule::where(['id_user' => $findUser->id, 'ci_date' => $transform_dateIn, 'state' => true])->first();
            if (!$findUserSch) {
                return new Schedule([
                    'id_user' => $findUser->id,
                    'ci_date' => $transform_dateIn,
                    'co_date' => $transform_dateOut,
                    'ci_time' => $trans_timein,
                    'co_time' => $trans_timeout,
                    'create_by' => request()->auth->id
                ]);
            }
        }
    }
}
