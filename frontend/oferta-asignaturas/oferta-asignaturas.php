<?php
// Llamar al CRUD/UTILS
include 'crud/utils.php';
// Llamar al CRUD/GET
include 'crud/get.php';

// Dirección de apirest; en otro servidor se cambia con la variable de entorno SIAWEB_API_URL
$apiUrl = rtrim(getenv('SIAWEB_API_URL') ?: 'https://api.pruebas.siaweb.com.mx/api', '/');

// Catálogos que no cambian mientras se usa la pantalla; si la API falla regresa un arreglo vacío
function ofertaCatalogo($endpoint, $clave)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => array('Accept: application/json')
    ));

    $data = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($data === false || $http_code != 200) {
        return [];
    }

    $json = json_decode($data, true);

    return $json[$clave] ?? [];
}

$niveles  = ofertaCatalogo($apiUrl . '/niveles', 'niveles');
$carreras = ofertaCatalogo($apiUrl . '/carreras', 'carreras');
$formatos = ofertaCatalogo($apiUrl . '/formatos', 'formatos');

function reporte2($endpoint)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        )
    ));

    $data = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if (curl_errno($curl)) {
        return 'ERROR_CURL: ' . curl_error($curl);
    }

    curl_close($curl);

    if ($http_code != 200) {
        return "ERROR_HTTP_" . $http_code;
    }

    $json = json_decode($data);

    if (!$json) {
        return "ERROR_JSON: " . $data;
    }

    return $json->message ?? "ERROR_SIN_MESSAGE";
}

