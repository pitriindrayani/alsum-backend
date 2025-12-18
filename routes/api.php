<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PersentaseAssuranceController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\MasterCodeController;
use App\Http\Controllers\ModuleMenuController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\SpecialistController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\YsbSchoolController;
use App\Http\Controllers\YsbEducationalStageController;
use App\Http\Controllers\YsbBranchController;
use App\Http\Controllers\YsbPositionController;
use App\Http\Controllers\YsbTeacherStatusController;
use App\Http\Controllers\YsbTeacherGroupController;
use App\Http\Controllers\YsbLeaveController;
use App\Http\Controllers\YsbHolidayController;
use App\Http\Controllers\YsbHolidayTypeController;
use App\Http\Controllers\YsbPeriodController;
use App\Http\Controllers\YsbScheduleController;
use App\Http\Controllers\YsbScheduleTimeController;
use App\Http\Controllers\YsbAttendanceTrxController;
use App\Http\Controllers\YsbTeacherController;
use App\Http\Controllers\YsbTeacherStatusRecordController;
use App\Http\Controllers\YsbAttendanceDailyController;
use App\Http\Controllers\YsbWfhController;
use App\Http\Controllers\YsbScheduleTeacherController;
use App\Http\Controllers\YsbSchoolsUkkConfigController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


/**
 * Check Login
 */
Route::group(['prefix' => 'auth'], function () {

    Route::post('/login', [AuthController::class, 'checkLogin']);

    Route::get('/find/{id}', [UserController::class, 'show'])->middleware('kis');
    Route::get('/permission-check', [AuthController::class, 'checkPermission'])->middleware('kis');
    Route::get('/profile', [AuthController::class, 'profile'])->middleware('kis');
    
    
    // Route::post('/reset-pass', [AuthController::class, 'resetPass']);
    // Route::post('/forget-pass', [AuthController::class, 'forgetPass']);
});

Route::get('/auth/verify/{id}', function (Request $request) {
    $findUser = User::find($request->id);
    if (!$findUser) {
        return view('pages.verifgagal');
    }
    if ($findUser->email_verified_at !== null) {
        return view('pages.verifgagal');
    }
    $findUser->email_verified_at = date('Y-m-d H:i:s');
    $findUser->save();
    return view('pages.verifsuccess');
});

