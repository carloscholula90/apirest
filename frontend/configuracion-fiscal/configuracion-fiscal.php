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


    <div class="siaweb-ep" id="ep-pantalla-fiscal">

      <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
        <div>
          <h1 class="ep-titulo">Configuración fiscal</h1>
          <p class="ep-subtitulo">Regímenes fiscales, costo por hora por vigencia e impuestos y retenciones que aplican a cada uno.</p>
        </div>
        <button type="button" class="btn btn-primary" id="ep-btn-nuevo-regimen" disabled><span data-icono="mas" data-tam="18"></span>Nuevo régimen fiscal</button>
      </div>

      <div id="ep-alerta-general" class="d-none mb-3"></div>
      <div id="ep-alerta-sin-regimen" class="d-none mb-3"></div>
      <div class="info-box neutral mb-4 d-none" id="ep-aviso-pendiente">
        <span data-icono="info" data-tam="18"></span>
        <span>La API de régimen fiscal no respondió (<code>/regimenesfiscales</code>). Revisa que se hayan corrido las migraciones de apirest; el catálogo de impuestos funciona aparte.</span>
      </div>

      <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-end gap-4">
          <div style="flex: 0 1 300px;">
            <label class="form-label" for="ep-sel-regimen">Régimen fiscal</label>
            <select class="form-select" id="ep-sel-regimen" disabled><option>Sin regímenes</option></select>
          </div>
          <div class="readonly-row"><span class="rlabel">Empleados asignados</span><span class="rvalue" id="ep-regimen-empleados">—</span></div>
          <div class="readonly-row"><span class="rlabel">Costo por hora vigente</span><span class="rvalue" id="ep-regimen-costo">—</span></div>
          <div class="readonly-row"><span class="rlabel">Impuestos asignados</span><span class="rvalue" id="ep-regimen-num-impuestos">—</span></div>
        </div>
      </div>

      <div class="row g-4 align-items-start mb-4">
        <div class="col-12 col-xl-5">
          <section class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
              <h2 class="card-title"><span class="icon-badge"><span data-icono="dinero" data-tam="18"></span></span>Costo por hora</h2>
              <button type="button" class="btn btn-outline" id="ep-btn-nuevo-costo" disabled><span data-icono="mas"></span>Nuevo costo</button>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table">
                  <thead><tr><th>Vigencia desde</th><th>Hasta</th><th class="num">Costo por hora</th><th>Estado</th></tr></thead>
                  <tbody id="ep-tabla-costos"></tbody>
                </table>
              </div>
              <p class="ep-ayuda mt-3 mb-0">El histórico no se edita: un costo nuevo cierra la vigencia del anterior.</p>
            </div>
          </section>
        </div>

        <div class="col-12 col-xl-7">
          <section class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
              <h2 class="card-title"><span class="icon-badge"><span data-icono="porcentaje" data-tam="18"></span></span><span id="ep-titulo-impuestos-regimen">Impuestos del régimen</span></h2>
              <button type="button" class="btn btn-outline" id="ep-btn-asignar-impuesto" disabled><span data-icono="mas"></span>Asignar impuesto</button>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table">
                  <thead><tr><th>Impuesto</th><th class="num">Valor</th><th>Efecto</th><th><span class="visually-hidden">Acciones</span></th></tr></thead>
                  <tbody id="ep-tabla-impuestos-regimen"></tbody>
                </table>
              </div>
            </div>
          </section>
        </div>
      </div>

      <section class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
          <div>
            <h2 class="card-title">Catálogo de impuestos</h2>
            <p class="ep-ayuda mt-1 mb-0">Catálogo general. Cada régimen toma de aquí sus impuestos.</p>
          </div>
          <button type="button" class="btn btn-outline" id="ep-btn-nuevo-impuesto"><span data-icono="mas"></span>Nuevo impuesto</button>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table">
              <thead><tr><th>Clave</th><th>Impuesto</th><th class="num">Porcentaje</th><th>Efecto</th><th><span class="visually-hidden">Acciones</span></th></tr></thead>
              <tbody id="ep-tabla-catalogo"></tbody>
            </table>
          </div>
          <p class="ep-ayuda mt-3 mb-0">Un traslado (como el IVA) se suma a los honorarios; una retención (como el ISR retenido) se resta. Ambos se calculan sobre los honorarios.</p>
        </div>
      </section>
    </div>

  <!-- Nuevo / editar impuesto (mismo modal) -->
  <div class="modal fade siaweb-ep siaweb-ep-modal" id="ep-modal-impuesto" tabindex="-1" aria-labelledby="ep-titulo-modal-impuesto" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" id="ep-form-impuesto" novalidate>
        <div class="modal-header">
          <h2 class="modal-title" id="ep-titulo-modal-impuesto"><span class="icon-badge"><span data-icono="porcentaje" data-tam="18"></span></span><span class="ep-texto-titulo">Nuevo impuesto</span></h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div>
            <label class="form-label" for="ep-impuesto-descripcion">Descripción</label>
            <input class="form-control" id="ep-impuesto-descripcion" type="text" maxlength="255" placeholder="Ej. ISR RETENIDO">
          </div>
          <div>
            <label class="form-label" for="ep-impuesto-porcentaje">Porcentaje (%)</label>
            <input class="form-control" id="ep-impuesto-porcentaje" type="number" min="0" max="100" step="0.0001" placeholder="Ej. 10.6667">
            <div class="form-text">Se aplica sobre los honorarios.</div>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="ep-impuesto-retencion">
            <label class="form-check-label" for="ep-impuesto-retencion">Es retención (se descuenta del pago)</label>
          </div>
          <div class="ep-error d-none"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-link-danger me-auto d-none" id="ep-btn-eliminar-impuesto">Eliminar impuesto</button>
          <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><span data-icono="check"></span>Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Nuevo régimen fiscal -->
  <div class="modal fade siaweb-ep siaweb-ep-modal" id="ep-modal-regimen" tabindex="-1" aria-labelledby="ep-titulo-modal-regimen" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" id="ep-form-regimen" novalidate>
        <div class="modal-header">
          <h2 class="modal-title" id="ep-titulo-modal-regimen"><span class="icon-badge"><span data-icono="documento" data-tam="18"></span></span>Nuevo régimen fiscal</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div>
            <label class="form-label" for="ep-regimen-descripcion">Descripción</label>
            <input class="form-control" id="ep-regimen-descripcion" type="text" maxlength="255" placeholder="Ej. ASIMILADOS A SALARIOS">
          </div>
          <p class="ep-ayuda mb-0">Después de crearlo, registra su costo por hora y asígnale impuestos.</p>
          <div class="ep-error d-none"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><span data-icono="check"></span>Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Nuevo costo por hora -->
  <div class="modal fade siaweb-ep siaweb-ep-modal" id="ep-modal-costo" tabindex="-1" aria-labelledby="ep-titulo-modal-costo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" id="ep-form-costo" novalidate>
        <div class="modal-header">
          <h2 class="modal-title" id="ep-titulo-modal-costo"><span class="icon-badge"><span data-icono="dinero" data-tam="18"></span></span>Nuevo costo por hora</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="readonly-row"><span class="rlabel">Régimen fiscal</span><span class="rvalue" style="font-size:15px" id="ep-costo-regimen"></span></div>
          <div>
            <label class="form-label" for="ep-costo-valor">Costo por hora</label>
            <div class="amount-input"><span>$</span><input class="form-control" id="ep-costo-valor" type="number" min="0" step="0.01"></div>
            <div class="form-text">Importe antes de impuestos.</div>
          </div>
          <div>
            <label class="form-label" for="ep-costo-desde">Vigente desde</label>
            <input class="form-control" id="ep-costo-desde" type="date">
          </div>
          <div class="info-box stacked d-none" id="ep-costo-cierre">
            <strong style="font-size:13px">Se cierra la vigencia anterior</strong>
            <span></span>
          </div>
          <div class="ep-error d-none"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><span data-icono="check"></span>Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Asignar impuesto al régimen -->
  <div class="modal fade siaweb-ep siaweb-ep-modal" id="ep-modal-asignar" tabindex="-1" aria-labelledby="ep-titulo-modal-asignar" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" id="ep-form-asignar" novalidate>
        <div class="modal-header">
          <h2 class="modal-title" id="ep-titulo-modal-asignar"><span class="icon-badge"><span data-icono="porcentaje" data-tam="18"></span></span><span class="ep-texto-titulo">Asignar impuesto</span></h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div>
            <label class="form-label" for="ep-asignar-impuesto">Impuesto</label>
            <select class="form-select" id="ep-asignar-impuesto"></select>
            <div class="form-text">Solo aparecen impuestos que el régimen aún no tiene.</div>
          </div>
          <div class="ep-error d-none"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><span data-icono="check"></span>Asignar</button>
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
 * Configuración fiscal (Esquema de pago 2.3 y 2.4)
 * APIs: /impuestos (catálogo con porcentaje y retención) y /regimenesfiscales:
 *   GET    /regimenesfiscales                         -> { regimenesFiscales: [{ idRegimenFiscal, descripcion, empleados }] }
 *   GET    /regimenesfiscales/sinregimen              -> { empleados: [{ uid, nombre, primerApellido, segundoApellido }] }
 *   POST   /regimenesfiscales/create                  <- { descripcion }
 *   GET    /regimenesfiscales/{id}/costos             -> { costosHora: [{ costoHora, vigenciaInicio, vigenciaFin }] }
 *   POST   /regimenesfiscales/{id}/costos             <- { costoHora, vigenciaInicio }   (cierra la vigencia anterior)
 *   GET    /regimenesfiscales/{id}/impuestos          -> { impuestos: [{ idImpuesto, descripcion, porcentaje, esRetencion }] }
 *   POST   /regimenesfiscales/{id}/impuestos          <- { idImpuesto }
 *   DELETE /regimenesfiscales/{id}/impuestos/{idImpuesto}
 */