$jsonOpciones = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
<body>
  <style>
    /* Sistema de diseño SIAWEB; todo vive bajo .ofa para no tocar otras secciones */
    .ofa, .ofa-modal {
      --ofa-surface: #ffffff; --ofa-muted: #edf1ed; --ofa-border: #dfe5df;
      --ofa-text: #1b221c; --ofa-text-muted: #5c665d;
      --ofa-accent: #1c7a3d; --ofa-accent-strong: #146130; --ofa-accent-soft: #e2f4e9;
      --ofa-bad-bg: #fbe7e4; --ofa-bad-text: #a23b2e; --ofa-info-bg: #e2eef8; --ofa-info-text: #2f6a95;
      --ofa-danger: #c0392b; --ofa-on-accent: #ffffff; --ofa-shadow: rgba(20, 30, 20, .10);
      color: var(--ofa-text);
    }
    .dark .ofa, .dark .ofa-modal {
      --ofa-surface: #1c211e; --ofa-muted: #252b27; --ofa-border: #343c36;
      --ofa-text: #e8ece8; --ofa-text-muted: #9aa79c;
      --ofa-accent: #3fbf70; --ofa-accent-strong: #57d184; --ofa-accent-soft: #1c3226;
      --ofa-bad-bg: #3a221d; --ofa-bad-text: #f0a08f; --ofa-info-bg: #1c2b36; --ofa-info-text: #8fc3eb;
      --ofa-on-accent: #0f1a12; --ofa-shadow: rgba(0, 0, 0, .45);
    }
    .ofa .ofa-label, .ofa-modal .ofa-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
    .ofa .form-select, .ofa .form-control, .ofa-modal .form-control { border: 1.5px solid var(--ofa-border); border-radius: 10px; background-color: var(--ofa-surface); color: var(--ofa-text); }
    .ofa .form-select:focus, .ofa .form-control:focus, .ofa-modal .form-control:focus { border-color: var(--ofa-accent); box-shadow: 0 0 0 3px var(--ofa-accent-soft); }
    .ofa .ofa-card { background: var(--ofa-surface); border: 1px solid var(--ofa-border); border-radius: 14px; box-shadow: 0 10px 30px var(--ofa-shadow); padding: 18px; }
    .ofa .ofa-buscar { position: relative; width: 300px; max-width: 100%; }
    .ofa .ofa-buscar input { padding-right: 36px; }
    .ofa .ofa-buscar > i { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--ofa-text-muted); pointer-events: none; }
    .ofa .ofa-tabla { width: 100%; font-size: 14px; color: var(--ofa-text); margin: 0; }
    .ofa .ofa-tabla thead th { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--ofa-text-muted); background: var(--ofa-muted); padding: 10px 12px; white-space: nowrap; border: 0; }
    .ofa .ofa-tabla td { padding: 10px 12px; border-top: 1px solid var(--ofa-muted); vertical-align: middle; background: transparent; color: var(--ofa-text); }
    .ofa .ofa-tabla tbody tr[data-id] { cursor: pointer; }
    .ofa .ofa-tabla tbody tr[data-id]:hover td { background: var(--ofa-muted); }
    .ofa .ofa-tabla tbody tr.activa td { background: var(--ofa-accent-soft); }
    .ofa .ofa-radio { width: 16px; height: 16px; accent-color: var(--ofa-accent); cursor: pointer; }
    .ofa .tag { display: inline-block; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 999px; text-transform: uppercase; letter-spacing: .03em; white-space: nowrap; }
    .ofa .tag.bien { background: var(--ofa-accent-soft); color: var(--ofa-accent-strong); }
    .ofa .tag.mal { background: var(--ofa-bad-bg); color: var(--ofa-bad-text); }
    .ofa .tag.info { background: var(--ofa-info-bg); color: var(--ofa-info-text); margin-left: 6px; }
    .ofa .tabla-activa .tag.bien { background: var(--ofa-surface); }
    .ofa .ofa-btn-menu { width: 30px; height: 30px; border-radius: 999px; border: 1px solid var(--ofa-border); background: transparent; color: var(--ofa-text-muted); }
    .ofa .ofa-btn-menu:hover { border-color: var(--ofa-accent); color: var(--ofa-accent-strong); }
    .ofa .ofa-vacio { text-align: center; padding: 28px 16px; color: var(--ofa-text-muted); font-size: 14px; }
    .ofa .ofa-vacio strong { display: block; color: var(--ofa-text); font-size: 15px; margin-bottom: 4px; }
    .ofa .ofa-tabs { display: flex; gap: 4px; border-bottom: 1px solid var(--ofa-border); margin: 24px 0 14px; overflow-x: auto; }
    .ofa .ofa-tab { padding: 9px 14px; font-size: 14px; font-weight: 600; color: var(--ofa-text-muted); background: none; border: 0; border-bottom: 3px solid transparent; white-space: nowrap; }
    .ofa .ofa-tab:hover { color: var(--ofa-text); }
    .ofa .ofa-tab.activa { color: var(--ofa-accent-strong); border-bottom-color: var(--ofa-accent); }
    .ofa .ofa-cabecera { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
    .ofa .ofa-icono, .ofa-modal .ofa-icono { width: 34px; height: 34px; border-radius: 999px; background: var(--ofa-accent-soft); color: var(--ofa-accent-strong); display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .ofa-modal .ofa-icono.peligro { background: var(--ofa-bad-bg); color: var(--ofa-danger); }
    .ofa .ofa-titulo { font-size: 16px; font-weight: 700; }
    .ofa .ofa-sub { font-size: 13px; color: var(--ofa-text-muted); }
    .ofa .ofa-fila-dato { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 10px 12px; background: var(--ofa-muted); border-radius: 10px; font-size: 14px; }
    .ofa .ofa-rlabel { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--ofa-text-muted); margin-right: 8px; }
    .ofa .ofa-link { background: none; border: 0; padding: 2px 0; color: var(--ofa-accent-strong); font-size: 13px; font-weight: 600; }
    .ofa .ofa-link:hover { text-decoration: underline; }
    .ofa .ofa-ayuda { font-size: 12.5px; color: var(--ofa-text-muted); }
    .ofa .ofa-acciones { display: flex; justify-content: flex-end; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--ofa-muted); }
    .ofa .ofa-guardado { margin-right: auto; color: var(--ofa-accent-strong); font-size: 14px; font-weight: 600; }
    .ofa .ofa-alerta, .ofa-modal .ofa-alerta { display: flex; gap: 8px; align-items: flex-start; background: var(--ofa-bad-bg); color: var(--ofa-bad-text); border-radius: 10px; padding: 10px 12px; font-size: 13px; font-weight: 500; }
    .ofa .ofa-btn, .ofa-modal .ofa-btn { padding: 9px 18px; border-radius: 10px; font-size: 14px; font-weight: 600; border: 1.5px solid transparent; }
    .ofa .ofa-btn:disabled, .ofa-modal .ofa-btn:disabled { opacity: .5; }
    .ofa .ofa-btn.primario, .ofa-modal .ofa-btn.primario { background: var(--ofa-accent); color: var(--ofa-on-accent); }
    .ofa .ofa-btn.secundario, .ofa-modal .ofa-btn.secundario { background: var(--ofa-surface); border-color: var(--ofa-border); color: var(--ofa-text); }
    .ofa .ofa-btn.peligro, .ofa-modal .ofa-btn.peligro { background: var(--ofa-danger); color: #ffffff; }
    .ofa .ofa-btn.texto-peligro { background: none; color: var(--ofa-danger); padding-left: 4px; padding-right: 4px; }
    .ofa .ofa-persona { position: relative; }
    .ofa .ofa-persona input { padding-right: 36px; }
    .ofa .ofa-persona > i { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--ofa-text-muted); pointer-events: none; }
    .ofa .ofa-resultados { position: absolute; z-index: 30; left: 0; right: 0; top: calc(100% + 4px); max-height: 260px; overflow: auto; background: var(--ofa-surface); border: 1px solid var(--ofa-border); border-radius: 10px; box-shadow: 0 10px 30px var(--ofa-shadow); }
    .ofa .ofa-resultados button, .ofa .ofa-resultados div { display: block; width: 100%; text-align: left; padding: 8px 12px; border: 0; background: none; color: var(--ofa-text); font-size: 14px; }
    .ofa .ofa-resultados button:hover { background: var(--ofa-muted); }
    .ofa .ofa-dias td { padding: 7px 12px; }
    .ofa .ofa-dias table { min-width: 620px; }
    .ofa .ofa-dias tbody tr:hover td { background: transparent; }
    .ofa .ofa-dias tr.inactivo .form-control, .ofa .ofa-dias tr.inactivo .form-select { opacity: .45; }
    .ofa .ofa-dias input[readonly] { background: transparent; color: var(--ofa-text-muted); }
    .ofa .ofa-dia { min-width: 44px; padding: 7px 6px; border: 1.5px solid var(--ofa-border); border-radius: 999px; background: var(--ofa-surface); color: var(--ofa-text-muted); font-size: 12px; font-weight: 600; }
    .ofa .ofa-dia.on { background: var(--ofa-accent); border-color: var(--ofa-accent); color: var(--ofa-on-accent); }
    .ofa .ofa-dia:hover:not(.on) { border-color: var(--ofa-accent); color: var(--ofa-accent-strong); }
    .ofa-menu { position: fixed; z-index: 1060; min-width: 180px; padding: 6px; background: var(--ofa-surface); border: 1px solid var(--ofa-border); border-radius: 10px; box-shadow: 0 10px 30px var(--ofa-shadow); }
    .ofa-menu button { display: flex; align-items: center; gap: 8px; width: 100%; padding: 8px 10px; border: 0; border-radius: 7px; background: none; color: var(--ofa-text); font-size: 14px; text-align: left; }
    .ofa-menu button:hover:not(:disabled) { background: var(--ofa-muted); }
    .ofa-menu button:disabled { opacity: .5; }
    .ofa-menu button.peligro { color: var(--ofa-danger); }
    .ofa-modal .modal-content { background: var(--ofa-surface); color: var(--ofa-text); border: 1px solid var(--ofa-border); border-radius: 14px; box-shadow: 0 10px 30px var(--ofa-shadow); }
    .ofa-modal .modal-header { border-bottom: 0; padding: 18px 22px; }
    .ofa-modal .modal-title { font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .ofa-modal .modal-body { padding: 4px 22px 22px; display: flex; flex-direction: column; gap: 14px; }
    .ofa-modal .modal-footer { border-top: 1px solid var(--ofa-muted); padding: 14px 22px; gap: 10px; }
    .ofa-modal .ofa-resumen { background: var(--ofa-accent-soft); color: var(--ofa-accent-strong); border-radius: 10px; padding: 12px 14px; font-size: 14px; font-weight: 600; display: flex; flex-direction: column; gap: 2px; }
    .ofa-modal .ofa-resumen.peligro { background: var(--ofa-bad-bg); color: var(--ofa-bad-text); }
    .ofa-modal .ofa-logo { padding: 12px 14px; border: 1.5px solid var(--ofa-border); border-radius: 10px; background: var(--ofa-surface); color: var(--ofa-text); font-size: 13px; font-weight: 600; }
    .ofa-modal .ofa-logo.on { border-color: var(--ofa-accent); background: var(--ofa-accent-soft); }
  </style>

  <div class="list_btn_header" style="margin-bottom: 20px;">
      <a class="btn btn-primario button_reporte" data-titulo="Grupos" data-tipo="excel"><img src="/images/csv.png">Excel</a>
  </div>

  <div class="--container ofa" id="ofa">
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <label class="ofa-label" for="ofa-nivel">Nivel</label>
        <select class="form-select" id="ofa-nivel">
          <option value="">Selecciona un nivel</option>
          <?php foreach ($niveles as $nivel): ?>
          <option value="<?= htmlspecialchars($nivel['idNivel']) ?>"><?= htmlspecialchars(trim($nivel['descripcion'])) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="ofa-label" for="ofa-periodo">Periodo</label>
        <select class="form-select" id="ofa-periodo" disabled><option value="">Selecciona un periodo</option></select>
      </div>
      <div class="col-md-4">
        <label class="ofa-label" for="ofa-carrera">Carrera</label>
        <select class="form-select" id="ofa-carrera" disabled><option value="">Selecciona una carrera</option></select>
      </div>
      <div class="col-md-4">
        <label class="ofa-label" for="ofa-turno">Turno</label>
        <select class="form-select" id="ofa-turno" disabled><option value="">Todos</option></select>
      </div>
      <div class="col-md-4">
        <label class="ofa-label" for="ofa-semestre">Semestre</label>
        <select class="form-select" id="ofa-semestre" disabled><option value="">Todos</option></select>
      </div>
      <div class="col-md-4">
        <label class="ofa-label" for="ofa-grupo">Grupo</label>
        <select class="form-select" id="ofa-grupo" disabled><option value="">Todos</option></select>
      </div>
    </div>

    <div id="ofa-alerta-general" class="mb-3"></div>

    <div class="ofa-card">
      <div class="d-flex justify-content-end mb-3">
        <div class="ofa-buscar">
          <input type="text" id="ofa-buscar" class="form-control" autocomplete="off" placeholder="Buscar por clave o nombre..." aria-label="Buscar por clave o nombre">
          <i class="bi bi-search" aria-hidden="true"></i>
        </div>
      </div>
      <div class="table-responsive">
        <table class="ofa-tabla" id="ofaTable">
          <thead>
            <tr>
              <th><span class="visually-hidden">Selección</span></th>
              <th>Clave</th>
              <th>Nombre</th>
              <th>Grupo</th>
              <th>Turno</th>
              <th>Inscritos</th>
              <th>Acta</th>
              <th>Horario</th>
              <th><span class="visually-hidden">Acciones</span></th>
            </tr>
          </thead>
          <tbody id="ofa-cuerpo"></tbody>
        </table>
      </div>
      <div class="ofa-ayuda mt-2" id="ofa-total"></div>
    </div>

    <div class="ofa-tabs" role="tablist" aria-label="Captura del grupo seleccionado">
      <button type="button" class="ofa-tab activa" role="tab" id="ofa-tab-acta" data-tab="acta" aria-selected="true">Acta</button>
      <button type="button" class="ofa-tab" role="tab" id="ofa-tab-horario" data-tab="horario" aria-selected="false">Horario</button>
    </div>
    <div class="ofa-card" id="ofa-detalle" aria-live="polite"></div>
  </div>

  <div class="ofa-menu ofa d-none" id="ofa-menu" role="menu">
    <button type="button" role="menuitem" data-accion="editar"><i class="bi bi-pencil"></i>Editar</button>
    <button type="button" role="menuitem" data-accion="eliminar" class="peligro"><i class="bi bi-trash"></i>Eliminar</button>
  </div>

  <div class="modal fade ofa-modal" id="ofa-modal-capacidad" tabindex="-1" aria-labelledby="ofa-titulo-capacidad" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" id="ofa-form-capacidad" novalidate>
        <div class="modal-header">
          <h2 class="modal-title" id="ofa-titulo-capacidad"><span class="ofa-icono"><i class="bi bi-pencil"></i></span>Editar grupo</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="ofa-resumen"><span id="ofa-capacidad-grupo"></span><span id="ofa-capacidad-nombre" style="font-weight:500"></span></div>
          <div>
            <label class="ofa-label" for="ofa-capacidad">Capacidad</label>
            <input class="form-control" id="ofa-capacidad" type="number" min="1">
            <div class="ofa-ayuda mt-1" id="ofa-capacidad-ayuda"></div>
          </div>
          <div class="ofa-error"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="ofa-btn secundario" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="ofa-btn primario">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal fade ofa-modal" id="ofa-modal-logotipo" tabindex="-1" aria-labelledby="ofa-titulo-logotipo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" id="ofa-form-logotipo" novalidate>
        <div class="modal-header">
          <h2 class="modal-title" id="ofa-titulo-logotipo"><span class="ofa-icono"><i class="bi bi-image"></i></span>Cambiar logotipo del acta</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="d-flex flex-wrap gap-2" id="ofa-logos"></div>
          <div class="ofa-error"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="ofa-btn secundario" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="ofa-btn primario">Usar este logotipo</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal fade ofa-modal" id="ofa-modal-confirmar" tabindex="-1" aria-labelledby="ofa-titulo-confirmar" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title" id="ofa-titulo-confirmar"><span class="ofa-icono peligro"><i class="bi bi-trash"></i></span><span id="ofa-confirmar-titulo"></span></h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <p class="mb-0" id="ofa-confirmar-texto" style="font-size:14px"></p>
          <div class="ofa-resumen peligro" id="ofa-confirmar-resumen"></div>
          <div class="ofa-error"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="ofa-btn secundario" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="ofa-btn peligro" id="ofa-confirmar-ok">Eliminar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- EXCEL -->
  <template id="excel">
      <iframe src="https://view.officeapps.live.com/op/view.aspx?src=<?= reporte2($apiUrl . '/grupos/reporte') ?>" style="width:100%; height: 800px;"></iframe>
  </template>

  <script src="js/crud/utils.js?v=<?= time(); ?>"></script>

  <script>
  (function () {
    'use strict';

    var API = <?= json_encode($apiUrl, $jsonOpciones) ?>;
    var CARRERAS = <?= json_encode($carreras, $jsonOpciones) ?>;
    var FORMATOS = <?= json_encode($formatos, $jsonOpciones) ?>;

    var ORDEN_DIAS = [2, 3, 4, 5, 6, 7, 1]; // Lunes a domingo; 1 es domingo igual que en apirest
    var DIAS = { 1: 'domingo', 2: 'lunes', 3: 'martes', 4: 'miércoles', 5: 'jueves', 6: 'viernes', 7: 'sábado' };
    var DIAS_CORTOS = { 1: 'Dom', 2: 'Lun', 3: 'Mar', 4: 'Mié', 5: 'Jue', 6: 'Vie', 7: 'Sáb' };
    var FIRMANTES = ['secretario', 'presidente', 'supervisor'];
    var MAX_SESIONES = 4;
    var LOGOS_SEP = ['logoSEP05.png', 'logoSEP1617.png'];

    var estado = { filas: [], seleccion: null, tab: 'acta', acta: null, horario: null, borrador: {}, previa: null, menuFila: null, logo: null, pedido: 0, confirmar: null };
    var $detalle = $('#ofa-detalle');
    var temporizador = null;

    // Los modales van al body para que el layout no los recorte
    $('#ofa-modal-capacidad, #ofa-modal-logotipo, #ofa-modal-confirmar, #ofa-menu').appendTo('body');
    var modalCapacidad = new bootstrap.Modal('#ofa-modal-capacidad');
    var modalLogotipo = new bootstrap.Modal('#ofa-modal-logotipo');
    var modalConfirmar = new bootstrap.Modal('#ofa-modal-confirmar');

    // ---------- Utilidades ----------

    function escapar(texto) {
      return String(texto === null || texto === undefined ? '' : texto)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function limpio(texto) {
      return $.trim(String(texto === null || texto === undefined ? '' : texto));
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

    function fecha(iso) {
      var p = String(iso || '').substr(0, 10).split('-');
      return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : '—';
    }

    function alerta(mensaje) {
      return '<div class="ofa-alerta" role="alert"><i class="bi bi-exclamation-circle"></i><span>' + escapar(mensaje) + '</span></div>';
    }

    function vacio(titulo, texto) {
      return '<div class="ofa-vacio"><strong>' + escapar(titulo) + '</strong>' + escapar(texto || '') + '</div>';
    }

    // Llamada a apirest con jQuery; rechaza con un mensaje legible
    function api(metodo, ruta, datos) {
      var diferido = $.Deferred();
      $.ajax({
        url: API + ruta,
        type: metodo,
        dataType: 'json',
        contentType: datos ? 'application/json' : undefined,
        data: datos ? JSON.stringify(datos) : undefined
      }).done(function (respuesta) {
        diferido.resolve(respuesta);
      }).fail(function (xhr) {
        var r = xhr.responseJSON, mensaje = '';
        if (r && r.error && typeof r.error === 'object') {
          $.each(r.error, function (_, lista) { mensaje += [].concat(lista).join(' ') + ' '; });
        }
        mensaje = limpio(mensaje) || (r && r.message)
          || (xhr.status === 0 ? 'No hay conexión con la API.' : 'Ocurrió un error inesperado (' + xhr.status + ').');
        diferido.reject(mensaje, xhr);
      });
      return diferido.promise();
    }

    function bloquear($boton, si) {
      $boton.prop('disabled', si);
    }

    function guardado() {
      var $lugar = $detalle.find('.ofa-guardado');
      $lugar.html('<i class="bi bi-check2"></i> Guardado');
      setTimeout(function () { $lugar.empty(); }, 4000);
    }

    function errorDetalle(mensaje) {
      $detalle.find('.ofa-error').html(mensaje ? alerta(mensaje) : '');
    }

    function llenarSelect($select, opciones, textoInicial, valor) {
      var html = '<option value="">' + escapar(textoInicial) + '</option>';
      $.each(opciones, function (_, o) {
        html += '<option value="' + escapar(o.valor) + '">' + escapar(o.texto) + '</option>';
      });
      $select.html(html).val(valor === undefined || valor === null ? '' : String(valor));
      if ($select.val() === null) $select.val('');
    }

    function filaSeleccionada() {
      return estado.filas.find(function (f) { return f.gruposec === estado.seleccion; }) || null;
    }

    // ---------- Filtros ----------

    function alCambiarNivel() {
      var idNivel = $('#ofa-nivel').val();
      estado.filas = [];
      seleccionar(null);
      llenarSelect($('#ofa-periodo'), [], 'Selecciona un periodo');
      llenarSelect($('#ofa-carrera'), [], 'Selecciona una carrera');
      reiniciarLocales();
      $('#ofa-periodo, #ofa-carrera').prop('disabled', true);
      pintarTabla();
      if (!idNivel) return;

      llenarSelect($('#ofa-carrera'), $.map(CARRERAS.filter(function (c) {
        return String(c.idNivel) === idNivel && Number(c.activo) === 1;
      }), function (c) {
        return { valor: c.idCarrera, texto: limpio(c.descripcion) };
      }), 'Selecciona una carrera');
      $('#ofa-carrera').prop('disabled', false);

      api('GET', '/periodos/' + encodeURIComponent(idNivel)).done(function (r) {
        var periodos = ((r && (r.periodos || r.$periodos)) || []).slice().sort(function (a, b) { return b.idPeriodo - a.idPeriodo; });
        var activo = periodos.find(function (p) { return Number(p.activo) === 1; });
        llenarSelect($('#ofa-periodo'), $.map(periodos, function (p) {
          return { valor: p.idPeriodo, texto: p.idPeriodo + ' - ' + limpio(p.descripcion) };
        }), 'Selecciona un periodo', activo ? activo.idPeriodo : '');
        $('#ofa-periodo').prop('disabled', false);
        cargarOferta();
      }).fail(function (mensaje) {
        $('#ofa-alerta-general').html(alerta(mensaje));
      });
    }

    function reiniciarLocales() {
      llenarSelect($('#ofa-turno'), [], 'Todos');
      llenarSelect($('#ofa-semestre'), [], 'Todos');
      llenarSelect($('#ofa-grupo'), [], 'Todos');
      $('#ofa-turno, #ofa-semestre, #ofa-grupo').prop('disabled', true);
    }

    function cargarOferta(silencioso) {
      var idNivel = $('#ofa-nivel').val(), idPeriodo = $('#ofa-periodo').val(), idCarrera = $('#ofa-carrera').val();
      if (!idNivel || !idPeriodo || !idCarrera) {
        estado.filas = [];
        seleccionar(null);
        reiniciarLocales();
        pintarTabla();
        return $.Deferred().resolve().promise();
      }

      if (!silencioso) $('#ofa-cuerpo').html('<tr><td colspan="9">' + vacio('Cargando…') + '</td></tr>');

      return api('GET', '/grupos/oferta/' + [idNivel, idPeriodo, idCarrera].map(encodeURIComponent).join('/')).done(function (r) {
        $('#ofa-alerta-general').empty();
        estado.filas = (r && r.grupos) || [];
        actualizarLocales();
        pintarTabla();
      }).fail(function (mensaje) {
        estado.filas = [];
        $('#ofa-alerta-general').html(alerta(mensaje));
        pintarTabla();
      });
    }

    function distintos(filas, clave, texto) {
      var vistos = {}, salida = [];
      $.each(filas, function (_, f) {
        var v = f[clave];
        if (v === null || v === undefined || vistos[v]) return;
        vistos[v] = true;
        salida.push({ valor: v, texto: texto ? texto(f) : v });
      });
      return salida.sort(function (a, b) { return String(a.texto).localeCompare(String(b.texto), 'es', { numeric: true }); });
    }

    // Turno, semestre y grupo salen de los renglones de la oferta, en cascada
    function actualizarLocales() {
      var turno = $('#ofa-turno').val(), semestre = $('#ofa-semestre').val(), grupo = $('#ofa-grupo').val();

      llenarSelect($('#ofa-turno'), distintos(estado.filas, 'idTurno', function (f) { return f.turno || f.idTurno; }), 'Todos', turno);
      turno = $('#ofa-turno').val();
      var porTurno = estado.filas.filter(function (f) { return !turno || String(f.idTurno) === turno; });

      llenarSelect($('#ofa-semestre'), distintos(porTurno, 'semestre'), 'Todos', semestre);
      semestre = $('#ofa-semestre').val();
      var porSemestre = porTurno.filter(function (f) { return !semestre || String(f.semestre) === semestre; });

      llenarSelect($('#ofa-grupo'), distintos(porSemestre, 'grupo'), 'Todos', grupo);
      $('#ofa-turno, #ofa-semestre, #ofa-grupo').prop('disabled', !estado.filas.length);
    }

    function filasVisibles() {
      var turno = $('#ofa-turno').val(), semestre = $('#ofa-semestre').val(), grupo = $('#ofa-grupo').val();
      var texto = limpio($('#ofa-buscar').val()).toUpperCase();
      return estado.filas.filter(function (f) {
        return (!turno || String(f.idTurno) === turno)
          && (!semestre || String(f.semestre) === semestre)
          && (!grupo || f.grupo === grupo)
          && (!texto || String(f.idAsignatura).toUpperCase().indexOf(texto) !== -1 || String(f.asignatura || '').toUpperCase().indexOf(texto) !== -1);
      });
    }

    // ---------- Tabla ----------

    function pintarTabla() {
      var filas = filasVisibles();

      // Si el grupo activo deja de estar a la vista se limpia, para no capturar sobre algo oculto
      if (estado.seleccion && !filas.some(function (f) { return f.gruposec === estado.seleccion; })) {
        seleccionar(null);
      }

      if (!filas.length) {
        var listo = $('#ofa-nivel').val() && $('#ofa-periodo').val() && $('#ofa-carrera').val();
        $('#ofa-cuerpo').html('<tr><td colspan="9">' + (listo
          ? vacio('No hay asignaturas para estos filtros', 'Cambia los filtros. Si el periodo es nuevo, revisa primero la oferta de grupos.')
          : vacio('Elige nivel, periodo y carrera', 'Con esos tres filtros aparece la oferta de asignaturas.')) + '</td></tr>');
        $('#ofa-total').text('');
        return;
      }

      $('#ofa-cuerpo').html($.map(filas, function (f) {
        var activa = f.gruposec === estado.seleccion;
        return '<tr data-id="' + f.gruposec + '"' + (activa ? ' class="activa tabla-activa"' : '') + '>'
          + '<td><input class="ofa-radio" type="radio" name="ofa-seleccion" aria-label="Seleccionar ' + escapar(f.asignatura) + ', grupo ' + escapar(f.grupo) + '"' + (activa ? ' checked' : '') + '></td>'
          + '<td class="text-nowrap">' + escapar(f.idAsignatura) + '</td>'
          + '<td>' + escapar(f.asignatura) + '</td>'
          + '<td class="text-nowrap">' + escapar(f.grupo) + '</td>'
          + '<td class="text-nowrap">' + escapar(f.turno) + '</td>'
          + '<td class="text-nowrap">' + escapar(f.inscritos) + ' / ' + escapar(f.capacidad === null ? '—' : f.capacidad) + '</td>'
          + '<td>' + (f.actaCompleta ? '<span class="tag bien">Completa</span>' : '<span class="tag mal">Pendiente</span>') + '</td>'
          + '<td>' + (f.horarioCapturado ? '<span class="tag bien">Capturado</span>' : '<span class="tag mal">Pendiente</span>') + '</td>'
          + '<td><button type="button" class="ofa-btn-menu" aria-label="Acciones de ' + escapar(f.idAsignatura) + '" aria-haspopup="menu"><i class="bi bi-three-dots-vertical"></i></button></td>'
          + '</tr>';
      }).join(''));
      $('#ofa-total').text(filas.length + (filas.length === 1 ? ' asignatura' : ' asignaturas'));
    }

    function seleccionar(id) {
      if (estado.seleccion === id) return;
      estado.seleccion = id;
      estado.acta = null;
      estado.horario = null;
      estado.borrador = {};
      estado.previa = null;
      cargarDetalle();
    }

    // ---------- Detalle ----------

    function cargarDetalle() {
      var id = estado.seleccion, pedido = ++estado.pedido;

      if (!id) {
        pintarDetalle();
        return;
      }

      $detalle.html(vacio('Cargando…'));
      api('GET', estado.tab === 'acta' ? '/grupos/actas/' + id : '/horarios/' + id).done(function (r) {
        if (pedido !== estado.pedido) return;
        if (estado.tab === 'acta') {
          estado.acta = r.acta;
          resolverPredeterminados();
        } else {
          recibirHorario(r.horario);
        }
        pintarDetalle();
      }).fail(function (mensaje) {
        if (pedido === estado.pedido) $detalle.html(alerta(mensaje));
      });
    }

    function pintarDetalle() {
      var fila = filaSeleccionada();
      if (!fila) {
        $detalle.html(vacio('Selecciona un grupo', estado.tab === 'acta'
          ? 'Aquí aparecerá la captura del acta del grupo elegido.'
          : 'Aquí aparecerá la captura del horario del grupo elegido.'));
        return;
      }
      if (estado.tab === 'acta' && estado.acta) pintarActa(fila);
      if (estado.tab === 'horario' && estado.horario) pintarHorario(fila);
    }

    function cabecera(fila, icono, titulo) {
      return '<div class="ofa-cabecera"><span class="ofa-icono"><i class="bi ' + icono + '"></i></span><div><div class="ofa-titulo">' + titulo + '</div>'
        + '<div class="ofa-sub">' + escapar(fila.grupo + ' · ' + fila.idAsignatura + ' · ' + fila.asignatura) + '</div></div></div>';
    }

    // ---------- Acta ----------

    // El nombre de configuracion se convierte en uid solo si coincide con un único empleado
    // Solo depende de que siga cargada la misma acta, no de la pestaña visible
    function resolverPredeterminados() {
      $.each(FIRMANTES, function (_, rol) {
        var firmante = estado.acta[rol];
        if (!firmante.predeterminado || firmante.uid || !firmante.nombre) return;
        api('GET', '/profesores/nuevo/' + encodeURIComponent(firmante.nombre)).done(function (r) {
          // Si el usuario ya cambió este firmante, el resultado se ignora
          if (!estado.acta || estado.acta[rol] !== firmante || !firmante.predeterminado) return;
          var lista = (r && r.profesores) || [];
          if (lista.length === 1) firmante.uid = lista[0].uid;
        });
      });
    }

    function campoPersona(rol, etiqueta) {
      var p = estado.acta[rol] || {};
      return '<div class="col-12 col-md-6"><label class="ofa-label" for="ofa-' + rol + '">' + etiqueta
        + (p.predeterminado ? '<span class="tag info" id="ofa-marca-' + rol + '">Predeterminado</span>' : '') + '</label>'
        + '<div class="ofa-persona"><input class="form-control ofa-persona-input" id="ofa-' + rol + '" data-rol="' + rol + '" type="text" autocomplete="off" placeholder="Buscar por nombre o UID..." value="' + escapar(p.nombre || '') + '">'
        + '<i class="bi bi-search" aria-hidden="true"></i><div class="ofa-resultados d-none" role="listbox"></div></div></div>';
    }

    function pintarActa(fila) {
      var a = estado.acta;
      var hayMarcas = FIRMANTES.some(function (r) { return a[r].predeterminado; });
      var opcionesFormato = '<option value="">Selecciona...</option>' + $.map(FORMATOS, function (f) {
        return '<option value="' + escapar(f.idFormato) + '"' + (String(f.idFormato) === String(a.idFormato) ? ' selected' : '') + '>' + escapar(limpio(f.nombre)) + '</option>';
      }).join('');

      $detalle.html(cabecera(fila, 'bi-file-earmark-text', 'Acta del grupo')
        + '<div class="row g-3">' + campoPersona('profesor', 'Profesor') + campoPersona('secretario', 'Secretario')
        + campoPersona('presidente', 'Presidente') + campoPersona('supervisor', 'Supervisor escolar') + '</div>'
        + (hayMarcas ? '<div class="ofa-ayuda mt-2" id="ofa-ayuda-predeterminado">Los campos marcados vienen de la configuración. Se guardan en el acta al pulsar Guardar acta.</div>' : '')
        + '<div class="row g-3 mt-1">'
        + '<div class="col-12 col-md"><label class="ofa-label" for="ofa-fecha">Fecha</label><input class="form-control" id="ofa-fecha" data-a="fechaIni" type="date" value="' + escapar(String(a.fechaIni || '').substr(0, 10)) + '"></div>'
        + '<div class="col-6 col-md"><label class="ofa-label" for="ofa-ini">Hora de inicio</label><input class="form-control" id="ofa-ini" data-a="horaIni" type="time" value="' + escapar(hora(a.horaIni)) + '"></div>'
        + '<div class="col-6 col-md"><label class="ofa-label" for="ofa-fin">Hora de fin</label><input class="form-control" id="ofa-fin" data-a="horaFin" type="time" value="' + escapar(hora(a.horaFin)) + '"></div>'
        + (FORMATOS.length ? '<div class="col-12 col-md"><label class="ofa-label" for="ofa-formato">Formato</label><select class="form-select" id="ofa-formato" data-a="idFormato">' + opcionesFormato + '</select></div>' : '')
        + '</div>'
        + '<div class="ofa-fila-dato mt-3"><span><span class="ofa-rlabel">Logotipo</span><span id="ofa-logo-actual">' + escapar(a.logoSep || 'Sin logotipo') + '</span></span><button type="button" class="ofa-link" id="ofa-btn-logo">Cambiar logotipo</button></div>'
        + '<div class="ofa-error mt-3"></div>'
        + '<div class="ofa-acciones"><span class="ofa-guardado" aria-live="polite"></span><button type="button" class="ofa-btn secundario" id="ofa-descartar">Descartar cambios</button>'
        + '<button type="button" class="ofa-btn primario" id="ofa-guardar-acta">Guardar acta</button></div>');
    }

    function buscarPersona($input) {
      var texto = limpio($input.val());
      var $resultados = $input.siblings('.ofa-resultados');
      clearTimeout(temporizador);

      if (texto.length < 3) {
        $resultados.addClass('d-none').empty();
        return;
      }

      temporizador = setTimeout(function () {
        api('GET', '/profesores/nuevo/' + encodeURIComponent(texto)).done(function (r) {
          if (limpio($input.val()) !== texto) return;
          var lista = (r && r.profesores) || [];
          $resultados.html(lista.length ? $.map(lista, function (p) {
            var nombre = limpio([p.primerApellido, p.segundoApellido, p.nombre].filter(Boolean).join(' '));
            return '<button type="button" class="ofa-opcion" data-uid="' + escapar(p.uid) + '" data-secuencia="' + escapar(p.secuencia) + '" data-nombre="' + escapar(nombre) + '">'
              + escapar(p.uid + ' — ' + nombre) + '</button>';
          }).join('') : '<div>Sin coincidencias</div>').removeClass('d-none');
        }).fail(errorDetalle);
      }, 300);
    }

    function quitarMarca(rol) {
      if (!estado.acta[rol].predeterminado) return;
      estado.acta[rol].predeterminado = false;
      $('#ofa-marca-' + rol).remove();
      if (!FIRMANTES.some(function (r) { return estado.acta[r].predeterminado; })) $('#ofa-ayuda-predeterminado').remove();
    }

    function guardarActa() {
      var a = estado.acta, id = estado.seleccion;
      var faltan = $.map(['profesor'].concat(FIRMANTES), function (rol) { return a[rol].uid ? null : rol; });

      errorDetalle('');
      if (faltan.length) return errorDetalle('Elige del catálogo de empleados a: ' + faltan.join(', ') + '.');
      if (!a.fechaIni || !a.horaIni || !a.horaFin) return errorDetalle('Completa la fecha y las horas del acta.');
      if (hora(a.horaFin) <= hora(a.horaIni)) return errorDetalle('La hora de fin debe ser posterior a la de inicio.');

      var datos = { uidSecretario: a.secretario.uid, uidPresidente: a.presidente.uid, uidSupervisor: a.supervisor.uid, fechaIni: String(a.fechaIni).substr(0, 10), horaIni: hora(a.horaIni), horaFin: hora(a.horaFin) };
      if (FORMATOS.length) datos.idFormato = a.idFormato || null;

      var $boton = $('#ofa-guardar-acta');
      bloquear($boton, true);

      // El profesor se guarda en su propia ruta y solo si cambió
      var profesor = a.profesor.cambio
        ? api('POST', '/grupos/grupo/' + id, { uidProfesor: a.profesor.uid, secuencia: a.profesor.secuencia })
        : $.Deferred().resolve().promise();

      profesor.then(function () {
        return api('POST', '/grupos/actas/' + id, datos);
      }).done(function () {
        a.profesor.cambio = false;
        FIRMANTES.forEach(quitarMarca);
        bloquear($boton, false);
        guardado();
        cargarOferta(true);
      }).fail(function (mensaje) {
        bloquear($boton, false);
        errorDetalle(mensaje);
      });
    }

    function pintarLogos() {
      $('#ofa-logos').html($.map(LOGOS_SEP, function (logo) {
        var on = logo === estado.logo;
        return '<button type="button" class="ofa-logo' + (on ? ' on' : '') + '" data-logo="' + escapar(logo) + '" aria-pressed="' + on + '">' + escapar(logo) + '</button>';
      }).join(''));
    }

    // ---------- Horario ----------

    function recibirHorario(horario) {
      estado.horario = horario;
      estado.previa = null;
      estado.borrador = {};
      $.each(horario.dias || [], function (_, d) {
        estado.borrador[d.day] = { horaIni: d.horaIni, sesiones: d.sesiones && d.sesiones <= MAX_SESIONES ? d.sesiones : 1 };
      });
    }

    function horaFin(dia, duracion) {
      if (!dia.horaIni || !duracion) return '';
      var total = minutos(dia.horaIni) + dia.sesiones * duracion;
      return total < 1440 ? horaDesdeMinutos(total) : '';
    }

    function pintarHorario(fila) {
      var duracion = fila.duracionSesion, inicio = cabecera(fila, 'bi-calendar-week', 'Horario del grupo');

      if (!duracion) {
        $detalle.html(inicio + alerta('No hay duración de sesión configurada para el nivel y el turno ' + (fila.turno || '') + '. Configúrala para poder capturar el horario.'));
        return;
      }

      var filas = $.map(ORDEN_DIAS, function (n) {
        var d = estado.borrador[n], on = !!d, off = on ? '' : ' disabled', opciones = '';
        for (var s = 1; s <= MAX_SESIONES; s++) {
          opciones += '<option value="' + s + '"' + (on && d.sesiones === s ? ' selected' : '') + '>' + (s === 1 ? '1 sesión' : s + ' sesiones seguidas') + ' (' + (s * duracion) + ' min)</option>';
        }
        return '<tr' + (on ? '' : ' class="inactivo"') + '>'
          + '<td><button type="button" class="ofa-dia' + (on ? ' on' : '') + '" data-dia="' + n + '" aria-pressed="' + on + '" aria-label="Clase el ' + DIAS[n] + '">' + DIAS_CORTOS[n] + '</button></td>'
          + '<td><input class="form-control" type="time" id="ofa-h-ini-' + n + '" data-h="horaIni" data-d="' + n + '" aria-label="Hora de inicio del ' + DIAS[n] + '" value="' + (on ? escapar(d.horaIni) : '') + '"' + off + '></td>'
          + '<td><select class="form-select" id="ofa-h-ses-' + n + '" data-h="sesiones" data-d="' + n + '" aria-label="Duración del ' + DIAS[n] + '"' + off + '>' + opciones + '</select></td>'
          + '<td><input class="form-control" type="text" id="ofa-h-fin-' + n + '" readonly tabindex="-1" aria-label="Hora de fin del ' + DIAS[n] + '" value="' + escapar((on && horaFin(d, duracion)) || '—') + '"></td>'
          + '</tr>';
      }).join('');

      var h = estado.horario;
      $detalle.html(inicio
        + '<div class="ofa-fila-dato mb-3"><span><span class="ofa-rlabel">Duración por sesión</span>' + duracion + ' minutos · ' + escapar(fila.turno || '') + '</span>'
        + (h.total ? '<span>' + h.total + ' sesiones guardadas' + (h.impartidas ? ', ' + h.impartidas + ' impartidas (no se modifican)' : '') + '</span>' : '') + '</div>'
        + '<div class="table-responsive ofa-dias"><table class="ofa-tabla"><thead><tr><th>Día</th><th>Hora de inicio</th><th>Duración</th><th>Hora de fin</th></tr></thead><tbody>' + filas + '</tbody></table></div>'
        + '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3"><span class="ofa-ayuda">Activa cada día con clase. Las sesiones cubren el periodo y omiten los días festivos.</span>'
        + '<button type="button" class="ofa-btn secundario" id="ofa-generar">Generar sesiones</button></div>'
        + '<div class="mt-3" id="ofa-previa">' + htmlPrevia() + '</div>'
        + '<div class="ofa-error mt-3"></div>'
        + '<div class="ofa-acciones"><span class="ofa-guardado" aria-live="polite"></span>'
        + (h.total ? '<button type="button" class="ofa-btn texto-peligro" id="ofa-eliminar-horario">Eliminar horario</button>' : '')
        + '<button type="button" class="ofa-btn secundario" id="ofa-descartar">Descartar cambios</button>'
        + '<button type="button" class="ofa-btn primario" id="ofa-guardar-horario">Guardar horario</button></div>');
    }

    function htmlPrevia() {
      if (!estado.previa) return '';
      return '<div class="ofa-ayuda mb-2">' + estado.previa.length + ' clases en el periodo. Se guardan al pulsar Guardar horario; aquí se muestran las primeras 6.</div>'
        + '<div class="table-responsive"><table class="ofa-tabla"><thead><tr><th>Fecha</th><th>Día</th><th>Horario</th></tr></thead><tbody>'
        + $.map(estado.previa.slice(0, 6), function (s) {
          return '<tr><td>' + fecha(s.fechaIni) + '</td><td>' + DIAS_CORTOS[s.day] + '</td><td>' + hora(String(s.fechaIni).substr(11)) + '–' + hora(String(s.fechaFin).substr(11)) + '</td></tr>';
        }).join('') + '</tbody></table></div>';
    }

    function validarHorario() {
      var duracion = filaSeleccionada().duracionSesion;
      var dias = ORDEN_DIAS.filter(function (n) { return estado.borrador[n]; });
      if (!dias.length) return 'Activa al menos un día.';
      for (var i = 0; i < dias.length; i++) {
        var d = estado.borrador[dias[i]];
        if (!d.horaIni) return 'Captura la hora de inicio del ' + DIAS[dias[i]] + '.';
        if (!horaFin(d, duracion)) return 'La clase del ' + DIAS[dias[i]] + ' debe terminar antes de medianoche.';
      }
      return '';
    }

    function diasParaApi() {
      return ORDEN_DIAS.filter(function (n) { return estado.borrador[n]; }).map(function (n) {
        return { day: n, horaIni: estado.borrador[n].horaIni, sesiones: estado.borrador[n].sesiones };
      });
    }

    function confirmar(titulo, texto, resumen, alConfirmar) {
      estado.confirmar = alConfirmar;
      $('#ofa-confirmar-titulo').text(titulo);
      $('#ofa-confirmar-texto').text(texto);
      $('#ofa-confirmar-resumen').html(resumen);
      $('#ofa-modal-confirmar .ofa-error').empty();
      modalConfirmar.show();
    }

    // ---------- Eventos: filtros y tabla ----------

    $('#ofa-nivel').on('change', alCambiarNivel);
    $('#ofa-periodo, #ofa-carrera').on('change', function () {
      estado.filas = [];
      seleccionar(null);
      reiniciarLocales();
      cargarOferta();
    });
    $('#ofa-turno, #ofa-semestre, #ofa-grupo').on('change', function () {
      actualizarLocales();
      pintarTabla();
    });
    $('#ofa-buscar').on('input', pintarTabla);

    $('#ofa-cuerpo').on('click', 'tr[data-id]', function (e) {
      if ($(e.target).closest('.ofa-btn-menu').length) return;
      seleccionar(Number($(this).data('id')));
      pintarTabla();
    });

    $('.ofa-tabs').on('click', '.ofa-tab', function () {
      estado.tab = $(this).data('tab');
      $('.ofa-tab').each(function () {
        var on = $(this).data('tab') === estado.tab;
        $(this).toggleClass('activa', on).attr('aria-selected', on);
      });
      if (estado.seleccion && !(estado.tab === 'acta' ? estado.acta : estado.horario)) {
        cargarDetalle();
      } else {
        estado.pedido++;
        pintarDetalle();
      }
    });

    // ---------- Eventos: menú por fila y modales ----------

    function cerrarMenu() {
      $('#ofa-menu').addClass('d-none');
    }

    $('#ofa-cuerpo').on('click', '.ofa-btn-menu', function (e) {
      e.stopPropagation();
      var id = Number($(this).closest('tr').data('id'));
      var fila = estado.filas.find(function (f) { return f.gruposec === id; });
      var $menu = $('#ofa-menu');
      var conAlumnos = Number(fila.inscritos) > 0;
      estado.menuFila = fila;
      $menu.find('[data-accion="eliminar"]').prop('disabled', conAlumnos).attr('title', conAlumnos ? 'Tiene alumnos inscritos' : '');
      $menu.removeClass('d-none');
      var caja = this.getBoundingClientRect();
      $menu.css({
        top: Math.max(8, Math.min(caja.bottom + 4, window.innerHeight - $menu.outerHeight() - 8)),
        left: Math.max(8, Math.min(caja.right - $menu.outerWidth(), window.innerWidth - $menu.outerWidth() - 8))
      });
      $menu.find('button:not(:disabled)').first().trigger('focus');
    });

    $(document).on('click', function (e) {
      if (!$(e.target).closest('#ofa-menu').length) cerrarMenu();
      if (!$(e.target).closest('.ofa-persona').length) $('.ofa-resultados').addClass('d-none');
    });
    $(document).on('keydown', function (e) {
      if (e.key === 'Escape') {
        cerrarMenu();
        $('.ofa-resultados').addClass('d-none');
      }
    });
    $(window).on('resize', cerrarMenu);
    window.addEventListener('scroll', cerrarMenu, true);

    $('#ofa-menu').on('click', 'button', function () {
      var fila = estado.menuFila;
      cerrarMenu();

      if ($(this).data('accion') === 'editar') {
        $('#ofa-capacidad-grupo').text(fila.grupo + ' · ' + fila.idAsignatura);
        $('#ofa-capacidad-nombre').text(fila.asignatura || '');
        $('#ofa-capacidad').val(fila.capacidad).attr('min', Math.max(1, Number(fila.inscritos)));
        $('#ofa-capacidad-ayuda').text('Tiene ' + fila.inscritos + ' inscritos. La capacidad no puede ser menor.');
        $('#ofa-modal-capacidad .ofa-error').empty();
        modalCapacidad.show();
        return;
      }

      confirmar('Eliminar asignatura de la oferta',
        'Vas a eliminar ' + fila.asignatura + ' (' + fila.grupo + ') de la oferta de este periodo. Esta acción no se puede deshacer.',
        '<span>Se eliminará</span><span style="font-weight:500">El grupo, su acta capturada y su horario, si ya existen</span>',
        function () {
          return api('DELETE', '/grupos/grupo/' + fila.gruposec).then(function () {
            if (estado.seleccion === fila.gruposec) seleccionar(null);
            return cargarOferta(true);
          });
        });
    });

    $('#ofa-confirmar-ok').on('click', function () {
      var $boton = $(this);
      bloquear($boton, true);
      $.when(estado.confirmar()).done(function () {
        modalConfirmar.hide();
      }).fail(function (mensaje) {
        $('#ofa-modal-confirmar .ofa-error').html(alerta(mensaje));
      }).always(function () {
        bloquear($boton, false);
      });
    });

    $('#ofa-form-capacidad').on('submit', function (e) {
      e.preventDefault();
      var fila = estado.menuFila, valor = parseInt($('#ofa-capacidad').val(), 10);
      var $boton = $(this).find('[type="submit"]'), $error = $(this).find('.ofa-error');

      if (isNaN(valor) || valor < 1 || valor < Number(fila.inscritos)) {
        $error.html(alerta('La capacidad debe ser un número igual o mayor a ' + Math.max(1, Number(fila.inscritos)) + '.'));
        return;
      }

      bloquear($boton, true);
      api('PUT', '/grupos/capacidad/' + fila.gruposec, { capacidad: valor }).done(function () {
        modalCapacidad.hide();
        cargarOferta(true);
      }).fail(function (mensaje) {
        $error.html(alerta(mensaje));
      }).always(function () {
        bloquear($boton, false);
      });
    });

    $('#ofa-logos').on('click', '[data-logo]', function () {
      estado.logo = $(this).data('logo');
      pintarLogos();
    });

    $('#ofa-form-logotipo').on('submit', function (e) {
      e.preventDefault();
      var $boton = $(this).find('[type="submit"]'), $error = $(this).find('.ofa-error');

      if (!estado.logo) {
        $error.html(alerta('Elige un logotipo.'));
        return;
      }

      bloquear($boton, true);
      api('PUT', '/grupos/logotipo/' + estado.seleccion, { logoSep: estado.logo }).done(function () {
        estado.acta.logoSep = estado.logo;
        $('#ofa-logo-actual').text(estado.logo);
        modalLogotipo.hide();
      }).fail(function (mensaje) {
        $error.html(alerta(mensaje));
      }).always(function () {
        bloquear($boton, false);
      });
    });

    // ---------- Eventos: captura ----------

    $detalle.on('input', '.ofa-persona-input', function () {
      var rol = $(this).data('rol');
      estado.acta[rol].uid = null;
      estado.acta[rol].nombre = $(this).val();
      if (rol === 'profesor') estado.acta.profesor.cambio = true;
      else quitarMarca(rol);
      buscarPersona($(this));
    });

    $detalle.on('click', '.ofa-opcion', function () {
      var $input = $(this).closest('.ofa-persona').find('.ofa-persona-input');
      var persona = estado.acta[$input.data('rol')];
      persona.uid = $(this).data('uid');
      persona.nombre = String($(this).data('nombre'));
      if ($input.data('rol') === 'profesor') {
        persona.secuencia = $(this).data('secuencia');
        persona.cambio = true;
      }
      $input.val(persona.nombre);
      $(this).parent().addClass('d-none').empty();
      errorDetalle('');
    });

    // Lo capturado vive en el estado para no perderse si el panel se vuelve a pintar
    $detalle.on('input change', '[data-a]', function () {
      estado.acta[$(this).data('a')] = $(this).val();
    });

    $detalle.on('click', '#ofa-descartar', cargarDetalle);
    $detalle.on('click', '#ofa-guardar-acta', guardarActa);
    $detalle.on('click', '#ofa-btn-logo', function () {
      estado.logo = estado.acta.logoSep || null;
      pintarLogos();
      $('#ofa-modal-logotipo .ofa-error').empty();
      modalLogotipo.show();
    });

    $detalle.on('click', '.ofa-dia', function () {
      var n = Number($(this).data('dia'));
      if (estado.borrador[n]) {
        delete estado.borrador[n];
      } else {
        // Un día nuevo parte del horario del primer día activo para no recapturarlo
        var previo = ORDEN_DIAS.filter(function (x) { return estado.borrador[x]; })[0];
        estado.borrador[n] = previo ? { horaIni: estado.borrador[previo].horaIni, sesiones: estado.borrador[previo].sesiones } : { horaIni: '', sesiones: 1 };
      }
      estado.previa = null;
      pintarDetalle();
      $detalle.find('.ofa-dia[data-dia="' + n + '"]').trigger('focus');
    });

    $detalle.on('input change', '[data-h]', function () {
      var n = Number($(this).data('d')), campo = $(this).data('h');
      estado.borrador[n][campo] = campo === 'sesiones' ? Number($(this).val()) : $(this).val();
      $('#ofa-h-fin-' + n).val(horaFin(estado.borrador[n], filaSeleccionada().duracionSesion) || '—');
      estado.previa = null;
      $('#ofa-previa').empty();
    });

    $detalle.on('click', '#ofa-generar', function () {
      var error = validarHorario(), $boton = $(this);
      errorDetalle('');
      if (error) return errorDetalle(error);

      bloquear($boton, true);
      api('POST', '/horarios/vista-previa', { grupoSec: estado.seleccion, dias: diasParaApi() }).done(function (r) {
        estado.previa = r.sesiones || [];
        $('#ofa-previa').html(htmlPrevia());
      }).fail(errorDetalle).always(function () {
        bloquear($boton, false);
      });
    });

    $detalle.on('click', '#ofa-guardar-horario', function () {
      var error = validarHorario(), $boton = $(this);
      errorDetalle('');
      if (error) return errorDetalle(error);

      bloquear($boton, true);
      api('PUT', '/horarios/' + estado.seleccion, { dias: diasParaApi() }).done(function (r) {
        recibirHorario(r.horario);
        pintarDetalle();
        guardado();
        cargarOferta(true);
      }).fail(function (mensaje) {
        bloquear($boton, false);
        errorDetalle(mensaje);
      });
    });

    $detalle.on('click', '#ofa-eliminar-horario', function () {
      var id = estado.seleccion;
      confirmar('Eliminar horario', 'Se borrarán las sesiones pendientes de este grupo. Esta acción no se puede deshacer.',
        '<span>Se conserva</span><span style="font-weight:500">Las sesiones ya impartidas no se borran</span>',
        function () {
          return api('DELETE', '/horarios/' + id).then(function () {
            return api('GET', '/horarios/' + id);
          }).then(function (r) {
            if (estado.seleccion === id) {
              recibirHorario(r.horario);
              pintarDetalle();
            }
            return cargarOferta(true);
          });
        });
    });

    pintarTabla();
    pintarDetalle();
  })();
  </script>
