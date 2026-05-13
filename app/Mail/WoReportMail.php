<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WoReportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $dataLaporan;
    public $namaPimpinan;

    public function __construct($dataLaporan, $namaPimpinan)
    {
        $this->dataLaporan = $dataLaporan;
        $this->namaPimpinan = $namaPimpinan;
    }

    public function build()
    {
        return $this->subject("[REPORT] Laporan Work Order " . $this->dataLaporan['departemen'] . " - " . $this->dataLaporan['tipe_laporan'])
            ->view('emails.wo_report');
    }
}
