<?php
return ['email_enabled'=>env('COMM_EMAIL_ENABLED',false),'integrations_enabled'=>env('COMM_INTEGRATIONS_ENABLED',false),'push'=>['enabled'=>env('COMM_PUSH_ENABLED',false),'subject'=>env('VAPID_SUBJECT'),'public_key'=>env('VAPID_PUBLIC_KEY'),'private_key'=>env('VAPID_PRIVATE_KEY'),'allowed_hosts'=>['fcm.googleapis.com','push.services.mozilla.com','web.push.apple.com','notify.windows.com']]];
