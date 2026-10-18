'use strict';
const money=n=>'Rp '+Math.round(n).toLocaleString('id-ID');
const escapeHtml=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const el=id=>document.getElementById(id);
let cart=[],category='';
for(const row of (Array.isArray(oldItems)?oldItems:Object.values(oldItems||{}))){const p=products.find(x=>x.id===Number(row?.product_id)),qty=Number(row?.quantity);if(p&&qty>0)cart.push({id:p.id,qty,notes:String(row.notes||'')})}
const qtyFor=id=>cart.filter(c=>c.id===id).reduce((s,c)=>s+c.qty,0);
const total=()=>cart.reduce((s,c)=>s+products.find(p=>p.id===c.id).price*c.qty,0);
function add(id,delta){const p=products.find(p=>p.id===id);let c=cart.find(c=>c.id===id);if(delta>0&&qtyFor(id)>=Math.min(p.stock,100))return;if(!c){c={id,qty:0,notes:''};cart.push(c)}c.qty+=delta;cart=cart.filter(c=>c.qty>0);render()}
function renderMenu(){el('menu').innerHTML=products.filter(p=>!category||String(p.category_id)===category).map(p=>{const q=qtyFor(p.id),out=p.stock<1;return `<div class="guest-item${out?' out':''}"><div><b>${escapeHtml(p.name)}</b><small>${out?'Habis':money(p.price)}</small></div>${out?'':`<div class="guest-qty">${q?`<button type="button" data-add="${p.id}" data-d="-1" aria-label="Kurangi ${escapeHtml(p.name)}">−</button><b>${q}</b>`:''}<button type="button" class="plus" data-add="${p.id}" data-d="1" aria-label="Tambah ${escapeHtml(p.name)}">+</button></div>`}</div>`}).join('')||'<p class="muted">Menu belum tersedia.</p>'}
function render(){renderMenu();const n=cart.reduce((s,c)=>s+c.qty,0);el('bar').hidden=!n||!el('order-form').hidden;el('open-cart').textContent=`Lihat pesanan (${n}) · ${money(total())}`;
el('cart').innerHTML=cart.map((c,i)=>{const p=products.find(p=>p.id===c.id);return `<div class="guest-item"><div style="flex:1"><b>${c.qty} × ${escapeHtml(p.name)}</b><small>${money(p.price*c.qty)}</small><input class="guest-note" data-note="${i}" maxlength="300" placeholder="Catatan, mis. tidak pedas" value="${escapeHtml(c.notes)}"></div><div class="guest-qty"><button type="button" data-add="${c.id}" data-d="-1" aria-label="Kurangi">−</button><button type="button" class="plus" data-add="${c.id}" data-d="1" aria-label="Tambah">+</button></div></div>`}).join('')||'<p class="muted">Belum ada menu dipilih.</p>';
el('items').innerHTML=cart.map((c,i)=>`<input type="hidden" name="items[${i}][product_id]" value="${c.id}"><input type="hidden" name="items[${i}][quantity]" value="${c.qty}"><input type="hidden" name="items[${i}][notes]" value="${escapeHtml(c.notes)}">`).join('');el('total').textContent=money(total());el('submit').disabled=!cart.length}
function showCart(open){el('order-form').hidden=!open;el('menu').hidden=open;el('cats').hidden=open;render();window.scrollTo(0,0)}
document.addEventListener('click',e=>{const b=e.target.closest('[data-add]');if(b)return add(Number(b.dataset.add),Number(b.dataset.d));const c=e.target.closest('[data-cat]');if(c){category=c.dataset.cat;document.querySelectorAll('[data-cat]').forEach(x=>x.setAttribute('aria-pressed',x===c));renderMenu()}});
el('cart').addEventListener('change',e=>{const n=e.target.closest('[data-note]');if(n){cart[Number(n.dataset.note)].notes=n.value.trim().slice(0,300);render()}});
el('open-cart').onclick=()=>showCart(true);el('back').onclick=()=>showCart(false);
el('order-form').addEventListener('submit',e=>{if(!cart.length)return e.preventDefault();el('submit').disabled=true;el('submit').textContent='Mengirim pesanan…'});
window.addEventListener('pageshow',()=>{el('submit').disabled=!cart.length;el('submit').textContent='Kirim pesanan'});
showCart(cart.length>0&&oldItems&&Object.keys(oldItems).length>0);
