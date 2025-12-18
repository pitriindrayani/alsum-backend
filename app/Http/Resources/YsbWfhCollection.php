<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Resources\Json\ResourceCollection;

class YsbWfhCollection extends ResourceCollection
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
                    'ysb_teacher_id'    => $data->ysb_teacher_id,
                    'ysb_branch_id'     => $data->ysb_branch_id,
                    'ysb_school_id'     => $data->ysb_school_id,
                    'id_user_head_school'  => $data->id_user_head_school,
                    'teacher_nip_ypi'      => $data->teacherDetail->nip_ypi_karyawan === null? $data->teacherDetail->nip_ypi : $data->teacherDetail->nip_ypi_karyawan,
                    'full_name'         => $data->full_name,
                    'att_date'          => $data->att_date,
                    'att_clock_in'      => $data->att_clock_in,
                    'att_clock_out'     => $data->att_clock_out,
                    'keterangan'        => $data->keterangan,
                    'dokument'          => $data->dokument,
                    'approve_hr'          => $data->approve_hr,
                    'approve_head_school' => $data->approve_head_school,
                    'approve_at_head'   => $data->approve_at_head,
                    'approve_by_head'   => $data->approve_by_head,
                    'approve_at_hr'     => $data->approve_at_hr,
                    'approve_by_hr'     => $data->approve_by_hr,
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
