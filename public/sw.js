const VERSION='softorio-static-v1';
const STATIC=['/manifest.webmanifest','/offline.html','/pwa-icon.svg'];
self.addEventListener('install',event=>{event.waitUntil(caches.open(VERSION).then(cache=>cache.addAll(STATIC)));self.skipWaiting();});
self.addEventListener('activate',event=>{event.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(key=>key!==VERSION).map(key=>caches.delete(key)))).then(()=>self.clients.claim()));});
self.addEventListener('fetch',event=>{
 const request=event.request,url=new URL(request.url);
 if(request.method!=='GET'||url.origin!==self.location.origin)return;
 if(request.mode==='navigate'){event.respondWith(fetch(request).catch(()=>caches.match('/offline.html')));return;}
 // Only cache public, fingerprinted build assets and explicit public PWA files.
 const asset=url.pathname.startsWith('/build/assets/')||STATIC.includes(url.pathname);
 if(!asset)return;
 event.respondWith(caches.match(request).then(cached=>cached||fetch(request).then(response=>{if(response.ok&&response.type==='basic'){const copy=response.clone();caches.open(VERSION).then(cache=>cache.put(request,copy));}return response;})));
});
self.addEventListener('push',event=>{let data={title:'Softorio Workspace',body:'You have a new notification.'};try{data={...data,...event.data?.json()};}catch{}event.waitUntil(self.registration.showNotification(data.title,{body:data.body,icon:'/pwa-icon.svg',badge:'/pwa-icon.svg',data:{url:'/notifications'}}));});
self.addEventListener('notificationclick',event=>{event.notification.close();const target=new URL(event.notification.data?.url||'/notifications',self.location.origin).href;event.waitUntil(self.clients.matchAll({type:'window',includeUncontrolled:true}).then(clients=>{const existing=clients.find(c=>c.url===target);return existing?existing.focus():self.clients.openWindow(target);}));});
