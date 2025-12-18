<?php

namespace App\Kis;

use App\Models\RoleHasPermission;
use App\Models\User;
use App\Models\UserHasRole;

class RoleAssign {
    
    /**
     * Store new role to SU
     *
     * @return void
     */
    public static function addSURole($idRole, $idUser)
    {
        $findSu = User::where('level', 'developer')->get();
        foreach ($findSu as $value) {
            UserHasRole::create([
                'id_user' => $value->id,
                'id_role' => $idRole,
                'create_by' => $idUser
            ]);
        }
    }
    
    /**
     * Remove role to SU
     *
     * @return void
     */
    public static function removeSURole($idRole, $idUser)
    {
        UserHasRole::where('id_role', $idRole)->update([
            'state' => false,
            'update_by' => $idUser
        ]);
        LogActivity::addToLog('Success to remove all user role', $idUser);
    }

    /**
     * Update new permission by update user role
     */
    public static function updatePermissionByRole($idUser, $idRoleNew)
    {
        $findUserRole = UserHasRole::where(['id_user' => $idUser, 'state' => true])->first();
        if(!$findUserRole)
        {
            UserHasRole::create([
                'id_user' => $idUser,
                'id_role' => $idRoleNew,
                'create_by' => request()->auth->id
            ]);

            LogActivity::addToLog('Success to create data user role', request()->auth->id);
        } else {
            if($idRoleNew !== $findUserRole->id_role)
            {
                PermissionAssign::clearAllByUser($idUser);
            }
        }
        PermissionAssign::addPermissionByRole($idRoleNew, $idUser);
    }
}