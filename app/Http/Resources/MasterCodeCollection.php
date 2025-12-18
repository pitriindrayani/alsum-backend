<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Kis\HttpStatusCodes;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MasterCodeCollection extends ResourceCollection
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
                    'type'           => $data->type,
                    'start_by'       => $data->start_by,
                    'first_code'     => $data->first_code,
                    'seperate_by'    => $data->seperate_by,
                    'month_type'     => $data->month_type,
                    'reset_type'     => $data->reset_type,
                    'create_by'      => $data->create_by,
                    'update_by'      => $data->update_by,
                    'create_by_data' => $data->create_by != null ? $data->create_by_data : null,
                    'update_by_data' => $data->create_by != null ? $data->update_by_data : null,
                    'created_at'     => date('Y-m-d H:i:s', strtotime($data->created_at)),
                    'updated_at'     => date('Y-m-d H:i:s', strtotime($data->updated_at))
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
            'status'  => HttpStatusCodes::HTTP_OK,
            'message' => 'Successfully get master codes list'
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
