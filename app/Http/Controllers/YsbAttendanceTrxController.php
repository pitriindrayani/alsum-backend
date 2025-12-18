<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\SDC\RestCurl;
use App\Models\YsbAttendanceTrx;
use App\Models\YsbTeacher;
use App\Models\YsbHoliday;
use App\Models\YsbPeriod;
use App\Models\YsbScheduleTime;
use Illuminate\Support\Str;
use App\Kis\HttpStatusCodes;
use Illuminate\Http\Request;
use App\Kis\PaginationFormat;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\YsbAttendanceTrxCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class YsbAttendanceTrxController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, YsbAttendanceTrx $TblData)
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
            }else{
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
                    $query->where('description', 'LIKE', '%' . $search . '%')
                          ->orWhere('finger_id', 'LIKE', '%' . $search . '%')
                          ->orWhere('att_time', 'LIKE', '%' . $search . '%')
                          ->orWhere('att_date', 'LIKE', '%' . $search . '%');
                });
            });
            
            $data->where('state', true);
            $result = $data->orderBy('created_at', $request->ascending == true ? 'asc' : 'desc')->paginate($request->limit);

            return new YsbAttendanceTrxCollection($result);
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
            'id_attendance'  => 'nullable',
            'att_method'     => 'nullable',
            'att_date'       => 'nullable',
            'att_time'       => 'nullable',
            'finger_id'      => 'nullable',
            'description'    => 'nullable',
            'att_type'       => 'nullable'
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
            $save = YsbAttendanceTrx::create([
                'id_attendance'  => $request->id_attendance,
                'att_method'     => $request->att_method,
                'att_date'       => $request->att_date,
                'att_time'       => $request->att_time,
                'finger_id'      => $request->finger_id,
                'description'    => $request->description,
                'att_type'       => $request->att_type,
                'create_by'         => $request->auth->id
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
            $data = YsbAttendanceTrx::where(['id' => $id, 'state' => true])->first();
            if(!$data)
            {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error' => true,
                'message' => "Absen Tidak Ditemukan!"
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
            'id_attendance'  => 'nullable',
            'att_method'     => 'nullable',
            'att_date'       => 'nullable',
            'att_time'       => 'nullable',
            'finger_id'      => 'nullable',
            'description'    => 'nullable',
            'att_type'       => 'nullable'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        try {
            $data = YsbAttendanceTrx::where(['id' => $id, 'state' => true])->first();
            if(!$data)
            {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => "Absen Tidak Ditemukan!"
                ], HttpStatusCodes::HTTP_BAD_REQUEST);                
            }

            $data->id_attendance = $request->id_attendance;
            $data->att_method    = $request->att_method;
            $data->att_date      = $request->att_date;
            $data->att_time      = $request->att_time;
            $data->finger_id     = $request->finger_id;
            $data->description   = $request->description;
            $data->att_type      = $request->att_type;
            $data->update_by     = $request->auth->id;
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
            $data = YsbAttendanceTrx::where(['id' => $id, 'state' => true])->first();
            if (!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'Absen Tidak Ditemukan!'
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

     /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeExcel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'absen_log'                 => 'required|array',
            // 'absen_log.*.id_attendance' => 'required',
            // 'absen_log.*.att_method'    => 'required',
            // 'absen_log.*.att_date'      => 'required',
            // 'absen_log.*.att_time'      => 'required',
            // 'absen_log.*.finger_id'     => 'required',
            // 'absen_log.*.description'   => 'required',
            // 'absen_log.*.att_type'      => 'required',

        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        try {
            foreach ($request->absen_log as $value) {
                // $findNameStr = DiagnosaPatient::where(['code_skri' => $value['code_skri'], 'state' => true])->first();
                // if($findNameStr)
                // {
                //     return response()->json([
                //         'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                //         'error' => true,
                //         'message' => 'The code has already been taken'
                //     ], HttpStatusCodes::HTTP_BAD_REQUEST);
                // }

                YsbAttendanceTrx::create([
                    // 'id_attendance'  => $value['id_attendance'],
                    // 'att_method'     => $value['att_method'],
                    'att_date'          => \DateTime::createFromFormat('d/m/Y', $value['att_date'])->format('Y-m-d'),
                    'att_time'          => str_replace('.', ':', $value['att_time']),
                    'finger_id'         => $value['finger_id'],
                    'description'       => $value['description'],
                    'state'             => true,
                    // 'att_type'       => $value['att_type'],
                    'create_by'         => $request->auth->id
                ]);
            }
            // LogActivity::addToLog('Success to upload data', $request->auth->id);
            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Success to upload data'
            ], HttpStatusCodes::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $th->getMessage(),
                'trace' => $th->getTrace()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
    }

    public function attendanceByIdTeacher(Request $request, YsbTeacher $TblData)
    {
        try {
            $fingerId = YsbTeacher::where(['id' => $request->id_teacher, 'state' => true]);

           

            $data = $fingerId->get();

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

   public function showByDate(Request $request)
{
    try {
        $teacherSchool = YsbTeacher::where('id', $request->ysb_teacher_id)
            ->where('state', true)
            ->first();

        if (!$teacherSchool) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error' => true,
                'message' => "Guru tidak ditemukan!"
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        
        // Ambil data jadwal shift
        $jadwalShift = YsbScheduleTime::where('state', true)
            ->where('ysb_id_teacher', $request->ysb_teacher_id)
            ->whereDate('date_in', '<=', $request->att_date)
            ->whereDate('date_out', '>=', $request->att_date)
            ->first();

        // Ambil data periode
        $periodData = YsbPeriod::where('state', true)
            ->whereDate('period_start', '<=', $request->att_date)
            ->whereDate('period_end', '>=', $request->att_date)
            ->first();

        // Ambil data absensi
        $trxAbsen = YsbAttendanceTrx::where([
            'att_date' => $request->att_date,
            'finger_id' => $teacherSchool->finger_id,
            'state' => true
        ])->get();

        $absensiWithExtraData = $trxAbsen->map(function ($item) use ($jadwalShift, $periodData) {
            $item->jadwal_shift = $jadwalShift;
            $item->periode = $periodData;
            return $item;
        });

        // Hitung start_time dan end_time
        $start_time = null;
        $end_time = null;

        if ($trxAbsen->isNotEmpty()) {
            // Ambil waktu dari kolom att_time (atau kolom waktu lainnya)
            $times = $trxAbsen->pluck('att_time')->sort();
            
            // Ambil waktu paling awal
            $start_time = $times->first();
            
            // Jika ada lebih dari satu waktu dan waktu terakhir berbeda dari waktu pertama
            if ($times->count() > 1 && $times->last() !== $start_time) {
                $end_time = $times->last();
            }
        }

        // Gabungkan semua data dalam satu response
        $data = [
            // 'absensi' => $absensiWithExtraData,
            'start_time' => $start_time,
            'end_time' => $end_time,
            // 'jadwal_libur' => $jadwalLibur,
            'jadwal_shift' => $jadwalShift,
            'periode' => $periodData
        ];

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

}

