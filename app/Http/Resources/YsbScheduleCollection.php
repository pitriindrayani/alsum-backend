<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Resources\Json\ResourceCollection;

class YsbScheduleCollection extends ResourceCollection
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
                    'ysb_school_id'     => $data->ysb_school_id,
                    'ysb_position_id'    => $data->ysb_position_id,
                    'ysb_position_code'  => $data->ysb_position_code,                 
                    'schedule_code'     => $data->schedule_code,
                    'in_time'           => $data->in_time,
                    'out_time'          => $data->out_time,
                    'day_1'             => $data->day_1,
                    'day_2'             => $data->day_2,
                    'day_3'             => $data->day_3,
                    'day_4'             => $data->day_4,
                    'day_5'             => $data->day_5,
                    'day_6'             => $data->day_6,
                    'day_7'             => $data->day_7,
                    'fg_school_default' => $data->fg_school_default,
                    'holiday_type'      => $data->holiday_type,
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
