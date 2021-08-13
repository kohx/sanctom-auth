<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $token;
    protected $resetRoute = 'reset';

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($token)
    {
        // 引数でトークンを受け取る
        $this->token = $token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // 件名
        $subject = __('reset password mail');

        // VueへのコールバックURLをルート名で取得
        $baseUrl = config('app.url');
        $token = $this->token;
        $url = "{$baseUrl}/{$this->resetRoute}/{$token}";

        return $this->subject($subject)
            // 送信メールのビュー
            ->view('mails.reset_password_mail')
            // ビューで使う変数を渡す
            ->with('url', $url);
    }
}