Route::group(['middleware' => ['kis']], function(){

    /**
     * PROFILE
     */
    Route::prefix('profiles')->group(function () {
        Route::get('/', [AuthController::class, 'profile']);
        Route::post('/update', [UserController::class, 'updateProfile']);
    });

   /**
     * USERS
     */
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->middleware('permit:/users_read');
        Route::post('/store', [UserController::class, 'store'])->middleware('permit:/users_create');
        Route::get('/{id}', [UserController::class, 'show'])->middleware('permit:/users_read');
        Route::put('/{id}', [UserController::class, 'update'])->middleware('permit:/users_update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('permit:/users_delete');
        Route::post('/log-activity', [UserController::class, 'logActivity'])->middleware('permit:/logs_read');
        Route::post('/profile', [UserController::class, 'updateProfile']);
    });

    /**
     * SCHOOLS
     */
    Route::prefix('schools')->group(function () {
        Route::get('/', [YsbSchoolController::class, 'index'])->middleware('permit:/schools_read');
        Route::post('/store', [YsbSchoolController::class, 'store'])->middleware('permit:/schools_create');
        Route::get('/{id}', [YsbSchoolController::class, 'show'])->middleware('permit:/schools_read');
        Route::put('/{id}', [YsbSchoolController::class, 'update'])->middleware('permit:/schools_update');
        Route::delete('/{id}', [YsbSchoolController::class, 'destroy'])->middleware('permit:/schools_delete');
    });

     /**
     * STAGE
     */
    Route::prefix('educational-stages')->group(function () {
        Route::get('/', [YsbEducationalStageController::class, 'index'])->middleware('permit:/educational-stages_read');
        Route::post('/store', [YsbEducationalStageController::class, 'store'])->middleware('permit:/educational-stages_create');
        Route::get('/{id}', [YsbEducationalStageController::class, 'show'])->middleware('permit:/educational-stages_read');
        Route::put('/{id}', [YsbEducationalStageController::class, 'update'])->middleware('permit:/educational-stages_update');
        Route::delete('/{id}', [YsbEducationalStageController::class, 'destroy'])->middleware('permit:/educational-stages_delete');
    });

     /**
     * BRANCH
     */
    Route::prefix('branches')->group(function () {
        Route::get('/', [YsbBranchController::class, 'index'])->middleware('permit:/branches_read');
        Route::post('/store', [YsbBranchController::class, 'store'])->middleware('permit:/branches_create');
        Route::get('/{id}', [YsbBranchController::class, 'show'])->middleware('permit:/branches_read');
        Route::put('/{id}', [YsbBranchController::class, 'update'])->middleware('permit:/branches_update');
        Route::delete('/{id}', [YsbBranchController::class, 'destroy'])->middleware('permit:/branches_delete');
    });

    /**
     * POSITION
     */
    Route::prefix('positions')->group(function () {
        Route::get('/', [YsbPositionController::class, 'index'])->middleware('permit:/positions_read');
        Route::post('/store', [YsbPositionController::class, 'store'])->middleware('permit:/positions_create');
        Route::get('/{id}', [YsbPositionController::class, 'show'])->middleware('permit:/positions_read');
        Route::put('/{id}', [YsbPositionController::class, 'update'])->middleware('permit:/positions_update');
        Route::delete('/{id}', [YsbPositionController::class, 'destroy'])->middleware('permit:/positions_delete');
    });


     /**
     * 
     */
    Route::prefix('teachers')->group(function () {
        Route::get('/', [YsbTeacherController::class, 'index'])->middleware('permit:/teachers_read');
        Route::get('/all', [YsbTeacherController::class, 'indexSchedule']);
        Route::post('/store', [YsbTeacherController::class, 'store'])->middleware('permit:/teachers_create');
        Route::get('/{id}', [YsbTeacherController::class, 'show'])->middleware('permit:/teachers_read');
        Route::put('/{id}', [YsbTeacherController::class, 'update'])->middleware('permit:/teachers_update');
        Route::delete('/{id}', [YsbTeacherController::class, 'destroy'])->middleware('permit:/teachers_delete');
        Route::post('/excel/store', [YsbTeacherController::class, 'storeExcel'])->middleware('permit:/teachers_create');
    });

   
    Route::prefix('role-teachers')->group(function () {
        Route::get('/', [RoleController::class, 'indexTeachers']);
    });

    Route::prefix('change-passwords')->group(function () {
        Route::put('/', [UserController::class, 'changePassword']);
    });

    Route::prefix('role-developers')->group(function () {
        Route::get('/', [RoleController::class, 'indexDeveloper'])->middleware('permit:role-developers_read');
    });
   
    /**
     * PREVILEGES
     */
    Route::prefix('privileges')->group(function() {

        /**
         * USERS
         */
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index'])->middleware('permit:/users_read');
            Route::post('/store', [UserController::class, 'store'])->middleware('permit:/users_create');
            Route::get('/{id}', [UserController::class, 'show'])->middleware('permit:/users_read');
            Route::put('/{id}', [UserController::class, 'update'])->middleware('permit:/users_update');
            Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('permit:/users_delete');
            Route::post('/log-activity', [UserController::class, 'logActivity'])->middleware('permit:/logs_read');
        });

        /**
         * ROLES
         */
        Route::prefix('roles')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->middleware('permit:/roles_read');
            Route::post('/store', [RoleController::class, 'store'])->middleware('permit:/roles_create');
            Route::get('/{id}', [RoleController::class, 'show'])->middleware('permit:/users_read');
            Route::put('/{id}', [RoleController::class, 'update'])->middleware('permit:/users_update');
            Route::delete('/{id}', [RoleController::class, 'destroy'])->middleware('permit:/users_delete');
        });

        /**
         * MODULE
         */
        Route::prefix('modules')->group(function () {
            Route::get('/', [ModuleController::class, 'index'])->middleware('permit:/modules_read');
            Route::post('/store', [ModuleController::class, 'store'])->middleware('permit:/modules_create');
            Route::get('/{id}', [ModuleController::class, 'show'])->middleware('permit:/modules_read');
            Route::put('/{id}', [ModuleController::class, 'update'])->middleware('permit:/modules_update');
            Route::delete('/{id}', [ModuleController::class, 'destroy'])->middleware('permit:/modules_delete');
        });

        /**
         * MODULE
         */
        Route::prefix('module-menus')->group(function () {
            Route::get('/', [ModuleMenuController::class, 'index'])->middleware('permit:/module-menus_read');
            Route::post('/store', [ModuleMenuController::class, 'store'])->middleware('permit:/module-menus_create');
            Route::delete('/{id}', [ModuleMenuController::class, 'destroy'])->middleware('permit:/module-menus_delete');
        });

        /**
         * MENU
         */
        Route::prefix('menus')->group(function () {
            Route::get('/', [MenuController::class, 'index'])->middleware('permit:/menus_read');
            Route::post('/store', [MenuController::class, 'store'])->middleware('permit:/menus_create');
            Route::get('/{id}', [MenuController::class, 'show'])->middleware('permit:/menus_read');
            Route::put('/{id}', [MenuController::class, 'update'])->middleware('permit:/menus_update');
            Route::delete('/{id}', [MenuController::class, 'destroy'])->middleware('permit:/menus_delete');
        });

        /**
         * Role Permission
         */
        Route::prefix('role-permissions')->group(function () {
            Route::get('/{idRole}', [RolePermissionController::class, 'index'])->middleware('permit:/role-permissions_read');
            Route::post('/store', [RolePermissionController::class, 'store'])->middleware('permit:/role-permissions_create');
            Route::put('/{id}', [RolePermissionController::class, 'update'])->middleware('permit:/role-permissions_update');
            Route::delete('/{id}', [RolePermissionController::class, 'destroy'])->middleware('permit:/role-permissions_delete');
        });

        /**
         * User Permission
         */
        Route::prefix('permissions')->group(function () {
            Route::get('/{idUser}', [PermissionController::class, 'index'])->middleware('permit:/permissions_read');
            Route::post('/store', [PermissionController::class, 'store'])->middleware('permit:/permissions_create');
            Route::put('/{id}', [PermissionController::class, 'update'])->middleware('permit:/permissions_update');
            Route::delete('/{id}', [PermissionController::class, 'destroy'])->middleware('permit:/permissions_delete');
        });
    });

        /**
         * MASTER CODE
         */
    Route::prefix('master-codes')->group(function () {
        Route::get('/', [MasterCodeController::class, 'index'])->middleware('permit:/master-codes_read');
        Route::post('/store', [MasterCodeController::class, 'store'])->middleware('permit:/master-codes_create');
        Route::get('/{id}', [MasterCodeController::class, 'show'])->middleware('permit:/master-codes_read');
        Route::put('/{id}', [MasterCodeController::class, 'update'])->middleware('permit:/master-codes_update');
        Route::delete('/{id}', [MasterCodeController::class, 'destroy'])->middleware('permit:/master-codes_delete');
        Route::post('/generate', [MasterCodeController::class, 'generateNumber']);
        Route::post('/update-number', [MasterCodeController::class, 'updateNumber']);
    });

    /**
     * USER ROLE
     */
    Route::prefix('user-roles')->group(function () {
        Route::get('/{id}', [UserController::class, 'checkUserByRoleName']);
        });
});

   