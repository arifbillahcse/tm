// UI-only preferences; no private workspace records are stored in the browser.
const root = document.documentElement;
const media = window.matchMedia('(prefers-color-scheme: dark)');
function read(key) { try { return localStorage.getItem(key); } catch { return null; } }
function write(key, value) { try { localStorage.setItem(key, value); } catch {} }
function theme(value) { root.dataset.theme = value; document.querySelectorAll('[data-theme-toggle]').forEach(b => { b.setAttribute('aria-label', value === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'); }); }
theme(read('softorio-theme') === 'dark' ? 'dark' : read('softorio-theme') === 'light' ? 'light' : media.matches ? 'dark' : 'light');
document.querySelectorAll('[data-theme-toggle]').forEach(b => b.addEventListener('click', () => { const next = root.dataset.theme === 'dark' ? 'light' : 'dark'; theme(next); write('softorio-theme', next); }));
const shell = document.querySelector('[data-shell]');
const toggle = document.querySelector('[data-sidebar-toggle]');
const mobile = window.matchMedia('(max-width: 760px)');
const sidebar = document.getElementById('sidebar');
shell?.classList.add('js-ready');
function sync() { if (!shell) return; const open = mobile.matches ? shell.classList.contains('mobile-open') : !shell.classList.contains('collapsed'); toggle?.setAttribute('aria-expanded', String(open)); if(sidebar) sidebar.inert = mobile.matches && !open; const body = document.querySelector('.app-body'); if(body) body.inert = mobile.matches && open; const backdrop = document.querySelector('[data-sidebar-close]'); if(backdrop) backdrop.hidden = !mobile.matches || !open; }
if(shell && read('softorio-sidebar') === 'collapsed') shell.classList.add('collapsed');
sync();
toggle?.addEventListener('click', () => { if(mobile.matches) shell.classList.toggle('mobile-open'); else { shell.classList.toggle('collapsed'); write('softorio-sidebar',shell.classList.contains('collapsed')?'collapsed':'expanded'); } sync(); if(mobile.matches && shell.classList.contains('mobile-open')) sidebar?.querySelector('a')?.focus(); });
function closeNav(){shell?.classList.remove('mobile-open');sync();}
document.querySelector('[data-sidebar-close]')?.addEventListener('click',()=>{closeNav();toggle?.focus();});
mobile.addEventListener('change',()=>{closeNav();sync();});
const dialog=document.getElementById('search-dialog');
function openSearch(){if(dialog&&!dialog.open){dialog.showModal();document.getElementById('quick-search')?.focus();}}
document.querySelector('[data-search-open]')?.addEventListener('click',openSearch);
document.querySelector('[data-search-close]')?.addEventListener('click',()=>dialog?.close());
document.addEventListener('keydown',event=>{if((event.ctrlKey||event.metaKey)&&event.key.toLowerCase()==='k'&&dialog){event.preventDefault();openSearch();}if(event.key==='Escape'&&shell?.classList.contains('mobile-open')&&!dialog?.open){closeNav();toggle?.focus();}});

// Calendar drag/drop is a progressive enhancement; the editor is the keyboard alternative.
let draggedEvent=null;
document.querySelectorAll('[data-calendar-move]').forEach(el=>{el.addEventListener('dragstart',e=>{draggedEvent=el.dataset.calendarMove;e.dataTransfer?.setData('text/plain','calendar-event');});el.addEventListener('dragend',()=>{draggedEvent=null;document.querySelectorAll('.calendar-drop-active').forEach(d=>d.classList.remove('calendar-drop-active'));});});
document.querySelectorAll('[data-calendar-date]').forEach(day=>{day.addEventListener('dragover',e=>{if(draggedEvent){e.preventDefault();day.classList.add('calendar-drop-active');}});day.addEventListener('dragleave',()=>day.classList.remove('calendar-drop-active'));day.addEventListener('drop',e=>{if(!draggedEvent)return;e.preventDefault();const form=document.getElementById('calendar-move-form');if(!form)return;const target=new URL(draggedEvent,location.href);if(target.origin!==location.origin)return;form.action=target.href;form.elements.date.value=day.dataset.calendarDate;form.requestSubmit();});});
document.querySelector('[data-template-add]')?.addEventListener('click',()=>{const container=document.getElementById('template-blocks');const blocks=container?.querySelectorAll('.template-block');if(!blocks?.length||blocks.length>=12)return;const clone=blocks[0].cloneNode(true);clone.querySelectorAll('[name]').forEach(el=>{el.name=el.name.replace(/blocks\[\d+\]/,'blocks['+blocks.length+']');if(el.name.endsWith('[title]'))el.value='';});const remove=document.createElement('button');remove.type='button';remove.className='btn secondary';remove.textContent='Remove block';remove.addEventListener('click',()=>{clone.remove();container.querySelectorAll('.template-block').forEach((b,i)=>b.querySelectorAll('[name]').forEach(el=>el.name=el.name.replace(/blocks\[\d+\]/,'blocks['+i+']')));});clone.append(remove);container.append(clone);clone.querySelector('input')?.focus();});

const focusMode=document.querySelector('[data-focus-mode]');focusMode?.addEventListener('change',()=>{const minutes=document.querySelector('[data-focus-minutes]');if(minutes)minutes.value=({pomodoro:25,deep:90,custom:45,short_break:5,long_break:15})[focusMode.value]??25;});
const focusClock=document.querySelector('[data-focus-elapsed]');if(focusClock){const base=Number(focusClock.dataset.focusElapsed),target=Number(focusClock.dataset.focusTarget),running=focusClock.dataset.focusRunning==='1',loaded=Date.now();const format=s=>[Math.floor(s/3600),Math.floor(s/60)%60,s%60].map(v=>String(v).padStart(2,'0')).join(':');const tick=()=>{const elapsed=base+(running?Math.max(0,Math.floor((Date.now()-loaded)/1000)):0);focusClock.textContent=format(Math.max(0,target-elapsed));const actual=document.querySelector('[data-focus-actual]');if(actual)actual.textContent=format(elapsed);const notice=document.querySelector('[data-focus-notice]');if(notice&&elapsed>=target)notice.textContent='Target reached. Stop or continue recording actual time.';};tick();if(running)setInterval(tick,1000);}

// Progressive web app: cache only public static assets; never persist authenticated pages or data.
if ('serviceWorker' in navigator && window.isSecureContext) {
  window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js', {scope: '/' }).catch(() => {}), {once:true});
}
let installPrompt=null;
const installButton=document.querySelector('[data-pwa-install]');
window.addEventListener('beforeinstallprompt',event=>{event.preventDefault();installPrompt=event;if(installButton)installButton.hidden=false;});
installButton?.addEventListener('click',async()=>{if(!installPrompt)return;installPrompt.prompt();await installPrompt.userChoice;installPrompt=null;installButton.hidden=true;});
window.addEventListener('appinstalled',()=>{installPrompt=null;if(installButton)installButton.hidden=true;});

const pushButton=document.querySelector('[data-push-subscribe]'),pushStatus=document.querySelector('[data-push-status]');
function decodeVapidKey(value){const padded=value.replace(/-/g,'+').replace(/_/g,'/');const raw=atob(padded+'='.repeat((4-padded.length%4)%4));return Uint8Array.from(raw,char=>char.charCodeAt(0));}
pushButton?.addEventListener('click',async()=>{pushButton.disabled=true;if(pushStatus)pushStatus.textContent='Requesting notification permission…';try{if(!('serviceWorker'in navigator)||!('PushManager'in window)||!('Notification'in window))throw new Error('This browser does not support push notifications.');const permission=await Notification.requestPermission();if(permission!=='granted')throw new Error('Notification permission was not granted.');const registration=await navigator.serviceWorker.ready;let subscription=await registration.pushManager.getSubscription();if(!subscription)subscription=await registration.pushManager.subscribe({userVisibleOnly:true,applicationServerKey:decodeVapidKey(pushButton.dataset.publicKey)});const response=await fetch(pushButton.dataset.subscribeUrl,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':pushButton.dataset.csrf},body:JSON.stringify({...subscription.toJSON(),contentEncoding:'aes128gcm'})});if(!response.ok)throw new Error('Could not save this device subscription. Please refresh and try again.');if(pushStatus)pushStatus.textContent='This device is ready to receive workspace notifications.';}catch(error){if(pushStatus)pushStatus.textContent=error instanceof Error?error.message:'Push setup failed.';}finally{pushButton.disabled=false;}});
