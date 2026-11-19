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


    <div class="siaweb-ep" id="ep-pantalla-turnos">

      <div class="mb-4">
        <h1 class="ep-titulo">Configuración de turnos y bloques</h1>
        <p class="ep-subtitulo">Define los días y horarios de cada turno, los bloques que aplican a todo el turno y el catálogo global de tipos de bloque.</p>
      </div>

      <div id="ep-alerta-general" class="d-none mb-3"></div>

      <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-end gap-4">
          <div style="flex: 0 1 280px;">
            <label class="form-label" for="ep-sel-turno">Turno</label>
            <select class="form-select" id="ep-sel-turno"></select>
          </div>
          <div class="readonly-row"><span class="rlabel">Letra</span><span class="rvalue" id="ep-turno-letra">—</span></div>
          <div class="readonly-row"><span class="rlabel">Parciales</span><span class="rvalue" id="ep-turno-parciales">—</span></div>
          <div class="readonly-row"><span class="rlabel">Horario que cubre</span><span class="rvalue" id="ep-turno-horario">—</span></div>
          <div class="readonly-row"><span class="rlabel">Días del turno</span><div class="d-flex gap-1 mt-1" id="ep-turno-dias"></div></div>
        </div>
      </div>

      <div class="row g-4 align-items-start">
        <div class="col-12 col-xl-4">
          <section class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
              <h2 class="card-title"><span class="icon-badge"><span data-icono="calendario" data-tam="18"></span></span>Detalle de turno</h2>
              <button type="button" class="btn btn-outline" id="ep-btn-nuevo-detalle"><span data-icono="mas"></span>Nuevo registro</button>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table">
                  <thead><tr><th>Día</th><th>Hora inicio</th><th>Hora fin</th><th><span class="visually-hidden">Acciones</span></th></tr></thead>
                  <tbody id="ep-tabla-detalle"></tbody>
                </table>
              </div>
              <p class="ep-ayuda mt-3 mb-0">Un registro por día; no se permite repetir un día ni empalmar horarios.</p>
            </div>
          </section>
        </div>

        <div class="col-12 col-xl-5">
          <section class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
              <h2 class="card-title"><span class="icon-badge"><span data-icono="capas" data-tam="18"></span></span>Default por turno</h2>
              <button type="button" class="btn btn-outline" id="ep-btn-nuevo-default"><span data-icono="mas"></span>Nuevo bloque</button>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table">
                  <thead><tr><th>Tipo de bloque</th><th>Inicio</th><th>Fin</th><th>Estado</th><th><span class="visually-hidden">Acciones</span></th></tr></thead>
                  <tbody id="ep-tabla-default"></tbody>
                </table>
              </div>
              <div class="info-box mt-3">
                <span data-icono="info" data-tam="18"></span>
                <span>Aplican a todos los días del turno y se pintan automáticamente en el horario de cada profesor; desde ahí no se pueden editar.</span>
              </div>
            </div>
          </section>
        </div>

        <div class="col-12 col-xl-3">
          <section class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
              <h2 class="card-title"><span class="icon-badge"><span data-icono="cuadros" data-tam="18"></span></span>Tipos de bloque</h2>
              <button type="button" class="btn btn-outline" id="ep-btn-nuevo-tipo"><span data-icono="mas"></span>Nuevo tipo</button>
            </div>
            <div class="card-body">
              <p class="ep-ayuda mt-0 mb-2">Catálogo global: aplica a todos los turnos.</p>
              <div class="table-responsive">
                <table class="table">
                  <thead><tr><th>Descripción</th><th>Estado</th><th><span class="visually-hidden">Acciones</span></th></tr></thead>
                  <tbody id="ep-tabla-tipos"></tbody>
                </table>
              </div>
            </div>
          </section>
        </div>
      </div>
    </div>

  <!-- Nuevo día del turno -->
  <div class="modal fade siaweb-ep siaweb-ep-modal" id="ep-modal-detalle" tabindex="-1" aria-labelledby="ep-titulo-modal-detalle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" id="ep-form-detalle" novalidate>
        <div class="modal-header">
          <h2 class="modal-title" id="ep-titulo-modal-detalle"><span class="icon-badge"><span data-icono="calendario" data-tam="18"></span></span><span class="ep-texto-titulo">Nuevo día</span></h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div>
            <label class="form-label" for="ep-detalle-dia">Día de la semana</label>
            <select class="form-select" id="ep-detalle-dia"></select>
            <div class="form-text">Los días que ya tienen horario aparecen deshabilitados.</div>
          </div>
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label" for="ep-detalle-inicio">Hora inicio</label>
              <input class="form-control" id="ep-detalle-inicio" type="time">
            </div>
            <div class="col-6">
              <label class="form-label" for="ep-detalle-fin">Hora fin</label>
              <input class="form-control" id="ep-detalle-fin" type="time">
            </div>
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

  <!-- Nuevo / editar bloque por default (mismo modal) -->
  <div class="modal fade siaweb-ep siaweb-ep-modal" id="ep-modal-default" tabindex="-1" aria-labelledby="ep-titulo-modal-default" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" id="ep-form-default" novalidate>
        <div class="modal-header">
          <h2 class="modal-title" id="ep-titulo-modal-default"><span class="icon-badge"><span data-icono="capas" data-tam="18"></span></span><span class="ep-texto-titulo">Nuevo bloque por default</span></h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div>
            <label class="form-label" for="ep-default-tipo">Tipo de bloque</label>
            <select class="form-select" id="ep-default-tipo"></select>
            <div class="form-text d-none" id="ep-default-tipo-ayuda">El tipo no se puede cambiar al editar; elimina el bloque y créalo de nuevo.</div>
          </div>
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label" for="ep-default-inicio">Hora inicio</label>
              <input class="form-control" id="ep-default-inicio" type="time">
            </div>
            <div class="col-6">
              <label class="form-label" for="ep-default-fin">Hora fin</label>
              <input class="form-control" id="ep-default-fin" type="time">
            </div>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="ep-default-activo" checked>
            <label class="form-check-label" for="ep-default-activo">Activo</label>
          </div>
          <div class="info-box stacked">
            <strong style="font-size:13px">Horario permitido</strong>
            <span id="ep-default-rango"></span>
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

  <!-- Nuevo / editar tipo de bloque (mismo modal) -->
  <div class="modal fade siaweb-ep siaweb-ep-modal" id="ep-modal-tipo" tabindex="-1" aria-labelledby="ep-titulo-modal-tipo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" id="ep-form-tipo" novalidate>
        <div class="modal-header">
          <h2 class="modal-title" id="ep-titulo-modal-tipo"><span class="icon-badge"><span data-icono="cuadros" data-tam="18"></span></span><span class="ep-texto-titulo">Nuevo tipo de bloque</span></h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div>
            <label class="form-label" for="ep-tipo-descripcion">Descripción</label>
            <input class="form-control" id="ep-tipo-descripcion" type="text" maxlength="255" placeholder="Ej. TUTORÍA">
          </div>
          <div>
            <label class="form-label" for="ep-tipo-color">Color</label>
            <div class="d-flex align-items-center gap-3">
              <input class="form-control form-control-color" id="ep-tipo-color" type="color" value="#3B82F6" style="width:64px">
              <span class="ep-ayuda">Así se pinta el bloque en el horario del profesor.</span>
            </div>
          </div>
          <div class="d-flex flex-wrap gap-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="ep-tipo-activo" checked>
              <label class="form-check-label" for="ep-tipo-activo">Activo</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="ep-tipo-pagable" checked>
              <label class="form-check-label" for="ep-tipo-pagable">Se paga (cuenta para la hoja de pago)</label>
            </div>
          </div>
          <div class="ep-error d-none"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-link-danger me-auto d-none" id="ep-btn-eliminar-tipo">Eliminar tipo</button>
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
 * Configuración de turnos y bloques (Esquema de pago 1.2)
 * APIs: /turnos, /detallesturno, /defaultturno, /tipobloque
 */
