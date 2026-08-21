<style>
/* Chart tokens. Both sets validated against the lightness band, chroma floor, CVD
   separation, normal-vision floor and surface contrast — light on #FFFFFF, dark on
   #141A21. Dark mode is its own selection, not an automatic flip of the light values. */
.mgmt {
  --sv1: #0891B2;  /* cyan   — the brand hue */
  --sv2: #B45309;  /* amber  — sits between the brand hues so adjacent CVD separation holds */
  --sv3: #C026D3;  /* magenta */
  --chart-grid: var(--border);
  --chart-surface: var(--surface);
}
[data-theme="dark"] .mgmt { --sv1:#17A3BC; --sv2:#A9690D; --sv3:#C048C0; }

.mgmt .uchart { display:block; overflow:visible }
.mgmt .uchart .grid { stroke: var(--chart-grid); stroke-width:1 }
.mgmt .uchart .axis { font-size:10px; fill: var(--text-3); font-family: var(--font-2) }
.mgmt .uchart .mark.sv1, .mgmt .uchart .dot.sv1 { fill: var(--sv1) }
.mgmt .uchart .mark.sv2, .mgmt .uchart .dot.sv2 { fill: var(--sv2) }
.mgmt .uchart .mark.sv3, .mgmt .uchart .dot.sv3 { fill: var(--sv3) }
.mgmt .uchart .mark:hover, .mgmt .uchart .dot:hover { opacity:.78 }
.mgmt .uchart .line { fill:none; stroke-width:2; stroke-linecap:round; stroke-linejoin:round }
.mgmt .uchart .line.sv1 { stroke: var(--sv1) }
.mgmt .uchart .line.sv2 { stroke: var(--sv2) }
.mgmt .uchart .line.sv3 { stroke: var(--sv3) }
.mgmt .uchart .dot { stroke: var(--chart-surface); stroke-width:2 }

.mgmt .chart-legend { display:flex; flex-wrap:wrap; gap:14px; margin-top:12px; font-size:12px; color: var(--text-2) }
.mgmt .chart-legend span { display:flex; align-items:center; gap:6px }
.mgmt .swatch { width:10px; height:10px; border-radius:3px; flex:none }
.mgmt .swatch.sv1 { background: var(--sv1) }
.mgmt .swatch.sv2 { background: var(--sv2) }
.mgmt .swatch.sv3 { background: var(--sv3) }

.mgmt .funnel { display:flex; flex-direction:column; gap:10px }
.mgmt .funnel-row { display:grid; grid-template-columns:120px 1fr 48px; align-items:center; gap:12px; font-size:13px }
.mgmt .funnel-label { color: var(--text-2) }
.mgmt .funnel-track { height:14px; background: var(--surface-muted); border-radius:4px; overflow:hidden }
.mgmt .funnel-fill { display:block; height:100%; background: var(--sv1); border-radius:4px }
.mgmt .funnel-value { text-align:right; font-family: var(--font-1); font-weight:600 }

.mgmt .kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px; margin-bottom:20px }
.mgmt .kpi { background: var(--surface); border:1px solid var(--border); border-radius: var(--r-md);
             box-shadow: var(--sh-sm); padding:16px }
.mgmt .kpi .k { font-size:11px; color: var(--text-3); display:block; margin-bottom:6px }
.mgmt .kpi .v { font-family: var(--font-1); font-size:26px; font-weight:700; letter-spacing:-.02em; line-height:1 }
.mgmt .kpi .s { font-size:11px; color: var(--text-3); display:block; margin-top:6px }
.mgmt details.tableview { margin-top:14px }
.mgmt details.tableview summary { font-size:12px; color: var(--brand-cyan-text); cursor:pointer }
</style>
