<?php

namespace App\Http\Controllers;

use App\Kis\RestCurl;
use App\Kis\LogActivity;
use App\Models\UserPicture;
use App\Kis\HttpStatusCodes;
use Illuminate\Http\Request;
use App\Kis\PaginationFormat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\UserPictureCollection;

class UserPictureController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, UserPicture $TblData)
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
                $query->where('url_image', 'LIKE', '%' . $request->search . '%');
            });
            $data->where('id_user', $request->auth->id)->where('state', true);
            $result = $data->orderBy('created_at', $request->ascending == true ? 'asc' : 'desc')->paginate($request->limit);

            LogActivity::addToLog('Successfully get user picture list', $request->auth->id);

            return new UserPictureCollection($result);
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
                'pictures' => 'required|array',
                'pictures.*.filename' => 'required',
                'pictures.*.content' => 'required',
                'pictures.*.is_signature' => 'required|boolean',
                'pictures.*.set_as_pp' => 'required|boolean',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'   => true,
                    'message' => $validator->errors()->all()[0]
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            foreach ($request->pictures as $value) {
                $restUploadFile = RestCurl::post(env('STORAGE_SERVICE_BASE_URI') . '/upload', [
                    'filename' => $value['filename'],
                    'content'  => $value['content']
                ], getallheaders());
                if (!$restUploadFile->error) {
                    $file = $restUploadFile->data->url;
                    if($value['set_as_pp'])
                    {
                        $findPP = UserPicture::where(['id_user' => $request->auth->id, 'set_as_pp' => true, 'state' => true])->first();
                        if ($findPP) {
                            $findPP->set_as_pp = false;
                            $findPP->update_by = $request->auth->id;
                            $findPP->save();
                        }
                    }
                    UserPicture::create([
                        'id_user' => $request->auth->id,
                        'url_image' => $file,
                        'is_signature' => $value['is_signature'],
                        'set_as_pp' => $value['set_as_pp'],
                        'create_by' => $request->auth->id
                    ]);
                }
            }
            LogActivity::addToLog('Success to create data user picture', $request->auth->id);
            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Success to upload picture'
            ], HttpStatusCodes::HTTP_OK);
        } catch (\Throwable $th) {
            LogActivity::addToLog($th->getMessage(), $request->auth->id);
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
    public function destroy($id)
    {
        //
    }
}
