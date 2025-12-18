<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Resources\Json\ResourceCollection;

class YsbSchoolCollection extends ResourceCollection
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
                    'ysb_branch_id'     => $data->ysb_branch_id,
                    'ysb_branch_name'   => $data->ysb_branch->branch_name,
                    'school_code'       => $data->school_code,
                    'school_name'       => $data->school_name,
                    'slug_name'         => $data->slug_name,
                    'npsn'              => $data->npsn,
                    'province'          => $data->province,
                    'district'          => $data->district,
                    'subdistrict'       => $data->subdistrict,
                    'address'           => $data->address,
                    'postal_code'       => $data->postal_code,
                    'edu_stage'         => $data->edu_stage,
                    'phone'             => $data->phone,
                    'website'           => $data->website,
                    'email'             => $data->email,
                    'school_logo'       => $data->school_logo,
                    'nss'               => $data->nss,
                    'village'           => $data->village,
                    'footer_school_name'=> $data->footer_school_name,
                    'akreditasi'        => $data->akreditasi,
                    // 'create_by'         => !$data->user_detail ? null : User::find($data->user_detail->create_by),
                    // 'update_by'      => !$data->user_detail ? null : User::find($data->user_detail->update_by),
                    'created_at'     => date('Y-m-d H:i:s', strtotime($data->created_at)),
                    // 'updated_at'     => date('Y-m-d H:i:s', strtotime($data->updated_at))
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
