<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shift Happens</title>
<style>
  :root {
    --bg: #0d1117;
    --border: #2d333b;
    --text: #e6edf3;
    --muted: #8b949e;
    --accent: #f96854;
    --mono: ui-monospace, "SF Mono", "JetBrains Mono", Menlo, Consolas, monospace;
  }
  * { margin: 0; padding: 0; box-sizing: border-box; }
  html, body { height: 100%; }
  body {
    background: var(--bg);
    color: var(--text);
    font-family: var(--mono);
    display: flex;
    align-items: center;
    justify-content: center;
  }
  h1 {
    font-size: clamp(48px, 9vw, 120px);
    font-weight: 800;
    letter-spacing: 0.04em;
  }
  h1 span { color: var(--accent); }
  .next {
    position: fixed; top: 28px; right: 32px;
    font-size: 14px; color: var(--muted);
    text-decoration: none;
    border: 1px solid var(--border); border-radius: 999px;
    padding: 7px 16px;
    transition: color 0.15s, border-color 0.15s;
  }
  .next:hover { color: var(--text); border-color: var(--muted); }
</style>
</head>
<body>
<a class="next" href="{{ route('stage.debug') }}">next →</a>
<h1>SHIFT <span>HAPPENS</span></h1>
</body>
</html>
