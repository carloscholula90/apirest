<?php
// Llamar al CRUD/UTILS
include 'crud/utils.php';
// Llamar al CRUD/GET
include 'crud/get.php';

// Dirección de apirest; en otro servidor se cambia con la variable de entorno SIAWEB_API_URL
$apiUrl = rtrim(getenv('SIAWEB_API_URL') ?: 'https://api.pruebas.siaweb.com.mx/api', '/');

// Generado con frontend/esquema-pago/generar-secciones.php; los cambios se hacen en el prototipo y se vuelve a generar
?>
<body>
  <style>
/*
 * Esquema de pago — estilos del sistema de diseño SIAWEB sobre Hope UI 2.0 / Bootstrap 5.
 * Todo vive bajo .siaweb-ep para no alterar el resto de las secciones.
 */
.siaweb-ep {
  --ep-bg: #f1f4f1;
  --ep-surface: #ffffff;
  --ep-surface-muted: #edf1ed;
  --ep-border: #dfe5df;
  --ep-text: #1b221c;
  --ep-text-muted: #5c665d;
  --ep-accent: #1c7a3d;
  --ep-accent-strong: #146130;
  --ep-accent-soft-bg: #e2f4e9;
  --ep-accent-soft-text: #1f7a41;
  --ep-bad-bg: #fbe7e4;
  --ep-bad-text: #a23b2e;
  --ep-info-bg: #e2eef8;
  --ep-info-text: #2f6a95;
  --ep-danger: #c0392b;
  --ep-on-accent: #ffffff;
  --ep-shadow-1: rgba(20, 30, 20, .10);
  --ep-shadow-2: rgba(20, 30, 20, .06);

  /* Bootstrap toma el verde de marca como primario dentro de estas pantallas */
  --bs-primary: var(--ep-accent);
  --bs-primary-rgb: 28, 122, 61;
  --bs-link-color: var(--ep-accent-strong);
  --bs-link-hover-color: var(--ep-accent-strong);

  font-family: 'Titillium Web', -apple-system, BlinkMacSystemFont, sans-serif;
  font-size: 1.12rem;
  color: var(--ep-text);
}

/* Modo oscuro de Hope UI (dark.min.css agrega .dark al body) */
.dark .siaweb-ep,
.siaweb-ep.dark {
  --ep-bg: #14181a;
  --ep-surface: #1c211e;
  --ep-surface-muted: #252b27;
  --ep-border: #343c36;
  --ep-text: #e8ece8;
  --ep-text-muted: #9aa79c;
  --ep-accent: #3fbf70;
  --ep-accent-strong: #57d184;
  --ep-accent-soft-bg: #1c3226;
  --ep-accent-soft-text: #7fe3a5;
  --ep-bad-bg: #3a221d;
  --ep-bad-text: #f0a08f;
  --ep-info-bg: #1c2b36;
  --ep-info-text: #8fc3eb;
  --ep-on-accent: #0f1a12;
  --ep-shadow-1: rgba(0, 0, 0, .45);
  --ep-shadow-2: rgba(0, 0, 0, .3);
  --bs-primary-rgb: 63, 191, 112;
}

.siaweb-ep .ep-titulo { font-family: 'Sora', 'Titillium Web', sans-serif; font-size: 23px; font-weight: 700; margin: 0; }
.siaweb-ep .ep-subtitulo { color: var(--ep-text-muted); font-size: 15px; margin: 4px 0 0; }

/* Tarjetas */
.siaweb-ep .card {
  background: var(--ep-surface);
  border: 1px solid var(--ep-border);
  border-radius: 14px;
  box-shadow: 0 10px 30px var(--ep-shadow-1), 0 2px 6px var(--ep-shadow-2);
}
.siaweb-ep .card-header { background: transparent; border-bottom: 0; padding: 18px 20px 6px; }
.siaweb-ep .card-body { padding: 14px 20px 18px; }
.siaweb-ep .card-title { display: flex; align-items: center; gap: 10px; font-size: 16px; font-weight: 700; margin: 0; }

.siaweb-ep .icon-badge {
  width: 34px; height: 34px; border-radius: 999px; flex: none;
  display: inline-flex; align-items: center; justify-content: center;
  background: var(--ep-accent-soft-bg); color: var(--ep-accent-strong);
}
.siaweb-ep .icon-badge.danger { background: var(--ep-bad-bg); color: var(--ep-danger); }
.siaweb-ep .icon-badge.info { background: var(--ep-info-bg); color: var(--ep-info-text); }

