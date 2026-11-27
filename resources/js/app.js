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
function sync() { if (!shell) return; const open = mobile.matches ? shell.classList.contains('mobile-open') : !shell.classList.contains('collapsed'); toggle?.setAttribute('aria-expanded', String(open)); if(sidebar) sidebar.inert = mobile.matches && !open; const backdrop = document.querySelector('[data-sidebar-close]'); if(backdrop) backdrop.hidden = !mobile.matches || !open; }
if(shell && read('softorio-sidebar') === 'collapsed') shell.classList.add('collapsed');
sync();
toggle?.addEventListener('click', () => { if(mobile.matches) shell.classList.toggle('mobile-open'); else { shell.classList.toggle('collapsed'); write('softorio-sidebar',shell.classList.contains('collapsed')?'collapsed':'expanded'); } sync(); });
function closeNav(){shell?.classList.remove('mobile-open');sync();}
document.querySelector('[data-sidebar-close]')?.addEventListener('click',()=>{closeNav();toggle?.focus();});
mobile.addEventListener('change',()=>{closeNav();sync();});
const dialog=document.getElementById('search-dialog');
function openSearch(){if(dialog&&!dialog.open){dialog.showModal();document.getElementById('quick-search')?.focus();}}
document.querySelector('[data-search-open]')?.addEventListener('click',openSearch);
document.querySelector('[data-search-close]')?.addEventListener('click',()=>dialog?.close());
document.addEventListener('keydown',event=>{if((event.ctrlKey||event.metaKey)&&event.key.toLowerCase()==='k'&&dialog){event.preventDefault();openSearch();}if(event.key==='Escape'&&shell?.classList.contains('mobile-open')){closeNav();toggle?.focus();}});
