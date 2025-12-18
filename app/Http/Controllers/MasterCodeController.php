<?php

namespace App\Http\Controllers;

use App\Kis\LogActivity;
use App\Models\MasterCode;
use App\Kis\HttpStatusCodes;
use Illuminate\Http\Request;
use App\Kis\PaginationFormat;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MasterCodeCollection;
use App\Kis\GeneratingNumber;
use App\Kis\PatientGeneratingNumber;
use App\Models\GenerateNumber;

class MasterCodeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, MasterCode $TblData)
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
                $query->where('type', 'LIKE', '%' . $request->search . '%');
            });
            $data->where('state', true);
            $result = $data->orderBy('created_at', $request->ascending == true ? 'asc' : 'desc')->paginate($request->limit);

            LogActivity::addToLog('Successfully get master code list', $request->auth->id);

            return new MasterCodeCollection($result);
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
        try {
            $validator = Validator::make($request->all(), [
                'type'         => 'required|in:appointment,invoice,emr,service',
                'start_by'     => 'required|in:general,spesific',
                'first_code'   => 'required',
                'seperate_by'  => 'required|in:/,-',
                'month_type'   => 'required|in:numeral,roman',
                'reset_type'   => 'required|in:monthly,annually',
                'need_initial' => 'required|boolean',
                'initial_by'   => $request->need_initial ? 'required|in:subject,object' : 'nullable',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'   => true,
                    'message' => $validator->errors()->all()[0]
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $checkType = MasterCode::where(['type' => $request->type, 'state' => true])->first();
            if($checkType)
            {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'   => true,
                    'message' => 'The type has been taken'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            if($request->start_by == 'spesific')
            {
                if(!$request->need_initial)
                {
                    return response()->json([
                        'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                        'error'   => true,
                        'message' => 'The need initial must be true. Because start by spesific was selected'
                    ], HttpStatusCodes::HTTP_BAD_REQUEST);
                }
            }
            if($request->start_by == 'general')
            {
                if($request->need_initial)
                {
                    return response()->json([
                        'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                        'error'   => true,
                        'message' => 'The need initial must be false. Because start by general was selected'
                    ], HttpStatusCodes::HTTP_BAD_REQUEST);
                }
            }
            MasterCode::create([
                'type'         => $request->type,
                'start_by'     => $request->start_by,
                'first_code'   => $request->first_code,
                'seperate_by'  => $request->seperate_by,
                'month_type'   => $request->month_type,
                'reset_type'   => $request->reset_type,
                'need_initial' => $request->need_initial,
                'initial_by'   => $request->initial_by,
                'create_by'    => $request->auth->id,
            ]);
            LogActivity::addToLog('Successfully create master code list', $request->auth->id);
            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error'   => false,
                'message' => 'Success to create data master code'
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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        try {
            $data = MasterCode::where(['id' => $id, 'state' => true])->first();
            if(!$data)
            {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'   => true,
                    'message' => 'The master code not found'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }

            LogActivity::addToLog('Successfully get master code list', $request->auth->id);

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
        try {
            $validator = Validator::make($request->all(), [
                'type'         => 'required|in:appointment,invoice,emr,service',
                'start_by'     => 'required|in:general,spesific',
                'first_code'   => 'required',
                'seperate_by'  => 'required|in:/,-',
                'month_type'   => 'required|in:numeral,roman',
                'reset_type'   => 'required|in:monthly,annually',
                'need_initial' => 'required|boolean',
                'initial_by'   => $request->need_initial ? 'required|in:subject,object' : 'nullable',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'   => true,
                    'message' => $validator->errors()->all()[0]
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            
            $data = MasterCode::where(['id' => $id, 'state' => true])->first();
            if (!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'   => true,
                    'message' => 'The master code not found'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            
            $checkType = MasterCode::where('id','!=',$id)->where(['type' => $request->type, 'state' => true])->first();
            if ($checkType) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'   => true,
                    'message' => 'The type has been taken'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            if ($request->start_by == 'spesific') {
                if (!$request->need_initial) {
                    return response()->json([
                        'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                        'error'   => true,
                        'message' => 'The need initial must be true. Because start by spesific was selected'
                    ], HttpStatusCodes::HTTP_BAD_REQUEST);
                }
            }
            if ($request->start_by == 'general') {
                if ($request->need_initial) {
                    return response()->json([
                        'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                        'error'   => true,
                        'message' => 'The need initial must be false. Because start by general was selected'
                    ], HttpStatusCodes::HTTP_BAD_REQUEST);
                }
            }
            $data->type = $request->type;
            $data->start_by = $request->start_by;
            $data->first_code = $request->first_code;
            $data->seperate_by = $request->seperate_by;
            $data->month_type = $request->month_type;
            $data->reset_type = $request->reset_type;
            $data->need_initial = $request->need_initial;
            $data->initial_by = $request->initial_by;
            $data->update_by = $request->auth->id;
            $data->save();
            LogActivity::addToLog('Successfully update master code list', $request->auth->id);
            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error'   => false,
                'message' => 'Success to update data master code'
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
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try {
            $data = MasterCode::where(['id' => $id, 'state' => true])->first();
            if (!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'   => true,
                    'message' => 'The master code not found'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }

            $data->state = false;
            $data->update_by = $request->auth->id;
            $data->save();

            LogActivity::addToLog('Successfully delete data master code', $request->auth->id);

            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Successfully delete data master code'
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
     * generate number
     */
    public function generateNumber(Request $request)
    {
       try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:appointment,invoice,emr,service',
                'object_code' => 'nullable'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'   => true,
                    'message' => $validator->errors()->all()[0]
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $now = date('Y-m-d H:i:s');
            $genNumber = GeneratingNumber::findNewNumber($request->type, $request->object_code);
            $findGN = GenerateNumber::where(['number' => $genNumber['number'], 'available' => true])->first();
            if(!$findGN)
            {
                GenerateNumber::create([
                    'type' => $request->type,
                    'order' => $genNumber['order'],
                    'initial' => $genNumber['initial'],
                    'number' => $genNumber['number'],
                    'expire_at' => date('Y-m-d H:i:s', strtotime('+ 3 minutes', strtotime($now))),
                    'create_by' => $request->auth->id
                ]);
            }
            LogActivity::addToLog('Successfully generate new number', $request->auth->id);
            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Successfully generate new number',
                'data' => GeneratingNumber::findNewNumber($request->type, $request->object_code)
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
     * generate number patient
     */
    public function patientGenerateNumber(Request $request)
    {
       try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:appointment,invoice,emr,service',
                'id_patient' => 'required',
                'object_code' => 'nullable'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'   => true,
                    'message' => $validator->errors()->all()[0]
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $now = date('Y-m-d H:i:s');
            $genNumber = PatientGeneratingNumber::findNewNumber($request->type, $request->object_code, $request->id_patient);
            $findGN = GenerateNumber::where(['number' => $genNumber['number'], 'available' => true])->first();
            if(!$findGN)
            {
                GenerateNumber::create([
                    'type' => $request->type,
                    'order' => $genNumber['order'],
                    'initial' => $genNumber['initial'],
                    'number' => $genNumber['number'],
                    'expire_at' => date('Y-m-d H:i:s', strtotime('+ 3 minutes', strtotime($now))),
                    'create_by' => $request->id_patient
                ]);
            }
            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Successfully generate new number',
                'data' => PatientGeneratingNumber::findNewNumber($request->type, $request->object_code, $request->id_patient)
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
     * Update number available
     */
    public function updateNumber(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'number' => 'required|exists:generate_numbers,number',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'   => true,
                    'message' => $validator->errors()->all()[0]
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $findNumber = GenerateNumber::where(['number' => $request->number, 'available' => true])->first();
            if(!$findNumber)
            {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'   => true,
                    'message' => 'The number is not available'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $findNumber->available = false;
            $findNumber->update_by = $request->auth->id;
            $findNumber->save();
            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Successfully update number',
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
     * Update number available patient
     */
    public function patientUpdateNumber(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'number' => 'required|exists:generate_numbers,number',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'   => true,
                    'message' => $validator->errors()->all()[0]
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $findNumber = GenerateNumber::where(['number' => $request->number, 'available' => true])->first();
            if(!$findNumber)
            {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'   => true,
                    'message' => 'The number is not available'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $findNumber->available = false;
            $findNumber->update_by = $request->id_patient;
            $findNumber->save();
            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Successfully update number',
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
