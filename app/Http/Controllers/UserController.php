<?php

namespace App\Http\Controllers;
//test
use App\Models\User;
use App\Models\Role;
use App\Kis\RestCurl;
use App\Kis\RoleAssign;
use App\Kis\LogActivity;
use App\Models\Specialist;
use App\Models\UserDetail;
use App\Models\UserHasRole;
use App\Models\UserPicture;
use Illuminate\Support\Str;
use App\Kis\HttpStatusCodes;
use Illuminate\Http\Request;
use App\Kis\PaginationFormat;
use App\Kis\PermissionAssign;
use App\Models\UserSpecialist;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserCollection;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, User $TblData)
    {
        $validator = Validator::make($request->all(), array_merge(PaginationFormat::VALIDATION,[
            'id_role' => 'nullable|exists:roles,id',
            'name_role' => 'nullable'
        ]));
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
                $query->where('username', 'LIKE', '%' . $request->search . '%')
                ->orWhere('email', 'LIKE', '%' . $request->search . '%');
            });
            $data->when((string)$request->id_role != null, function($filter) use($request){
                $filter->whereIn('id', function($subQuery) use($request){
                    $subQuery->select('id_user')->from('user_has_roles')->where('id_role', $request->id_role);
                })->where('level','!=', 'developer');
            });
            $data->when((string)$request->name_role != null, function($filter) use($request){
                $filter->whereIn('id', function($subQuery) use($request){
                    $subQuery->select('id_user')->from('user_has_roles')->where('id_role', function($subQuery2) use($request){
                        $subQuery2->select('id')->from('roles')->where('name','LIKE', '%' . $request->name_role . '%');
                    });
                })->where('level','!=', 'developer');
            });
            $data->where('state', true)->where('level', '!=', 'developer');
            $data->where('state', true)->where('level', '!=', 'superadmin');
            $data->whereIn('id', function ($subQuery) {
                $subQuery->select('id_user')->from('user_has_roles')->whereIn('id_role', function ($subQuery2) {
                    $subQuery2->select('id')->from('roles')->whereIn('slug_name', [
                        'hr', 'hr_bandung', 'hr_makassar', 'hr_serpong', 'hr_bekasi'
                    ]);
                });
            });
            $data->where('state', true);
            $result = $data->orderBy('created_at', $request->ascending == true ? 'asc' : 'desc')->paginate($request->limit);

            LogActivity::addToLog('Successfully get user list', $request->auth->id);

            return new UserCollection($result);
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
            'id_role'      => $request->level !== 'developer' ? 'required|exists:roles,id' : 'nullable',
            'username'     => 'required',
            'password'     => 'required',
            'email'        => 'required|email',
            'level'        => 'required|in:admin,user,developer',
            'gender'       => 'nullable',
            'ysb_branch_id'=> 'nullable'
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
            $findUsername = User::where(['username' => $request->username, 'state' => true])->first();
            if ($findUsername) {
                DB::rollback();
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The username has already been taken'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $findEmail = User::where(['email' => $request->email, 'state' => true])->first();
            if ($findEmail) {
                DB::rollback();
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The email has already been taken'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            if ($request->level == 'superadmin') {
                DB::rollback();
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'Invalid level'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $findBranch = Role::where(['id' => $request->id_role, 'state' => true])->first();

            $save = User::create([
                'id_role'           => $request->id_role,
                'username'          => $request->username,
                'password'          => Hash::make($request->password),
                'email'             => $request->email,
                'level'             => $request->level,
                'ysb_branch_id'     => $findBranch->ysb_branch_id,
                // 'nik_ysb'           => $request->nik_ysb
            ]);
            if ($save) {
                if($request->level == 'developer')
                {
                    PermissionAssign::addAllPermission($save->id);
                } else {
                    PermissionAssign::addPermissionByRole($request->id_role, $save->id);
                }
                $save2 = UserDetail::create([
                    'id_user'      => $save->id,
                    'gender'       => $request->gender,
                    'create_by'    => $request->auth->id
                ]);
                if($save2 && $request->level !== 'developer')
                {
                    UserHasRole::create([
                        'id_user'   => $save->id,
                        'id_role'   => $request->id_role,
                        'create_by' => $request->auth->id
                    ]);
                }
                DB::commit();
                LogActivity::addToLog('Success to create data user', $request->auth->id);
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_OK,
                    'error' => false,
                    'message' => 'Success to create data user'
                ], HttpStatusCodes::HTTP_OK);
            }
            DB::rollback();
            LogActivity::addToLog('Fail to create data user', $request->auth->id);
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error' => true,
                'message' => 'Fail to create data user'
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
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
            $data = User::where(['id' => $id, 'state' => true])->with('user_detail')->with('user_fee')->with('specialists')->first();
            if (!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The user not found'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }

            LogActivity::addToLog('Successfully get user list', $request->auth->id);

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
            'id_role'  => $request->level !== 'developer' ? 'required|exists:roles,id' : 'nullable',
            'username' => 'required',
            'password' => 'nullable',
            'email'    => 'required|email',
            'level'    => 'required|in:admin,user,developer',
            'gender'   => 'nullable',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status'  => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
    
        DB::beginTransaction();
        try {
            // Cek apakah username atau email sudah digunakan
            // tes
            $findUsername = User::where('id', '!=', $id)
                ->where(['username' => $request->username, 'state' => true])
                ->first();
            if ($findUsername) {
                DB::rollback();
                return response()->json([
                    'status'  => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'   => true,
                    'message' => 'The username has already been taken'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
    
            $findEmail = User::where('id', '!=', $id)
                ->where(['email' => $request->email, 'state' => true])
                ->first();
            if ($findEmail) {
                DB::rollback();
                return response()->json([
                    'status'  => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'   => true,
                    'message' => 'The email has already been taken'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
    
            $data = User::where(['id' => $id, 'state' => true])->first();
            if (!$data) {
                DB::rollback();
                return response()->json([
                    'status'  => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'   => true,
                    'message' => 'The user not found'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
    
            // Jika role berubah, cek role baru
            $findBranch = Role::where(['id' => $request->id_role, 'state' => true])->first();
            if (!$findBranch && $request->level !== 'developer') {
                DB::rollback();
                return response()->json([
                    'status'  => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'   => true,
                    'message' => 'Invalid role'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
    
            // Update data user
            $data->id_role = $request->id_role;
            $data->username = $request->username;
            if ($request->password) {
                $data->password = Hash::make($request->password);
            }
            $data->email = $request->email;
            $data->level = $request->level;
            $data->ysb_branch_id = $findBranch ? $findBranch->ysb_branch_id : null;
    
            if ($data->save()) {
                // Atur permission berdasarkan level
                if ($request->level == 'developer') {
                    PermissionAssign::addAllPermission($id);
                } else {
                    PermissionAssign::addPermissionByRole($request->id_role, $id);
                }
    
                // Update atau buat UserDetail
                $findDetail = UserDetail::where(['id_user' => $id, 'state' => true])->first();
                if (!$findDetail) {
                    UserDetail::create([
                        'id_user'   => $id,
                        'gender'    => $request->gender,
                        'update_by' => $request->auth->id
                    ]);
                } else {
                    $findDetail->gender = $request->gender;
                    $findDetail->update_by = $request->auth->id;
                    $findDetail->save();
                }
    
                // Update atau buat UserHasRole jika bukan developer
                if ($request->level !== 'developer') {
                    $findUserRole = UserHasRole::where(['id_user' => $id, 'state' => true])->first();
                    if (!$findUserRole) {
                        UserHasRole::create([
                            'id_user'   => $id,
                            'id_role'   => $request->id_role,
                            'create_by' => $request->auth->id
                        ]);
                    } else {
                        $findUserRole->id_role = $request->id_role;
                        $findUserRole->update_by = $request->auth->id;
                        $findUserRole->save();
                    }
                }
    
                DB::commit();
                LogActivity::addToLog('Success to update data user', $request->auth->id);
                return response()->json([
                    'status'  => HttpStatusCodes::HTTP_OK,
                    'error'   => false,
                    'message' => 'Success to update data user'
                ], HttpStatusCodes::HTTP_OK);
            }
    
            DB::rollback();
            LogActivity::addToLog('Fail to update data user', $request->auth->id);
            return response()->json([
                'status'  => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => 'Fail to update data user'
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        } catch (\Throwable $th) {
            LogActivity::addToLog($th->getMessage(), $request->auth->id);
            return response()->json([
                'status'  => HttpStatusCodes::HTTP_BAD_REQUEST,
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
            // Mulai transaksi database
            DB::beginTransaction();
    
            // Cari user dengan state true
            $data = User::where(['id' => $id, 'state' => true])->first();
            if (!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'Guru Tidak Ditemukan!'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
    
            // Update semua relasi yang terkait (tanpa loop)
            // UserDetail::where(['id_user' => $data->id, 'state' => true])->update(['state' => false]);
            UserHasRole::where(['id_user' => $data->id, 'state' => true])->update(['state' => false]);
    
            // Update state pada tabel User
            $data->update([
                'state' => false,
                'update_by' => $request->auth->id
            ]);
    
            // Commit transaksi jika semua berhasil
            DB::commit();
    
            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Success to delete data'
            ], HttpStatusCodes::HTTP_OK);
        } catch (\Throwable $th) {
            // Rollback transaksi jika ada error
            DB::rollBack();
    
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $th->getMessage()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
    }
    

    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username'     => 'required',
            'password'     => 'nullable',
            'email'        => 'required|email',
            'level'        => 'required|in:admin,user,developer',
            'unique_id'    => 'required',
            'firstname'    => 'required',
            'lastname'     => 'required',
            'address'      => 'required',
            'phone_number' => 'required',
            'birth_place'  => 'nullable',
            'birth_day'    => 'nullable|date',
            'gender'       => 'required|in:male,female',
            'filename'     => 'nullable',
            'content'      => $request->filename ? 'required' : 'nullable'
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
            $findUniqueId = UserDetail::where('id_user', '!=', $request->auth->id)->where(['unique_id' => $request->unique_id])->first();
            if ($findUniqueId) {
                DB::rollback();
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The unique id has already been taken'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $findUsername = User::where('id', '!=', $request->auth->id)->where(['username' => $request->username, 'state' => true])->first();
            if ($findUsername) {
                DB::rollback();
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The username has already been taken'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $findEmail = User::where('id', '!=', $request->auth->id)->where(['email' => $request->email, 'state' => true])->first();
            if ($findEmail) {
                DB::rollback();
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The email has already been taken'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $data = User::where(['id' => $request->auth->id, 'state' => true])->first();
            if (!$data) {
                DB::rollback();
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The user not found'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $data->username = $request->username;
            if ($request->password) {
                $data->password = Hash::make($request->password);
            }
            $data->email = $request->email;
            $data->level = $request->level;

            if ($data->save()) {
                $findDetail = UserDetail::where(['id_user' => $request->auth->id])->first();
                if (!$findDetail) {
                    UserDetail::create([
                        'unique_id'    => $request->unique_id,
                        'id_user'      => $request->auth->id,
                        'firstname'    => $request->firstname,
                        'lastname'     => $request->lastname,
                        'address'      => $request->address,
                        'phone_number' => $request->phone_number,
                        'birth_place'  => $request->birth_place,
                        'birth_day'    => date('Y-m-d', strtotime($request->birth_day)),
                        'gender'       => $request->gender,
                        'update_by'    => $request->auth->id
                    ]);
                } else {
                    $findDetail->unique_id = $request->unique_id;
                    $findDetail->firstname = $request->firstname;
                    $findDetail->lastname = $request->lastname;
                    $findDetail->address = $request->address;
                    $findDetail->phone_number = $request->phone_number;
                    $findDetail->birth_place = $request->birth_place;
                    $findDetail->birth_day = date('Y-m-d', strtotime($request->birth_day));
                    $findDetail->gender = $request->gender;
                    $findDetail->update_by = $request->auth->id;
                    $findDetail->save();
                }

                //upload PP
                if ($request->filename) {

                    //upload ke user service-pending dulu karena ada masalah setelah upload
                    $restUploadFile = RestCurl::post(env('STORAGE_SERVICE_BASE_URI') . '/upload', [
                        'filename' => $request->filename,
                        'content'  => $request->content
                    ], getallheaders());
                    if (!$restUploadFile->error) {
                        $file = $restUploadFile->data->url;
                        $findPP = UserPicture::where(['id_user' => $request->auth->id, 'set_as_pp' => true, 'state' => true])->first();
                        if ($findPP) {
                            $findPP->set_as_pp = false;
                            $findPP->update_by = $request->auth->id;
                            $findPP->save();
                        }
                        UserPicture::create([
                            'id_user' => $request->auth->id,
                            'url_image' => $file,
                            'set_as_pp' => true,
                            'create_by' => $request->auth->id
                        ]);
                    } else {
                        DB::rollback();
                        return response()->json([
                            'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                            'error' => true,
                            'message' => 'Failed to upload file'
                        ], HttpStatusCodes::HTTP_BAD_REQUEST);
                    }
                }

                DB::commit();
                LogActivity::addToLog('Success to update data user', $request->auth->id);
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_OK,
                    'error' => false,
                    'message' => 'Success to update data user'
                ], HttpStatusCodes::HTTP_OK);
            }
            DB::rollback();
            LogActivity::addToLog('Fail to update data user', $request->auth->id);
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error' => true,
                'message' => 'Fail to update data user'
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
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
     * Check the user if doctor
     */
    public function checkUserByRoleName(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name_role' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'   => true,
                    'message' => $validator->errors()->all()[0]
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $findUser = UserHasRole::where(['id_user' => $id, 'state' => true])->where('id_role', function($query) use($request){
                $query->select('id')->from('roles')->where('slug_name', Str::slug($request->name_role,'_'));
            })->first();
            if(!$findUser)
            {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'   => true,
                    'message' => 'The user not found'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error'   => false,
                'message' => 'The user found'
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
     * SHOW FOR PATIENT
     */
    public function showByPatient(Request $request, $id)
    {
        try {
            $data = User::where(['id' => $id, 'state' => true])->with('user_detail')->first();
            if (!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The user not found'
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
     * LIST DOCTOR FOR PATIENT
     */
    public function indexByPatient(Request $request, User $TblData)
    {
        $validator = Validator::make($request->all(), array_merge(PaginationFormat::VALIDATION,[
            'id_role' => 'nullable|exists:roles,id',
            'name_role' => 'required'
        ]));
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
                $query->where('username', 'LIKE', '%' . $request->search . '%')
                ->orWhere('email', 'LIKE', '%' . $request->search . '%');
            });
            $data->when((string)$request->id_role != null, function($filter) use($request){
                $filter->whereIn('id', function($subQuery) use($request){
                    $subQuery->select('id_user')->from('user_has_roles')->where('id_role', $request->id_role);
                })->where('level','!=', 'developer');
            });
            $data->when((string)$request->name_role != null, function($filter) use($request){
                $filter->whereIn('id', function($subQuery) use($request){
                    $subQuery->select('id_user')->from('user_has_roles')->where('id_role', function($subQuery2) use($request){
                        $subQuery2->select('id')->from('roles')->where('name','LIKE', '%' . $request->name_role . '%');
                    });
                })->where('level','!=', 'developer');
            });
            $data->where('state', true)->where('level', '!=', 'superadmin');
            $result = $data->orderBy('created_at', $request->ascending == true ? 'asc' : 'desc')->paginate($request->limit);

            return new UserCollection($result);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $th->getMessage()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
    }

    public function storeSpecialist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_user' => 'required',
            'id_specialist' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        try {
            $findUser = User::where(['id' => $request->id_user, 'state' => true])->first();
            if (!$findUser) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The user not found'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            $findSpecialist = Specialist::where(['id' => $request->id_specialist, 'state' => true])->first();
            if (!$findSpecialist) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The specialist not found'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            UserSpecialist::create([
                'id_user' => $request->id_user,
                'id_specialist' => $request->id_specialist,
                'create_by' => $request->auth->id
            ]);

            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Success to create data user specialist'
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
    public function deleteSpecialist(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'specialists'           => 'required|array',
            'specialists.*.id_specialist' => 'required|exists:specialists,id'
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
            // $findSpecialist = Specialist::where(['id' => $id, 'state' => true])->first();
            // if(!$findSpecialist)
            // {
            //     DB::rollBack();
            //     return response()->json([
            //         'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
            //         'error' => true,
            //         'message' => 'The Specialists not found!'
            //     ], HttpStatusCodes::HTTP_BAD_REQUEST);                
            // }
            foreach ($request->specialists as $value) {
                $findSpecialist = Specialist::where('id', $value['id_specialist'])->first();
                if (!$findSpecialist->state) {
                    DB::rollBack();
                    return response()->json([
                        'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                        'error' => true,
                        'message' => 'Specialist ' . $findSpecialist->name . ' not found!'
                    ], HttpStatusCodes::HTTP_BAD_REQUEST);
                }
                $checkData = UserSpecialist::where(['id_user' => $id, 'id_specialist' => $value['id_specialist'], 'state' => true])->first();
                if (!$checkData) {
                    DB::rollBack();
                    return response()->json([
                        'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                        'error' => true,
                        'message' => 'The specialist ' . $findSpecialist->name . ' has already been deleted!'
                    ], HttpStatusCodes::HTTP_BAD_REQUEST);
                }
                $checkData->state = false;
                $checkData->update_by = $request->auth->id;
                $checkData->save();
            }
            LogActivity::addToLog('Success to delete data user specialist', $request->auth->id);
            DB::commit();
            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Success to delete data user specialist'
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

    public function availableNurse(Request $request, User $TblData)
    {
        try {
            $dataMedInv = '';
            $data = UserHasRole::whereIn('id', function($subQuery){
                $subQuery->select('id_user')->from('user_has_roles')->where('id_role', function($subQuery2){
                    $subQuery2->select('id')->from('roles')->where('name','LIKE', '%' . 'nurse' . '%');
                });
            })->whereNotIn('id',)
            ->where('level','!=', 'developer');
            if (!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The user not found'
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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexSequrity(Request $request, User $TblData)
    {
        try {
            $data = $TblData->newQuery();
            $data->when((string)$request->search != null, function ($query) use ($request) {
                $query->where('username', 'LIKE', '%' . $request->search . '%')
                ->orWhere('email', 'LIKE', '%' . $request->search . '%');
            });
            $data->when((string)$request->name_role != null, function ($query) use ($request) {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->where('name', 'LIKE', '%' . $request->name_role . '%');
                });
            });
            $data->where('state', true)->where('level', '!=', 'developer');
            $data->where('state', true)->where('level', '!=', 'superadmin');
            $data->where('state', true);
            $result = $data->orderBy('created_at', $request->ascending == true ? 'asc' : 'desc')->paginate($request->limit);

            LogActivity::addToLog('Successfully get user list', $request->auth->id);

            return new UserCollection($result);
        } catch (\Throwable $th) {
            LogActivity::addToLog($th->getMessage(), $request->auth->id);
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $th->getMessage()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'        => 'required|email',
            'password'     => 'required',
            'new_password' => 'required|min:6',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'  => true,
                'message' => $validator->errors()->first()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
    
        try {
            $user = User::where(['email' => $request->email, 'state' => true])->first();
    
            if (!$user) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'Email not found or inactive'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
    
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'Password Lama Salah'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
    
            // Update password
            $user->password = Hash::make($request->new_password);
            $user->save();
    
            LogActivity::addToLog('Success to change password user', $request->auth->id ?? null);
            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Success to update user password'
            ], HttpStatusCodes::HTTP_OK);
    
        } catch (\Throwable $th) {
            LogActivity::addToLog($th->getMessage(), $request->auth->id ?? null);
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error' => true,
                'message' => $th->getMessage()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
    }    
}
