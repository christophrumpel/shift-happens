<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shift Happens · Debug</title>
<style>
  :root {
    --bg: #0d1117;
    --panel: #161b22;
    --border: #2d333b;
    --text: #e6edf3;
    --muted: #8b949e;
    --accent: #f96854;
    --green: #3fb950;
    --blue: #58a6ff;
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
  .next {
    font-size: 14px; color: var(--muted); text-decoration: none;
    border: 1px solid var(--border); border-radius: 999px; padding: 7px 16px;
    transition: color 0.15s, border-color 0.15s;
  }
  .next:hover { color: var(--text); border-color: var(--muted); }

  #wake {
    position: fixed; inset: 0; background: rgba(13,17,23,0.96);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 14px; z-index: 10; text-align: center; padding: 20px;
  }
  #wake .big { font-size: 30px; font-weight: 700; }
  #wake .hint { color: var(--muted); font-size: 16px; }
  #wake.hidden { display: none; }

  .layout {
    flex: 1; min-height: 0;
    display: grid;
    grid-template-columns: 1.1fr 1fr;
    gap: 28px;
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

  /* ── left: current button + grid ── */
  #current {
    text-align: center;
    padding: 24px 0 32px;
  }
  #current .value {
    font-size: 130px; font-weight: 800; line-height: 1.05;
    color: var(--accent);
    font-variant-numeric: tabular-nums;
  }
  #current .value.idle { color: #2b323c; }
  #current .label {
    font-size: 14px; color: var(--muted);
    text-transform: uppercase; letter-spacing: 0.16em; margin-top: 6px;
  }

  #buttons {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-top: auto;
  }
  .btn {
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 18px 0;
    text-align: center;
    font-size: 30px;
    font-weight: 800;
    color: #4a5462;
    transition: background 0.05s, color 0.05s, border-color 0.05s;
  }
  .btn.on {
    background: var(--accent); border-color: var(--accent); color: #fff;
    box-shadow: 0 0 20px rgba(249,104,84,0.4);
  }

  #padInfo {
    margin-top: 24px; font-size: 13px; color: var(--muted);
    text-align: center; word-break: break-all;
  }

  /* ── right: event log ── */
  .log {
    flex: 1; min-height: 0; overflow-y: auto;
    display: flex; flex-direction: column; gap: 14px;
    scrollbar-width: none;
  }
  .log::-webkit-scrollbar { display: none; }
  .entry {
    display: flex; align-items: baseline; gap: 16px;
    animation: slideIn 0.15s ease-out;
  }
  @keyframes slideIn {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: none; }
  }
  .entry .t { color: var(--muted); font-size: 14px; flex-shrink: 0; }
  .entry .body { font-size: 24px; font-weight: 600; }
  .entry .body .down { color: var(--accent); }
  .entry .body .up { color: var(--muted); font-weight: 500; }
  .entry .body .sys { color: var(--blue); }
  .entry:not(:first-child) { opacity: 0.5; }
  .entry:first-child .body { font-size: 30px; font-weight: 700; }

  @media (max-width: 900px) { .layout { grid-template-columns: 1fr; } body { overflow: auto; } }
</style>
</head>
<body>

<div id="wake">
  <div class="big">🎮 Press a button on the shifter</div>
  <div class="hint">The browser hides gamepads until the device sends input.</div>
</div>

<header>
  <h1>SHIFTER <span>DEBUG</span></h1>
  <div style="display:flex; gap:14px; align-items:center">
    <div class="status" id="status">no gamepad</div>
    <a class="next" href="{{ route('shifter') }}">next →</a>
  </div>
</header>

<div class="layout">

  <div class="panel">
    <h2>Buttons</h2>
    <div id="current">
      <div class="value idle" id="currentValue">–</div>
      <div class="label">button pressed</div>
    </div>
    <div id="buttons"></div>
    <div id="padInfo"></div>
  </div>

  <div class="panel">
    <h2>Event log</h2>
    <div class="log" id="log"></div>
  </div>

</div>

<script>
(() => {
  const $ = (id) => document.getElementById(id);
  const statusEl = $('status'), wakeEl = $('wake'),
        currentEl = $('currentValue'), buttonsEl = $('buttons'),
        padInfoEl = $('padInfo'), logEl = $('log');

  let activeIndex = null;
  let prevButtons = [];
  let btnEls = [];

  const ts = () => {
    const d = new Date();
    return d.toTimeString().slice(0,8) + '.' + String(d.getMilliseconds()).padStart(3,'0');
  };

  function log(html) {
    const div = document.createElement('div');
    div.className = 'entry';
    div.innerHTML = `<span class="t">${ts()}</span><span class="body">${html}</span>`;
    logEl.prepend(div);
    while (logEl.children.length > 30) logEl.lastChild.remove();
  }

  function adopt(pad) {
    activeIndex = pad.index;
    prevButtons = pad.buttons.map(() => false);
    statusEl.textContent = 'connected';
    statusEl.classList.add('connected');
    wakeEl.classList.add('hidden');

    buttonsEl.innerHTML = '';
    btnEls = pad.buttons.map((_, i) => {
      const d = document.createElement('div');
      d.className = 'btn';
      d.textContent = i;
      buttonsEl.appendChild(d);
      return d;
    });

    padInfoEl.textContent = `${pad.id} (${pad.buttons.length} buttons)`;
    log(`<span class="sys">connected: gamepad found</span>`);
  }

  window.addEventListener('gamepadconnected', e => {
    if (activeIndex === null) adopt(e.gamepad);
  });
  window.addEventListener('gamepaddisconnected', e => {
    if (e.gamepad.index === activeIndex) {
      activeIndex = null;
      statusEl.textContent = 'no gamepad';
      statusEl.classList.remove('connected');
      log(`<span class="sys">disconnected</span>`);
    }
  });
  document.addEventListener('click', () => {
    for (const p of navigator.getGamepads()) if (p && activeIndex === null) { adopt(p); break; }
  });

  function frame() {
    requestAnimationFrame(frame);
    if (activeIndex === null) {
      for (const p of navigator.getGamepads()) if (p) { adopt(p); break; }
      return;
    }
    const pad = navigator.getGamepads()[activeIndex];
    if (!pad) return;

    let pressed = -1;

    pad.buttons.forEach((b, i) => {
      if (b.pressed && pressed === -1) pressed = i;
      if (btnEls[i]) btnEls[i].classList.toggle('on', b.pressed);
      if (b.pressed !== prevButtons[i]) {
        log(b.pressed
          ? `button ${i} <span class="down">▼ pressed</span>`
          : `button ${i} <span class="up">▲ released</span>`);
        prevButtons[i] = b.pressed;
      }
    });

    currentEl.textContent = pressed >= 0 ? pressed : '–';
    currentEl.classList.toggle('idle', pressed < 0);
  }

  requestAnimationFrame(frame);
})();
</script>

</body>
</html>
