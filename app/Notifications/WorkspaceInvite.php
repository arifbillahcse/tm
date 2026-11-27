<?php
namespace App\Notifications;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
class WorkspaceInvite extends Notification {
 public function __construct(private string $token){}
 public function via(object $notifiable): array{return ['mail'];}
 public function toMail(object $notifiable): MailMessage {
  return (new MailMessage)->subject('Workspace invitation')->line('You have been invited to Softorio Workspace. This link expires in 48 hours.')->action('Accept invitation',rtrim(config('app.url'),'/').'/invite?'.http_build_query(['token'=>$this->token]));
 }
}
