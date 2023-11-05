<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\SendEmailVerificationLink;
use Illuminate\Support\Str;
use App\Models\Verification;
use Mail;

class SendVerificationMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $details;

    protected $randomNumber;

    protected $randomString;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($details)
    {
         $this->details = $details;

        $this->randomString = Str::random(40);
        $this->randomNumber = rand(1000, 9999);

        Verification::updateOrCreate([
            'username' => $details['email'],
        ],[
           // 'expiry_at' => now()->addDays(config('common.email_verification_expiry_time_in_days')),
            'random_string' => $this->randomString,
            'otp' => $this->randomNumber
        ]);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Log::info(['I am from SendEmailVerificationJob' => $this->details, $this->randomNumber]);
        Mail::to($this->details['email'])->send(new SendEmailVerificationLink($this->details, $this->randomNumber));
    }
}
