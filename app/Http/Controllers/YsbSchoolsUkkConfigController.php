<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\SDC\RestCurl;
use App\Models\YsbSchoolsUkkConfig;
use Illuminate\Support\Str;
use App\Kis\HttpStatusCodes;
use Illuminate\Http\Request;
use App\Kis\PaginationFormat;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\YsbSchoolsUkkConfigCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class YsbSchoolsUkkConfigController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, YsbSchoolsUkkConfig $TblData)
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
                        $query->where('branch_code', 'LIKE', '%' . $branch . '%');
                    });
                });
            }

            $data = $data->when(!empty($request->search), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($query) use ($search) {
                    $query->where('ysb_branch_id', 'LIKE', '%' . $search . '%')
                        ->orWhere('ysb_school_id', 'LIKE', '%' . $search . '%')
                        ->orWhere('ysb_position_id', 'LIKE', '%' . $search . '%')
                        ->orWhere('ukk', 'LIKE', '%' . $search . '%')
                        ->orWhere('ukk_baru', 'LIKE', '%' . $search . '%')
                        ->orWhere('ut', 'LIKE', '%' . $search . '%')
                        ->orWhere('um', 'LIKE', '%' . $search . '%');
                });
            });
            
            $data->where('state', true);
            $result = $data->orderBy('created_at', $request->ascending == true ? 'asc' : 'desc')->paginate($request->limit);

            return new YsbSchoolsUkkConfigCollection($result);
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
            'ysb_branch_id'     => 'required',
            'ysb_school_id'     => 'required',
            'ysb_position_id'   => 'required',
            'kinerja'           => 'nullable',
            'ukk'               => 'required',
            'ukk_baru'          => 'required',
            'ut'                => 'required',
            'um'                => 'required'
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
            $save = YsbSchoolsUkkConfig::create([
                'ysb_branch_id'     => $request->ysb_branch_id,
                'ysb_school_id'     => $request->ysb_school_id,
                'ysb_position_id'   => $request->ysb_position_id,
                'kinerja'           => $request->kinerja,
                'ukk'               => $request->ukk,
                'ukk_baru'          => $request->ukk_baru,
                'ut'                => $request->ut,
                'um'                => $request->um,
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
            $data = YsbSchoolsUkkConfig::where(['id' => $id, 'state' => true])->first();
            if(!$data)
            {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error' => true,
                'message' => "Data Tidak Ditemukan!"
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
           'ysb_branch_id'     => 'required',
            'ysb_school_id'     => 'required',
            'ysb_position_id'   => 'required',
            'kinerja'           => 'nullable',
            'ukk'               => 'required',
            'ukk_baru'          => 'required',
            'ut'                => 'required',
            'um'                => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        try {
            $data = YsbSchoolsUkkConfig::where(['id' => $id, 'state' => true])->first();
            if(!$data)
            {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => "Data Tidak Ditemukan!"
                ], HttpStatusCodes::HTTP_BAD_REQUEST);                
            }
            $data->ysb_branch_id        = $request->ysb_branch_id;
            $data->ysb_school_id        = $request->ysb_school_id;
            $data->ysb_position_id      = $request->ysb_position_id;
            $data->kinerja              = $request->kinerja;
            $data->ukk                  = $request->ukk;
            $data->ukk_baru             = $request->ukk_baru;
            $data->ut                   = $request->ut;
            $data->um                   = $request->um;        
            $data->update_by            = $request->auth->id;
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
            $data = YsbSchoolsUkkConfig::where(['id' => $id, 'state' => true])->first();
            if (!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'Data Tidak Ditemukan!'
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

