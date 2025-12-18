<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class RoleCollection extends ResourceCollection
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
                    'id'                 => $data->id,
                    'name'               => $data->name,
                    'slug_name'          => $data->slug_name,
                    'icon_name'          => $data->icon_name,
                    'ysb_branch_id'      => $data->ysb_branch_id,
                    // 'create_by'          => $data->create_by,
                    // 'update_by'          => $data->update_by,
                    // 'create_by_data'     => $data->create_by != null ? $data->create_by_data : null,
                    // 'update_by_data'     => $data->create_by != null ? $data->update_by_data : null,
                    'created_at'         => date('Y-m-d H:i:s', strtotime($data->created_at)),
                    // 'updated_at'         => date('Y-m-d H:i:s', strtotime($data->updated_at))
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
            'message' => 'Successfully get role list'
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
