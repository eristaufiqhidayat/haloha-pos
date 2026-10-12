'use strict';
(()=>{
const COLORS=['#806021','#2f6f8f','#9a3f5a','#3f7d4e','#6b4fa0','#b0632a','#2d7f7a','#8a6d1f','#4d5fb0','#a14444','#5a7a2a','#7a4a8c','#2c6b5a','#9c5a1e','#3c5a7a'];
const TOKEN_LIMIT=12,d=splitData,get=id=>document.getElementById(id),rupiah=n=>'Rp '+Math.round(n).toLocaleString('id-ID');
const esc=s=>String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const state={mode:get('split-mode').value==='equal'?'equal':'item',count:Math.min(d.max,Math.max(2,d.count)),active:1,labels:{}};
for(const [n,l] of Object.entries(d.labels||{}))state.labels[n]=l||'';
// alloc[i][bill]=qty per baris menu
const alloc=d.items.map(it=>{const a={};for(const [n,q] of Object.entries(it.alloc||{}))a[n]=q;return a;});
const itemName=it=>it.name+(d.mixed&&it.takeaway?' [TA]':'');
const assigned=i=>Object.values(alloc[i]).reduce((s,q)=>s+q,0);
function allocate(total,weights){const sum=weights.reduce((a,b)=>a+b,0);if(sum<=0)return weights.map(()=>0);const base=weights.map(w=>Math.floor(total*w/sum));let rest=total-base.reduce((a,b)=>a+b,0);weights.map((w,i)=>[(total*w)%sum,i]).sort((a,b)=>b[0]-a[0]||a[1]-b[1]).forEach(([,i])=>{if(rest>0){base[i]++;rest--;}});return base;}
function bills(){
  const n=state.count,list=Array.from({length:n},(_,k)=>({no:k+1,lines:[],units:0,subtotal:0}));
  if(state.mode==='item')d.items.forEach((it,i)=>{for(const [b,q] of Object.entries(alloc[i])){if(!q||b>n)continue;const bill=list[b-1];bill.lines.push(q+' × '+itemName(it));bill.units+=q;bill.subtotal+=q*it.price;}});
  if(state.mode==='item'){const sub=list.map(b=>b.subtotal),disc=allocate(d.discount,sub),tax=allocate(d.tax,sub.map((s,k)=>s-disc[k]));list.forEach((b,k)=>{b.discount=disc[k];b.tax=tax[k];b.total=sub[k]-disc[k]+tax[k];});}
  else{const eq=list.map(()=>1),tot=allocate(d.total,eq),disc=allocate(d.discount,eq),tax=allocate(d.tax,eq);list.forEach((b,k)=>{b.total=tot[k];b.discount=disc[k];b.tax=tax[k];b.subtotal=tot[k]+disc[k]-tax[k];});}
  return list;
}
function move(i,bill,delta){const cur=alloc[i][bill]||0,free=d.items[i].qty-assigned(i);const next=Math.max(0,cur+Math.min(delta,free));alloc[i][bill]=next;}
function unitsHtml(it,i){
  if(it.qty>TOKEN_LIMIT){const parts=Object.entries(alloc[i]).filter(([b,q])=>q&&b<=state.count).map(([b,q])=>`<span class="chip small" style="background:${COLORS[b-1]}">T${b}: ${q}</span>`).join(' ');
    return `${parts}<button type="button" data-act="minus" data-i="${i}">−1 dari T${state.active}</button><button type="button" data-act="plus" data-i="${i}">+1 ke T${state.active}</button>`;}
  const tokens=[];for(const [b,q] of Object.entries(alloc[i]))for(let k=0;k<q;k++)if(b<=state.count)tokens.push(+b);
  while(tokens.length<it.qty)tokens.push(0);
  return tokens.map(b=>`<button type="button" class="unit${b?' set':''}" style="${b?`background:${COLORS[b-1]};border-color:${COLORS[b-1]}`:''}" data-act="unit" data-i="${i}" data-b="${b}" aria-label="${esc(it.name)}: ${b?'Tagihan '+b:'belum dibagi'}">${b?'T'+b:'–'}</button>`).join('');
}
function render(){
  for(const i of alloc.keys())for(const b of Object.keys(alloc[i]))if(b>state.count)delete alloc[i][b];
  if(state.active>state.count)state.active=state.count;
  get('split-count').textContent=state.count;get('split-count-input').value=state.count;get('split-mode').value=state.mode;
  get('split-dec').disabled=state.count<=2;get('split-inc').disabled=state.count>=d.max;
  get('mode-item').setAttribute('aria-pressed',state.mode==='item');get('mode-equal').setAttribute('aria-pressed',state.mode==='equal');
  get('item-mode').hidden=state.mode!=='item';get('equal-mode').hidden=state.mode!=='equal';
  get('split-chips').innerHTML=Array.from({length:state.count},(_,k)=>`<button type="button" class="chip" style="background:${COLORS[k]}" aria-pressed="${state.active===k+1}" data-act="active" data-b="${k+1}">T${k+1}${state.labels[k+1]?' · '+esc(state.labels[k+1]):''}</button>`).join('');
  get('split-items').innerHTML=d.items.map((it,i)=>{const free=it.qty-assigned(i);return `<div class="split-item"><div class="sectiontitle"><div><b>${it.qty} × ${esc(itemName(it))}</b>${it.notes?`<p class="muted item-notes">${esc(it.notes)}</p>`:''}</div><span class="muted">${rupiah(it.price)} / porsi</span></div><div class="units">${unitsHtml(it,i)}${free?`<button type="button" data-act="rest" data-i="${i}">Sisa ${free} ke T${state.active}</button>`:''}</div></div>`;}).join('');
  const list=bills(),free=d.items.reduce((s,it,i)=>s+it.qty-assigned(i),0),empty=state.mode==='item'?list.filter(b=>!b.units).map(b=>'T'+b.no):[];
  let msg;if(state.mode==='item'&&free)msg=`<div class="alert error">${free} porsi belum masuk tagihan mana pun.</div>`;else if(empty.length)msg=`<div class="alert error">${empty.join(', ')} masih kosong. Isi atau kurangi jumlah tagihan.</div>`;else msg=`<div class="alert">Semua porsi terbagi. Jumlah ${state.count} tagihan = ${rupiah(list.reduce((s,b)=>s+b.total,0))}.</div>`;
  get('split-status').innerHTML=msg;get('split-save').disabled=state.mode==='item'&&(free>0||empty.length>0);
  get('split-bills').innerHTML=list.map(b=>`<div class="bill${state.mode==='item'&&state.active===b.no?' active':''}"><div class="sectiontitle"><span class="bill-name"><span class="dot" style="background:${COLORS[b.no-1]}"></span><b>Tagihan ${b.no}</b></span><b>${rupiah(b.total)}</b></div>
    <input type="text" maxlength="40" placeholder="Nama tamu (opsional)" value="${esc(state.labels[b.no])}" data-label="${b.no}" aria-label="Nama untuk tagihan ${b.no}">
    ${state.mode==='equal'?`<span class="muted">Bagian ${b.no} dari ${state.count}</span>`:(b.lines.length?`<ul>${b.lines.map(l=>`<li>${esc(l)}</li>`).join('')}</ul>`:'<span class="muted">Belum ada item.</span>')}
    ${d.discount||d.tax?`<span class="muted">Subtotal ${rupiah(b.subtotal)}${d.discount?' · Diskon −'+rupiah(b.discount):''}${d.tax?' · Pajak '+rupiah(b.tax):''}</span>`:''}</div>`).join('');
}
document.addEventListener('click',e=>{const t=e.target.closest('button[data-act],#split-inc,#split-dec,#mode-item,#mode-equal');if(!t)return;const i=+t.dataset.i;
  switch(t.dataset.act||t.id){
    case 'active':state.active=+t.dataset.b;break;
    case 'unit':{const b=+t.dataset.b;if(b){alloc[i][b]--;if(b!==state.active)move(i,state.active,1);}else move(i,state.active,1);break;}
    case 'plus':move(i,state.active,1);break;
    case 'minus':alloc[i][state.active]=Math.max(0,(alloc[i][state.active]||0)-1);break;
    case 'rest':move(i,state.active,d.items[i].qty);break;
    case 'split-inc':state.count=Math.min(d.max,state.count+1);break;
    case 'split-dec':state.count=Math.max(2,state.count-1);break;
    case 'mode-item':state.mode='item';break;
    case 'mode-equal':state.mode='equal';break;
    default:return;}
  render();});
document.addEventListener('change',e=>{const n=e.target.dataset?.label;if(n){state.labels[n]=e.target.value.trim();render();}});
get('split-form').addEventListener('submit',e=>{
  const box=get('split-hidden'),add=(name,value)=>{const el=document.createElement('input');el.type='hidden';el.name=name;el.value=value;box.appendChild(el);};
  box.innerHTML='';render();if(get('split-save').disabled){e.preventDefault();return;}
  for(let n=1;n<=state.count;n++)add(`labels[${n}]`,state.labels[n]||'');
  if(state.mode==='item')d.items.forEach((it,i)=>{for(const [b,q] of Object.entries(alloc[i]))if(q)add(`alloc[${it.key}][${b}]`,q);});
  get('split-save').disabled=true;get('split-save').textContent='Menyimpan…';});
window.addEventListener('pageshow',()=>{get('split-save').textContent='Simpan pembagian';render();});
render();
})();
