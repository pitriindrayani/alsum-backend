<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\SDC\RestCurl;
use App\Models\YsbPeriod;
use App\Models\YsbTeacher;
use App\Models\YsbAttendanceTrx;
use App\Models\YsbSchedule;
use App\Models\YsbHoliday;
use App\Models\YsbWfh;
use App\Models\YsbScheduleTime;
use App\Models\YsbAttendanceDaily;
use Illuminate\Support\Str;
use App\Kis\HttpStatusCodes;
use Illuminate\Http\Request;
use App\Kis\PaginationFormat;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\YsbPeriodCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class YsbPeriodController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, YsbPeriod $TblData)
    {
        $validator = Validator::make($request->all(), PaginationFormat::VALIDATION);
        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        try {
            $data = $TblData->newQuery();
            $data->when((string)$request->search != null, function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($query) use ($search) {
                    $query->where('branch_name', 'LIKE', '%' . $search . '%');
                });
            });
            
            $data->where('state', true);
            $result = $data->orderBy('created_at', $request->ascending == true ? 'asc' : 'desc')->paginate($request->limit);

            return new YsbPeriodCollection($result);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $th->getMessage(),
                'trace' => $th->getTrace()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period_title'         => 'nullable',
            'year'                 => 'nullable',
            'month'                => 'nullable',
            'period_start'         => 'required',
            'period_start_weekday' => 'nullable',
            'period_end'           => 'required',
            'period_end_weekday'   => 'nullable',
            'in_time'              => 'required',
            'out_time'             => 'required',
            'days'                 => 'nullable',
            'alazhar_title'        => 'nullable',
            'alazhar_pic'          => 'nullable',
            'fg_active'            => 'nullable',
            // puasa
            'period_start_puasa'   => 'nullable',
            'period_end_puasa'     => 'nullable',
            'in_time_puasa'        => 'nullable',
            'out_time_puasa'       => 'nullable',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'error'  => true,
                'message' => $validator->errors()->all()[0]
            ], 400);
        }
    
        try {
            // Save data
            $startDate =  Carbon::parse($request->period_start)->startOfDay();
            $endDate = Carbon::parse($request->period_end)->startOfDay();

            $save = YsbPeriod::create([
                'period_title'         => $request->period_title,
                'year'                 => date('Y', strtotime($request->period_end)),
                'month'                => date('m', strtotime($request->period_end)),
                'period_start'         => $request->period_start,
                'period_start_weekday' => $request->period_start_weekday,
                'period_end'           => $request->period_end,
                'period_end_weekday'   => $request->period_end_weekday,
                'days'                 => $startDate->diffInDays($endDate)+1,
                'in_time'              => $request->in_time,
                'out_time'             => $request->out_time,
                'alazhar_title'        => $request->alazhar_title,
                'alazhar_pic'          => $request->alazhar_pic,
                 // puasa
                'period_start_puasa'   => $request->period_start_puasa,
                'period_end_puasa'     => $request->period_end_puasa,
                'in_time_puasa'        => $request->in_time_puasa,
                'out_time_puasa'       => $request->out_time_puasa,
                'fg_active'            => 1,
                'create_by'            => $request->auth->id
            ]);
           
            return response()->json([
                'status' => 200,
                'error'  => false,
                'message' => 'Success to create data'
            ], 200);
        } catch (\Throwable $th) {
            // Capture specific error details
            return response()->json([
                'status' => 400,
                'error'  => true,
                'message' => $th->getMessage()
            ], 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $data = YsbPeriod::where(['id' => $id, 'state' => true])->first();
            if(!$data)
            {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error' => true,
                'message' => "Periode Tidak Ditemukan!"
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }

            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'data' => $data
            ], HttpStatusCodes::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $th->getMessage()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'period_title'         => 'nullable',
            'year'                 => 'nullable',
            'month'                => 'nullable',
            'period_start'         => 'required',
            'period_start_weekday' => 'nullable',
            'period_end'           => 'required',
            'period_end_weekday'   => 'nullable',
            'in_time'              => 'required',
            'out_time'             => 'required',
            'days'                 => 'nullable',
            'alazhar_title'        => 'nullable',
            'alazhar_pic'          => 'nullable',
            'fg_active'            => 'nullable',
            // puasa
            'period_start_puasa'   => 'nullable',
            'period_end_puasa'     => 'nullable',
            'in_time_puasa'        => 'nullable',
            'out_time_puasa'       => 'nullable',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        try {
            $data = YsbPeriod::where(['id' => $id, 'state' => true])->first();
            if(!$data)
            {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => "Periode Tidak Ditemukan!"
                ], HttpStatusCodes::HTTP_BAD_REQUEST);                
            }

            $startDate =  Carbon::parse($request->period_start)->startOfDay();
            $endDate = Carbon::parse($request->period_end)->startOfDay();
                
            $data->year                = date('Y', strtotime($request->period_end));
            $data->month               = date('m', strtotime($request->period_end));
            $data->period_start        = $request->period_start;
            $data->period_end          = $request->period_end;
            $data->in_time             = $request->in_time;
            $data->out_time            = $request->out_time;
            $data->days                = $startDate->diffInDays($endDate)+1;
            $data->alazhar_title       = $request->alazhar_title;
            $data->alazhar_pic         = $request->alazhar_pic;
            $data->fg_active           = $request->fg_active;
            // puasa
            $data->period_start_puasa  = $request->period_start_puasa;
            $data->period_end_puasa    = $request->period_end_puasa;
            $data->in_time_puasa       = $request->in_time_puasa;
            $data->out_time_puasa      = $request->out_time_puasa;
            $data->update_by           = $request->auth->id;
            $data->save();

            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Success to update data'
            ], HttpStatusCodes::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $th->getMessage()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try {
            $data = YsbPeriod::where(['id' => $id, 'state' => true])->first();
            if (!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'Periode Tidak Ditemukan!'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $data->state = false;
            $data->update_by = $request->auth->id;
            $data->save();
            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Success to delete data'
            ], HttpStatusCodes::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $th->getMessage()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
    }

    public function indexByMonthYear(Request $request, YsbPeriod $TblData)
    {
        $validator = Validator::make($request->all(), PaginationFormat::VALIDATION);
        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        try {
            $data = $TblData->newQuery();
            $data->when((string)$request->search != null, function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($query) use ($search) {
                    $query->where('month', 'LIKE', '%' . $search . '%');
                    $query->where('year', 'LIKE', '%' . $search . '%');
                });
            });
            
            $data->where('state', true);

            return new YsbPeriodCollection($data);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $th->getMessage(),
                'trace' => $th->getTrace()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
    }

    public function recapAbsensi(Request $request, YsbPeriod $TblData)
    {
        try {
            //check absensi
            $data = YsbPeriod::where(['state' => true]);
            $data->when((string)$request->monthYear != null, function ($query) use ($request) {
                // Ubah format input bulan dan tahun menjadi 'YYYY-MM'
                $monthYear = date('Y-m', strtotime($request->monthYear));
                return $query->whereRaw("DATE_FORMAT(period_end, '%Y-%m') = ?", [$monthYear]);
            });
            $data = $data->get()->first();
            
            if ($data) {
                // Ambil data finger ID
                $fingerId = YsbTeacher::where(['id' => $request->id_teacher, 'state' => true])->first();            
                if (!$fingerId) {
                    return response()->json([
                        'status' => 400,
                        'error' => true,
                        'message' => 'Data guru tidak ditemukan'
                    ]);
                }
            
                // Ambil data absen berdasarkan finger ID
                $arrayAbsen = YsbAttendanceTrx::where(['finger_id' => $fingerId->finger_id, 'state' => true])->get();

                // Ambil data Kalender libur
                $arrayJadwalLibur = YsbHoliday::where(['ysb_teacher_id' => $fingerId->id, 'state' => true])->get();

                // Ambil data jadwal shift
                $arrayJadwalShift = YsbScheduleTime::where(['ysb_id_teacher' => $fingerId->id, 'state' => true])->get();

                // Ambil data koreksi absen berdasarkan id guru dan waktu
                $attendanceDailyTeacher = YsbAttendanceDaily::where(['ysb_teacher_id' => $request->id_teacher, 'state' => true])
                ->whereBetween('att_date', [$data->period_start, $data->period_end])
                ->get();

                // Ambil data wfh
                $attendanceDailyWfh = YsbWfh::where(['ysb_teacher_id' => $request->id_teacher, 'state' => true])
                ->whereBetween('att_date', [$data->period_start, $data->period_end])
                ->get();
                
                // Ambil data id kepala sekolah 
                $headTeacherSchool = YsbTeacher::where('ysb_school_id', $fingerId->ysb_school_id)
                ->whereIn('ysb_position_id', ['B', 'D1', 'J'])
                ->where('state', true)
                ->first();

                // Generate range of dates
                $startDate =  Carbon::parse($data->period_start);
                $endDate = Carbon::parse($data->period_end);
                $dateRange = [];
            
                while($startDate->lte($endDate)){
                    $currentDate = $startDate->format('Y-m-d');

                    // Cek tanggal masuk dalam periode puasa , nanti waktunya akan ditiban dengan waktu puasa sesuai period puasa
                    if ($data && $currentDate >= $data->period_start_puasa && $currentDate <= $data->period_end_puasa) {
                        $inTime = $data->in_time_puasa;
                        $outTime = $data->out_time_puasa;
                    }else{
                        $inTime = $data->in_time;
                        $outTime = $data->out_time;
                    }

                    // cek jika terdapat jadwal shift / waktu tertentu
                    $hasShiftToday = false;
                    if(!$arrayJadwalShift->isEmpty()) { 
                        foreach($arrayJadwalShift as $shift) {
                            if($currentDate >= $shift->date_in && $currentDate <= $shift->date_out) {
                                $inTime = $shift->time_in;
                                $outTime = $shift->time_out;
                                $hasShiftToday = true;
                                break; 
                            }
                        }
                    }

                    // // cek jika terdapat jadwal wfh
                    // $hasWfhToday = false;
                    // if(!$arrayJadwalWfh->isEmpty()) { 
                    //     foreach($arrayJadwalWfh as $wfh) {
                    //         if($wfh->att_date === $currentDate) {
                    //             $inTime = $wfh->att_clock_in;
                    //             $outTime = $wfh->att_clock_out;
                    //             $hasWfhToday = true;
                    //             break; 
                    //         }
                    //     }
                    // }

                     // Cek apakah tanggal saat ini ada dalam rentang libur
                     $dayLibur = 0;
                     foreach ($arrayJadwalLibur as $libur) {
                         if ($currentDate >= $libur->holiday_date && $currentDate <= $libur->holiday_date_end) {
                             $dayLibur = 1;
                             break;
                         }
                     }
                     
                     // Cek apakah hari ini adalah Sabtu atau Minggu
                     $checkSabtuMinggu = Carbon::parse($currentDate)->translatedFormat('l');
                     if (in_array($checkSabtuMinggu, ['Sabtu', 'Minggu'])) {
                         $dayLibur = 1;
                     }

                    // Filter absen untuk tanggal ini
                    $absenForDate = $arrayAbsen->filter(function ($absen) use ($currentDate) {
                        return $absen->att_date === $currentDate;
                    })->sortBy('att_time')->values();
            
                    // Ambil start_time (waktu paling awal) dan end_time (waktu paling akhir)
                    $startTime = $absenForDate->isNotEmpty() ? $absenForDate->first()->att_time : null;
                    $endTime = $absenForDate->isNotEmpty() ? $absenForDate->last()->att_time : null;
                    $attendanceDaily = $attendanceDailyTeacher->firstWhere('att_date', $currentDate) ?? null;
                    $attendanceWfh = $attendanceDailyWfh->firstWhere('att_date', $currentDate) ?? null;
                    
                    if($startTime && $inTime){
                        $startTimeObj = Carbon::createFromFormat('H:i:s', $startTime, 'Asia/Jakarta');
                        $inTimeObjCheck = Carbon::createFromFormat('H:i:s', $inTime, 'Asia/Jakarta');
                        // $startTimePlus3 = $inTimeObjCheck->copy()->addHours(3)->addMinutes(59);
                        $startTimePlus3 = $inTimeObjCheck->copy()->addHours(5);

                        if($hasShiftToday === false && $dayLibur === 0 && $startTimeObj > $startTimePlus3){
                             $startTime = null;
                        }else if($hasShiftToday === false && $dayLibur === 1 && $startTimeObj > $startTimePlus3){
                             $startTime = null;
                        }else if($hasShiftToday === true && $dayLibur === 0 && $startTimeObj > $startTimePlus3){
                             $startTime = null;
                        }else if($absenForDate->isNotEmpty() && $hasShiftToday === false && $dayLibur === 0 && $startTimeObj->lessThanOrEqualTo($startTimePlus3)){
                             $startTime = $absenForDate->first()->att_time;
                        }else if($absenForDate->isNotEmpty() && $hasShiftToday === false && $dayLibur === 0 && $startTimeObj->lessThanOrEqualTo($startTimePlus3)){
                            $startTime = $absenForDate->first()->att_time;
                        }else if($absenForDate->isNotEmpty() && ($hasShiftToday === true || $dayLibur === 1)){
                            $startTime = $absenForDate->first()->att_time;
                        }else{
                            $startTime = null;
                        }
                    }

                    if($endTime && $outTime){
                        $endTimeObj = Carbon::createFromFormat('H:i:s', $endTime, 'Asia/Jakarta');
                        $outTimeObjCheck = Carbon::createFromFormat('H:i:s', $outTime, 'Asia/Jakarta');
                        $endTimeMinus3 = $outTimeObjCheck->copy()->subHours(4);

                        if($hasShiftToday === false && $dayLibur === 0 && $endTimeObj < $endTimeMinus3){
                            $endTime = null;
                        }else if($hasShiftToday === false && $dayLibur === 1 && $endTimeObj < $endTimeMinus3){
                            $endTime = null;
                        }else if($hasShiftToday === true && $dayLibur === 0 && $endTimeObj < $endTimeMinus3){
                            $endTime = null;
                        }else if($absenForDate->isNotEmpty() && $hasShiftToday === false && $dayLibur === 0 && $endTimeObj->greaterThanOrEqualTo($endTimeMinus3)){
                            $endTime = $absenForDate->last()->att_time;
                        }else if($absenForDate->isNotEmpty() && $hasShiftToday === false && $dayLibur === 0 && $endTimeObj->greaterThanOrEqualTo($endTimeMinus3)){
                            $endTime = $absenForDate->last()->att_time;
                        }else if($absenForDate->isNotEmpty() && ($hasShiftToday === true || $dayLibur === 1)){
                            $endTime = $absenForDate->last()->att_time;
                        }else{
                            $endTime = null;
                        }
                    }

                    // if($startTime){
                    //     $startTimeObj = \Carbon\Carbon::createFromFormat('H:i:s', $startTime);
                    //     $inTimeObjCheck = \Carbon\Carbon::createFromFormat('H:i:s', $inTime);
                    //     $startTimePlus3 = $inTimeObjCheck->copy()->addHours(2);

                    //     // Cek jika waktunya berada di atas jam 11:00 dan terdapat waktu kertentu
                    //     if(!$arrayJadwalShift->isEmpty() && $startTimeObj->lessThanOrEqualTo($startTimePlus3)) { 
                    //         $startTime = $absenForDate->isNotEmpty() ? $absenForDate->first()->att_time : null;
                    //     }else if($startTimeObj->hour > 11) {
                    //         $startTime = null;
                    //     }
                    // }

                    // if($endTime){
                    //     $endTimeObj = \Carbon\Carbon::createFromFormat('H:i:s', $endTime);
                    //     $outTimeObjCheck = \Carbon\Carbon::createFromFormat('H:i:s', $outTime);
                    //     $endTimeMinus3 = $outTimeObjCheck->copy()->subHours(2);
                    
                    //     // Cek apakah endTime masih dalam rentang outTime - 3 jam
                    //     if (!$arrayJadwalShift->isEmpty() && $endTimeObj->greaterThanOrEqualTo($endTimeMinus3)) { 
                    //         $endTime = $absenForDate->isNotEmpty() ? $absenForDate->last()->att_time : null;
                    //     } else if ($endTimeObj->hour < 11) {
                    //         $endTime = null;
                    //     }
                    // }
                    
                    // Jika ada data di attendanceDaily dan memiliki att_clock_in serta att_clock_out

                    if(!is_null($attendanceWfh) && 
                    $attendanceWfh->approve_hr === 1 && 
                    $attendanceWfh->approve_head_school === 1){
                        $startTime = $attendanceWfh->att_clock_in;
                        $endTime = $attendanceWfh->att_clock_out;
                    }elseif(!is_null($attendanceDaily) && 
                    $attendanceDaily->absent_type === "3" &&
                    !is_null($attendanceDaily->in_time) &&
                    is_null($attendanceDaily->out_time) &&
                    $attendanceDaily->tipe_koreksi === "masuk" &&
                    $attendanceDaily->telat_kurang_5 === "1" &&
                    $attendanceDaily->telat_lebih_5 === "0" &&
                    $attendanceDaily->pulang_kurang_5 === null &&
                    $attendanceDaily->pulang_lebih_5 === null &&
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                        $startTime = ($attendanceDaily->att_clock_in === "00:00:00") ? null : $attendanceDaily->att_clock_in;
                        $endTime = null;
                    }elseif(!is_null($attendanceDaily) && 
                    $attendanceDaily->absent_type === "3" &&
                    !is_null($attendanceDaily->in_time) &&
                    is_null($attendanceDaily->out_time) &&
                    $attendanceDaily->tipe_koreksi === "masuk" &&
                    $attendanceDaily->telat_kurang_5 === "0" &&
                    $attendanceDaily->telat_lebih_5 === "1" &&
                    $attendanceDaily->pulang_kurang_5 === null &&
                    $attendanceDaily->pulang_lebih_5 === null &&
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                        $startTime = ($attendanceDaily->att_clock_in === "00:00:00") ? null : $attendanceDaily->att_clock_in;
                        $endTime = null;
                    }elseif(!is_null($attendanceDaily) && 
                    $attendanceDaily->absent_type === "3" &&
                    is_null($attendanceDaily->in_time) &&
                    !is_null($attendanceDaily->out_time) &&
                    $attendanceDaily->tipe_koreksi === "pulang" &&
                    $attendanceDaily->telat_kurang_5 === null &&
                    $attendanceDaily->telat_lebih_5 === null &&
                    $attendanceDaily->pulang_kurang_5 === "1" &&
                    $attendanceDaily->pulang_lebih_5 === "0" &&
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                        $startTime = null;
                        $endTime = ($attendanceDaily->att_clock_out === "00:00:00") ? null : $attendanceDaily->att_clock_out;
                    }elseif(!is_null($attendanceDaily) && 
                    $attendanceDaily->absent_type === "3" &&
                    is_null($attendanceDaily->in_time) &&
                    !is_null($attendanceDaily->out_time) &&
                    $attendanceDaily->tipe_koreksi === "pulang" &&
                    $attendanceDaily->telat_kurang_5 === null &&
                    $attendanceDaily->telat_lebih_5 === null &&
                    $attendanceDaily->pulang_kurang_5 === "0" &&
                    $attendanceDaily->pulang_lebih_5 === "1" &&
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                        $startTime = null;
                        $endTime = ($attendanceDaily->att_clock_out === "00:00:00") ? null : $attendanceDaily->att_clock_out;
                    }elseif(!is_null($attendanceDaily) && 
                    $attendanceDaily->absent_type === "9" &&
                    ((is_null($attendanceDaily->in_time) && is_null($attendanceDaily->out_time)) || 
                    (!is_null($attendanceDaily->in_time) && is_null($attendanceDaily->out_time)) ||
                    (is_null($attendanceDaily->in_time) && !is_null($attendanceDaily->out_time))) &&
                    $attendanceDaily->total_koreksi >= 3 && 
                    $attendanceDaily->tipe_koreksi === "masuk_pulang_kampus" &&
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                        $startTime = $attendanceDaily->in_time;
                        $endTime = $attendanceDaily->out_time;
                    }elseif(!is_null($attendanceDaily) && 
                    $attendanceDaily->absent_type === "9" &&
                    ((is_null($attendanceDaily->in_time) && is_null($attendanceDaily->out_time)) || 
                    (!is_null($attendanceDaily->in_time) && is_null($attendanceDaily->out_time)) ||
                    (is_null($attendanceDaily->in_time) && !is_null($attendanceDaily->out_time))) &&
                    $attendanceDaily->total_koreksi < 3 && 
                    $attendanceDaily->tipe_koreksi === "masuk_pulang_kampus" &&
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                        $startTime = ($attendanceDaily->att_clock_in === "00:00:00") ? null : $attendanceDaily->att_clock_in;
                        $endTime = ($attendanceDaily->att_clock_out === "00:00:00") ? null : $attendanceDaily->att_clock_out;
                    }elseif(!is_null($attendanceDaily) && 
                    $attendanceDaily->absent_type === "9" &&
                    is_null($attendanceDaily->in_time) &&
                    is_null($attendanceDaily->out_time) &&
                    $attendanceDaily->total_koreksi > 1 && 
                    $attendanceDaily->total_koreksi < 3 &&
                    $attendanceDaily->tipe_koreksi === "masuk_kampus" &&
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                        $startTime = ($attendanceDaily->att_clock_in === "00:00:00") ? null : $attendanceDaily->att_clock_in;
                        $endTime = $attendanceDaily->out_time;
                    }elseif(!is_null($attendanceDaily) && 
                    $attendanceDaily->absent_type === "9" &&
                    is_null($attendanceDaily->in_time) &&
                    !is_null($attendanceDaily->out_time) &&
                    $attendanceDaily->total_koreksi > 1 && 
                    $attendanceDaily->total_koreksi < 3 &&
                    $attendanceDaily->tipe_koreksi === "masuk_kampus" &&
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                        $startTime = ($attendanceDaily->att_clock_in === "00:00:00") ? null : $attendanceDaily->att_clock_in;
                        $endTime = ($attendanceDaily->att_clock_out === "00:00:00") ? null : $attendanceDaily->att_clock_out;
                    }elseif(!is_null($attendanceDaily) && 
                    $attendanceDaily->absent_type === "9" &&
                    is_null($attendanceDaily->in_time) &&
                    is_null($attendanceDaily->out_time) &&
                    $attendanceDaily->total_koreksi > 1 && 
                    $attendanceDaily->total_koreksi < 3 &&
                    $attendanceDaily->tipe_koreksi === "pulang_kampus" &&
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                        $startTime = $attendanceDaily->in_time;
                        $endTime = ($attendanceDaily->att_clock_out === "00:00:00") ? null : $attendanceDaily->att_clock_out;
                    }elseif(!is_null($attendanceDaily) && 
                    $attendanceDaily->absent_type === "9" &&
                    !is_null($attendanceDaily->in_time) &&
                    is_null($attendanceDaily->out_time) &&
                    $attendanceDaily->total_koreksi > 1 && 
                    $attendanceDaily->total_koreksi < 3 &&
                    $attendanceDaily->tipe_koreksi === "pulang_kampus" &&
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                        $startTime = ($attendanceDaily->att_clock_in === "00:00:00") ? null : $attendanceDaily->att_clock_in;
                        $endTime = ($attendanceDaily->att_clock_out === "00:00:00") ? null : $attendanceDaily->att_clock_out;
                    }elseif(!is_null($attendanceDaily) && 
                    !is_null($attendanceDaily->att_clock_in) && 
                    !is_null($attendanceDaily->att_clock_out) && 
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                        $startTime = ($attendanceDaily->att_clock_in === "00:00:00") ? null : $attendanceDaily->att_clock_in;
                        $endTime = ($attendanceDaily->att_clock_out === "00:00:00") ? null : $attendanceDaily->att_clock_out;
                    }

                    // Menentukan nilai absen 1x
                    $absen_1x = 0;
                    if(!is_null($attendanceDaily) && 
                    !is_null($attendanceDaily->update) && 
                    $attendanceDaily->update === 1 &&  
                    $attendanceDaily->update_absen1x === 0 && 
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                        $absen_1x = 0; 
                    }elseif(!is_null($attendanceDaily) && 
                    !is_null($attendanceDaily->update) && 
                    $attendanceDaily->update === 1 &&  
                    $attendanceDaily->update_absen1x === 1 && 
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                       $absen_1x = 1;
                    }elseif(!is_null($attendanceDaily) && 
                    $attendanceDaily->absent_type === "3" &&
                    $dayLibur === 0 &&
                    !is_null($attendanceDaily->in_time) &&
                    is_null($attendanceDaily->out_time) &&
                    $attendanceDaily->tipe_koreksi === "masuk" &&
                    $attendanceDaily->telat_kurang_5 === "1" &&
                    $attendanceDaily->telat_lebih_5 === "0" &&
                    $attendanceDaily->pulang_kurang_5 === null &&
                    $attendanceDaily->pulang_lebih_5 === null &&
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                        $absen_1x = 1;
                    }elseif(!is_null($attendanceDaily) && 
                    $attendanceDaily->absent_type === "3" &&
                    $dayLibur === 0 &&
                    !is_null($attendanceDaily->in_time) &&
                    is_null($attendanceDaily->out_time) &&
                    $attendanceDaily->tipe_koreksi === "masuk" &&
                    $attendanceDaily->telat_kurang_5 === "0" &&
                    $attendanceDaily->telat_lebih_5 === "1" &&
                    $attendanceDaily->pulang_kurang_5 === null &&
                    $attendanceDaily->pulang_lebih_5 === null &&
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                        $absen_1x = 1;
                    }elseif(!is_null($attendanceDaily) && 
                    $attendanceDaily->absent_type === "3" &&
                    $dayLibur === 0 &&
                    is_null($attendanceDaily->in_time) &&
                    !is_null($attendanceDaily->out_time) &&
                    $attendanceDaily->tipe_koreksi === "pulang" &&
                    $attendanceDaily->telat_kurang_5 === null &&
                    $attendanceDaily->telat_lebih_5 === null &&
                    $attendanceDaily->pulang_kurang_5 === "1" &&
                    $attendanceDaily->pulang_lebih_5 === "0" &&
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                        $absen_1x = 1;
                    }elseif(!is_null($attendanceDaily) && 
                    $attendanceDaily->absent_type === "3" &&
                    $dayLibur === 0 &&
                    is_null($attendanceDaily->in_time) &&
                    !is_null($attendanceDaily->out_time) &&
                    $attendanceDaily->tipe_koreksi === "pulang" &&
                    $attendanceDaily->telat_kurang_5 === null &&
                    $attendanceDaily->telat_lebih_5 === null &&
                    $attendanceDaily->pulang_kurang_5 === "0" &&
                    $attendanceDaily->pulang_lebih_5 === "1" &&
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                        $absen_1x = 1;
                    }elseif(!is_null($startTime) && !is_null($endTime)) {
                        $absen_1x = 0; 
                    }elseif(is_null($startTime) && !is_null($endTime) && $dayLibur === 1) {
                        $absen_1x = 0; 
                    }elseif(!is_null($startTime) && is_null($endTime) && $dayLibur === 1) {
                        $absen_1x = 0; 
                    }elseif(is_null($startTime) && !is_null($endTime) && $dayLibur === 0) {
                        $absen_1x = 1; 
                    }elseif(!is_null($startTime) && is_null($endTime) && $dayLibur === 0) {
                        $absen_1x = 1; 
                    }elseif(!is_null($startTime) || !is_null($endTime)) {
                        $absen_1x = 1; 
                    }

                    // Menentukan nilai kehadiran
                    if(!is_null($attendanceDaily) && 
                    !is_null($attendanceDaily->update) && 
                    $attendanceDaily->update === 1 &&  
                    $attendanceDaily->update_kehadiran === 0 && 
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                        $kehadiran = 0; 
                    }elseif(!is_null($attendanceDaily) && 
                    !is_null($attendanceDaily->update) && 
                    $attendanceDaily->update === 1 &&  
                    $attendanceDaily->update_kehadiran === 1 && 
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                       $kehadiran = 1;
                    }elseif($dayLibur === 0 &&
                    ((!is_null($startTime) && is_null($endTime)) || 
                    (is_null($startTime) && !is_null($endTime)))){
                        $kehadiran = 1;
                    }elseif(!is_null($attendanceDaily) && 
                    $attendanceDaily->absent_type === "3" &&
                    !is_null($attendanceDaily->in_time) &&
                    is_null($attendanceDaily->out_time) &&
                    $attendanceDaily->tipe_koreksi === "masuk" &&
                    $attendanceDaily->telat_kurang_5 === "1" &&
                    $attendanceDaily->telat_lebih_5 === "0" &&
                    $attendanceDaily->pulang_kurang_5 === null &&
                    $attendanceDaily->pulang_lebih_5 === null &&
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                        $kehadiran = 0;
                    }elseif(!is_null($attendanceDaily) && 
                    $attendanceDaily->absent_type === "3" &&
                    !is_null($attendanceDaily->in_time) &&
                    is_null($attendanceDaily->out_time) &&
                    $attendanceDaily->tipe_koreksi === "masuk" &&
                    $attendanceDaily->telat_kurang_5 === "0" &&
                    $attendanceDaily->telat_lebih_5 === "1" &&
                    $attendanceDaily->pulang_kurang_5 === null &&
                    $attendanceDaily->pulang_lebih_5 === null &&
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                        $kehadiran = 0;
                    }elseif(!is_null($attendanceDaily) && 
                    $attendanceDaily->absent_type === "3" &&
                    is_null($attendanceDaily->in_time) &&
                    !is_null($attendanceDaily->out_time) &&
                    $attendanceDaily->tipe_koreksi === "pulang" &&
                    $attendanceDaily->telat_kurang_5 === null &&
                    $attendanceDaily->telat_lebih_5 === null &&
                    $attendanceDaily->pulang_kurang_5 === "1" &&
                    $attendanceDaily->pulang_lebih_5 === "0" &&
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                        $kehadiran = 0;
                    }elseif(!is_null($attendanceDaily) && 
                    $attendanceDaily->absent_type === "3" &&
                    is_null($attendanceDaily->in_time) &&
                    !is_null($attendanceDaily->out_time) &&
                    $attendanceDaily->tipe_koreksi === "pulang" &&
                    $attendanceDaily->telat_kurang_5 === null &&
                    $attendanceDaily->telat_lebih_5 === null &&
                    $attendanceDaily->pulang_kurang_5 === "0" &&
                    $attendanceDaily->pulang_lebih_5 === "1" &&
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                        $kehadiran = 0;
                    }elseif(!is_null($attendanceDaily) && 
                    $dayLibur === 1 &&
                    $attendanceDaily->approve_hr === 1 && 
                    $attendanceDaily->approve_head_school === 1){
                        $kehadiran = 1;
                    }elseif(!is_null($attendanceDaily) && 
                    $dayLibur === 1 &&
                    $attendanceDaily->approve_hr === 0 && 
                    $attendanceDaily->approve_head_school === 1){
                        $kehadiran = 0;
                    }elseif(!is_null($attendanceDaily) && 
                    $dayLibur === 1 &&
                    $attendanceDaily->approve_hr === 0 && 
                    $attendanceDaily->approve_head_school === 0){
                        $kehadiran = 0;
                    }elseif(is_null($attendanceDaily) && 
                    $dayLibur === 1){
                        $kehadiran = 0;
                    }elseif(is_null($startTime) && is_null($endTime) && is_null($attendanceDaily)){
                        $kehadiran = 0;
                    }elseif(!is_null($startTime) && !is_null($endTime) && is_null($attendanceDaily)) {
                        $kehadiran = 1;
                    }elseif(!is_null($startTime) && !is_null($endTime) && !is_null($attendanceDaily)) {
                        $kehadiran = 1;
                    }
                    // elseif(((!is_null($startTime) && is_null($endTime)) || (is_null($startTime) && !is_null($endTime)) ) && is_null($attendanceDaily)) {
                    //     $kehadiran = 1;
                    // }elseif(((!is_null($startTime) && is_null($endTime)) || (is_null($startTime) && !is_null($endTime)) ) && !is_null($attendanceDaily)) {
                    //     $kehadiran = 1;
                    // }
                    elseif(is_null($startTime) && is_null($endTime) && !is_null($attendanceDaily)) {
                        $kehadiran = $attendanceDaily->absent_type;
                    }else{
                        $kehadiran = 0;
                    }
                    
                    // Tambahkan data ke array dateRange
                    $dateRange[] = [
                        'id_head_school' => optional($headTeacherSchool)->id ?? '',
                        'date' => $currentDate,
                        'day' => $startDate->translatedFormat('l'),
                        'day_libur' => $dayLibur,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'absen_1x' => $absen_1x,
                        'kehadiran' => $kehadiran,
                        'absen' => $endTime,
                        'duration_attendance' => ($startTime && $endTime) 
                        ? \Carbon\Carbon::createFromFormat('H:i:s', $startTime)
                            ->diff(\Carbon\Carbon::createFromFormat('H:i:s', $endTime))
                            ->format('%H:%I:%S') 
                        : null,
                       'arrive_five_minutes' => ($startTime) ? (function () use ($startTime, $inTime, $attendanceDaily, $dayLibur, $currentDate, $attendanceWfh){
                        $startTimeObj = Carbon::createFromFormat('H:i:s', $startTime);
                        $inTimeObj = Carbon::createFromFormat('H:i:s', $inTime);

                        // Cek apakah hari Sabtu/Minggu atau libur
                        $hariIni = Carbon::parse($currentDate)->translatedFormat('l');

                        if(!is_null($attendanceWfh) && $attendanceWfh->approve_hr === 1 && $attendanceWfh->approve_head_school === 1){
                            return 0;   
                        }

                        if(!is_null($attendanceDaily) && !is_null($attendanceDaily->update) && $attendanceDaily->update === 1 &&  $attendanceDaily->update_arrive === 0 && 
                        $attendanceDaily->approve_hr === 1 && $attendanceDaily->approve_head_school === 1){
                            return 0;   
                        }

                        if(!is_null($attendanceDaily) && !is_null($attendanceDaily->update) && $attendanceDaily->update === 1 &&  $attendanceDaily->update_arrive === 1 && 
                        $attendanceDaily->approve_hr === 1 && $attendanceDaily->approve_head_school === 1){
                            return 1;   
                        }

                        if(!is_null($attendanceDaily) && !is_null($attendanceDaily->update) && $attendanceDaily->update === 1 &&  $attendanceDaily->update_arrive === 2 && 
                        $attendanceDaily->approve_hr === 1 && $attendanceDaily->approve_head_school === 1){
                            return 2;   
                        }

                        if(!is_null($attendanceDaily) && ($attendanceDaily->absent_type === "10" || $attendanceDaily->absent_type === "10_hr" ) && 
                        $attendanceDaily->approve_hr === 1 && $attendanceDaily->approve_head_school === 1){
                            return 0;   
                        }
                        
                        if ($dayLibur === 1 || in_array($hariIni, ['Sabtu', 'Minggu'])) {
                            return 0;
                        }

                        // Kondisi untuk 2 koreksi absen 
                        if(!is_null($attendanceDaily) && 
                        $attendanceDaily->absent_type === "3" &&
                        !is_null($attendanceDaily->in_time) &&
                        is_null($attendanceDaily->out_time) &&
                        $attendanceDaily->tipe_koreksi === "masuk" &&
                        $attendanceDaily->telat_kurang_5 === "1" &&
                        $attendanceDaily->telat_lebih_5 === "0" &&
                        $attendanceDaily->pulang_kurang_5 === null &&
                        $attendanceDaily->pulang_lebih_5 === null &&
                        $attendanceDaily->approve_hr === 1 && 
                        $attendanceDaily->approve_head_school === 1){
                            return 0;
                        }

                        if(!is_null($attendanceDaily) && 
                        $attendanceDaily->absent_type === "3" &&
                        !is_null($attendanceDaily->in_time) &&
                        is_null($attendanceDaily->out_time) &&
                        $attendanceDaily->tipe_koreksi === "masuk" &&
                        $attendanceDaily->telat_kurang_5 === "0" &&
                        $attendanceDaily->telat_lebih_5 === "1" &&
                        $attendanceDaily->pulang_kurang_5 === null &&
                        $attendanceDaily->pulang_lebih_5 === null &&
                        $attendanceDaily->approve_hr === 1 && 
                        $attendanceDaily->approve_head_school === 1){
                            return 0;
                        }

                        if(!is_null($attendanceDaily) && 
                        $attendanceDaily->absent_type === "3" &&
                        !is_null($attendanceDaily->in_time) &&
                        !is_null($attendanceDaily->out_time) &&
                        $attendanceDaily->tipe_koreksi === "masuk" &&
                        $attendanceDaily->telat_kurang_5 === "1" &&
                        $attendanceDaily->telat_lebih_5 === "0" &&
                        $attendanceDaily->pulang_kurang_5 === "0" &&
                        $attendanceDaily->pulang_lebih_5 === "0" &&
                        $attendanceDaily->approve_hr === 1 && 
                        $attendanceDaily->approve_head_school === 1){
                            return 0;
                        }

                        // Kondisi untuk 2 koreksi absen 
                        if(!is_null($attendanceDaily) && 
                        $attendanceDaily->absent_type === "3" &&
                        !is_null($attendanceDaily->in_time) &&
                        !is_null($attendanceDaily->out_time) &&
                        $attendanceDaily->tipe_koreksi === "masuk" &&
                        $attendanceDaily->telat_kurang_5 === "0" &&
                        $attendanceDaily->telat_lebih_5 === "1" &&
                        $attendanceDaily->pulang_kurang_5 === "0" &&
                        $attendanceDaily->pulang_lebih_5 === "0" &&
                        $attendanceDaily->approve_hr === 1 && 
                        $attendanceDaily->approve_head_school === 1){
                            return 0;
                        }

                        // Kondisi untuk 2 koreksi absen 
                        if(!is_null($attendanceDaily) && 
                        $attendanceDaily->absent_type === "3" &&
                        !is_null($attendanceDaily->in_time) &&
                        !is_null($attendanceDaily->out_time) &&
                        $attendanceDaily->tipe_koreksi === "masuk" &&
                        $attendanceDaily->telat_kurang_5 === "1" &&
                        $attendanceDaily->telat_lebih_5 === "0" &&
                        $attendanceDaily->pulang_kurang_5 === "1" &&
                        $attendanceDaily->pulang_lebih_5 === "0" &&
                        $attendanceDaily->approve_hr === 1 && 
                        $attendanceDaily->approve_head_school === 1){
                            return 0;
                        }
                          
                        // Kondisi untuk 2 koreksi absen 
                        if(!is_null($attendanceDaily) && 
                        $attendanceDaily->absent_type === "3" &&
                        !is_null($attendanceDaily->in_time) &&
                        !is_null($attendanceDaily->out_time) &&
                        $attendanceDaily->tipe_koreksi === "masuk" &&
                        $attendanceDaily->telat_kurang_5 === "1" &&
                        $attendanceDaily->telat_lebih_5 === "0" &&
                        $attendanceDaily->pulang_kurang_5 === "0" &&
                        $attendanceDaily->pulang_lebih_5 === "1" &&
                        $attendanceDaily->approve_hr === 1 && 
                        $attendanceDaily->approve_head_school === 1){
                            return 0;
                        }

                        // Kondisi untuk 2 koreksi absen 
                        if(!is_null($attendanceDaily) && 
                        $attendanceDaily->absent_type === "3" &&
                        !is_null($attendanceDaily->in_time) &&
                        !is_null($attendanceDaily->out_time) &&
                        $attendanceDaily->tipe_koreksi === "masuk" &&
                        $attendanceDaily->telat_kurang_5 === "0" &&
                        $attendanceDaily->telat_lebih_5 === "1" &&
                        $attendanceDaily->pulang_kurang_5 === "1" &&
                        $attendanceDaily->pulang_lebih_5 === "0" &&
                        $attendanceDaily->approve_hr === 1 && 
                        $attendanceDaily->approve_head_school === 1){
                            return 0;
                        }
 
                        // Kondisi untuk 2 koreksi absen 
                        if(!is_null($attendanceDaily) && 
                        $attendanceDaily->absent_type === "3" &&
                        !is_null($attendanceDaily->in_time) &&
                        !is_null($attendanceDaily->out_time) &&
                        $attendanceDaily->tipe_koreksi === "masuk" &&
                        $attendanceDaily->telat_kurang_5 === "0" &&
                        $attendanceDaily->telat_lebih_5 === "1" &&
                        $attendanceDaily->pulang_kurang_5 === "0" &&
                        $attendanceDaily->pulang_lebih_5 === "1" &&
                        $attendanceDaily->approve_hr === 1 && 
                        $attendanceDaily->approve_head_school === 1){
                            return 0;
                        }
                        
                        ////////////////////////////////////////////
                         // Kondisi untuk 2 koreksi absen 
                         if(!is_null($attendanceDaily) && 
                         $attendanceDaily->absent_type === "3" &&
                         is_null($attendanceDaily->in_time) &&
                         !is_null($attendanceDaily->out_time) &&
                         $attendanceDaily->tipe_koreksi === "pulang" &&
                         $attendanceDaily->telat_kurang_5 === null &&
                         $attendanceDaily->telat_lebih_5 === null &&
                         $attendanceDaily->pulang_kurang_5 === "1" &&
                         $attendanceDaily->pulang_lebih_5 === "0" &&
                         $attendanceDaily->approve_hr === 1 && 
                         $attendanceDaily->approve_head_school === 1){
                             return 0;
                         }

                         if(!is_null($attendanceDaily) && 
                         $attendanceDaily->absent_type === "3" &&
                         is_null($attendanceDaily->in_time) &&
                         !is_null($attendanceDaily->out_time) &&
                         $attendanceDaily->tipe_koreksi === "pulang" &&
                         $attendanceDaily->telat_kurang_5 === null &&
                         $attendanceDaily->telat_lebih_5 === null &&
                         $attendanceDaily->pulang_kurang_5 === "0" &&
                         $attendanceDaily->pulang_lebih_5 === "1" &&
                         $attendanceDaily->approve_hr === 1 && 
                         $attendanceDaily->approve_head_school === 1){
                             return 0;
                         }

                         if(!is_null($attendanceDaily) && 
                         $attendanceDaily->absent_type === "3" &&
                         !is_null($attendanceDaily->in_time) &&
                         is_null($attendanceDaily->out_time) &&
                         $attendanceDaily->tipe_koreksi === "pulang" &&
                         $attendanceDaily->telat_kurang_5 === "1" &&
                         $attendanceDaily->telat_lebih_5 === "0" &&
                         $attendanceDaily->pulang_kurang_5 === null &&
                         $attendanceDaily->pulang_lebih_5 === null &&
                         $attendanceDaily->approve_hr === 1 && 
                         $attendanceDaily->approve_head_school === 1){
                             return 1;
                         }

                         if(!is_null($attendanceDaily) && 
                         $attendanceDaily->absent_type === "3" &&
                         !is_null($attendanceDaily->in_time) &&
                         is_null($attendanceDaily->out_time) &&
                         $attendanceDaily->tipe_koreksi === "pulang" &&
                         $attendanceDaily->telat_kurang_5 === "0" &&
                         $attendanceDaily->telat_lebih_5 === "1" &&
                         $attendanceDaily->pulang_kurang_5 === null &&
                         $attendanceDaily->pulang_lebih_5 === null &&
                         $attendanceDaily->approve_hr === 1 && 
                         $attendanceDaily->approve_head_school === 1){
                             return 2;
                         }

                         if(!is_null($attendanceDaily) && 
                         $attendanceDaily->absent_type === "3" &&
                         !is_null($attendanceDaily->in_time) &&
                         !is_null($attendanceDaily->out_time) &&
                         $attendanceDaily->tipe_koreksi === "pulang" &&
                         $attendanceDaily->telat_kurang_5 === "0" &&
                         $attendanceDaily->telat_lebih_5 === "0" &&
                         $attendanceDaily->pulang_kurang_5 === "1" &&
                         $attendanceDaily->pulang_lebih_5 === "0" &&
                         $attendanceDaily->approve_hr === 1 && 
                         $attendanceDaily->approve_head_school === 1){
                             return 0;
                         }

                         // Kondisi untuk 2 koreksi absen 
                         if(!is_null($attendanceDaily) && 
                         $attendanceDaily->absent_type === "3" &&
                         !is_null($attendanceDaily->in_time) &&
                         !is_null($attendanceDaily->out_time) &&
                         $attendanceDaily->tipe_koreksi === "pulang" &&
                         $attendanceDaily->telat_kurang_5 === "0" &&
                         $attendanceDaily->telat_lebih_5 === "0" &&
                         $attendanceDaily->pulang_kurang_5 === "0" &&
                         $attendanceDaily->pulang_lebih_5 === "1" &&
                         $attendanceDaily->approve_hr === 1 && 
                         $attendanceDaily->approve_head_school === 1){
                             return 0;
                         }

                         // Kondisi untuk 2 koreksi absen 
                         if(!is_null($attendanceDaily) && 
                         $attendanceDaily->absent_type === "3" &&
                         !is_null($attendanceDaily->in_time) &&
                         !is_null($attendanceDaily->out_time) &&
                         $attendanceDaily->tipe_koreksi === "pulang" &&
                         $attendanceDaily->telat_kurang_5 === "1" &&
                         $attendanceDaily->telat_lebih_5 === "0" &&
                         $attendanceDaily->pulang_kurang_5 === "1" &&
                         $attendanceDaily->pulang_lebih_5 === "0" &&
                         $attendanceDaily->approve_hr === 1 && 
                         $attendanceDaily->approve_head_school === 1){
                             return 1;
                         }
                         
                         // Kondisi untuk 2 koreksi absen 
                         if(!is_null($attendanceDaily) && 
                         $attendanceDaily->absent_type === "3" &&
                         !is_null($attendanceDaily->in_time) &&
                         !is_null($attendanceDaily->out_time) &&
                         $attendanceDaily->tipe_koreksi === "pulang" &&
                         $attendanceDaily->telat_kurang_5 === "1" &&
                         $attendanceDaily->telat_lebih_5 === "0" &&
                         $attendanceDaily->pulang_kurang_5 === "0" &&
                         $attendanceDaily->pulang_lebih_5 === "1" &&
                         $attendanceDaily->approve_hr === 1 && 
                         $attendanceDaily->approve_head_school === 1){
                             return 1;
                         }

                         // Kondisi untuk 2 koreksi absen 
                         if(!is_null($attendanceDaily) && 
                         $attendanceDaily->absent_type === "3" &&
                         !is_null($attendanceDaily->in_time) &&
                         !is_null($attendanceDaily->out_time) &&
                         $attendanceDaily->tipe_koreksi === "pulang" &&
                         $attendanceDaily->telat_kurang_5 === "0" &&
                         $attendanceDaily->telat_lebih_5 === "1" &&
                         $attendanceDaily->pulang_kurang_5 === "1" &&
                         $attendanceDaily->pulang_lebih_5 === "0" &&
                         $attendanceDaily->approve_hr === 1 && 
                         $attendanceDaily->approve_head_school === 1){
                             return 2;
                         }
 
                         // Kondisi untuk 2 koreksi absen 
                         if(!is_null($attendanceDaily) && 
                         $attendanceDaily->absent_type === "3" &&
                         !is_null($attendanceDaily->in_time) &&
                         !is_null($attendanceDaily->out_time) &&
                         $attendanceDaily->tipe_koreksi === "pulang" &&
                         $attendanceDaily->telat_kurang_5 === "0" &&
                         $attendanceDaily->telat_lebih_5 === "1" &&
                         $attendanceDaily->pulang_kurang_5 === "0" &&
                         $attendanceDaily->pulang_lebih_5 === "1" &&
                         $attendanceDaily->approve_hr === 1 && 
                         $attendanceDaily->approve_head_school === 1){
                             return 2;
                         }

                        // Cek jika startTime di bawah jam masuk
                        if ($startTimeObj->lessThan($inTimeObj)) {
                            return 0; 
                        }

                        if ($startTimeObj->equalTo($inTimeObj)) {
                            return 0; 
                        }

                        if(!is_null($attendanceDaily) && $attendanceDaily->approve_hr === 1 && $attendanceDaily->approve_head_school === 1){
                            return 0;
                        }
                        
                        // Tidak telat (07:00:00 s.d. 07:00:59)
                        if ($startTimeObj->greaterThanOrEqualTo($inTimeObj) && $startTimeObj->lessThan($inTimeObj->copy()->addMinute())) {
                            return 0;
                        }

                        // Telat ringan (07:01:00 s.d. 07:05:00)
                        if ($startTimeObj->greaterThanOrEqualTo($inTimeObj->copy()->addMinute()) && $startTimeObj->lessThanOrEqualTo($inTimeObj->copy()->addMinutes(5))) {
                            return 1;
                        }

                        return 2; 
                        })() : null,
                        'late_five_minutes' => ($endTime) ? (function () use ($startTime, $endTime, $outTime, $attendanceDaily, $dayLibur, $currentDate, $attendanceWfh) {
                            $endTimeObj = Carbon::createFromFormat('H:i:s', $endTime);
                            $outTimeObj = Carbon::createFromFormat('H:i:s', $outTime);

                            $durationInHours = ($startTime && $endTime) ? 
                            \Carbon\Carbon::createFromFormat('H:i:s', $startTime)
                            ->diff(\Carbon\Carbon::createFromFormat('H:i:s', $endTime))
                            ->format('%H:%I:%S') 
                            : null;
                            $duration = "05:00:00";

                            // if(!is_null($attendanceDaily) && ($attendanceDaily->absent_type === "10" || $attendanceDaily->absent_type === "10_hr" ) && 
                            // $attendanceDaily->approve_hr === 1 && $attendanceDaily->approve_head_school === 1){
                            //     return 0;
                            // }

                            // if($durationInHours < $duration && $dayLibur === 1 && !is_null($attendanceDaily) && 
                            // $attendanceDaily->absent_type === "9" && $attendanceDaily->total_koreksi < 3 && 
                            // ($attendanceDaily->pulang_kurang_5 === "1" || (is_null($attendanceDaily->in_time) && is_null($attendanceDaily->out_time)) || (!is_null($attendanceDaily->in_time) && is_null($attendanceDaily->out_time)) || (is_null($attendanceDaily->in_time) && !is_null($attendanceDaily->out_time))) && 
                            // $attendanceDaily->approve_hr === 1 && $attendanceDaily->approve_head_school === 1
                            // ){
                            //     return 0;
                            // }

                            // if ($durationInHours < $duration && $dayLibur === 1 && !is_null($attendanceDaily) && 
                            // $attendanceDaily->absent_type === "9" && $attendanceDaily->total_koreksi < 3 && $attendanceDaily->pulang_kurang_5 === "0" && 
                            // $attendanceDaily->approve_hr === 1 && $attendanceDaily->approve_head_school === 1
                            // ) {
                            //     return 0;
                            // }

                            // if ($durationInHours < $duration && $dayLibur === 1 && !is_null($attendanceDaily) && 
                            // $attendanceDaily->absent_type === "9" && $attendanceDaily->total_koreksi < 3 && $attendanceDaily->pulang_kurang_5 === "1" && 
                            // $attendanceDaily->approve_hr === 1 && $attendanceDaily->approve_head_school === 1
                            // ) {
                            //     return 0;
                            // }


                            if(!is_null($attendanceWfh) && $attendanceWfh->approve_hr === 1 && $attendanceWfh->approve_head_school === 1){
                                return 0;   
                            }

                            if(!is_null($attendanceDaily) && !is_null($attendanceDaily->update) && $attendanceDaily->update === 1 &&  $attendanceDaily->update_late === 0 && 
                            $attendanceDaily->approve_hr === 1 && $attendanceDaily->approve_head_school === 1){
                                return 0;   
                            }

                            if(!is_null($attendanceDaily) && !is_null($attendanceDaily->update) && $attendanceDaily->update === 1 &&  $attendanceDaily->update_late === 1 && 
                            $attendanceDaily->approve_hr === 1 && $attendanceDaily->approve_head_school === 1){
                                return 1;   
                            }

                            if(!is_null($attendanceDaily) && !is_null($attendanceDaily->update) && $attendanceDaily->update === 1 &&  $attendanceDaily->update_late === 2 && 
                            $attendanceDaily->approve_hr === 1 && $attendanceDaily->approve_head_school === 1){
                                return 2;   
                            }

                            if($dayLibur === 1 &&
                            is_null($attendanceDaily) &&
                            !is_null($startTime) &&
                            !is_null($endTime)){
                                return 0;
                            }

                            if($dayLibur === 1 &&
                            !is_null($attendanceDaily) && 
                            !is_null($attendanceDaily->in_time) &&
                            !is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->approve_hr === 0 && 
                            $attendanceDaily->approve_head_school === 0){
                                return 0;
                            }

                             if($dayLibur === 1 &&
                            !is_null($attendanceDaily) && 
                            !is_null($attendanceDaily->in_time) &&
                            !is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->approve_hr === 0 && 
                            $attendanceDaily->approve_head_school === 1){
                                return 0;
                            }

                            if($dayLibur === 1 &&
                            is_null($attendanceDaily) &&
                            is_null($startTime) &&
                            !is_null($endTime)){
                                return 0;
                            }

                            if($dayLibur === 1 &&
                            is_null($attendanceDaily) &&
                            !is_null($startTime) &&
                            is_null($endTime)){
                                return 0;
                            }

                            if($dayLibur === 1 &&
                            !is_null($attendanceDaily) && 
                            is_null($attendanceDaily->in_time) &&
                            !is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->approve_hr === 0 && 
                            $attendanceDaily->approve_head_school === 0){
                                return 0;
                            }

                            if($dayLibur === 1 &&
                            !is_null($attendanceDaily) && 
                            is_null($attendanceDaily->in_time) &&
                            !is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->approve_hr === 0 && 
                            $attendanceDaily->approve_head_school === 1){
                                return 0;
                            }

                            if($dayLibur === 1 &&
                            !is_null($attendanceDaily) && 
                            !is_null($attendanceDaily->in_time) &&
                            is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->approve_hr === 0 && 
                            $attendanceDaily->approve_head_school === 0){
                                return 0;
                            }

                            if($dayLibur === 1 &&
                            !is_null($attendanceDaily) && 
                            !is_null($attendanceDaily->in_time) &&
                            is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->approve_hr === 0 && 
                            $attendanceDaily->approve_head_school === 1){
                                return 0;
                            }
                            

                            if($durationInHours < $duration && $dayLibur === 1 && !is_null($attendanceDaily) && $attendanceDaily->approve_hr === 0 && $attendanceDaily->approve_head_school === 0) {
                                return 1;
                            }

                            if($durationInHours < $duration && $dayLibur === 1 && !is_null($attendanceDaily) && $attendanceDaily->approve_hr === 0 && $attendanceDaily->approve_head_school === 0) {
                                return 1;
                            }

                            if($durationInHours < $duration && $dayLibur === 1 && !is_null($attendanceDaily) && $attendanceDaily->approve_hr === 0 && $attendanceDaily->approve_head_school === 1) {
                                return 1;
                            }

                            if($durationInHours < $duration && $dayLibur === 1 && !is_null($attendanceDaily) && $attendanceDaily->approve_hr === 1 && $attendanceDaily->approve_head_school === 1) {
                                return 1;
                            }

                            if($durationInHours < $duration && $dayLibur === 1 && is_null($attendanceDaily)) {
                                return 1;
                            }

                             // Cek apakah hari Sabtu/Minggu atau libur
                            $hariIni = Carbon::parse($currentDate)->translatedFormat('l');
                            if ($dayLibur === 1 || in_array($hariIni, ['Sabtu', 'Minggu'])) {
                                return 0;
                            }

                            // Kondisi untuk 2 koreksi absen 
                            if(!is_null($attendanceDaily) && 
                            $attendanceDaily->absent_type === "3" &&
                            is_null($attendanceDaily->in_time) &&
                            !is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->tipe_koreksi === "pulang" &&
                            $attendanceDaily->telat_kurang_5 === null &&
                            $attendanceDaily->telat_lebih_5 === null &&
                            $attendanceDaily->pulang_kurang_5 === "1" &&
                            $attendanceDaily->pulang_lebih_5 === "0" &&
                            $attendanceDaily->approve_hr === 1 && 
                            $attendanceDaily->approve_head_school === 1){
                                return 0;
                            }

                            if(!is_null($attendanceDaily) && 
                            $attendanceDaily->absent_type === "3" &&
                            is_null($attendanceDaily->in_time) &&
                            !is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->tipe_koreksi === "pulang" &&
                            $attendanceDaily->telat_kurang_5 === null &&
                            $attendanceDaily->telat_lebih_5 === null &&
                            $attendanceDaily->pulang_kurang_5 === "0" &&
                            $attendanceDaily->pulang_lebih_5 === "1" &&
                            $attendanceDaily->approve_hr === 1 && 
                            $attendanceDaily->approve_head_school === 1){
                                return 0;
                            }

                            if(!is_null($attendanceDaily) && 
                            $attendanceDaily->absent_type === "3" &&
                            !is_null($attendanceDaily->in_time) &&
                            !is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->tipe_koreksi === "pulang" &&
                            $attendanceDaily->telat_kurang_5 === "0" &&
                            $attendanceDaily->telat_lebih_5 === "0" &&
                            $attendanceDaily->pulang_kurang_5 === "1" &&
                            $attendanceDaily->pulang_lebih_5 === "0" &&
                            $attendanceDaily->approve_hr === 1 && 
                            $attendanceDaily->approve_head_school === 1){
                                return 0;
                            }

                            // Kondisi untuk 2 koreksi absen 
                            if(!is_null($attendanceDaily) && 
                            $attendanceDaily->absent_type === "3" &&
                            !is_null($attendanceDaily->in_time) &&
                            !is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->tipe_koreksi === "pulang" &&
                            $attendanceDaily->telat_kurang_5 === "0" &&
                            $attendanceDaily->telat_lebih_5 === "0" &&
                            $attendanceDaily->pulang_kurang_5 === "0" &&
                            $attendanceDaily->pulang_lebih_5 === "1" &&
                            $attendanceDaily->approve_hr === 1 && 
                            $attendanceDaily->approve_head_school === 1){
                                return 0;
                            }

                            // Kondisi untuk 2 koreksi absen 
                            if(!is_null($attendanceDaily) && 
                            $attendanceDaily->absent_type === "3" &&
                            !is_null($attendanceDaily->in_time) &&
                            !is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->tipe_koreksi === "pulang" &&
                            $attendanceDaily->telat_kurang_5 === "1" &&
                            $attendanceDaily->telat_lebih_5 === "0" &&
                            $attendanceDaily->pulang_kurang_5 === "1" &&
                            $attendanceDaily->pulang_lebih_5 === "0" &&
                            $attendanceDaily->approve_hr === 1 && 
                            $attendanceDaily->approve_head_school === 1){
                                return 0;
                            }
                            
                            // Kondisi untuk 2 koreksi absen 
                            if(!is_null($attendanceDaily) && 
                            $attendanceDaily->absent_type === "3" &&
                            !is_null($attendanceDaily->in_time) &&
                            !is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->tipe_koreksi === "pulang" &&
                            $attendanceDaily->telat_kurang_5 === "1" &&
                            $attendanceDaily->telat_lebih_5 === "0" &&
                            $attendanceDaily->pulang_kurang_5 === "0" &&
                            $attendanceDaily->pulang_lebih_5 === "1" &&
                            $attendanceDaily->approve_hr === 1 && 
                            $attendanceDaily->approve_head_school === 1){
                                return 0;
                            }

                            // Kondisi untuk 2 koreksi absen 
                            if(!is_null($attendanceDaily) && 
                            $attendanceDaily->absent_type === "3" &&
                            !is_null($attendanceDaily->in_time) &&
                            !is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->tipe_koreksi === "pulang" &&
                            $attendanceDaily->telat_kurang_5 === "0" &&
                            $attendanceDaily->telat_lebih_5 === "1" &&
                            $attendanceDaily->pulang_kurang_5 === "1" &&
                            $attendanceDaily->pulang_lebih_5 === "0" &&
                            $attendanceDaily->approve_hr === 1 && 
                            $attendanceDaily->approve_head_school === 1){
                                return 0;
                            }
    
                            // Kondisi untuk 2 koreksi absen 
                            if(!is_null($attendanceDaily) && 
                            $attendanceDaily->absent_type === "3" &&
                            !is_null($attendanceDaily->in_time) &&
                            !is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->tipe_koreksi === "pulang" &&
                            $attendanceDaily->telat_kurang_5 === "0" &&
                            $attendanceDaily->telat_lebih_5 === "1" &&
                            $attendanceDaily->pulang_kurang_5 === "0" &&
                            $attendanceDaily->pulang_lebih_5 === "1" &&
                            $attendanceDaily->approve_hr === 1 && 
                            $attendanceDaily->approve_head_school === 1){
                                return 0;
                            }

                            ///////////////////////////////////////////
                              // Kondisi untuk 2 koreksi absen 
                            if(!is_null($attendanceDaily) && 
                            $attendanceDaily->absent_type === "3" &&
                            !is_null($attendanceDaily->in_time) &&
                            is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->tipe_koreksi === "masuk" &&
                            $attendanceDaily->telat_kurang_5 === "1" &&
                            $attendanceDaily->telat_lebih_5 === "0" &&
                            $attendanceDaily->pulang_kurang_5 === null &&
                            $attendanceDaily->pulang_lebih_5 === null &&
                            $attendanceDaily->approve_hr === 1 && 
                            $attendanceDaily->approve_head_school === 1){
                                return 0;
                            }

                            if(!is_null($attendanceDaily) && 
                            $attendanceDaily->absent_type === "3" &&
                            !is_null($attendanceDaily->in_time) &&
                            is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->tipe_koreksi === "masuk" &&
                            $attendanceDaily->telat_kurang_5 === "0" &&
                            $attendanceDaily->telat_lebih_5 === "1" &&
                            $attendanceDaily->pulang_kurang_5 === null &&
                            $attendanceDaily->pulang_lebih_5 === null &&
                            $attendanceDaily->approve_hr === 1 && 
                            $attendanceDaily->approve_head_school === 1){
                                return 0;
                            }

                            if(!is_null($attendanceDaily) && 
                            $attendanceDaily->absent_type === "3" &&
                            is_null($attendanceDaily->in_time) &&
                            !is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->tipe_koreksi === "masuk" &&
                            $attendanceDaily->telat_kurang_5 === null &&
                            $attendanceDaily->telat_lebih_5 === null &&
                            $attendanceDaily->pulang_kurang_5 === "1" &&
                            $attendanceDaily->pulang_lebih_5 === "0" &&
                            $attendanceDaily->approve_hr === 1 && 
                            $attendanceDaily->approve_head_school === 1){
                                return 1;
                            }

                            if(!is_null($attendanceDaily) && 
                            $attendanceDaily->absent_type === "3" &&
                            is_null($attendanceDaily->in_time) &&
                            !is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->tipe_koreksi === "masuk" &&
                            $attendanceDaily->telat_kurang_5 === null &&
                            $attendanceDaily->telat_lebih_5 === null &&
                            $attendanceDaily->pulang_kurang_5 === "0" &&
                            $attendanceDaily->pulang_lebih_5 === "1" &&
                            $attendanceDaily->approve_hr === 1 && 
                            $attendanceDaily->approve_head_school === 1){
                                return 2;
                            }

                            if(!is_null($attendanceDaily) && 
                            $attendanceDaily->absent_type === "3" &&
                            !is_null($attendanceDaily->in_time) &&
                            !is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->tipe_koreksi === "masuk" &&
                            $attendanceDaily->telat_kurang_5 === "1" &&
                            $attendanceDaily->telat_lebih_5 === "0" &&
                            $attendanceDaily->pulang_kurang_5 === "0" &&
                            $attendanceDaily->pulang_lebih_5 === "0" &&
                            $attendanceDaily->approve_hr === 1 && 
                            $attendanceDaily->approve_head_school === 1){
                                return 0;
                            }

                            // Kondisi untuk 2 koreksi absen 
                            if(!is_null($attendanceDaily) && 
                            $attendanceDaily->absent_type === "3" &&
                            !is_null($attendanceDaily->in_time) &&
                            !is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->tipe_koreksi === "masuk" &&
                            $attendanceDaily->telat_kurang_5 === "0" &&
                            $attendanceDaily->telat_lebih_5 === "1" &&
                            $attendanceDaily->pulang_kurang_5 === "0" &&
                            $attendanceDaily->pulang_lebih_5 === "0" &&
                            $attendanceDaily->approve_hr === 1 && 
                            $attendanceDaily->approve_head_school === 1){
                                return 0;
                            }

                            // Kondisi untuk 2 koreksi absen 
                            if(!is_null($attendanceDaily) && 
                            $attendanceDaily->absent_type === "3" &&
                            !is_null($attendanceDaily->in_time) &&
                            !is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->tipe_koreksi === "masuk" &&
                            $attendanceDaily->telat_kurang_5 === "1" &&
                            $attendanceDaily->telat_lebih_5 === "0" &&
                            $attendanceDaily->pulang_kurang_5 === "1" &&
                            $attendanceDaily->pulang_lebih_5 === "0" &&
                            $attendanceDaily->approve_hr === 1 && 
                            $attendanceDaily->approve_head_school === 1){
                                return 1;
                            }
                            
                            // Kondisi untuk 2 koreksi absen 
                            if(!is_null($attendanceDaily) && 
                            $attendanceDaily->absent_type === "3" &&
                            !is_null($attendanceDaily->in_time) &&
                            !is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->tipe_koreksi === "masuk" &&
                            $attendanceDaily->telat_kurang_5 === "1" &&
                            $attendanceDaily->telat_lebih_5 === "0" &&
                            $attendanceDaily->pulang_kurang_5 === "0" &&
                            $attendanceDaily->pulang_lebih_5 === "1" &&
                            $attendanceDaily->approve_hr === 1 && 
                            $attendanceDaily->approve_head_school === 1){
                                return 2;
                            }

                            // Kondisi untuk 2 koreksi absen 
                            if(!is_null($attendanceDaily) && 
                            $attendanceDaily->absent_type === "3" &&
                            !is_null($attendanceDaily->in_time) &&
                            !is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->tipe_koreksi === "masuk" &&
                            $attendanceDaily->telat_kurang_5 === "0" &&
                            $attendanceDaily->telat_lebih_5 === "1" &&
                            $attendanceDaily->pulang_kurang_5 === "1" &&
                            $attendanceDaily->pulang_lebih_5 === "0" &&
                            $attendanceDaily->approve_hr === 1 && 
                            $attendanceDaily->approve_head_school === 1){
                                return 1;
                            }
    
                            // Kondisi untuk 2 koreksi absen 
                            if(!is_null($attendanceDaily) && 
                            $attendanceDaily->absent_type === "3" &&
                            !is_null($attendanceDaily->in_time) &&
                            !is_null($attendanceDaily->out_time) &&
                            $attendanceDaily->tipe_koreksi === "masuk" &&
                            $attendanceDaily->telat_kurang_5 === "0" &&
                            $attendanceDaily->telat_lebih_5 === "1" &&
                            $attendanceDaily->pulang_kurang_5 === "0" &&
                            $attendanceDaily->pulang_lebih_5 === "1" &&
                            $attendanceDaily->approve_hr === 1 && 
                            $attendanceDaily->approve_head_school === 1){
                                return 2;
                            }

                            // Cek jika endTime di bawah jam masuk
                            if ($endTimeObj->greaterThan($outTimeObj)) {
                                return 0; 
                            }

                            if ($endTimeObj->equalTo($outTimeObj)) {
                                return 0; 
                            }

                            if(!is_null($attendanceDaily) && $attendanceDaily->approve_hr === 1 && $attendanceDaily->approve_head_school === 1){
                                return 0;
                            }

                            // Cek jika startTime berada di antara jam masuk dan 5 menit setelahnya
                            if ($endTimeObj->lessThanOrEqualTo($outTimeObj) && $endTimeObj->greaterThan($outTimeObj->subMinutes(5))) {
                                return 1;
                            }
                            return 2; 
                            })() : null,
                            'in_schedule' => $inTime,
                            'out_schedule' => $outTime,
                        'attendance_daily' => $attendanceDailyTeacher->firstWhere('att_date', $currentDate) ?? null,
                    ];
                  $startDate->addDay();
                }

                $totalKehadiran = collect($dateRange)->sum(function ($day) {
                    return $day['kehadiran'] === 1 ? 1 : 0;
                });

                $totalKehadiranTanpaHariLibur = collect($dateRange)->sum(function ($day) {
                    return $day['kehadiran'] === 1 && $day['day_libur'] === 0 ? 1 : 0;
                });

                $totalAbsen1x = collect($dateRange)->sum(function ($day) {
                    return $day['absen_1x'] === 1 ? 1 : 0;
                });

                $totalDatangKurang5menit = collect($dateRange)->sum(function ($day) {
                    return $day['arrive_five_minutes'] === 1 ? 1 : 0;
                });

                $totalDatangLebih5menit = collect($dateRange)->sum(function ($day) {
                    return $day['arrive_five_minutes'] === 2 ? 1 : 0;
                });
                
                $totalPulangKurang5menit = collect($dateRange)->sum(function ($day) {
                    return $day['late_five_minutes'] === 1 ? 1 : 0;
                });
                
                $totalPulangLebih5menit = collect($dateRange)->sum(function ($day) {
                    return $day['late_five_minutes'] === 2 ? 1 : 0;
                });

                $totalHariKerja = collect($dateRange)->filter(function ($day) {
                    return $day['day_libur'] === 0 ? 1 : 0; 
                })->count();

                $totalLiburSabtuMinggu = collect($dateRange)->filter(function ($day) {
                    return in_array($day['day'], ['Sabtu', 'Minggu']); 
                })->count();

                $totalHariLibur = collect($dateRange)->filter(function ($day) {
                    return $day['day_libur'] === 1 && !in_array($day['day'], ['Sabtu', 'Minggu']);
                })->count();

                $totalKoreksiAbsen = collect($dateRange)->sum(function ($item) {
                    $attendanceDaily = $item['attendance_daily'] ?? null;
                    $absenType = $attendanceDaily->absent_type ?? null;
                    $inTime = $attendanceDaily->in_time ?? null;
                    $outTime = $attendanceDaily->out_time ?? null;
                    $telatKurang5 = $attendanceDaily->telat_kurang_5 ?? null;
                    $telatLebih5 = $attendanceDaily->telat_lebih_5 ?? null;
                    $pulangKurang5 = $attendanceDaily->pulang_kurang_5 ?? null;
                    $pulangLebih5 = $attendanceDaily->pulang_lebih_5 ?? null;
                    $tipeKoreksi = $attendanceDaily->tipe_koreksi ?? null;
                    $totalKoreksi = $attendanceDaily->total_koreksi ?? null;
                    $approveHeadSchool = $attendanceDaily->approve_head_school ?? null;
                    $approveHr = $attendanceDaily->approve_hr ?? null;
                    
                    $totalDatangKurang5menit = $item['arrive_five_minutes'] ?? null;
                    $totalPulangKurang5menit = $item['late_five_minutes'] ?? null;
                    // dd($totalDatangKurang5menit);
                    // Kondisi untuk mengurangi 2 kuota koreksi absen
                    if(
                        // Kondisi jika tipe absen dinas dalam kampus dengan aben_type nomor 9
                        ($absenType === "9" &&
                        is_null($inTime) && 
                        is_null($outTime) &&
                        $totalKoreksi < 3 &&
                        $tipeKoreksi === "masuk_pulang_kampus" 
                        ) 
                        ||
                        ($absenType === "9" &&
                        is_null($inTime) && 
                        is_null($outTime) &&
                        $totalKoreksi < 3 &&
                        $tipeKoreksi === "masuk_pulang_kampus" &&
                        $approveHeadSchool === 1 &&
                        $approveHr === 1
                        ) 
                        ||
                        ($absenType === "9" &&
                        is_null($inTime) && 
                        is_null($outTime) &&
                        $totalKoreksi >= 3 &&
                        $tipeKoreksi === "masuk_pulang_kampus" 
                        ) 
                        ||
                        ($absenType === "9" &&
                        is_null($inTime) && 
                        is_null($outTime) &&
                        $totalKoreksi >= 3 &&
                        $tipeKoreksi === "masuk_pulang_kampus" &&
                        $approveHeadSchool === 1 &&
                        $approveHr === 1
                        ) 
                        ||
                        // kondisi jika ada jam masuk telat kurang 5 menit dan pulang cepat kurang 5 menit
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === 1 &&
                        $totalPulangKurang5menit === 1 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "1" && 
                        $telatLebih5 === "0" &&
                        $pulangKurang5 === "1" &&
                        $pulangLebih5 === "0"
                        ) 
                        ||
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === 0 &&
                        $totalPulangKurang5menit === 0 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "1" && 
                        $telatLebih5 === "0" &&
                        $pulangKurang5 === "1" &&
                        $pulangLebih5 === "0" &&
                        $approveHeadSchool === 1 &&
                        $approveHr === 1
                        )  
                        ||
                        // kondisi jika ada jam masuk telat kurang 5 menit dan pulang cepat lebih 5 menit
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === 1 &&
                        $totalPulangKurang5menit === 2 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "1" && 
                        $telatLebih5 === "0" &&
                        $pulangKurang5 === "0" &&
                        $pulangLebih5 === "1"
                        ) 
                        ||
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === 0 &&
                        $totalPulangKurang5menit === 0 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "1" && 
                        $telatLebih5 === "0" &&
                        $pulangKurang5 === "0" &&
                        $pulangLebih5 === "1" &&
                        $approveHeadSchool === 1 &&
                        $approveHr === 1
                        )
                        ||
                        // kondisi jika ada jam masuk tapi datang telat kurang 5 menit dan pulang null
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        is_null($outTime) &&
                        $totalDatangKurang5menit === 1 &&
                        $totalPulangKurang5menit === null &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "1" && 
                        $telatLebih5 === "0" &&
                        $pulangKurang5 === null &&
                        $pulangLebih5 === null) 
                        ||
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        is_null($outTime) &&
                        $totalDatangKurang5menit === 0 &&
                        $totalPulangKurang5menit === 0 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "1" && 
                        $telatLebih5 === "0" &&
                        $pulangKurang5 === null &&
                        $pulangLebih5 === null &&
                        $approveHeadSchool === 1 &&
                        $approveHr === 1) 
                        ||
                        // kondisi jika ada jam masuk null dan pulang lebih cepat kurang 5 menit
                        ($absenType === "3" &&
                        is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === null &&
                        $totalPulangKurang5menit === 1 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === null && 
                        $telatLebih5 === null &&
                        $pulangKurang5 === "1" &&
                        $pulangLebih5 === "0"
                        ) 
                        ||
                        ($absenType === "3" &&
                        is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === 0 &&
                        $totalPulangKurang5menit === 0 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === null && 
                        $telatLebih5 === null &&
                        $pulangKurang5 === "1" &&
                        $pulangLebih5 === "0" &&
                        $approveHeadSchool === 1 &&
                        $approveHr === 1
                        ) 
                        || 
                        // kondisi jika ada jam masuk null dan pulang cepat lebih 5 menit
                        ($absenType === "3" &&
                        is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === null &&
                        $totalPulangKurang5menit === 2 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === null && 
                        $telatLebih5 === null &&
                        $pulangKurang5 === "0" &&
                        $pulangLebih5 === "1"
                        ) 
                        ||
                        ($absenType === "3" &&
                        is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === 0 &&
                        $totalPulangKurang5menit === 0 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === null && 
                        $telatLebih5 === null &&
                        $pulangKurang5 === "0" &&
                        $pulangLebih5 === "1" &&
                        $approveHeadSchool === 1 &&
                        $approveHr === 1
                        )  
                        || 
                        // kodisi jika jam masuk dan pulang sama-sama kosong
                        ($absenType === "3" &&
                        is_null($inTime) &&
                        is_null($outTime) &&
                        $totalDatangKurang5menit === null &&
                        $totalPulangKurang5menit === null &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === null && 
                        $telatLebih5 === null &&
                        $pulangKurang5 === null &&
                        $pulangLebih5 === null) 
                        ||
                        ($absenType === "3" &&
                        is_null($inTime) &&
                        is_null($outTime) &&
                        $totalDatangKurang5menit === 0 &&
                        $totalPulangKurang5menit === 0 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === null && 
                        $telatLebih5 === null &&
                        $pulangKurang5 === null &&
                        $pulangLebih5 === null &&
                        $approveHeadSchool === 1 &&
                        $approveHr === 1) 
                        ||
                        // kondisi jika ada jam masuk telat lebih 5 menit dan pulang cepat lebih 5 menit
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === 2 &&
                        $totalPulangKurang5menit === 1 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "0" && 
                        $telatLebih5 === "1" &&
                        $pulangKurang5 === "1" &&
                        $pulangLebih5 === "0"
                        ) 
                        ||
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === 0 &&
                        $totalPulangKurang5menit === 0 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "0" && 
                        $telatLebih5 === "1" &&
                        $pulangKurang5 === "1" &&
                        $pulangLebih5 === "0" &&
                        $approveHeadSchool === 1 &&
                        $approveHr === 1
                        )   
                        ||
                        // kondisi jika ada jam masuk telat lebih 5 menit dan pulang cepat lebih 5 menit
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === 2 &&
                        $totalPulangKurang5menit === 2 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "0" && 
                        $telatLebih5 === "1" &&
                        $pulangKurang5 === "0" &&
                        $pulangLebih5 === "1"
                        ) 
                        ||
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === 0 &&
                        $totalPulangKurang5menit === 0 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "0" && 
                        $telatLebih5 === "1" &&
                        $pulangKurang5 === "0" &&
                        $pulangLebih5 === "1" &&
                        $approveHeadSchool === 1 &&
                        $approveHr === 1
                        )  
                        ||
                        // kondisi jika ada jam masuk tapi datang telat lebih 5 menit dan pulang null
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        is_null($outTime) &&
                        $totalDatangKurang5menit === 2 &&
                        $totalPulangKurang5menit === null &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "0" && 
                        $telatLebih5 === "1" &&
                        $pulangKurang5 === null &&
                        $pulangLebih5 === null
                        ) 
                        ||
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        is_null($outTime) &&
                        $totalDatangKurang5menit === 0 &&
                        $totalPulangKurang5menit === 0 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "0" && 
                        $telatLebih5 === "1" &&
                        $pulangKurang5 === null &&
                        $pulangLebih5 === null &&
                        $approveHeadSchool === 1 &&
                        $approveHr === 1
                        )
                    ){
                        return 2;
                    }

                    if(($absenType === "9" && !is_null($inTime) && !is_null($outTime) && $tipeKoreksi === "masuk_pulang_kampus" && $pulangKurang5 === "0") ||
                       ($absenType === "9" && !is_null($inTime) && !is_null($outTime) && $tipeKoreksi === "masuk_pulang_kampus" && $pulangKurang5 === "0" && $approveHeadSchool === 1 && $approveHr === 1)
                    ){
                        return 0;
                    }

                    if(($absenType === "9" && !is_null($inTime) && !is_null($outTime) && $tipeKoreksi === "masuk_pulang_kampus" && $pulangKurang5 === "1") ||
                       ($absenType === "9" && !is_null($inTime) && !is_null($outTime) && $tipeKoreksi === "masuk_pulang_kampus" && $pulangKurang5 === "1" && $approveHeadSchool === 1 && $approveHr === 1)
                    ){
                        return 1;
                    }

                    // Kondisi 2: absenType dalam ["3", "4", "5"]
                    if (in_array($absenType, ["3", "4", "5", "9"])) {
                        return 1;
                    }
                    // Default
                    return 0;
                });

                $totalSakit = collect($dateRange)->sum(function ($item) {
                    $attendanceDaily = $item['attendance_daily'] ?? null;
                    $absenType = $attendanceDaily->absent_type ?? null;
                    $approveHeadSchool = $attendanceDaily->approve_head_school ?? null;
                    $approveHr = $attendanceDaily->approve_hr ?? null;
                    if (!is_null($attendanceDaily) && $absenType === "1" && $approveHeadSchool === 1 && $approveHr === 1) {
                        return 1; 
                    }
                    return 0;
                });

                $totalIzin = collect($dateRange)->sum(function ($item) {
                    $attendanceDaily = $item['attendance_daily'] ?? null;
                    $absenType = $attendanceDaily->absent_type ?? null;
                    $approveHeadSchool = $attendanceDaily->approve_head_school ?? null;
                    $approveHr = $attendanceDaily->approve_hr ?? null;
                    if (!is_null($attendanceDaily) && $absenType === "2" && $approveHeadSchool === 1 && $approveHr === 1) {
                        return 1; 
                    }
                    return 0;
                });

                // Total Dinas masih di 0 kan karna sudah tidak dipakai
                $totalDinas = collect($dateRange)->sum(function ($item) {
                    return 0;
                });

                // Total Cuti masih 0 karena belum ada fitur cuti
                $totalCuti = collect($dateRange)->sum(function ($item) {
                    return 0;
                });

                $totalAlpa = collect($dateRange)->sum(function ($day) {
                    $attendanceDaily = $day['attendance_daily'] ?? null;

                    // Pastikan kehadiran == 0
                    $isNotPresent = isset($day['kehadiran']) && $day['kehadiran'] === 0;

                    // Bukan hari Sabtu atau Minggu
                    $isWeekday = !in_array($day['day'], ['Sabtu', 'Minggu']);

                    // Bukan hari libur
                    $isNotHoliday = isset($day['day_libur']) && $day['day_libur'] === 0;

                    // Tidak termasuk sakit (absent_type == 1) atau izin (absent_type == 2) yang disetujui
                    $isNotSakitOrIzinApproved = true;
                    if (!is_null($attendanceDaily)) {
                        $absentType = $attendanceDaily->absent_type ?? null;
                        $approveHeadSchool = $attendanceDaily->approve_head_school ?? null;
                        $approveHr = $attendanceDaily->approve_hr ?? null;

                        if (in_array($absentType, ['1', '2']) && $approveHeadSchool == 1 && $approveHr == 1) {
                            $isNotSakitOrIzinApproved = false;
                        }
                    }
                    if ($isNotPresent && $isWeekday && $isNotHoliday && $isNotSakitOrIzinApproved) {
                        return 1;
                    }

                    return 0;
                });

                $totalKehadiranLibur = collect($dateRange)->sum(function ($day) {
                    $kehadiran = $day['kehadiran'] ?? null;

                    $isPresent = $kehadiran === 1;
                    $isWeekend = in_array($day['day'], ['Sabtu', 'Minggu']);
                    $isHoliday = isset($day['day_libur']) && $day['day_libur'] === 1;

                    if ($isPresent && ($isWeekend || $isHoliday)) {
                        return 1;
                    }

                    return 0;
                });

                $totalKoreksiAbsen = collect($dateRange)->sum(function ($item) {
                    $attendanceDaily = $item['attendance_daily'] ?? null;
                    $absenType = $attendanceDaily->absent_type ?? null;
                    $inTime = $attendanceDaily->in_time ?? null;
                    $outTime = $attendanceDaily->out_time ?? null;
                    $telatKurang5 = $attendanceDaily->telat_kurang_5 ?? null;
                    $telatLebih5 = $attendanceDaily->telat_lebih_5 ?? null;
                    $pulangKurang5 = $attendanceDaily->pulang_kurang_5 ?? null;
                    $pulangLebih5 = $attendanceDaily->pulang_lebih_5 ?? null;
                    $tipeKoreksi = $attendanceDaily->tipe_koreksi ?? null;
                    $totalKoreksi = $attendanceDaily->total_koreksi ?? null;
                    $approveHeadSchool = $attendanceDaily->approve_head_school ?? null;
                    $approveHr = $attendanceDaily->approve_hr ?? null;
                    
                    $totalDatangKurang5menit = $item['arrive_five_minutes'] ?? null;
                    $totalPulangKurang5menit = $item['late_five_minutes'] ?? null;
                    // dd($totalDatangKurang5menit);
                    // Kondisi untuk mengurangi 2 kuota koreksi absen
                    if(
                        // Kondisi jika tipe absen dinas dalam kampus dengan aben_type nomor 9
                        ($absenType === "9" &&
                        is_null($inTime) && 
                        is_null($outTime) &&
                        $totalKoreksi < 3 &&
                        $tipeKoreksi === "masuk_pulang_kampus" 
                        ) 
                        ||
                        ($absenType === "9" &&
                        is_null($inTime) && 
                        is_null($outTime) &&
                        $totalKoreksi < 3 &&
                        $tipeKoreksi === "masuk_pulang_kampus" &&
                        $approveHeadSchool === 1 &&
                        $approveHr === 1
                        ) 
                        ||
                        ($absenType === "9" &&
                        is_null($inTime) && 
                        is_null($outTime) &&
                        $totalKoreksi >= 3 &&
                        $tipeKoreksi === "masuk_pulang_kampus" 
                        ) 
                        ||
                        ($absenType === "9" &&
                        is_null($inTime) && 
                        is_null($outTime) &&
                        $totalKoreksi >= 3 &&
                        $tipeKoreksi === "masuk_pulang_kampus" &&
                        $approveHeadSchool === 1 &&
                        $approveHr === 1
                        ) 
                        ||
                        // kondisi jika ada jam masuk telat kurang 5 menit dan pulang cepat kurang 5 menit
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === 1 &&
                        $totalPulangKurang5menit === 1 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "1" && 
                        $telatLebih5 === "0" &&
                        $pulangKurang5 === "1" &&
                        $pulangLebih5 === "0"
                        ) 
                        ||
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === 0 &&
                        $totalPulangKurang5menit === 0 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "1" && 
                        $telatLebih5 === "0" &&
                        $pulangKurang5 === "1" &&
                        $pulangLebih5 === "0" &&
                        $approveHeadSchool === 1 &&
                        $approveHr === 1
                        )  
                        ||
                        // kondisi jika ada jam masuk telat kurang 5 menit dan pulang cepat lebih 5 menit
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === 1 &&
                        $totalPulangKurang5menit === 2 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "1" && 
                        $telatLebih5 === "0" &&
                        $pulangKurang5 === "0" &&
                        $pulangLebih5 === "1"
                        ) 
                        ||
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === 0 &&
                        $totalPulangKurang5menit === 0 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "1" && 
                        $telatLebih5 === "0" &&
                        $pulangKurang5 === "0" &&
                        $pulangLebih5 === "1" &&
                        $approveHeadSchool === 1 &&
                        $approveHr === 1
                        )
                        ||
                        // kondisi jika ada jam masuk tapi datang telat kurang 5 menit dan pulang null
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        is_null($outTime) &&
                        $totalDatangKurang5menit === 1 &&
                        $totalPulangKurang5menit === null &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "1" && 
                        $telatLebih5 === "0" &&
                        $pulangKurang5 === null &&
                        $pulangLebih5 === null) 
                        ||
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        is_null($outTime) &&
                        $totalDatangKurang5menit === 0 &&
                        $totalPulangKurang5menit === 0 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "1" && 
                        $telatLebih5 === "0" &&
                        $pulangKurang5 === null &&
                        $pulangLebih5 === null &&
                        $approveHeadSchool === 1 &&
                        $approveHr === 1) 
                        ||
                        // kondisi jika ada jam masuk null dan pulang lebih cepat kurang 5 menit
                        ($absenType === "3" &&
                        is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === null &&
                        $totalPulangKurang5menit === 1 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === null && 
                        $telatLebih5 === null &&
                        $pulangKurang5 === "1" &&
                        $pulangLebih5 === "0"
                        ) 
                        ||
                        ($absenType === "3" &&
                        is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === 0 &&
                        $totalPulangKurang5menit === 0 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === null && 
                        $telatLebih5 === null &&
                        $pulangKurang5 === "1" &&
                        $pulangLebih5 === "0" &&
                        $approveHeadSchool === 1 &&
                        $approveHr === 1
                        ) 
                        || 
                        // kondisi jika ada jam masuk null dan pulang cepat lebih 5 menit
                        ($absenType === "3" &&
                        is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === null &&
                        $totalPulangKurang5menit === 2 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === null && 
                        $telatLebih5 === null &&
                        $pulangKurang5 === "0" &&
                        $pulangLebih5 === "1"
                        ) 
                        ||
                        ($absenType === "3" &&
                        is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === 0 &&
                        $totalPulangKurang5menit === 0 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === null && 
                        $telatLebih5 === null &&
                        $pulangKurang5 === "0" &&
                        $pulangLebih5 === "1" &&
                        $approveHeadSchool === 1 &&
                        $approveHr === 1
                        )  
                        || 
                        // kodisi jika jam masuk dan pulang sama-sama kosong
                        ($absenType === "3" &&
                        is_null($inTime) &&
                        is_null($outTime) &&
                        $totalDatangKurang5menit === null &&
                        $totalPulangKurang5menit === null &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === null && 
                        $telatLebih5 === null &&
                        $pulangKurang5 === null &&
                        $pulangLebih5 === null) 
                        ||
                        ($absenType === "3" &&
                        is_null($inTime) &&
                        is_null($outTime) &&
                        $totalDatangKurang5menit === 0 &&
                        $totalPulangKurang5menit === 0 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === null && 
                        $telatLebih5 === null &&
                        $pulangKurang5 === null &&
                        $pulangLebih5 === null &&
                        $approveHeadSchool === 1 &&
                        $approveHr === 1) 
                        ||
                        // kondisi jika ada jam masuk telat lebih 5 menit dan pulang cepat lebih 5 menit
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === 2 &&
                        $totalPulangKurang5menit === 1 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "0" && 
                        $telatLebih5 === "1" &&
                        $pulangKurang5 === "1" &&
                        $pulangLebih5 === "0"
                        ) 
                        ||
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === 0 &&
                        $totalPulangKurang5menit === 0 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "0" && 
                        $telatLebih5 === "1" &&
                        $pulangKurang5 === "1" &&
                        $pulangLebih5 === "0" &&
                        $approveHeadSchool === 1 &&
                        $approveHr === 1
                        )   
                        ||
                        // kondisi jika ada jam masuk telat lebih 5 menit dan pulang cepat lebih 5 menit
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === 2 &&
                        $totalPulangKurang5menit === 2 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "0" && 
                        $telatLebih5 === "1" &&
                        $pulangKurang5 === "0" &&
                        $pulangLebih5 === "1"
                        ) 
                        ||
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        !is_null($outTime) &&
                        $totalDatangKurang5menit === 0 &&
                        $totalPulangKurang5menit === 0 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "0" && 
                        $telatLebih5 === "1" &&
                        $pulangKurang5 === "0" &&
                        $pulangLebih5 === "1" &&
                        $approveHeadSchool === 1 &&
                        $approveHr === 1
                        )  
                        ||
                        // kondisi jika ada jam masuk tapi datang telat lebih 5 menit dan pulang null
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        is_null($outTime) &&
                        $totalDatangKurang5menit === 2 &&
                        $totalPulangKurang5menit === null &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "0" && 
                        $telatLebih5 === "1" &&
                        $pulangKurang5 === null &&
                        $pulangLebih5 === null
                        ) 
                        ||
                        ($absenType === "3" &&
                        !is_null($inTime) &&
                        is_null($outTime) &&
                        $totalDatangKurang5menit === 0 &&
                        $totalPulangKurang5menit === 0 &&
                        $tipeKoreksi === "masuk_pulang" &&
                        $telatKurang5 === "0" && 
                        $telatLebih5 === "1" &&
                        $pulangKurang5 === null &&
                        $pulangLebih5 === null &&
                        $approveHeadSchool === 1 &&
                        $approveHr === 1
                        )
                    ){
                        return 2;
                    }

                    if(($absenType === "9" && !is_null($inTime) && !is_null($outTime) && $tipeKoreksi === "masuk_pulang_kampus" && $pulangKurang5 === "0") ||
                       ($absenType === "9" && !is_null($inTime) && !is_null($outTime) && $tipeKoreksi === "masuk_pulang_kampus" && $pulangKurang5 === "0" && $approveHeadSchool === 1 && $approveHr === 1)
                    ){
                        return 0;
                    }

                    if(($absenType === "9" && !is_null($inTime) && !is_null($outTime) && $tipeKoreksi === "masuk_pulang_kampus" && $pulangKurang5 === "1") ||
                       ($absenType === "9" && !is_null($inTime) && !is_null($outTime) && $tipeKoreksi === "masuk_pulang_kampus" && $pulangKurang5 === "1" && $approveHeadSchool === 1 && $approveHr === 1)
                    ){
                        return 1;
                    }

                    // Kondisi 2: absenType dalam ["3", "4", "5"]
                    if (in_array($absenType, ["3", "4", "5", "9"])) {
                        return 1;
                    }
                    // Default
                    return 0;
                });
                
                $data->name_head_school =  optional($headTeacherSchool)->full_name ?? '';
                $data->total_koreksi_absen = $totalKoreksiAbsen;
                $data->total_kehadiran = $totalKehadiran;
                $data->total_kehadiran_tanpa_libur = $totalKehadiranTanpaHariLibur;
                $data->total_absen_1x = $totalAbsen1x;
                $data->total_datang_kurang_5_menit = $totalDatangKurang5menit;
                $data->total_datang_lebih_5_menit = $totalDatangLebih5menit;
                $data->total_pulang_kurang_5_menit = $totalPulangKurang5menit;                
                $data->total_pulang_lebih_5_menit = $totalPulangLebih5menit; 
                $data->total_hari_kerja = $totalHariKerja;                
                $data->date_range = $dateRange;
                $data->full_name = $fingerId->full_name;
                $data->id_finger = $fingerId->finger_id;
                $data->nip_ypi = $fingerId->nip_ypi;
                $data->nip_ypi_karyawan = $fingerId->nip_ypi_karyawan;
                $data->total_hari_libur = $totalHariLibur; 
                $data->total_hari_libur_sabtu_minggu = $totalLiburSabtuMinggu;
                $data->teacher_data = $fingerId;  
                // Tidak Hadir
                $data->total_sakit = $totalSakit;
                $data->total_izin = $totalIzin; 
                $data->total_dinas = $totalDinas; 
                $data->total_cuti = $totalCuti;
                $data->total_alpa = $totalAlpa;
                $data->jumlah_tidak_hadir = $totalSakit + $totalIzin + $totalDinas + $totalCuti + $totalAlpa;
                $data->total_kehadiran_libur = $totalKehadiranLibur;
                $data->jumlah_kehadiran = $totalKehadiranTanpaHariLibur + $totalKehadiranLibur;                         
            }

            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'data' => $data
            ], HttpStatusCodes::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error' => true,
                'message' => $th->getMessage()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
    }

    public function recapAbsensiAll(Request $request)
    {
    try {
        $data = YsbPeriod::where(['state' => true]);

        $data->when((string)$request->monthYear != null, function ($query) use ($request) {
            $monthYear = date('Y-m', strtotime($request->monthYear));
            return $query->whereRaw("DATE_FORMAT(period_end, '%Y-%m') = ?", [$monthYear]);
        });

        $data = $data->get()->first();

        if (!$data) {
            return response()->json([
                'status' => 200,
                'error' => true,
                'message' => 'Periode tidak ditemukan',
                'data' => []
            ]);
        }

        $teachers = YsbTeacher::where('state', true);

        // Filtering
        if ($request->level === "developer" && !empty($request->branch) && $request->branch !== "ALL") {
            $teachers->where('ysb_branch_id', 'LIKE', '%' . $request->branch . '%');
        } else if ($request->level === "user" && $request->branch !== "ALL") {
            $teachers->where('ysb_branch_id', 'LIKE', '%' . $request->branch . '%');
        }

        if (!empty($request->ysb_school_id)) {
            $teachers->where('ysb_school_id', 'LIKE', '%' . $request->ysb_school_id . '%');
        }

        $teachers = $teachers->orderBy('ysb_school_id', 'asc')->orderBy('full_name', 'asc')->get();
        $result = [];
        // Inisialisasi semua total global
        $total_datang_kurang_5_menit_all = 0;
        $total_datang_lebih_5_menit_all = 0;
        $total_pulang_kurang_5_menit_all = 0;
        $total_pulang_lebih_5_menit_all = 0;
        $total_absen_1x_all = 0;
        $total_sakit_all = 0;
        $total_izin_all = 0;
        $total_dinas_all = 0;
        $total_cuti_all = 0;
        $total_alpa_all = 0;
        $jumlah_tidak_hadir_all = 0;
        $total_kehadiran_all = 0;
        $total_kehadiran_libur_all = 0;
        $total_kehadiran_tanpa_libur_all = 0;
        $jumlah_kehadiran_all = 0;

        foreach ($teachers as $teacher) {
            $teacherRequest = new Request([
                'id_teacher' => $teacher->id,
                'monthYear' => $request->monthYear
            ]);

            $recapData = $this->recapAbsensi($teacherRequest, $data);

            if ($recapData->getStatusCode() == 200) {
                $decoded = json_decode($recapData->getContent(), true);
                
                $total_datang_kurang_5_menit = $decoded['data']['total_datang_kurang_5_menit'] ?? 0;
                $total_datang_lebih_5_menit = $decoded['data']['total_datang_lebih_5_menit'] ?? 0;
                $total_pulang_kurang_5_menit = $decoded['data']['total_pulang_kurang_5_menit'] ?? 0;
                $total_pulang_lebih_5_menit = $decoded['data']['total_pulang_lebih_5_menit'] ?? 0;
                $total_absen_1x = $decoded['data']['total_absen_1x'] ?? 0;
                $total_sakit = $decoded['data']['total_sakit'] ?? 0;
                $total_izin = $decoded['data']['total_izin'] ?? 0;
                $total_dinas = $decoded['data']['total_dinas'] ?? 0;
                $total_cuti = $decoded['data']['total_cuti'] ?? 0;
                $total_alpa = $decoded['data']['total_alpa'] ?? 0;
                $jumlah_tidak_hadir = $decoded['data']['jumlah_tidak_hadir'] ?? 0;
                $total_kehadiran = $decoded['data']['total_kehadiran'] ?? 0;
                $total_kehadiran_tanpa_libur = $decoded['data']['total_kehadiran_tanpa_libur'] ?? 0;
                $total_kehadiran_libur = $decoded['data']['total_kehadiran_libur'] ?? 0;
                $jumlah_kehadiran = $decoded['data']['jumlah_kehadiran'] ?? 0;

                $total_datang_kurang_5_menit_all += $total_datang_kurang_5_menit;
                $total_datang_lebih_5_menit_all += $total_datang_lebih_5_menit;
                $total_pulang_kurang_5_menit_all += $total_pulang_kurang_5_menit;
                $total_pulang_lebih_5_menit_all += $total_pulang_lebih_5_menit;
                $total_absen_1x_all += $total_absen_1x;
                $total_sakit_all += $total_sakit;
                $total_izin_all += $total_izin;
                $total_dinas_all += $total_dinas;
                $total_cuti_all += $total_cuti;
                $total_alpa_all += $total_alpa;
                $jumlah_tidak_hadir_all += $jumlah_tidak_hadir;
                $total_kehadiran_all += $total_kehadiran;
                $total_kehadiran_tanpa_libur_all += $total_kehadiran_tanpa_libur;
                $total_kehadiran_libur_all += $total_kehadiran_libur;
                $jumlah_kehadiran_all += $jumlah_kehadiran;
                $result[] = $decoded;
            }
        }

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Rekap absensi semua guru berhasil',
                'data' => $result,
                // Untuk Tfoot
                'total_datang_kurang_5_menit_all' => $total_datang_kurang_5_menit_all,
                'total_datang_lebih_5_menit_all' => $total_datang_lebih_5_menit_all,
                'total_pulang_kurang_5_menit_all' => $total_pulang_kurang_5_menit_all,
                'total_pulang_lebih_5_menit_all' => $total_pulang_lebih_5_menit_all,
                'total_absen_1x_all' => $total_absen_1x_all,
                'total_sakit_all' => $total_sakit_all,
                'total_izin_all' => $total_izin_all,
                'total_dinas_all' => $total_dinas_all,
                'total_cuti_all' => $total_cuti_all,
                'total_alpa_all' => $total_alpa_all,
                'jumlah_tidak_hadir_all' => $jumlah_tidak_hadir_all,
                'total_kehadiran_all' => $total_kehadiran_all,
                'total_kehadiran_libur_all' => $total_kehadiran_libur_all,
                'total_kehadiran_tanpa_libur_all' => $total_kehadiran_tanpa_libur_all,
                'jumlah_kehadiran_all' => $jumlah_kehadiran_all,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }

    public function recapKoreksiAbsen(Request $request)
    {
        try {
            // Ambil periode berdasarkan monthYear dan state
            $data = YsbPeriod::where('state', true);

            if ($request->filled('monthYear')) {
                $monthYear = date('Y-m', strtotime($request->monthYear));
                $data->whereRaw("DATE_FORMAT(period_end, '%Y-%m') = ?", [$monthYear]);
            }
            $data = $data->first();

            if (!$data) {
                return response()->json([
                    'status' => 200,
                    'error' => true,
                    'message' => 'Periode tidak ditemukan',
                    'data' => []
                ]);
            }

            // Ambil data absensi
           $teachers = YsbAttendanceDaily::with(['teacherDetail' => function ($query) {
                $query->where('state', true); 
            }])->whereHas('teacherDetail', function ($query) {
                $query->where('state', true); 
            });
            // Filter berdasarkan level dan branch
            if ($request->level === 'developer' && $request->filled('branch') && $request->branch !== 'ALL') {
                $teachers->where('ysb_branch_id', $request->branch);
            } elseif ($request->level === 'user' && $request->filled('branch') && $request->branch !== 'ALL') {
                $teachers->where('ysb_branch_id', $request->branch);
            }

            // Filter berdasarkan ysb_school_id
            if ($request->filled('ysb_school_id')) {
                $teachers->where('ysb_school_id', $request->ysb_school_id);
            }

            // Filter berdasarkan ysb_teacher_id
            if ($request->filled('ysb_teacher_id')) {
                $teachers->where('ysb_teacher_id', $request->ysb_teacher_id);
            }

            // Filter berdasarkan tanggal (date_in dan date_out)
            if ($request->filled('date_in') && $request->filled('date_out')) {
                // Pastikan date_in dan date_out berada dalam periode
                $dateIn = max($data->period_start, date('Y-m-d', strtotime($request->date_in)));
                $dateOut = min($data->period_end, date('Y-m-d', strtotime($request->date_out)));
                $teachers->whereBetween('att_date', [$dateIn, $dateOut]);
            } else {
                // Jika date_in atau date_out kosong, gunakan period_start dan period_end
                $teachers->whereBetween('att_date', [$data->period_start, $data->period_end]);
            }

            // Urutkan data
            $teachers = $teachers->where('state', true)
                                //  ->orderBy('ysb_school_id', 'asc')
                                 ->orderBy('created_at', 'desc')
                                 ->get();

            $teachers = $teachers->map(function ($item) use ($data) {
            $item->period = $data; 
            return $item;
            });

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Rekap absensi berhasil',
                'data' => $teachers,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }

    public function recapUkk(Request $request, YsbPeriod $TblData)
    {
        try {
            // Ambil data periode berdasarkan bulan dan tahun yang dipilih
            $data = YsbPeriod::where(['state' => true]);
            $data->when($request->monthYear != null, function ($query) use ($request) {
                $monthYear = date('Y-m', strtotime($request->monthYear));
                return $query->whereRaw("DATE_FORMAT(period_end, '%Y-%m') = ?", [$monthYear]);
            });
            $data = $data->get()->first();

            if (!$data) {
                return response()->json([
                    'status' => 400,
                    'error' => true,
                    'message' => 'Data periode tidak ditemukan'
                ]);
            }

            // Ambil semua guru yang aktif
            $teachers = YsbTeacher::where(['state' => true])->get();
            $rekapData = [];

            foreach ($teachers as $teacher) {
                // Ambil ID sidik jari
                $fingerId = $teacher->finger_id;

                // Ambil data absensi guru
                $arrayAbsen = YsbAttendanceTrx::where(['finger_id' => $fingerId, 'state' => true])->get();
                $InOutTime = YsbSchedule::where(['ysb_position_code' => $teacher->ysb_position_id, 'state' => true])->first();
                $attendanceDailyTeacher = YsbAttendanceDaily::where(['ysb_teacher_id' => $teacher->id, 'state' => true])->get();
                $headTeacherSchool = YsbTeacher::where('ysb_school_id', $teacher->ysb_school_id)
                    ->whereIn('ysb_position_id', ['B', 'D1', 'J'])
                    ->where('state', true)
                    ->first();

                $startDate = Carbon::parse($data->period_start);
                $endDate = Carbon::parse($data->period_end);
                $dateRange = [];

                while ($startDate->lte($endDate)) {
                    $currentDate = $startDate->format('Y-m-d');
                    $absenForDate = $arrayAbsen->filter(function ($absen) use ($currentDate) {
                        return $absen->att_date === $currentDate;
                    })->sortBy('att_time')->values();

                    $startTime = $absenForDate->isNotEmpty() ? $absenForDate->first()->att_time : null;
                    $endTime = $absenForDate->isNotEmpty() ? $absenForDate->last()->att_time : null;
                    $attendanceDaily = $attendanceDailyTeacher->firstWhere('att_date', $currentDate) ?? null;

                    if ($startTime) {
                        $startTimeObj = Carbon::createFromFormat('H:i:s', $startTime);
                        if ($startTimeObj->hour > 11) {
                            $startTime = null;
                        }
                    }

                    if ($endTime) {
                        $endTimeObj = Carbon::createFromFormat('H:i:s', $endTime);
                        if ($endTimeObj->hour < 11) {
                            $endTime = null;
                        }
                    }

                    if (!is_null($attendanceDaily) && !is_null($attendanceDaily->att_clock_in) && !is_null($attendanceDaily->att_clock_out)) {
                        $startTime = ($attendanceDaily->att_clock_in === "00:00:00") ? null : $attendanceDaily->att_clock_in;
                        $endTime = ($attendanceDaily->att_clock_out === "00:00:00") ? null : $attendanceDaily->att_clock_out;
                    }

                    $absen_1x = (!is_null($startTime) && !is_null($endTime)) ? 2 : (!is_null($startTime) || !is_null($endTime) ? 1 : 0);
                    $kehadiran = is_null($startTime) && is_null($endTime) && is_null($attendanceDaily) ? 0 : 1;

                    $dateRange[] = [
                        'date' => $currentDate,
                        'day' => $startDate->translatedFormat('l'),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'absen_1x' => $absen_1x,
                        'kehadiran' => $kehadiran,
                        'duration_attendance' => ($startTime && $endTime) 
                            ? Carbon::createFromFormat('H:i:s', $startTime)
                                ->diff(Carbon::createFromFormat('H:i:s', $endTime))
                                ->format('%H:%I:%S') 
                            : null,
                    ];

                    $startDate->addDay();
                }

                $totalKehadiran = collect($dateRange)->sum(function ($day) {
                    return $day['kehadiran'];
                });

                $totalAbsen1x = collect($dateRange)->sum(function ($day) {
                    return $day['absen_1x'] === 1 ? 1 : 0;
                });

                $rekapData[] = [
                    'teacher_id' => $teacher->id,
                    'teacher_name' => $teacher->name,
                    'rekap_periode' => $data->period_start . ' - ' . $data->period_end,
                    'total_hadir' => $totalKehadiran,
                    'total_absen_1x' => $totalAbsen1x,
                    'detail_absensi' => $dateRange
                ];
            }

            return response()->json([
                'status' => 200,
                'error' => false,
                'data' => $rekapData
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 400,
                'error' => true,
                'message' => $th->getMessage()
            ]);
        }
    }
}

