<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        Carbon::setLocale('id');
        return [
            'data' => $this->collection->transform(function ($data) {
                return [
                    'id'            => $data->id,
                    'code'          => $data->code,
                    'unique_id'     => !$data->user_detail ? null : $data->user_detail->unique_id,
                    'username'      => $data->username,
                    'id_role'      => $data->id_role,
                    'firstname'     => !$data->user_detail ? null : $data->user_detail->firstname,
                    'lastname'      => !$data->user_detail ? null : $data->user_detail->lastname,
                    'address'       => !$data->user_detail ? null : $data->user_detail->address,
                    'email'         => $data->email,
                    'level'         => $data->level,
                    'phone_number'  => !$data->user_detail ? null : $data->user_detail->phone_number,
                    'birth_place'   => !$data->user_detail ? null : $data->user_detail->birth_place,
                    'birth_day'     => !$data->user_detail ? null : $data->user_detail->birth_day,
                    'gender'        => !$data->user_detail ? null : $data->user_detail->gender,
                    'photo_profile' => $data->photo_profile,
                    'roles'         => $data->roles,
                    //'specialists'   => $data->specialists,
                    // 'create_by'     => !$data->user_detail ? null : User::find($data->user_detail->create_by),
                    // 'update_by'     => !$data->user_detail ? null : User::find($data->user_detail->update_by),
                    'created_at'    => date('Y-m-d H:i:s', strtotime($data->created_at)),
                    // 'updated_at'    => date('Y-m-d H:i:s', strtotime($data->updated_at))
                ];
            }),
            'pagination' => [
                'total'        => $this->total(),
                'count'        => $this->count(),
                'per_page'     => $this->perPage(),
                'current_page' => $this->currentPage(),
                'total_pages'  => $this->lastPage()
            ]
        ];
    }

    public function with($request)
    {
        return [
            'error'   => false,
            'status'  => 200,
            'message' => 'Successfully get user list'
        ];
    }

    public function withResponse($request, $response)
    {
        $jsonResponse = json_decode($response->getContent(), true);
        unset($jsonResponse['links']);
        unset($jsonResponse['meta']['path']);
        $response->setContent(json_encode($jsonResponse));
    }
}
