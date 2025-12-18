<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\SDC\RestCurl;
use App\Models\YsbTeacherStatusRecord;
use Illuminate\Support\Str;
use App\Kis\HttpStatusCodes;
use Illuminate\Http\Request;
use App\Kis\PaginationFormat;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\YsbTeacherStatusRecordCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class YsbTeacherStatusRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, YsbTeacherStatusRecord $TblData)
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
            $data->when((string)$request->ysb_branch_id != null, function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($query) use ($search) {
                    $query->where('status_code', 'LIKE', '%' . $search . '%');
                });
            });
            $data->when((string)$request->search != null, function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($query) use ($search) {
                    $query->where('status_code', 'LIKE', '%' . $search . '%')
                    ->orWhere('status', 'LIKE', '%' . $search . '%');
                });
            });
            
            $data->where('state', true);
            $result = $data->orderBy('created_at', $request->ascending == true ? 'asc' : 'desc')->paginate($request->limit);

            return new YsbTeacherStatusRecordCollection($result);
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
            'ysb_id_teacher'    => 'required',
            'status_code'       => 'required',
            'date'              => 'required',
            'nip_ypi'           => 'required'
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
            $save = YsbTeacherStatusRecord::create([
                'ysb_id_teacher'    => $request->ysb_id_teacher,
                'status_code'       => $request->status_code,
                'date'              => $request->date,
                'nip_ypi'           => $request->nip_ypi,
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
            $data = YsbTeacherStatusRecord::where(['id' => $id, 'state' => true])->first();
            if(!$data){
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => "Status Tidak Ditemukan!"
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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showArray($id)
    {
        try {
            $data = YsbTeacherStatusRecord::where(['ysb_id_teacher' => $id, 'state' => true])->orderBy('date', 'asc')->get();
            if(!$data){
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => "Status Tidak Ditemukan!",
                    'data'  => []
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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showArrayUpdate($id)
    {
        try {
            $data = YsbTeacherStatusRecord::where(['ysb_id_teacher' => $id, 'state' => true])->orderBy('date', 'asc')->get();
            if(!$data){
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => "Status Tidak Ditemukan!",
                    'data'  => []
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
            'ysb_id_teacher'    => 'required',
            'status_code'       => 'required',
            'date'              => 'required',
            'nip_ypi'           => 'required'
        ]);

        if($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }

        try {
            $data = YsbTeacherStatusRecord::where(['id' => $id, 'state' => true])->first();
            if(!$data){
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => "Status Tidak Ditemukan!"
                ], HttpStatusCodes::HTTP_BAD_REQUEST);                
            }
            $data->ysb_id_teacher   = $request->ysb_id_teacher;
            $data->status_code      = $request->status_code;
            $data->date             = $request->date;
            $data->nip_ypi          = $request->nip_ypi;
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
            $data = YsbTeacherStatusRecord::where(['id' => $id, 'state' => true])->first();
            if (!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'Status Tidak Ditemukan!'
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

