<?php

namespace App\Jobs;

use App\Mail\WorkOrderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendWorkOrderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Email recipient
     */
    protected $emailAddress;

    /**
     * Work Order Notification Mailable
     */
    protected $mailable;

    /**
     * Create a new job instance.
     */
    public function __construct(?string $emailAddress, WorkOrderNotification $mailable)
    {
        $this->emailAddress = $emailAddress;
        $this->mailable = $mailable;
        $this->queue = 'emails';
        $this->tries = 3;
        $this->backoff = [60, 300, 900]; // Retry: 1 min, 5 min, 15 min
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->emailAddress)) {
            Log::warning('SendWorkOrderNotification: Email address is empty, skipping...');
            return;
        }

        try {
            Mail::to($this->emailAddress)->send($this->mailable);
            Log::info("Email notification sent to: {$this->emailAddress}");
        } catch (\Exception $e) {
            Log::error('SendWorkOrderNotification Error: ' . $e->getMessage());
            // Jika sudah retry 3 kali, akan di-fail dan masuk ke failed_jobs table
            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendWorkOrderNotification Failed: ' . $exception->getMessage());
        // Bisa tambahan logic di sini, misal: notify admin, send slack message, dll
    }
}
