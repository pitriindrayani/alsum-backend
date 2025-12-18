<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\SDC\RestCurl;
use App\Models\YsbSchool;
use Illuminate\Support\Str;
use App\Kis\HttpStatusCodes;
use Illuminate\Http\Request;
use App\Kis\PaginationFormat;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\YsbSchoolCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class YsbSchoolController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, YsbSchool $TblData)
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
            }else if($request->ysb_school_id != null){
                $data->when((string)$request->ysb_school_id != null, function ($query) use ($request) {
                    $ysb_school_id = $request->ysb_school_id;
                    $query->where(function ($query) use ($ysb_school_id) {
                        $query->where('school_code', 'LIKE', '%' . $ysb_school_id . '%');
                    });
                });
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
                    $query->where('school_name', 'LIKE', '%' . $search . '%')
                          ->orWhere('province', 'LIKE', '%' . $search . '%')
                          ->orWhere('address', 'LIKE', '%' . $search . '%')
                          ->orWhere('email', 'LIKE', '%' . $search . '%')
                          ->orWhere('phone', 'LIKE', '%' . $search . '%');
                });
            });
            
            $data->where('state', true);
            $result = $data->orderBy('created_at', $request->ascending == true ? 'asc' : 'desc')->paginate($request->limit);

            return new YsbSchoolCollection($result);
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
            'school_code'       => 'nullable',
            'school_name'       => 'nullable',
            'npsn'              => 'nullable',
            'province'          => 'nullable',
            'district'          => 'nullable',
            'subdistrict'       => 'nullable',
            'address'           => 'nullable',
            'postal_code'       => 'nullable',
            'edu_stage'         => 'nullable',
            'phone'             => 'nullable',
            'website'           => 'nullable',
            'email'             => 'nullable',
            'school_logo'       => 'nullable',
            'nss'               => 'nullable',
            'village'           => 'nullable',
            'footer_school_name'=> 'nullable',
            'akreditasi'        => 'nullable'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'error'  => true,
                'message' => $validator->errors()->all()[0]
            ], 400);
        }
    
        // Check if Name exists
        if ($request->school_name) {
            $checkSchoolName = YsbSchool::where('school_name', '=', $request->school_name)->first();
            if ($checkSchoolName) {
                return response()->json([
                    'status' => 400,
                    'error'  => true,
                    'message' => 'Nama Sekolah Sudah Ada!'
                ], 400);
            }
        }
    
        try {
            // Save data
            $save = YsbSchool::create([
                'ysb_branch_id'     => $request->ysb_branch_id,
                'school_code'       => $request->school_code,
                'school_name'       => $request->school_name,
                'slug_name'         => Str::slug($request->school_name, '_'),
                'npsn'              => $request->npsn,
                'province'          => $request->province,
                'district'          => $request->district,
                'subdistrict'       => $request->subdistrict,
                'address'           => $request->address,
                'postal_code'       => $request->postal_code,
                'edu_stage'         => $request->edu_stage,
                'phone'             => $request->phone,
                'website'           => $request->website,
                'email'             => $request->email,
                'school_logo'       => $request->school_logo,
                'nss'               => $request->nss,
                'village'           => $request->village,
                'footer_school_name'=> $request->footer_school_name,
                'akreditasi'        => $request->akreditasi,
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
            $data = YsbSchool::where(['id' => $id, 'state' => true])->first();
            if(!$data)
            {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error' => true,
                'message' => "Sekolah Tidak Ditemukan!"
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
            'school_code'       => 'nullable',
            'school_name'       => 'nullable',
            'npsn'              => 'nullable',
            'province'          => 'nullable',
            'district'          => 'nullable',
            'subdistrict'       => 'nullable',
            'address'           => 'nullable',
            'postal_code'       => 'nullable',
            'edu_stage'         => 'nullable',
            'phone'             => 'nullable',
            'website'           => 'nullable',
            'email'             => 'nullable',
            'school_logo'       => 'nullable',
            'nss'               => 'nullable',
            'village'           => 'nullable',
            'footer_school_name'=> 'nullable',
            'akreditasi'        => 'nullable'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        try {
            $data = YsbSchool::where(['id' => $id, 'state' => true])->first();
            if(!$data)
            {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => "Sekolah Tidak Ditemukan!"
                ], HttpStatusCodes::HTTP_BAD_REQUEST);                
            }
            $data->ysb_branch_id    = $request->ysb_branch_id;
            $data->school_code      = $request->school_code;
            $data->school_name      = $request->school_name;
            $data->slug_name        = Str::slug($request->school_name, '_');
            $data->npsn             = $request->npsn;
            $data->province         = $request->province;
            $data->district         = $request->district;
            $data->subdistrict      = $request->subdistrict;
            $data->address          = $request->address;
            $data->postal_code      = $request->postal_code;
            $data->edu_stage        = $request->edu_stage;
            $data->phone            = $request->phone;
            $data->website          = $request->website;
            $data->email            = $request->email;
            $data->school_logo      = $request->school_logo;
            $data->nss              = $request->nss;
            $data->village          = $request->village;
            $data->footer_school_name = $request->footer_school_name;
            $data->akreditasi       = $request->akreditasi;
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
            $data = YsbSchool::where(['id' => $id, 'state' => true])->first();
            if (!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'Sekolah Tidak Ditemukan!'
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