$(function () {
  'use strict';

  var estado = {
    catalogo: [],
    regimenes: [],
    idRegimen: null,
    costos: [],
    impuestosRegimen: [],
    impuestoEditando: null,
    apiRegimenDisponible: false
  };

  var modalImpuesto = new bootstrap.Modal('#ep-modal-impuesto');
  var modalRegimen = new bootstrap.Modal('#ep-modal-regimen');
  var modalCosto = new bootstrap.Modal('#ep-modal-costo');
  var modalAsignar = new bootstrap.Modal('#ep-modal-asignar');

  function regimenActual() {
    return estado.regimenes.find(function (r) { return r.idRegimenFiscal === estado.idRegimen; }) || null;
  }

  function costoVigente() {
    return estado.costos.find(function (c) { return !c.vigenciaFin; }) || null;
  }

  function diaAntes(iso) {
    var d = new Date(iso + 'T12:00:00');
    d.setDate(d.getDate() - 1);
    return d.toISOString().substr(0, 10);
  }

  // ---------- Catálogo de impuestos (conectado) ----------

  function cargarCatalogo() {
    $('#ep-tabla-catalogo').html(EP.filaCargando(5));
    return EP.api('GET', '/impuestos').done(function (r) {
      estado.catalogo = (r.impuestos || []).slice().sort(function (a, b) { return a.idImpuesto - b.idImpuesto; });
      pintarCatalogo();
    }).fail(function (mensaje) {
      $('#ep-tabla-catalogo').html(EP.filaVacia(5, 'alerta', 'No se pudo cargar el catálogo', mensaje));
    });
  }

  function pintarCatalogo() {
    $('#ep-tabla-catalogo').html(estado.catalogo.length ? estado.catalogo.map(function (i) {
      var retencion = Number(i.esRetencion) === 1;
      return '<tr><td>' + EP.escapar(i.idImpuesto) + '</td><td class="fw-semibold">' + EP.escapar($.trim(i.descripcion)) + '</td>'
        + '<td class="num">' + porcentaje(i.porcentaje) + '</td>'
        + '<td><span class="tag ' + (retencion ? 'bad' : 'info') + '">' + (retencion ? 'Retención' : 'Traslado') + '</span></td>'
        + '<td class="text-end"><button type="button" class="btn btn-icon" data-editar-impuesto="' + i.idImpuesto + '" aria-label="Editar '
        + EP.escapar($.trim(i.descripcion)) + '">' + EP.icono('editar') + '</button></td></tr>';
    }).join('') : EP.filaVacia(5, 'porcentaje', 'Sin impuestos', 'Agrega el primero con Nuevo impuesto.'));
  }

  function porcentaje(valor) {
    if (valor === undefined || valor === null) return '—';
    return String(Number(valor).toFixed(4)).replace(/0+$/, '').replace(/\.$/, '') + '%';
  }

  function cargarSinRegimen() {
    EP.api('GET', '/regimenesfiscales/sinregimen').done(function (r) {
      var lista = r.empleados || [];
      if (!lista.length) return EP.limpiarAlerta($('#ep-alerta-sin-regimen'));
      var nombres = lista.slice(0, 5).map(function (p) {
        return $.trim([p.primerApellido, p.segundoApellido, p.nombre].filter(Boolean).join(' ')).toUpperCase();
      }).join(', ') + (lista.length > 5 ? ' y ' + (lista.length - 5) + ' más' : '');
      EP.mostrarAlerta($('#ep-alerta-sin-regimen'), lista.length + ' empleado(s) activo(s) sin régimen fiscal (' + nombres
        + '). No se incluirán al procesar el pago.');
    });
  }

  function abrirImpuesto(impuesto) {
    estado.impuestoEditando = impuesto;
    $('#ep-impuesto-descripcion').val(impuesto ? $.trim(impuesto.descripcion) : '');
    $('#ep-impuesto-porcentaje').val(impuesto && impuesto.porcentaje !== undefined ? Number(impuesto.porcentaje) : '');
    $('#ep-impuesto-retencion').prop('checked', impuesto ? Number(impuesto.esRetencion) === 1 : false);
    $('#ep-btn-eliminar-impuesto').toggleClass('d-none', !impuesto);
    $('#ep-form-impuesto .ep-texto-titulo').text(impuesto ? 'Editar impuesto' : 'Nuevo impuesto');
    EP.limpiarAlerta($('#ep-form-impuesto .ep-error'));
    modalImpuesto.show();
  }

  $('#ep-btn-nuevo-impuesto').on('click', function () { abrirImpuesto(null); });

  $('#ep-tabla-catalogo').on('click', '[data-editar-impuesto]', function () {
    var id = Number($(this).data('editar-impuesto'));
    abrirImpuesto(estado.catalogo.find(function (i) { return Number(i.idImpuesto) === id; }));
  });

  $('#ep-form-impuesto').on('submit', function (e) {
    e.preventDefault();
    var $error = $(this).find('.ep-error');
    var impuesto = estado.impuestoEditando;
    var descripcion = $.trim($('#ep-impuesto-descripcion').val()).toUpperCase();
    if (!descripcion) return EP.mostrarAlerta($error, 'Escribe la descripción del impuesto.');
    var valor = $('#ep-impuesto-porcentaje').val() === '' ? 0 : Number($('#ep-impuesto-porcentaje').val());
    if (!(valor >= 0 && valor <= 100)) return EP.mostrarAlerta($error, 'El porcentaje debe estar entre 0 y 100.');

    var datos = { descripcion: descripcion, porcentaje: valor, esRetencion: $('#ep-impuesto-retencion').is(':checked') ? 1 : 0 };
    var peticion = impuesto
      ? EP.api('PUT', '/impuestos/' + impuesto.idImpuesto, $.extend({ idImpuesto: Number(impuesto.idImpuesto) }, datos))
      : EP.api('POST', '/impuestos/create', datos);

    var $boton = $(this).find('[type=submit]');
    EP.bloquearBoton($boton, true);
    peticion.done(function () {
      modalImpuesto.hide();
      cargarCatalogo().done(cargarRegimen);
    }).fail(function (mensaje) {
      EP.mostrarAlerta($error, mensaje);
    }).always(function () {
      EP.bloquearBoton($boton, false);
    });
  });

  $('#ep-btn-eliminar-impuesto').on('click', function () {
    var impuesto = estado.impuestoEditando;
    if (!impuesto) return;
    modalImpuesto.hide();
    EP.confirmar({
      titulo: 'Eliminar impuesto',
      texto: 'Se eliminará ' + $.trim(impuesto.descripcion) + ' del catálogo de impuestos.',
      resumen: ['Si algún régimen o factura lo usa, la API no permitirá eliminarlo.'],
      alConfirmar: function () {
        return EP.api('DELETE', '/impuestos/' + impuesto.idImpuesto).done(cargarCatalogo);
      }
    });
  });

  // ---------- Régimen fiscal (rutas propuestas) ----------

  function activarSeccionesRegimen(activar) {
    estado.apiRegimenDisponible = activar;
    $('#ep-aviso-pendiente').toggleClass('d-none', activar);
    $('#ep-btn-nuevo-regimen, #ep-sel-regimen').prop('disabled', !activar);
    $('#ep-btn-nuevo-costo, #ep-btn-asignar-impuesto').prop('disabled', !activar || estado.idRegimen === null);
  }

  function cargarRegimenes(seleccionar) {
    return EP.api('GET', '/regimenesfiscales').done(function (r) {
      estado.regimenes = (r.regimenesFiscales || []).map(function (x) {
        return $.extend({}, x, { idRegimenFiscal: Number(x.idRegimenFiscal), descripcion: $.trim(x.descripcion) });
      });
      var $sel = $('#ep-sel-regimen').empty();
      estado.regimenes.forEach(function (x) { $sel.append($('<option>').val(x.idRegimenFiscal).text(x.descripcion)); });
      var existe = estado.regimenes.some(function (x) { return x.idRegimenFiscal === seleccionar; });
      estado.idRegimen = existe ? seleccionar : (estado.regimenes[0] ? estado.regimenes[0].idRegimenFiscal : null);
      if (estado.idRegimen !== null) $sel.val(estado.idRegimen);
      activarSeccionesRegimen(true);
      cargarRegimen();
    }).fail(function (mensaje, xhr) {
      activarSeccionesRegimen(false);
      var pendiente = EP.estadoVacio('info', 'Pendiente en apirest', 'Se activa cuando exista la API de régimen fiscal.');
      $('#ep-tabla-costos').html('<tr><td colspan="4">' + pendiente + '</td></tr>');
      $('#ep-tabla-impuestos-regimen').html('<tr><td colspan="4">' + pendiente + '</td></tr>');
      if (xhr && xhr.status !== 404) EP.mostrarAlerta($('#ep-alerta-general'), 'No se pudieron cargar los regímenes fiscales. ' + mensaje);
    });
  }

  function cargarRegimen() {
    var regimen = regimenActual();
    $('#ep-btn-nuevo-costo, #ep-btn-asignar-impuesto').prop('disabled', !regimen);
    if (!regimen) {
      estado.costos = [];
      estado.impuestosRegimen = [];
      pintarRegimen();
      return;
    }
    $('#ep-tabla-costos').html(EP.filaCargando(4));
    $('#ep-tabla-impuestos-regimen').html(EP.filaCargando(4));
    var base = '/regimenesfiscales/' + regimen.idRegimenFiscal;
    $.when(
      EP.api('GET', base + '/costos', null, { vacioEn404: true }),
      EP.api('GET', base + '/impuestos', null, { vacioEn404: true })
    ).done(function (costos, impuestos) {
      estado.costos = ((costos && costos.costosHora) || []).slice().sort(function (a, b) {
        return String(b.vigenciaInicio).localeCompare(String(a.vigenciaInicio));
      });
      estado.impuestosRegimen = (impuestos && impuestos.impuestos) || [];
      pintarRegimen();
    }).fail(function (mensaje) {
      EP.mostrarAlerta($('#ep-alerta-general'), 'No se pudo cargar el régimen. ' + mensaje);
    });
  }

  function pintarRegimen() {
    var regimen = regimenActual();
    var vigente = costoVigente();
    $('#ep-regimen-empleados').text(regimen && regimen.empleados !== undefined ? regimen.empleados : '—');
    $('#ep-regimen-costo').text(vigente ? EP.dinero(vigente.costoHora) : 'Sin definir');
    $('#ep-regimen-num-impuestos').text(regimen ? estado.impuestosRegimen.length : '—');
    $('#ep-titulo-impuestos-regimen').text(regimen ? 'Impuestos de ' + regimen.descripcion : 'Impuestos del régimen');

    $('#ep-tabla-costos').html(estado.costos.length ? estado.costos.map(function (c) {
      var esVigente = !c.vigenciaFin;
      return '<tr><td>' + EP.fecha(c.vigenciaInicio) + '</td><td>' + (c.vigenciaFin ? EP.fecha(c.vigenciaFin) : 'Sin fecha fin') + '</td>'
        + '<td class="num fw-bold">' + EP.dinero(c.costoHora) + '</td>'
        + '<td><span class="tag ' + (esVigente ? 'good' : 'muted') + '">' + (esVigente ? 'Vigente' : 'Histórico') + '</span></td></tr>';
    }).join('') : EP.filaVacia(4, 'dinero', 'Sin costo por hora', 'Registra el costo por hora y desde cuándo aplica.'));

    $('#ep-tabla-impuestos-regimen').html(estado.impuestosRegimen.length ? estado.impuestosRegimen.map(function (i) {
      var retencion = Number(i.esRetencion) === 1;
      return '<tr><td class="fw-semibold">' + EP.escapar($.trim(i.descripcion)) + '</td>'
        + '<td class="num">' + (i.porcentaje !== undefined && i.porcentaje !== null ? EP.numero(i.porcentaje, 4).replace(/0+$/, '').replace(/[.,]$/, '') + '%' : '—') + '</td>'
        + '<td><span class="tag ' + (retencion ? 'bad' : 'info') + '">' + (retencion ? 'Retención' : 'Traslado') + '</span></td>'
        + '<td class="text-end"><button type="button" class="btn btn-icon del" data-quitar-impuesto="' + i.idImpuesto + '" aria-label="Quitar '
        + EP.escapar($.trim(i.descripcion)) + '">' + EP.icono('eliminar') + '</button></td></tr>';
    }).join('') : EP.filaVacia(4, 'porcentaje', 'Este régimen no tiene impuestos', 'Asigna los impuestos y retenciones que se calculan al pagarle a sus empleados.'));
  }

  $('#ep-sel-regimen').on('change', function () {
    estado.idRegimen = Number($(this).val());
    cargarRegimen();
  });

  $('#ep-btn-nuevo-regimen').on('click', function () {
    $('#ep-regimen-descripcion').val('');
    EP.limpiarAlerta($('#ep-form-regimen .ep-error'));
    modalRegimen.show();
  });

  $('#ep-form-regimen').on('submit', function (e) {
    e.preventDefault();
    var $error = $(this).find('.ep-error');
    var descripcion = $.trim($('#ep-regimen-descripcion').val()).toUpperCase();
    if (!descripcion) return EP.mostrarAlerta($error, 'Escribe la descripción del régimen.');
    var $boton = $(this).find('[type=submit]');
    EP.bloquearBoton($boton, true);
    EP.api('POST', '/regimenesfiscales/create', { descripcion: descripcion }).done(function (r) {
      modalRegimen.hide();
      cargarRegimenes(r.regimenFiscal ? Number(r.regimenFiscal.idRegimenFiscal) : null);
    }).fail(function (mensaje) {
      EP.mostrarAlerta($error, mensaje);
    }).always(function () {
      EP.bloquearBoton($boton, false);
    });
  });

  $('#ep-btn-nuevo-costo').on('click', function () {
    var regimen = regimenActual();
    if (!regimen) return;
    $('#ep-costo-regimen').text(regimen.descripcion);
    $('#ep-costo-valor').val('');
    $('#ep-costo-desde').val('');
    $('#ep-costo-cierre').addClass('d-none');
    EP.limpiarAlerta($('#ep-form-costo .ep-error'));
    modalCosto.show();
  });

  $('#ep-costo-desde').on('change', function () {
    var vigente = costoVigente();
    var desde = $(this).val();
    $('#ep-costo-cierre').toggleClass('d-none', !vigente || !desde).find('span').text(vigente && desde
      ? 'El costo de ' + EP.dinero(vigente.costoHora) + ' (desde ' + EP.fecha(vigente.vigenciaInicio) + ') quedará vigente hasta el ' + EP.fecha(diaAntes(desde)) + '.'
      : '');
  });

  $('#ep-form-costo').on('submit', function (e) {
    e.preventDefault();
    var $error = $(this).find('.ep-error');
    var costo = Number($('#ep-costo-valor').val());
    var desde = $('#ep-costo-desde').val();
    var vigente = costoVigente();
    if (!(costo > 0)) return EP.mostrarAlerta($error, 'Captura un costo por hora mayor a cero.');
    if (!desde) return EP.mostrarAlerta($error, 'Indica desde cuándo aplica el costo.');
    if (vigente && desde <= String(vigente.vigenciaInicio).substr(0, 10)) {
      return EP.mostrarAlerta($error, 'La nueva vigencia debe iniciar después del ' + EP.fecha(vigente.vigenciaInicio) + '.');
    }
    var $boton = $(this).find('[type=submit]');
    EP.bloquearBoton($boton, true);
    EP.api('POST', '/regimenesfiscales/' + estado.idRegimen + '/costos', { costoHora: costo, vigenciaInicio: desde }).done(function () {
      modalCosto.hide();
      cargarRegimen();
    }).fail(function (mensaje) {
      EP.mostrarAlerta($error, mensaje);
    }).always(function () {
      EP.bloquearBoton($boton, false);
    });
  });

  $('#ep-btn-asignar-impuesto').on('click', function () {
    var regimen = regimenActual();
    if (!regimen) return;
    var asignados = estado.impuestosRegimen.map(function (i) { return Number(i.idImpuesto); });
    var disponibles = estado.catalogo.filter(function (i) { return asignados.indexOf(Number(i.idImpuesto)) < 0; });
    if (!disponibles.length) {
      return EP.mostrarAlerta($('#ep-alerta-general'), 'El régimen ' + regimen.descripcion + ' ya tiene todos los impuestos del catálogo.');
    }
    var $sel = $('#ep-asignar-impuesto').empty();
    disponibles.forEach(function (i) { $sel.append($('<option>').val(i.idImpuesto).text($.trim(i.descripcion))); });
    $('#ep-form-asignar .ep-texto-titulo').text('Asignar impuesto a ' + regimen.descripcion);
    EP.limpiarAlerta($('#ep-form-asignar .ep-error'));
    modalAsignar.show();
  });

  $('#ep-form-asignar').on('submit', function (e) {
    e.preventDefault();
    var $error = $(this).find('.ep-error');
    var $boton = $(this).find('[type=submit]');
    EP.bloquearBoton($boton, true);
    EP.api('POST', '/regimenesfiscales/' + estado.idRegimen + '/impuestos', { idImpuesto: Number($('#ep-asignar-impuesto').val()) }).done(function () {
      modalAsignar.hide();
      cargarRegimen();
    }).fail(function (mensaje) {
      EP.mostrarAlerta($error, mensaje);
    }).always(function () {
      EP.bloquearBoton($boton, false);
    });
  });

  $('#ep-tabla-impuestos-regimen').on('click', '[data-quitar-impuesto]', function () {
    var id = Number($(this).data('quitar-impuesto'));
    var impuesto = estado.impuestosRegimen.find(function (i) { return Number(i.idImpuesto) === id; });
    var regimen = regimenActual();
    if (!impuesto || !regimen) return;
    EP.confirmar({
      titulo: 'Quitar impuesto del régimen',
      texto: 'Se quitará ' + $.trim(impuesto.descripcion) + ' del régimen ' + regimen.descripcion + '.',
      tituloResumen: 'Qué cambia',
      resumen: ['Los próximos pagos de los empleados de este régimen ya no lo calcularán. Los pagos ya procesados no cambian.'],
      textoBoton: 'Quitar',
      alConfirmar: function () {
        return EP.api('DELETE', '/regimenesfiscales/' + regimen.idRegimenFiscal + '/impuestos/' + id).done(cargarRegimen);
      }
    });
  });

  // ---------- Inicio ----------
  cargarCatalogo();
  cargarRegimenes(null);
  cargarSinRegimen();
});

  </script>
</body>
