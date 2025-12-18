<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\SDC\RestCurl;
use App\Models\YsbSchedule;
use App\Models\YsbPosition;
use Illuminate\Support\Str;
use App\Kis\HttpStatusCodes;
use Illuminate\Http\Request;
use App\Kis\PaginationFormat;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\YsbScheduleCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class YsbScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, YsbSchedule $TblData)
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

            return new YsbScheduleCollection($result);
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
            'ysb_school_id'     => 'required',
            'ysb_position_id'   => 'required',
            'in_time'           => 'required',
            'out_time'          => 'required',
            'day_1'             => 'required',
            'day_2'             => 'required',
            'day_3'             => 'required',
            'day_4'             => 'required',
            'day_5'             => 'required',
            'day_6'             => 'required',
            'day_7'             => 'required',
            'fg_school_default' => 'nullable',
            'holiday_type'      => 'nullable'
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
            $findPosition = YsbPosition::where(['id' => $request->ysb_position_id, 'state' => true])->first();

            $save = YsbSchedule::create([
                'ysb_school_id'         => $request->ysb_school_id,
                'ysb_position_id'       => $request->ysb_position_id,
                'ysb_position_code'     => $findPosition->position_code,
                'schedule_code'         => $findPosition->position,
                'in_time'               => $request->in_time,
                'out_time'              => $request->out_time,
                'day_1'                 => $request->day_1,
                'day_2'                 => $request->day_2,
                'day_3'                 => $request->day_3,
                'day_4'                 => $request->day_4,
                'day_5'                 => $request->day_5,
                'day_6'                 => $request->day_6,
                'day_7'                 => $request->day_7,
                'fg_school_default'     => $request->fg_school_default,
                'holiday_type'          => $request->holiday_type,
                'create_by'             => $request->auth->id
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
            $data = YsbSchedule::where(['id' => $id, 'state' => true])->first();
            if(!$data)
            {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error' => true,
                'message' => "Jadwal Tidak Ditemukan!"
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
            'ysb_school_id'     => 'required',
            'ysb_position_id'   => 'required',
            'in_time'           => 'required',
            'out_time'          => 'required',
            'day_1'             => 'required',
            'day_2'             => 'required',
            'day_3'             => 'required',
            'day_4'             => 'required',
            'day_5'             => 'required',
            'day_6'             => 'required',
            'day_7'             => 'required',
            'fg_school_default' => 'nullable',
            'holiday_type'      => 'nullable'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        try {
            $data = YsbSchedule::where(['id' => $id, 'state' => true])->first();
            if(!$data)
            {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => "Jadwal Tidak Ditemukan!"
                ], HttpStatusCodes::HTTP_BAD_REQUEST);                
            }

            $findPosition = YsbPosition::where(['id' => $request->ysb_position_id, 'state' => true])->first();

            $data->ysb_school_id        = $request->ysb_school_id;
            $data->ysb_position_id      = $request->ysb_position_id;
            $data->ysb_position_code    = $findPosition->position_code;
            $data->schedule_code        = $findPosition->position;
            $data->in_time              = $request->in_time;
            $data->out_time             = $request->out_time;
            $data->day_1                = $request->day_1;
            $data->day_2                = $request->day_2;
            $data->day_3                = $request->day_3;
            $data->day_4                = $request->day_4;
            $data->day_5                = $request->day_5;
            $data->day_6                = $request->day_6;
            $data->day_7                = $request->day_7;
            $data->fg_school_default    = $request->fg_school_default;
            $data->holiday_type         = $request->holiday_type;
            $data->update_by        = $request->auth->id;
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
            $data = YsbSchedule::where(['id' => $id, 'state' => true])->first();
            if (!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'Jadwal Tidak Ditemukan!'
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

