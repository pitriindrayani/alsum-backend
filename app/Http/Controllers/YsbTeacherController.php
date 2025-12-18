<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\SDC\RestCurl;
use App\Models\YsbTeacher;
use App\Models\YsbTeacherStatusRecord;
use App\Models\User;
use App\Models\UserDetail;
use App\Kis\LogActivity;
use App\Models\UserHasRole;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Kis\HttpStatusCodes;
use Illuminate\Http\Request;
use App\Kis\PaginationFormat;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\YsbTeacherCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Kis\PermissionAssign;

class YsbTeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, YsbTeacher $TblData)
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
            }else if($request->level !== "developer" || $request->level !== "superadmin"){
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
                    $query->where('full_name', 'LIKE', '%' . $search . '%');
                });
            });
            
            $data->where('state', true);
            $result = $data->orderBy('created_at', $request->ascending == true ? 'asc' : 'desc')->paginate($request->limit);

            return new YsbTeacherCollection($result);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $th->getMessage(),
                'trace' => $th->getTrace()
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
    }

    public function indexSchedule(Request $request, YsbTeacher $TblData)
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
            $data->when((string)$request->ysb_school_id != null, function ($query) use ($request) {
                $ysb_school_id = $request->ysb_school_id;
                $query->where(function ($query) use ($ysb_school_id) {
                    $query->where('ysb_school_id', 'LIKE', '%' . $ysb_school_id . '%');
                });
            });

            $data->when((string)$request->search != null, function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($query) use ($search) {
                    $query->where('full_name', 'LIKE', '%' . $search . '%');
                });
            });
            
            $data->where('state', true);
            $result = $data->orderBy('full_name', 'asc' )
               ->orderBy('created_at', $request->ascending_name == true ? 'asc' : 'desc')
               ->paginate($request->limit);


            return new YsbTeacherCollection($result);
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
            'id_role'=> 'required',
            'nip_ypi'=> 'nullable',
            'id_role'=> 'required',
            'nip_ypi_karyawan'=> 'nullable',
            'email'=> 'required',
            'password'=> 'required',
            'nik_ysb'=> 'required',
            'join_date_ypi'=> 'nullable',
            'join_date_ysb'=> 'nullable',
            'full_name'=> 'required',
            'nik_ktp'=> 'nullable',
            'birthplace'=> 'nullable',
            'birthdate'=> 'nullable',
            'gender'=> 'nullable',
            'employment_status'=> 'required',
            'ysb_branch_id'=> 'required',
            'edu_stage'=> 'required',
            'ysb_school_id'=> 'required',
            'ysb_position_id'=> 'nullable',
            'ysb_schedule_id'=> 'nullable',
            'bidang'=> 'nullable',
            'ysb_teacher_group_id'=> 'nullable',
            'religion'=> 'nullable',
            'addrees'=> 'nullable',
            'dom_address'=> 'nullable',
            'marriage'=> 'nullable',
            'npwp'=> 'nullable',
            'ptkp'=> 'nullable',
            'university'=> 'nullable',
            'major'=> 'nullable',
            'degree'=> 'nullable',
            'mobile'=> 'nullable',
            'bank'=> 'nullable',
            'nama_rekening'=> 'nullable',
            'no_rekening'=> 'nullable',
            'contact_name'=> 'nullable',
            'contact_relation'=> 'nullable',
            'contact_number'=> 'nullable',
            'nuptk'=> 'nullable',
            'user_id'=> 'nullable',
            'zakat'=> 'nullable',
            'fg_active'=> 'nullable',
            'finger_id'=> 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'error'  => true,
                'message' => $validator->errors()->all()[0]
            ], 400);
        }
    
        try {
            $email = User::where(['email' => $request->email, 'state' => true])->first();
            if ($email) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The email has already'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }

            $nikYsb = User::where(['nik_ysb' => $request->nik_ysb, 'state' => true])->first();
            if ($nikYsb) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The nik has already'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }

            $fingerId = YsbTeacher::where(['finger_id' => $request->finger_id, 'state' => true])->first();
            if ($fingerId) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'The finger id has already'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            // Save data
            $save = YsbTeacher::create([
                'nip_ypi'=> $request->nip_ypi,
                'nip_ypi_karyawan'=> $request->nip_ypi_karyawan,
                'nik_ysb'=> $request->nik_ysb,
                'join_date_ypi'=> $request->join_date_ypi,
                'join_date_ysb'=> $request->join_date_ysb,
                'full_name'=> $request->full_name,
                'nik_ktp'=> $request->nik_ktp,
                'birthplace'=> $request->birthplace,
                'birthdate'=> $request->birthdate,
                'gender'=> $request->gender,
                'employment_status'=> $request->employment_status,
                'ysb_branch_id'=> $request->ysb_branch_id,
                'edu_stage'=> $request->edu_stage,
                'ysb_school_id'=> $request->ysb_school_id,
                'ysb_position_id'=> $request->ysb_position_id,
                'ysb_schedule_id'=> $request->ysb_schedule_id,
                'bidang'=> $request->bidang,
                'ysb_teacher_group_id'=> $request->ysb_teacher_group_id,
                'religion'=> $request->religion,
                'addrees'=> $request->addrees,
                'dom_address'=> $request->dom_address,
                'marriage'=> $request->marriage,
                'npwp'=> $request->npwp,
                'ptkp'=> $request->ptkp,
                'university'=> $request->university,
                'major'=> $request->major,
                'degree'=> $request->degree,
                'mobile'=> $request->mobile,
                'email'=> $request->email,
                'bank'=> $request->bank,
                'nama_rekening'=> $request->nama_rekening,
                'no_rekening'=> $request->no_rekening,
                'contact_name'=> $request->contact_name,
                'contact_relation'=> $request->contact_relation,
                'contact_number'=> $request->contact_number,
                'nuptk'=> $request->nuptk,
                'user_id'=> $request->user_id,
                'zakat'=> $request->zakat,
                'fg_active'=> $request->fg_active,
                'finger_id'=> $request->finger_id,
                'create_by' => $request->auth->id
            ]);
           
            // Jika $save gagal, hentikan proses
            if (!$save) {
                throw new \Exception('Failed to save YsbTeacher');
            }

            // Proses untuk memasukan data array status kepegawaian
            if (!empty($request->array_status) && $save) {
                foreach ($request->array_status as $value) {
                    YsbTeacherStatusRecord::create([
                        'ysb_id_teacher' => $save->id, 
                        'nip_ypi'        => $value['nip_ypi'],
                        'status_code'    => $value['status_code'],
                        'date'           => $value['date'],
                        'create_by'      => $request->auth->id
                    ]);
                }
            }

            // Save data ke User
            $save2 = User::create([
                'username' => $request->full_name,
                'id_role' => $request->id_role,
                'id_teacher' => $save->id,
                'ysb_branch_id' => $request->ysb_branch_id,
                'ysb_school_id' => $request->ysb_school_id,
                'nik_ysb' => $request->nik_ysb,
                'password' => Hash::make($request->password),
                'email' => $request->email,
                'level' => "user",
            ]);

            if (!$save2) {
                throw new \Exception('Failed to save User');
            }

            // Save data ke UserHasRole
            $save3 = UserHasRole::create([
                'id_user' => $save2->id,
                'id_role' => $request->id_role,
                'create_by' => $request->auth->id
            ]);

            if (!$save3) {
                throw new \Exception('Failed to save UserHasRole');
            }

            DB::commit(); // Commit jika semua proses berhasil

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
            $data = YsbTeacher::where(['id' => $id, 'state' => true])->first();
            if(!$data)
            {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error' => true,
                'message' => "Guru Tidak Ditemukan!"
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
            'nip_ypi'=> 'nullable',
            'id_role'=> 'required',
            'nip_ypi_karyawan'=> 'nullable',
            'email'=> 'required',
            'nik_ysb'=> 'required',
            'join_date_ypi'=> 'nullable',
            'join_date_ysb'=> 'nullable',
            'full_name'=> 'required',
            'nik_ktp'=> 'nullable',
            'birthplace'=> 'nullable',
            'birthdate'=> 'nullable',
            'gender'=> 'nullable',
            'employment_status'=> 'required',
            'ysb_branch_id'=> 'required',
            'edu_stage'=> 'required',
            'ysb_school_id'=> 'required',
            'ysb_position_id'=> 'nullable',
            'ysb_schedule_id'=> 'nullable',
            'bidang'=> 'nullable',
            'ysb_teacher_group_id'=> 'nullable',
            'religion'=> 'nullable',
            'addrees'=> 'nullable',
            'dom_address'=> 'nullable',
            'marriage'=> 'nullable',
            'npwp'=> 'nullable',
            'ptkp'=> 'nullable',
            'university'=> 'nullable',
            'major'=> 'nullable',
            'degree'=> 'nullable',
            'mobile'=> 'nullable',
            'bank'=> 'nullable',
            'nama_rekening'=> 'nullable',
            'no_rekening'=> 'nullable',
            'contact_name'=> 'nullable',
            'contact_relation'=> 'nullable',
            'contact_number'=> 'nullable',
            'nuptk'=> 'nullable',
            'user_id'=> 'nullable',
            'zakat'=> 'nullable',
            'fg_active'=> 'nullable',
            'finger_id'=> 'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        try {
            $data = YsbTeacher::where(['id' => $id, 'state' => true])->first();
           
            if(!$data)
            {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => "Guru Tidak Ditemukan!"
                ], HttpStatusCodes::HTTP_BAD_REQUEST);                
            }

            $user = User::where(['id_teacher' => $data->id, 'state' => true])->first();
            if ($user) {
                $user->username = $request->full_name ?? $user->username;
                $user->id_role = $request->id_role ?? $user->id_role;
                $user->ysb_branch_id = $request->ysb_branch_id ?? $user->ysb_branch_id;
                $user->ysb_school_id = $request->ysb_school_id ?? $user->ysb_school_id;
                $user->nik_ysb = $request->nik_ysb ?? $user->nik_ysb;
                $user->email = $request->email ?? $user->email;
            
                // Update password jika ada perubahan
                if ($request->filled('password')) {
                    $user->password = Hash::make($request->password);
                }
            
                $user->save();
            }
            
            $data->nip_ypi = $request->nip_ypi;
            $data->nip_ypi_karyawan = $request->nip_ypi_karyawan;
            $data->nik_ysb = $request->nik_ysb;
            $data->join_date_ypi = $request->join_date_ypi;
            $data->join_date_ysb = $request->join_date_ysb;
            $data->full_name = $request->full_name;
            $data->nik_ktp = $request->nik_ktp;
            $data->birthplace = $request->birthplace;
            $data->birthdate = $request->birthdate;
            $data->gender = $request->gender;
            $data->employment_status = $request->employment_status;
            $data->ysb_branch_id = $request->ysb_branch_id;
            $data->edu_stage = $request->edu_stage;
            $data->ysb_school_id = $request->ysb_school_id;
            $data->ysb_position_id = $request->ysb_position_id;
            $data->ysb_schedule_id = $request->ysb_schedule_id;
            $data->bidang = $request->bidang;
            $data->ysb_teacher_group_id = $request->ysb_teacher_group_id;
            $data->religion = $request->religion;
            $data->addrees = $request->addrees;
            $data->dom_address = $request->dom_address;
            $data->marriage = $request->marriage;
            $data->npwp = $request->npwp;
            $data->ptkp = $request->ptkp;
            $data->university = $request->university;
            $data->major = $request->major;
            $data->degree = $request->degree;
            $data->mobile = $request->mobile;
            $data->email = $request->email;
            $data->bank = $request->bank;
            $data->nama_rekening = $request->nama_rekening;
            $data->no_rekening = $request->no_rekening;
            $data->contact_name = $request->contact_name;
            $data->contact_relation = $request->contact_relation;
            $data->contact_number = $request->contact_number;
            $data->nuptk = $request->nuptk;
            $data->user_id = $request->user_id;
            $data->zakat = $request->zakat;
            $data->fg_active = $request->fg_active;
            $data->finger_id = $request->finger_id;
            $data->update_by = $request->auth->id;
            $data->save();

            if ($data->save()) {
                $findUserRole = UserHasRole::where(['id_user' => $user->id, 'state' => true])->first();
                $findUserRole->id_role = $request->id_role;
                $findUserRole->update_by = $request->auth->id;
                $findUserRole->save();

                DB::commit();
                LogActivity::addToLog('Success to update data user', $request->auth->id);
                return response()->json([
                    'status'  => HttpStatusCodes::HTTP_OK,
                    'error'   => false,
                    'message' => 'Success to update data user'
                ], HttpStatusCodes::HTTP_OK);
            }

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
            // Cari data YsbTeacher yang akan dihapus
            $data = YsbTeacher::where(['id' => $id, 'state' => true])->first();
    
            if (!$data) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'Guru Tidak Ditemukan!'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
    
            // Cari user yang terkait dengan teacher ini
            $user = User::where('id_teacher', $data->id)->first();
            if ($user) {
                $user->state = false; 
                $user->save();
            }
    
            // Ubah state di tabel YsbTeacher
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

    public function showTeacherByIdUser($email)
    {
        try {
            $data = YsbTeacher::where(['email' => $email, 'state' => true])->first();
            if(!$data)
            {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error' => true,
                'message' => "Guru Tidak Ditemukan!"
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

    public function excelDateToYMD($excelDate) {
        if (is_numeric($excelDate)) {
            return Carbon::createFromFormat('Y-m-d', '1900-01-01')
                ->addDays($excelDate - 2) 
                ->format('Y-m-d');
        }
        return $excelDate;
    }

    public function storeExcel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file'               => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error'   => true,
                'message' => $validator->errors()->all()[0]
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }

        try {
            foreach ($request->file as $key => $value) {
                $save = YsbTeacher::create([
                    'nip_ypi' => $value['nip_ypi'] ?? null,
                    'nip_ypi_karyawan' => $value['nip_ypi_karyawan'] ?? null,
                    'nik_ysb' => $value['nik_ysb'] ?? null,
                    'join_date_ypi' => isset($value['join_date_ypi']) ? $this->excelDateToYMD($value['join_date_ypi']) : null,
                    'join_date_ysb' => isset($value['join_date_ysb']) ? $this->excelDateToYMD($value['join_date_ysb']) : null,
                    'birthdate' => isset($value['birthdate']) ? $this->excelDateToYMD($value['birthdate']) : null,
                    'full_name' => $value['full_name'] ?? null,
                    'nik_ktp' => $value['nik_ktp'] ?? null,
                    'birthplace' => $value['birthplace'] ?? null,
                    'gender' => $value['gender'] ?? null,
                    'employment_status' => $value['employment_status'] ?? null,
                    'ysb_branch_id' => $value['ysb_branch_id'] ?? null,
                    'edu_stage' => $value['edu_stage'] ?? null,
                    'ysb_school_id' => $value['ysb_school_id'] ?? null,
                    'ysb_position_id' => $value['ysb_position_id'] ?? null,
                    'ysb_schedule_id' => $value['ysb_schedule_id'] ?? null,
                    'bidang' => $value['bidang'] ?? null,
                    'ysb_teacher_group_id' => $value['ysb_teacher_group_id'] ?? null,
                    'religion' => $value['religion'] ?? null,
                    'addrees' => $value['addrees'] ?? null,
                    'dom_address' => $value['dom_address'] ?? null,
                    'marriage' => $value['marriage'] ?? null,
                    'npwp' => $value['npwp'] ?? null,
                    'ptkp' => $value['ptkp'] ?? null, 
                    'university' => $value['university'] ?? null,
                    'major' => $value['major'] ?? null,
                    'degree' => $value['degree'] ?? null,
                    'mobile' => $value['mobile'] ?? null,
                    'email' => $value['email'] ?? null,
                    'bank' => $value['bank'] ?? null,
                    'nama_rekening' => $value['nama_rekening'] ?? null,
                    'no_rekening' => $value['no_rekening'] ?? null,
                    'contact_name' => $value['contact_name'] ?? null,
                    'contact_relation' => $value['contact_relation'] ?? null,
                    'contact_number' => $value['contact_number'] ?? null,
                    'nuptk' => $value['nuptk'] ?? null,
                    'user_id' => $value['user_id'] ?? null,
                    'zakat' => $value['zakat'] ?? null,
                    'fg_active' => $value['fg_active'] ?? null,
                    'finger_id' => $value['finger_id'] ?? null,
                    'create_by' => $request->auth->id,
                ]);
                

                 // Jika $save gagal, hentikan proses
                if (!$save) {
                    throw new \Exception('Failed to save YsbTeacher');
                }

                $save2 = User::create([
                    'username' => $value['full_name'] ?? null,
                    'id_role' => $value['id_role'] ?? null,
                    'id_teacher' => $save->id,
                    'ysb_branch_id' => $value['ysb_branch_id'] ?? null,
                    'ysb_school_id' => $value['ysb_school_id'] ?? null,
                    'nik_ysb' => $value['nik_ysb'] ?? null,
                    'password' => Hash::make($value['password']) ?? null,
                    'email' => $value['email'] ?? null,
                    'level' => "user",
                ]);

                if (!$save2) {
                    throw new \Exception('Failed to save User');
                }

                $save3 = UserHasRole::create([
                    'id_user' => $save2->id,
                    'id_role' => $value['id_role'] ?? null,
                    'create_by' => $request->auth->id,
                ]);

                if (!$save3) {
                    throw new \Exception('Failed to save UserHasRole');
                }
            }
        
            DB::commit(); 
        
            return response()->json([
                'status' => 200,
                'error'  => false,
                'message' => 'Success to import data'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => 400,
                'error'  => true,
                'message' => $th->getMessage()
            ], 400);
        }
    }

}

