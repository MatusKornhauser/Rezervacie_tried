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
    <title>Rezervácie učební</title>
    <style>
        *,*::before,*::after{box-sizing:border-box}
        body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:0;background:#f6f7fb;color:#111827}
        .top{background:#fff;border-bottom:1px solid #e6e8f0}
        .top-inner{max-width:1200px;margin:0 auto;padding:14px 16px;display:flex;gap:12px;align-items:center;justify-content:space-between}
        .links a{margin-left:12px;color:#2b6cb0;text-decoration:none;font-weight:600}
        .wrap{max-width:1200px;margin:18px auto;padding:0 16px}
        .grid{display:grid;grid-template-columns:340px 1fr;gap:16px}
        .card{background:#fff;border:1px solid #e6e8f0;border-radius:12px;padding:14px;min-width:0}
        label{display:block;font-weight:600;margin-top:10px}
        input,select,textarea{width:100%;padding:10px;border:1px solid #d6daea;border-radius:10px;margin-top:6px;font:inherit}
        textarea{min-height:80px;resize:vertical}
        button{margin-top:12px;padding:10px 12px;border-radius:10px;border:1px solid #2b6cb0;background:#2b6cb0;color:#fff;font-weight:700;cursor:pointer}
        .msg{margin-top:10px}
        .msg.err{color:#b00020}
        .msg.ok{color:#0a7a2f}
        table{width:100%;border-collapse:separate;border-spacing:0}
        th,td{border-bottom:1px solid #eef0f7;padding:8px 8px;text-align:left;vertical-align:top}
        th{position:sticky;top:0;background:#fff;z-index:1}
        .pill{display:inline-block;padding:2px 8px;border-radius:999px;border:1px solid #d6daea;background:#f9fafb;font-size:12px}
        .row{display:flex;gap:10px}
        .row > div{flex:1;min-width:0}
        .muted{color:#6b7280;font-size:13px}
        .time{font-variant-numeric:tabular-nums}
        .modal-backdrop{position:fixed;inset:0;background:rgba(17,24,39,.55);display:none;align-items:center;justify-content:center;padding:16px;z-index:50}
        .modal{width:min(560px,100%);background:#fff;border:1px solid #e6e8f0;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden}
        .modal-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 14px;border-bottom:1px solid #eef0f7}
        .modal-body{padding:14px}
        /* .modal-actions{display:flex;gap:10px;justify-content:flex-end;padding:14px;border-top:1px solid #eef0f7;background:#fafbff} */
        /* .btn-secondary{border:1px solid #d6daea;background:#fff;color:#111827}
        .btn-secondary,.btn-primary{padding:8px 14px;border-radius:10px;font-weight:700;cursor:pointer;flex:0 0 auto}
        .btn-primary{border:1px solid #2b6cb0;background:#2b6cb0;color:#fff}
         */
        .btn-primary {
            /* border: 1px solid transparent; */
            background: #2b6cb0;
            color: #fff;
            box-shadow: 0 0 0 1px #1e4e8c inset;
        }
        #modalClose {
            transform: translateY(-6px);
        }
        .btn-primary,
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            height: 44px;
            padding: 0 20px;

            font-weight: 700;
            font-size: 16px;
            line-height: 44px;   /* 👈 KĽÚČOVÉ */
            
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            box-sizing: border-box;

            vertical-align: middle; /* 👈 fix baseline */
        }

        /* reset button */
        button.btn-secondary {
            appearance: none;
            -webkit-appearance: none;
            border-width: 1px;
        }

        /* reset anchor */
        a.btn-primary {
            display: inline-flex;
        }

        /* rodič */
        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            align-items: center;
        }
        .xbtn{border:1px solid #d6daea;background:#fff;border-radius:10px;padding:8px 10px;cursor:pointer}
        .kv{display:grid;grid-template-columns:130px 1fr;gap:8px 12px;margin-top:10px}
        .kv div{min-width:0}
        .kv .k{color:#6b7280}
        .kv .v{font-weight:650}
        .status-icon{width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:12px;font-size:24px}
        .status-icon.ok{background:#dcfce7;color:#0a7a2f}
        .status-icon.err{background:#fee2e2;color:#b42318}
        .modal-status{display:flex;align-items:center;gap:12px;margin-bottom:12px}
        .modal-status-icon{font-size:28px}
        .xbtn{border:1px solid #d6daea;background:#fff;border-radius:10px;padding:8px 14px;cursor:pointer;font-size:18px;font-weight:700;color:#6b7280;transition:all .15s}
        .xbtn:hover{background:#f3f4f6;border-color:#9ca3af;color:#374151}
    </style>
</head>
<body>
<div class="top">
    <div class="top-inner">
        <div>
            <strong>Rezervácie učební</strong>
            <div class="muted"><?= htmlspecialchars(($me['full_name'] ?? '') . ' (' . ($me['username'] ?? '') . ')') ?></div>
        </div>
        <div class="links">
            <a href="my_reservations.php">Moje rezervácie</a>
            <a href="logout.php">Odhlásiť</a>
        </div>
    </div>
</div>

<div class="wrap">
    <div class="grid">
        <div class="card">
            <h2 style="margin:0 0 8px 0">Vytvoriť rezerváciu</h2>

            <label for="date">Dátum</label>
            <input id="date" type="date">

            <label for="classroom">Učebňa</label>
            <select id="classroom"></select>

            <div class="row">
                <div>
                    <label for="time_from">Od</label>
                    <select id="time_from"></select>
                </div>
                <div>
                    <label for="time_to">Do</label>
                    <select id="time_to"></select>
                </div>
            </div>

            <label for="note">Poznámka</label>
            <textarea id="note" placeholder="Predmet, trieda..."></textarea>

            <button id="createBtn" type="button">Rezervovať</button>
            <div id="formMsg" class="msg"></div>
            <div class="muted" style="margin-top:10px">Klikni na dátum a hneď vidíš obsadenosť v tabuľke vpravo.</div>
        </div>

        <div class="card">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                <h2 style="margin:0">Kalendár obsadenosti</h2>
                <div style="display:flex;gap:8px;align-items:center">
                    <button id="prevMonth" type="button" style="margin:0;padding:6px 12px">&lt;</button>
                    <span id="monthLabel" style="font-weight:600;min-width:120px;text-align:center"></span>
                    <button id="nextMonth" type="button" style="margin:0;padding:6px 12px">&gt;</button>
                </div>
            </div>
            <div class="legend" style="display:flex;gap:12px;margin-top:10px;font-size:13px;flex-wrap:wrap">
                <span><span style="display:inline-block;width:12px;height:12px;background:#dcfce7;border:1px solid #10b981;border-radius:3px;margin-right:4px"></span>Voľné</span>
                <span><span style="display:inline-block;width:12px;height:12px;background:#fee2e2;border:1px solid #ef4444;border-radius:3px;margin-right:4px"></span>Vytvorená rezervácia</span>
            </div>
            <div id="calendarGrid" style="margin-top:12px">
                <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;margin-bottom:4px;font-weight:600;font-size:13px;text-align:center;color:#6b7280">
                    <div>Po</div><div>Ut</div><div>St</div><div>Št</div><div>Pi</div><div>So</div><div>Ne</div>
                </div>
                <div id="calendarDays" style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px"></div>
            </div>
            <div id="dayDetail" style="margin-top:16px;padding-top:16px;border-top:1px solid #e6e8f0;display:none">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                    <h3 style="margin:0">Detail: <span id="detailDate"></span></h3>
                    <button id="closeDetail" type="button" class="btn-secondary" style="margin:0">Zavrieť</button>
                </div>
                <div id="detailContent" style="margin-top:10px;overflow:auto;max-height:40vh"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="modalBackdrop" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal">
        <div class="modal-head">
            <div style="display:flex;align-items:center;gap:10px">
                <div id="modalStatusIcon" class="modal-status-icon"></div>
                <div>
                    <div id="modalTitle" style="font-weight:800">Rezervácia vytvorená</div>
                    <div class="muted" id="modalSubtitle"></div>
                </div>
            </div>
            <button id="modalCloseX" class="xbtn" type="button">&times;</button>
        </div>
        <div class="modal-body">
            <div class="kv">
                <div class="k">Učebňa</div><div class="v" id="mClassroom"></div>
                <div class="k">Dátum</div><div class="v" id="mDate"></div>
                <div class="k">Čas</div><div class="v" id="mTime"></div>
                <div class="k">Poznámka</div><div class="v" id="mNote"></div>
                <div class="k">ID</div><div class="v" id="mId"></div>
            </div>
        </div>
        <!-- <div class="modal-actions">
            <button id="modalClose" class="btn-secondary" type="button" style="height:36px;display:inline-flex;align-items:center;justify-content:center;vertical-align:middle;box-sizing:border-box">Zavrieť</button>
            <a class="btn-primary" href="my_reservations.php" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;height:36px;vertical-align:middle;box-sizing:border-box">Moje rezervácie</a>
        </div> -->
        <div class="modal-actions">
          <button id="modalClose" class="btn-secondary" type="button">
              Zavrieť
          </button>
          <a class="btn-primary" href="my_reservations.php">
              Moje rezervácie
          </a>
      </div>
    </div>
</div>

<script>
const classroomSelect = document.getElementById('classroom');
const dateInput = document.getElementById('date');
const dateLabel = document.getElementById('dateLabel');
const tableBody = document.getElementById('tableBody');
const formMsg = document.getElementById('formMsg');
const timeFrom = document.getElementById('time_from');
const timeTo = document.getElementById('time_to');

function pad(n){return String(n).padStart(2,'0');}
function toDateValue(d){return d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate());}

function buildTimeOptions(){
  const times = [];
  for(let h=7;h<=19;h++){
    times.push(pad(h)+':00');
    if(h!==19) times.push(pad(h)+':30');
  }
  const make = (sel) => {
    sel.innerHTML = '';
    for(const t of times){
      const opt = document.createElement('option');
      opt.value = t;
      opt.textContent = t;
      sel.appendChild(opt);
    }
  };
  make(timeFrom);
  make(timeTo);
  timeFrom.value = '08:00';
  timeTo.value = '09:00';
}

async function loadClassrooms(){
  const res = await fetch('get_classrooms.php');
  const data = await res.json();
  if(!data.ok) throw new Error(data.error || 'Failed');
  classroomSelect.innerHTML = '';
  for(const c of data.classrooms){
    const opt = document.createElement('option');
    opt.value = c.id;
    opt.textContent = c.name + (c.note ? ' — ' + c.note : '');
    classroomSelect.appendChild(opt);
  }
}

function groupReservations(reservations){
  const map = new Map();
  for(const r of reservations){
    if(!map.has(String(r.classroom_id))) map.set(String(r.classroom_id), []);
    map.get(String(r.classroom_id)).push(r);
  }
  return map;
}

function groupReservationsByDate(reservations){
  const map = new Map();
  for(const r of reservations){
    const dateKey = r.date;
    if(!map.has(dateKey)) map.set(dateKey, []);
    map.get(dateKey).push(r);
  }
  return map;
}

function esc(s){
  return String(s).replace(/[&<>\"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#39;'}[c]));
}

let currentMonth = new Date();
let cachedClassrooms = [];
let cachedReservations = new Map();

function getMonthName(date){
  const months = ['Január','Február','Marec','Apríl','Máj','Jún','Júl','August','September','Október','November','December'];
  return months[date.getMonth()] + ' ' + date.getFullYear();
}

function getDaysInMonth(year, month){
  return new Date(year, month + 1, 0).getDate();
}

function getFirstDayOfMonth(year, month){
  // 0 = Sunday, 1 = Monday, etc. Convert to 0 = Monday
  const d = new Date(year, month, 1).getDay();
  return d === 0 ? 6 : d - 1; // Monday = 0, Sunday = 6
}

async function loadCalendar(){
  const monthLabel = document.getElementById('monthLabel');
  const calendarDays = document.getElementById('calendarDays');
  
  monthLabel.textContent = getMonthName(currentMonth);
  calendarDays.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:20px">Načítavam...</div>';
  
  const year = currentMonth.getFullYear();
  const month = currentMonth.getMonth();
  const daysInMonth = getDaysInMonth(year, month);
  const firstDay = getFirstDayOfMonth(year, month);
  
  // Calculate date range for query
  const startDate = `${year}-${pad(month+1)}-01`;
  const endDate = `${year}-${pad(month+1)}-${daysInMonth}`;
  
  // Load classrooms and reservations for the month
  const [cRes, rRes] = await Promise.all([
    fetch('get_classrooms.php'),
    fetch(`get_reservations.php?start_date=${startDate}&end_date=${endDate}`)
  ]);
  
  const cData = await cRes.json();
  const rData = await rRes.json();
  if(!cData.ok) throw new Error(cData.error || 'Failed');
  if(!rData.ok) throw new Error(rData.error || 'Failed');
  
  cachedClassrooms = cData.classrooms;
  cachedReservations = groupReservationsByDate(rData.reservations);
  
  // Build calendar grid
  calendarDays.innerHTML = '';
  
  // Empty cells before first day
  for(let i = 0; i < firstDay; i++){
    const empty = document.createElement('div');
    empty.style.cssText = 'min-height:60px;background:#f9fafb;border-radius:6px';
    calendarDays.appendChild(empty);
  }
  
  // Day cells
  for(let day = 1; day <= daysInMonth; day++){
    const dateStr = `${year}-${pad(month+1)}-${pad(day)}`;
    const dayReservations = cachedReservations.get(dateStr) || [];
    
    const cell = document.createElement('div');
    cell.className = 'calendar-day';
    cell.style.cssText = 'min-height:60px;padding:6px;border-radius:6px;cursor:pointer;transition:transform .1s;border:1px solid #e6e8f0;display:flex;flex-direction:column;align-items:center;justify-content:center';
    cell.dataset.date = dateStr;
    
    // Calculate occupancy
    const totalClassrooms = cachedClassrooms.length;
    const occupiedClassrooms = new Set(dayReservations.map(r => r.classroom_id)).size;
    const occupancyRate = totalClassrooms > 0 ? occupiedClassrooms / totalClassrooms : 0;
    
    // Set background color based on occupancy
    if(occupancyRate === 0){
      cell.style.background = '#dcfce7'; // green - free
    } else if(occupancyRate < 1){
      cell.style.background = '#fee2e2'; // light red - partially occupied
    } else {
      cell.style.background = '#ef4444'; // red - fully occupied
      cell.style.color = '#fff';
    }
    
    cell.innerHTML = `<div style="font-size:18px;font-weight:700">${day}</div>`;
    
    if(dayReservations.length > 0){
      cell.innerHTML += `<div style="font-size:11px;margin-top:2px">${dayReservations.length} rez.</div>`;
    }
    
    cell.addEventListener('click', () => showDayDetail(dateStr));
    
    // Highlight if it's today
    const today = new Date();
    if(year === today.getFullYear() && month === today.getMonth() && day === today.getDate()){
      cell.style.border = '2px solid #2b6cb0';
    }
    
    calendarDays.appendChild(cell);
  }
}

function formatDateSK(dateStr){
  const [y, m, d] = dateStr.split('-');
  return `${d}.${m}.${y}`;
}

function showDayDetail(dateStr){
  const dayDetail = document.getElementById('dayDetail');
  const detailDate = document.getElementById('detailDate');
  const detailContent = document.getElementById('detailContent');
  
  detailDate.textContent = formatDateSK(dateStr);
  dayDetail.style.display = 'block';
  
  const dayReservations = cachedReservations.get(dateStr) || [];
  
  if(dayReservations.length === 0){
    detailContent.innerHTML = '<div class="muted">Žiadne rezervácie na tento deň.</div>';
    return;
  }
  
  // Group by classroom
  const byClassroom = new Map();
  for(const r of dayReservations){
    if(!byClassroom.has(r.classroom_id)) byClassroom.set(r.classroom_id, []);
    byClassroom.get(r.classroom_id).push(r);
  }
  
  let html = '<table style="width:100%;font-size:13px"><thead><tr><th>Učebňa</th><th>Čas</th><th>Učiteľ</th><th>Poznámka</th></tr></thead><tbody>';
  
  for(const [classroomId, reservations] of byClassroom){
    const classroom = cachedClassrooms.find(c => c.id == classroomId);
    const classroomName = classroom ? classroom.name : 'Neznáma';
    
    for(const r of reservations){
      html += `<tr>
        <td>${esc(classroomName)}</td>
        <td class="time">${esc(r.time_from.slice(0,5))}–${esc(r.time_to.slice(0,5))}</td>
        <td>${esc(r.teacher_name)}</td>
        <td class="muted">${esc(r.note || '—')}</td>
      </tr>`;
    }
  }
  
  html += '</tbody></table>';
  detailContent.innerHTML = html;
}

function closeDayDetail(){
  document.getElementById('dayDetail').style.display = 'none';
}

document.getElementById('prevMonth').addEventListener('click', () => {
  currentMonth.setMonth(currentMonth.getMonth() - 1);
  loadCalendar();
});

document.getElementById('nextMonth').addEventListener('click', () => {
  currentMonth.setMonth(currentMonth.getMonth() + 1);
  loadCalendar();
});

document.getElementById('closeDetail').addEventListener('click', closeDayDetail);

function setMsg(el, text, ok){
  el.textContent = text || '';
  el.className = 'msg ' + (ok ? 'ok' : 'err');
}

const modalBackdrop = document.getElementById('modalBackdrop');
const modalClose = document.getElementById('modalClose');
const modalCloseX = document.getElementById('modalCloseX');

function openModal(summary){
  const isOk = summary.id !== undefined && summary.id !== null;
  const statusIcon = document.getElementById('modalStatusIcon');
  
  document.getElementById('modalTitle').textContent = summary.title || 'Informácia';
  document.getElementById('modalTitle').style.color = isOk ? '#0a7a2f' : '#b42318';
  document.getElementById('modalSubtitle').textContent = summary.subtitle || '';
  document.getElementById('mClassroom').textContent = summary.classroom || '—';
  document.getElementById('mDate').textContent = summary.date || '—';
  document.getElementById('mTime').textContent = summary.time || '—';
  document.getElementById('mNote').textContent = summary.note || '—';
  document.getElementById('mId').textContent = isOk ? String(summary.id) : '—';
  
  // Status icon
  statusIcon.textContent = isOk ? '✓' : '✗';
  statusIcon.style.color = isOk ? '#0a7a2f' : '#b42318';
  
  modalBackdrop.style.display = 'flex';
  modalBackdrop.setAttribute('aria-hidden', 'false');
}

function closeModal(){
  modalBackdrop.style.display = 'none';
  modalBackdrop.setAttribute('aria-hidden', 'true');
}

modalClose.addEventListener('click', closeModal);
modalCloseX.addEventListener('click', closeModal);
modalBackdrop.addEventListener('click', (e) => {
  if(e.target === modalBackdrop) closeModal();
});
document.addEventListener('keydown', (e) => {
  if(e.key === 'Escape') closeModal();
});

document.getElementById('createBtn').addEventListener('click', async () => {
  setMsg(formMsg, '', true);
  const payload = {
    classroom_id: parseInt(classroomSelect.value, 10),
    date: dateInput.value,
    time_from: timeFrom.value,
    time_to: timeTo.value,
    note: document.getElementById('note').value
  };

  try{
    const res = await fetch('create_reservation.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if(!data.ok){
      const classroomText = classroomSelect.options[classroomSelect.selectedIndex]?.textContent || '';
      const attempted = {
        title: 'Rezerváciu sa nepodarilo vytvoriť',
        subtitle: data.error || 'Chyba',
        id: null,
        classroom: classroomText,
        date: payload.date,
        time: payload.time_from + '–' + payload.time_to,
        note: payload.note
      };

      if(res.status === 409){
        openModal(attempted);
      }

      setMsg(formMsg, data.error || 'Chyba', false);
      return;
    }
    setMsg(formMsg, 'Rezervácia vytvorená.', true);
    document.getElementById('note').value = '';
    const classroomText = classroomSelect.options[classroomSelect.selectedIndex]?.textContent || '';
    openModal({
      title: 'Rezervácia vytvorená',
      subtitle: 'Skontroluj údaje.',
      id: data.id,
      classroom: classroomText,
      date: payload.date,
      time: payload.time_from + '–' + payload.time_to,
      note: payload.note
    });
    await loadCalendar();
  }catch(e){
    setMsg(formMsg, e.message || 'Chyba', false);
  }
});

(async function init(){
  buildTimeOptions();
  dateInput.value = toDateValue(new Date());
  await loadClassrooms();
  await loadCalendar();
})();
</script>
</body>
</html>
