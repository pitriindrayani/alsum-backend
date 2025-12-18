<?php

namespace App\Kis;

use App\Models\DefaultRolePermission;
use App\Models\Menu;
use App\Models\User;
use App\Models\Permission;
use App\Models\RoleHasPermission;
use App\Models\RolePermission;
use App\Models\UserPermission;

class PermissionAssign {

    /**
     * Store permission to user
     */
    public static function addPermissionByRole($idRole, $idUser)
    {
        $getPermission = RoleHasPermission::where(['id_role' => $idRole, 'state' => true])->get();
        if(count($getPermission) > 0)
        {
            foreach ($getPermission as $value) {
                $checkDefault = Permission::where(['id_user' => $idUser,'id_menu' => $value->id_menu,'state' => true])->first();
                if(!$checkDefault)
                {
                    Permission::create([
                        'id_user'    => $idUser,
                        'id_menu'    => $value->id_menu,
                        'create'     => $value->create,
                        'read'       => $value->read,
                        'update'     => $value->update,
                        'delete'     => $value->delete,
                        'create_by'  => request()->auth->id
                    ]);
                }
            }
        }
    }

    /**
     * Assign all menu permission to user
     */
    public static function addAllPermission($idUser)
    {
        $getAllMenu = Menu::where('state', true)->get();
        if (count($getAllMenu) > 0) {
            foreach ($getAllMenu as $value) {
                $checkUser = Permission::where(['id_user' => $idUser, 'id_menu' => $value->id, 'state' => true])->first();
                if(!$checkUser)
                {
                    Permission::create([
                        'id_user' => $idUser,
                        'id_menu' => $value->id,
                        'create' => true,
                        'read' => true,
                        'update' => true,
                        'delete' => true,
                        'create_by' => request()->auth->id
                    ]);
                }
            }
        }
    }

    /**
     * Assign new menu to all developer permission
     */
    public static function addNewMenuToDev($idMenu)
    {
        $getAllDev = User::where(['level' => 'developer', 'state' => true])->get();
        if(count($getAllDev) > 0)
        {
            foreach ($getAllDev as $value) {
                Permission::create([
                    'id_user' => $value->id,
                    'id_menu' => $idMenu,
                    'create' => true,
                    'read' => true,
                    'update' => true,
                    'delete' => true,
                    'create_by' => request()->auth->id
                ]);
            }
        }
    }

    /**
     * Remove menu from developer permission
     */
    public static function removeMenuFromDev($idMenu)
    {
        $getAllDev = User::where(['level' => 'developer', 'state' => true])->get();
        if (count($getAllDev) > 0) {
            foreach ($getAllDev as $value) {
                Permission::where(['id_user' => $value->id, 'id_menu' => $idMenu])->update([
                    'state' => false,
                    'update_by' => request()->auth->id
                ]);
            }
        }
    }

    /**
     * Remove all permission
     */
    public static function clearAllByUser($idUser)
    {
        Permission::where(['id_user' => $idUser, 'state' => true])->update([
            'state' => false,
            'update_by' => request()->auth->id
        ]);
    }
}