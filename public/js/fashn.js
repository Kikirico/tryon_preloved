const $ = id => document.getElementById(id);
const form = $('fitting-form'), token = form.querySelector('[name=_token]').value;
let photo, stream, previewUrl, currentRun, timer, busy = false, ready = false, cameraGeneration = 0;
const status = text => { $('fitting-status').textContent = text; };
function update() {
  let reason = '';
  if (busy) {
    reason = 'Tunggu proses foto saat ini selesai.';
  } else if (!ready) {
    reason = 'Layanan AI belum siap. Tombol akan aktif setelah AI siap dan formulir lengkap.';
  } else if (!$('fitting-product').value) {
    reason = 'Pilih pakaian yang ingin dicoba terlebih dahulu.';
  } else if (!photo) {
    reason = 'Unggah foto atau ambil foto dengan kamera terlebih dahulu.';
  } else if (!form.querySelector('[name=consent]').checked) {
    reason = 'Centang persetujuan pemrosesan foto untuk melanjutkan.';
  }
  $('fitting-submit').disabled = Boolean(reason);
  $('fitting-submit-help').textContent = reason || 'Foto siap diproses. Klik tombol di atas untuk memulai.';
}
form.querySelector('[name=consent]').addEventListener('change', update);
async function api(url, options = {}) {
  const res = await fetch(url, { ...options, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token, ...options.headers } });
  if (!res.ok) { const data = await res.json().catch(() => ({})); throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Permintaan gagal. Muat ulang halaman lalu coba lagi.'); }
  return res.status === 204 ? null : res.json();
}
function stopCamera() {
  cameraGeneration++; stream?.getTracks().forEach(track => track.stop()); stream = null;
  $('fitting-video').srcObject = null; $('fitting-video').hidden = true;
  $('fitting-capture').hidden = $('fitting-stop').hidden = true; $('fitting-camera').disabled = false;
}
function setPhoto(file) {
  if (file.size > 5 * 1024 * 1024) { status('Ukuran foto maksimal 5 MB.'); return; }
  if (!['image/jpeg','image/png','image/webp'].includes(file.type)) { status('Gunakan JPG, PNG, atau WebP.'); return; }
  photo = file; if (previewUrl) URL.revokeObjectURL(previewUrl);
  previewUrl = URL.createObjectURL(file); $('fitting-preview').src = previewUrl; $('fitting-preview').hidden = false; update();
}
$('fitting-file').addEventListener('change', e => { if(e.target.files[0]) { stopCamera(); setPhoto(e.target.files[0]); } });
$('fitting-product').addEventListener('change', () => {
  const url = $('fitting-product').selectedOptions[0]?.dataset.image;
  $('fitting-garment').hidden = !url; if (url) $('fitting-garment').src = url; update();
});
$('fitting-product').dispatchEvent(new Event('change'));
$('fitting-camera').addEventListener('click', async () => {
  const generation = ++cameraGeneration; $('fitting-camera').disabled = true;
  try {
    if (!navigator.mediaDevices?.getUserMedia) throw new Error('Kamera memerlukan HTTPS atau localhost.');
    const media = await navigator.mediaDevices.getUserMedia({video:{facingMode:'user',width:{ideal:1280},height:{ideal:960}},audio:false});
    if (generation !== cameraGeneration) { media.getTracks().forEach(t => t.stop()); return; }
    stream = media; $('fitting-video').srcObject = media; $('fitting-video').hidden = false;
    await $('fitting-video').play(); $('fitting-capture').hidden = $('fitting-stop').hidden = false;
  } catch (e) { stopCamera(); status(e.name === 'NotAllowedError' ? 'Izin kamera ditolak. Izinkan kamera atau unggah foto.' : e.message); }
});
$('fitting-stop').addEventListener('click', stopCamera);
$('fitting-capture').addEventListener('click', () => {
  const video = $('fitting-video'); if (!video.videoWidth) return;
  const canvas = document.createElement('canvas'); canvas.width = video.videoWidth; canvas.height = video.videoHeight;
  canvas.getContext('2d').drawImage(video,0,0); canvas.toBlob(blob => { if(blob) setPhoto(new File([blob],'camera.jpg',{type:'image/jpeg'})); },'image/jpeg',.9); stopCamera();
});
async function health() {
  try { ready = (await api('/ai-fitting/health')).ready; } catch { ready = false; }
  $('ai-health').textContent = ready ? '● AI siap' : '○ AI belum aktif'; update();
}
const photoDialog = $('fitting-photo-dialog');
const largePhoto = $('fitting-photo-large');
for (const button of document.querySelectorAll('.fitting-photo')) {
  button.addEventListener('click', () => {
    const image = button.querySelector('img');
    const src = image.getAttribute('src');
    if (!src || photoDialog.open) return;
    $('fitting-photo-title').textContent = button.dataset.title;
    largePhoto.alt = image.alt;
    largePhoto.src = src;
    photoDialog.showModal();
  });
}
photoDialog.addEventListener('click', event => { if (event.target === photoDialog) photoDialog.close(); });
photoDialog.addEventListener('close', () => largePhoto.removeAttribute('src'));
async function poll(id) {
  try {
    const run = await api(`/ai-fitting/runs/${id}`); currentRun = id;
    busy = ['queued','processing'].includes(run.status); update();
    $('fitting-delete').hidden = busy;
    if (run.status === 'completed') {
      status('Foto try-on selesai. Klik foto untuk memperbesar.');
      $('fitting-before').src = run.before; $('fitting-after').src = run.result;
      $('fitting-comparison').hidden = false; $('fitting-download').href = run.result; $('fitting-download').hidden = false;
    } else if (run.status === 'failed') status(run.error);
    else { status(run.status === 'queued' ? 'Foto masuk antrean. Menunggu giliran AI…' : 'AI sedang memproses pakaian pada fotomu…'); timer = setTimeout(() => poll(id),3000); }
  } catch (e) { busy = false; update(); status(e.message + ' Muat ulang untuk memeriksa status kembali.'); }
}
form.addEventListener('submit', async e => {
  e.preventDefault(); if (!photo || busy) return;
  busy = true; update(); stopCamera(); clearTimeout(timer);
  $('fitting-comparison').hidden = $('fitting-download').hidden = $('fitting-delete').hidden = true;
  status('Mengirim foto ke layanan AI toko…');
  const data = new FormData(form); data.append('person',photo);
  try { const run = await api('/ai-fitting',{method:'POST',body:data}); await poll(run.id); }
  catch(e) { busy = false; update(); status(e.message); }
});
$('fitting-delete').addEventListener('click', async () => {
  if (!currentRun || busy) return;
  try { await api(`/ai-fitting/runs/${currentRun}`,{method:'DELETE'}); currentRun = null; $('fitting-comparison').hidden = $('fitting-download').hidden = $('fitting-delete').hidden = true; status('Foto dan hasil telah dihapus dari server.'); }
  catch(e) { status(e.message); }
});
document.addEventListener('visibilitychange', () => { if(document.hidden) stopCamera(); });
window.addEventListener('pagehide', () => { stopCamera(); clearTimeout(timer); if(previewUrl) URL.revokeObjectURL(previewUrl); });
health(); const healthTimer = setInterval(health,15000);
window.addEventListener('pagehide', () => clearInterval(healthTimer));
if (form.dataset.latest) { busy = true; poll(form.dataset.latest); }
