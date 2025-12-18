<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Kis\RoleAssign;
use App\Kis\LogActivity;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Kis\PaginationFormat;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\RoleCollection;
use App\Kis\HttpStatusCodes;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Role $TblData)
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
                $query->where('name', 'LIKE', '%' . $request->search . '%');
            });
            $data->where('state', true);
            $result = $data->orderBy('created_at', $request->ascending == true ? 'asc' : 'desc')->paginate($request->limit);

            LogActivity::addToLog('Successfully get menu list', $request->auth->id);

            return new RoleCollection($result);
        } catch (\Throwable $th) {
            LogActivity::addToLog($th->getMessage(), $request->auth->id);
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
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
            'name' => 'required',
            'icon_name' => 'required',
            'ysb_branch_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        try {
            $findByName = Role::where(['name' => $request->name, 'state' => true])->first();
            if ($findByName) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The name has already been taken'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $save = Role::create([
                'name' => $request->name,
                'slug_name' => Str::slug($request->name, '_'),
                'icon_name' => $request->icon_name,
                'ysb_branch_id' => $request->ysb_branch_id,
                'create_by' => $request->auth->id
            ]);
            if ($save) {
                RoleAssign::addSURole($save->id, $request->auth->id);
                // LogActivity::addToLog('Success to create data role', $request->auth->id);

                return response()->json([
                    'status' => HttpStatusCodes::HTTP_OK,
                    'error' => false,
                    'message' => 'Success to create data role'
                ], HttpStatusCodes::HTTP_OK);
            }
            LogActivity::addToLog('Fail to create data role', $request->auth->id);

            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error' => true,
                'message' => 'Fail to create data role'
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
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
    public function show(Request $request, $id)
    {
        try {
            $data = Role::where(['id' => $id, 'state' => true])->first();
            if (!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The role not found'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            LogActivity::addToLog('Success to get data role', $request->auth->id);
            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'data' => $data
            ], HttpStatusCodes::HTTP_OK);
        } catch (\Throwable $th) {
            LogActivity::addToLog($th->getMessage(), $request->auth->id);
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
            'name' => 'required',
            'icon_name' => 'required',
            'ysb_branch_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        try {
            $findData = Role::where(['id' => $id, 'state' => true])->first();
            if (!$findData) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The role not found'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $findByName = Role::where(['name' => $request->name, 'state' => true])->where('id', '!=', $id)->first();
            if ($findByName) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The name has already been taken'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $findData->name = $request->name;
            $findData->slug_name = Str::slug($request->name, '_');
            $findData->icon_name = $request->icon_name;
            $findData->ysb_branch_id = $request->ysb_branch_id;
            $findData->update_by = $request->auth->id;
            $findData->save();

            LogActivity::addToLog('Success to update data role', $request->auth->id);

            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Success to update data role'
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
        DB::beginTransaction();
        try {
            $findRole = Role::where(['id' => $id, 'state' => true])->first();
            if (!$findRole) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The role not found'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $findRole->state = false;
            $findRole->update_by = $request->auth->id;
            if ($findRole->save()) {
                RoleAssign::removeSURole($id, $request->auth->id);
                DB::commit();
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_OK,
                    'error' => false,
                    'message' => 'Success to delete data role'
                ], HttpStatusCodes::HTTP_OK);
            }
            DB::rollBack();
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error' => true,
                'message' => 'Fail to delete data role'
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $th->getMessage()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexTeachers(Request $request, Role $TblData)
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
                $data->whereNotIn('name', ['HR Bandung', 'HR Makassar', 'HR Serpong', 'HR Bekasi', 'HR']);
            }else{
                $data->when((string)$request->branch != null, function ($query) use ($request) {
                    $branch = $request->branch;
                    $query->where(function ($query) use ($branch) {
                        $query->where('ysb_branch_id', 'LIKE', '%' . $branch . '%');
                    });
                })->whereNotIn('name', ['HR Bandung', 'HR Makassar', 'HR Serpong', 'HR Bekasi', 'HR']);
            }
          
            $data->where('state', true);
            $result = $data->orderBy('created_at', $request->ascending == true ? 'asc' : 'desc')->paginate($request->limit);

            return new RoleCollection($result);
        } catch (\Throwable $th) {
            LogActivity::addToLog($th->getMessage(), $request->auth->id);
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $th->getMessage()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
    }

      /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexDeveloper(Request $request, Role $TblData)
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
            $data->whereIn('name', ['HR Bandung', 'HR Makassar', 'HR Serpong', 'HR Bekasi', 'HR']);
            $data->where('state', true);
            $result = $data->orderBy('created_at', $request->ascending == true ? 'asc' : 'desc')->paginate($request->limit);

            return new RoleCollection($result);
        } catch (\Throwable $th) {
            LogActivity::addToLog($th->getMessage(), $request->auth->id);
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $th->getMessage()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
    }
}
