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


    <div class="siaweb-ep" id="ep-pantalla-horario">

      <div class="mb-4">
        <h1 class="ep-titulo">Horario del profesor</h1>
        <p class="ep-subtitulo">Arma el horario semanal de cada profesor en bloques de 50 minutos (cada bloque cuenta como 1 hora). Los recesos del turno se pintan solos.</p>
      </div>

      <div id="ep-alerta-general" class="d-none mb-3"></div>

      <div class="card mb-4">
        <div class="card-body">
          <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-5">
              <label class="form-label" for="ep-buscar-profesor">Profesor</label>
              <div class="search-wrap">
                <input class="form-control" id="ep-buscar-profesor" type="text" autocomplete="off" placeholder="Busca por nombre o UID (mínimo 3 caracteres)"
                       role="combobox" aria-expanded="false" aria-controls="ep-resultados-profesor" aria-autocomplete="list">
                <span data-icono="buscar" data-tam="18"></span>
                <div class="list-group search-results d-none" id="ep-resultados-profesor" role="listbox"></div>
              </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
              <label class="form-label" for="ep-sel-turno">Turno</label>
              <select class="form-select" id="ep-sel-turno"></select>
            </div>
            <div class="col-6 col-lg-2">
              <label class="form-label" for="ep-vigencia-inicio">Vigencia desde</label>
              <input class="form-control" id="ep-vigencia-inicio" type="date">
            </div>
            <div class="col-6 col-lg-2">
              <label class="form-label" for="ep-vigencia-fin">Vigencia hasta</label>
              <input class="form-control" id="ep-vigencia-fin" type="date">
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4 d-none" id="ep-datos-profesor">
        <div class="card-body d-flex flex-wrap align-items-center gap-4 py-3">
          <div class="readonly-row"><span class="rlabel">UID</span><span class="rvalue" id="ep-profesor-uid"></span></div>
          <div class="readonly-row"><span class="rlabel">Profesor</span><span class="rvalue" id="ep-profesor-nombre"></span></div>
          <div class="readonly-row"><span class="rlabel">Horario del turno</span><span class="rvalue" id="ep-turno-horario"></span></div>
          <div class="readonly-row"><span class="rlabel">Días del turno</span><div class="d-flex gap-1 mt-1" id="ep-turno-dias"></div></div>
          <div class="readonly-row ms-lg-auto"><span class="rlabel">Horas pagables por semana</span><span class="rvalue" id="ep-total-horas">0</span></div>
        </div>
      </div>

      <section class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
          <h2 class="card-title">Arma el horario <span class="tag info ms-1" id="ep-tag-turno"></span></h2>
          <button type="button" class="btn btn-outline" id="ep-btn-imprimir" disabled><span data-icono="documento"></span>Imprimir horario global</button>
        </div>
        <div class="card-body d-flex flex-column gap-3">
          <!-- Tipos de bloque: solo informativo; el tipo se elige al seleccionar una celda -->
          <div class="leyenda" id="ep-leyenda" aria-label="Tipos de bloque"></div>
          <div class="info-box neutral d-none" id="ep-aviso-todos">
            <span data-icono="info" data-tam="18"></span>
            <span>Vista de todos los turnos, solo de consulta y sin recesos. Para agregar o cambiar bloques elige un turno.</span>
          </div>
          <div id="ep-zona-horario">
            <div class="table-empty">
              <span data-icono="buscar" data-tam="30"></span>
              <strong>Selecciona un profesor</strong>
              <span>Búscalo por nombre o UID para ver y capturar su horario.</span>
            </div>
          </div>
          <div class="info-box neutral d-none" id="ep-aviso-ocultos">
            <span data-icono="info" data-tam="18"></span>
            <span></span>
          </div>
          <div id="ep-resumen-horas"></div>
          <div class="d-flex justify-content-end" id="ep-estado-guardado" aria-live="polite"></div>
        </div>
      </section>
    </div>

  <!-- Horario global para imprimir (todos los turnos, sin recesos) -->
  <div class="siaweb-ep ep-impresion" id="ep-impresion" aria-hidden="true"></div>

  <!-- Nuevo / editar bloque (mismo modal) -->
  <div class="modal fade siaweb-ep siaweb-ep-modal" id="ep-modal-bloque" tabindex="-1" aria-labelledby="ep-titulo-modal-bloque" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" id="ep-form-bloque" novalidate>
        <div class="modal-header">
          <h2 class="modal-title" id="ep-titulo-modal-bloque"><span class="icon-badge"><span data-icono="reloj" data-tam="18"></span></span><span class="ep-texto-titulo">Nuevo bloque</span></h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="d-flex flex-wrap gap-4">
            <div class="readonly-row"><span class="rlabel">Profesor</span><span class="rvalue" style="font-size:15px" id="ep-bloque-profesor"></span></div>
            <div class="readonly-row"><span class="rlabel">Día</span><span class="rvalue" style="font-size:15px" id="ep-bloque-dia"></span></div>
            <div class="readonly-row"><span class="rlabel">Vigencia</span><span class="rvalue" style="font-size:15px" id="ep-bloque-vigencia"></span></div>
          </div>
          <div>
            <label class="form-label" for="ep-bloque-tipo">Tipo de bloque</label>
            <select class="form-select" id="ep-bloque-tipo"></select>
          </div>
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label" for="ep-bloque-inicio">Hora inicio</label>
              <select class="form-select" id="ep-bloque-inicio"></select>
            </div>
            <div class="col-6">
              <label class="form-label" for="ep-bloque-fin">Hora fin</label>
              <select class="form-select" id="ep-bloque-fin"></select>
            </div>
          </div>
          <div class="info-box stacked">
            <strong style="font-size:13px">Duración</strong>
            <span id="ep-bloque-duracion"></span>
          </div>
          <div class="ep-error d-none"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-link-danger me-auto d-none" id="ep-btn-eliminar-bloque">Eliminar bloque</button>
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
 * Horario del profesor (Esquema de pago 1.3)
 * APIs: /profesores/nuevo/{texto}, /turnos/detalles, /tipobloque, /defaultturno, /horarioprofesor
 *
 * La cuadrícula se arma en bloques de 50 minutos (1 bloque = 1 hora para el pago); los recesos del
 * turno cortan la secuencia. "TODOS" muestra los bloques de todos los turnos, sin recesos y solo
 * de consulta. Un bloque no puede traslaparse con otro del mismo profesor aunque sea de otro turno.
 */