/* Botones */
.siaweb-ep .btn { font-weight: 600; font-size: 15px; border-radius: 10px; min-height: 44px; display: inline-flex; align-items: center; gap: 8px; }
.siaweb-ep .btn-primary { background: var(--ep-accent); border-color: var(--ep-accent); color: var(--ep-on-accent); }
.siaweb-ep .btn-primary:hover, .siaweb-ep .btn-primary:focus { background: var(--ep-accent-strong); border-color: var(--ep-accent-strong); color: var(--ep-on-accent); }
.siaweb-ep .btn-outline { background: var(--ep-surface); border: 1.5px solid var(--ep-border); color: var(--ep-text); }
.siaweb-ep .btn-outline:hover { border-color: var(--ep-accent); color: var(--ep-text); }
.siaweb-ep .btn-danger { background: var(--ep-danger); border-color: var(--ep-danger); color: #fff; }
.siaweb-ep .btn-link-danger { background: transparent; border: 0; color: var(--ep-danger); padding-left: 4px; padding-right: 4px; }
.siaweb-ep .btn:disabled { opacity: .45; }
.siaweb-ep .btn-icon {
  width: 36px; height: 36px; min-height: 36px; padding: 0; justify-content: center;
  border-radius: 999px; border: 1px solid var(--ep-border); background: var(--ep-surface); color: var(--ep-text-muted);
}
.siaweb-ep .btn-icon:hover { color: var(--ep-accent-strong); border-color: var(--ep-accent); }
.siaweb-ep .btn-icon.del:hover { color: var(--ep-danger); border-color: var(--ep-danger); }

/* Formularios */
.siaweb-ep .form-label, .siaweb-ep .ep-label { font-size: 13px; font-weight: 600; color: var(--ep-text-muted); margin-bottom: 6px; }
.siaweb-ep .form-control, .siaweb-ep .form-select {
  font-size: 14px; color: var(--ep-text); background-color: var(--ep-surface);
  border: 1.5px solid var(--ep-border); border-radius: 10px; min-height: 44px;
}
.siaweb-ep .form-control:focus, .siaweb-ep .form-select:focus { border-color: var(--ep-accent); box-shadow: 0 0 0 .2rem rgba(var(--bs-primary-rgb), .2); }
.siaweb-ep .form-check-input:checked { background-color: var(--ep-accent); border-color: var(--ep-accent); }
.siaweb-ep .form-text, .siaweb-ep .ep-ayuda { font-size: 12px; color: var(--ep-text-muted); }
.siaweb-ep .amount-input { display: flex; align-items: center; border: 1.5px solid var(--ep-accent); border-radius: 10px; background: var(--ep-surface); padding: 0 12px; }
.siaweb-ep .amount-input span { color: var(--ep-accent-strong); font-weight: 700; margin-right: 6px; }
.siaweb-ep .amount-input .form-control { border: 0; box-shadow: none; padding-left: 0; }
.siaweb-ep .search-wrap { position: relative; }
.siaweb-ep .search-wrap .form-control { padding-right: 40px; }
.siaweb-ep .search-wrap > svg { position: absolute; right: 12px; top: 13px; color: var(--ep-text-muted); pointer-events: none; }
.siaweb-ep .search-results { position: absolute; z-index: 20; left: 0; right: 0; top: calc(100% + 4px); max-height: 280px; overflow: auto; border-radius: 10px; box-shadow: 0 10px 30px var(--ep-shadow-1); }
.siaweb-ep .search-results .list-group-item { font-size: 14px; cursor: pointer; }

/* Tablas */
.siaweb-ep .table { font-size: 14px; color: var(--ep-text); margin-bottom: 0; }
.siaweb-ep .table thead th {
  font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em;
  color: var(--ep-text-muted); border-bottom: 1px solid var(--ep-border); white-space: nowrap; background: transparent;
}
.siaweb-ep .table td { border-bottom: 1px solid var(--ep-surface-muted); vertical-align: middle; background: transparent; }
.siaweb-ep .table .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.siaweb-ep .table tr.row-active td { background: var(--ep-accent-soft-bg); }
.siaweb-ep .table tfoot td { font-weight: 700; background: var(--ep-surface-muted); border-top: 1px solid var(--ep-border); }
.siaweb-ep .ofa-radio { width: 18px; height: 18px; accent-color: var(--ep-accent); cursor: pointer; }

/* Etiquetas */
.siaweb-ep .tag {
  display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .03em; border-radius: 999px; padding: 3px 10px; white-space: nowrap;
}
.siaweb-ep .tag.good { background: var(--ep-accent-soft-bg); color: var(--ep-accent-soft-text); }
.siaweb-ep .tag.bad { background: var(--ep-bad-bg); color: var(--ep-bad-text); }
.siaweb-ep .tag.info { background: var(--ep-info-bg); color: var(--ep-info-text); }
.siaweb-ep .tag.muted { background: var(--ep-surface-muted); color: var(--ep-text-muted); }
.siaweb-ep .swatch { width: 14px; height: 14px; border-radius: 999px; display: inline-block; flex: none; border: 1px solid rgba(0, 0, 0, .08); }

/* Mensajes */
.siaweb-ep .alert-siaweb, .siaweb-ep .info-box {
  display: flex; gap: 10px; align-items: flex-start; border-radius: 10px; padding: 10px 12px; font-size: 14px;
}
.siaweb-ep .alert-siaweb { background: var(--ep-bad-bg); color: var(--ep-bad-text); }
.siaweb-ep .info-box { background: var(--ep-accent-soft-bg); color: var(--ep-accent-strong); }
.siaweb-ep .info-box.neutral { background: var(--ep-info-bg); color: var(--ep-info-text); }
.siaweb-ep .info-box.danger-soft { background: var(--ep-bad-bg); color: var(--ep-bad-text); }
.siaweb-ep .info-box.stacked { flex-direction: column; gap: 4px; }
.siaweb-ep .alert-siaweb svg, .siaweb-ep .info-box > svg { flex: none; margin-top: 2px; }
.siaweb-ep .table-empty {
  display: flex; flex-direction: column; align-items: center; gap: 6px; text-align: center;
  padding: 28px 12px; color: var(--ep-text-muted); font-size: 14px;
}
.siaweb-ep .table-empty strong { color: var(--ep-text); font-size: 15px; }
.siaweb-ep .saved { display: inline-flex; align-items: center; gap: 6px; color: var(--ep-accent-strong); font-size: 14px; font-weight: 600; }

/* Datos de solo lectura */
.siaweb-ep .readonly-row { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.siaweb-ep .rlabel { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--ep-text-muted); }
.siaweb-ep .rvalue { font-size: 16px; font-weight: 700; }

/* Días */
.siaweb-ep .day-chip {
  font-size: 12px; font-weight: 700; width: 28px; height: 28px; border-radius: 999px;
  display: inline-flex; align-items: center; justify-content: center;
  background: var(--ep-surface-muted); color: var(--ep-text-muted);
}
.siaweb-ep .day-chip.on { background: var(--ep-accent-soft-bg); color: var(--ep-accent-soft-text); }

/* Selector de una opción */
.siaweb-ep .segmented { display: inline-flex; border: 1.5px solid var(--ep-border); border-radius: 10px; padding: 3px; gap: 3px; background: var(--ep-surface); }
.siaweb-ep .segmented button { font: inherit; font-size: 14px; font-weight: 600; border: 0; border-radius: 8px; padding: 6px 12px; min-height: 36px; background: transparent; color: var(--ep-text-muted); }
.siaweb-ep .segmented button.on { background: var(--ep-accent-soft-bg); color: var(--ep-accent-strong); }

.siaweb-ep .tipo-opt {
  display: flex; align-items: center; gap: 12px; width: 100%; text-align: left; padding: 10px 12px; min-height: 52px;
  border-radius: 10px; border: 1.5px solid var(--ep-border); background: var(--ep-surface); color: var(--ep-text); font-size: 14px; font-weight: 700;
}
.siaweb-ep .tipo-opt.on { border-color: var(--ep-accent); background: var(--ep-accent-soft-bg); }
.siaweb-ep .tipo-dot { width: 16px; height: 16px; border-radius: 5px; flex: none; }

/* Indicadores */
.siaweb-ep .kpi { background: var(--ep-surface); border: 1px solid var(--ep-border); border-radius: 14px; padding: 14px 16px; display: flex; flex-direction: column; gap: 8px; height: 100%; }
.siaweb-ep .kpi .v { font-size: 24px; font-weight: 700; font-variant-numeric: tabular-nums; line-height: 1.1; }
.siaweb-ep .kpi.key { background: var(--ep-accent-soft-bg); border-color: var(--ep-accent); }
.siaweb-ep .kpi.key .v { color: var(--ep-accent-strong); }
.siaweb-ep .calc { display: grid; grid-template-columns: 1fr auto; gap: 4px 16px; font-size: 14px; font-variant-numeric: tabular-nums; }
.siaweb-ep .calc .tot { font-weight: 700; border-top: 1px solid currentColor; padding-top: 6px; margin-top: 2px; }

/* Cuadrícula del horario */
.siaweb-ep .grid-wrap { overflow-x: auto; border: 1px solid var(--ep-border); border-radius: 10px; }
.siaweb-ep .hgrid { display: grid; min-width: 640px; background: var(--ep-surface); }
.siaweb-ep .hhead {
  font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--ep-text-muted);
  display: flex; align-items: center; justify-content: center; border-bottom: 1px solid var(--ep-border); background: var(--ep-surface-muted);
}
.siaweb-ep .hhour { font-size: 12px; color: var(--ep-text-muted); padding: 2px 8px 0 0; text-align: right; border-right: 1px solid var(--ep-border); }
.siaweb-ep .hcell { border: 0; border-bottom: 1px solid var(--ep-surface-muted); border-right: 1px solid var(--ep-surface-muted); background: transparent; padding: 0; }
.siaweb-ep .hcell:hover, .siaweb-ep .hcell:focus { background: var(--ep-accent-soft-bg); outline: none; }
.siaweb-ep .hblk {
  margin: 2px; border-radius: 8px; border: 1.5px solid; padding: 4px 8px; text-align: left; font-size: 12px; line-height: 1.25;
  color: var(--ep-text); display: flex; flex-direction: column; gap: 1px; overflow: hidden; z-index: 1; background: var(--ep-surface);
}
.siaweb-ep .hblk strong { font-size: 12px; letter-spacing: .02em; }
.siaweb-ep .hblk.fixed { border-style: dashed; cursor: default; }
.siaweb-ep .stat { display: flex; justify-content: space-between; align-items: center; font-size: 14px; padding: 6px 0; border-bottom: 1px solid var(--ep-surface-muted); }
.siaweb-ep .hhour.receso, .siaweb-ep .hhour.hueco { font-size: 11px; }
.siaweb-ep .hreceso {
  display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 11px; font-weight: 700;
  letter-spacing: .03em; text-transform: uppercase; border-radius: 6px; margin: 2px; border: 1.5px dashed;
}
.siaweb-ep .hhueco { background: var(--ep-surface-muted); }
.siaweb-ep .hblk.otro { border-style: dotted; cursor: default; background: var(--ep-surface-muted) !important; color: var(--ep-text-muted); }

