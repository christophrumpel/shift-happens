<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Gear Shifter</title>
<style>
  :root {
    --bg: #0d1117;
    --panel: #161b22;
    --border: #2d333b;
    --slot: #1f2630;
    --text: #e6edf3;
    --muted: #8b949e;
    --accent: #f96854;
    --green: #3fb950;
    --blue: #58a6ff;
    --yellow: #d29922;
    --red: #f85149;
    --mono: ui-monospace, "SF Mono", "JetBrains Mono", Menlo, Consolas, monospace;
  }
  * { margin: 0; padding: 0; box-sizing: border-box; }
  html, body { height: 100%; }
  body {
    background: var(--bg);
    color: var(--text);
    font-family: var(--mono);
    display: flex;
    flex-direction: column;
    padding: 32px;
    overflow: hidden;
  }
  header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 28px;
  }
  h1 { font-size: 20px; font-weight: 600; letter-spacing: 0.05em; }
  h1 span { color: var(--accent); }
  .status {
    font-size: 14px; padding: 7px 16px; border-radius: 999px;
    border: 1px solid var(--border); color: var(--muted);
  }
  .status.connected { border-color: var(--green); color: var(--green); }
  .hint { font-size: 12px; color: #566070; margin-left: auto; margin-right: 16px; }

  /* ── gear info modal (press "i") ── */
  #infoModal {
    position: fixed; inset: 0; background: rgba(13,17,23,0.8);
    display: none; align-items: center; justify-content: center; z-index: 20;
  }
  #infoModal.open { display: flex; }
  .info-card {
    background: var(--panel); border: 1px solid var(--border);
    border-radius: 16px; padding: 28px; min-width: 420px;
    box-shadow: 0 24px 60px rgba(0,0,0,0.5);
  }
  .info-head {
    display: flex; justify-content: space-between; align-items: center;
    font-size: 14px; text-transform: uppercase; letter-spacing: 0.16em;
    color: var(--muted); font-weight: 600; margin-bottom: 20px;
  }
  .info-close {
    font-size: 11px; border: 1px solid var(--border); border-radius: 6px;
    padding: 3px 8px; text-transform: lowercase; letter-spacing: 0.05em;
  }
  .info-row {
    display: flex; align-items: center; gap: 18px;
    padding: 12px 4px; border-bottom: 1px solid var(--border);
    font-size: 17px;
  }
  .info-row:last-child { border-bottom: none; }
  .info-gear {
    font-size: 26px; font-weight: 800; color: var(--accent);
    width: 36px; text-align: center; flex-shrink: 0;
  }
  .info-label { font-weight: 600; }
  .info-hold {
    margin-left: auto; font-size: 12px; font-weight: 700;
    color: var(--yellow); background: rgba(210,153,34,0.15);
    padding: 3px 10px; border-radius: 6px;
  }
  .info-foot { margin-top: 16px; font-size: 12px; color: var(--muted); }

  .layout {
    flex: 1;
    min-height: 0;
    display: grid;
    grid-template-columns: 1.15fr 1fr;
    gap: 28px;
    align-items: stretch;
  }
  .panel {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 28px;
    display: flex;
    flex-direction: column;
    min-height: 0;
  }
  .panel h2 {
    font-size: 14px; text-transform: uppercase; letter-spacing: 0.16em;
    color: var(--muted); margin-bottom: 20px; font-weight: 600;
  }

  /* ── H-pattern ── */
  #shifterPanel { align-items: center; justify-content: center; gap: 16px; }
  #gate { width: 100%; max-width: 680px; height: auto; }
  .slot { stroke: var(--slot); stroke-width: 26; stroke-linecap: round; fill: none; }
  .pos-label {
    font-family: var(--mono); font-size: 34px; font-weight: 800;
    fill: #4a5462; text-anchor: middle; dominant-baseline: central;
    transition: fill 0.12s;
  }
  .pos-label.active { fill: var(--accent); }
  .pos-dot { fill: none; stroke: var(--accent); stroke-width: 2.5; opacity: 0; transition: opacity 0.12s; }
  .pos-dot.active { opacity: 1; }
  #knob {
    fill: var(--accent);
    transition: transform 0.12s ease-out;
    filter: drop-shadow(0 0 16px rgba(249,104,84,0.55));
  }
  #knob.neutral { fill: #3a4250; filter: none; }

  #currentGear { text-align: center; }
  #currentGear .label { font-size: 13px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.16em; }
  #currentGear .value { font-size: 80px; font-weight: 800; color: var(--accent); line-height: 1.15; }
  #currentGear .value.neutral { color: #3a4250; }

  /* ── right column ── */
  .right { display: flex; flex-direction: column; gap: 28px; min-height: 0; }
  .right .panel { transition: flex 0.25s ease; }
  #rawPanel { flex: 1; }
  .right.has-run #console { flex: 1.7; display: flex; }
  .right.has-run #rawPanel { flex: 1; }

  /* ── arming panel (hold-to-fire gears) ── */
  #armPanel {
    display: none;
    border-color: var(--yellow);
    box-shadow: 0 0 28px rgba(210,153,34,0.14);
  }
  .right.arming #armPanel { display: flex; }
  #armPanel.cancelled {
    border-color: var(--red);
    box-shadow: 0 0 28px rgba(248,81,73,0.16);
  }
  .arm-track {
    height: 16px; background: var(--bg);
    border: 1px solid var(--border); border-radius: 8px; overflow: hidden;
  }
  .arm-fill { height: 100%; width: 0%; background: var(--yellow); }
  #armPanel.cancelled .arm-fill { background: var(--red); }
  .arm-msg {
    margin-top: 12px; font-size: 15px; font-weight: 600;
    color: var(--red); min-height: 20px;
  }
  .pill.hold { background: rgba(210,153,34,0.15); color: var(--yellow); }

  /* ── action console ── */
  #console { display: none; min-height: 0; transition: border-color 0.2s, box-shadow 0.2s; }
  #console.st-running { border-color: var(--yellow); box-shadow: 0 0 28px rgba(210,153,34,0.12); }
  #console.st-passed  { border-color: var(--green);  box-shadow: 0 0 28px rgba(63,185,80,0.14); }
  #console.st-failed  { border-color: var(--red);    box-shadow: 0 0 28px rgba(248,81,73,0.16); }

  .console-head {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 16px; margin-bottom: 16px;
  }
  .console-head .title { font-size: 26px; font-weight: 700; line-height: 1.2; }
  .console-head .sub { font-size: 13px; color: var(--muted); margin-top: 4px; text-transform: uppercase; letter-spacing: 0.14em; }
  .pill {
    font-size: 13px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;
    padding: 6px 14px; border-radius: 999px; white-space: nowrap;
  }
  .pill.queued  { background: rgba(139,148,158,0.15); color: var(--muted); }
  .pill.running { background: rgba(210,153,34,0.15);  color: var(--yellow); animation: pulse 1.1s ease-in-out infinite; }
  .pill.passed  { background: rgba(63,185,80,0.15);   color: var(--green); }
  .pill.failed  { background: rgba(248,81,73,0.15);   color: var(--red); }
  @keyframes pulse { 50% { opacity: 0.45; } }

  #consoleLog {
    flex: 1; min-height: 0; overflow-y: auto;
    background: var(--bg); border: 1px solid var(--border); border-radius: 10px;
    padding: 14px 16px; font-size: 13.5px; line-height: 1.65;
    white-space: pre-wrap; word-break: break-word;
  }
  #consoleLog .ln-pass { color: var(--green); }
  #consoleLog .ln-fail { color: var(--red); font-weight: 700; }
  #consoleLog .ln-cmd  { color: var(--blue); }
  .console-foot {
    display: flex; justify-content: space-between; margin-top: 12px;
    font-size: 13px; color: var(--muted);
  }
  #consoleBusy { color: var(--yellow); }

  /* ── raw input log ── */
  .log {
    flex: 1; min-height: 0; overflow-y: auto;
    display: flex; flex-direction: column; gap: 18px;
    scrollbar-width: none;
  }
  .log::-webkit-scrollbar { display: none; }
  .entry {
    display: flex; flex-direction: column; gap: 6px;
    animation: slideIn 0.18s ease-out;
    transition: font-size 0.15s, opacity 0.15s;
  }
  @keyframes slideIn {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: none; }
  }
  .entry .meta { display: flex; gap: 12px; align-items: center; }
  .entry .t { color: var(--muted); font-size: 14px; }
  .tag {
    font-size: 11px; font-weight: 700; letter-spacing: 0.1em;
    padding: 3px 10px; border-radius: 6px; text-transform: uppercase;
  }
  .tag.event { background: rgba(88,166,255,0.15);  color: var(--blue); }
  .tag.poll  { background: rgba(210,153,34,0.15);  color: var(--yellow); }
  .entry .body { font-size: 20px; font-weight: 600; line-height: 1.35; }
  .entry .body .gear { color: var(--accent); }
  .entry .body .up { color: var(--muted); font-weight: 500; }

  .entry:first-child {
    background: var(--bg);
    border: 1px solid var(--accent);
    border-radius: 14px;
    padding: 20px 22px;
    gap: 10px;
    box-shadow: 0 0 24px rgba(249,104,84,0.12);
  }
  .entry:first-child .body { font-size: 38px; font-weight: 700; }
  .entry:first-child .t { font-size: 15px; }
  .entry:not(:first-child) { opacity: 0.5; padding: 0 22px; }

  .right.has-run .entry:first-child,
  .right.arming .entry:first-child { padding: 12px 16px; }
  .right.has-run .entry:first-child .body,
  .right.arming .entry:first-child .body { font-size: 22px; }
  .right.has-run .entry .body,
  .right.arming .entry .body { font-size: 16px; }
  .right.has-run .log,
  .right.arming .log { gap: 10px; }
  .right.has-run #rawPanel h2,
  .right.arming #rawPanel h2 { margin-bottom: 12px; }

  @media (max-width: 980px) { .layout { grid-template-columns: 1fr; } body { overflow: auto; } }
