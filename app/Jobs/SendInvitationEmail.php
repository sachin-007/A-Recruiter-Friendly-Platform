<?php

namespace App\Jobs;

use App\Models\Invitation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendInvitationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $invitation;

    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
    }

    public function handle()
    {
        $frontendUrl = config('app.frontend_url') . '/test/' . $this->invitation->token;
        Mail::send('emails.invitation', ['invitation' => $this->invitation, 'url' => $frontendUrl], function ($message) {
            $message->to($this->invitation->candidate_email)->subject('Test Invitation');
        });
    }
}