$(function () {
  'use strict';

  var MODULO = 50; // minutos por bloque; equivale a 1 hora
  var TODOS = 'todos';

  var estado = {
    profesor: null,
    turnos: [],
    idTurno: null,
    tipos: [],
    defaults: [],
    horario: [],
    bloque: null // { dia, registro|null }
  };

  var modalBloque = new bootstrap.Modal('#ep-modal-bloque');
  var $zona = $('#ep-zona-horario');

  // ---------- Utilidades ----------

  function turnoPorId(id) {
    return estado.turnos.find(function (t) { return t.idTurno === Number(id); }) || null;
  }

  function turnoActual() {
    return estado.idTurno === TODOS ? null : turnoPorId(estado.idTurno);
  }

  function tipoPorId(id) {
    return estado.tipos.find(function (t) { return Number(t.idTipoBloque) === Number(id); }) || null;
  }

  function nombreCompleto(p) {
    return $.trim([p.primerApellido, p.segundoApellido, p.nombre].filter(Boolean).join(' ')).toUpperCase();
  }

  function colorTipo(tipo) {
    var c = String((tipo && tipo.color) || '#94A3B8').replace(';', '').trim();
    return /^#[0-9A-Fa-f]{6}$/.test(c) ? c : '#94A3B8';
  }

  function rangoDia(turno, numero) {
    return turno ? turno.dias.find(function (d) { return d.numero === numero; }) || null : null;
  }

  function vigencia() {
    return { inicio: $('#ep-vigencia-inicio').val(), fin: $('#ep-vigencia-fin').val() };
  }

  function enVigencia(h) {
    var v = vigencia();
    return (!v.inicio || String(h.fechaFin).substr(0, 10) >= v.inicio)
      && (!v.fin || String(h.fechaInicio).substr(0, 10) <= v.fin);
  }

  function defaultsDe(idTurno) {
    return estado.defaults.filter(function (d) { return Number(d.idTurno) === idTurno && Number(d.activo) === 1; })
      .sort(function (a, b) { return EP.minutos(a.horaInicio) - EP.minutos(b.horaInicio); });
  }

  // Tipos que algún turno pinta por default (como el receso): no se capturan a mano
  function tiposDefault() {
    return estado.defaults.filter(function (d) { return Number(d.activo) === 1; }).map(function (d) { return Number(d.idTipoBloque); });
  }

  function textoHoras(minutos) {
    return EP.numero(minutos / MODULO, minutos % MODULO ? 1 : 0) + ' h';
  }

  // Turno al que pertenece una actividad: el que tiene ese día y la contiene completa
  function turnoDeBloque(h) {
    var dia = EP.diaNumero(h.diaSemana);
    return estado.turnos.find(function (t) {
      var r = rangoDia(t, dia);
      return r && EP.hora(h.horaInicio) >= r.inicio && EP.hora(h.horaFin) <= r.fin;
    }) || null;
  }

  // ---------- Renglones de la cuadrícula ----------

  // Bloques de 50 minutos desde el inicio del turno; cada receso corta la secuencia
  function filasTurno(turno) {
    var filas = [];
    var t = EP.minutos(turno.inicio), fin = EP.minutos(turno.fin);
    function cortar(a, b) {
      while (a < b) {
        var z = Math.min(a + MODULO, b);
        filas.push({ ini: a, fin: z, tipo: 'modulo' });
        a = z;
      }
    }
    defaultsDe(turno.idTurno).forEach(function (d) {
      var di = EP.minutos(d.horaInicio), df = EP.minutos(d.horaFin);
      if (di < t || df > fin) return;
      cortar(t, di);
      filas.push({ ini: di, fin: df, tipo: 'receso', def: d });
      t = df;
    });
    cortar(t, fin);
    return filas;
  }

  // Todos los turnos juntos: los límites de sus bloques y de las actividades del profesor
  function filasGlobales(bloques) {
    var modulos = [];
    estado.turnos.forEach(function (t) {
      filasTurno(t).forEach(function (f) { if (f.tipo === 'modulo') modulos.push(f); });
    });
    var limites = {};
    modulos.forEach(function (f) { limites[f.ini] = true; limites[f.fin] = true; });
    bloques.forEach(function (h) { limites[EP.minutos(h.horaInicio)] = true; limites[EP.minutos(h.horaFin)] = true; });
    var puntos = Object.keys(limites).map(Number).sort(function (a, b) { return a - b; });
    var filas = [];
    for (var i = 0; i + 1 < puntos.length; i++) {
      var a = puntos[i], b = puntos[i + 1];
      var cubierto = modulos.some(function (f) { return f.ini <= a && b <= f.fin; })
        || bloques.some(function (h) { return EP.minutos(h.horaInicio) <= a && b <= EP.minutos(h.horaFin); });
      filas.push({ ini: a, fin: b, tipo: cubierto ? 'modulo' : 'hueco' });
    }
    return filas;
  }

  function vistaActual() {
    var turno = turnoActual();
    if (turno) {
      return { modo: 'turno', turno: turno, filas: filasTurno(turno), dias: turno.dias };
    }
    return vistaGlobal();
  }

  function vistaGlobal() {
    var bloques = estado.horario.filter(enVigencia);
    var numeros = {};
    estado.turnos.forEach(function (t) { t.dias.forEach(function (d) { numeros[d.numero] = true; }); });
    bloques.forEach(function (h) { var n = EP.diaNumero(h.diaSemana); if (n) numeros[n] = true; });
    var dias = Object.keys(numeros).map(Number).sort(function (a, b) { return a - b; }).map(function (n) { return { numero: n }; });
    return { modo: TODOS, turno: null, filas: filasGlobales(bloques), dias: dias };
  }

  function ubicar(filas, ini, fin) {
    var i = filas.findIndex(function (f) { return f.ini <= ini && ini < f.fin; });
    var j = filas.findIndex(function (f) { return f.ini < fin && fin <= f.fin; });
    return i < 0 || j < 0 || j < i ? null : { desde: i, hasta: j };
  }

  /*
   * Arma el HTML de la cuadrícula. opciones.editable: celdas y bloques propios con clic (solo vista de un turno).
   * Devuelve { html, ocultos, minutosPorTipo }.
   */
  function construirCuadricula(vista, opciones) {
    var filas = vista.filas;
    var dias = vista.dias;
    var resultado = { html: '', ocultos: 0, minutosPorTipo: {} };
    if (!filas.length || !dias.length) {
      resultado.html = EP.estadoVacio('calendario', 'Sin horario que mostrar', 'No hay turnos con días configurados.');
      return resultado;
    }

    var columna = function (numero) { return dias.findIndex(function (d) { return d.numero === numero; }) + 2; };
    var alto = function (f) { return f.tipo === 'modulo' ? '46px' : f.tipo === 'receso' ? '28px' : '10px'; };
    var html = '<div class="hgrid" style="grid-template-columns:96px repeat(' + dias.length + ', minmax(110px, 1fr));grid-template-rows:36px '
      + filas.map(alto).join(' ') + '">';
    html += '<div class="hhead" style="grid-column:1;grid-row:1">Hora</div>';
    dias.forEach(function (d) {
      html += '<div class="hhead" style="grid-column:' + columna(d.numero) + ';grid-row:1">' + EP.DIAS[d.numero] + '</div>';
    });

    filas.forEach(function (f, i) {
      var etiqueta = f.tipo === 'hueco' ? '' : EP.horaDesdeMinutos(f.ini) + ' – ' + EP.horaDesdeMinutos(f.fin);
      html += '<div class="hhour ' + f.tipo + '" style="grid-column:1;grid-row:' + (i + 2) + '">' + etiqueta + '</div>';
      if (f.tipo === 'receso') {
        var t = f.def.tipo_bloque || tipoPorId(f.def.idTipoBloque) || {};
        html += '<div class="hreceso" style="grid-column:2 / -1;grid-row:' + (i + 2) + ';border-color:' + colorTipo(t) + ';background:' + colorTipo(t) + '22">'
          + EP.icono('candado', 12) + EP.escapar($.trim(t.descripcion)) + ' del turno</div>';
      } else if (f.tipo === 'hueco') {
        html += '<div class="hhueco" style="grid-column:2 / -1;grid-row:' + (i + 2) + '"></div>';
      }
    });

    var ocupado = {};
    var bloques = '';
    estado.horario.filter(enVigencia).forEach(function (h) {
      var numero = EP.diaNumero(h.diaSemana);
      var col = columna(numero);
      var ini = EP.minutos(h.horaInicio), fin = EP.minutos(h.horaFin);
      var t = h.tipo_bloque || tipoPorId(h.idTipoBloque) || {};
      var deTurno = turnoDeBloque(h);
      var rangoVista = vista.turno ? rangoDia(vista.turno, numero) : null;
      // En la vista de un turno, es propia si cae completa dentro del horario de ese turno ese día
      var propio = vista.modo === TODOS
        || (rangoVista && EP.hora(h.horaInicio) >= rangoVista.inicio && EP.hora(h.horaFin) <= rangoVista.fin);
      if (!propio && vista.turno && deTurno && deTurno.idTurno === vista.turno.idTurno) deTurno = null;
      var pos = null;

      if (col >= 2) {
        if (propio) {
          pos = ubicar(filas, ini, fin);
        } else {
          // Actividad de otro turno que cae en este horario: se muestra como ocupada
          var rango = rangoDia(vista.turno, numero);
          var a = Math.max(ini, rango ? EP.minutos(rango.inicio) : ini), z = Math.min(fin, rango ? EP.minutos(rango.fin) : fin);
          if (rango && a < z) pos = ubicar(filas, a, z);
        }
      }
      if (!pos) {
        if (propio || vista.modo === TODOS) resultado.ocultos++;
        return;
      }
      if (propio) resultado.minutosPorTipo[h.idTipoBloque] = (resultado.minutosPorTipo[h.idTipoBloque] || 0) + fin - ini;
      for (var r = pos.desde; r <= pos.hasta; r++) ocupado[numero + '@' + r] = true;

      var estilo = 'grid-column:' + col + ';grid-row:' + (pos.desde + 2) + ' / ' + (pos.hasta + 3) + ';border-color:' + colorTipo(t) + ';background:' + colorTipo(t) + '22';
      var texto = '<strong>' + EP.escapar($.trim(t.descripcion)) + '</strong><span>' + EP.hora(h.horaInicio) + ' – ' + EP.hora(h.horaFin) + '</span>';
      if (!propio) {
        bloques += '<div class="hblk otro" style="' + estilo + '" title="Ocupado en otro turno"><strong>OCUPADO</strong><span>'
          + EP.escapar(deTurno ? deTurno.descripcion : 'Otro turno') + ' · ' + EP.hora(h.horaInicio) + ' – ' + EP.hora(h.horaFin) + '</span></div>';
      } else if (opciones.editable) {
        bloques += '<button type="button" class="hblk" data-secuencia="' + h.secuencia + '" style="' + estilo + '" aria-label="Editar '
          + EP.escapar($.trim(t.descripcion)) + ', ' + EP.diaNombre(h.diaSemana) + ' ' + EP.hora(h.horaInicio) + ' a ' + EP.hora(h.horaFin) + '">' + texto + '</button>';
      } else {
        bloques += '<div class="hblk fixed" style="' + estilo + ';border-style:solid">' + texto
          + (vista.modo === TODOS && deTurno ? '<span>' + EP.escapar(deTurno.descripcion) + '</span>' : '') + '</div>';
      }
    });

    filas.forEach(function (f, i) {
      if (f.tipo !== 'modulo') return;
      dias.forEach(function (d) {
        if (ocupado[d.numero + '@' + i]) return;
        var lugar = 'grid-column:' + columna(d.numero) + ';grid-row:' + (i + 2);
        var rango = vista.turno ? rangoDia(vista.turno, d.numero) : null;
        var dentro = rango && f.ini >= EP.minutos(rango.inicio) && f.fin <= EP.minutos(rango.fin);
        if (opciones.editable && dentro) {
          html += '<button type="button" class="hcell" style="' + lugar + '" data-dia="' + d.numero + '" data-fila="' + i + '" '
            + 'aria-label="Agregar bloque ' + EP.DIAS[d.numero] + ' ' + EP.horaDesdeMinutos(f.ini) + '"></button>';
        } else {
          html += '<div class="hcell" style="' + lugar + (vista.modo === TODOS ? '' : ';background:var(--ep-surface-muted)') + '"></div>';
        }
      });
    });

    resultado.html = '<div class="grid-wrap">' + html + bloques + '</div></div>';
    return resultado;
  }

  // ---------- Carga ----------

  function cargarCatalogos() {
    var turnos = EP.api('GET', '/turnos/detalles').done(function (r) {
      estado.turnos = (r.turnos || []).map(function (t) {
        var dias = (t.diasTurno || []).map(function (d) {
          return { numero: EP.diaNumero(d.diaSemana), inicio: EP.hora(d.horaInicio), fin: EP.hora(d.horaFin) };
        }).filter(function (d) { return d.numero; }).sort(function (a, b) { return a.numero - b.numero; });
        return {
          idTurno: Number(t.idTurno),
          descripcion: $.trim(t.descripcion),
          dias: dias,
          inicio: dias.reduce(function (m, d) { return d.inicio < m ? d.inicio : m; }, '99:99'),
          fin: dias.reduce(function (m, d) { return d.fin > m ? d.fin : m; }, '00:00')
        };
      }).filter(function (t) { return t.dias.length; });

      var $sel = $('#ep-sel-turno').empty();
      estado.turnos.forEach(function (t) {
        $sel.append($('<option>').val(t.idTurno).text(t.descripcion + ' (' + t.inicio + ' – ' + t.fin + ')'));
      });
      if (estado.turnos.length) {
        $sel.append($('<option>').val(TODOS).text('TODOS (consulta, sin recesos)'));
        estado.idTurno = estado.turnos[0].idTurno;
        $sel.val(estado.idTurno);
      } else {
        EP.mostrarAlerta($('#ep-alerta-general'), 'No hay turnos con días configurados. Defínelos primero en Configuración de turnos y bloques.');
      }
    });

    var tipos = EP.api('GET', '/tipobloque').done(function (r) {
      estado.tipos = r.tiposBloque || [];
    });

    var defaults = EP.api('GET', '/defaultturno', null, { vacioEn404: true }).done(function (r) {
      estado.defaults = (r && r.defaultTurnos) || [];
    });

    return $.when(turnos, tipos, defaults).fail(function (mensaje) {
      EP.mostrarAlerta($('#ep-alerta-general'), 'No se pudieron cargar los catálogos. ' + mensaje);
    });
  }

  function cargarHorario() {
    if (!estado.profesor) return $.Deferred().resolve().promise();
    $zona.html('<div class="table-empty"><span class="spinner-border spinner-border-sm" role="status"></span><span>Cargando horario…</span></div>');
    return EP.api('GET', '/horarioprofesor/' + estado.profesor.uid, null, { vacioEn404: true }).done(function (r) {
      estado.horario = (r && r.horariosProfesor) || [];
    }).fail(function (mensaje) {
      estado.horario = [];
      EP.mostrarAlerta($('#ep-alerta-general'), 'No se pudo cargar el horario del profesor. ' + mensaje);
    });
  }

  // ---------- Pintado ----------

  function pintarLeyenda() {
    var excluidos = tiposDefault();
    var items = estado.tipos.filter(function (t) { return Number(t.activo) === 1 && excluidos.indexOf(Number(t.idTipoBloque)) < 0; })
      .map(function (t) {
        return '<span class="leyenda-item"><span class="leyenda-muestra" style="border-color:' + colorTipo(t) + ';background:' + colorTipo(t) + '22"></span>'
          + EP.escapar($.trim(t.descripcion)) + '</span>';
      });
    var turno = turnoActual();
    if (turno) {
      defaultsDe(turno.idTurno).forEach(function (d) {
        var t = d.tipo_bloque || tipoPorId(d.idTipoBloque) || {};
        items.push('<span class="leyenda-item"><span class="leyenda-muestra" style="border-style:dashed;border-color:' + colorTipo(t) + ';background:' + colorTipo(t) + '22"></span>'
          + EP.escapar($.trim(t.descripcion)) + ' (lo pone el turno)</span>');
      });
      items.push('<span class="leyenda-item"><span class="leyenda-muestra" style="border-style:dotted;border-color:var(--ep-text-muted);background:var(--ep-surface-muted)"></span>Ocupado en otro turno</span>');
    }
    $('#ep-leyenda').html('<span class="rlabel me-1">Tipos de bloque</span>' + items.join(''));
  }

  function pintarTodo() {
    var turno = turnoActual();
    var vista = vistaActual();
    pintarLeyenda();

    $('#ep-tag-turno').text(turno ? turno.descripcion : 'TODOS');
    $('#ep-turno-horario').text(turno ? turno.inicio + ' – ' + turno.fin : 'Todos los turnos');
    $('#ep-turno-dias').html(EP.chipsDias(vista.dias.map(function (d) { return d.numero; })));
    $('#ep-aviso-todos').toggleClass('d-none', !!turno);
    $('#ep-btn-imprimir').prop('disabled', !estado.profesor);

    var $aviso = $('#ep-aviso-ocultos');
    if (!estado.profesor) {
      $zona.html(EP.estadoVacio('buscar', 'Selecciona un profesor', 'Búscalo por nombre o UID para ver y capturar su horario.'));
      $aviso.addClass('d-none');
      $('#ep-resumen-horas').empty();
      return;
    }

    var res = construirCuadricula(vista, { editable: !!turno });
    $zona.html(res.html);
    $aviso.toggleClass('d-none', res.ocultos <= 0).find('span').last()
      .text(res.ocultos + ' actividad(es) del profesor están fuera de este turno o de su horario y no se muestran. Elige TODOS para verlas.');

    var total = 0;
    var detalle = Object.keys(res.minutosPorTipo).map(function (id) {
      var t = tipoPorId(id) || { descripcion: 'TIPO ' + id };
      if (Number(t.pagable) !== 0) total += res.minutosPorTipo[id];
      return '<span class="leyenda-item"><span class="leyenda-muestra" style="border-color:' + colorTipo(t) + ';background:' + colorTipo(t) + '22"></span>'
        + EP.escapar($.trim(t.descripcion)) + ': <strong>' + textoHoras(res.minutosPorTipo[id]) + '</strong></span>';
    });
    $('#ep-resumen-horas').html(detalle.length
      ? '<div class="leyenda"><span class="rlabel me-1">Horas por semana' + (turno ? ' en ' + EP.escapar(turno.descripcion) : '') + '</span>' + detalle.join('') + '</div>'
      : '<p class="ep-ayuda mb-0">Sin actividades capturadas' + (turno ? ' en este turno' : '') + '.</p>');
    $('#ep-total-horas').text(textoHoras(total));
  }

  // ---------- Búsqueda de profesor ----------

  var temporizador = null;
  var $buscar = $('#ep-buscar-profesor');
  var $resultados = $('#ep-resultados-profesor');

  function cerrarResultados() {
    $resultados.addClass('d-none').empty();
    $buscar.attr('aria-expanded', 'false');
  }

  $buscar.on('input', function () {
    var texto = $.trim($buscar.val());
    clearTimeout(temporizador);
    if (texto.length < 3) return cerrarResultados();
    temporizador = setTimeout(function () {
      EP.api('GET', '/profesores/nuevo/' + encodeURIComponent(texto)).done(function (r) {
        var lista = r.profesores || [];
        $resultados.html(lista.length ? lista.map(function (p) {
          return '<button type="button" class="list-group-item list-group-item-action" role="option" data-uid="' + EP.escapar(p.uid) + '" data-nombre="'
            + EP.escapar(nombreCompleto(p)) + '"><strong>' + EP.escapar(nombreCompleto(p)) + '</strong> <span class="text-muted">· UID ' + EP.escapar(p.uid) + '</span></button>';
        }).join('') : '<div class="list-group-item text-muted">No se encontraron profesores.</div>').removeClass('d-none');
        $buscar.attr('aria-expanded', 'true');
      }).fail(function (mensaje) {
        $resultados.html('<div class="list-group-item text-danger">' + EP.escapar(mensaje) + '</div>').removeClass('d-none');
      });
    }, 300);
  });

  $resultados.on('click', '[data-uid]', function () {
    var $item = $(this);
    estado.profesor = { uid: Number($item.data('uid')), nombre: String($item.data('nombre')) };
    $buscar.val(estado.profesor.nombre);
    cerrarResultados();
    $('#ep-profesor-uid').text(estado.profesor.uid);
    $('#ep-profesor-nombre').text(estado.profesor.nombre);
    $('#ep-datos-profesor').removeClass('d-none');
    EP.limpiarAlerta($('#ep-alerta-general'));
    cargarHorario().done(function () {
      // Propone la vigencia más reciente del profesor; si no tiene horario, el semestre a partir de hoy
      var ultima = estado.horario.slice().sort(function (a, b) { return String(b.fechaFin).localeCompare(String(a.fechaFin)); })[0];
      $('#ep-vigencia-inicio').val(ultima ? String(ultima.fechaInicio).substr(0, 10) : EP.hoyIso());
      $('#ep-vigencia-fin').val(ultima ? String(ultima.fechaFin).substr(0, 10) : EP.hoyIso(180));
    }).always(pintarTodo);
  });

  $(document).on('click', function (e) {
    if (!$(e.target).closest('.search-wrap').length) cerrarResultados();
  });

  // ---------- Filtros ----------

  $('#ep-sel-turno').on('change', function () {
    var valor = $(this).val();
    estado.idTurno = valor === TODOS ? TODOS : Number(valor);
    pintarTodo();
  });

  $('#ep-vigencia-inicio, #ep-vigencia-fin').on('change', pintarTodo);

  // ---------- Bloques ----------

  // Bloques de 50 minutos del día dentro del horario del turno (sin recesos)
  function modulosDelDia(turno, numeroDia) {
    var rango = rangoDia(turno, numeroDia);
    if (!rango) return [];
    return filasTurno(turno).filter(function (f) {
      return f.tipo === 'modulo' && f.ini >= EP.minutos(rango.inicio) && f.fin <= EP.minutos(rango.fin);
    });
  }

  function llenarInicio(modulos, valor) {
    var $ini = $('#ep-bloque-inicio').empty();
    var opciones = modulos.map(function (m) { return m.ini; });
    if (valor !== undefined && opciones.indexOf(valor) < 0) opciones.push(valor);
    opciones.sort(function (a, b) { return a - b; }).forEach(function (m) {
      $ini.append($('<option>').val(m).text(EP.horaDesdeMinutos(m)));
    });
    $ini.val(valor);
  }

  // La hora fin avanza de bloque en bloque y se detiene en el siguiente receso
  function llenarFin(modulos, inicio, valor) {
    var $fin = $('#ep-bloque-fin').empty();
    var opciones = [];
    var i = modulos.findIndex(function (m) { return m.ini === inicio; });
    if (i >= 0) {
      for (var k = i; k < modulos.length; k++) {
        if (k > i && modulos[k].ini !== modulos[k - 1].fin) break;
        opciones.push(modulos[k].fin);
      }
    }
    if (valor !== undefined && valor > inicio && opciones.indexOf(valor) < 0) opciones.push(valor);
    opciones.sort(function (a, b) { return a - b; }).forEach(function (m) {
      $fin.append($('<option>').val(m).text(EP.horaDesdeMinutos(m)));
    });
    $fin.val(opciones.indexOf(valor) >= 0 ? valor : opciones[0]);
    pintarDuracion();
  }

  function pintarDuracion() {
    var minutos = Number($('#ep-bloque-fin').val()) - Number($('#ep-bloque-inicio').val());
    $('#ep-bloque-duracion').text(minutos > 0
      ? minutos + ' minutos · cuenta como ' + textoHoras(minutos).replace(' h', minutos === MODULO ? ' hora' : ' horas')
      : '—');
  }

  function abrirBloque(numeroDia, registro, indiceFila) {
    var turno = turnoActual();
    var v = vigencia();
    if (!turno) return;
    if (!registro && (!v.inicio || !v.fin || v.fin < v.inicio)) {
      return EP.mostrarAlerta($('#ep-alerta-general'), 'Captura una vigencia válida (desde / hasta) antes de agregar bloques.');
    }

    estado.bloque = { dia: numeroDia, registro: registro };
    var excluidos = tiposDefault();
    var $tipo = $('#ep-bloque-tipo').empty();
    estado.tipos.forEach(function (t) {
      var esActual = registro && Number(registro.idTipoBloque) === Number(t.idTipoBloque);
      if (!esActual && (Number(t.activo) !== 1 || excluidos.indexOf(Number(t.idTipoBloque)) >= 0)) return;
      $tipo.append($('<option>').val(t.idTipoBloque).text($.trim(t.descripcion)));
    });
    if (registro) $tipo.val(registro.idTipoBloque);

    var modulos = modulosDelDia(turno, numeroDia);
    var filas = filasTurno(turno);
    var inicio = registro ? EP.minutos(registro.horaInicio) : filas[indiceFila].ini;
    var fin = registro ? EP.minutos(registro.horaFin) : filas[indiceFila].fin;
    llenarInicio(modulos, inicio);
    llenarFin(modulos, inicio, fin);

    $('#ep-bloque-profesor').text(estado.profesor.nombre);
    $('#ep-bloque-dia').text(EP.DIAS[numeroDia]);
    $('#ep-bloque-vigencia').text(registro
      ? EP.fecha(registro.fechaInicio) + ' – ' + EP.fecha(registro.fechaFin)
      : EP.fecha(v.inicio) + ' – ' + EP.fecha(v.fin));
    $('#ep-btn-eliminar-bloque').toggleClass('d-none', !registro);
    $('#ep-form-bloque .ep-texto-titulo').text(registro ? 'Editar bloque' : 'Nuevo bloque');
    EP.limpiarAlerta($('#ep-form-bloque .ep-error'));
    modalBloque.show();
  }

  $('#ep-bloque-inicio').on('change', function () {
    var turno = turnoActual();
    var b = estado.bloque;
    if (!turno || !b) return;
    var inicio = Number($(this).val());
    llenarFin(modulosDelDia(turno, b.dia), inicio, inicio + MODULO);
  });

  $('#ep-bloque-fin').on('change', pintarDuracion);

  $zona.on('click', 'button.hcell', function () {
    abrirBloque(Number($(this).data('dia')), null, Number($(this).data('fila')));
  });

  $zona.on('click', 'button.hblk', function () {
    var secuencia = Number($(this).data('secuencia'));
    var registro = estado.horario.find(function (h) { return Number(h.secuencia) === secuencia; });
    if (registro) abrirBloque(EP.diaNumero(registro.diaSemana), registro);
  });

  $('#ep-form-bloque').on('submit', function (e) {
    e.preventDefault();
    var $error = $(this).find('.ep-error');
    var b = estado.bloque;
    var turno = turnoActual();
    var rango = rangoDia(turno, b.dia);
    var inicio = EP.horaDesdeMinutos(Number($('#ep-bloque-inicio').val()));
    var fin = EP.horaDesdeMinutos(Number($('#ep-bloque-fin').val()));
    var v = vigencia();
    var datos = {
      fechaInicio: b.registro ? String(b.registro.fechaInicio).substr(0, 10) : v.inicio,
      fechaFin: b.registro ? String(b.registro.fechaFin).substr(0, 10) : v.fin,
      diaSemana: EP.DIAS[b.dia],
      horaInicio: inicio,
      horaFin: fin,
      idTipoBloque: Number($('#ep-bloque-tipo').val())
    };

    if (!datos.idTipoBloque) return EP.mostrarAlerta($error, 'Elige el tipo de bloque.');
    if (fin <= inicio) return EP.mostrarAlerta($error, 'La hora fin debe ser mayor que la hora inicio.');
    if (inicio < rango.inicio || fin > rango.fin) {
      return EP.mostrarAlerta($error, 'El bloque debe quedar dentro del turno ' + turno.descripcion + ' (' + rango.inicio + ' – ' + rango.fin + ').');
    }
    var receso = defaultsDe(turno.idTurno).find(function (d) { return EP.seEmpalman(inicio, fin, d.horaInicio, d.horaFin); });
    if (receso) {
      var tr = receso.tipo_bloque || tipoPorId(receso.idTipoBloque) || {};
      return EP.mostrarAlerta($error, 'Se traslapa con ' + $.trim(tr.descripcion) + ' del turno (' + EP.hora(receso.horaInicio) + ' – ' + EP.hora(receso.horaFin) + ').');
    }
    // No puede chocar con otra actividad del profesor, aunque sea de otro turno
    var choque = estado.horario.find(function (h) {
      return (!b.registro || Number(h.secuencia) !== Number(b.registro.secuencia))
        && EP.diaNumero(h.diaSemana) === b.dia
        && String(h.fechaInicio).substr(0, 10) <= datos.fechaFin && String(h.fechaFin).substr(0, 10) >= datos.fechaInicio
        && EP.seEmpalman(inicio, fin, h.horaInicio, h.horaFin);
    });
    if (choque) {
      var tc = choque.tipo_bloque || tipoPorId(choque.idTipoBloque) || {};
      var turnoChoque = turnoDeBloque(choque);
      return EP.mostrarAlerta($error, 'Se traslapa con ' + $.trim(tc.descripcion) + ' del ' + EP.DIAS[b.dia] + ' (' + EP.hora(choque.horaInicio) + ' – '
        + EP.hora(choque.horaFin) + ')' + (turnoChoque ? ' en el turno ' + turnoChoque.descripcion : '') + '.');
    }

    var peticion = b.registro
      ? EP.api('PUT', '/horarioprofesor/' + estado.profesor.uid + '/' + b.registro.secuencia, datos)
      : EP.api('POST', '/horarioprofesor/create', $.extend({ uid: estado.profesor.uid }, datos));

    var $boton = $(this).find('[type=submit]');
    EP.bloquearBoton($boton, true);
    peticion.done(function () {
      modalBloque.hide();
      cargarHorario().always(function () {
        pintarTodo();
        EP.marcarGuardado($('#ep-estado-guardado'));
      });
    }).fail(function (mensaje) {
      EP.mostrarAlerta($error, mensaje);
    }).always(function () {
      EP.bloquearBoton($boton, false);
    });
  });

  $('#ep-btn-eliminar-bloque').on('click', function () {
    var r = estado.bloque && estado.bloque.registro;
    if (!r) return;
    var t = r.tipo_bloque || tipoPorId(r.idTipoBloque) || {};
    modalBloque.hide();
    EP.confirmar({
      titulo: 'Eliminar bloque',
      texto: 'Se eliminará ' + $.trim(t.descripcion) + ' del ' + EP.diaNombre(r.diaSemana) + ' (' + EP.hora(r.horaInicio) + ' – ' + EP.hora(r.horaFin) + ') del horario de ' + estado.profesor.nombre + '.',
      resumen: ['Estas horas dejan de contar para el cálculo del pago del periodo.'],
      alConfirmar: function () {
        return EP.api('DELETE', '/horarioprofesor/' + estado.profesor.uid + '/' + r.secuencia).done(function () {
          cargarHorario().always(pintarTodo);
        });
      }
    });
  });

  // ---------- Impresión del horario global ----------

  $('#ep-btn-imprimir').on('click', function () {
    if (!estado.profesor) return;
    var v = vigencia();
    var res = construirCuadricula(vistaGlobal(), { editable: false });
    var total = 0;
    var horas = Object.keys(res.minutosPorTipo).map(function (id) {
      var t = tipoPorId(id) || { descripcion: 'TIPO ' + id };
      if (Number(t.pagable) !== 0) total += res.minutosPorTipo[id];
      return EP.escapar($.trim(t.descripcion)) + ': ' + textoHoras(res.minutosPorTipo[id]);
    });
    $('#ep-impresion').html(
      '<h1 class="ep-titulo" style="font-size:20px">Horario del profesor</h1>'
      + '<div class="d-flex flex-wrap gap-4 my-2" style="font-size:14px">'
      + '<div><span class="rlabel">Profesor</span><br><strong>' + EP.escapar(estado.profesor.nombre) + '</strong></div>'
      + '<div><span class="rlabel">UID</span><br><strong>' + EP.escapar(estado.profesor.uid) + '</strong></div>'
      + '<div><span class="rlabel">Vigencia</span><br><strong>' + EP.fecha(v.inicio) + ' – ' + EP.fecha(v.fin) + '</strong></div>'
      + '<div><span class="rlabel">Horas pagables por semana</span><br><strong>' + textoHoras(total) + '</strong>'
      + (horas.length ? ' (' + horas.join(', ') + ')' : '') + '</div>'
      + '</div>'
      + res.html
      + '<p style="font-size:11px;margin-top:8px">Todos los turnos. Bloques de 50 minutos (1 bloque = 1 hora). Impreso el ' + EP.fecha(EP.hoyIso()) + '.</p>'
    );
    window.print();
  });

  // ---------- Inicio ----------
  $('#ep-vigencia-inicio').val(EP.hoyIso());
  $('#ep-vigencia-fin').val(EP.hoyIso(180));
  cargarCatalogos().always(pintarTodo);
});

  </script>
</body>
