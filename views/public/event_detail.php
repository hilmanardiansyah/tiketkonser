<?php
include __DIR__ . '/../layout/header.php';
include __DIR__ . '/../layout/navbar.php';

$BASE = defined('BASE_URL') ? BASE_URL : '';
$WEB  = $BASE . '/public';

$id = (int)($event_id ?? ($_GET['id'] ?? 0));
if ($id <= 0) {
  http_response_code(404);
  echo "<main class='site-wrap' style='padding:24px 0 44px;'><div class='panel p-4'>Event tidak valid.</div></main>";
  include __DIR__ . '/../layout/footer.php';
  exit;
}

function http_get_json(string $url): ?array {
  $ch = function_exists('curl_init') ? curl_init($url) : null;
  if ($ch) {
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_TIMEOUT => 10,
      CURLOPT_HTTPHEADER => ['Accept: application/json']
    ]);
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($raw === false || $code < 200 || $code >= 300) return null;
    $json = json_decode($raw, true);
    return is_array($json) ? $json : null;
  }

  $ctx = stream_context_create([
    'http' => [
      'method' => 'GET',
      'timeout' => 10,
      'header' => "Accept: application/json\r\n",
    ],
  ]);
  $raw = @file_get_contents($url, false, $ctx);
  if ($raw === false) return null;
  $json = json_decode($raw, true);
  return is_array($json) ? $json : null;
}

function pick_one(?array $res): ?array {
  if (!$res) return null;
  if (isset($res['data']) && is_array($res['data'])) return $res['data'];
  if (isset($res['event']) && is_array($res['event'])) return $res['event'];
  return $res;
}

function pick_list(?array $res): array {
  if (!$res) return [];
  if (isset($res['data']) && is_array($res['data'])) return $res['data'];
  if (isset($res['events']) && is_array($res['events'])) return $res['events'];
  if (isset($res['ticket_types']) && is_array($res['ticket_types'])) return $res['ticket_types'];
  return is_array($res) ? $res : [];
}

// Build absolute URL for server-side API calls
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$API_BASE = $protocol . '://' . $host . $WEB;

$API_EVENT = $API_BASE . '/api/events/' . $id;
$API_TT    = $WEB . '/api/events/' . $id . '/ticket-types';
$API_LIST  = $WEB . '/api/events';
$API_ORDER = $WEB . '/api/orders';

$eventRes = http_get_json($API_EVENT);
$event = pick_one($eventRes);

if (!$event) {
  http_response_code(404);
  echo "<main class='site-wrap' style='padding:24px 0 44px;'><div class='panel p-4'>Event tidak ditemukan.</div></main>";
  include __DIR__ . '/../layout/footer.php';
  exit;
}

$title = (string)($event['title'] ?? $event['name'] ?? 'Untitled Event');
$desc  = (string)($event['description'] ?? '');
$city  = (string)($event['city'] ?? '');
$venue = (string)($event['venue'] ?? '');
$date  = (string)($event['event_date'] ?? $event['date'] ?? '');
$timeS = (string)($event['start_time'] ?? '');
$timeE = (string)($event['end_time'] ?? '');
$poster = (string)($event['poster_file'] ?? $event['poster_url'] ?? $event['poster'] ?? '');

$u = function_exists('current_user') ? current_user() : null;
$isLogged = $u ? true : false;
?>
<link rel="stylesheet" href="<?= $WEB ?>/assets/landing.css">

