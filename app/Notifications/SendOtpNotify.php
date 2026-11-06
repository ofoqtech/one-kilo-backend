<?php

namespace App\Notifications;

use App\Channels\WhatsappChannel;
use Fisal\Otp\Otp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SendOtpNotify extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected string $phone;
    protected string $code;
    public function __construct($phone)
    {
        $this->phone = $phone;

        $otp = (new Otp)->generate($this->phone, 'numeric', 5, 5);
        $this->code = $otp->token;
        Log::info("[OTP generate] phone={$this->phone} token={$this->code}", [
            'row_after_generate' => \Fisal\Otp\Models\Otp::where('identifier', $this->phone)->where('otp_type', 'default')->where('valid', true)->get(['id', 'token', 'created_at'])->toArray(),
        ]);
        if (app()->environment('local')) {
            Log::info("[OTP for {$this->phone}]: {$this->code}");
        }
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return [WhatsappChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     $otp = $this->otp->generate($notifiable->email , 'numeric' , 5 , 20);
    //     // dd($otp);
    //     return (new MailMessage)
    //                 ->greeting('otp code')
    //                 ->line('Verify your code.')
    //                 ->line('Code : ' .$otp->token );
    // }

    public function toWhatsapp($notifiable): array
    {
        return [
            'phone' => $this->phone,
            'code'  => $this->code,
        ];
    }
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
