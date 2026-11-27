<?php
namespace App\Notifications;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
class PasswordReset extends Notification {
 public function __construct(private string $token){}
 public function via(object $notifiable): array{return ['mail'];}
 public function toMail(object $notifiable): MailMessage {
  $url=rtrim(config('app.url'),'/').'/reset-password?'.http_build_query(['token'=>$this->token,'email'=>$notifiable->email]);
  return (new MailMessage)->subject('Reset your workspace password')->line('A password reset was requested.')->action('Reset password',$url)->line('Ignore this message if you did not request it.');
 }
}