$(function () {
  'use strict';

  var estado = {
    turnos: [],
    idTurno: null,
    detalle: [],
    defaults: [],
    tipos: [],
    defaultEditando: null,
    tipoEditando: null
  };

  var $tablaDetalle = $('#ep-tabla-detalle');
  var $tablaDefault = $('#ep-tabla-default');
  var $tablaTipos = $('#ep-tabla-tipos');
  var modalDetalle = new bootstrap.Modal('#ep-modal-detalle');
  var modalDefault = new bootstrap.Modal('#ep-modal-default');
  var modalTipo = new bootstrap.Modal('#ep-modal-tipo');

  function turnoActual() {
    return estado.turnos.find(function (t) { return t.idTurno === estado.idTurno; }) || null;
  }

  function tipoPorId(id) {
    return estado.tipos.find(function (t) { return Number(t.idTipoBloque) === Number(id); }) || null;
  }

  function diasDelTurno() {
    return estado.detalle.map(function (d) { return EP.diaNumero(d.diaSemana); }).filter(Boolean);
  }

  // ---------- Carga ----------

  function cargarTurnos(seleccionar) {
    return EP.api('GET', '/turnos').done(function (r) {
      // idTurno 0 es "TODOS": no es un turno real
      estado.turnos = (r.turnos || []).filter(function (t) { return Number(t.idTurno) !== 0; })
        .map(function (t) { return $.extend({}, t, { idTurno: Number(t.idTurno), descripcion: $.trim(t.descripcion) }); })
        .sort(function (a, b) { return a.descripcion.localeCompare(b.descripcion); });

      var $sel = $('#ep-sel-turno').empty();
      estado.turnos.forEach(function (t) {
        $sel.append($('<option>').val(t.idTurno).text(t.descripcion));
      });

      if (!estado.turnos.length) {
        estado.idTurno = null;
        pintarTurno();
        return;
      }
      var existe = estado.turnos.some(function (t) { return t.idTurno === seleccionar; });
      estado.idTurno = existe ? seleccionar : estado.turnos[0].idTurno;
      $sel.val(estado.idTurno);
      cargarTurno();
    }).fail(function (mensaje) {
      EP.mostrarAlerta($('#ep-alerta-general'), 'No se pudieron cargar los turnos. ' + mensaje);
    });
  }

  function cargarTipos() {
    return EP.api('GET', '/tipobloque').done(function (r) {
      estado.tipos = r.tiposBloque || [];
      pintarTipos();
    }).fail(function (mensaje) {
      $tablaTipos.html(EP.filaVacia(3, 'alerta', 'No se pudo cargar el catálogo', mensaje));
    });
  }

  function cargarTurno() {
    if (estado.idTurno === null) return;
    $tablaDetalle.html(EP.filaCargando(4));
    $tablaDefault.html(EP.filaCargando(5));

    var detalle = EP.api('GET', '/detallesturno/' + estado.idTurno, null, { vacioEn404: true });
    var defaults = EP.api('GET', '/defaultturno/' + estado.idTurno, null, { vacioEn404: true });

    detalle.done(function (r) {
      estado.detalle = ((r && r.detallesTurno) || []).slice().sort(function (a, b) {
        return EP.diaNumero(a.diaSemana) - EP.diaNumero(b.diaSemana);
      });
      pintarTurno();
      pintarDetalle();
    }).fail(function (mensaje) {
      $tablaDetalle.html(EP.filaVacia(4, 'alerta', 'No se pudo cargar el detalle', mensaje));
    });

    defaults.done(function (r) {
      estado.defaults = ((r && r.defaultTurnos) || []).slice().sort(function (a, b) {
        return EP.hora(a.horaInicio) < EP.hora(b.horaInicio) ? -1 : 1;
      });
      pintarDefaults();
    }).fail(function (mensaje) {
      $tablaDefault.html(EP.filaVacia(5, 'alerta', 'No se pudieron cargar los bloques', mensaje));
    });
  }

  // ---------- Pintado ----------

  function pintarTurno() {
    var turno = turnoActual();
    $('#ep-turno-letra').text(turno && turno.letra ? turno.letra : '—');
    $('#ep-turno-parciales').text(turno && turno.parciales !== null && turno.parciales !== undefined ? turno.parciales : '—');

    if (estado.detalle.length) {
      var inicio = estado.detalle.reduce(function (m, d) { return EP.hora(d.horaInicio) < m ? EP.hora(d.horaInicio) : m; }, '99:99');
      var fin = estado.detalle.reduce(function (m, d) { return EP.hora(d.horaFin) > m ? EP.hora(d.horaFin) : m; }, '00:00');
      $('#ep-turno-horario').text(inicio + ' – ' + fin);
    } else {
      $('#ep-turno-horario').text('Sin días definidos');
    }
    $('#ep-turno-dias').html(EP.chipsDias(diasDelTurno()));
    $('#ep-btn-nuevo-default').prop('disabled', !estado.detalle.length);
  }

  function pintarDetalle() {
    if (!estado.detalle.length) {
      $tablaDetalle.html(EP.filaVacia(4, 'calendario', 'Este turno aún no tiene días', 'Agrega un registro por cada día con su hora de inicio y fin.'));
      return;
    }
    $tablaDetalle.html(estado.detalle.map(function (d) {
      return '<tr>'
        + '<td class="fw-semibold">' + EP.escapar(EP.diaNombre(d.diaSemana) || d.diaSemana) + '</td>'
        + '<td>' + EP.hora(d.horaInicio) + '</td>'
        + '<td>' + EP.hora(d.horaFin) + '</td>'
        + '<td class="text-end"><button type="button" class="btn btn-icon del" data-eliminar-detalle="' + d.idDtlTurno + '" aria-label="Eliminar '
        + EP.escapar(EP.diaNombre(d.diaSemana)) + '">' + EP.icono('eliminar') + '</button></td>'
        + '</tr>';
    }).join(''));
  }

  function pintarDefaults() {
    if (!estado.defaults.length) {
      var texto = estado.detalle.length ? 'Agrega recesos o actividades que apliquen a todo el turno.' : 'Primero define los días del turno.';
      $tablaDefault.html(EP.filaVacia(5, 'capas', 'Sin bloques por default', texto));
      return;
    }
    $tablaDefault.html(estado.defaults.map(function (d) {
      var tipo = d.tipo_bloque || tipoPorId(d.idTipoBloque) || { descripcion: 'TIPO ' + d.idTipoBloque, color: '#999999' };
      var activo = Number(d.activo) === 1;
      return '<tr>'
        + '<td><span class="d-inline-flex align-items-center gap-2 fw-semibold"><span class="swatch" style="background:' + EP.escapar(tipo.color) + '"></span>'
        + EP.escapar($.trim(tipo.descripcion)) + '</span></td>'
        + '<td>' + EP.hora(d.horaInicio) + '</td>'
        + '<td>' + EP.hora(d.horaFin) + '</td>'
        + '<td><span class="tag ' + (activo ? 'good' : 'muted') + '">' + (activo ? 'Activo' : 'Inactivo') + '</span></td>'
        + '<td class="text-end text-nowrap">'
        + '<button type="button" class="btn btn-icon me-1" data-editar-default="' + d.idTipoBloque + '" aria-label="Editar ' + EP.escapar($.trim(tipo.descripcion)) + '">' + EP.icono('editar') + '</button>'
        + '<button type="button" class="btn btn-icon del" data-eliminar-default="' + d.idTipoBloque + '" aria-label="Eliminar ' + EP.escapar($.trim(tipo.descripcion)) + '">' + EP.icono('eliminar') + '</button>'
        + '</td></tr>';
    }).join(''));
  }

  function pintarTipos() {
    if (!estado.tipos.length) {
      $tablaTipos.html(EP.filaVacia(3, 'cuadros', 'Sin tipos de bloque', 'Crea el primero con Nuevo tipo.'));
      return;
    }
    $tablaTipos.html(estado.tipos.map(function (t) {
      var activo = Number(t.activo) === 1;
      return '<tr>'
        + '<td><span class="d-inline-flex align-items-center gap-2 fw-semibold"><span class="swatch" style="background:' + EP.escapar(t.color) + '"></span>'
        + EP.escapar($.trim(t.descripcion)) + '</span>'
        + (Number(t.pagable) === 0 ? '<div class="ep-ayuda">No se paga</div>' : '') + '</td>'
        + '<td><span class="tag ' + (activo ? 'good' : 'muted') + '">' + (activo ? 'Activo' : 'Inactivo') + '</span></td>'
        + '<td class="text-end"><button type="button" class="btn btn-icon" data-editar-tipo="' + t.idTipoBloque + '" aria-label="Editar '
        + EP.escapar($.trim(t.descripcion)) + '">' + EP.icono('editar') + '</button></td>'
        + '</tr>';
    }).join(''));
  }

  function rangoPermitido() {
    if (!estado.detalle.length) return 'El turno no tiene días definidos.';
    return estado.detalle.map(function (d) {
      return EP.diaNombre(d.diaSemana) + ' ' + EP.hora(d.horaInicio) + ' – ' + EP.hora(d.horaFin);
    }).join(' · ');
  }

  // ---------- Turno ----------

  $('#ep-sel-turno').on('change', function () {
    estado.idTurno = Number($(this).val());
    cargarTurno();
  });

  // ---------- Detalle de turno ----------

  $('#ep-btn-nuevo-detalle').on('click', function () {
    var turno = turnoActual();
    if (!turno) return;
    var usados = diasDelTurno();
    var $dia = $('#ep-detalle-dia').empty();
    var primeroLibre = null;
    for (var d = 1; d <= 7; d++) {
      var usado = usados.indexOf(d) >= 0;
      if (!usado && primeroLibre === null) primeroLibre = d;
      $dia.append($('<option>').val(d).text(EP.DIAS[d] + (usado ? ' (ya registrado)' : '')).prop('disabled', usado));
    }
    if (primeroLibre === null) {
      return EP.mostrarAlerta($('#ep-alerta-general'), 'El turno ' + turno.descripcion + ' ya tiene registrados los 7 días.');
    }
    $dia.val(primeroLibre);
    $('#ep-detalle-inicio').val('08:00');
    $('#ep-detalle-fin').val('14:00');
    $('#ep-form-detalle .ep-texto-titulo').text('Nuevo día en ' + turno.descripcion);
    EP.limpiarAlerta($('#ep-form-detalle .ep-error'));
    modalDetalle.show();
  });

  $('#ep-form-detalle').on('submit', function (e) {
    e.preventDefault();
    var $error = $(this).find('.ep-error');
    var datos = {
      idTurno: estado.idTurno,
      diaSemana: Number($('#ep-detalle-dia').val()),
      horaInicio: $('#ep-detalle-inicio').val(),
      horaFin: $('#ep-detalle-fin').val()
    };
    if (!datos.horaInicio || !datos.horaFin) return EP.mostrarAlerta($error, 'Captura la hora de inicio y la hora de fin.');
    if (datos.horaFin <= datos.horaInicio) return EP.mostrarAlerta($error, 'La hora fin debe ser mayor que la hora inicio.');

    var $boton = $(this).find('[type=submit]');
    EP.bloquearBoton($boton, true);
    EP.api('POST', '/detallesturno/create', datos).done(function () {
      modalDetalle.hide();
      cargarTurno();
    }).fail(function (mensaje) {
      EP.mostrarAlerta($error, mensaje);
    }).always(function () {
      EP.bloquearBoton($boton, false);
    });
  });

  $tablaDetalle.on('click', '[data-eliminar-detalle]', function () {
    var id = Number($(this).data('eliminar-detalle'));
    var d = estado.detalle.find(function (x) { return Number(x.idDtlTurno) === id; });
    var turno = turnoActual();
    if (!d || !turno) return;
    EP.confirmar({
      titulo: 'Eliminar día del turno',
      texto: 'Se eliminará el ' + EP.diaNombre(d.diaSemana) + ' (' + EP.hora(d.horaInicio) + ' – ' + EP.hora(d.horaFin) + ') del turno ' + turno.descripcion + '.',
      resumen: ['Los horarios de profesor capturados ese día quedarán fuera del turno.'],
      alConfirmar: function () {
        return EP.api('DELETE', '/detallesturno/' + estado.idTurno + '/' + id).done(cargarTurno);
      }
    });
  });

  // ---------- Default por turno ----------

  function abrirDefault(registro) {
    estado.defaultEditando = registro;
    var $tipo = $('#ep-default-tipo').empty();
    var usados = estado.defaults.map(function (d) { return Number(d.idTipoBloque); });
    estado.tipos.forEach(function (t) {
      var esActual = registro && Number(registro.idTipoBloque) === Number(t.idTipoBloque);
      // La llave es (turno, tipo): un tipo solo puede estar una vez por turno
      if (!esActual && (Number(t.activo) !== 1 || usados.indexOf(Number(t.idTipoBloque)) >= 0)) return;
      $tipo.append($('<option>').val(t.idTipoBloque).text($.trim(t.descripcion)));
    });
    if (!registro && !$tipo.children().length) {
      return EP.mostrarAlerta($('#ep-alerta-general'), 'Todos los tipos de bloque activos ya tienen un default en este turno.');
    }
    $tipo.prop('disabled', !!registro);
    $('#ep-default-tipo-ayuda').toggleClass('d-none', !registro);
    if (registro) $tipo.val(registro.idTipoBloque);
    $('#ep-default-inicio').val(registro ? EP.hora(registro.horaInicio) : '');
    $('#ep-default-fin').val(registro ? EP.hora(registro.horaFin) : '');
    $('#ep-default-activo').prop('checked', registro ? Number(registro.activo) === 1 : true);
    $('#ep-default-rango').text(rangoPermitido());
    $('#ep-form-default .ep-texto-titulo').text(registro ? 'Editar bloque por default' : 'Nuevo bloque por default');
    EP.limpiarAlerta($('#ep-form-default .ep-error'));
    modalDefault.show();
  }

  $('#ep-btn-nuevo-default').on('click', function () { abrirDefault(null); });

  $tablaDefault.on('click', '[data-editar-default]', function () {
    var id = Number($(this).data('editar-default'));
    abrirDefault(estado.defaults.find(function (d) { return Number(d.idTipoBloque) === id; }));
  });

  $('#ep-form-default').on('submit', function (e) {
    e.preventDefault();
    var $error = $(this).find('.ep-error');
    var editando = estado.defaultEditando;
    var idTipo = Number($('#ep-default-tipo').val());
    var inicio = $('#ep-default-inicio').val();
    var fin = $('#ep-default-fin').val();

    if (!inicio || !fin) return EP.mostrarAlerta($error, 'Captura la hora de inicio y la hora de fin.');
    if (fin <= inicio) return EP.mostrarAlerta($error, 'La hora fin debe ser mayor que la hora inicio.');
    var fuera = estado.detalle.find(function (d) { return inicio < EP.hora(d.horaInicio) || fin > EP.hora(d.horaFin); });
    if (fuera) {
      return EP.mostrarAlerta($error, 'El bloque debe quedar dentro del horario del ' + EP.diaNombre(fuera.diaSemana)
        + ' (' + EP.hora(fuera.horaInicio) + ' – ' + EP.hora(fuera.horaFin) + ').');
    }
    var choque = estado.defaults.find(function (d) {
      return Number(d.idTipoBloque) !== idTipo && EP.seEmpalman(inicio, fin, d.horaInicio, d.horaFin);
    });
    if (choque) {
      var tipoChoque = choque.tipo_bloque || tipoPorId(choque.idTipoBloque) || {};
      return EP.mostrarAlerta($error, 'Se empalma con ' + $.trim(tipoChoque.descripcion || 'otro bloque') + ' (' + EP.hora(choque.horaInicio) + ' – ' + EP.hora(choque.horaFin) + ').');
    }

    var datos = { horaInicio: inicio, horaFin: fin, activo: $('#ep-default-activo').is(':checked') ? 1 : 0 };
    var peticion = editando
      ? EP.api('PUT', '/defaultturno/' + estado.idTurno + '/' + idTipo, datos)
      : EP.api('POST', '/defaultturno/create', $.extend({ idTurno: estado.idTurno, idTipoBloque: idTipo }, datos));

    var $boton = $(this).find('[type=submit]');
    EP.bloquearBoton($boton, true);
    peticion.done(function () {
      modalDefault.hide();
      cargarTurno();
    }).fail(function (mensaje) {
      EP.mostrarAlerta($error, mensaje);
    }).always(function () {
      EP.bloquearBoton($boton, false);
    });
  });

  $tablaDefault.on('click', '[data-eliminar-default]', function () {
    var idTipo = Number($(this).data('eliminar-default'));
    var d = estado.defaults.find(function (x) { return Number(x.idTipoBloque) === idTipo; });
    var tipo = (d && (d.tipo_bloque || tipoPorId(idTipo))) || {};
    var turno = turnoActual();
    if (!d || !turno) return;
    EP.confirmar({
      titulo: 'Eliminar bloque por default',
      texto: 'Se eliminará ' + $.trim(tipo.descripcion || '') + ' (' + EP.hora(d.horaInicio) + ' – ' + EP.hora(d.horaFin) + ') del turno ' + turno.descripcion + '.',
      resumen: ['Dejará de pintarse automáticamente en el horario de todos los profesores del turno.'],
      alConfirmar: function () {
        return EP.api('DELETE', '/defaultturno/' + estado.idTurno + '/' + idTipo).done(cargarTurno);
      }
    });
  });

  // ---------- Tipos de bloque ----------

  function abrirTipo(tipo) {
    estado.tipoEditando = tipo;
    $('#ep-tipo-descripcion').val(tipo ? $.trim(tipo.descripcion) : '');
    $('#ep-tipo-color').val(tipo && /^#[0-9A-Fa-f]{6}$/.test(String(tipo.color).replace(';', '')) ? String(tipo.color).replace(';', '') : '#3B82F6');
    $('#ep-tipo-activo').prop('checked', tipo ? Number(tipo.activo) === 1 : true);
    $('#ep-tipo-pagable').prop('checked', tipo && tipo.pagable !== undefined ? Number(tipo.pagable) === 1 : true);
    $('#ep-btn-eliminar-tipo').toggleClass('d-none', !tipo);
    $('#ep-form-tipo .ep-texto-titulo').text(tipo ? 'Editar tipo de bloque' : 'Nuevo tipo de bloque');
    EP.limpiarAlerta($('#ep-form-tipo .ep-error'));
    modalTipo.show();
  }

  $('#ep-btn-nuevo-tipo').on('click', function () { abrirTipo(null); });

  $tablaTipos.on('click', '[data-editar-tipo]', function () {
    var id = Number($(this).data('editar-tipo'));
    abrirTipo(tipoPorId(id));
  });

  $('#ep-form-tipo').on('submit', function (e) {
    e.preventDefault();
    var $error = $(this).find('.ep-error');
    var tipo = estado.tipoEditando;
    var descripcion = $.trim($('#ep-tipo-descripcion').val()).toUpperCase();
    if (!descripcion) return EP.mostrarAlerta($error, 'Escribe la descripción del tipo de bloque.');
    var repetido = estado.tipos.find(function (t) {
      return $.trim(t.descripcion).toUpperCase() === descripcion && (!tipo || t.idTipoBloque !== tipo.idTipoBloque);
    });
    if (repetido) return EP.mostrarAlerta($error, 'Ya existe un tipo de bloque llamado ' + descripcion + '.');

    var datos = {
      descripcion: descripcion,
      color: $('#ep-tipo-color').val().toUpperCase(),
      activo: $('#ep-tipo-activo').is(':checked') ? 1 : 0,
      pagable: $('#ep-tipo-pagable').is(':checked') ? 1 : 0
    };
    var peticion = tipo
      ? EP.api('PUT', '/tipobloque/' + tipo.idTipoBloque, datos)
      : EP.api('POST', '/tipobloque/create', datos);

    var $boton = $(this).find('[type=submit]');
    EP.bloquearBoton($boton, true);
    peticion.done(function () {
      modalTipo.hide();
      cargarTipos().done(cargarTurno);
    }).fail(function (mensaje) {
      EP.mostrarAlerta($error, mensaje);
    }).always(function () {
      EP.bloquearBoton($boton, false);
    });
  });

  $('#ep-btn-eliminar-tipo').on('click', function () {
    var tipo = estado.tipoEditando;
    if (!tipo) return;
    modalTipo.hide();
    EP.confirmar({
      titulo: 'Eliminar tipo de bloque',
      texto: 'Se eliminará el tipo ' + $.trim(tipo.descripcion) + ' del catálogo global.',
      resumen: ['Si algún turno u horario de profesor lo usa, la API no permitirá eliminarlo; en ese caso márcalo como inactivo.'],
      alConfirmar: function () {
        return EP.api('DELETE', '/tipobloque/' + tipo.idTipoBloque).done(function () {
          cargarTipos();
        });
      }
    });
  });

  // ---------- Inicio ----------
  $tablaTipos.html(EP.filaCargando(3));
  cargarTipos().always(function () { cargarTurnos(null); });
});

  </script>
</body>