/* Leyenda de tipos de bloque (solo informativa) */
.siaweb-ep .leyenda { display: flex; flex-wrap: wrap; gap: 8px 16px; align-items: center; font-size: 13px; font-weight: 600; }
.siaweb-ep .leyenda-item { display: inline-flex; align-items: center; gap: 6px; }
.siaweb-ep .leyenda-muestra { width: 14px; height: 14px; border-radius: 4px; border: 1.5px solid; flex: none; }

/* Impresión del horario global */
.ep-impresion { display: none; }
@media print {
  body * { visibility: hidden; }
  .ep-impresion, .ep-impresion * { visibility: visible; }
  .ep-impresion { display: block; position: absolute; left: 0; top: 0; width: 100%; padding: 0; font-family: 'Titillium Web', sans-serif; color: #000; }
  .ep-impresion .hgrid { min-width: 0; }
  .ep-impresion .grid-wrap { overflow: visible; }
  .ep-impresion .hblk { break-inside: avoid; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .ep-impresion .hhead, .ep-impresion .hhueco { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  @page { size: landscape; margin: 12mm; }
}

/* Modales (Bootstrap) con el patrón "Condonar cargo" */
.siaweb-ep-modal .modal-content {
  font-family: 'Titillium Web', -apple-system, BlinkMacSystemFont, sans-serif;
  border-radius: 14px; border: 1px solid var(--ep-border, #dfe5df);
  box-shadow: 0 10px 30px rgba(20, 30, 20, .10), 0 2px 6px rgba(20, 30, 20, .06);
}
.siaweb-ep-modal .modal-header { border-bottom: 0; padding: 18px 22px; }
.siaweb-ep-modal .modal-title { font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
.siaweb-ep-modal .modal-body { padding: 4px 22px 22px; display: flex; flex-direction: column; gap: 14px; }
.siaweb-ep-modal .modal-footer { border-top: 1px solid var(--ep-surface-muted, #edf1ed); padding: 14px 22px; gap: 10px; }
.siaweb-ep-modal .modal-footer > * { margin: 0; }

  </style>


    <div class="siaweb-ep" id="ep-pantalla-pagos">

      <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
        <div>
          <h1 class="ep-titulo">Procesamiento de pagos</h1>
          <p class="ep-subtitulo">Administra los calendarios de pago por turno, procesa el pago y revisa el reporte concentrado.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <button type="button" class="btn btn-outline" id="ep-btn-nuevo-calendario"><span data-icono="calendario"></span>Nuevo calendario</button>
          <a class="btn btn-outline" href="#ep-reporte"><span data-icono="documento"></span>Ver reporte concentrado</a>
        </div>
      </div>

      <div id="ep-alerta-general" class="d-none mb-3"></div>

      <div class="row g-4 align-items-stretch mb-4">
        <div class="col-12 col-xl-6">
          <section class="card h-100">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
              <h2 class="card-title"><span class="icon-badge"><span data-icono="calendario" data-tam="18"></span></span>Calendarios de pago</h2>
              <div class="segmented" role="radiogroup" aria-label="Filtro de calendarios">
                <button type="button" role="radio" aria-checked="true" class="on" data-filtro="pendientes">Por procesar</button>
                <button type="button" role="radio" aria-checked="false" data-filtro="todos">Todos</button>
              </div>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table">
                  <thead><tr><th><span class="visually-hidden">Seleccionar</span></th><th>Turno</th><th>Fecha inicio</th><th>Fecha fin</th><th>Estado</th><th><span class="visually-hidden">Acciones</span></th></tr></thead>
                  <tbody id="ep-tabla-calendarios"></tbody>
                </table>
              </div>
              <p class="ep-ayuda mt-3 mb-0">Por default se selecciona el siguiente calendario por fecha que aún no se procesa. Un calendario procesado ya no se puede modificar.</p>
            </div>
          </section>
        </div>

        <div class="col-12 col-xl-6">
          <section class="card h-100">
            <div class="card-header d-flex flex-wrap align-items-start justify-content-between gap-2">
              <div>
                <h2 class="card-title">Resumen del calendario</h2>
                <p class="ep-ayuda mt-1 mb-0" id="ep-resumen-titulo">Sin calendario seleccionado</p>
              </div>
              <span class="tag muted" id="ep-resumen-estado"></span>
            </div>
            <div class="card-body d-flex flex-column gap-3">
              <div class="row g-3">
                <div class="col-6 col-md-4"><div class="kpi"><span class="icon-badge info"><span data-icono="usuarios" data-tam="18"></span></span><span class="rlabel">Empleados</span><span class="v" id="ep-kpi-empleados">—</span></div></div>
                <div class="col-6 col-md-4"><div class="kpi"><span class="icon-badge info"><span data-icono="reloj" data-tam="18"></span></span><span class="rlabel">Horas trabajadas</span><span class="v" id="ep-kpi-horas">—</span></div></div>
                <div class="col-6 col-md-4"><div class="kpi"><span class="icon-badge danger"><span data-icono="menos" data-tam="18"></span></span><span class="rlabel">Horas a descontar</span><span class="v" id="ep-kpi-descontar">—</span></div></div>
                <div class="col-6 col-md-6"><div class="kpi"><span class="icon-badge danger"><span data-icono="porcentaje" data-tam="18"></span></span><span class="rlabel">Retenciones</span><span class="v" id="ep-kpi-retenciones">—</span></div></div>
                <div class="col-12 col-md-6"><div class="kpi key"><span class="icon-badge"><span data-icono="dinero" data-tam="18"></span></span><span class="rlabel">Neto a pagar</span><span class="v" id="ep-kpi-neto">—</span></div></div>
              </div>
              <div class="info-box stacked d-none" id="ep-resumen-calculo">
                <strong style="font-size:13px">Cómo se llega al neto</strong>
                <div class="calc">
                  <span>Honorarios (<span id="ep-calc-horas"></span> h a pagar)</span><span id="ep-calc-honorarios"></span>
                  <span>Más IVA trasladado</span><span id="ep-calc-iva"></span>
                  <span>Menos retenciones de ISR e IVA</span><span id="ep-calc-retenciones"></span>
                  <span class="tot">Neto a pagar</span><span class="tot" id="ep-calc-neto"></span>
                </div>
              </div>
              <div class="d-flex flex-wrap justify-content-end align-items-center gap-3 mt-auto">
                <span id="ep-estado-procesado" aria-live="polite"></span>
                <button type="button" class="btn btn-primary" id="ep-btn-procesar" disabled><span data-icono="procesar"></span>Procesar pago</button>
              </div>
            </div>
          </section>
        </div>
      </div>

      <section class="card" id="ep-reporte">
        <div class="card-header">
          <h2 class="card-title"><span class="icon-badge"><span data-icono="documento" data-tam="18"></span></span>Reporte concentrado</h2>
          <p class="ep-ayuda mt-1 mb-0">Pago de servicios profesionales independientes · <span id="ep-reporte-titulo">sin calendario</span></p>
        </div>
        <div class="card-body">
          <div id="ep-alerta-omitidos" class="d-none mb-3"></div>
          <div class="table-responsive">
            <table class="table" style="min-width:1180px">
              <thead>
                <tr>
                  <th>No.</th><th>Catedrático</th><th>Grado</th><th>Régimen</th>
                  <th class="num">Horas trab.</th><th class="num">Desc.</th><th class="num">Horas a pagar</th><th class="num">$ por hora</th>
                  <th class="num">Honorarios</th><th class="num">IVA</th><th class="num">Subtotal</th>
                  <th class="num">Ret. ISR</th><th class="num">% ISR</th><th class="num">Ret. IVA</th><th class="num">Neto</th>
                </tr>
              </thead>
              <tbody id="ep-tabla-concentrado"></tbody>
              <tfoot id="ep-pie-concentrado"></tfoot>
            </table>
          </div>
        </div>
      </section>
    </div>

  <!-- Nuevo / editar calendario (mismo modal) -->
  <div class="modal fade siaweb-ep siaweb-ep-modal" id="ep-modal-calendario" tabindex="-1" aria-labelledby="ep-titulo-modal-calendario" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" id="ep-form-calendario" novalidate>
        <div class="modal-header">
          <h2 class="modal-title" id="ep-titulo-modal-calendario"><span class="icon-badge"><span data-icono="calendario" data-tam="18"></span></span><span class="ep-texto-titulo">Nuevo calendario de pago</span></h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div>
            <label class="form-label" for="ep-calendario-turno">Turno</label>
            <select class="form-select" id="ep-calendario-turno"></select>
          </div>
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label" for="ep-calendario-inicio">Fecha inicio</label>
              <input class="form-control" id="ep-calendario-inicio" type="date">
            </div>
            <div class="col-6">
              <label class="form-label" for="ep-calendario-fin">Fecha fin</label>
              <input class="form-control" id="ep-calendario-fin" type="date">
            </div>
          </div>
          <div class="readonly-row"><span class="rlabel">Procesado</span><span class="rvalue" style="font-size:15px">No</span></div>
          <div class="ep-error d-none"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><span data-icono="check"></span>Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    window.SIAWEB_API_URL = <?= json_encode($apiUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  </script>
  <script>
/*
 * Esquema de pago — utilerías compartidas por las 4 pantallas.
 * Requiere jQuery 3 y Bootstrap 5 (incluido en libs.min.js de Hope UI).
 *
 * La URL de apirest se puede cambiar sin tocar este archivo:
 *   <script>window.SIAWEB_API_URL = 'https://api.siaweb.com.mx/api';<\/script>
 */
(function (window, $) {
  'use strict';

  var API_URL = String(window.SIAWEB_API_URL || 'http://127.0.0.1:8000/api').replace(/\/$/, '');

  // 1 es Domingo, 2 Lunes ... 7 Sábado (mismo criterio que dtlTurno en apirest)
  var DIAS = ['', 'DOMINGO', 'LUNES', 'MARTES', 'MIÉRCOLES', 'JUEVES', 'VIERNES', 'SÁBADO'];
  var DIAS_CORTOS = ['', 'D', 'L', 'M', 'M', 'J', 'V', 'S'];

  var ICONOS = {
    mas: '<path d="M12 5v14M5 12h14"/>',
    editar: '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
    eliminar: '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/>',
    cerrar: '<path d="M18 6 6 18M6 6l12 12"/>',
    alerta: '<circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>',
    info: '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
    check: '<path d="M20 6 9 17l-5-5"/>',
    calendario: '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    reloj: '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
    capas: '<path d="m12 2 10 5-10 5L2 7z"/><path d="m2 17 10 5 10-5M2 12l10 5 10-5"/>',
    cuadros: '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
    candado: '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    buscar: '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
    usuarios: '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    menos: '<circle cx="12" cy="12" r="10"/><path d="M8 12h8"/>',
    porcentaje: '<path d="M19 5 5 19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>',
    dinero: '<path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
    procesar: '<path d="M5 3l14 9-14 9V3z"/>',
    documento: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8"/>'
  };

  function icono(nombre, tam) {
    tam = tam || 16;
    return '<svg width="' + tam + '" height="' + tam + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
      + 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + (ICONOS[nombre] || '') + '</svg>';
  }

  // Pinta los íconos declarados en el HTML como <span data-icono="nombre" data-tam="18"></span>
  function pintarIconos(contexto) {
    $('[data-icono]', contexto || document).each(function () {
      var $el = $(this);
      $el.replaceWith(icono($el.data('icono'), $el.data('tam')));
    });
  }

  function escapar(texto) {
    return String(texto === null || texto === undefined ? '' : texto)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  /*
   * Llamada a apirest. Devuelve una promesa de jQuery con el JSON.
   * opciones.vacioEn404: en las rutas de lista que responden 404 cuando no hay registros,
   * resuelve con null en lugar de fallar.
   */
  function api(metodo, ruta, datos, opciones) {
    opciones = opciones || {};
    var diferido = $.Deferred();
    $.ajax({
      url: API_URL + ruta,
      method: metodo,
      dataType: 'json',
      contentType: datos ? 'application/json' : undefined,
      data: datos ? JSON.stringify(datos) : undefined
    }).done(function (respuesta) {
      diferido.resolve(respuesta);
    }).fail(function (xhr) {
      if (opciones.vacioEn404 && xhr.status === 404) {
        diferido.resolve(null);
        return;
      }
      diferido.reject(mensajeError(xhr), xhr);
    });
    return diferido.promise();
  }

  function mensajeError(xhr, porDefecto) {
    var r = xhr && xhr.responseJSON;
    if (r) {
      if (r.error && typeof r.error === 'object') {
        var mensajes = [];
        $.each(r.error, function (_, lista) { mensajes = mensajes.concat(lista); });
        if (mensajes.length) return mensajes.join(' ');
      }
      if (r.message) return r.message;
    }
    if (xhr && xhr.status === 0) return 'No hay conexión con la API (' + API_URL + ').';
    if (xhr && xhr.status === 404) return 'La API no tiene esta ruta todavía (404).';
    return porDefecto || 'Ocurrió un error inesperado (' + (xhr ? xhr.status : '?') + ').';
  }

  function mostrarAlerta($contenedor, mensaje) {
    $contenedor.html('<div class="alert-siaweb" role="alert">' + icono('alerta', 18) + '<span>' + escapar(mensaje) + '</span></div>')
      .removeClass('d-none');
  }

  function limpiarAlerta($contenedor) {
    $contenedor.empty().addClass('d-none');
  }

  function estadoVacio(nombreIcono, titulo, texto) {
    return '<div class="table-empty">' + icono(nombreIcono, 28) + '<strong>' + escapar(titulo) + '</strong>'
      + (texto ? '<span>' + escapar(texto) + '</span>' : '') + '</div>';
  }

  function filaVacia(columnas, nombreIcono, titulo, texto) {
    return '<tr><td colspan="' + columnas + '">' + estadoVacio(nombreIcono, titulo, texto) + '</td></tr>';
  }

  function filaCargando(columnas) {
    return '<tr><td colspan="' + columnas + '"><div class="table-empty"><span class="spinner-border spinner-border-sm" role="status"></span><span>Cargando…</span></div></td></tr>';
  }

  // Texto "Guardado" junto al botón, para formularios integrados en la página
  function marcarGuardado($lugar, texto) {
    $lugar.html('<span class="saved" role="status">' + icono('check') + escapar(texto || 'Guardado') + '</span>');
    clearTimeout($lugar.data('temporizador'));
    $lugar.data('temporizador', setTimeout(function () { $lugar.empty(); }, 4000));
  }

  function bloquearBoton($boton, bloquear) {
    $boton.prop('disabled', bloquear);
    if (bloquear) {
      $boton.data('html', $boton.html()).prepend('<span class="spinner-border spinner-border-sm" role="status"></span>');
    } else if ($boton.data('html')) {
      $boton.html($boton.data('html'));
    }
  }

  function hora(valor) {
    return valor ? String(valor).substr(0, 5) : '';
  }

  function minutos(valor) {
    var p = hora(valor).split(':');
    return Number(p[0]) * 60 + Number(p[1]);
  }

  function horaDesdeMinutos(total) {
    var h = Math.floor(total / 60), m = total % 60;
    return (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m;
  }

  function seEmpalman(inicioA, finA, inicioB, finB) {
    return hora(inicioA) < hora(finB) && hora(inicioB) < hora(finA);
  }

  function fecha(iso) {
    if (!iso) return '—';
    var p = String(iso).substr(0, 10).split('-');
    return p[2] + '/' + p[1] + '/' + p[0];
  }

  function hoyIso(sumarDias) {
    var d = new Date();
    d.setDate(d.getDate() + (sumarDias || 0));
    return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
  }

  function dinero(valor) {
    return '$' + Number(valor || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function numero(valor, decimales) {
    return Number(valor || 0).toLocaleString('es-MX', { minimumFractionDigits: decimales || 0, maximumFractionDigits: decimales || 0 });
  }

  // Acepta el número (1-7) o el nombre del día, con o sin acento
  function diaNumero(dia) {
    var texto = $.trim(String(dia === null || dia === undefined ? '' : dia));
    if (/^\d+$/.test(texto)) {
      var n = Number(texto);
      return n >= 1 && n <= 7 ? n : null;
    }
    var limpio = texto.toUpperCase().replace('É', 'E').replace('Á', 'A');
    for (var i = 1; i <= 7; i++) {
      if (DIAS[i].replace('É', 'E').replace('Á', 'A') === limpio) return i;
    }
    return null;
  }

  function diaNombre(dia) {
    var n = diaNumero(dia);
    return n ? DIAS[n] : '';
  }

  function chipsDias(numerosActivos) {
    var html = '';
    for (var d = 1; d <= 7; d++) {
      html += '<span class="day-chip' + (numerosActivos.indexOf(d) >= 0 ? ' on' : '') + '" title="' + DIAS[d] + '">' + DIAS_CORTOS[d] + '</span>';
    }
    return html;
  }

  /*
   * Modal de confirmación reutilizable (eliminar o procesar).
   * opciones: titulo, texto, resumen (arreglo), tituloResumen, textoBoton, peligro (bool), icono, alConfirmar (devuelve promesa)
   */
  function confirmar(opciones) {
    var $modal = $('#ep-modal-confirmar');
    if (!$modal.length) {
      $modal = $(
        '<div class="modal fade siaweb-ep siaweb-ep-modal" id="ep-modal-confirmar" tabindex="-1" aria-labelledby="ep-confirmar-titulo" aria-hidden="true">'
        + '<div class="modal-dialog modal-dialog-centered"><div class="modal-content">'
        + '<div class="modal-header"><h2 class="modal-title" id="ep-confirmar-titulo"></h2>'
        + '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>'
        + '<div class="modal-body"><p class="mb-0 ep-confirmar-texto" style="font-size:15px"></p>'
        + '<div class="info-box stacked ep-confirmar-resumen"></div><div class="ep-confirmar-error d-none"></div></div>'
        + '<div class="modal-footer"><button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>'
        + '<button type="button" class="btn ep-confirmar-ok"></button></div>'
        + '</div></div></div>'
      ).appendTo('body');
    }

    var peligro = opciones.peligro !== false;
    $modal.find('.modal-title').html('<span class="icon-badge' + (peligro ? ' danger' : '') + '">'
      + icono(opciones.icono || (peligro ? 'eliminar' : 'check'), 18) + '</span>' + escapar(opciones.titulo));
    $modal.find('.ep-confirmar-texto').text(opciones.texto || '');
    var resumen = opciones.resumen || [];
    $modal.find('.ep-confirmar-resumen')
      .toggleClass('danger-soft', peligro)
      .toggleClass('d-none', !resumen.length)
      .html('<strong style="font-size:13px">' + escapar(opciones.tituloResumen || (peligro ? 'Qué se pierde' : 'Resumen')) + '</strong>'
        + $.map(resumen, function (linea) { return '<span>' + escapar(linea) + '</span>'; }).join(''));
    limpiarAlerta($modal.find('.ep-confirmar-error'));

    var $ok = $modal.find('.ep-confirmar-ok')
      .removeClass('btn-danger btn-primary').addClass(peligro ? 'btn-danger' : 'btn-primary')
      .html(icono(opciones.icono || (peligro ? 'eliminar' : 'check')) + escapar(opciones.textoBoton || (peligro ? 'Eliminar' : 'Confirmar')));

    var instancia = bootstrap.Modal.getOrCreateInstance($modal[0]);
    $ok.off('click').on('click', function () {
      bloquearBoton($ok, true);
      $.when(opciones.alConfirmar()).done(function () {
        instancia.hide();
      }).fail(function (mensaje) {
        mostrarAlerta($modal.find('.ep-confirmar-error'), mensaje);
      }).always(function () {
        bloquearBoton($ok, false);
      });
    });
    instancia.show();
  }

  window.EP = {
    API_URL: API_URL,
    DIAS: DIAS,
    DIAS_CORTOS: DIAS_CORTOS,
    api: api,
    icono: icono,
    pintarIconos: pintarIconos,
    escapar: escapar,
    mensajeError: mensajeError,
    mostrarAlerta: mostrarAlerta,
    limpiarAlerta: limpiarAlerta,
    estadoVacio: estadoVacio,
    filaVacia: filaVacia,
    filaCargando: filaCargando,
    marcarGuardado: marcarGuardado,
    bloquearBoton: bloquearBoton,
    hora: hora,
    minutos: minutos,
    horaDesdeMinutos: horaDesdeMinutos,
    seEmpalman: seEmpalman,
    fecha: fecha,
    hoyIso: hoyIso,
    dinero: dinero,
    numero: numero,
    diaNumero: diaNumero,
    diaNombre: diaNombre,
    chipsDias: chipsDias,
    confirmar: confirmar
  };

  $(function () { pintarIconos(); });
})(window, jQuery);

  </script>
  <script>
    // Modales y hoja de impresion van directo en body para que el layout no los recorte
    $('.siaweb-ep-modal, .ep-impresion').appendTo('body');
  </script>
  <script>
/*
 * Procesamiento de pagos (Esquema de pago 3.1)
 * APIs: /calendariopago, /turnos y /pagos:
 *   GET  /pagos/concentrado/{idCalendarioPago} -> { concentrado: [{ uid, nombre, grado, regimen, horasTrabajadas, horasDescontar,
 *                                                   costoHora, honorarios, iva, retencionIsr, porcentajeIsr, retencionIva, neto }],
 *                                                   omitidos: [{ uid, nombre, motivo }] }  (vista previa si no está procesado)
 *   POST /pagos/procesar/{idCalendarioPago}     -> guarda pago/detallePago y marca el calendario como procesado
 */
$(function () {
  'use strict';

  var estado = {
    filtro: 'pendientes',
    calendarios: [],
    seleccionado: null,
    turnos: [],
    editando: null,
    concentrado: null
  };

  var modalCalendario = new bootstrap.Modal('#ep-modal-calendario');
  var $tabla = $('#ep-tabla-calendarios');

  function calendarioActual() {
    return estado.calendarios.find(function (c) { return c.idCalendarioPago === estado.seleccionado; }) || null;
  }

  function nombreTurno(c) {
    if (c.turno && c.turno.descripcion) return $.trim(c.turno.descripcion);
    var t = estado.turnos.find(function (x) { return Number(x.idTurno) === Number(c.idTurno); });
    return t ? $.trim(t.descripcion) : 'TURNO ' + c.idTurno;
  }

  function tituloCalendario(c) {
    return nombreTurno(c) + ' · del ' + EP.fecha(c.fechaInicio) + ' al ' + EP.fecha(c.fechaFin);
  }

  // ---------- Carga ----------

  function cargarTurnos() {
    return EP.api('GET', '/turnos').done(function (r) {
      estado.turnos = (r.turnos || []).filter(function (t) { return Number(t.idTurno) !== 0; });
    });
  }

  function cargarCalendarios(seleccionar) {
    $tabla.html(EP.filaCargando(6));
    var ruta = '/calendariopago' + (estado.filtro === 'pendientes' ? '?pendientes=1' : '');
    var siguiente = EP.api('GET', '/calendariopago/siguiente', null, { vacioEn404: true });
    return $.when(EP.api('GET', ruta), siguiente).done(function (r, sig) {
      estado.calendarios = (r.calendariosPago || []).map(function (c) {
        return $.extend({}, c, { idCalendarioPago: Number(c.idCalendarioPago), procesado: Number(c.procesado) === 1 });
      });
      var ids = estado.calendarios.map(function (c) { return c.idCalendarioPago; });
      var idSiguiente = sig && sig.calendarioPago ? Number(sig.calendarioPago.idCalendarioPago) : null;
      if (ids.indexOf(seleccionar) >= 0) estado.seleccionado = seleccionar;
      else if (ids.indexOf(estado.seleccionado) < 0) estado.seleccionado = ids.indexOf(idSiguiente) >= 0 ? idSiguiente : (ids[0] || null);
      pintarCalendarios();
      cargarConcentrado();
    }).fail(function (mensaje) {
      $tabla.html(EP.filaVacia(6, 'alerta', 'No se pudieron cargar los calendarios', mensaje));
    });
  }

  function cargarConcentrado() {
    var c = calendarioActual();
    estado.concentrado = null;
    pintarResumen();
    if (!c) {
      $('#ep-tabla-concentrado').html(EP.filaVacia(15, 'documento', 'Sin calendario seleccionado', 'Elige un calendario para ver su concentrado.'));
      $('#ep-pie-concentrado').empty();
      return;
    }
    $('#ep-tabla-concentrado').html(EP.filaCargando(15));
    $('#ep-pie-concentrado').empty();
    EP.limpiarAlerta($('#ep-alerta-omitidos'));
    EP.api('GET', '/pagos/concentrado/' + c.idCalendarioPago).done(function (r) {
      estado.concentrado = r.concentrado || [];
      pintarConcentrado();
      pintarResumen();
      pintarOmitidos(r.omitidos || []);
    }).fail(function (mensaje, xhr) {
      var pendiente = xhr && xhr.status === 404;
      $('#ep-tabla-concentrado').html(EP.filaVacia(15, pendiente ? 'info' : 'alerta',
        pendiente ? 'Cálculo del pago pendiente en apirest' : 'No se pudo cargar el concentrado',
        pendiente ? 'Se mostrará cuando exista la ruta /pagos/concentrado/{idCalendarioPago}.' : mensaje));
    });
  }

  // ---------- Pintado ----------

  function pintarOmitidos(omitidos) {
    if (!omitidos.length) return EP.limpiarAlerta($('#ep-alerta-omitidos'));
    var detalle = omitidos.slice(0, 5).map(function (o) {
      return (o.nombre || 'UID ' + o.uid) + ': ' + o.motivo;
    }).join('; ') + (omitidos.length > 5 ? '; y ' + (omitidos.length - 5) + ' más' : '');
    EP.mostrarAlerta($('#ep-alerta-omitidos'), omitidos.length + ' profesor(es) con horas en este calendario no se incluyen. ' + detalle + '.');
  }

  function pintarCalendarios() {
    if (!estado.calendarios.length) {
      $tabla.html(EP.filaVacia(6, 'check', estado.filtro === 'pendientes' ? 'No hay calendarios por procesar' : 'No hay calendarios de pago',
        'Crea el siguiente calendario o cambia el filtro a Todos.'));
      return;
    }
    $tabla.html(estado.calendarios.map(function (c) {
      var activo = c.idCalendarioPago === estado.seleccionado;
      var bloqueo = c.procesado ? ' disabled title="Ya procesado: no se puede modificar"' : '';
      return '<tr class="' + (activo ? 'row-active' : '') + '">'
        + '<td><input type="radio" class="ofa-radio" name="calendario-sel" value="' + c.idCalendarioPago + '"' + (activo ? ' checked' : '')
        + ' aria-label="Seleccionar ' + EP.escapar(tituloCalendario(c)) + '"></td>'
        + '<td class="fw-semibold">' + EP.escapar(nombreTurno(c)) + '</td>'
        + '<td>' + EP.fecha(c.fechaInicio) + '</td>'
        + '<td>' + EP.fecha(c.fechaFin) + '</td>'
        + '<td><span class="tag ' + (c.procesado ? 'good' : 'muted') + '">' + (c.procesado ? 'Procesado' : 'Por procesar') + '</span></td>'
        + '<td class="text-end text-nowrap">'
        + '<button type="button" class="btn btn-icon me-1" data-editar="' + c.idCalendarioPago + '" aria-label="Editar calendario ' + EP.escapar(nombreTurno(c)) + '"' + bloqueo + '>' + EP.icono('editar') + '</button>'
        + '<button type="button" class="btn btn-icon del" data-eliminar="' + c.idCalendarioPago + '" aria-label="Eliminar calendario ' + EP.escapar(nombreTurno(c)) + '"' + bloqueo + '>' + EP.icono('eliminar') + '</button>'
        + '</td></tr>';
    }).join(''));
  }

  function totales() {
    var t = { empleados: 0, horas: 0, descontar: 0, pagar: 0, honorarios: 0, iva: 0, subtotal: 0, retIsr: 0, retIva: 0, neto: 0 };
    (estado.concentrado || []).forEach(function (r) {
      var horas = Number(r.horasTrabajadas || 0), desc = Number(r.horasDescontar || 0);
      var hon = Number(r.honorarios || 0), iva = Number(r.iva || 0);
      t.empleados++;
      t.horas += horas;
      t.descontar += desc;
      t.pagar += horas - desc;
      t.honorarios += hon;
      t.iva += iva;
      t.subtotal += hon + iva;
      t.retIsr += Number(r.retencionIsr || 0);
      t.retIva += Number(r.retencionIva || 0);
      t.neto += Number(r.neto || 0);
    });
    return t;
  }

  function pintarResumen() {
    var c = calendarioActual();
    $('#ep-resumen-titulo').text(c ? tituloCalendario(c) : 'Sin calendario seleccionado');
    $('#ep-reporte-titulo').text(c ? tituloCalendario(c) : 'sin calendario');
    $('#ep-resumen-estado').text(c ? (c.procesado ? 'Procesado' : 'Por procesar') : '')
      .toggleClass('good', !!(c && c.procesado)).toggleClass('muted', !(c && c.procesado));
    $('#ep-btn-procesar').prop('disabled', !c || c.procesado);
    $('#ep-estado-procesado').html(c && c.procesado ? '<span class="saved">' + EP.icono('candado') + 'Procesado</span>' : '');

    var hay = estado.concentrado !== null;
    var t = totales();
    $('#ep-kpi-empleados').text(hay ? EP.numero(t.empleados) : '—');
    $('#ep-kpi-horas').text(hay ? EP.numero(t.horas) : '—');
    $('#ep-kpi-descontar').text(hay ? EP.numero(t.descontar) : '—');
    $('#ep-kpi-retenciones').text(hay ? EP.dinero(t.retIsr + t.retIva) : '—');
    $('#ep-kpi-neto').text(hay ? EP.dinero(t.neto) : '—');
    $('#ep-resumen-calculo').toggleClass('d-none', !hay || !t.empleados);
    $('#ep-calc-horas').text(EP.numero(t.pagar));
    $('#ep-calc-honorarios').text(EP.dinero(t.honorarios));
    $('#ep-calc-iva').text('+' + EP.dinero(t.iva));
    $('#ep-calc-retenciones').text('−' + EP.dinero(t.retIsr + t.retIva));
    $('#ep-calc-neto').text(EP.dinero(t.neto));
  }

  function pintarConcentrado() {
    var filas = estado.concentrado || [];
    if (!filas.length) {
      $('#ep-tabla-concentrado').html(EP.filaVacia(15, 'usuarios', 'Sin empleados en este calendario', 'No hay horas registradas para el periodo.'));
      $('#ep-pie-concentrado').empty();
      return;
    }
    $('#ep-tabla-concentrado').html(filas.map(function (r, i) {
      var horas = Number(r.horasTrabajadas || 0), desc = Number(r.horasDescontar || 0);
      var hon = Number(r.honorarios || 0), iva = Number(r.iva || 0);
      return '<tr><td>' + (i + 1) + '</td>'
        + '<td class="fw-semibold text-nowrap">' + EP.escapar(String(r.nombre || '').toUpperCase()) + '</td>'
        + '<td>' + EP.escapar(String(r.grado || '').toUpperCase()) + '</td>'
        + '<td><span class="tag muted">' + EP.escapar(String(r.regimen || 'SIN RÉGIMEN').toUpperCase()) + '</span></td>'
        + '<td class="num">' + EP.numero(horas) + '</td><td class="num">' + EP.numero(desc) + '</td><td class="num">' + EP.numero(horas - desc) + '</td>'
        + '<td class="num">' + EP.dinero(r.costoHora) + '</td><td class="num">' + EP.dinero(hon) + '</td><td class="num">' + EP.dinero(iva) + '</td>'
        + '<td class="num">' + EP.dinero(hon + iva) + '</td><td class="num">' + EP.dinero(r.retencionIsr) + '</td>'
        + '<td class="num">' + EP.numero(Number(r.porcentajeIsr || 0), 2) + '%</td><td class="num">' + EP.dinero(r.retencionIva) + '</td>'
        + '<td class="num fw-bold">' + EP.dinero(r.neto) + '</td></tr>';
    }).join(''));
    var t = totales();
    $('#ep-pie-concentrado').html('<tr><td></td><td>Totales</td><td></td><td></td>'
      + '<td class="num">' + EP.numero(t.horas) + '</td><td class="num">' + EP.numero(t.descontar) + '</td><td class="num">' + EP.numero(t.pagar) + '</td><td></td>'
      + '<td class="num">' + EP.dinero(t.honorarios) + '</td><td class="num">' + EP.dinero(t.iva) + '</td><td class="num">' + EP.dinero(t.subtotal) + '</td>'
      + '<td class="num">' + EP.dinero(t.retIsr) + '</td><td></td><td class="num">' + EP.dinero(t.retIva) + '</td><td class="num">' + EP.dinero(t.neto) + '</td></tr>');
  }

  // ---------- Eventos ----------

  $('.segmented [data-filtro]').on('click', function () {
    estado.filtro = $(this).data('filtro');
    $('.segmented [data-filtro]').each(function () {
      var on = $(this).data('filtro') === estado.filtro;
      $(this).toggleClass('on', on).attr('aria-checked', on);
    });
    cargarCalendarios(estado.seleccionado);
  });

  $tabla.on('change', '.ofa-radio', function () {
    estado.seleccionado = Number($(this).val());
    pintarCalendarios();
    cargarConcentrado();
  });

  function abrirCalendario(c) {
    estado.editando = c;
    var $turno = $('#ep-calendario-turno').empty();
    estado.turnos.forEach(function (t) { $turno.append($('<option>').val(t.idTurno).text($.trim(t.descripcion))); });
    if (c) $turno.val(c.idTurno);
    $('#ep-calendario-inicio').val(c ? String(c.fechaInicio).substr(0, 10) : '');
    $('#ep-calendario-fin').val(c ? String(c.fechaFin).substr(0, 10) : '');
    $('#ep-form-calendario .ep-texto-titulo').text(c ? 'Editar calendario de pago' : 'Nuevo calendario de pago');
    EP.limpiarAlerta($('#ep-form-calendario .ep-error'));
    modalCalendario.show();
  }

  $('#ep-btn-nuevo-calendario').on('click', function () { abrirCalendario(null); });

  $tabla.on('click', '[data-editar]', function () {
    var id = Number($(this).data('editar'));
    abrirCalendario(estado.calendarios.find(function (c) { return c.idCalendarioPago === id; }));
  });

  $('#ep-form-calendario').on('submit', function (e) {
    e.preventDefault();
    var $error = $(this).find('.ep-error');
    var c = estado.editando;
    var datos = {
      idTurno: Number($('#ep-calendario-turno').val()),
      fechaInicio: $('#ep-calendario-inicio').val(),
      fechaFin: $('#ep-calendario-fin').val(),
      procesado: 0
    };
    if (!datos.idTurno) return EP.mostrarAlerta($error, 'Selecciona el turno.');
    if (!datos.fechaInicio || !datos.fechaFin) return EP.mostrarAlerta($error, 'Captura la fecha de inicio y la fecha de fin.');
    if (datos.fechaFin <= datos.fechaInicio) return EP.mostrarAlerta($error, 'La fecha fin debe ser mayor que la fecha inicio.');

    var peticion = c
      ? EP.api('PUT', '/calendariopago/' + c.idCalendarioPago, datos)
      : EP.api('POST', '/calendariopago/create', datos);

    var $boton = $(this).find('[type=submit]');
    EP.bloquearBoton($boton, true);
    peticion.done(function (r) {
      modalCalendario.hide();
      var guardado = r.calendarioPago ? Number(r.calendarioPago.idCalendarioPago) : null;
      cargarCalendarios(guardado);
    }).fail(function (mensaje) {
      EP.mostrarAlerta($error, mensaje);
    }).always(function () {
      EP.bloquearBoton($boton, false);
    });
  });

  $tabla.on('click', '[data-eliminar]', function () {
    var id = Number($(this).data('eliminar'));
    var c = estado.calendarios.find(function (x) { return x.idCalendarioPago === id; });
    if (!c) return;
    EP.confirmar({
      titulo: 'Eliminar calendario',
      texto: 'Se eliminará el calendario ' + tituloCalendario(c) + '.',
      resumen: ['El periodo deja de estar disponible para procesar; los horarios de los profesores no cambian.'],
      alConfirmar: function () {
        return EP.api('DELETE', '/calendariopago/' + id).done(function () {
          if (estado.seleccionado === id) estado.seleccionado = null;
          cargarCalendarios(null);
        });
      }
    });
  });

  $('#ep-btn-procesar').on('click', function () {
    var c = calendarioActual();
    if (!c || c.procesado) return;
    var t = totales();
    var resumen = estado.concentrado
      ? ['Empleados: ' + EP.numero(t.empleados), 'Horas a pagar: ' + EP.numero(t.pagar), 'Retenciones: ' + EP.dinero(t.retIsr + t.retIva), 'Neto a pagar: ' + EP.dinero(t.neto)]
      : ['El cálculo se hará con el régimen fiscal de cada empleado y los impuestos de su régimen.'];
    resumen.push('Después de procesarlo, el calendario ya no podrá editarse ni eliminarse.');
    EP.confirmar({
      titulo: 'Procesar pago',
      texto: 'Se procesará el calendario ' + tituloCalendario(c) + '.',
      tituloResumen: 'Resumen',
      resumen: resumen,
      peligro: false,
      icono: 'procesar',
      textoBoton: 'Procesar',
      alConfirmar: function () {
        var diferido = $.Deferred();
        EP.api('POST', '/pagos/procesar/' + c.idCalendarioPago).done(function (r) {
          diferido.resolve();
          // El filtro "Por procesar" ya no lo mostraría; se cambia a "Todos" para seguir viéndolo
          estado.filtro = 'todos';
          $('.segmented [data-filtro]').each(function () {
            var on = $(this).data('filtro') === 'todos';
            $(this).toggleClass('on', on).attr('aria-checked', on);
          });
          cargarCalendarios(c.idCalendarioPago).done(function () {
            EP.marcarGuardado($('#ep-estado-procesado'), 'Procesado: ' + (r.pagos || 0) + ' pago(s)');
          });
        }).fail(function (mensaje, xhr) {
          diferido.reject(xhr && xhr.status === 404
            ? 'El procesamiento todavía no existe en apirest (POST /pagos/procesar/{idCalendarioPago}). El calendario no se modificó.'
            : mensaje);
        });
        return diferido.promise();
      }
    });
  });

  // ---------- Inicio ----------
  cargarTurnos().always(function () { cargarCalendarios(null); });
});

  </script>
</body>
