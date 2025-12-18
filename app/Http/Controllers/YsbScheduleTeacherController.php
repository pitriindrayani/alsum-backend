<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\SDC\RestCurl;
use App\Models\YsbScheduleTeacher;
use App\Models\YsbTeacher;
use App\Models\YsbPeriod;
use App\Models\YsbTeacherStatus;
use Illuminate\Support\Str;
use App\Kis\HttpStatusCodes;
use Illuminate\Http\Request;
use App\Kis\PaginationFormat;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\YsbScheduleTeacherCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class YsbScheduleTeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, YsbScheduleTeacher $TblData)
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
            if($request->branch === "ALL" || $request->level === "developer" || $request->level === "superadmin" ){
                // No action
            }else if($request->level !== "developer" || $request->level !== "superadmin"){
                $data->when((string)$request->branch != null, function ($query) use ($request) {
                    $branch = $request->branch;
                    $query->where(function ($query) use ($branch) {
                        $query->where('ysb_branch_id', 'LIKE', '%' . $branch . '%');
                    });
                });
            }
            
            $data->when((string)$request->search != null, function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($query) use ($search) {
                    $query->where('ysb_school_id', 'LIKE', '%' . $search . '%')
                    ->orWhere('full_name', 'LIKE', '%' . $search . '%');
                });
            });
             
            $data->where('state', true);
            $result = $data->orderBy('created_at', $request->ascending == true ? 'asc' : 'desc')->paginate($request->limit);

            return new YsbScheduleTeacherCollection($result);
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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexJadwal(Request $request, YsbPeriod $TblData)
    {
        try {
             $data = YsbTeacher::where(['ysb_id_teacher' => $request->ysb_id_teacher, 'state' => true, dan ambil ])->get();            
                if (!$data) {
                    return response()->json([
                        'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                        'error' => true,
                        'message' => 'Data guru tidak ditemukan',
                    ]);
                }
            
                // Ambil data absen berdasarkan finger ID
                $arrayAbsen = YsbTeacherStatus::where(['id' => $fingerId->finger_id, 'state' => true])->get();

                $dateRange = [];
            
                while($startDate->lte($endDate)){
                    $currentDate = $startDate->format('Y-m-d');

                    // Ambil start_time (waktu paling awal) dan end_time (waktu paling akhir)
                    $startTime = $absenForDate->isNotEmpty() ? $absenForDate->first()->att_time : null;
                    $endTime = $absenForDate->isNotEmpty() ? $absenForDate->last()->att_time : null;
                    $attendanceDaily = $attendanceDailyTeacher->firstWhere('att_date', $currentDate) ?? null;
                    
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
                        'absen' => $endTime
                    ];
                  $startDate->addDay();
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
            'date'   => 'required|array',
            'date.*' => 'exists:year',
            'date.*' => 'exists:month',
            'date.*' => 'exists:ysb_branch_id',
            'date.*' => 'exists:ysb_school_id',
            'date.*' => 'exists:ysb_id_teacher',
            'date.*' => 'exists:full_name',
            'date.*' => 'exists:day_libur',
            'date.*' => 'exists:day_keterangan',
            'date.*' => 'exists:in_time',
            'date.*' => 'exists:out_time',
            'date.*' => 'exists:update_arrive',
            'date.*' => 'exists:update_late',
            'date.*' => 'exists:update_duration'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'error'  => true,
                'message' => $validator->errors()->first()
            ], 400);
        }
    
        try {

            foreach ($request->array_id_teacher as $teacherId) {
                $checkTeacher = YsbTeacher::where(['id' => $teacherId, 'state' => true])->first();
                if (!$checkTeacher) {
                    continue; 
                }
            
                YsbScheduleTeacher::create([
                    'year'              => $request->year,
                    'month'             => $request->month,
                    'ysb_branch_id'     => $request->ysb_branch_id,
                    'ysb_school_id'     => $request->ysb_school_id,
                    'ysb_id_teacher'    => $request->ysb_id_teacher,
                    'full_name'         => $checkTeacher->full_name,
                    'day_libur'         => $request->day_libur,
                    'day_keterangan'    => $request->day_keterangan,
                    'in_time'           => $request->in_time,
                    'out_time'          => $request->out_time,
                    'update_arrive'     => $request->update_arrive,
                    'update_late'       => $request->update_late,
                    'update_duration'   => $request->update_duration,
                    'create_by'         => $request->auth->id
                ]);
            }
    
            return response()->json([
                'status' => 200,
                'error'  => false,
                'message' => 'Success to create data'
            ], 200);
        } catch (\Throwable $th) {
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
            $data = YsbScheduleTeacher::where(['id' => $id, 'state' => true])->first();
            if(!$data)
            {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error' => true,
                'message' => "Schedule Tidak Ditemukan!"
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
            'ysb_id_teacher' => 'required',
            'date_in'        => 'required',
            'date_out'       => 'required',
            'time_in'        => 'required',
            'time_out'       => 'required',
            'description'    => 'nullable'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        try {
            $checkTeacherUpdate = YsbTeacher::where(['id' => $request->ysb_id_teacher, 'state' => true])->first();
            if(!$checkTeacherUpdate)
            {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => "Guru Tidak Ditemukan!"
                ], HttpStatusCodes::HTTP_BAD_REQUEST);                
            }

            $data = YsbScheduleTeacher::where(['id' => $id, 'state' => true])->first();
            if(!$data)
            {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => "Data Tidak Ditemukan!"
                ], HttpStatusCodes::HTTP_BAD_REQUEST);                
            }

            $data->date_in           = $request->date_in;
            $data->date_out          = $request->date_out;
            $data->time_in           = $request->time_in;
            $data->time_out          = $request->time_out;
            $data->description       = $request->description;
            $data->update_by         = $request->auth->id;
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
            $data = YsbScheduleTeacher::where(['id' => $id, 'state' => true])->first();
            if (!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'Schedule Tidak Ditemukan!'
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
}

