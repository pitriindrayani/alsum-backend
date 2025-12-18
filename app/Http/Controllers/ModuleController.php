<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Kis\LogActivity;
use Illuminate\Support\Str;
use App\Kis\HttpStatusCodes;
use Illuminate\Http\Request;
use App\Kis\PaginationFormat;
use App\Http\Resources\ModuleCollection;
use Illuminate\Support\Facades\Validator;

class ModuleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Module $TblData)
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

            return new ModuleCollection($result);
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
            // 'color_icon' => 'nulable',
            'number_order' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        try {
            $cekNumber = Module::where(['number_order' => $request->number_order, 'state' => true])->first();
            if ($cekNumber) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The number order has already been taken'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            Module::create([
                'name'         => $request->name,
                'slug_name'    => Str::slug($request->name, '_'),
                'number_order' => $request->number_order,
                'icon_name'    => $request->icon_name,
                // 'color_icon'    => $request->color_icon,
                'create_by'    => $request->auth->id
            ]);

            // LogActivity::addToLog('Successfully create data module', $request->auth->id);

            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Success to create data menu'
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
    public function show(Request $request, $id)
    {
        try {
            $data = Module::where(['id' => $id, 'state' => true])->first();
            if (!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The module not found'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }

            LogActivity::addToLog('Successfully get data module', $request->auth->id);

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
            'color_icon' => 'required',
            'number_order' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        try {
            if ($request->url !== null) {
                $cekNumber = Module::where(['id' => $id,'number_order' => $request->number_order, 'state' => true])->first();
                if ($cekNumber) {
                    return response()->json([
                        'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                        'error' => true,
                        'message' => 'The number order has already been taken'
                    ], HttpStatusCodes::HTTP_BAD_REQUEST);
                }
            }
            $data = Module::where(['id' => $id, 'state' => true])->first();
            if (!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The module not found'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $data->name = $request->name;
            $data->slug_name = Str::slug($request->name, '_');
            $data->icon_name = $request->icon_name;
            $data->color_icon = $request->color_icon;
            $data->number_order = $request->number_order;
            $data->update_by = $request->auth->id;
            $data->save();

            LogActivity::addToLog('Successfully update data module', $request->auth->id);

            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Success to update data menu'
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
            $data = Module::where(['id' => $id, 'state' => true])->first();
            if (!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The menu not found'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $data->state = false;
            $data->update_by = $request->auth->id;
            $data->save();

            LogActivity::addToLog('Successfully delete data module', $request->auth->id);

            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Success to delete data module'
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
