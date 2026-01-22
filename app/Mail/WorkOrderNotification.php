<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\GeneralAffair\WorkOrderGeneralAffair;

class WorkOrderNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $ticket;
    public $type;

    // TIPE-TIPE BARU:
    // 'created_info'  -> Info ke Pelapor (Tiket Dibuat)
    // 'need_approval' -> Request ke Manager (Butuh Approval)
    // 'ga_new'        -> Info ke GA (Tiket Baru Masuk dari Dept Lain)
    // 'approved'      -> Info ke Pelapor (Tiket Disetujui/On Process)
    // 'rejected'      -> Info ke Pelapor (Ditolak)
    // 'completed'     -> Info ke Pelapor (Selesai)

    public function __construct(WorkOrderGeneralAffair $ticket, $type)
    {
        $this->ticket = $ticket;
        $this->type = $type;
    }

    public function build()
    {
        $subject = '';
        $ticketNum = $this->ticket->ticket_num;

        switch ($this->type) {
            case 'created_info':
                $subject = "[GA-WO] Tiket Berhasil Dibuat ($ticketNum)";
                break;
            case 'need_approval':
                $subject = "[URGENT] Permintaan Approval Tiket GA ($ticketNum)";
                break;
            case 'ga_new':
                $subject = "[GA-ADMIN] Tiket Baru Masuk Antrian ($ticketNum)";
                break;
            case 'approved':
                $subject = "[GA-WO] Tiket Sedang Diproses ($ticketNum)";
                break;
            case 'rejected':
                $subject = "[GA-WO] Tiket Ditolak ($ticketNum)";
                break;
            case 'completed':
                $subject = "[GA-WO] Pekerjaan Selesai ($ticketNum)";
                break;
            default:
                $subject = "[GA-WO] Update Status ($ticketNum)";
        }

        return $this->subject($subject)
            ->view('emails.work_order_notification'); // View tetap satu file, tapi isinya kita bikin dinamis
    }
}
