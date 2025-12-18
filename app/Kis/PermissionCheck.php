<?php

namespace App\Kis;

use App\Models\Menu;
use App\Models\RoleHasPermission;
use App\Models\Submenu;
use App\Models\UserPermission;

class PermissionCheck {

    /**
     * Check by Id User 
     */
    public static function checkByIdUser($idUser)
    {
        return Permission::where(['id_user' => $idUser, 'state' => true])->with('menu','submenu')->get();
    }

    /**
     * Check by menu & submenu permission
     */
    public static function checkByMenu($permission, $idUser, $idRole)
    {
        $expValue = \explode('_', $permission);

        $findPermission = RoleHasPermission::where('id_role', $idRole)->where('id_menu', function($query) use($expValue){
            $query->select('id')->from('menus')->where('url', $expValue[0])->where('state', true);
        })->where('state', true)->first();
        if(!$findPermission)
        {
            return false;
        }
        if ($findPermission->create && $expValue[1] == 'create') {
            return true;
        }
        if ($findPermission->read && $expValue[1] == 'read') {
            return true;
        }
        if ($findPermission->update && $expValue[1] == 'update') {
            return true;
        }
        if ($findPermission->delete && $expValue[1] == 'delete') {
            return true;
        }
        return false;
    }
}