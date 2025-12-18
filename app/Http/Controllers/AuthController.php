<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Kis\RestCurl;
use Firebase\JWT\JWT;
use App\Kis\RoleAssign;
use App\Kis\LogActivity;
use App\Mail\VerifyEmail;
use App\Models\UserDetail;
use App\Models\UserPicture;
use App\Models\YsbBranch;
use App\Models\Role;
use App\Kis\HttpStatusCodes;
use App\Kis\PermissionCheck;
use Illuminate\Http\Request;
use App\Kis\PermissionAssign;
use App\Models\Menu;
use App\Models\Module;
use App\Models\RoleHasPermission;
use App\Models\YsbTeacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Create a new token.
     * 
     * @param  \App\User   $user
     * @return string
     */
    protected function createJwt(User $user)
    {
        $payload = [
            'iss'      => "kis-apps-api",       // Issuer of the token
            'sub'      => $user->id,         // Subject of the token
            'email'    => $user->email,   // Subject of the token
            'iat'      => time(),            // Time when JWT was issued. 
            // 'exp'      => time() + 86400     // Expiration time 86400 means 24 hours
        ];
        return JWT::encode($payload, env('JWT_SECRET_KEY'), env('JWT_ALGORITMA'));
    }

    public function checkLogin(Request $request)
    {
        $validator = Validator::make($request->all(),  [
            'email'    => 'required',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'  => true,
                'message' => $validator->errors()->all()[0],
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        $user = User::where(function ($query) use ($request) {
            $query->where('email', $request->email)
                  ->orWhere('nik_ysb', $request->email);
        })
        ->where('state', true)
        ->with('roles')
        ->first();
        
        if (!$user) {
            return response()->json([
                'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                'error' => true,
                'message' => 'User not found'
            ], HttpStatusCodes::HTTP_BAD_REQUEST);
        }
        
        if (Hash::check($request->password, $user->password)) {
            $isDeveloper = $user->level === 'developer';
            if($isDeveloper){
                $rolePermission = RoleHasPermission::where(['state' => true])->with('menu:id,url')->get()->map(function ($permission) {
                    $permission->create = 1;
                    $permission->read = 1;
                    $permission->update = 1;
                    $permission->delete = 1;
                    return $permission;
                });                
                $modules = Module::whereIn('id', function($query) use($user){
                    $query->select('id_module')->from('module_has_menus')->whereIn('id_menu', function($subQuery) use($user) {
                        $subQuery->select('id_menu')->from('permissions')->where('id_user', $user->id);
                    });
                })->orderby('number_order', 'asc')->get();
                $excludedUrls = [
                    '/attendance-dailys-filter-head',
                    '/attendance-summarys',
                    '/attendance-summary-lists-head',
                    '/attendance-dailys',
                    '/wfh',
                    '/wfh-filter-head',
                ];
                foreach ($modules as $key => $value) {
                    $value->menus = Menu::whereIn('id', function($query) use($value) {
                            $query->select('id_menu')
                                ->from('module_has_menus')
                                ->where('id_module', $value->id)
                                ->where('state', true);
                        })
                        ->whereNotIn('url', $excludedUrls) 
                        ->orderBy('name', 'asc')
                        ->get();
                }
            }else{
                $rolePermission = RoleHasPermission::where(['id_role' => $user->id_role, 'state' => true])->with('menu:id,url')->get();
                $ysbRole = Role::where(['id' => $user->id_role, 'state' => true])->first();
                $teacherData = YsbTeacher::where(['id' => $user->id_teacher, 'state' => true])->first();
                $modules = Module::whereIn('id', function ($query) use ($user) {
                    $query->select('id_module')
                        ->from('module_has_menus')
                        ->whereIn('id_menu', function ($subQuery) use ($user) {
                            $subQuery->select('id_menu')
                                ->from('role_has_permissions')
                                ->where('id_role', $user->id_role)
                                ->where('state', true);
                        });
                })->orderBy('number_order', 'asc')->get();
                foreach ($modules as $key => $value) {
                    $value->menus = Menu::whereIn('id', function ($query) use ($value, $user) {
                        $query->select('id_menu')
                            ->from('module_has_menus')
                            ->where('id_module', $value->id)
                            ->whereIn('id_menu', function ($subQuery) use ($user) {
                                $subQuery->select('id_menu')
                                    ->from('role_has_permissions')
                                    ->where('id_role', $user->id_role)
                                    ->where('state', true);
                            });
                    })
                    ->orderBy('name', 'asc')
                    ->get();
                    if ($value->menus->isEmpty()) {
                        unset($modules[$key]);
                    }   
                }
            };
            
            $user->modules = $modules->values() ;
            $user->role_permission = $rolePermission; 
            $user->teacher_data = $teacherData ?? null;
            // $user->ysb_branch_name = $ysbBranch === null? null : $ysbBranch->branch_name;
            if($isDeveloper){
            }else{
            $user->ysb_role_name = $ysbRole === null ? null : $ysbRole->name;
            }

            $token = $this->createJwt($user);
            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Login Successfully',
                'data' => [
                    'token' => $token,
                    'user' => $user
                ]
            ], HttpStatusCodes::HTTP_OK);
        }
        return response()->json([
            'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
            'error'   => true,
            'message' => 'Email or password is wrong!'
        ], HttpStatusCodes::HTTP_BAD_REQUEST);
    }

    /**
     * Check User Permission
     */
    public function checkPermission(Request $request)
    {
        try {
            $validator = Validator::make($request->all(),  [
                'permission'    => 'nullable'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error'  => true,
                    'message' => $validator->errors()->all()[0],
                ],HttpStatusCodes::HTTP_BAD_REQUEST);
            }

            if ($request->auth->level == 'superadmin') {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_OK,
                    'error' => false,
                    'message' => 'Permission granted',
                    'data' => $request->auth 
                ], HttpStatusCodes::HTTP_OK);
            }
            if ($request->auth->level !== 'developer') {
                if($request->permission)
                {
                    $check = PermissionCheck::checkByMenu($request->permission, $request->auth->id, $request->auth->id_role);
                    if (!$check) {
                        return response()->json([
                            'status' => HttpStatusCodes::HTTP_UNAUTHORIZED,
                            'error' => true,
                            'message' => 'Permission denied '. $request->permission,
                            'data' => $request->auth
                        ], HttpStatusCodes::HTTP_UNAUTHORIZED);
                    }
                }
            }
            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'message' => 'Permission granted',
                'data' => $request->auth
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
     * find Profile
     */
    public function profile(Request $request)
    {
        try {
            $user = User::where(['id' => $request->auth->id, 'state' => true])->with('user_detail','pictures')->first();
            if (!$user) {
                return response()->json([
                    'status' => HttpStatusCodes::HTTP_BAD_REQUEST,
                    'error' => true,
                    'message' => 'User not found'
                ], HttpStatusCodes::HTTP_BAD_REQUEST);
            }
            return response()->json([
                'status' => HttpStatusCodes::HTTP_OK,
                'error' => false,
                'data' => $user
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
     * Update user profile
     */
    
}
