<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Module;
use App\Kis\LogActivity;
use App\Kis\HttpStatusCodes;
use Illuminate\Http\Request;
use App\Kis\PaginationFormat;
use App\Models\ModuleHasMenu;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ModuleMenuCollection;

class ModuleMenuController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, ModuleHasMenu $TblData)
    {
        $validator = Validator::make($request->all(), array_merge(PaginationFormat::VALIDATION,[
            'id_module' => 'required|exists:modules,id'
        ]));
        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        try {
            $checkModule = Module::where(['id' => $request->id_module, 'state' => true])->first();
            if(!$checkModule)
            {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'   => true,
                    'message' => 'The module not found'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $data = $TblData->newQuery();
            $data->when((string)$request->search != null, function ($query) use ($request) {
                $query->where('id_menu', function($subquery) use($request){
                    $subquery->select('id')->from('menus')->where('name', 'LIKE', '%' . $request->search . '%');
                });
            });
            $data->where('id_module', $request->id_module)->where('state', true);
            $result = $data->orderBy('created_at', $request->ascending == true ? 'asc' : 'desc')->paginate($request->limit);

            LogActivity::addToLog('Successfully get module menu list', $request->auth->id);

            return new ModuleMenuCollection($result);
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
            'id_module'       => 'required|exists:modules,id',
            'menus'           => 'required|array',
            'menus.*.id_menu' => 'required|exists:menus,id'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        DB::beginTransaction();
        try {
            foreach ($request->menus as $value) {
                $findMenu = Menu::where('id', $value['id_menu'])->first();
                if (!$findMenu->state) {
                    DB::rollBack();
                    return response()->json([
                        'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                        'error' => true,
                        'message' => 'Menu ' . $findMenu->name . ' not found!'
                    ], HttpStatusCodes::HTTP_BAD_REQUEST);
                }
                $checkData = ModuleHasMenu::where(['id_module' => $request->id_module, 'id_menu' => $value['id_menu'], 'state' => true])->first();
                if ($checkData) {
                    DB::rollBack();
                    return response()->json([
                        'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                        'error' => true,
                        'message' => 'The menu '.$findMenu->name.' has already been taken!'
                    ], HttpStatusCodes::HTTP_BAD_REQUEST);
                }
                ModuleHasMenu::create([
                    'id_module' => $request->id_module,
                    'id_menu' => $value['id_menu'],
                    'create_by' => $request->auth->id
                ]);
            }
            LogActivity::addToLog('Success to create data module has menu', $request->auth->id);
            DB::commit();
            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Success to create data module has menu'
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
    public function show($id)
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'menus'           => 'required|array',
            'menus.*.id_menu' => 'required|exists:menus,id'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        DB::beginTransaction();
        try {
            $findModule = Module::where(['id' => $id, 'state' => true])->first();
            if(!$findModule)
            {
                DB::rollBack();
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The Module not found!'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);                
            }
            foreach ($request->menus as $value) {
                $findMenu = Menu::where('id', $value['id_menu'])->first();
                if (!$findMenu->state) {
                    DB::rollBack();
                    return response()->json([
                        'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                        'error' => true,
                        'message' => 'Menu ' . $findMenu->name . ' not found!'
                    ], HttpStatusCodes::HTTP_BAD_REQUEST);
                }
                $checkData = ModuleHasMenu::where(['id_module' => $id, 'id_menu' => $value['id_menu'], 'state' => true])->first();
                if (!$checkData) {
                    DB::rollBack();
                    return response()->json([
                        'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                        'error' => true,
                        'message' => 'The menu ' . $findMenu->name . ' has already been deleted!'
                    ], HttpStatusCodes::HTTP_BAD_REQUEST);
                }
                $checkData->state = false;
                $checkData->update_by = $request->auth->id;
                $checkData->save();
            }
            LogActivity::addToLog('Success to delete data module has menu', $request->auth->id);
            DB::commit();
            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Success to delete data module has menu'
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
}
