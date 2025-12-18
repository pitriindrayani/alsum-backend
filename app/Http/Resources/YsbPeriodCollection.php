<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Resources\Json\ResourceCollection;

class YsbPeriodCollection extends ResourceCollection
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
                    'id'                    => $data->id,
                    'period_title'          => $data->period_title,
                    'year'                  => $data->year,
                    'month'                 => $data->month,
                    'period_start'          => $data->period_start,
                    'period_start_weekday'  => $data->period_start_weekday,
                    'period_end'            => $data->period_end,
                    'period_end_weekday'    => $data->period_end_weekday,
                    'in_time'               => $data->in_time,
                    'out_time'              => $data->out_time,
                    'days'                  => $data->days,
                    'alazhar_title'         => $data->alazhar_title,
                    'alazhar_pic'           => $data->alazhar_pic,
                    'period_start_puasa'    => $data->period_start_puasa,
                    'period_end_puasa'      => $data->period_end_puasa,
                    'in_time_puasa'         => $data->in_time_puasa,
                    'out_time_puasa'        => $data->out_time_puasa,
                    'fg_active'             => $data->fg_active,         
                    // 'create_by'             => $data->create_by,
                    'created_at'            => date('Y-m-d H:i:s', strtotime($data->created_at))
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
