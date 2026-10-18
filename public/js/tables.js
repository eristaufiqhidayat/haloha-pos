'use strict';
// Baca foto QRIS di browser lalu kirim isi kodenya; server memeriksa ulang format dan CRC.
(()=>{const file=document.getElementById('qris-file');if(!file)return;const status=document.getElementById('qris-status'),payload=document.getElementById('qris-payload');
file.addEventListener('change',()=>{payload.value='';const f=file.files[0];if(!f)return;status.textContent='Membaca QRIS…';const img=new Image();img.onload=()=>{
for(const max of [1600,1000,700]){const scale=Math.min(1,max/Math.max(img.width,img.height)),c=document.createElement('canvas');c.width=Math.round(img.width*scale);c.height=Math.round(img.height*scale);const ctx=c.getContext('2d');ctx.drawImage(img,0,0,c.width,c.height);const d=ctx.getImageData(0,0,c.width,c.height),r=window.jsQR&&jsQR(d.data,c.width,c.height);
if(r&&r.data){if(!/^000201/.test(r.data)||!/ID\.CO\.QRIS/i.test(r.data)){status.textContent='QR terbaca, tapi bukan QRIS. Pilih foto QRIS resto.';return}payload.value=r.data;status.textContent='QRIS terbaca. Tekan Simpan.';return}}
status.textContent='QRIS tidak terbaca. Foto ulang lebih dekat dan terang, lalu pilih lagi.'};img.onerror=()=>status.textContent='File bukan gambar.';img.src=URL.createObjectURL(f)})})();
