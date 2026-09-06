<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Intervia API</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: #0f0f11;
      color: #e5e5e5;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* ── Noise overlay ───────────────────────────────── */
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
      pointer-events: none;
      z-index: 0;
    }

    /* ── Gradient blob ───────────────────────────────── */
    .blob {
      position: fixed;
      width: 700px;
      height: 700px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, transparent 70%);
      top: -200px;
      left: 50%;
      transform: translateX(-50%);
      pointer-events: none;
      z-index: 0;
    }

    /* ── Layout ──────────────────────────────────────── */
    .container {
      position: relative;
      z-index: 1;
      max-width: 680px;
      margin: 0 auto;
      padding: 80px 24px 60px;
      flex: 1;
    }

    /* ── Header ──────────────────────────────────────── */
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(99,102,241,0.12);
      border: 1px solid rgba(99,102,241,0.3);
      color: #a5b4fc;
      font-size: 12px;
      font-weight: 500;
      padding: 4px 12px;
      border-radius: 999px;
      margin-bottom: 28px;
      letter-spacing: 0.02em;
    }

    .badge::before {
      content: '';
      width: 6px;
      height: 6px;
      background: #6ee7b7;
      border-radius: 50%;
      box-shadow: 0 0 6px #6ee7b7;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.4; }
    }

    h1 {
      font-size: clamp(36px, 6vw, 52px);
      font-weight: 700;
      line-height: 1.1;
      letter-spacing: -0.03em;
      margin-bottom: 16px;
    }

    h1 span {
      background: linear-gradient(135deg, #818cf8, #c084fc);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .subtitle {
      font-size: 17px;
      color: #9ca3af;
      line-height: 1.6;
      margin-bottom: 40px;
      max-width: 500px;
    }

    /* ── CTA buttons ─────────────────────────────────── */
    .cta-row {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      margin-bottom: 64px;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 11px 22px;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.15s;
      cursor: pointer;
    }

    .btn-primary {
      background: #6366f1;
      color: #fff;
      border: 1px solid transparent;
    }
    .btn-primary:hover { background: #4f52d6; transform: translateY(-1px); }

    .btn-outline {
      background: transparent;
      color: #e5e5e5;
      border: 1px solid rgba(255,255,255,0.15);
    }
    .btn-outline:hover { background: rgba(255,255,255,0.05); transform: translateY(-1px); }

    /* ── Endpoints card ──────────────────────────────── */
    .card {
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 16px;
      overflow: hidden;
      margin-bottom: 32px;
    }

    .card-header {
      padding: 16px 20px;
      border-bottom: 1px solid rgba(255,255,255,0.06);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .card-title {
      font-size: 13px;
      font-weight: 600;
      color: #9ca3af;
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }

    .endpoint-list {
      list-style: none;
    }

    .endpoint-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 20px;
      border-bottom: 1px solid rgba(255,255,255,0.04);
      font-size: 13px;
    }
    .endpoint-item:last-child { border-bottom: none; }

    .method {
      font-size: 11px;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 5px;
      min-width: 46px;
      text-align: center;
      letter-spacing: 0.03em;
    }
    .method.get    { background: rgba(52,211,153,0.15); color: #6ee7b7; }
    .method.post   { background: rgba(99,102,241,0.2);  color: #a5b4fc; }
    .method.delete { background: rgba(248,113,113,0.15); color: #fca5a5; }

    .path {
      font-family: 'SF Mono', 'Fira Code', monospace;
      color: #e5e5e5;
      flex: 1;
    }

    .desc {
      color: #6b7280;
      font-size: 12px;
    }

    /* ── Stats row ───────────────────────────────────── */
    .stats {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin-bottom: 32px;
    }

    .stat {
      background: rgba(255,255,255,0.03);
      border: 1px solid rgba(255,255,255,0.07);
      border-radius: 12px;
      padding: 18px 20px;
      text-align: center;
    }

    .stat-value {
      font-size: 28px;
      font-weight: 700;
      background: linear-gradient(135deg, #818cf8, #c084fc);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .stat-label {
      font-size: 12px;
      color: #6b7280;
      margin-top: 4px;
    }

    /* ── Stack badges ────────────────────────────────── */
    .stack {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 40px;
    }

    .tag {
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.08);
      color: #9ca3af;
      font-size: 12px;
      font-weight: 500;
      padding: 4px 12px;
      border-radius: 6px;
    }

    /* ── Footer ──────────────────────────────────────── */
    footer {
      position: relative;
      z-index: 1;
      text-align: center;
      padding: 20px;
      border-top: 1px solid rgba(255,255,255,0.06);
      font-size: 12px;
      color: #4b5563;
    }
  </style>
</head>
<body>
  <div class="blob"></div>

  <div class="container">

    <!-- Header -->
    <div class="badge">API Running</div>

    <h1>Intervia<br><span>Backend API</span></h1>
    <p class="subtitle">
      AI-powered interview preparation platform. REST API for the Intervia frontend application.
    </p>

    <div class="cta-row">
      <a href="/api/v1/positions" class="btn btn-primary">
        ↗ Try API
      </a>
      <a href="https://github.com" class="btn btn-outline">
        ⌥ Documentation
      </a>
    </div>

    <!-- Stats -->
    <div class="stats">
      <div class="stat">
        <div class="stat-value">31</div>
        <div class="stat-label">Endpoints</div>
      </div>
      <div class="stat">
        <div class="stat-value">6</div>
        <div class="stat-label">AI Services</div>
      </div>
      <div class="stat">
        <div class="stat-value">{{ $positions ?? 0 }}</div>
        <div class="stat-label">Positions</div>
      </div>
    </div>

    <!-- Key endpoints -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">Key Endpoints</span>
        <span style="font-size:12px;color:#6b7280;">Base: /api/v1</span>
      </div>
      <ul class="endpoint-list">
        <li class="endpoint-item">
          <span class="method post">POST</span>
          <span class="path">/auth/register</span>
          <span class="desc">Create account</span>
        </li>
        <li class="endpoint-item">
          <span class="method post">POST</span>
          <span class="path">/auth/login</span>
          <span class="desc">Get token</span>
        </li>
        <li class="endpoint-item">
          <span class="method get">GET</span>
          <span class="path">/positions</span>
          <span class="desc">Position library</span>
        </li>
        <li class="endpoint-item">
          <span class="method post">POST</span>
          <span class="path">/analyze-job</span>
          <span class="desc">AI job analysis</span>
        </li>
        <li class="endpoint-item">
          <span class="method post">POST</span>
          <span class="path">/sessions</span>
          <span class="desc">Start interview</span>
        </li>
        <li class="endpoint-item">
          <span class="method get">GET</span>
          <span class="path">/sessions/{id}/next-question</span>
          <span class="desc">Get AI question</span>
        </li>
        <li class="endpoint-item">
          <span class="method post">POST</span>
          <span class="path">/sessions/{id}/answer</span>
          <span class="desc">Submit & evaluate</span>
        </li>
        <li class="endpoint-item">
          <span class="method get">GET</span>
          <span class="path">/sessions/{id}/report</span>
          <span class="desc">Final report</span>
        </li>
        <li class="endpoint-item">
          <span class="method get">GET</span>
          <span class="path">/dashboard</span>
          <span class="desc">User dashboard</span>
        </li>
      </ul>
    </div>

    <!-- Stack -->
    <div class="stack">
      <span class="tag">Laravel 12</span>
      <span class="tag">PHP 8.2</span>
      <span class="tag">MySQL</span>
      <span class="tag">Sanctum</span>
      <span class="tag">OpenAI</span>
      <span class="tag">Gemini</span>
      <span class="tag">Prism PHP</span>
      <span class="tag">Queue</span>
    </div>

  </div>

  <footer>
    Intervia API &nbsp;·&nbsp; {{ config('app.env') }} &nbsp;·&nbsp; Laravel {{ app()->version() }}
  </footer>
</body>
</html>
