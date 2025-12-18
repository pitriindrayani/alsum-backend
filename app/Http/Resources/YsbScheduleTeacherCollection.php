<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Resources\Json\ResourceCollection;

class YsbScheduleTeacherCollection extends ResourceCollection
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
                    'year'              => $data->year,
                    'month'             => $data->month,
                    'ysb_branch_id'     => $data->ysb_branch_id,
                    'ysb_school_id'     => $data->ysb_school_id,
                    'ysb_id_teacher'    => $data->ysb_id_teacher,
                    'full_name'         => $data->full_name,
                    'date'              => $data->date,
                    'day_libur'         => $data->day_libur,
                    'day_keterangan'    => $data->day_keterangan,
                    'in_time'           => $data->in_time,
                    'out_time'          => $data->out_time,
                    'update_arrive'     => $data->update_arrive,
                    'update_late'       => $data->update_late,
                    'update_duration'   => $data->update_duration,
                    // 'create_by'      => $data->create_by,
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
