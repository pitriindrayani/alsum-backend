<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Resources\Json\ResourceCollection;

class YsbHolidayCollection extends ResourceCollection
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
                    'ysb_branch_id'      => $data->ysb_branch_id,
                    'ysb_school_id'      => $data->ysb_school_id,
                    'ysb_teacher_id'     => $data->ysb_teacher_id,
                    'ysb_school_name'    => $data->ysb_school->school_name,
                    'full_name'          => $data->full_name,
                    'holiday_name'       => $data->holiday_name,
                    'holiday_date'       => $data->holiday_date,
                    'holiday_date_end'   => $data->holiday_date_end,
                    'holiday_weekday'    => $data->holiday_weekday,
                    'holiday_type_id'    => $data->holiday_type_id,
                    'holiday_type_name'  => $data->ysb_holiday_type->holiday_type,
                    // 'create_by'          => $data->create_by,
                    'created_at'         => date('Y-m-d H:i:s', strtotime($data->created_at)),
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
