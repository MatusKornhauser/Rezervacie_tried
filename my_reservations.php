<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';

$teacherId = require_login_page();

$stmt = db()->prepare('SELECT full_name, username FROM teachers WHERE id = ?');
$stmt->execute([$teacherId]);
$me = $stmt->fetch();

?><!doctype html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Moje rezervácie</title>
    <style>
        body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:0;background:#f6f7fb;color:#111827}
        .top{background:#fff;border-bottom:1px solid #e6e8f0}
        .top-inner{max-width:1000px;margin:0 auto;padding:14px 16px;display:flex;gap:12px;align-items:center;justify-content:space-between}
        .links a{margin-left:12px;color:#2b6cb0;text-decoration:none;font-weight:600}
        .wrap{max-width:1000px;margin:18px auto;padding:0 16px}
        .card{background:#fff;border:1px solid #e6e8f0;border-radius:12px;padding:14px}
        table{width:100%;border-collapse:separate;border-spacing:0}
        th,td{border-bottom:1px solid #eef0f7;padding:8px 8px;text-align:left;vertical-align:top}
        th{background:#fff}
        button{padding:8px 10px;border-radius:10px;border:1px solid #b42318;background:#b42318;color:#fff;font-weight:700;cursor:pointer}
        .muted{color:#6b7280;font-size:13px}
        .msg{margin-top:10px}
        .msg.err{color:#b00020}
        .msg.ok{color:#0a7a2f}
        .time{font-variant-numeric:tabular-nums}
        .modal-backdrop{position:fixed;inset:0;background:rgba(17,24,39,.55);display:none;align-items:center;justify-content:center;padding:16px;z-index:50}
        .modal{width:min(480px,100%);background:#fff;border:1px solid #e6e8f0;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden}
        .modal-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 14px;border-bottom:1px solid #eef0f7}
        .modal-body{padding:20px 14px;text-align:center}
        .modal-actions{display:flex;gap:10px;justify-content:center;padding:14px;border-top:1px solid #eef0f7;background:#fafbff}
        .btn-secondary{border:1px solid #d6daea;background:#fff;color:#111827}
        .btn-secondary,.btn-primary{padding:10px 16px;border-radius:10px;font-weight:700;cursor:pointer;flex:1;min-width:140px}
        .btn-primary{border:1px solid #2b6cb0;background:#2b6cb0;color:#fff}
        .btn-danger{border:1px solid #b42318;background:#b42318;color:#fff;padding:10px 16px;border-radius:10px;font-weight:700;cursor:pointer;flex:1;min-width:140px}
        .xbtn{border:1px solid #d6daea;background:#fff;border-radius:10px;padding:6px 14px;cursor:pointer;font-size:22px;font-weight:700;color:#6b7280;line-height:1}
        .xbtn:hover{background:#f3f4f6;border-color:#9ca3af;color:#374151}
        .modal-icon{width:64px;height:64px;border-radius:50%;background:#fee2e2;color:#b42318;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:32px;font-weight:700}
        .modal-title{font-size:18px;font-weight:700;margin-bottom:8px}
        .modal-text{color:#6b7280;font-size:15px}
    </style>
</head>
<body>
<div class="top">
    <div class="top-inner">
        <div>
            <strong>Moje rezervácie</strong>
            <div class="muted"><?= htmlspecialchars(($me['full_name'] ?? '') . ' (' . ($me['username'] ?? '') . ')') ?></div>
        </div>
        <div class="links">
            <a href="index.php">Obsadenosť</a>
            <a href="logout.php">Odhlásiť</a>
        </div>
    </div>
</div>

<div class="wrap">
    <div class="card">
        <h2 style="margin:0 0 8px 0">Zoznam</h2>

        <div id="deleteModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-hidden="true">
            <div class="modal">
                <div class="modal-head">
                    <div style="font-weight:800;font-size:16px">Potvrdenie vymazania</div>
                    <button id="deleteModalCloseX" class="xbtn" type="button">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="modal-icon">!</div>
                    <div class="modal-title">Naozaj chceš zrušiť rezerváciu?</div>
                    <div class="modal-text" id="deleteModalText">Táto akcia je nenavrateľná.</div>
                </div>
                <div class="modal-actions">
                    <button id="deleteModalCancel" class="btn-secondary" type="button">Nie, ponechať</button>
                    <button id="deleteModalConfirm" class="btn-danger" type="button">Áno, zrušiť</button>
                </div>
            </div>
        </div>
        <div id="msg" class="msg"></div>
        <div style="overflow:auto;max-height:70vh">
            <table>
                <thead>
                <tr>
                    <th>Dátum</th>
                    <th>Čas</th>
                    <th>Učebňa</th>
                    <th>Poznámka</th>
                    <th></th>
                </tr>
                </thead>
                <tbody id="tbody"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
const tbody = document.getElementById('tbody');
const msg = document.getElementById('msg');

const deleteModal = document.getElementById('deleteModal');
const deleteModalCloseX = document.getElementById('deleteModalCloseX');
const deleteModalCancel = document.getElementById('deleteModalCancel');
const deleteModalConfirm = document.getElementById('deleteModalConfirm');
const deleteModalText = document.getElementById('deleteModalText');
let deleteReservationId = null;

function openDeleteModal(reservation){
  console.log('Opening delete modal for reservation:', reservation);
  deleteReservationId = reservation.id;
  deleteModalText.innerHTML = `<strong>${esc(reservation.classroom_name)}</strong><br>${esc(reservation.date)} ${esc(String(reservation.time_from).slice(0,5))}–${esc(String(reservation.time_to).slice(0,5))}`;
  deleteModal.style.display = 'flex';
  deleteModal.setAttribute('aria-hidden', 'false');
}

function closeDeleteModal(){
  deleteModal.style.display = 'none';
  deleteModal.setAttribute('aria-hidden', 'true');
  deleteReservationId = null;
}

deleteModalCloseX.addEventListener('click', closeDeleteModal);
deleteModalCancel.addEventListener('click', closeDeleteModal);
deleteModalConfirm.addEventListener('click', async () => {
  const idToDelete = deleteReservationId;
  console.log('Deleting reservation ID:', idToDelete);
  if(!idToDelete){
    setMsg('Chyba: Neplatné ID rezervácie', false);
    return;
  }
  closeDeleteModal();
  const del = await fetch('delete_reservation.php?id=' + encodeURIComponent(idToDelete));
  const dd = await del.json();
  if(!dd.ok){
    setMsg(dd.error || 'Chyba pri mazaní', false);
    return;
  }
  setMsg('Rezervácia bola úspešne zrušená.', true);
  await loadMine();
});
deleteModal.addEventListener('click', (e) => {
  if(e.target === deleteModal) closeDeleteModal();
});
document.addEventListener('keydown', (e) => {
  if(e.key === 'Escape' && deleteModal.style.display === 'flex') closeDeleteModal();
});

function esc(s){
  return String(s).replace(/[&<>\"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#39;'}[c]));
}

function setMsg(text, ok){
  msg.textContent = text || '';
  msg.className = 'msg ' + (ok ? 'ok' : 'err');
}

async function loadMine(){
  tbody.innerHTML = '<tr><td colspan="5" class="muted">Načítavam...</td></tr>';

  const res = await fetch('get_my_reservations.php');
  const data = await res.json();
  if(!data.ok) throw new Error(data.error || 'Failed');

  const mine = data.reservations;

  tbody.innerHTML = '';
  if(mine.length === 0){
    tbody.innerHTML = '<tr><td colspan="5" class="muted">Nemáš žiadne rezervácie.</td></tr>';
    return;
  }

  for(const r of mine){
    const tr = document.createElement('tr');
    const d = document.createElement('td');
    d.textContent = r.date;
    const t = document.createElement('td');
    t.innerHTML = '<span class="time">' + esc(String(r.time_from).slice(0,5)) + '–' + esc(String(r.time_to).slice(0,5)) + '</span>';
    const c = document.createElement('td');
    c.textContent = r.classroom_name;
    const n = document.createElement('td');
    n.textContent = r.note || '';
    const a = document.createElement('td');
    const btn = document.createElement('button');
    btn.textContent = 'Zrušiť';
    btn.addEventListener('click', () => {
      openDeleteModal(r);
    });
    a.appendChild(btn);

    tr.appendChild(d);
    tr.appendChild(t);
    tr.appendChild(c);
    tr.appendChild(n);
    tr.appendChild(a);
    tbody.appendChild(tr);
  }
}
loadMine().catch(e => setMsg(e.message || 'Chyba', false));
</script>
</body>
</html>
