<?php

namespace App\Http\Controllers;

use App\Kis\LogActivity;
use Illuminate\Http\Request;
use App\Kis\PaginationFormat;
use App\Models\RoleHasPermission;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\RolePermissionCollection;
use App\Kis\HttpStatusCodes;
use App\Models\Menu;

class RolePermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $idRole, RoleHasPermission $TblData)
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
            $data->where('id_role', $idRole);
            $data->when((string)$request->search != null, function ($query) use ($request) {
                $query->orWhereIn('id_menu', function($subQuery) use($request){
                    $subQuery->select('id')->from('menus')->where('name', 'LIKE','%'.$request->search.'%');
                });
            });
            $data->where('state', true);
            $result = $data->orderBy('created_at', $request->ascending == true ? 'asc' : 'desc')->paginate($request->limit);

            LogActivity::addToLog('Successfully get role permission list', $request->auth->id);

            return new RolePermissionCollection($result);
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
            'id_role'         => 'required|exists:roles,id',
            'menus'           => 'required|array',
            'menus.*.id_menu' => 'required|exists:menus,id',
            'menus.*.create'  => 'required|boolean',
            'menus.*.read'    => 'required|boolean',
            'menus.*.update'  => 'required|boolean',
            'menus.*.delete'  => 'required|boolean'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        try {
            foreach ($request->menus as $key => $value) {
                $findMenu = Menu::where('id', $value['id_menu'])->first();
                if(!$findMenu->state)
                {
                    return response()->json([
                        'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                        'error' => true,
                        'message' => 'Menu number '.$findMenu->name.' not found!'
                    ], HttpStatusCodes::HTTP_BAD_REQUEST);                    
                }
                $checkData = RoleHasPermission::where(['id_role' => $request->id_role, 'id_menu' => $value['id_menu'], 'state' => true])->first();
                if ($checkData) {
                    return response()->json([
                        'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                        'error' => true,
                        'message' => 'The data has already been taken! Please use update.'
                    ], HttpStatusCodes::HTTP_BAD_REQUEST);
                }
                RoleHasPermission::create([
                    'id_role' => $request->id_role,
                    'id_menu' => $value['id_menu'],
                    'create' => $value['create'],
                    'read' => $value['read'],
                    'update' => $value['update'],
                    'delete' => $value['delete'],
                    'create_by' => $request->auth->id
                ]);
            }
            LogActivity::addToLog('Success to create data role permission', $request->auth->id);
            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Success to create data role permission'
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

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        //
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
        try {
            $validator = Validator::make($request->all(), [
                'create'  => 'required|boolean',
                'read'    => 'required|boolean',
                'update'  => 'required|boolean',
                'delete'  => 'required|boolean'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'   => true,
                    'message' => $validator->errors()->all()[0]
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $data = RoleHasPermission::where(['id' => $id, 'state' => true])->first();
            if(!$data)
            {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'   => true,
                    'message' => "Role permission not found"
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $data->create = $request->create;
            $data->read = $request->read;
            $data->update = $request->update;
            $data->delete = $request->delete;
            $data->save();
            LogActivity::addToLog('Success to update data role permission', $request->auth->id);
            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Success to update data role permission'
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try {
            $data = RoleHasPermission::where(['id' => $id, 'state' => true])->first();
            if (!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The role permission not found'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $data->state = false;
            $data->update_by = $request->auth->id;
            $data->save();

            LogActivity::addToLog('Successfully delete data role permission', $request->auth->id);

            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Success to delete data role permission'
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