</style>
</head>
<body>

<header>
  <h1>GEAR <span>SHIFTER</span></h1>
  <span class="hint">i · info</span>
  <div class="status" id="status">no gamepad</div>
</header>

<div class="layout">

  <div class="panel" id="shifterPanel">
    <svg id="gate" viewBox="0 0 480 400">
      <text class="pos-label" data-gear="1" x="90"  y="40">1</text>
      <text class="pos-label" data-gear="3" x="190" y="40">3</text>
      <text class="pos-label" data-gear="5" x="290" y="40">5</text>
      <text class="pos-label" data-gear="7" x="390" y="40">7</text>
      <text class="pos-label" data-gear="2" x="90"  y="330">2</text>
      <text class="pos-label" data-gear="4" x="190" y="330">4</text>
      <text class="pos-label" data-gear="6" x="290" y="330">6</text>
      <text class="pos-label" data-gear="R" x="390" y="330">R</text>
      <line class="slot" x1="90"  y1="200" x2="390" y2="200"/>
      <line class="slot" x1="90"  y1="125" x2="90"  y2="275"/>
      <line class="slot" x1="190" y1="125" x2="190" y2="275"/>
      <line class="slot" x1="290" y1="125" x2="290" y2="275"/>
      <line class="slot" x1="390" y1="125" x2="390" y2="275"/>
      <circle class="pos-dot" data-gear="1" cx="90"  cy="125" r="27"/>
      <circle class="pos-dot" data-gear="2" cx="90"  cy="275" r="27"/>
      <circle class="pos-dot" data-gear="3" cx="190" cy="125" r="27"/>
      <circle class="pos-dot" data-gear="4" cx="190" cy="275" r="27"/>
      <circle class="pos-dot" data-gear="5" cx="290" cy="125" r="27"/>
      <circle class="pos-dot" data-gear="6" cx="290" cy="275" r="27"/>
      <circle class="pos-dot" data-gear="7" cx="390" cy="125" r="27"/>
      <circle class="pos-dot" data-gear="R" cx="390" cy="275" r="27"/>
      <circle id="knob" class="neutral" cx="0" cy="0" r="16" transform="translate(240,200)"/>
    </svg>
    <div id="currentGear">
      <div class="label">Current gear</div>
      <div class="value neutral" id="gearValue">N</div>
    </div>
  </div>

  <div class="right" id="rightCol">

    <div class="panel" id="armPanel">
      <div class="console-head">
        <div>
          <div class="title" id="armTitle">&nbsp;</div>
          <div class="sub" id="armSub">&nbsp;</div>
        </div>
        <div class="pill hold" id="armCount"></div>
      </div>
      <div class="arm-track"><div class="arm-fill" id="armFill"></div></div>
      <div class="arm-msg" id="armMsg"></div>
    </div>

    <div class="panel" id="console">
      <div class="console-head">
        <div>
          <div class="title" id="consoleTitle">&nbsp;</div>
          <div class="sub" id="consoleSub">&nbsp;</div>
        </div>
        <div class="pill queued" id="consolePill">queued</div>
      </div>
      <div id="consoleLog"></div>
      <div class="console-foot">
        <span id="consoleElapsed"></span>
        <span id="consoleBusy"></span>
      </div>
    </div>

    <div class="panel" id="rawPanel">
      <h2>Raw input</h2>
      <div class="log" id="rawLog"></div>
    </div>

  </div>