<style>
.ev-wrap{padding:16px 0 44px}
.ev-hero{border-radius:22px; overflow:hidden; position:relative}
.ev-hero__bg{height:340px; background:rgba(255,255,255,.04); border-bottom:1px solid rgba(255,255,255,.08); position:relative}
.ev-hero__bg img{width:100%; height:100%; object-fit:cover; display:block; filter:saturate(1.05) contrast(1.05)}
.ev-hero__bg::after{content:""; position:absolute; inset:0; background:linear-gradient(180deg, rgba(5,6,10,.05), rgba(5,6,10,.85) 76%, rgba(5,6,10,1))}
.ev-hero__in{position:relative; padding:18px 18px 18px}
.ev-kicker{display:inline-flex; align-items:center; gap:10px; padding:8px 12px; border-radius:999px; background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.10); color:rgba(229,231,235,.85); font-weight:800; font-size:13px; width:fit-content}
.ev-title{margin:12px 0 0; font-size:34px; font-weight:950; line-height:1.12; color:#fff}
@media(max-width:576px){.ev-title{font-size:28px}}
.ev-meta{margin-top:10px; display:flex; flex-wrap:wrap; gap:10px}
.ev-chip{display:inline-flex; gap:8px; align-items:center; padding:8px 10px; border-radius:999px; background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.10); color:rgba(229,231,235,.75); font-size:12px; font-weight:800}
.ev-content{display:grid; grid-template-columns: 1.3fr .7fr; gap:14px; margin-top:14px}
@media(max-width:992px){.ev-content{grid-template-columns:1fr}}
.ev-block{border-radius:18px; padding:16px; background:rgba(17,24,39,.55); border:1px solid rgba(255,255,255,.10)}
.ev-h2{margin:0; font-size:16px; font-weight:950; color:#fff}
.ev-p{margin-top:10px; color:rgba(229,231,235,.72); line-height:1.75; font-size:13px}
.ev-sideHead{display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px}
.ev-search{width:100%}
.ev-search .form-control{border-radius:14px}
.tt-grid{display:grid; gap:10px; margin-top:10px}
.tt-card{border-radius:16px; border:1px solid rgba(255,255,255,.10); background:rgba(0,0,0,.18); padding:12px; display:flex; align-items:flex-start; justify-content:space-between; gap:10px; cursor:pointer; transition:transform .14s ease, border-color .14s ease, background .14s ease}
.tt-card:hover{transform:translateY(-1px); border-color:rgba(37,99,235,.35); background:rgba(0,0,0,.22)}
.tt-card.active{border-color:rgba(37,99,235,.55); background:rgba(37,99,235,.10)}
.tt-name{font-weight:950; color:#fff; line-height:1.2}
.tt-sub{margin-top:6px; color:rgba(229,231,235,.65); font-size:12px}
.tt-price{font-weight:950; color:rgba(229,231,235,.92); font-size:13px; white-space:nowrap}
.ev-check{margin-top:12px; border-top:1px solid rgba(255,255,255,.08); padding-top:12px}
.ev-row{display:flex; align-items:center; justify-content:space-between; gap:12px}
.ev-row label{font-size:12px; color:rgba(229,231,235,.70); font-weight:900}
.qty-wrap{display:flex; align-items:center; gap:8px}
.qty-btn{width:34px; height:34px; border-radius:12px; display:grid; place-items:center; background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.10); color:#fff; font-weight:950}
.qty-input{width:64px; text-align:center; border-radius:12px !important}
.ev-total{margin-top:10px; padding:12px; border-radius:16px; background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.10)}
.ev-total b{font-weight:950}
.ev-actions{margin-top:10px; display:flex; gap:10px; flex-wrap:wrap}
.ev-actions .btn{border-radius:999px; font-weight:950; padding:10px 14px}
.ev-msg{margin-top:10px; font-size:12px; color:rgba(229,231,235,.70)}
.rel-head{margin-top:18px}
.rel-grid{margin-top:10px}
.small-note{font-size:12px; color:rgba(229,231,235,.62)}
</style>

<main class="site-wrap ev-wrap">
  <section class="panel ev-hero">
    <div class="ev-hero__bg">
      <?php if ($poster): ?>
        <img src="<?= e($poster) ?>" alt="<?= e($title) ?>">
      <?php endif; ?>
    </div>

    <div class="ev-hero__in">
      <div class="ev-kicker">
        <span class="app-dot"></span>
        <span><?= e($city ?: 'Fesmic') ?></span>
      </div>

      <h1 class="ev-title"><?= e($title) ?></h1>

      <div class="ev-meta">
        <?php if ($venue || $city): ?>
          <div class="ev-chip"><?= e(trim(($city ? $city : '') . ($venue ? ' • ' . $venue : ''))) ?></div>
        <?php endif; ?>
        <?php if ($date): ?>
          <div class="ev-chip"><?= e($date) ?><?= $timeS ? ' • ' . e($timeS) : '' ?><?= $timeE ? ' - ' . e($timeE) : '' ?></div>
        <?php endif; ?>
        <div class="ev-chip"><?= $isLogged ? 'Login: ' . e($u['name'] ?? '') : 'Guest' ?></div>
      </div>
    </div>
  </section>

  <section class="ev-content">
    <div class="ev-block">
      <h2 class="ev-h2">Deskripsi Event</h2>
      <div class="ev-p" style="white-space:pre-line;"><?= e($desc ?: 'Belum ada deskripsi untuk event ini.') ?></div>

      <div class="ev-block rel-head" style="margin-top:14px;">
        <div class="d-flex align-items-end justify-content-between gap-2">
          <div>
            <div class="ev-h2">Event Lainnya</div>
            <div class="small-note">Rekomendasi event lain yang sedang tersedia.</div>
          </div>
          <a class="btn btn-outline-light btn-sm" style="border-radius:999px; font-weight:950;" href="<?= $WEB ?>/events">All Event</a>
        </div>

        <div id="relGrid" class="row g-3 rel-grid"></div>
      </div>
    </div>

    <aside class="ev-block">
      <div class="ev-sideHead">
        <div>
          <div class="ev-h2">Pilih Tiket</div>
          <div class="small-note">Pilih kategori, tentukan jumlah, lalu checkout.</div>
        </div>
      </div>

      <div class="ev-search">
        <input id="ttQ" type="text" class="form-control" placeholder="Cari kategori tiket..." autocomplete="off">
      </div>

      <div id="ttGrid" class="tt-grid">
        <div class="tt-card">
          <div style="flex:1;">
            <div class="tt-name" style="opacity:.7;">Memuat tiket...</div>
            <div class="tt-sub">Tunggu sebentar</div>
          </div>
          <div class="tt-price">—</div>
        </div>
      </div>

      <div class="ev-check">
        <div class="ev-row">
          <label>Jumlah</label>
          <div class="qty-wrap">
            <button type="button" class="qty-btn" id="dec">-</button>
            <input id="qty" class="form-control qty-input" type="number" min="1" value="1">
            <button type="button" class="qty-btn" id="inc">+</button>
          </div>
        </div>

        <div class="ev-total">
          <div class="d-flex align-items-center justify-content-between">
            <span style="color:rgba(229,231,235,.7); font-size:12px; font-weight:900;">Total</span>
            <b id="totalText">Rp 0</b>
          </div>
          <div class="small-note" id="stockText" style="margin-top:6px;">Pilih kategori tiket dulu.</div>
        </div>

        <div class="ev-actions">
          <?php if (!$isLogged): ?>
            <a class="btn btn-primary" href="<?= $WEB ?>/login">Login untuk beli</a>
            <a class="btn btn-outline-light" href="<?= $WEB ?>/events">Cari event lain</a>
          <?php else: ?>
            <button class="btn btn-primary" id="btnBuy" type="button">Checkout</button>
            <a class="btn btn-outline-light" href="<?= $WEB ?>/dashboard">Dashboard</a>
          <?php endif; ?>
        </div>

        <div class="ev-msg" id="msg"></div>
      </div>
    </aside>
  </section>
</main>

<script>
  const WEB = <?= json_encode($WEB) ?>;
  const API_TT = <?= json_encode($API_TT) ?>;
  const API_LIST = <?= json_encode($API_LIST) ?>;
  const API_ORDER = <?= json_encode($API_ORDER) ?>;
  const EVENT_ID = <?= (int)$id ?>;
  const LOGGED = <?= $isLogged ? 'true' : 'false' ?>;

  const ttGrid = document.getElementById('ttGrid');
  const ttQ = document.getElementById('ttQ');
  const qtyEl = document.getElementById('qty');
  const dec = document.getElementById('dec');
  const inc = document.getElementById('inc');
  const totalText = document.getElementById('totalText');
  const stockText = document.getElementById('stockText');
  const msg = document.getElementById('msg');
  const btnBuy = document.getElementById('btnBuy');
  const relGrid = document.getElementById('relGrid');

  let ticketTypes = [];
  let selected = null;

  function safe(v){ return (v===null||v===undefined) ? '' : String(v); }
  function pickList(res){
    if (Array.isArray(res)) return res;
    if (res && Array.isArray(res.data)) return res.data;
    if (res && Array.isArray(res.ticket_types)) return res.ticket_types;
    if (res && Array.isArray(res.events)) return res.events;
    return [];
  }

  function rupiah(n){
    const x = Number(n || 0);
    return 'Rp ' + x.toLocaleString('id-ID');
  }

  function remain(tt){
    const quota = Number(tt.quota ?? tt.stock ?? 0);
    const sold  = Number(tt.sold ?? tt.sold_count ?? 0);
    if (!quota && !sold) return null;
    return Math.max(0, quota - sold);
  }

  function calcTotal(){
    const qty = Math.max(1, Number(qtyEl.value || 1));
    qtyEl.value = qty;
    const price = selected ? Number(selected.price ?? selected.unit_price ?? 0) : 0;
    totalText.textContent = rupiah(price * qty);

    if (!selected){
      stockText.textContent = 'Pilih kategori tiket dulu.';
      return;
    }
    const r = remain(selected);
    const rText = (r===null) ? 'Kuota: —' : ('Sisa kuota: ' + r);
    const tName = safe(selected.name ?? selected.title ?? 'Tiket');
    stockText.textContent = tName + ' • ' + rText;
  }

  function setMsg(text, isErr=false){
    msg.textContent = text || '';
    msg.style.color = isErr ? 'rgba(248,113,113,.95)' : 'rgba(229,231,235,.70)';
  }

  function renderTickets(list){
    if (!list.length){
      ttGrid.innerHTML = `
        <div class="tt-card" style="cursor:default;">
          <div style="flex:1;">
            <div class="tt-name">Tiket belum tersedia</div>
            <div class="tt-sub">Admin/EO belum menambahkan kategori tiket untuk event ini.</div>
          </div>
          <div class="tt-price">—</div>
        </div>
      `;
      selected = null;
      calcTotal();
      return;
    }

    ttGrid.innerHTML = list.map(tt => {
      const id = tt.id ?? tt.ticket_type_id ?? '';
      const name = safe(tt.name ?? tt.title ?? 'Ticket');
      const price = Number(tt.price ?? tt.unit_price ?? 0);
      const r = remain(tt);
      const sub = [
        (r===null ? null : ('Sisa ' + r)),
        safe(tt.sales_start ?? ''),
        safe(tt.sales_end ?? '')
      ].filter(Boolean).join(' • ') || 'Tersedia';
      return `
        <div class="tt-card" data-id="${id}">
          <div style="flex:1;">
            <div class="tt-name">${name}</div>
            <div class="tt-sub">${sub}</div>
          </div>
          <div class="tt-price">${rupiah(price)}</div>
        </div>
      `;
    }).join('');

    const firstId = list[0].id ?? list[0].ticket_type_id ?? null;
    if (firstId !== null) selectTicket(String(firstId));
  }

  function selectTicket(id){
    const found = ticketTypes.find(x => String(x.id ?? x.ticket_type_id) === String(id));
    if (!found) return;

    selected = found;
    Array.from(ttGrid.querySelectorAll('.tt-card')).forEach(el => {
      el.classList.toggle('active', el.dataset.id === String(id));
    });
    setMsg('');
    calcTotal();
  }

  function applyTicketFilter(){
    const q = (ttQ.value || '').trim().toLowerCase();
    if (!q) return renderTickets(ticketTypes);
    const filtered = ticketTypes.filter(tt => safe(tt.name ?? tt.title).toLowerCase().includes(q));
    renderTickets(filtered);
  }

  async function loadTicketTypes(){
    try{
      const res = await fetch(API_TT, { headers: { 'Accept':'application/json' }});
      const json = await res.json();
      ticketTypes = pickList(json);
      renderTickets(ticketTypes);
    }catch(e){
      ttGrid.innerHTML = `
        <div class="tt-card" style="cursor:default;">
          <div style="flex:1;">
            <div class="tt-name">Gagal memuat tiket</div>
            <div class="tt-sub">Cek route <b>${API_TT}</b></div>
          </div>
          <div class="tt-price">—</div>
        </div>
      `;
      selected = null;
      calcTotal();
    }
  }

  async function loadRelated(){
    try{
      const res = await fetch(API_LIST, { headers: { 'Accept':'application/json' }});
      const json = await res.json();
      const list = pickList(json).filter(x => Number(x.id ?? x.event_id ?? 0) !== EVENT_ID).slice(0, 3);

      if (!list.length){
        relGrid.innerHTML = `
          <div class="col-12">
            <div class="small-note">Belum ada event lain untuk ditampilkan.</div>
          </div>
        `;
        return;
      }

      relGrid.innerHTML = list.map(ev => {
        const id = ev.id ?? ev.event_id ?? 0;
        const title = safe(ev.title ?? ev.name ?? 'Event');
        const city = safe(ev.city ?? '');
        const venue = safe(ev.venue ?? '');
        const date = safe(ev.event_date ?? ev.date ?? '');
        const poster = safe(ev.poster_file ?? ev.poster_url ?? ev.poster ?? '');
        const meta = [city, venue, date].filter(Boolean).join(' • ') || '—';
        const href = `${WEB}/events/${id}`;

        return `
          <div class="col-12 col-md-4">
            <a class="event-card d-block text-decoration-none" href="${href}">
              <div class="event-card__img">
                ${
                  poster
                    ? `<img src="${poster}" alt="${title.replaceAll('"','&quot;')}">`
                    : `<div style="height:170px; display:flex; align-items:center; justify-content:center; color:rgba(229,231,235,.6); background:rgba(255,255,255,.04);">No poster</div>`
                }
              </div>
              <div class="event-card__body">
                <div class="event-card__title">${title}</div>
                <div class="event-card__meta">${meta}</div>
              </div>
            </a>
          </div>
        `;
      }).join('');
    }catch(e){
      relGrid.innerHTML = `
        <div class="col-12">
          <div class="small-note">Gagal memuat rekomendasi event.</div>
        </div>
      `;
    }
  }

  async function checkout(){
    if (!LOGGED){
      window.location.href = `${WEB}/login`;
      return;
    }
    if (!selected){
      setMsg('Pilih kategori tiket dulu.', true);
      return;
    }

    const qty = Math.max(1, Number(qtyEl.value || 1));
    const r = remain(selected);
    if (r !== null && qty > r){
      setMsg('Jumlah melebihi sisa kuota.', true);
      return;
    }

    const ticketTypeId = selected.id ?? selected.ticket_type_id;
    if (!ticketTypeId){
      setMsg('Tiket tidak valid.', true);
      return;
    }

    btnBuy.disabled = true;
    btnBuy.textContent = 'Memproses...';
    setMsg('');

    try{
      const payload = { ticket_type_id: Number(ticketTypeId), qty: qty, event_id: EVENT_ID };
      const res = await fetch(API_ORDER, {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'Accept':'application/json' },
        body: JSON.stringify(payload)
      });

      const json = await res.json().catch(() => ({}));

      if (!res.ok){
        const err = (json && (json.error || json.message)) ? (json.error || json.message) : 'Gagal membuat pesanan.';
        setMsg(err, true);
        return;
      }

      const code = json.order_code ?? json.code ?? json.data?.order_code ?? json.data?.code ?? '';
      if (code){
        setMsg('Pesanan berhasil dibuat: ' + code);
      } else {
        setMsg('Pesanan berhasil dibuat.');
      }

      setTimeout(() => {
        window.location.href = `${WEB}/dashboard`;
      }, 700);

    }catch(e){
      setMsg('Gagal membuat pesanan. Coba lagi.', true);
    }finally{
      btnBuy.disabled = false;
      btnBuy.textContent = 'Checkout';
    }
  }

  ttGrid.addEventListener('click', (e) => {
    const card = e.target.closest('.tt-card');
    if (!card || !card.dataset.id) return;
    selectTicket(card.dataset.id);
  });

  ttQ.addEventListener('input', applyTicketFilter);

  dec.addEventListener('click', () => {
    qtyEl.value = Math.max(1, Number(qtyEl.value || 1) - 1);
    calcTotal();
  });

  inc.addEventListener('click', () => {
    qtyEl.value = Math.max(1, Number(qtyEl.value || 1) + 1);
    calcTotal();
  });

  qtyEl.addEventListener('input', calcTotal);

  if (btnBuy) btnBuy.addEventListener('click', checkout);

  loadTicketTypes();
  loadRelated();
  calcTotal();
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
