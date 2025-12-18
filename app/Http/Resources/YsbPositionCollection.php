<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Resources\Json\ResourceCollection;

class YsbPositionCollection extends ResourceCollection
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
                    'id'                => $data->id,
                    'position_code'     => $data->position_code,
                    'position_category' => $data->position_category,
                    'position'          => $data->position,
                    'slug_name'         => $data->slug_name,
                    // 'create_by'         => $data->create_by,
                    'created_at'        => date('Y-m-d H:i:s', strtotime($data->created_at)),
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
            'message' => 'Successfully get data'
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
