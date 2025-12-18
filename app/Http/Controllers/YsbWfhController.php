<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\SDC\RestCurl;
use App\Models\YsbWfh;
use App\Models\YsbPeriod;
use App\Models\YsbTeacher;
use Illuminate\Support\Str;
use App\Kis\HttpStatusCodes;
use Illuminate\Http\Request;
use App\Kis\PaginationFormat;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\YsbWfhCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class YsbWfhController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, YsbWfh $TblData)
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
            }else if($request->branch !== "ALL" && $request->level === "user" && $request->role_hr === "true" && $request->role_head_school === "false"){
                $data->when((string)$request->branch != null, function ($query) use ($request) {
                    $branch = $request->branch;
                    $query->where(function ($query) use ($branch) {
                        $query->where('ysb_branch_id', 'LIKE', '%' . $branch . '%');
                    });
                });
            }else if($request->branch !== "ALL" && $request->level === "user" && $request->role_hr === "false" && $request->role_head_school === "true"){
                $data->when((string)$request->ysb_school_id != null, function ($query) use ($request) {
                    $ysb_school_id = $request->ysb_school_id;
                    $query->where(function ($query) use ($ysb_school_id) {
                        $query->where('ysb_school_id', 'LIKE', '%' . $ysb_school_id . '%');
                    });
                });
            }else{
                $data->when((string)$request->ysb_teacher_id != null, function ($query) use ($request) {
                    $ysb_teacher_id = $request->ysb_teacher_id;
                    $query->where(function ($query) use ($ysb_teacher_id) {
                        $query->where('ysb_teacher_id', 'LIKE', '%' . $ysb_teacher_id . '%');
                    });
                });
            }
         
            // Gunakan query builder dengan when untuk pencarian
            $data = $data->when(!empty($request->search), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($query) use ($search) {
                    $query->where('att_date', 'LIKE', '%' . $search . '%')
                        ->orWhere('full_name', 'LIKE', '%' . $search . '%')
                        ->orWhere('ysb_school_id', 'LIKE', '%' . $search . '%')
                        ->orWhere('schedule_in', 'LIKE', '%' . $search . '%')
                        ->orWhere('schedule_out', 'LIKE', '%' . $search . '%');
                });
            });

            $data->where('state', true);
            $result = $data->orderBy('created_at', $request->ascending == true ? 'asc' : 'desc')->paginate($request->limit);

            return new YsbWfhCollection($result);
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
    public function indexFilter(Request $request, YsbWfh $TblData)
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
            }else if($request->branch !== "ALL" && $request->level === "user" && $request->role_hr === "true" && $request->role_head_school === "false"){
                $data->when((string)$request->branch != null, function ($query) use ($request) {
                    $branch = $request->branch;
                    $query->where(function ($query) use ($branch) {
                        $query->where('ysb_branch_id', 'LIKE', '%' . $branch . '%');
                    });
                });
            }else if($request->branch !== "ALL" && $request->level === "user" && $request->role_hr === "false" && $request->role_head_school === "true"){
                $data->when((string)$request->ysb_school_id != null, function ($query) use ($request) {
                    $ysb_school_id = $request->ysb_school_id;
                    $query->where(function ($query) use ($ysb_school_id) {
                        $query->where('ysb_school_id', 'LIKE', '%' . $ysb_school_id . '%');
                    });
                });
            }else{
                $data->when((string)$request->ysb_teacher_id != null, function ($query) use ($request) {
                    $ysb_teacher_id = $request->ysb_teacher_id;
                    $query->where(function ($query) use ($ysb_teacher_id) {
                        $query->where('ysb_teacher_id', 'LIKE', '%' . $ysb_teacher_id . '%');
                    });
                });
            }
         
            // Gunakan query builder dengan when untuk pencarian
            $data = $data->when(!empty($request->search), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($query) use ($search) {
                    $query->where('att_date', 'LIKE', '%' . $search . '%')
                        ->orWhere('full_name', 'LIKE', '%' . $search . '%')
                        ->orWhere('ysb_school_id', 'LIKE', '%' . $search . '%')
                        ->orWhere('schedule_in', 'LIKE', '%' . $search . '%')
                        ->orWhere('schedule_out', 'LIKE', '%' . $search . '%');
                });
            });

            $data->where('state', true);
            $result = $data->orderBy('created_at', $request->ascending == true ? 'asc' : 'desc')->paginate($request->limit);

            return new YsbWfhCollection($result);
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
        'ysb_teacher_id'        => 'required',
        'ysb_branch_id'         => 'required',
        'ysb_school_id'         => 'required',
        'id_user_head_school'   => 'nullable',
        'att_clock_in'          => 'required',
        'att_clock_out'         => 'required',
        // 'keterangan'            => 'required',
        // 'date_in'               => 'required',
        // 'date_out'              => 'required',
        'dokument'              => 'nullable|file|mimes:jpeg,jpg,png,pdf' 
    ]);

    if ($request->dokument === null) {
        return response()->json([
            'status'  => 400,
            'error'   => true,
            'message' => "Surat Tugas wajib diupload"
        ], 400);
    };    

    if ($validator->fails()) {
        return response()->json([
            'status' => 400,
            'error'  => true,
            'message' => $validator->errors()->first()
        ], 400);
    };

    try {
        // Simpan file jika ada
        $path = null;
        if ($request->hasFile('dokument')) {
            $file = $request->file('dokument');
            $extension = $file->getClientOriginalExtension();
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $originalName = preg_replace('/\s+/', '_', $originalName); 

            // direktori penyimpanan
            if (!Storage::disk('public')->exists('wfh')) {
                Storage::disk('public')->makeDirectory('wfh');
            }

            // Hitung jumlah file yang sudah ada di direktori untuk mendapatkan running number
            $existingFiles = Storage::disk('public')->files('wfh');
            $runningNumber = count($existingFiles) + 1;

            // Format nam file
            $filename = "{$runningNumber}-{$originalName}.{$extension}";

            // Simpan file di storage/app/public/wfh
            $path = $file->storeAs('wfh', $filename, 'public');
        }

        $startDate = Carbon::parse($request->date_in);
        $endDate   = Carbon::parse($request->date_out);
        $totalDays = $endDate->diffInDays($startDate) + 1;

        $checkTeacher = YsbTeacher::where(['id' => $request->ysb_teacher_id, 'state' => true])->first();
        $headTeacherSchool = YsbTeacher::where('ysb_school_id', $checkTeacher->ysb_school_id)
            ->whereIn('ysb_position_id', ['B', 'D1', 'J'])
            ->where('state', true)
            ->first();

        for ($i = 0; $i < $totalDays; $i++) {
        $currentDate = $startDate->copy()->addDays($i);

        YsbWfh::create([
            'ysb_teacher_id'        => $request->ysb_teacher_id,
            'ysb_branch_id'         => $checkTeacher->ysb_branch_id,
            'ysb_school_id'         => $checkTeacher->ysb_school_id,
            'id_user_head_school'   => $headTeacherSchool->id,
            'full_name'             => $checkTeacher->full_name,
            'approve_hr'            => false,
            'approve_head_school'   => false,
            'att_date'              => $currentDate->format('Y-m-d'),
            'att_clock_in'          => $request->att_clock_in,
            'att_clock_out'         => $request->att_clock_out,
            'date_in'               => $request->date_in,
            'date_out'              => $request->date_out,
            'keterangan'            => $request->keterangan,
            'dokument'              => $path,
            'create_by'             => $request->auth->id,
        ]);
    }

        return response()->json([
            'status' => 200,
            'error'  => false,
            'message' => 'Success to create data',
            'file_url' => $path ? asset('storage/' . $path) : null 
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeHead(Request $request){
    $validator = Validator::make($request->all(), [
        'ysb_teacher_id'        => 'required',
        'ysb_branch_id'         => 'required',
        'ysb_school_id'         => 'required',
        'id_user_head_school'   => 'nullable',
        'att_clock_in'          => 'required',
        'att_clock_out'         => 'required',
        // 'keterangan'            => 'required',
        // 'date_in'               => 'required',
        // 'date_out'              => 'required',
        'dokument'              => 'nullable|file|mimes:jpeg,jpg,png,pdf' 
    ]);

    if ($request->dokument === null) {
        return response()->json([
            'status'  => 400,
            'error'   => true,
            'message' => "Surat Tugas wajib diupload"
        ], 400);
    };    

    if ($validator->fails()) {
        return response()->json([
            'status' => 400,
            'error'  => true,
            'message' => $validator->errors()->first()
        ], 400);
    };

    try {
        // Simpan file jika ada
        $path = null;
        if ($request->hasFile('dokument')) {
            $file = $request->file('dokument');
            $extension = $file->getClientOriginalExtension();
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $originalName = preg_replace('/\s+/', '_', $originalName); 

            // direktori penyimpanan
            if (!Storage::disk('public')->exists('wfh')) {
                Storage::disk('public')->makeDirectory('wfh');
            }

            // Hitung jumlah file yang sudah ada di direktori untuk mendapatkan running number
            $existingFiles = Storage::disk('public')->files('wfh');
            $runningNumber = count($existingFiles) + 1;

            // Format nam file
            $filename = "{$runningNumber}-{$originalName}.{$extension}";

            // Simpan file di storage/app/public/wfh
            $path = $file->storeAs('wfh', $filename, 'public');
        }

        $startDate = Carbon::parse($request->date_in);
        $endDate   = Carbon::parse($request->date_out);
        $totalDays = $endDate->diffInDays($startDate) + 1;

        $checkTeacher = YsbTeacher::where(['id' => $request->ysb_teacher_id, 'state' => true])->first();
        $headTeacherSchool = YsbTeacher::where('ysb_school_id', $checkTeacher->ysb_school_id)
            ->whereIn('ysb_position_id', ['B', 'D1', 'J'])
            ->where('state', true)
            ->first();

        for ($i = 0; $i < $totalDays; $i++) {
        $currentDate = $startDate->copy()->addDays($i);

        YsbWfh::create([
            'ysb_teacher_id'        => $request->ysb_teacher_id,
            'ysb_branch_id'         => $checkTeacher->ysb_branch_id,
            'ysb_school_id'         => $checkTeacher->ysb_school_id,
            'id_user_head_school'   => $headTeacherSchool->id,
            'full_name'             => $checkTeacher->full_name,
            'approve_hr'            => false,
            'approve_head_school'   => true,
            'att_date'              => $currentDate->format('Y-m-d'),
            'att_clock_in'          => $request->att_clock_in,
            'att_clock_out'         => $request->att_clock_out,
            'date_in'               => $request->date_in,
            'date_out'              => $request->date_out,
            'keterangan'            => $request->keterangan,
            'approve_at_head'       => Carbon::now()->format('Y-m-d H:i:s'),
            'approve_by_head'       => $request->auth->id,
            'dokument'              => $path,
            'create_by'             => $request->auth->id,
        ]);
        }

        return response()->json([
            'status' => 200,
            'error'  => false,
            'message' => 'Success to create data',
            'file_url' => $path ? asset('storage/' . $path) : null 
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
   public function storeHr(Request $request)
    {

    $validator = Validator::make($request->all(), [
        // 'array_id_teacher'      => 'required|array',
        // 'array_id_teacher.*'    => 'exists:ysb_teachers,id',
        'ysb_teacher_id'        => 'required',
        'absent_type'           => 'required',
        'ysb_teacher_id'        => 'nullable',
        'ysb_branch_id'         => 'nullable',
        'ysb_school_id'         => 'nullable',
        'id_user_head_school'   => 'nullable',
        'id_user_hr'            => 'nullable',
        'approve_hr'            => 'nullable',
        'approve_head_school'   => 'nullable',
        'att_date'              => 'nullable',
        'date_in'               => 'required|date',
        'date_out'              => 'required|date',
        'att_clock_in'          => 'required',
        'att_clock_out'         => 'required',
        'schedule_in'           => 'nullable',
        'schedule_out'          => 'nullable',
        'late_min'              => 'nullable',
        'early_min'             => 'nullable',
        'tipe_koreksi'          => 'nullable',
        'total_koreksi'         => 'nullable',
        'keterangan'            => 'nullable',
        'kjm'                   => 'nullable',
        'ket1'                  => 'nullable',
        'telat_kurang_5'        => 'nullable',
        'telat_lebih_5'         => 'nullable',
        'pulang_kurang_5'       => 'nullable',
        'pulang_lebih_5'        => 'nullable',
        'jumlah_waktu'          => 'nullable',
        'jam_lembur'            => 'nullable',
        'absen1'                => 'nullable',
        'in_time'               => 'nullable',
        'out_time'              => 'nullable',
        'fg_locked'             => 'nullable',
        'update'                => 'nullable',
        'update_arrive'         => 'nullable',
        'update_late'           => 'nullable',
        'update_absen1x'        => 'nullable',
        'update_kehadiran'      => 'nullable',
        'dokument'              => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048'
    ]);

    if (in_array($request->absent_type, ["6", "7", "8", "9", "10", "9_hr", "10_hr"]) && $request->dokument === null) {
        return response()->json([
            'status'  => 400,
            'error'   => true,
            'message' => "Surat Tugas wajib diupload"
        ], 400);
    };    

    if ($validator->fails()) {
        return response()->json([
            'status' => 400,
            'error'  => true,
            'message' => $validator->errors()->first()
        ], 400);
    }

    try {
        // Simpan file jika ada
        $path = null;
        if ($request->hasFile('dokument')) {
            $file = $request->file('dokument');
            $extension = $file->getClientOriginalExtension();
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $originalName = preg_replace('/\s+/', '_', $originalName);

            if (!Storage::disk('public')->exists('wfh')) {
                Storage::disk('public')->makeDirectory('wfh');
            }

            $existingFiles = Storage::disk('public')->files('wfh');
            $runningNumber = count($existingFiles) + 1;

            $filename = "{$runningNumber}-{$originalName}.{$extension}";

            $path = $file->storeAs('wfh', $filename, 'public');
        }

        $startDate = Carbon::parse($request->date_in);
        $endDate   = Carbon::parse($request->date_out);
        $totalDays = $endDate->diffInDays($startDate) + 1;

        $checkTeacher = YsbTeacher::where(['id' => $request->ysb_teacher_id, 'state' => true])->first();
        $headTeacherSchool = YsbTeacher::where('ysb_school_id', $checkTeacher->ysb_school_id)
            ->whereIn('ysb_position_id', ['B', 'D1', 'J'])
            ->where('state', true)
            ->first();

        for ($i = 0; $i < $totalDays; $i++) {
        $currentDate = $startDate->copy()->addDays($i);

        YsbWfh::create([
            'ysb_teacher_id'        => $request->ysb_teacher_id,
            'ysb_branch_id'         => $checkTeacher->ysb_branch_id,
            'ysb_school_id'         => $checkTeacher->ysb_school_id,
            'id_user_head_school'   => $headTeacherSchool?->id,
            'full_name'             => $checkTeacher->full_name,
            'in_time'               => $request->in_time,
            'out_time'              => $request->out_time,
            'approve_hr'            => 1,
            'approve_head_school'   => 1,
            'approve_at_head'       => Carbon::now()->format('Y-m-d H:i:s'),
            'approve_at_hr'         => Carbon::now()->format('Y-m-d H:i:s'),
            'approve_by_head'       => $request->auth->id ?? null,
            'approve_by_hr'         => $request->auth->id ?? null,
            'att_date'              => $currentDate->format('Y-m-d'),
            'absent_type'           => $request->absent_type,
            'att_clock_in'          => $request->att_clock_in,
            'att_clock_out'         => $request->att_clock_out,
            'keterangan'            => $request->keterangan,
            'create_by'             => $request->auth->id ?? null,
            'update'                => $request->update,
            'update_arrive'         => $request->update_arrive,
            'update_late'           => $request->arrive_late,
            'update_absen1x'        => $request->update_absen1x,
            'update_kehadiran'      => $request->update_absen_kehadiran,
            'dokument'              => $path
        ]);
    }

        return response()->json([
            'status'   => 200,
            'error'    => false,
            'message'  => 'Success to create data',
            'file_url' => $path ? asset('storage/' . $path) : null
        ], 200);
    } catch (\Throwable $th) {
        return response()->json([
            'status'  => 400,
            'error'   => true,
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
            $data = YsbWfh::where(['id' => $id, 'state' => true])->first();
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
        //  if (in_array($request->absent_type, ["6", "7", "8", "9", "10", "9_hr", "10_hr"]) && $request->dokument === null) {
        //     return response()->json([
        //         'status'  => 400,
        //         'error'   => true,
        //         'message' => "Surat Tugas wajib diupload"
        //     ], 400);
        // };    

        try {
            $data = YsbWfh::where(['id' => $id, 'state' => true])->first();
            if(!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => "Absen Tidak Ditemukan!"
                ], HttpStatusCodes::HTTP_BAD_REQUEST);                
            }

            $data->att_clock_in   = $request->att_clock_in;
            $data->att_clock_out  = $request->att_clock_out;
            $data->keterangan     = $request->keterangan;
            $data->update_by      =  $request->auth->id;
            // Jika ada file, baru proses dan simpan path-nya
            if ($request->hasFile('dokument')) {
                $file = $request->file('dokument');
                $extension = $file->getClientOriginalExtension();
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $originalName = preg_replace('/\s+/', '_', $originalName);

                if (!Storage::disk('public')->exists('wfh')) {
                    Storage::disk('public')->makeDirectory('wfh');
                }

                $existingFiles = Storage::disk('public')->files('wfh');
                $runningNumber = count($existingFiles) + 1;

                $filename = "{$runningNumber}-{$originalName}.{$extension}";
                $path = $file->storeAs('wfh', $filename, 'public');

                $data->dokument = $path;
            }
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

    public function updateHead(Request $request, $id)
        {
        try {
            $data = YsbWfh::where(['id' => $id, 'state' => true])->first();
            if(!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => "Data Tidak Ditemukan!"
                ], HttpStatusCodes::HTTP_BAD_REQUEST);                
            }

            $data->att_clock_in   = $request->att_clock_in;
            $data->att_clock_out  = $request->att_clock_out;
            $data->keterangan     = $request->keterangan;
            $data->update_by      =  $request->auth->id;
            // Jika ada file, baru proses dan simpan path-nya
            if ($request->hasFile('dokument')) {
                $file = $request->file('dokument');
                $extension = $file->getClientOriginalExtension();
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $originalName = preg_replace('/\s+/', '_', $originalName);

                if (!Storage::disk('public')->exists('wfh')) {
                    Storage::disk('public')->makeDirectory('wfh');
                }

                $existingFiles = Storage::disk('public')->files('wfh');
                $runningNumber = count($existingFiles) + 1;

                $filename = "{$runningNumber}-{$originalName}.{$extension}";
                $path = $file->storeAs('wfh', $filename, 'public');

                $data->dokument = $path;
            }
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

    public function updateHr(Request $request, $id)
    {
        try {
            $data = YsbWfh::where(['id' => $id, 'state' => true])->first();
            if(!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => "Data Tidak Ditemukan!"
                ], HttpStatusCodes::HTTP_BAD_REQUEST);                
            }

            $data->att_clock_in   = $request->att_clock_in;
            $data->att_clock_out  = $request->att_clock_out;
            $data->keterangan     = $request->keterangan;
            $data->update_by      = $request->auth->id;
            // Jika ada file, baru proses dan simpan path-nya
            if ($request->hasFile('dokument')) {
                $file = $request->file('dokument');
                $extension = $file->getClientOriginalExtension();
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $originalName = preg_replace('/\s+/', '_', $originalName);

                if (!Storage::disk('public')->exists('wfh')) {
                    Storage::disk('public')->makeDirectory('wfh');
                }

                $existingFiles = Storage::disk('public')->files('wfh');
                $runningNumber = count($existingFiles) + 1;

                $filename = "{$runningNumber}-{$originalName}.{$extension}";
                $path = $file->storeAs('wfh', $filename, 'public');

                $data->dokument = $path;
            }
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
            $data = YsbWfh::where(['id' => $id, 'state' => true])->first();
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

    public function approveHeadSchool(Request $request, $id)
    {
        try {
            $data = YsbWfh::where(['id' => $id, 'state' => true])->first();
            if (!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'Absen Tidak Ditemukan!'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $data->approve_head_school = true;
            $data->approve_at_head = Carbon::now()->format('Y-m-d H:i:s');
            $data->approve_by_head = $request->auth->id;
            $data->update_by = $request->auth->id;
            $data->save();
            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Success to approve data'
            ], HttpStatusCodes::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $th->getMessage()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
    }

    public function cancelApproveHeadSchool(Request $request, $id)
    {
        try {
            $data = YsbWfh::where(['id' => $id, 'state' => true])->first();
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
                'message' => 'Success to cancel data'
            ], HttpStatusCodes::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $th->getMessage()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
    }

    public function approveHrSchool(Request $request, $id)
    {
        try {
            $data = YsbWfh::where(['id' => $id, 'state' => true])->first();
            if (!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'Absen Tidak Ditemukan!'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $data->approve_hr = true;
            $data->approve_at_hr = Carbon::now()->format('Y-m-d H:i:s');
            $data->approve_by_hr = $request->auth->id;
            $data->update_by = $request->auth->id;
            $data->save();
            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Success to approve data'
            ], HttpStatusCodes::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $th->getMessage()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
    }

    public function cancelApproveHrSchool(Request $request, $id)
    {
        try {
            $data = YsbWfh::where(['id' => $id, 'state' => true])->first();
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
                'message' => 'Success to cancel data'
            ], HttpStatusCodes::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $th->getMessage()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
    }

    public function approveHrSchoolAll(Request $request) 
    {
        // Validasi payload agar selectedRows harus berupa array
        $validator = Validator::make($request->all(), [
            'selectedRows' => 'required|array',
            // 'selectedRows.*' => 'uuid|exists:ysb_attendance_dailys,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error' => true,
                'message' => $validator->errors()->first()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }

        try {
            $ids = $request->input('selectedRows');
            // Ambil data yang valid dan aktif
            $records = YsbWfh::whereIn('id', $ids)
                ->where('state', true)
                ->get();

            if ($records->count() !== count($ids)) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'Beberapa absen tidak ditemukan atau tidak valid!'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }

            // Update data
            foreach ($records as $record) {
                $record->approve_hr = true;
                $record->approve_at_hr = Carbon::now()->format('Y-m-d H:i:s');
                $record->approve_by_hr = $request->auth->id;
                $record->update_by = $request->auth->id;
                $record->save();
            }

            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Success to approve selected records'
            ], HttpStatusCodes::HTTP_OK);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error' => true,
                'message' => $th->getMessage()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
    }

    public function approveHeadSchoolAll(Request $request) 
    {
        // Validasi payload agar selectedRows harus berupa array
        $validator = Validator::make($request->all(), [
            'selectedRows' => 'required|array',
            // 'selectedRows.*' => 'uuid|exists:ysb_attendance_dailys,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error' => true,
                'message' => $validator->errors()->first()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }

        try {
            $ids = $request->input('selectedRows');
            // Ambil data yang valid dan aktif
            $records = YsbWfh::whereIn('id', $ids)
                ->where('state', true)
                ->get();

            if ($records->count() !== count($ids)) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'Beberapa data tidak ditemukan atau tidak valid!'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }

            // Update data
            foreach ($records as $record) {
                $record->approve_head_school = true;
                $record->approve_at_head = Carbon::now()->format('Y-m-d H:i:s');
                $record->approve_by_head = $request->auth->id;
                $record->update_by = $request->auth->id;
                $record->save();
            }

            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Success to approve selected records'
            ], HttpStatusCodes::HTTP_OK);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error' => true,
                'message' => $th->getMessage()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
    }

    public function recapWfh(Request $request)
    {
        try {
            // Ambil periode berdasarkan monthYear dan state
            $data = YsbPeriod::where('state', true);

            if($request->filled('monthYear')) {
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
           $teachers = YsbWfh::with(['teacherDetail' => function ($query) {
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

}

