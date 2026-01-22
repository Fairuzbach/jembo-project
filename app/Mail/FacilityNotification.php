<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Facilities\WorkOrderFacilities; // Model FH

class FacilityNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $ticket;
    public $type;

    public function __construct(WorkOrderFacilities $ticket, $type)
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
                $subject = "[FACILITY] Tiket Berhasil Dibuat ($ticketNum)";
                break;
            case 'need_approval':
                $subject = "[URGENT] Permintaan Approval Tiket Facility ($ticketNum)";
                break;
            case 'fh_new':
                $subject = "[FACILITY-ADMIN] Tiket Baru Masuk Antrian ($ticketNum)";
                break;
            case 'status_update': // Umum (approved/pending)
                $statusPretty = ucfirst(str_replace('_', ' ', $this->ticket->status));
                $subject = "[FACILITY] Status Tiket #{$ticketNum} diperbarui menjadi {$statusPretty}";
                break;
            case 'rejected':
                $subject = "[FACILITY] Tiket Ditolak ($ticketNum)";
                break;
            case 'completed':
                $subject = "[FACILITY] Pekerjaan Selesai ($ticketNum)";
                break;
            default:
                $subject = "[FACILITY] Update Status ($ticketNum)";
        }

        return $this->subject($subject)
            ->view('emails.facility_notification');
    }
}
