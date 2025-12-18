<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PermissionCollection extends ResourceCollection
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
                    'id'             => $data->id,
                    'id_user'        => $data->id_user,
                    'id_menu'        => $data->id_menu,
                    'create'         => $data->create ? true : false,
                    'read'           => $data->read ? true : false,
                    'update'         => $data->update  ? true : false,
                    'delete'         => $data->delete  ? true : false,
                    'user_data'      => $data->user,
                    'menu_data'      => $data->menu,
                    // 'create_by'      => $data->create_by,
                    // 'update_by'      => $data->update_by,
                    // 'create_by_data' => $data->create_by_data,
                    // 'update_by_data' => $data->update_by_data,
                    'created_at'     => date('Y-m-d H:i:s', strtotime($data->created_at)),
                    // 'updated_at'     => date('Y-m-d H:i:s', strtotime($data->updated_at))
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
            'message' => 'Successfully get user permission list'
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