</div>

<div id="infoModal">
  <div class="info-card">
    <div class="info-head">
      <span>Gear mapping</span>
      <span class="info-close">esc</span>
    </div>
    <div id="infoRows"></div>
    <div class="info-foot">unmapped gears do nothing</div>
  </div>
</div>

<script>
(() => {
  // ── button index → gear (standard TH8S) ──────────────────────
  const MAPPING = { 0:'1', 1:'2', 2:'3', 3:'4', 4:'5', 5:'6', 6:'7', 7:'R' };

  // gear → { label, hold }, straight from config/gears.php
  const ACTIONS = @json(collect(config('gears.map'))->map(fn ($action) => [
      'label' => $action['label'],
      'hold' => $action['hold'] ?? 0,
  ]));

  const CSRF = document.querySelector('meta[name="csrf-token"]').content;
  const DEBOUNCE_MS = 150;
  const POLL_MS = 500;

  const POS = {
    '1':[90,125],  '3':[190,125], '5':[290,125], '7':[390,125],
    '2':[90,275],  '4':[190,275], '6':[290,275], 'R':[390,275],
    'N':[240,200]
  };

  const $ = (id) => document.getElementById(id);
  const statusEl = $('status'), knob = $('knob'),
        gearValueEl = $('gearValue'), rawLogEl = $('rawLog'),
        rightCol = $('rightCol'), consoleEl = $('console'),
        consoleTitle = $('consoleTitle'), consoleSub = $('consoleSub'),
        consolePill = $('consolePill'), consoleLog = $('consoleLog'),
        consoleElapsed = $('consoleElapsed'), consoleBusy = $('consoleBusy'),
        armPanel = $('armPanel'), armTitle = $('armTitle'), armSub = $('armSub'),
        armCount = $('armCount'), armFill = $('armFill'), armMsg = $('armMsg');
  const labels = [...document.querySelectorAll('.pos-label')];
  const dots   = [...document.querySelectorAll('.pos-dot')];

  // build the gear info modal (press "i") from the config mapping
  const infoRows = $('infoRows');
  for (const [gear, action] of Object.entries(ACTIONS)) {
    const row = document.createElement('div');
    row.className = 'info-row';
    row.innerHTML =
      `<span class="info-gear">${gear}</span>` +
      `<span class="info-label">${action.label}</span>` +
      (action.hold > 0 ? `<span class="info-hold">hold ${action.hold}s</span>` : '');
    infoRows.appendChild(row);
  }
  const infoModal = $('infoModal');
  function toggleInfo(force) {
    infoModal.classList.toggle('open', force);
  }
  document.addEventListener('keydown', (e) => {
    if (e.key === 'i' || e.key === 'I') toggleInfo();
    if (e.key === 'Escape') toggleInfo(false);
  });
  infoModal.addEventListener('click', (e) => {
    if (e.target === infoModal) toggleInfo(false);
  });

  let activeIndex = null;
  let prevButtons = [];
  let displayedGear = 'N';
  let candidateGear = 'N';
  let candidateSince = 0;
  let sentGear = 'N';
  let armingGear = null;
  let armingStart = 0;
  let armHideTimer = null;
  let stateTrusted = false;
  let adoptTimestamp = 0;

  const ts = () => {
    const d = new Date();
    return d.toTimeString().slice(0,8) + '.' + String(d.getMilliseconds()).padStart(3,'0');
  };

  function addEntry(tag, html) {
    const div = document.createElement('div');
    div.className = 'entry';
    div.innerHTML =
      `<div class="meta"><span class="tag ${tag}">${tag}</span><span class="t">${ts()}</span></div>` +
      `<div class="body">${html}</div>`;
    rawLogEl.prepend(div);
    while (rawLogEl.children.length > 40) rawLogEl.lastChild.remove();
  }

  function gearName(g) { return g === 'R' ? 'reverse' : 'gear ' + g; }

  function setDisplay(gear) {
    if (gear === displayedGear) return;
    displayedGear = gear;
    const [x,y] = POS[gear] || POS.N;
    knob.setAttribute('transform', `translate(${x},${y})`);
    knob.classList.toggle('neutral', gear === 'N');
    labels.forEach(l => l.classList.toggle('active', l.dataset.gear === gear));
    dots.forEach(c => c.classList.toggle('active', c.dataset.gear === gear));
    gearValueEl.textContent = gear;
    gearValueEl.classList.toggle('neutral', gear === 'N');
  }

  function adopt(pad, via) {
    activeIndex = pad.index;
    statusEl.textContent = pad.id.slice(0, 40);
    statusEl.classList.add('connected');
    // clean-slate rule: never trust the state Chrome hands us at adoption
    // (it can be a stale leftover from before the page loaded). Start at
    // neutral and show nothing until the lever actually moves.
    stateTrusted = false;
    adoptTimestamp = pad.timestamp;
    prevButtons = pad.buttons.map(b => b.pressed);
    sentGear = candidateGear = 'N';
    addEntry(via, `shifter detected`);
  }

  window.addEventListener('gamepadconnected', e => {
    if (activeIndex === null) adopt(e.gamepad, 'event');
    else addEntry('event', 'gamepad connected');
  });
  window.addEventListener('gamepaddisconnected', e => {
    addEntry('event', 'gamepad disconnected');
    if (e.gamepad.index === activeIndex) {
      activeIndex = null;
      statusEl.textContent = 'no gamepad';
      statusEl.classList.remove('connected');
      setDisplay('N');
    }
  });
  document.addEventListener('click', () => {
    for (const p of navigator.getGamepads()) if (p && activeIndex === null) { adopt(p, 'poll'); break; }
  });

  function currentGear(pad) {
    for (const [idx, gear] of Object.entries(MAPPING)) {
      if (pad.buttons[idx]?.pressed) return gear;
    }
    return 'N';
  }

  // ── arming: hold-to-fire gears ────────────────────────────────
  function startArming(gear, now) {
    armingGear = gear;
    armingStart = now;
    clearTimeout(armHideTimer);
    armPanel.classList.remove('cancelled');
    armTitle.textContent = ACTIONS[gear].label;
    armSub.textContent = `keep holding ${gearName(gear)}`;
    armMsg.textContent = '';
    armFill.style.width = '0%';
    rightCol.classList.add('arming');
  }

  function updateArming(now) {
    const holdMs = ACTIONS[armingGear].hold * 1000;
    const elapsed = now - armingStart;
    const progress = Math.min(1, elapsed / holdMs);
    armFill.style.width = (progress * 100) + '%';
    armCount.textContent = (Math.max(0, holdMs - elapsed) / 1000).toFixed(1) + 's';
    if (progress >= 1) {
      const gear = armingGear;
      armingGear = null;
      rightCol.classList.remove('arming');
      sentGear = gear;
      shift(gear);
    }
  }

  function cancelArming() {
    armingGear = null;
    armPanel.classList.add('cancelled');
    armMsg.textContent = 'released too early — nothing was triggered';
    armCount.textContent = '';
    armFill.style.width = '0%';
    clearTimeout(armHideTimer);
    armHideTimer = setTimeout(() => {
      rightCol.classList.remove('arming');
      armPanel.classList.remove('cancelled');
    }, 2500);
  }

  // ── send a stable gear to Laravel ─────────────────────────────
  async function shift(gear) {
    try {
      const res = await fetch('/gear', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': CSRF,
        },
        body: JSON.stringify({ gear }),
      });
      if (res.status === 409) {
        consoleBusy.textContent = 'already running — shift ignored';
        setTimeout(() => consoleBusy.textContent = '', 2500);
      }
    } catch (e) {
      addEntry('event', `<span class="up">could not reach Laravel: ${e.message}</span>`);
    }
  }

  // ── poll the run state back from Laravel ──────────────────────
  let renderedRunKey = null;
  let renderedLines = 0;

  function lineClass(line) {
    if (/FAIL|✗|⨯|Error|ERROR|Exception/.test(line)) return 'ln-fail';
    if (/PASS|✓|passed|\[OK\]/.test(line)) return 'ln-pass';
    if (line.startsWith('$')) return 'ln-cmd';
    return '';
  }

  function renderRun(run) {
    if (!run) {
      rightCol.classList.remove('has-run');
      return;
    }
    rightCol.classList.add('has-run');

    const key = run.started_at + run.gear;
    if (key !== renderedRunKey) {
      renderedRunKey = key;
      renderedLines = 0;
      consoleLog.innerHTML = '';
    }

    consoleTitle.textContent = run.label;
    consoleSub.textContent = gearName(run.gear);
    consolePill.textContent = run.status;
    consolePill.className = 'pill ' + run.status;
    consoleEl.className = 'panel st-' + run.status;

    if (run.lines.length < renderedLines) { renderedLines = 0; consoleLog.innerHTML = ''; }
    if (run.lines.length > renderedLines) {
      const nearBottom = consoleLog.scrollHeight - consoleLog.scrollTop - consoleLog.clientHeight < 60;
      for (let i = renderedLines; i < run.lines.length; i++) {
        const div = document.createElement('div');
        const cls = lineClass(run.lines[i]);
        if (cls) div.className = cls;
        div.textContent = run.lines[i] || ' ';
        consoleLog.appendChild(div);
      }
      renderedLines = run.lines.length;
      if (nearBottom) consoleLog.scrollTop = consoleLog.scrollHeight;
    }

    const start = new Date(run.started_at);
    const end = run.finished_at ? new Date(run.finished_at) : new Date();
    const secs = Math.max(0, Math.round((end - start) / 1000));
    consoleElapsed.textContent = secs >= 60
      ? `${Math.floor(secs/60)}m ${String(secs%60).padStart(2,'0')}s`
      : `${secs}s`;
  }

  async function poll() {
    try {
      const res = await fetch('/gear/status', { headers: { 'Accept': 'application/json' } });
      renderRun(await res.json());
    } catch (e) { /* server not up yet — keep quiet */ }
  }
  // clean slate on every page load: forget the previous run
  // (the backend keeps it if one is actually running right now)
  fetch('/gear/reset', {
    method: 'POST',
    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
  }).catch(() => {}).finally(() => {
    setInterval(poll, POLL_MS);
    poll();
  });

  // ── main loop ─────────────────────────────────────────────────
  function frame(now) {
    requestAnimationFrame(frame);
    if (activeIndex === null) {
      for (const p of navigator.getGamepads()) if (p) { adopt(p, 'poll'); break; }
      return;
    }
    const pad = navigator.getGamepads()[activeIndex];
    if (!pad) return;

    // show nothing until the device sends its first real report
    if (!stateTrusted) {
      if (pad.timestamp === adoptTimestamp) return;
      stateTrusted = true;
      prevButtons = pad.buttons.map(() => false); // log real state from here
    }

    pad.buttons.forEach((b, i) => {
      if (b.pressed !== prevButtons[i]) {
        if (b.pressed) {
          const g = MAPPING[i];
          addEntry('poll', g !== undefined
            ? `button ${i} pressed → <span class="gear">${gearName(g)}</span>`
            : `button ${i} pressed → not mapped`);
        } else {
          addEntry('poll', `<span class="up">button ${i} released → neutral</span>`);
        }
        prevButtons[i] = b.pressed;
      }
    });

    const gear = currentGear(pad);
    setDisplay(gear);

    // arming lifecycle: keep filling while held, cancel on release
    if (armingGear !== null) {
      if (gear === armingGear) updateArming(now);
      else cancelArming();
    }

    if (gear !== candidateGear) {
      candidateGear = gear;
      candidateSince = now;
    } else if (gear !== sentGear && armingGear === null && now - candidateSince >= DEBOUNCE_MS) {
      const action = ACTIONS[gear];
      if (gear !== 'N' && action && action.hold > 0) {
        startArming(gear, now);
      } else {
        sentGear = gear;
        if (gear !== 'N') shift(gear);
      }
    }
  }

  requestAnimationFrame(frame);
})();
</script>

</body>
</html>
