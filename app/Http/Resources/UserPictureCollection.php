<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserPictureCollection extends ResourceCollection
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
                    'id_user'       => $data->id_user,
                    'url_image'     => $data->url_image,
                    'is_signature'  => $data->is_signature ? true : false,
                    'set_as_app'    => $data->set_as_app ? true : false,
                    'create_by'     => $data->create_by,
                    'update_by'     => $data->update_by,
                    'created_at'    => date('Y-m-d H:i:s', strtotime($data->created_at)),
                    'updated_at'    => date('Y-m-d H:i:s', strtotime($data->updated_at))
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
            'message' => 'Successfully get user picture list'
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
