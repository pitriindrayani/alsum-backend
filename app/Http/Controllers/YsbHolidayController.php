<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\SDC\RestCurl;
use App\Models\YsbHoliday;
use App\Models\YsbTeacher;
use App\Models\YsbSchool;
use Illuminate\Support\Str;
use App\Kis\HttpStatusCodes;
use Illuminate\Http\Request;
use App\Kis\PaginationFormat;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\YsbHolidayCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class YsbHolidayController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, YsbHoliday $TblData)
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
            
            $data = $data->when(!empty($request->search), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($query) use ($search) {
                    $query->where('ysb_branch_id', 'LIKE', '%' . $search . '%')
                        ->orWhere('ysb_school_id', 'LIKE', '%' . $search . '%')
                        ->orWhere('full_name', 'LIKE', '%' . $search . '%')
                        ->orWhere('holiday_name', 'LIKE', '%' . $search . '%')
                        ->orWhere('holiday_date', 'LIKE', '%' . $search . '%')
                        ->orWhere('holiday_date_end', 'LIKE', '%' . $search . '%');                
                    });
            });
            
            $data->where('state', true);
            $result = $data->orderBy('created_at', $request->ascending == true ? 'asc' : 'desc')->paginate($request->limit);

            return new YsbHolidayCollection($result);
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
            'array_id_teacher'   => 'required|array',
            'array_id_teacher.*' => 'exists:ysb_teachers,id',
            'ysb_branch_id'      => 'required',
            'ysb_school_id'      => 'required',
            'holiday_name'       => 'required',
            'holiday_date'       => 'required',
            'holiday_date_end'   => 'required',
            // 'holiday_weekday'    => 'required',
            'holiday_type_id'    => 'required'
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
                YsbHoliday::create([
                    'ysb_teacher_id'     => $teacherId,
                    'ysb_branch_id'      => $request->ysb_branch_id,
                    'ysb_school_id'      => $request->ysb_school_id,
                    'full_name'          => $checkTeacher->full_name,
                    'holiday_name'       => $request->holiday_name,
                    'holiday_date'       => $request->holiday_date,
                    'holiday_date_end'   => $request->holiday_date_end,
                    'holiday_weekday'    => $request->holiday_weekday,
                    'holiday_type_id'    => $request->holiday_type_id,
                    'create_by'          => $request->auth->id
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
            $data = YsbHoliday::where(['id' => $id, 'state' => true])->first();
            if(!$data)
            {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error' => true,
                'message' => "Kalender Libur Tidak Ditemukan!"
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
            'ysb_branch_id'      => 'required',
            'ysb_school_id'      => 'required',
            'ysb_teacher_id'     => 'required',
            'holiday_name'       => 'required',
            'holiday_date'       => 'required',
            'holiday_date_end'   => 'required',
            // 'holiday_weekday'    => 'required',
            'holiday_type_id'    => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        try {
            $checkTeacher = YsbTeacher::where(['id' => $request->ysb_teacher_id, 'state' => true])->first();
            $data = YsbHoliday::where(['id' => $id, 'state' => true])->first();
            if(!$data)
            {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => "Kalender Libur Tidak Ditemukan!"
                ], HttpStatusCodes::HTTP_BAD_REQUEST);                
            }

            $data->ysb_branch_id     = $request->ysb_branch_id;
            $data->ysb_school_id     = $request->ysb_school_id;
            $data->ysb_teacher_id    = $request->ysb_teacher_id;
            $data->full_name         = $checkTeacher->full_name;
            $data->holiday_name      = $request->holiday_name;
            $data->holiday_date      = $request->holiday_date;
            $data->holiday_date_end  = $request->holiday_date_end;
            // $data->holiday_weekday   = $request->holiday_weekday;
            $data->holiday_type_id   = $request->holiday_type_id;
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
            $data = YsbHoliday::where(['id' => $id, 'state' => true])->first();
            if (!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'Kalender Libur Tidak Ditemukan!'
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

