<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id'             => $this->id,
            'nomor_tiket'    => $this->ticket_num,
            'pengirim'       => [
                'nama'       => $this->requester_name,
                'department' => $this->department,
                'plant'      => $this->plant,
            ],
            // Bungkus di sini agar sesuai dengan JS item.detail_pekerjaan
            'detail_pekerjaan' => [
                'deskripsi'    => $this->description,
                'kategori'     => $this->category,
            ],
            'status_pengerjaan' => [
                'status_tiket' => $this->status, // sesuaikan dengan key di JS
            ],
            'tanggal_dibuat' => $this->created_at->format('d-m-Y H:i'),
        ];
    }
}
