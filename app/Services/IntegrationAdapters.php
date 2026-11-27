<?php
namespace App\Services;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;
class IntegrationAdapters {
 public const PROVIDERS=['telegram','gmail','google_calendar','google_drive','github','whmcs','rss','webhook'];
 public function __construct(private IntegrationTransport $transport){}
 public static function endpoint(string $provider,array $settings,?string $secret):array{
  if($provider==='telegram'){if(!preg_match('/^\d{5,20}:[A-Za-z0-9_-]{20,100}$/D',$secret??''))throw new RuntimeException('Invalid bot token.');return ['https://api.telegram.org/bot'.$secret.'/getMe',[]];}
  $fixed=['gmail'=>'https://gmail.googleapis.com/gmail/v1/users/me/profile','google_calendar'=>'https://www.googleapis.com/calendar/v3/users/me/calendarList?maxResults=1','google_drive'=>'https://www.googleapis.com/drive/v3/about?fields=user','github'=>'https://api.github.com/user'];
  if(isset($fixed[$provider])){if(!$secret||!preg_match('/^[A-Za-z0-9._~+\/=-]{1,2048}$/D',$secret))throw new RuntimeException('Invalid access token.');return [$fixed[$provider],['Authorization: Bearer '.$secret,'Accept: application/json']];}
  if($provider==='rss')return [$settings['url']??'',[]];
  throw new RuntimeException('Provider has no outbound test.');
 }
 public function check(object $integration):void{
  if(!config('communication.integrations_enabled'))throw new RuntimeException('Integrations disabled.');
  $settings=json_decode($integration->settings,true,512,JSON_THROW_ON_ERROR);$secret=$integration->secret?Crypt::decryptString($integration->secret):null;[$url,$headers]=self::endpoint($integration->provider,$settings,$secret);
  $response=$this->transport->get($url,$headers);
  if($integration->provider==='rss'){
   // Non-network XML parsing; reject DTDs/entities, never load remote resources.
   if(preg_match('/<!\s*(?:DOCTYPE|ENTITY)/i',$response['body']))throw new RuntimeException('Unsafe feed.');
   $doc=new \DOMDocument();if(!@$doc->loadXML($response['body'],LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING)||!in_array($doc->documentElement?->localName,['rss','feed','RDF'],true))throw new RuntimeException('Invalid feed.');
  }else{$json=json_decode($response['body'],true,512,JSON_THROW_ON_ERROR);if(!is_array($json)||($integration->provider==='telegram'&&($json['ok']??false)!==true))throw new RuntimeException('Invalid provider response.');}
 }
}
