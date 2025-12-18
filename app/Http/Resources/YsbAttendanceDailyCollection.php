<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Models\YsbPeriod;


class YsbAttendanceDailyCollection extends ResourceCollection
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
                $periodData = YsbPeriod::where('state', true)
                ->whereDate('period_start', '<=', $data->att_date)
                ->whereDate('period_end', '>=', $data->att_date)
                ->first();

                return [
                    'id'                    => $data->id,
                    'ysb_teacher_id'        => $data->ysb_teacher_id,
                    'ysb_branch_id'         => $data->ysb_branch_id,
                    'ysb_school_id'         => $data->ysb_school_id,
                    'id_user_head_school'   => $data->id_user_head_school,
                    'full_name'             => $data->full_name,
                    'id_user_hr'            => $data->id_user_hr,
                    'approve_hr'            => $data->approve_hr,
                    'approve_head_school'   => $data->approve_head_school,
                    'teacher_name'          => $data->teacherDetail->full_name,
                    'teacher_nip_ypi'       => $data->teacherDetail->nip_ypi_karyawan === null? $data->teacherDetail->nip_ypi : $data->teacherDetail->nip_ypi_karyawan,
                    'att_date'              => $data->att_date,
                    'att_clock_in'          => $data->att_clock_in,
                    'att_clock_out'         => $data->att_clock_out,
                    'schedule_in'           => $data->schedule_in,
                    'schedule_out'          => $data->schedule_out,
                    'late_min'              => $data->late_min,
                    'early_min'             => $data->early_min,
                    'absent_type'           => $data->absent_type,
                    'tipe_koreksi'          => $data->tipe_koreksi,
                    'total_koreksi'         => $data->total_koreksi,
                    'keterangan'            => $data->keterangan,
                    'kjm'                   => $data->kjm,
                    'ket1'                  => $data->ket1,
                    'telat_kurang_5'        => $data->telat_kurang_5,
                    'telat_lebih_5'         => $data->telat_lebih_5,
                    'pulang_kurang_5'       => $data->pulang_kurang_5,
                    'pulang_lebih_5'        => $data->pulang_lebih_5,
                    'jumlah_waktu'          => $data->jumlah_waktu,
                    'jam_lembur'            => $data->jam_lembur,
                    'absen1'                => $data->absen1,
                    'fg_locked'             => $data->fg_locked,
                    'dokument'              => $data->dokument,
                    'approve_at_head'       => $data->approve_at_head,
                    'approve_at_hr'         => $data->approve_at_hr,
                    'in_time'               => $data->in_time,
                    'out_time'              => $data->out_time,
                    'update'                => $data->update,
                    'update_arrive'         => $data->update_arrive,
                    'update_late'           => $data->update_late,
                    'update_absen1x'        => $data->update_absen1x,
                    'update_kehadiran'      => $data->update_kehadiran,
                    // 'create_by'          => $data->create_by,
                    'created_at'            => date('Y-m-d H:i:s', strtotime($data->created_at)),
                    'period_data'           => $periodData,
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
