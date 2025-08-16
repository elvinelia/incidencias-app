// Helpers
const $ = (s, p=document)=>p.querySelector(s);
const $$ = (s, p=document)=>Array.from(p.querySelectorAll(s));

function openModal(el){ el.removeAttribute('hidden'); document.body.classList.add('modal-open'); }
function closeModal(el){ el.setAttribute('hidden',''); document.body.classList.remove('modal-open'); }
document.addEventListener('click', e=>{
  if (e.target.matches('[data-close]') || e.target.classList.contains('modal')) {
    const m = e.target.closest('.modal'); if (m) closeModal(m);
  }
});

// Geolocalización
const btnGeo = $('#btnGeo');
if (btnGeo) {
  btnGeo.addEventListener('click', ()=>{
    if (!navigator.geolocation) return alert('Geolocalización no disponible');
    navigator.geolocation.getCurrentPosition(pos=>{
      $('#latitud').value = pos.coords.latitude.toFixed(6);
      $('#longitud').value = pos.coords.longitude.toFixed(6);
      alert('Ubicación establecida.');
    }, ()=> alert('No se pudo obtener ubicación'));
  });
}

// Selects dependientes
const provSel = $('#provincia');
const munSel = $('#municipio');
const barSel = $('#barrio');
async function loadMunicipios(pid){
  munSel.innerHTML = '<option value="">Cargando...</option>';
  munSel.disabled = true; barSel.disabled = true; barSel.innerHTML = '<option value="">Seleccione...</option>';
  const r = await fetch(`api/municipios.php?provincia_id=${pid}`); const data = await r.json();
  munSel.innerHTML = '<option value="">Seleccione...</option>' + data.map(m=>`<option value="${m.id}">${m.nombre}</option>`).join('');
  munSel.disabled = false;
}
async function loadBarrios(mid){
  barSel.innerHTML = '<option value="">Cargando...</option>'; barSel.disabled = true;
  const r = await fetch(`api/barrios.php?municipio_id=${mid}`); const data = await r.json();
  barSel.innerHTML = '<option value="">Seleccione...</option>' + data.map(b=>`<option value="${b.id}">${b.nombre}</option>`).join('');
  barSel.disabled = false;
}
if (provSel) provSel.addEventListener('change', e=>{ if(e.target.value) loadMunicipios(e.target.value) });
if (munSel) munSel.addEventListener('change', e=>{ if(e.target.value) loadBarrios(e.target.value) });

// Modal detalle
async function cargarDetalle(id){
  const r = await fetch(`api/incidencia_detalle.php?id=${id}`); 
  if(!r.ok) return alert('Error al cargar detalle');
  const data = await r.json();
  const i = data.incidencia;
  const cont = $('#detalleBody');
  $('#c_incidencia_id').value = i.id_incidencia;
  cont.innerHTML = `
    <h3>${i.titulo} ${i.validado ? '✅' : '⏳'}</h3>
    <p><b>Fecha:</b> ${i.fecha_ocurrida}</p>
    <p><b>Ubicación:</b> ${i.prov} / ${i.mun} / ${i.bar}</p>
    <p><b>Coordenadas:</b> ${i.latitud ?? '-'}, ${i.longitud ?? '-'}</p>
    <p><b>Muertos:</b> ${i.muertos} — <b>Heridos:</b> ${i.heridos} — <b>Pérdida RD$:</b> ${parseFloat(i.perdida_rd||0).toLocaleString()}</p>
    <p><b>Tipos:</b> ${(i.tipos||[]).join(', ')}</p>
    ${i.link_red ? `<p><a href="${i.link_red}" target="_blank">Enlace de referencia</a></p>` : ''}
    ${i.foto_url ? `<img src="${i.foto_url}" class="img-cover">` : ''}
  `;
  // comentarios
  const comDiv = $('#comentarios');
  if (comDiv) {
    comDiv.innerHTML = (data.comentarios||[]).map(c=>`
      <div class="comment"><b>${c.nombre}</b> <span class="muted">${c.creado_en}</span><p>${c.texto}</p></div>
    `).join('') || '<p class="muted">Sin comentarios aún.</p>';
  }
}
$$('.ver-btn').forEach(b=>{
  b.addEventListener('click', async ()=>{
    await cargarDetalle(b.dataset.id);
    openModal($('#modalDetalle'));
  });
});

// Comentar
const formComentario = $('#formComentario');
if (formComentario) formComentario.addEventListener('submit', async e=>{
  e.preventDefault();
  const fd = new FormData(formComentario);
  const r = await fetch('api/comentar.php', { method:'POST', body: fd });
  if (r.ok) {
    await cargarDetalle($('#c_incidencia_id').value);
    formComentario.reset();
  } else alert('Error al comentar');
});

// Sugerir
$$('.sugerir-btn').forEach(b=>{
  b.addEventListener('click', ()=>{
    $('#s_incidencia_id').value = b.dataset.id;
    openModal($('#modalSugerir'));
  });
});
const formSugerencia = $('#formSugerencia');
if (formSugerencia) formSugerencia.addEventListener('submit', async e=>{
  e.preventDefault();
  const fd = new FormData(formSugerencia);
  const r = await fetch('api/corregir.php', { method:'POST', body: fd });
  if (r.ok) { alert('Sugerencia enviada'); formSugerencia.reset(); closeModal($('#modalSugerir')); }
  else alert('Error al enviar sugerencia');
});

// Validar
$$('.validar-btn').forEach(b=>{
  b.addEventListener('click', async ()=>{
    if(!confirm('¿Marcar como validada?')) return;
    const fd = new FormData(); fd.append('id', b.dataset.id);
    const r = await fetch('api/validar.php', { method:'POST', body: fd });
    if (r.ok) location.reload(); else alert('Error al validar');
  });
});

// Fusión (super.php)
const modalFusion = $('#modalFusion');
$$('.fusionar-btn').forEach(b=>{
  b.addEventListener('click', ()=>{
    $('#principal_id').value = b.dataset.id;
    openModal(modalFusion);
  });
});
const formFusion = $('#formFusion');
if (formFusion) formFusion.addEventListener('submit', async e=>{
  e.preventDefault();
  const fd = new FormData(formFusion);
  const r = await fetch('api/fusionar.php', { method:'POST', body: fd });
  if (r.ok) { alert('Fusionado'); location.reload(); } else alert('Error al fusionar');
});
