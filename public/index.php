<?php
// public/index.php
require_once __DIR__ . '/../src/mailer.php';
?>
<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Lykkehjulet – Akari</title>
  <style>
    :root{
      --bg: #0b0f14;
      --panel: #121821;
      --accent: #3ddc97;
      --accent-2: #7dd3fc;
      /* Alternating slice colors */
      --slice-1: #f4fef8; /* lys grønn-hvit */
      --slice-2: #e9f7f0; /* svakt mørkere for tydelig annenhver */
      --text: #0b1b12;
      --ui: #e6edf3;
      --muted: #9fb0c1;
      --ring: #2ecf86;
    }
    html,body{height:100%;}
    body{margin:0; background: radial-gradient(1200px 800px at 70% 0%, #0e1621 0%, var(--bg) 60%); color:var(--ui); font: 16px/1.5 system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif; display:grid; place-items:center; padding:24px;}
    .app{width:min(1080px,100%);}    
  .header{display:flex; gap:16px; align-items:center; justify-content:center; flex-direction:column; margin-bottom:8px;}
  .header .logo-top{ width:420px; max-width:80%; height:auto; display:block; margin:12px 0 20px }
  .title{font-size:clamp(20px,2.6vw,28px); font-weight:700; text-align:center }
  .card{background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02)); border:1px solid rgba(255,255,255,0.08); border-radius:16px; box-shadow: 0 10px 30px rgba(0,0,0,.3); padding:24px; box-sizing:border-box }
  /* Ensure prize card content aligns visually with main controls/cards */
  #prizesCard{ padding:24px; box-sizing:border-box; width:100% }
  .wheel-wrap{display:grid; grid-template-columns: 1.15fr .85fr; gap:20px; align-items:stretch}
  @media (max-width:900px){.wheel-wrap{grid-template-columns:1fr;}}
    .stage{ position:relative; display:grid; place-items:center; padding:10px; }
    canvas{ width:100%; height:auto; display:block; max-width:680px; }
    .pointer{ position:absolute; top:10px; left:50%; transform: translateX(-50%); width:0; height:0; border-left:14px solid transparent; border-right:14px solid transparent; border-top:26px solid var(--accent); filter: drop-shadow(0 4px 8px rgba(0,0,0,.6));}
    .axle{ position:absolute; inset:0; display:grid; place-items:center; pointer-events:none;}
  .axle-dot{ width:120px; height:120px; border-radius:50%; background: #ffffffee; border: 8px solid rgba(0,0,0,.06); display:grid; place-items:center; overflow:hidden;}
  .axle-dot img, .axle-dot svg{ max-width:88%; max-height:88%; width:auto; height:auto; display:block; filter: none; object-fit:contain; }
    .controls{ display:flex; flex-direction:column; gap:14px; }
  /* Ensure the controls column card stretches to match the wheel height */
  .controls .card{ display:flex; flex-direction:column; height:100%; box-sizing:border-box }
  /* allow main .card content to grow and push result/footer to the bottom */
  .card > *{ flex-shrink:0 }
  .card .spacer{ flex:1 1 auto }
    .btn{ appearance:none; border:none; background:linear-gradient(180deg, var(--accent), #2ebc7d); color:#05250f; font-weight:800; letter-spacing:.3px; padding:14px 18px; border-radius:12px; cursor:pointer; box-shadow: 0 10px 18px rgba(61,220,151,.25), inset 0 -2px 0 rgba(0,0,0,.15); transition: transform .06s ease, filter .2s ease; font-size:16px;}
    .btn--sm{ padding:10px 14px; font-size:14px; box-shadow: 0 8px 14px rgba(61,220,151,.22), inset 0 -2px 0 rgba(0,0,0,.12); }
    .btn:active{ transform: translateY(2px); }
    .btn[disabled]{ opacity:.6; cursor:not-allowed; filter:grayscale(.3)}
    .result{ background: rgba(255,255,255,0.04); border:1px dashed rgba(255,255,255,0.18); border-radius:12px; padding:18px; margin-top:16px; }
    .result h3{ margin:.2rem 0 .4rem; font-size:18px }
    .result p{ margin:0; color:var(--ui) }
  /* Result description styling: match spacing of surrounding title and button */
  #resultDescription{ color:#a0b0c1; font-size:14px; display:block; margin:.2rem 0 .6rem; }
    .chip{ display:inline-flex; align-items:center; gap:8px; padding:8px 10px; border-radius:999px; background:rgba(125,211,252,.12); border:1px solid rgba(125,211,252,.25); font-size:13px }
  .special-legend{ display:flex; gap:12px; align-items:center; padding:12px; border-radius:10px; background:linear-gradient(180deg, rgba(175,137,19,0.08), rgba(175,137,19,0.03)); border:1px solid rgba(175,137,19,0.18); color:#fff; margin-bottom:12px }
  .special-legend .icon{ width:48px; height:48px; flex:0 0 48px; display:grid; place-items:center; border-radius:8px; background:#af8913 }
  .special-legend .icon svg{ width:28px; height:28px; fill:#fff }
  .special-legend .icon img{ width:28px; height:28px; object-fit:contain; display:block }
  .special-legend .meta{ display:flex; flex-direction:column }
  .special-legend .meta .label{ font-size:12px; color:#f5edd8 }
  .special-legend .meta .title{ font-weight:700; color:#fff }
  /* Prize list items: resemble special-legend but without gold background */
  /* Distribute prize chips horizontally and let them expand to fill the row */
  /* Prize list: single row, evenly distributed chips */
  #prizeList{ list-style:none; margin:0; padding:0; display:flex; gap:12px; flex-wrap:nowrap; align-items:center; width:100%; justify-content:space-between }
  /* make four equal columns across the row */
  .prize-item{ display:inline-flex; gap:10px; align-items:center; padding:8px 12px; border-radius:10px; background: rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.04); min-height:48px; flex:1 1 0; box-sizing:border-box; justify-content:flex-start; min-width:0 }
  @media (max-width:720px){
    #prizeList{ flex-wrap:wrap; }
    .prize-item{ flex:1 1 calc(50% - 8px); }
  }
  .prize-item .icon{ width:40px; height:40px; flex:0 0 40px; display:grid; place-items:center; border-radius:8px; background: rgba(255,255,255,0.02); color:var(--accent) }
  .prize-item .icon svg{ width:22px; height:22px; display:block }
  .prize-item .icon .prize-emoji{ font-size:20px; line-height:1 }
  .prize-item .meta{ display:flex; flex-direction:column }
  .prize-item .meta .title{ font-weight:700; color:var(--ui); font-size:13px }
  .prize-item .meta .label{ font-size:12px; color:var(--muted) }
    .footer{ margin-top:14px; color:var(--muted); font-size:13px }
    .toast{ position:fixed; inset:auto 16px 16px auto; background:#0f1720; color:var(--ui); border:1px solid rgba(255,255,255,.12); padding:10px 12px; border-radius:10px; opacity:0; transform: translateY(8px); transition: all .25s ease }
    .toast.show{ opacity:1; transform: translateY(0) }
   /* mini konfetti: pieces will be positioned at wheel center and animated via JS for
     better control. Keep styles minimal so a CSS fallback still looks okay. */
   .confetti{ position:absolute; inset:0; pointer-events:none; overflow:visible; }
   .confetti i{ position:absolute; width:6px; height:10px; border-radius:2px; background:var(--accent); will-change: transform, opacity; }
   .confetti i:nth-child(odd){ background:var(--accent-2) }
    /* form */
    form{ display:grid; gap:10px; margin-top:12px }
    label{ font-size:13px; color:var(--muted)}
  input, textarea, select{ width:100%; padding:10px 12px; border-radius:10px; border:1px solid rgba(255,255,255,.2); background:rgba(255,255,255,.06); color:var(--ui); box-sizing:border-box; max-width:100% }
    .success{ color:#a7f3d0; font-size:14px; }
  /* Debug bar (hidden by default; small helper for QA) */
  .debug-bar{ position:fixed; left:12px; right:12px; bottom:12px; display:flex; gap:8px; align-items:center; justify-content:flex-end; pointer-events:auto; z-index:9999 }
  .debug-bar .dbg{ background: rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.06); padding:8px 10px; border-radius:10px; color:var(--ui); font-size:13px }
  .debug-bar .dbg button{ appearance:none; border:none; padding:8px 10px; border-radius:8px; cursor:pointer; font-weight:700 }
  .debug-bar .dbg .spin{ background:linear-gradient(180deg,var(--accent),#2ebc7d); color:#05250f }
  .debug-bar .dbg .force{ background:#af8913; color:#fff }
  </style>
</head>
<body>
  <div class="app">
    <div class="header">
  <img src="Akari_jubileum_mint+turquoise_cropped.png" alt="Akari jubileumslogo" class="logo-top" onerror="this.onerror=null; this.src='Akari_Logo.png'" />
    </div>

    <div class="wheel-wrap">
      <div class="card stage">
        <div class="pointer" aria-hidden="true"></div>
        <canvas id="wheel" width="800" height="800" aria-label="Lykkehjul"></canvas>
        <div class="axle" aria-hidden="true">
          <div class="axle-dot">
            <img id="akariLogo" alt="Akari logo" src="Akari_Logo.png" onerror="this.style.display='none'">
          </div>
        </div>
        <div class="confetti" id="confetti" aria-hidden="true"></div>
      </div>

      <div class="controls">
        <div class="card">
          <h2 style="margin:.2rem 0 .3rem; font-size:20px">Spinn og vinn</h2>
          <p style="margin:0 0 12px; color:var(--muted)">Snurr hjulet og vinn 15 % rabatt på valgfri tjeneste, gratis og uforpliktende rådgivningstime eller stikk av med et gavekort.</p>

          <!-- Forhåndsskjema: fylles ut før man kan spinne (ingen ekstra knapp) -->
          <div id="preFormBox">
            <p style="margin:.2rem 0 .6rem; color:var(--muted)">Fyll inn bedriftsnavn, telefon og e‑post for å spille.</p>
            <form id="preForm">
              <div>
                <label for="company">Bedriftsnavn</label>
                <input id="company" name="company" required placeholder="Firmanavn AS" />
              </div>
              <div>
                <label for="preEmail">E‑post</label>
                <input id="preEmail" name="email" type="email" required placeholder="deg@firma.no" />
              </div>
              <div>
                <label for="prePhone">Telefon</label>
                <input id="prePhone" name="phone" inputmode="tel" pattern="[0-9 +()-]{5,}" required placeholder="+47 9x xx xx xx" />
              </div>
            </form>
          </div>

          <button id="spinBtn" class="btn" disabled style="margin:16px 0 14px">SPINN</button>

          <!-- spacer pushes result/footer to the bottom when card stretches -->
          <div class="spacer" aria-hidden="true"></div>

          <div class="result" id="resultBox" style="display:none">
            <h3 id="resultTitle">Gratulerer! Du vant:</h3>
            <p id="resultText"></p> 
            <p id="resultDescription"><i>Kupongen er sendt på e-post. En fra Akari vil kontakte deg fortløpende for å avtale et uforpliktende møte.</i></p>
            <div style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap">
              <button class="btn btn--sm" id="restartBtn" title="Spinn på nytt">SPINN PÅ NYTT</button>
            </div>
          </div>

          <p class="footer"><i>Ved å spinne hjulet aksepterer du at Akari kan sende deg premien på e-post og kontakte deg for å avtale et uforpliktende møte.</i></p>
        </div>
      </div>
    </div>
    <!-- Prizes card: moved special legend and prize list -->
    <div style="margin-top:18px;">
      <div class="card" id="prizesCard">
        <h3 style="margin:.2rem 0 .6rem;">Premier</h3>
        <div class="special-legend" role="note" aria-label="The Special Slice">
          <div class="icon" aria-hidden="true">
            <img src="honefosskortet.png" alt="Hønefosskortet" />
          </div>
          <div class="meta">
            <div class="label">Hønefosskortet</div>
            <div class="title">Gavekort på 1 000 kr</div>
          </div>
        </div>
        <ul id="prizeList" style="margin:0; padding:0; color:var(--ui)"></ul>
      </div>
    </div>

  </div>

  <div class="toast" id="toast" role="status" aria-live="polite">Kopiert til utklippstavlen</div>

  <!-- Debug bar for quick QA: Spin and Force Special -->
  <div class="debug-bar" id="debugBar" aria-hidden="false">
    <div class="dbg">
      <button id="dbgSpin" class="spin">Spin wheel</button>
      <button id="dbgForce" class="force">Force special</button>
    </div>
  </div>

  <script>
    const outcomes = [
      "-15 % på fotografering",
      "Gratis rådgivning",
      "-15 % på design",
      "Gratis rådgivning",
      "-15 % på video",
      "Gratis rådgivning",
      "Gavekort på 1000 kr" // sjeldent 7. alternativ
    ];

    const canvas = document.getElementById('wheel');
    const ctx = canvas.getContext('2d');
    const size = canvas.width;
    const center = size/2;
    const radius = center - 10;
    const sliceCount = outcomes.length;

  // Special tiny chance: 1 in 1,000,000,000 for the last outcome (selection only)
  const SPECIAL_CHANCE = 1 / 1000000000;

    // Compute per-slice weights and derived angles (angles sum to 2π)
    function computeSlices(){
      const weights = new Array(sliceCount).fill(0).map((_,i)=>{
        return (i === sliceCount - 1) ? SPECIAL_CHANCE : (1 - SPECIAL_CHANCE) / (sliceCount - 1);
      });
  const total = weights.reduce((s,v)=>s+v,0);
  const angles = weights.map(w => (w/total) * Math.PI * 2);
  const starts = [];
  let acc = 0;
  for (let a of angles){ starts.push(acc); acc += a; }

  // For rendering we want equal visual wedges so the UI suggests equal
  // opportunity. Selection still uses `weights`/`angles` above.
  const equal = (Math.PI * 2) / sliceCount;
  const visualAngles = new Array(sliceCount).fill(equal);
  const visualStarts = [];
  acc = 0;
  for (let a of visualAngles){ visualStarts.push(acc); acc += a; }

  return { weights, angles, starts, visualAngles, visualStarts };
    }

    let currentRotation = -Math.PI/2; // slik at index 0 starter øverst
    let isSpinning = false;
    let winnerIndex = null;

    function drawWheel(rotation=0, highlightIndex=null){
      const { angles, starts, visualAngles, visualStarts } = computeSlices();
      const specialIdx = sliceCount - 1;
      const specialCenter = visualStarts[specialIdx] + visualAngles[specialIdx]/2;

      ctx.clearRect(0,0,size,size);
      ctx.save();
      ctx.translate(center, center);
      ctx.rotate(rotation); // hele hjulet roterer med klokka

      // Draw non-special slices first
      for (let i=0; i<sliceCount; i++){
        if (i === specialIdx) continue;
        const start = visualStarts[i];
        const end = start + visualAngles[i];
        // sektor
        ctx.beginPath();
        ctx.moveTo(0,0);
        ctx.arc(0,0,radius,start,end);
        ctx.closePath();
        const c1 = getComputedStyle(document.documentElement).getPropertyValue('--slice-1').trim();
        const c2 = getComputedStyle(document.documentElement).getPropertyValue('--slice-2').trim();
        ctx.fillStyle = (i % 2 === 0) ? c1 : c2;
        ctx.fill();
        ctx.strokeStyle = 'rgba(0,0,0,0.08)';
        ctx.lineWidth = 2;
        ctx.stroke();

        // separators
        ctx.save();
        ctx.beginPath();
        const sx = Math.cos(start) * (radius + 2);
        const sy = Math.sin(start) * (radius + 2);
        ctx.moveTo(0,0);
        ctx.lineTo(sx, sy);
        ctx.strokeStyle = 'rgba(0,0,0,0.08)';
        ctx.lineWidth = 2;
        ctx.stroke();
        ctx.restore();

        // winner marker
        if (highlightIndex === i){
          ctx.save();
          ctx.beginPath();
          ctx.arc(0,0,radius-6,start+0.02,end-0.02);
          ctx.lineWidth = 10;
          ctx.strokeStyle = 'rgba(46,207,134,.9)';
          ctx.stroke();
          ctx.restore();
        }

        // text
        const label = outcomes[i];
        const a = start + visualAngles[i]/2;
        const textR = radius * 0.64;
        const worldA = a + rotation;
        const tx = Math.cos(worldA) * textR;
        const ty = Math.sin(worldA) * textR;
        ctx.save();
        ctx.rotate(-rotation);
        ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--text').trim();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.font = 'bold 22px system-ui, -apple-system, Segoe UI, Inter, Roboto, Arial';
        // skip label if it's too narrow or too close angularly to the special slice
        const angularDistance = Math.abs(((a - specialCenter + Math.PI) % (Math.PI*2)) - Math.PI);
        const specialHalf = visualAngles[specialIdx] / 2;
        if (visualAngles[i] >= 0.12 && angularDistance > (specialHalf + 0.08)){
          wrapText(ctx, label, tx, ty, radius*0.34, 3);
        }
        ctx.restore();
      }

      // Draw the special slice on top so it's always visible
      const sStart = visualStarts[specialIdx];
      const sEnd = sStart + visualAngles[specialIdx];
      // Create a realistic gold gradient for the special slice.
      // We'll use a radial gradient centered slightly towards the top-left
      // of the slice to simulate a light source and give a metallic feel.
      const grad = ctx.createRadialGradient(
        -radius*0.2, -radius*0.25, radius*0.05,
        0, 0, radius
      );
      // gradient stops chosen to mimic warm gold
      grad.addColorStop(0, '#fff9e6'); // very bright highlight
      grad.addColorStop(0.18, '#ffe599');
      grad.addColorStop(0.45, '#f2c86b');
      grad.addColorStop(0.7, '#b07a1a');
      grad.addColorStop(1, '#80550f');

      ctx.beginPath();
      ctx.moveTo(0,0);
      ctx.arc(0,0,radius,sStart,sEnd);
      ctx.closePath();
      ctx.fillStyle = grad;
      ctx.fill();
      // subtle darker stroke to separate from neighbors
      ctx.strokeStyle = 'rgba(0,0,0,0.22)';
      ctx.lineWidth = 2;
      ctx.stroke();

      // draw separators for special slice
      ctx.save();
      ctx.beginPath();
      const ssx = Math.cos(sStart) * (radius + 2);
      const ssy = Math.sin(sStart) * (radius + 2);
      ctx.moveTo(0,0);
      ctx.lineTo(ssx, ssy);
      ctx.strokeStyle = 'rgba(0,0,0,0.18)';
      ctx.lineWidth = 2;
      ctx.stroke();
      ctx.beginPath();
      const sex = Math.cos(sEnd) * (radius + 2);
      const sey = Math.sin(sEnd) * (radius + 2);
      ctx.moveTo(0,0);
      ctx.lineTo(sex, sey);
      ctx.stroke();
      ctx.restore();

      // winner marker for special
      if (highlightIndex === specialIdx){
        ctx.save();
        ctx.beginPath();
        ctx.arc(0,0,radius-6,sStart+0.02,sEnd-0.02);
        ctx.lineWidth = 10;
        ctx.strokeStyle = 'rgba(46,207,134,.9)';
        ctx.stroke();
        ctx.restore();
      }

      // Draw label for the special slice using the same placement/size as others
      const label = outcomes[specialIdx];
      const a = sStart + visualAngles[specialIdx]/2;
      const textR = radius * 0.64;
      const worldA = a + rotation;
      const tx = Math.cos(worldA) * textR;
      const ty = Math.sin(worldA) * textR;
  ctx.save();
  ctx.rotate(-rotation);
  // Draw readable overlay text: light fill with dark stroke for contrast
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.font = 'bold 22px system-ui, -apple-system, Segoe UI, Inter, Roboto, Arial';
  // stroke (outline) first for contrast
  ctx.lineWidth = 4;
  ctx.strokeStyle = 'rgba(0,0,0,0.65)';
  // draw each wrapped line manually so stroke + fill align
  (function drawWrapped(){
    const words = label.split(' ');
    let line = '';
    let lineCount = 0;
    const maxWidth = radius*0.34;
    const linesMax = 3;
    const lineHeight = 24;
    const collected = [];
    for (let n=0; n<words.length; n++){
      const testLine = line + words[n] + ' ';
      const metrics = ctx.measureText(testLine);
      if (metrics.width > maxWidth && n>0){
        collected.push(line.trim());
        line = words[n] + ' ';
        lineCount++;
        if (lineCount >= linesMax-1) break;
      } else {
        line = testLine;
      }
    }
    collected.push(line.trim());
    // stroke each line
    for (let i=0;i<collected.length;i++){
      ctx.strokeText(collected[i], tx, ty + i*lineHeight);
    }
    // fill on top
    ctx.fillStyle = '#fff9e6'; // warm light fill that reads on gold
    for (let i=0;i<collected.length;i++){
      ctx.fillText(collected[i], tx, ty + i*lineHeight);
    }
  })();
  ctx.restore();

      // outer ring
      ctx.beginPath();
      ctx.arc(0,0,radius+2,0,Math.PI*2);
      ctx.lineWidth = 6;
      ctx.strokeStyle = 'rgba(255,255,255,0.2)';
      ctx.stroke();

      ctx.restore();

      // Keep the pointer visually locked to the top-center of the canvas.
      // Call named helper (defined below) so other actions (showResult) can
      // re-position the pointer after layout changes.
      try { positionPointer(); } catch(e){ /* ignore */ }
    }

    // Position the pointer DOM element above the canvas so it remains visually
    // locked as page layout changes.
    function positionPointer(){
      const pointerEl = document.querySelector('.pointer');
      const stageEl = document.querySelector('.stage');
      if (!pointerEl || !stageEl) return;
      const canvasRect = canvas.getBoundingClientRect();
      const stageRect = stageEl.getBoundingClientRect();
      // center horizontally over the canvas
      const left = (canvasRect.left + canvasRect.right) / 2 - stageRect.left;
      // slightly above the canvas top so the triangle tip sits over the rim
      const top = canvasRect.top - stageRect.top - 8;
      pointerEl.style.left = left + 'px';
      pointerEl.style.top = top + 'px';
      pointerEl.style.transform = 'translateX(-50%)';
    }

    function wrapText(context, text, x, y, maxWidth, lines){
      const words = text.split(' ');
      let line = '';
      let lineCount = 0;
      for (let n=0; n<words.length; n++){
        const testLine = line + words[n] + ' ';
        const metrics = context.measureText(testLine);
        if (metrics.width > maxWidth && n>0){
          context.fillText(line.trim(), x, y + lineCount*24);
          line = words[n] + ' ';
          lineCount++;
          if (lineCount >= lines-1) break;
        } else {
          line = testLine;
        }
      }
      context.fillText(line.trim(), x, y + lineCount*24);
    }

    function easeOutQuint(t){ return 1 - Math.pow(1 - t, 5); } // mer «spennende» mot slutten

    async function spin(){
      if (isSpinning) return;
      // Krev gyldig og ulåst skjema før første spinn
      if (!formLocked){
        validate();
        if (!canSpin){ return; }
      }

      isSpinning = true;
      toggleControls(true);

      // Pick winner by weighted random using the same weights as computeSlices()
      const { weights, angles, starts, visualAngles, visualStarts } = computeSlices();
      // Allow debug forcing of the special slice
      if (window.DebugWheel && window.DebugWheel.forceSpecial){
        winnerIndex = sliceCount - 1;
      } else {
        const totalW = weights.reduce((s,v)=>s+v,0);
        let r = Math.random() * totalW;
        let acc = 0;
        winnerIndex = 0;
        for (let i=0;i<weights.length;i++){
          acc += weights[i];
          if (r <= acc){ winnerIndex = i; break; }
        }
      }
      
      // Først NÅ, etter at vinner er bestemt, sender vi data til backend
      if (!formLocked) {
        const prize = outcomes[winnerIndex];
        const success = await lockAndSendForm(prize);
        if (!success) {
          isSpinning = false;
          toggleControls(false);
          toast('Innsending feilet. Prøv igjen.');
          return;
        }
      }


  // For animation we want the visual wedge corresponding to the chosen
  // winnerIndex to line up with the pointer. Selection uses `weights` but
  // visual placement uses `visualStarts`/`visualAngles`.
  // Choose a random angle inside the visual slice (not always the center)
  // so the pointer can stop anywhere on the slice and feel more random.
  const sStart = visualStarts[winnerIndex];
  const sAngle = visualAngles[winnerIndex];
  // pad a little from the slice edges so we don't land on the boundary
  const padding = Math.min(sAngle * 0.12, 0.08);
  const inner = Math.max(0, sAngle - 2 * padding);
  const targetSliceCenter = sStart + padding + (Math.random() * inner);
      // Flere runder for mer spenning, alltid MED klokka
      const turns = 10 + Math.floor(Math.random()*3); // 10–12 fulle runder
      const spinDelta = (-Math.PI/2 - currentRotation - targetSliceCenter) + turns * (Math.PI*2);
      const duration = 5200 + Math.random()*1200; // lengre

      const start = performance.now();
      const startRot = currentRotation;

      (function animate(now){
        const t = Math.min(1, (now - start) / duration);
        const eased = easeOutQuint(t);
        currentRotation = startRot + spinDelta * eased;
        drawWheel(currentRotation);
        if (t < 1){ requestAnimationFrame(animate); }
        else {
          // ensure currentRotation is set to final and normalized to avoid huge numbers
          currentRotation = startRot + spinDelta * eased;
          // normalize to range [-PI, PI) to keep values stable
          const mod = (n, m) => ((n % m) + m) % m;
          currentRotation = mod(currentRotation + Math.PI, Math.PI * 2) - Math.PI;

          drawWheel(currentRotation, winnerIndex);
          isSpinning = false;
          // preserve scroll position to avoid the browser auto-scrolling when result is shown
          const prevScroll = window.scrollY || window.pageYOffset;
          showResult(outcomes[winnerIndex]);
          // restore immediately after layout so view doesn't jump, then re-position pointer
          setTimeout(()=>{ window.scrollTo(0, prevScroll); try{ positionPointer(); }catch(e){} }, 10);
          try{ positionPointer(); }catch(e){}
          fireConfetti();
          toggleControls(false);
        }
      })(performance.now());
    }

    function toggleControls(spinning){
      document.getElementById('spinBtn').disabled = spinning || !canSpin;
    }

    function showResult(text){
      const box = document.getElementById('resultBox');
      document.getElementById('resultText').textContent = text;
      box.style.display = 'block';
      // Etter gevinst: ikke tillat ny spinn før restart
      canSpin = false;
      document.getElementById('spinBtn').disabled = true;
    }


    function toast(msg){
      const el = document.getElementById('toast');
      el.textContent = msg; el.classList.add('show');
      setTimeout(()=> el.classList.remove('show'), 1600);
    }

    function fireConfetti(){
      const holder = document.getElementById('confetti');
      // clean previous
      holder.innerHTML = '';

  // Determine the origin above the pointer (top arrow). We'll compute
  // the pointer element's tip coordinates and spawn confetti slightly above it.
  const pointerEl = document.querySelector('.pointer');
  const pointerRect = pointerEl.getBoundingClientRect();
  const parentRect = holder.getBoundingClientRect();
  // pointer tip is centered horizontally at pointerRect.left + width/2, and at pointerRect.top
  const cx = (pointerRect.left + pointerRect.right) / 2 - parentRect.left;
  // spawn a bit above the tip so pieces appear to fall over the pointer
  const cy = pointerRect.top - parentRect.top - 8; // 8px above the tip

      const pieces = 80;
      const animations = [];
      for (let i = 0; i < pieces; i++){
        const el = document.createElement('i');
        // start at wheel center
        el.style.left = cx + 'px';
        el.style.top = cy + 'px';
        // random size and rotation
        const w = 5 + Math.random()*6;
        const h = 8 + Math.random()*10;
        el.style.width = w + 'px';
        el.style.height = h + 'px';
        el.style.borderRadius = (Math.random()*3) + 'px';
        el.style.opacity = '1';
        // color variation
        if (Math.random() < 0.5) el.style.background = getComputedStyle(document.documentElement).getPropertyValue('--accent').trim();
        else el.style.background = getComputedStyle(document.documentElement).getPropertyValue('--accent-2').trim();

        holder.appendChild(el);

        // random trajectory
        const angle = (-90 + (Math.random()*120 - 60)) * (Math.PI/180); // mostly downward ±60deg
        const distance = 120 + Math.random()*240; // px
        const dx = Math.cos(angle) * distance;
        const dy = Math.sin(angle) * distance;
        const rot = (Math.random()*720 - 360);
        const duration = 1000 + Math.random()*900;

        const keyframes = [
          { transform: `translate(0px,0px) rotate(${Math.random()*360}deg)`, opacity: 1 },
          { transform: `translate(${dx/2}px,${dy/2}px) rotate(${rot/2}deg)`, opacity: 1, offset: 0.6 },
          { transform: `translate(${dx}px,${dy}px) rotate(${rot}deg)`, opacity: 0 }
        ];

        const anim = el.animate(keyframes, {
          duration: duration,
          easing: 'cubic-bezier(.18,.9,.2,1)',
          delay: Math.random()*200
        });

        animations.push(anim);
        // cleanup when done
        anim.onfinish = () => { if (el && el.parentNode) el.parentNode.removeChild(el); };
      }

      // safety: if animations are still pending, clear after 3s
      setTimeout(()=>{ if (holder) holder.innerHTML = ''; }, 3000);
    }

    // Pre-skjema må fylles før spill
    let preData = null;
    let canSpin = false;
    let formLocked = false;
    const preForm = document.getElementById('preForm');
    const inputs = [document.getElementById('company'), document.getElementById('preEmail'), document.getElementById('prePhone')];

    function validate(){
      const [company, email, phone] = inputs;
      const ok = company.value.trim().length > 1 && email.validity.valid && (phone.value.trim().length >= 5) && new RegExp(phone.pattern).test(phone.value);
      canSpin = ok && !formLocked;
      document.getElementById('spinBtn').disabled = !canSpin;
    }
    inputs.forEach(el=> el.addEventListener('input', validate));
    validate();

    // Når vi spinner første gang, lås skjema og send data
    async function lockAndSendForm(prize) {
      if (formLocked) return true; // Ikke send på nytt
      
      preData = Object.fromEntries(new FormData(preForm).entries());
      preData.prize = prize; // Legg til premien i dataene

      try {
        const response = await fetch(window.location.href, { // Send til samme side
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(preData)
        });

        if (!response.ok) {
          console.error('Feil ved sending av data til server.');
          return false;
        }
        
        const result = await response.json();
        if (result.success) {
          inputs.forEach(el => el.disabled = true);
          formLocked = true;
          validate();
          return true;
        } else {
          return false;
        }
      } catch (error) {
        console.error('Nettverksfeil:', error);
        return false;
      }
    }

    // Restart flyt: nullstill alt (helt fresh start)
    function restart(){
      preData = null; canSpin = false; isSpinning = false; winnerIndex = null; currentRotation = -Math.PI/2; formLocked = false;
      document.getElementById('resultBox').style.display = 'none';
      preForm.reset();
      inputs.forEach(el=> { el.disabled = false; });
      document.getElementById('spinBtn').disabled = true;
      drawWheel(currentRotation);
      window.scrollTo({top:0, behavior:'smooth'});
    }

    document.getElementById('restartBtn').addEventListener('click', restart);

    // Eventer
    document.getElementById('spinBtn').addEventListener('click', spin);
  // --- Debug helpers ---
  window.DebugWheel = window.DebugWheel || { forceSpecial: false };
    document.getElementById('dbgSpin').addEventListener('click', ()=>{
      // unlock form for debug and set sample data so Spin can run
      if (!formLocked){
        document.getElementById('company').value = 'Debug AS';
        document.getElementById('preEmail').value = 'debug@local';
        document.getElementById('prePhone').value = '+4790000000';
        inputs.forEach(el=> el.dispatchEvent(new Event('input', {bubbles:true}))); 
      }
      // dispatch real click
      document.getElementById('spinBtn').click();
    });
    document.getElementById('dbgForce').addEventListener('click', (e)=>{
      DebugWheel.forceSpecial = !DebugWheel.forceSpecial;
      e.currentTarget.textContent = DebugWheel.forceSpecial ? 'Force special: ON' : 'Force special';
      e.currentTarget.style.opacity = DebugWheel.forceSpecial ? '1' : '0.9';
    });
        
    // Førstetegning
    drawWheel(currentRotation);
    // Populate prize list box
    function renderPrizes(){
      const ul = document.getElementById('prizeList');
      if (!ul) return;
      ul.innerHTML = '';
      // Define the visible prize entries we want (in this order)
      const entries = [
        { title: '-15 % på fotografering', note: ''},
        { title: '-15 % på design', note: ''},
        { title: '-15 % på video', note: ''},
        { title: 'Gratis rådgivning', note: ''}
      ];

      entries.forEach((e, idx)=>{
        const li = document.createElement('li');
        li.className = 'prize-item';

        const icon = document.createElement('div');
        icon.className = 'icon';
        const emoji = document.createElement('div');
        emoji.className = 'prize-emoji';
  // choose meaningful emoji/icon per prize
  if (e.title.includes('fotografering')) emoji.textContent = '📷';
  else if (e.title.includes('design')) emoji.textContent = '🎨';
  else if (e.title.includes('video')) emoji.textContent = '🎬';
  else emoji.textContent = '💬';
        icon.appendChild(emoji);

        const meta = document.createElement('div'); meta.className = 'meta';
        const title = document.createElement('div'); title.className = 'title'; title.textContent = e.title;
        const label = document.createElement('div'); label.className = 'label'; label.textContent = e.note;
        meta.appendChild(title); meta.appendChild(label);

        li.appendChild(icon);
        li.appendChild(meta);
        ul.appendChild(li);
      });
    }
    renderPrizes();

    // --- SIKRERE API-EKSPORT (unngå LHS-feil) ---
    (function(global){
      const api = global.WheelAPI || {};
      Object.defineProperty(api, 'currentWinner', { get(){ return (winnerIndex!=null) ? outcomes[winnerIndex] : null; } });
      api.spin = spin;
  api.setOutcomes = function(list){ if(Array.isArray(list) && list.length===outcomes.length){ list.forEach((v,i)=> outcomes[i]=String(v)); drawWheel(currentRotation); } };
      global.WheelAPI = api;
    })(window);

    // --- ENKLE TESTER (kjør med ?test=1 i URL) ---
    function runTests(){
      console.group('%cLykkehjulet – tester','color:#3ddc97');
      try {
        console.assert(document.getElementById('spinBtn').disabled === true, 'Spin skal være deaktivert før feltene er gyldige');
        console.assert(Array.isArray(outcomes) && outcomes.length===6, 'Outcomes skal ha 6 elementer');
        try{ drawWheel(currentRotation); console.log('drawWheel OK'); }catch(e){ console.error('drawWheel feilet', e); }
        console.assert(typeof window.WheelAPI === 'object', 'WheelAPI finnes på window');
        console.assert(typeof window.WheelAPI.spin === 'function', 'WheelAPI.spin finnes');
        const before0 = outcomes[0];
        window.WheelAPI.setOutcomes(['a']);
        console.assert(outcomes[0] === before0, 'setOutcomes med feil lengde skal ikke endre noe');
        // Fyll inn felter og sjekk at Spin blir aktiv
        document.getElementById('company').value = 'Test AS';
        document.getElementById('preEmail').value = 'test@firma.no';
        document.getElementById('prePhone').value = '+4790000000';
        inputs.forEach(el=> el.dispatchEvent(new Event('input', {bubbles:true})));
        console.assert(document.getElementById('spinBtn').disabled === false, 'Spin skal bli aktiv når feltene er gyldige');
        console.log('Alle tester fullført');
      } finally {
        console.groupEnd();
      }
    }
    if (new URLSearchParams(location.search).get('test') === '1') runTests();
  </script>
</body>
</html>