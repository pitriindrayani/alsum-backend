<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Resources\Json\ResourceCollection;

class YsbTeacherCollection extends ResourceCollection
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
                    'id' => $data->id,
                    'nip_ypi'=> $data->nip_ypi,
                    'nip_ypi_karyawan'=> $data->nip_ypi_karyawan,
                    'nik_ysb'=> $data->nik_ysb,
                    'join_date_ypi'=> $data->join_date_ypi,
                    'join_date_ysb'=> $data->join_date_ysb,
                    'full_name'=> $data->full_name,
                    'nik_ktp'=> $data->nik_ktp,
                    'birthplace'=> $data->birthplace,
                    'birthdate'=> $data->birthdate,
                    'gender'=> $data->gender,
                    'employment_status'=> $data->employment_status,
                    'ysb_branch_id'=> $data->ysb_branch_id,
                    'edu_stage'=> $data->edu_stage,
                    'ysb_school_id'=> $data->ysb_school_id,
                    'ysb_position_id'=> $data->ysb_position_id,
                    'ysb_schedule_id'=> $data->ysb_schedule_id,
                    'bidang'=> $data->bidang,
                    'ysb_teacher_group_id'=> $data->ysb_teacher_group_id,
                    'religion'=> $data->religion,
                    'addrees'=> $data->addrees,
                    'dom_address'=> $data->dom_address,
                    'marriage'=> $data->marriage,
                    'npwp'=> $data->npwp,
                    'ptkp'=> $data->ptkp,
                    'university'=> $data->university,
                    'major'=> $data->major,
                    'degree'=> $data->degree,
                    'mobile'=> $data->mobile,
                    'email'=> $data->email,
                    'bank'=> $data->bank,
                    'nama_rekening'=> $data->nama_rekening,
                    'no_rekening'=> $data->no_rekening,
                    'contact_name'=> $data->contact_name,
                    'contact_relation'=> $data->contact_relation,
                    'contact_number'=> $data->contact_number,
                    'nuptk'=> $data->nuptk,
                    'user_id'=> $data->user_id,
                    'zakat'=> $data->zakat,
                    'fg_active'=> $data->fg_active,
                    'finger_id'=> $data->finger_id,
                    // 'create_by' => $data->create_by,
                    'created_at' => date('Y-m-d H:i:s', strtotime($data->created_at)),
                    'user_detail' => $data->users
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
