<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\SDC\RestCurl;
use App\Models\YsbBranch;
use Illuminate\Support\Str;
use App\Kis\HttpStatusCodes;
use Illuminate\Http\Request;
use App\Kis\PaginationFormat;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\YsbBranchCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class YsbBranchController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, YsbBranch $TblData)
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
            $data->when((string)$request->search != null, function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($query) use ($search) {
                    $query->where('branch_name', 'LIKE', '%' . $search . '%');
                });
            });
            
            $data->where('state', true);
            $result = $data->orderBy('created_at', $request->ascending == true ? 'asc' : 'desc')->paginate($request->limit);

            return new YsbBranchCollection($result);
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
            'parent_id'         => 'nullable',
            'branch_code'       => 'nullable',
            'branch_name'       => 'nullable',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'error'  => true,
                'message' => $validator->errors()->all()[0]
            ], 400);
        }
    
        // Check if Name exists
        if ($request->branch_name) {
            $checkBranch = YsbBranch::where(['branch_name' => $request->branch_name, 'state' => true])->first();
            if ($checkBranch) {
                return response()->json([
                    'status' => 400,
                    'error'  => true,
                    'message' => 'Nama Branch Sudah Ada!'
                ], 400);
            }
        }
    
        try {
            // Save data
            $save = YsbBranch::create([
                'parent_id'         => $request->parent_id,
                'branch_code'       => $request->branch_code,
                'branch_name'       => $request->branch_name,
                'slug_name'         => Str::slug($request->branch_name, '_'),
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
            $data = YsbBranch::where(['id' => $id, 'state' => true])->first();
            if(!$data)
            {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error' => true,
                'message' => "Branch Tidak Ditemukan!"
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
            'parent_id'         => 'nullable',
            'branch_code'       => 'nullable',
            'branch_name'       => 'nullable'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        try {
            $data = YsbBranch::where(['id' => $id, 'state' => true])->first();
            if(!$data)
            {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => "Branch Tidak Ditemukan!"
                ], HttpStatusCodes::HTTP_BAD_REQUEST);                
            }
            $data->parent_id        = $request->parent_id;
            $data->branch_code      = $request->branch_code;
            $data->branch_name      = $request->branch_name;
            $data->slug_name        = Str::slug($request->branch_name, '_');
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
            $data = YsbBranch::where(['id' => $id, 'state' => true])->first();
            if (!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'Branch Tidak Ditemukan!'
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

