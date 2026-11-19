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

// Solo niveles activos: la oferta no se captura en niveles dados de baja
$niveles  = array_values(array_filter(ofertaCatalogo($apiUrl . '/niveles', 'niveles'), function ($nivel) {
    return (int) ($nivel['activo'] ?? 0) === 1;
}));
$carreras = ofertaCatalogo($apiUrl . '/carreras', 'carreras');
$formatos = ofertaCatalogo($apiUrl . '/formatos', 'formatos');
// Horario de cada turno por día y por nivel (dtlTurno.idNivel), para dibujar la cuadrícula del horario
$turnosDetalle = [];
foreach ($niveles as $nivel) {
    $turnosDetalle[$nivel['idNivel']] = ofertaCatalogo($apiUrl . '/turnos/detalles?idNivel=' . (int) $nivel['idNivel'], 'turnos');
}

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
    .ofa .ofa-ayuda, .ofa-modal .ofa-ayuda { font-size: 12.5px; color: var(--ofa-text-muted); }
    .ofa .ofa-acciones { display: flex; justify-content: flex-end; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--ofa-muted); }
    .ofa .ofa-guardado { margin-right: auto; color: var(--ofa-accent-strong); font-size: 14px; font-weight: 600; }
    .ofa .ofa-alerta, .ofa-modal .ofa-alerta { display: flex; gap: 8px; align-items: flex-start; background: var(--ofa-bad-bg); color: var(--ofa-bad-text); border-radius: 10px; padding: 10px 12px; font-size: 13px; font-weight: 500; }
    .ofa .ofa-btn, .ofa-modal .ofa-btn { padding: 9px 18px; border-radius: 10px; font-size: 14px; font-weight: 600; border: 1.5px solid transparent; }
    .ofa .ofa-btn:disabled, .ofa-modal .ofa-btn:disabled { opacity: .5; }
    .ofa .ofa-btn.primario, .ofa-modal .ofa-btn.primario { background: var(--ofa-accent); color: var(--ofa-on-accent); }
    .ofa .ofa-btn.secundario, .ofa-modal .ofa-btn.secundario { background: var(--ofa-surface); border-color: var(--ofa-border); color: var(--ofa-text); }
    .ofa .ofa-btn.peligro, .ofa-modal .ofa-btn.peligro { background: var(--ofa-danger); color: #ffffff; }
    .ofa .ofa-btn.texto-peligro, .ofa-modal .ofa-btn.texto-peligro { background: none; color: var(--ofa-danger); padding-left: 4px; padding-right: 4px; }
    .ofa .ofa-persona { position: relative; }
    .ofa .ofa-persona input { padding-right: 36px; }
    .ofa .ofa-persona > i { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--ofa-text-muted); pointer-events: none; }
    .ofa .ofa-resultados { position: absolute; z-index: 30; left: 0; right: 0; top: calc(100% + 4px); max-height: 260px; overflow: auto; background: var(--ofa-surface); border: 1px solid var(--ofa-border); border-radius: 10px; box-shadow: 0 10px 30px var(--ofa-shadow); }
    .ofa .ofa-resultados button, .ofa .ofa-resultados div { display: block; width: 100%; text-align: left; padding: 8px 12px; border: 0; background: none; color: var(--ofa-text); font-size: 14px; }
    .ofa .ofa-resultados button:hover { background: var(--ofa-muted); }
    /* Cuadrícula semanal, mismo diseño que Horario del profesor */
    .ofa .ofa-grid-wrap { overflow-x: auto; border: 1px solid var(--ofa-border); border-radius: 10px; }
    .ofa .ofa-hgrid { display: grid; min-width: 640px; background: var(--ofa-surface); position: relative; }
    .ofa .ofa-hhead { display: flex; align-items: center; justify-content: center; background: var(--ofa-muted); border-bottom: 1px solid var(--ofa-border); font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--ofa-text-muted); }
    .ofa .ofa-hhour { display: flex; align-items: flex-start; justify-content: center; padding-top: 6px; border-bottom: 1px solid var(--ofa-muted); border-right: 1px solid var(--ofa-border); font-size: 11px; color: var(--ofa-text-muted); white-space: nowrap; }
    .ofa .ofa-hcell { border: 0; border-bottom: 1px solid var(--ofa-muted); border-right: 1px solid var(--ofa-muted); background: transparent; padding: 0; }
    .ofa .ofa-hcell.fuera { background: var(--ofa-muted); cursor: not-allowed; }
    .ofa-modal .ofa-calculado { background: var(--ofa-muted); color: var(--ofa-text-muted); cursor: default; }
    .ofa-modal .ofa-calculado:focus { border-color: var(--ofa-border); box-shadow: none; }
    .ofa button.ofa-hcell:hover, .ofa button.ofa-hcell:focus-visible { background: var(--ofa-accent-soft); outline: none; }
    .ofa .ofa-hblk { z-index: 1; margin: 2px 4px; display: flex; flex-direction: column; justify-content: flex-start; gap: 2px; padding: 6px 8px; border: 1.5px solid var(--ofa-accent); border-radius: 8px; background: var(--ofa-accent-soft); color: var(--ofa-text); font-size: 11px; text-align: left; overflow: hidden; cursor: pointer; }
    .ofa .ofa-hblk strong { font-size: 12px; letter-spacing: .02em; }
    .ofa .ofa-hblk > * { max-width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex-shrink: 0; }
    /* El nombre de la materia usa hasta dos renglones; si no cabe, el completo está en el title del bloque */
    .ofa .ofa-hblk > .ofa-hblk-nombre { white-space: normal; line-height: 1.25; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    .ofa .ofa-hblk.otro { border-style: dashed; border-color: var(--ofa-text-muted); background: var(--ofa-muted); color: var(--ofa-text-muted); cursor: default; }
    .ofa .ofa-hblk.otro:hover { box-shadow: none; border-color: var(--ofa-text-muted); }
    .ofa .ofa-hbanda { z-index: 1; display: flex; align-items: center; justify-content: center; gap: 6px; border-top: 1.5px solid; border-bottom: 1.5px solid; font-size: 11px; font-weight: 600; letter-spacing: .04em; color: var(--ofa-text); overflow: hidden; }
    .ofa .ofa-hblk:hover, .ofa .ofa-hblk:focus-visible { box-shadow: 0 0 0 3px var(--ofa-accent-soft); outline: none; border-color: var(--ofa-accent-strong); }
    .ofa .ofa-leyenda { display: flex; flex-wrap: wrap; gap: 14px; font-size: 12px; color: var(--ofa-text-muted); }
    .ofa .ofa-leyenda i { display: inline-block; width: 14px; height: 14px; border-radius: 4px; border: 1.5px solid; vertical-align: -2px; margin-right: 6px; }
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
    .ofa-modal .ofa-logos { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 12px; }
    .ofa-modal .ofa-logo { position: relative; display: flex; flex-direction: column; gap: 8px; padding: 10px; border: 1.5px solid var(--ofa-border); border-radius: 10px; background: var(--ofa-surface); color: var(--ofa-text); font-size: 13px; font-weight: 600; text-align: center; }
    .ofa-modal .ofa-logo:hover { border-color: var(--ofa-accent); }
    .ofa-modal .ofa-logo.on { border-color: var(--ofa-accent); background: var(--ofa-accent-soft); box-shadow: 0 0 0 3px var(--ofa-accent-soft); }
    .ofa-modal .ofa-logo.on::after { content: "\2713"; position: absolute; top: 6px; right: 8px; width: 22px; height: 22px; border-radius: 999px; background: var(--ofa-accent); color: var(--ofa-on-accent); font-size: 13px; line-height: 22px; }
    /* Fondo blanco fijo: los logotipos son para impresión aunque la pantalla esté en modo oscuro */
    .ofa-logo-img { display: flex; align-items: center; justify-content: center; height: 90px; padding: 6px; border-radius: 6px; background: #ffffff; overflow: hidden; }
    .ofa-logo-img img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .ofa-logo-img.sin-imagen { background: var(--ofa-muted); color: var(--ofa-text-muted); font-size: 12px; font-weight: 500; }
    .ofa .ofa-logo-actual { display: inline-flex; align-items: center; gap: 10px; vertical-align: middle; }
    .ofa .ofa-logo-actual .ofa-logo-img { width: 120px; height: 44px; padding: 4px; border: 1px solid var(--ofa-border); }
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
        <select class="form-select" id="ofa-turno" required aria-required="true" disabled><option value="">Selecciona un turno</option></select>
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
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <form class="modal-content" id="ofa-form-logotipo" novalidate>
        <div class="modal-header">
          <h2 class="modal-title" id="ofa-titulo-logotipo"><span class="ofa-icono"><i class="bi bi-image"></i></span>Cambiar logotipo del acta</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="ofa-ayuda">Elige el logotipo de la SEP que se imprime en el acta.</div>
          <div class="ofa-logos" id="ofa-logos" role="radiogroup" aria-label="Logotipos"></div>
          <div class="ofa-error"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="ofa-btn secundario" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="ofa-btn primario">Usar este logotipo</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Clase de un día del horario (nuevo o editar, mismo modal) -->
  <div class="modal fade ofa-modal" id="ofa-modal-dia" tabindex="-1" aria-labelledby="ofa-titulo-dia" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form class="modal-content" id="ofa-form-dia" novalidate>
        <div class="modal-header">
          <h2 class="modal-title" id="ofa-titulo-dia"><span class="ofa-icono"><i class="bi bi-calendar-week"></i></span><span id="ofa-dia-titulo"></span></h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="ofa-resumen"><span>Grupo</span><span id="ofa-dia-grupo" style="font-weight:500"></span></div>
          <div class="row g-3">
            <div class="col-12 col-sm-4">
              <label class="ofa-label" for="ofa-dia-ini">Hora de inicio</label>
              <input class="form-control" id="ofa-dia-ini" type="time" step="300">
            </div>
            <div class="col-12 col-sm-5">
              <label class="ofa-label" for="ofa-dia-sesiones">Duración</label>
              <select class="form-control" id="ofa-dia-sesiones"></select>
            </div>
            <div class="col-12 col-sm-3">
              <label class="ofa-label" for="ofa-dia-fin">Hora de fin</label>
              <input class="form-control ofa-calculado" id="ofa-dia-fin" type="text" readonly tabindex="-1" aria-describedby="ofa-dia-turno">
            </div>
          </div>
          <div class="ofa-ayuda" id="ofa-dia-turno"></div>
          <div class="ofa-error"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="ofa-btn texto-peligro me-auto d-none" id="ofa-dia-quitar">Quitar día</button>
          <button type="button" class="ofa-btn secundario" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="ofa-btn primario">Aplicar</button>
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
    var TURNOS_DETALLE = <?= json_encode((object) $turnosDetalle, $jsonOpciones) ?>; // { idNivel: [turnos con sus días] }

    var ORDEN_DIAS = [2, 3, 4, 5, 6, 7, 1]; // Lunes a domingo; 1 es domingo igual que en apirest
    var DIAS = { 1: 'domingo', 2: 'lunes', 3: 'martes', 4: 'miércoles', 5: 'jueves', 6: 'viernes', 7: 'sábado' };
    var DIAS_CORTOS = { 1: 'Dom', 2: 'Lun', 3: 'Mar', 4: 'Mié', 5: 'Jue', 6: 'Vie', 7: 'Sáb' };
    var FIRMANTES = ['secretario', 'presidente', 'supervisor'];
    var MAX_SESIONES = 4;
    // Logotipos de la SEP en /secciones/img/logos; grupos.logoSep guarda solo el nombre del archivo
    var RUTA_LOGOS = 'img/logos/';
    var LOGOS_SEP = ['01', '02', '03', '04', '05', '06', '07'].map(function (n) { return 'logo-sep-ajustado-' + n + '.png'; });

    var estado = { filas: [], seleccion: null, tab: 'acta', acta: null, horario: null, borrador: {}, turnoPrevio: null, periodos: [], menuFila: null, logo: null, pedido: 0, confirmar: null };
    var $detalle = $('#ofa-detalle');
    var temporizador = null;

    // Los modales van al body para que el layout no los recorte
    $('#ofa-modal-capacidad, #ofa-modal-logotipo, #ofa-modal-dia, #ofa-modal-confirmar, #ofa-menu').appendTo('body');
    var modalDia = new bootstrap.Modal('#ofa-modal-dia');
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
        // apirest manda los errores en "error"; la validación de Laravel (422), en "errors"
        var errores = r && (r.error || r.errors);
        if (errores && typeof errores === 'object') {
          $.each(errores, function (_, lista) { mensaje += [].concat(lista).join(' ') + ' '; });
        }
        mensaje = limpio(mensaje) || mensajeServidor(xhr.status, r && r.message)
          || (xhr.status === 0 ? 'No hay conexión con la API.' : 'Ocurrió un error inesperado (' + xhr.status + ').');
        diferido.reject(mensaje, xhr);
      });
      return diferido.promise();
    }

    // Los errores que Laravel arma por su cuenta (ruta inexistente, método no permitido, error del servidor)
    // llegan en inglés; se cambian por un texto en español que diga qué hacer
    function mensajeServidor(status, mensaje) {
      mensaje = limpio(mensaje || '');
      if (status >= 500 || /^Server Error$/i.test(mensaje)) {
        return 'Ocurrió un error en el servidor (' + status + '). Intenta de nuevo; si continúa, avisa a sistemas.';
      }
      if (/^The route .* could not be found\.?$/i.test(mensaje) || /method is not supported/i.test(mensaje)) {
        return 'La API no tiene esta función en el servidor; puede faltar subir la versión más reciente (' + status + ').';
      }
      if (status === 429 || /^Too Many (Attempts|Requests)/i.test(mensaje)) {
        return 'Se hicieron demasiadas peticiones seguidas; espera un momento e intenta de nuevo.';
      }
      if (status === 401 || status === 403 || /^Unauthenticated|^This action is unauthorized/i.test(mensaje)) {
        return 'No tienes permiso para esta acción o tu sesión terminó.';
      }
      return mensaje;
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
      recordarTurno();
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
        // Solo periodos activos; si el nivel cambió mientras llegaba la respuesta, se descarta
        if ($('#ofa-nivel').val() !== idNivel) return;
        var periodos = ((r && (r.periodos || r.$periodos)) || []).filter(function (p) {
          return Number(p.activo) === 1;
        }).sort(function (a, b) { return b.idPeriodo - a.idPeriodo; });
        // Se guardan con sus fechas: el acta debe quedar dentro del periodo
        estado.periodos = periodos;
        llenarSelect($('#ofa-periodo'), $.map(periodos, function (p) {
          return { valor: p.idPeriodo, texto: p.idPeriodo + ' - ' + limpio(p.descripcion) };
        }), periodos.length ? 'Selecciona un periodo' : 'Sin periodo activo', periodos.length ? periodos[0].idPeriodo : '');
        // Con un solo periodo activo se muestra seleccionado pero no se puede cambiar; con varios se elige
        $('#ofa-periodo').prop('disabled', periodos.length <= 1);
        cargarOferta();
      }).fail(function (mensaje) {
        $('#ofa-alerta-general').html(alerta(mensaje));
      });
    }

    // Guarda el turno elegido antes de recargar la oferta, para no obligar a elegirlo otra vez
    function recordarTurno() {
      if ($('#ofa-turno').val()) estado.turnoPrevio = $('#ofa-turno').val();
    }

    function reiniciarLocales() {
      llenarSelect($('#ofa-turno'), [], 'Selecciona un turno');
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

    // porValor ordena por la clave numérica (idTurno) en lugar del texto
    function distintos(filas, clave, texto, porValor) {
      var vistos = {}, salida = [];
      $.each(filas, function (_, f) {
        var v = f[clave];
        if (v === null || v === undefined || vistos[v]) return;
        vistos[v] = true;
        salida.push({ valor: v, texto: texto ? texto(f) : v });
      });
      return salida.sort(function (a, b) {
        if (porValor) return Number(a.valor) - Number(b.valor);
        return String(a.texto).localeCompare(String(b.texto), 'es', { numeric: true });
      });
    }

    // Turno, semestre y grupo salen de los renglones de la oferta, en cascada
    function actualizarLocales() {
      var turno = $('#ofa-turno').val(), semestre = $('#ofa-semestre').val(), grupo = $('#ofa-grupo').val();

      // El turno es obligatorio (sin "Todos"). Al cambiar de nivel o carrera se conserva el que se tenía si
      // la nueva oferta lo trae; si la oferta solo tiene un turno, se elige solo
      var turnos = distintos(estado.filas, 'idTurno', function (f) { return f.turno || f.idTurno; }, true);
      if (!turno && estado.turnoPrevio && turnos.some(function (t) { return String(t.valor) === estado.turnoPrevio; })) turno = estado.turnoPrevio;
      if (estado.filas.length) estado.turnoPrevio = null;
      if (!turno && turnos.length === 1) turno = String(turnos[0].valor);
      llenarSelect($('#ofa-turno'), turnos, 'Selecciona un turno', turno);
      turno = $('#ofa-turno').val();
      var porTurno = turno ? estado.filas.filter(function (f) { return String(f.idTurno) === turno; }) : [];

      llenarSelect($('#ofa-semestre'), distintos(porTurno, 'semestre'), 'Todos', semestre);
      semestre = $('#ofa-semestre').val();
      var porSemestre = porTurno.filter(function (f) { return !semestre || String(f.semestre) === semestre; });

      llenarSelect($('#ofa-grupo'), distintos(porSemestre, 'grupo'), 'Todos', grupo);
      $('#ofa-turno').prop('disabled', !estado.filas.length);
      // Semestre y grupo dependen del turno
      $('#ofa-semestre, #ofa-grupo').prop('disabled', !turno);
    }

    function filasVisibles() {
      var turno = $('#ofa-turno').val(), semestre = $('#ofa-semestre').val(), grupo = $('#ofa-grupo').val();
      var texto = limpio($('#ofa-buscar').val()).toUpperCase();
      // Sin turno elegido no se muestra nada: el turno es obligatorio
      if (!turno) return [];
      return estado.filas.filter(function (f) {
        return String(f.idTurno) === turno
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
        var sinPeriodo = $('#ofa-nivel').val() && $('#ofa-periodo').prop('disabled') && $('#ofa-periodo option').length === 1 && $('#ofa-periodo option').text() === 'Sin periodo activo';
        var faltaTurno = listo && estado.filas.length && !$('#ofa-turno').val();
        $('#ofa-cuerpo').html('<tr><td colspan="9">' + (faltaTurno
          ? vacio('Elige el turno', 'El turno es obligatorio: al elegirlo aparecen sus asignaturas, semestres y grupos.')
          : listo
          ? vacio('No hay asignaturas para estos filtros', 'Cambia los filtros. Si el periodo es nuevo, revisa primero la oferta de grupos.')
          : sinPeriodo
            ? vacio('Este nivel no tiene periodo activo', 'Activa un periodo del nivel en el catálogo de periodos para capturar su oferta.')
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

    // El profesor sale de los empleados activos; secretario, presidente y supervisor, de todo el catálogo de personas
    function buscarCatalogo(rol, texto) {
      var esProfesor = rol === 'profesor';
      return api('GET', (esProfesor ? '/profesores/nuevo/' : '/personas/buscar/') + encodeURIComponent(texto)).then(function (r) {
        return (r && (esProfesor ? r.profesores : r.personas)) || [];
      });
    }

    // Los predeterminados llegan con uid (configuracionActas); si una API anterior manda solo el nombre,
    // se convierte en uid únicamente cuando coincide con una sola persona
    // Solo depende de que siga cargada la misma acta, no de la pestaña visible
    function resolverPredeterminados() {
      $.each(FIRMANTES, function (_, rol) {
        var firmante = estado.acta[rol];
        if (!firmante.predeterminado || firmante.uid || !firmante.nombre) return;
        buscarCatalogo(rol, firmante.nombre).done(function (lista) {
          // Si el usuario ya cambió este firmante, el resultado se ignora
          if (!estado.acta || estado.acta[rol] !== firmante || !firmante.predeterminado) return;
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
      var periodo = fechasPeriodo();
      var limitesFecha = periodo ? ' min="' + periodo.inicio + '" max="' + periodo.fin + '"' : '';
      var opcionesFormato = '<option value="">Selecciona...</option>' + $.map(FORMATOS, function (f) {
        return '<option value="' + escapar(f.idFormato) + '"' + (String(f.idFormato) === String(a.idFormato) ? ' selected' : '') + '>' + escapar(limpio(f.nombre)) + '</option>';
      }).join('');

      $detalle.html(cabecera(fila, 'bi-file-earmark-text', 'Acta del grupo')
        + '<div class="row g-3">' + campoPersona('profesor', 'Profesor') + campoPersona('secretario', 'Secretario')
        + campoPersona('presidente', 'Presidente') + campoPersona('supervisor', 'Supervisor escolar') + '</div>'
        + (hayMarcas ? '<div class="ofa-ayuda mt-2" id="ofa-ayuda-predeterminado">Los campos marcados son predeterminados: el presidente es el profesor de la asignatura; el secretario y el supervisor vienen de la configuración de actas del nivel. Se guardan en el acta al pulsar Guardar acta.</div>' : '')
        + '<div class="row g-3 mt-1">'
        + '<div class="col-12 col-md"><label class="ofa-label" for="ofa-fecha">Fecha</label><input class="form-control" id="ofa-fecha" data-a="fechaIni" type="date"' + limitesFecha + ' value="' + escapar(String(a.fechaIni || '').substr(0, 10)) + '"></div>'
        + '<div class="col-6 col-md"><label class="ofa-label" for="ofa-ini">Hora de inicio</label><input class="form-control" id="ofa-ini" data-a="horaIni" type="time" value="' + escapar(hora(a.horaIni)) + '"></div>'
        + '<div class="col-6 col-md"><label class="ofa-label" for="ofa-fin">Hora de fin</label><input class="form-control" id="ofa-fin" data-a="horaFin" type="time" value="' + escapar(hora(a.horaFin)) + '"></div>'
        + (FORMATOS.length ? '<div class="col-12 col-md"><label class="ofa-label" for="ofa-formato">Formato</label><select class="form-select" id="ofa-formato" data-a="idFormato">' + opcionesFormato + '</select></div>' : '')
        + '</div>'
        + '<div class="ofa-fila-dato mt-3"><span><span class="ofa-rlabel">Logotipo</span><span id="ofa-logo-actual">' + logoActual(a.logoSep) + '</span></span><button type="button" class="ofa-link" id="ofa-btn-logo">Cambiar logotipo</button></div>'
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
        buscarCatalogo($input.data('rol'), texto).done(function (lista) {
          if (limpio($input.val()) !== texto) return;
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

    // Fechas (AAAA-MM-DD) del periodo elegido en el filtro, o null si no se conocen
    function fechasPeriodo() {
      var p = (estado.periodos || []).find(function (x) { return String(x.idPeriodo) === $('#ofa-periodo').val(); });
      return p && p.fechaInicio && p.fechaTermino ? { inicio: String(p.fechaInicio).substr(0, 10), fin: String(p.fechaTermino).substr(0, 10) } : null;
    }

    // Secretario, presidente y supervisor: personas distintas entre sí y distintas del profesor (igual que la API)
    // El presidente sí puede ser el profesor (es su predeterminado); secretario y supervisor no
    function firmantesRepetidos(a) {
      var vistos = {};
      for (var i = 0; i < FIRMANTES.length; i++) {
        var rol = FIRMANTES[i], uid = a[rol].uid;
        if (!uid) continue; // vacío: no se compara
        if (rol !== 'presidente' && a.profesor.uid && String(uid) === String(a.profesor.uid)) {
          return 'El ' + rol + ' no puede ser el profesor de la asignatura.';
        }
        if (vistos[uid]) {
          return 'El ' + rol + ' no puede ser la misma persona que el ' + vistos[uid] + '.';
        }
        vistos[uid] = rol;
      }
      return '';
    }

    function guardarActa() {
      var a = estado.acta, id = estado.seleccion;
      errorDetalle('');
      // Lo único obligatorio es el profesor; firmantes, fecha y horas pueden quedar vacíos
      if (!a.profesor.uid) return errorDetalle('Elige al profesor de la lista de empleados; es lo único obligatorio para guardar el acta.');
      // Un firmante escrito pero no elegido de la lista no tiene uid: se pide elegirlo o dejarlo vacío
      var sinElegir = FIRMANTES.filter(function (rol) { return !a[rol].uid && limpio(a[rol].nombre || ''); });
      if (sinElegir.length) return errorDetalle('Elige de la lista de personas (o deja vacío) a: ' + sinElegir.join(', ') + '.');
      var repetido = firmantesRepetidos(a);
      if (repetido) return errorDetalle(repetido);
      var periodo = fechasPeriodo(), fechaActa = a.fechaIni ? String(a.fechaIni).substr(0, 10) : '';
      if (fechaActa && periodo && (fechaActa < periodo.inicio || fechaActa > periodo.fin)) {
        return errorDetalle('La fecha del acta debe quedar dentro del periodo (' + fecha(periodo.inicio) + ' al ' + fecha(periodo.fin) + ').');
      }
      if (a.horaIni && a.horaFin && hora(a.horaFin) <= hora(a.horaIni)) return errorDetalle('La hora de fin debe ser posterior a la de inicio.');

      var datos = {
        uidSecretario: a.secretario.uid || null, uidPresidente: a.presidente.uid || null, uidSupervisor: a.supervisor.uid || null,
        fechaIni: fechaActa || null, horaIni: a.horaIni ? hora(a.horaIni) : null, horaFin: a.horaFin ? hora(a.horaFin) : null
      };
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

    // Nombre corto para mostrar: logo-sep-ajustado-03.png -> Logotipo 03
    function nombreLogo(logo) {
      var m = /logo-sep-ajustado-(\d+)\.png$/i.exec(logo || '');
      return m ? 'Logotipo ' + m[1] : (logo || 'Sin logotipo');
    }

    // Vista previa; si la imagen no carga (logotipos anteriores) queda el aviso y el nombre se ve a un lado
    function imagenLogo(logo) {
      if (!logo) return '<span class="ofa-logo-img sin-imagen">Sin logotipo</span>';
      return '<span class="ofa-logo-img"><img src="' + escapar(RUTA_LOGOS + logo) + '" alt="' + escapar(nombreLogo(logo)) + '" loading="lazy"'
        + ' onerror="this.parentNode.classList.add(\'sin-imagen\');this.parentNode.textContent=\'Sin vista previa\'"></span>';
    }

    function logoActual(logo) {
      return '<span class="ofa-logo-actual">' + imagenLogo(logo) + '<span>' + escapar(nombreLogo(logo)) + '</span></span>';
    }

    function pintarLogos() {
      $('#ofa-logos').html($.map(LOGOS_SEP, function (logo) {
        var on = logo === estado.logo;
        return '<button type="button" class="ofa-logo' + (on ? ' on' : '') + '" data-logo="' + escapar(logo) + '" role="radio" aria-checked="' + on + '">'
          + imagenLogo(logo) + '<span>' + escapar(nombreLogo(logo)) + '</span></button>';
      }).join(''));
    }

    // ---------- Horario ----------

    function recibirHorario(horario) {
      estado.horario = horario;
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

      var h = estado.horario;
      $detalle.html(inicio
        + '<div class="ofa-fila-dato mb-3"><span><span class="ofa-rlabel">Duración por sesión</span>' + duracion + ' minutos · ' + escapar(fila.turno || '') + '</span>'
        + '<span class="d-inline-flex align-items-center gap-3"><span class="ofa-guardado" aria-live="polite"></span>'
        + (h.total ? '<span>' + h.total + ' sesiones guardadas' + (h.impartidas ? ', ' + h.impartidas + ' impartidas (no se modifican)' : '') + '</span>' : '<span>Sin horario guardado</span>') + '</span></div>'
        + cuadricula(fila, duracion)
        + leyendaHorario()
        + '<div class="ofa-ayuda mt-3">Pulsa una celda libre para agregar la clase de ese día o la clase de esta materia para cambiarla o quitarla; solo hay un horario por día y cada cambio se guarda al momento. '
        + 'Las clases de otras materias del grupo se cambian seleccionando esa materia en la tabla. Las sesiones cubren el periodo y omiten los días festivos; las impartidas se conservan.</div>'
        + '<div class="ofa-error mt-3"></div>');
    }

    // Días y horas del turno del grupo (1 domingo ... 7 sábado), desde /turnos/detalles
    var NOMBRES_DIA = { DOMINGO: 1, LUNES: 2, MARTES: 3, MIERCOLES: 4, JUEVES: 5, VIERNES: 6, SABADO: 7 };

    function diaNumero(valor) {
      var texto = limpio(valor).toUpperCase().replace('É', 'E').replace('Á', 'A');
      if (/^\d+$/.test(texto)) return Number(texto) >= 1 && Number(texto) <= 7 ? Number(texto) : null;
      return NOMBRES_DIA[texto] || null;
    }

    // Días del turno para el nivel elegido en el filtro (la API ya aplica: los del nivel o, si no tiene, los generales)
    function rangosTurno(idTurno) {
      var delNivel = TURNOS_DETALLE[$('#ofa-nivel').val()] || [];
      var turno = delNivel.find(function (t) { return String(t.idTurno) === String(idTurno); });
      var rangos = {};
      $.each((turno && turno.diasTurno) || [], function (_, d) {
        var n = diaNumero(d.diaSemana);
        if (n) rangos[n] = { ini: minutos(d.horaInicio), fin: minutos(d.horaFin) };
      });
      return rangos;
    }

    // Color de tipoBloque si es un hexadecimal válido (en la base hay valores como "#FF0000;")
    function colorBloque(valor) {
      var m = /^#([0-9a-f]{6}|[0-9a-f]{3})\b/i.exec(limpio(valor));
      return m ? m[0] : '#9aa79c';
    }

    // Bloques del turno (defaultTurno) en minutos
    function bloquesTurno() {
      return $.map((estado.horario && estado.horario.bloquesTurno) || [], function (b) {
        return { ini: minutos(b.horaIni), fin: minutos(b.horaFin), descripcion: limpio(b.descripcion), color: colorBloque(b.color), texto: limpio(b.descripcion) + ' del turno' };
      }).sort(function (a, b) { return a.ini - b.ini; });
    }

    // Lo que una clase no puede ocupar el día n: bloques del turno y clases de otras materias del grupo o del profesor
    // La cuadrícula solo se relaciona con las otras asignaturas del mismo grupo; los traslapes del profesor
    // con otros grupos no se dibujan, los rechaza la API al guardar y su mensaje sale en el modal
    function ocupadosGrupo() {
      return ((estado.horario && estado.horario.ocupados) || []).filter(function (o) { return o.tipo === 'grupo'; });
    }

    function ocupadosDia(n) {
      var lista = bloquesTurno();
      $.each(ocupadosGrupo(), function (_, o) {
        if (Number(o.day) !== n) return;
        lista.push({ ini: minutos(o.horaIni), fin: minutos(o.horaFin), texto: o.idAsignatura + ' del mismo grupo' });
      });
      return lista;
    }

    function choque(n, ini, fin) {
      return ocupadosDia(n).find(function (o) { return o.ini < fin && o.fin > ini; }) || null;
    }

    /*
     * Cuadrícula semanal como en Horario del profesor: una columna por día, una fila por sesión y una franja por cada
     * bloque del turno (receso). Internamente cada fila de la rejilla son 5 minutos, para ubicar clases que no empiezan
     * en el borde de una sesión. Las clases de otras materias del grupo y del profesor se ven, pero no se pueden mover aquí.
     */
    function cuadricula(fila, duracion) {
      var rangos = rangosTurno(fila.idTurno);
      var conTurno = Object.keys(rangos).length > 0;
      var cortes = bloquesTurno();
      var bloques = ORDEN_DIAS.filter(function (n) { return estado.borrador[n] && estado.borrador[n].horaIni; }).map(function (n) {
        var ini = minutos(estado.borrador[n].horaIni);
        return { dia: n, ini: ini, fin: Math.min(ini + estado.borrador[n].sesiones * duracion, 1440) };
      });

      // Días: los del turno y los que ya tienen clase (para poder quitarla); sin turno configurado, lunes a sábado
      var dias = ORDEN_DIAS.filter(function (n) {
        return rangos[n] || estado.borrador[n] || (!conTurno && n !== 1);
      });

      // Las filas parten del inicio del turno y se extienden por sesiones completas hasta cubrir todas las clases
      var base = conTurno ? Math.min.apply(null, $.map(rangos, function (r) { return r.ini; })) : 7 * 60;
      var tope = conTurno ? Math.max.apply(null, $.map(rangos, function (r) { return r.fin; })) : 22 * 60;
      $.each(bloques, function (_, b) {
        while (b.ini < base) base -= duracion;
        while (b.fin > tope) tope += duracion;
      });
      base = Math.max(base, 0);

      // Una fila por sesión; al llegar a un bloque del turno se pone su franja y las sesiones siguen después de él
      var renglones = [];
      for (var t = base; t < tope || !renglones.length;) {
        var corte = cortes.find(function (c) { return c.ini <= t && t < c.fin; });
        if (corte) {
          renglones.push({ tipo: 'turno', ini: t, fin: corte.fin, bloque: corte });
          t = corte.fin;
          continue;
        }
        var siguiente = cortes.filter(function (c) { return c.ini > t; })[0];
        var fin = Math.min(t + duracion, siguiente ? siguiente.ini : 1440, 1440);
        renglones.push({ tipo: 'sesion', ini: t, fin: fin });
        t = fin;
        if (t >= 1440) break;
      }

      // Cada sesión mide 80px: alcanza para clave, nombre en dos renglones y horario aunque la clase sea de una sola sesión
      var paso = 5, unidad = 80 * paso / duracion;
      var inicioGrid = base, finGrid = renglones[renglones.length - 1].fin;
      var renglon = function (minuto) { return Math.round((Math.min(Math.max(minuto, inicioGrid), finGrid) - base) / paso) + 2; };
      var columna = function (n) { return dias.indexOf(n) + 2; };
      var total = renglon(finGrid) - 2;

      var html = '<div class="ofa-hgrid" style="grid-template-columns:96px repeat(' + dias.length + ', minmax(110px, 1fr));grid-template-rows:36px repeat(' + total + ', ' + unidad.toFixed(2) + 'px)">'
        + '<div class="ofa-hhead" style="grid-column:1;grid-row:1">Hora</div>';
      $.each(dias, function (_, n) {
        html += '<div class="ofa-hhead" style="grid-column:' + columna(n) + ';grid-row:1">' + DIAS_CORTOS[n] + '</div>';
      });

      $.each(renglones, function (_, ren) {
        var filas = 'grid-row:' + renglon(ren.ini) + ' / ' + renglon(ren.fin);
        html += '<div class="ofa-hhour" style="grid-column:1;' + filas + '">' + horaDesdeMinutos(ren.ini) + ' – ' + horaDesdeMinutos(ren.fin % 1440) + '</div>';

        if (ren.tipo === 'turno') {
          var b = ren.bloque;
          html += '<div class="ofa-hbanda" style="grid-column:2 / -1;' + filas + ';border-color:' + b.color + ';background:' + b.color + '33" title="' + escapar(b.texto) + '">'
            + '<i class="bi bi-lock" aria-hidden="true"></i>' + escapar(b.descripcion) + ' del turno</div>';
          return;
        }

        $.each(dias, function (_, n) {
          var r = rangos[n], lugar = 'grid-column:' + columna(n) + ';' + filas;
          // Se puede capturar si desde el inicio de la fila cabe una sesión completa dentro del turno (dtlTurno)
          // y sin pisar un bloque del turno ni otra clase del grupo o del profesor
          var dentro = !conTurno || (r && ren.ini >= r.ini && ren.ini + duracion <= r.fin);
          var ocupado = dentro && choque(n, ren.ini, ren.ini + duracion);
          if (!dentro || ocupado || ren.ini + duracion > 1440) {
            html += '<div class="ofa-hcell fuera" style="' + lugar + '" title="' + escapar(ocupado ? 'Ocupado: ' + ocupado.texto : 'Fuera del horario del turno') + '"></div>';
            return;
          }
          html += '<button type="button" class="ofa-hcell" style="' + lugar + '" data-dia="' + n + '" data-ini="' + horaDesdeMinutos(ren.ini) + '"'
            + ' aria-label="' + (estado.borrador[n] ? 'Cambiar la clase del ' : 'Agregar clase el ') + DIAS[n] + ' a las ' + horaDesdeMinutos(ren.ini) + '"></button>';
        });
      });

      // Otras asignaturas del mismo grupo: solo lectura; se cambian seleccionando esa asignatura
      $.each(ocupadosGrupo(), function (_, o) {
        var n = Number(o.day), ini = minutos(o.horaIni), fin = minutos(o.horaFin);
        if (dias.indexOf(n) === -1 || fin <= inicioGrid || ini >= finGrid) return;
        html += '<div class="ofa-hblk otro" style="grid-column:' + columna(n) + ';grid-row:' + renglon(ini) + ' / ' + Math.max(renglon(fin), renglon(ini) + 1) + '"'
          + ' title="' + escapar(o.idAsignatura + ' · ' + limpio(o.asignatura) + '\nSelecciona esta materia en la tabla para cambiar su horario') + '">'
          + '<strong>' + escapar(o.idAsignatura) + '</strong>'
          + '<span class="ofa-hblk-nombre">' + escapar(limpio(o.asignatura)) + '</span>'
          + '<span>' + escapar(o.horaIni + ' – ' + o.horaFin) + '</span></div>';
      });

      // Clase de esta materia: clave, nombre y horario; el texto completo también queda en el title
      $.each(bloques, function (_, b) {
        var sesiones = estado.borrador[b.dia].sesiones;
        var horas = horaDesdeMinutos(b.ini) + ' – ' + horaDesdeMinutos(b.fin % 1440) + ' · ' + (sesiones === 1 ? '1 sesión' : sesiones + ' sesiones');
        html += '<button type="button" class="ofa-hblk" data-dia="' + b.dia + '" style="grid-column:' + columna(b.dia) + ';grid-row:' + renglon(b.ini) + ' / ' + Math.max(renglon(b.fin), renglon(b.ini) + 1) + '"'
          + ' title="' + escapar(fila.idAsignatura + ' · ' + limpio(fila.asignatura) + '\n' + horas) + '"'
          + ' aria-label="Cambiar la clase del ' + DIAS[b.dia] + ', ' + horaDesdeMinutos(b.ini) + ' a ' + horaDesdeMinutos(b.fin % 1440) + '">'
          + '<strong>' + escapar(fila.idAsignatura) + '</strong><span class="ofa-hblk-nombre">' + escapar(limpio(fila.asignatura)) + '</span><span>' + horas + '</span></button>';
      });

      return '<div class="ofa-grid-wrap">' + html + '</div></div>';
    }

    // Leyenda: clase propia, lo de otros y cada tipo de bloque del turno con su color
    function leyendaHorario() {
      var tipos = {};
      $.each(bloquesTurno(), function (_, b) { tipos[b.descripcion] = b.color; });
      return '<div class="ofa-leyenda mt-2"><span><i style="border-color:var(--ofa-accent);background:var(--ofa-accent-soft)"></i>Esta materia</span>'
        + '<span><i style="border-color:var(--ofa-text-muted);border-style:dashed;background:var(--ofa-muted)"></i>Otra materia del grupo</span>'
        + $.map(tipos, function (color, descripcion) {
          return '<span><i style="border-color:' + color + ';background:' + color + '33"></i>' + escapar(descripcion) + ' del turno</span>';
        }).join('')
        + '<span><i style="border-color:var(--ofa-border);background:var(--ofa-muted)"></i>No disponible</span></div>';
    }

    // Rango del turno para el día que se edita: null si el turno no tiene detalle (sin restricción),
    // false si el turno existe pero no tiene clases ese día
    function rangoDia(n) {
      var rangos = rangosTurno(filaSeleccionada().idTurno);
      if (!Object.keys(rangos).length) return null;
      return rangos[n] || false;
    }

    function abrirDia(n, horaIni) {
      var fila = filaSeleccionada(), duracion = fila.duracionSesion, actual = estado.borrador[n], rango = rangoDia(n);
      estado.diaEditado = n;
      $('#ofa-dia-titulo').text((actual ? 'Clase del ' : 'Agregar clase el ') + DIAS[n]);
      $('#ofa-dia-grupo').text(fila.grupo + ' · ' + fila.idAsignatura + ' · ' + limpio(fila.asignatura));
      $('#ofa-dia-ini').val(actual && actual.horaIni ? actual.horaIni : horaIni)
        .attr({ min: rango ? horaDesdeMinutos(rango.ini) : null, max: rango ? horaDesdeMinutos(rango.fin - duracion) : null })
        .prop('disabled', rango === false);
      $('#ofa-dia-turno').text(rango === false
        ? 'El turno ' + limpio(fila.turno) + ' no tiene clases el ' + DIAS[n] + '. Solo puedes quitar esta clase.'
        : rango ? 'Horario del turno el ' + DIAS[n] + ': ' + horaDesdeMinutos(rango.ini) + ' – ' + horaDesdeMinutos(rango.fin) + '. La hora de fin se calcula con la duración de ' + duracion + ' min por sesión.'
          : 'La hora de fin se calcula con la duración de ' + duracion + ' min por sesión.');
      $('#ofa-form-dia [type="submit"]').prop('disabled', rango === false);
      $('#ofa-dia-quitar').toggleClass('d-none', !actual);
      $('#ofa-modal-dia .ofa-error').empty();
      llenarSesiones(actual ? actual.sesiones : 1);
      modalDia.show();
    }

    // Solo ofrece las duraciones que terminan dentro del turno a partir de la hora de inicio
    function llenarSesiones(preferida) {
      var duracion = filaSeleccionada().duracionSesion, rango = rangoDia(estado.diaEditado);
      var ini = $('#ofa-dia-ini').val() ? minutos($('#ofa-dia-ini').val()) : null;
      var opciones = '', validas = [], estorbo = null;
      for (var s = 1; s <= MAX_SESIONES; s++) {
        if (rango && ini !== null && ini + s * duracion > rango.fin) break;
        // Sesiones seguidas: si la siguiente pisa un receso u otra clase, ya no se ofrecen más
        estorbo = ini !== null ? choque(estado.diaEditado, ini, ini + s * duracion) : null;
        if (estorbo) break;
        validas.push(s);
        opciones += '<option value="' + s + '">' + (s === 1 ? '1 sesión' : s + ' sesiones seguidas') + ' (' + (s * duracion) + ' min)</option>';
      }
      if (!validas.length) opciones = '<option value="">' + escapar(estorbo ? 'Ocupado: ' + estorbo.texto : 'No cabe en el horario del turno') + '</option>';
      var elegida = validas.indexOf(preferida) !== -1 ? preferida : validas[validas.length - 1];
      $('#ofa-dia-sesiones').html(opciones).val(elegida ? String(elegida) : '').prop('disabled', !validas.length || rango === false);
      pintarFinDia();
    }

    function pintarFinDia() {
      var sesiones = Number($('#ofa-dia-sesiones').val());
      var fin = sesiones ? horaFin({ horaIni: $('#ofa-dia-ini').val(), sesiones: sesiones }, filaSeleccionada().duracionSesion) : '';
      $('#ofa-dia-fin').val(fin || '—');
    }

    /*
     * Cada cambio de la cuadrícula se guarda en ese momento: se manda el horario completo (un día por clase)
     * y la API regenera las sesiones no impartidas del periodo. Sin días con clase, se elimina el horario.
     * El estado solo cambia si la API acepta, para no mostrar algo que no quedó guardado.
     */
    function guardarHorario(borrador) {
      var id = estado.seleccion;
      var dias = ORDEN_DIAS.filter(function (n) { return borrador[n]; }).map(function (n) {
        return { day: n, horaIni: borrador[n].horaIni, sesiones: borrador[n].sesiones };
      });
      var peticion = dias.length
        ? api('PUT', '/horarios/' + id, { dias: dias })
        : api('DELETE', '/horarios/' + id).then(function () { return api('GET', '/horarios/' + id); });

      return peticion.then(function (r) {
        if (estado.seleccion === id) {
          recibirHorario(r.horario);
          pintarDetalle();
          guardado();
        }
        cargarOferta(true);
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
      recordarTurno();
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
        $('#ofa-logo-actual').html(logoActual(estado.logo));
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
      var profesorAnterior = estado.acta.profesor.uid;
      persona.uid = $(this).data('uid');
      persona.nombre = String($(this).data('nombre'));
      if ($input.data('rol') === 'profesor') {
        persona.secuencia = $(this).data('secuencia');
        persona.cambio = true;
        // El presidente predeterminado es el profesor: se actualiza si era el predeterminado, estaba vacío
        // o era el profesor anterior; si se eligió a mano, se respeta
        var presidente = estado.acta.presidente;
        if (presidente.predeterminado || !presidente.uid || String(presidente.uid) === String(profesorAnterior)) {
          estado.acta.presidente = { uid: persona.uid, nombre: persona.nombre, predeterminado: true };
          $(this).parent().addClass('d-none').empty();
          errorDetalle('');
          return pintarDetalle();
        }
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

    // Celda vacía: agrega la clase de ese día a esa hora; si el día ya tiene clase, la abre para cambiarla
    // Solo celdas libres y la clase de esta materia; lo de otras materias y del profesor no responde
    $detalle.on('click', 'button.ofa-hcell, button.ofa-hblk', function () {
      abrirDia(Number($(this).data('dia')), $(this).data('ini'));
    });

    // Al cambiar la hora de inicio se recalculan las duraciones que caben; la hora de fin nunca se captura
    $('#ofa-dia-ini').on('input change', function () {
      llenarSesiones(Number($('#ofa-dia-sesiones').val()));
    });
    $('#ofa-dia-sesiones').on('change', pintarFinDia);

    $('#ofa-form-dia').on('submit', function (e) {
      e.preventDefault();
      var n = estado.diaEditado, ini = $('#ofa-dia-ini').val(), sesiones = Number($('#ofa-dia-sesiones').val());
      var duracion = filaSeleccionada().duracionSesion, rango = rangoDia(n);
      var $error = $('#ofa-modal-dia .ofa-error');
      if (rango === false) return $error.html(alerta('El turno no tiene clases este día.'));
      if (!ini) return $error.html(alerta('Captura la hora de inicio.'));
      if (rango && (minutos(ini) < rango.ini || !sesiones || minutos(ini) + sesiones * duracion > rango.fin)) {
        return $error.html(alerta('La clase debe quedar entre ' + horaDesdeMinutos(rango.ini) + ' y ' + horaDesdeMinutos(rango.fin) + ', el horario del turno.'));
      }
      if (!horaFin({ horaIni: ini, sesiones: sesiones }, duracion)) return $error.html(alerta('La clase debe terminar antes de medianoche.'));
      var estorbo = choque(n, minutos(ini), minutos(ini) + sesiones * duracion);
      if (estorbo) return $error.html(alerta('Se traslapa con ' + estorbo.texto + ' (' + horaDesdeMinutos(estorbo.ini) + ' – ' + horaDesdeMinutos(estorbo.fin) + ').'));

      // Se guarda al aplicar; si la API rechaza, el modal sigue abierto con su mensaje
      var nuevo = $.extend({}, estado.borrador);
      nuevo[n] = { horaIni: hora(ini), sesiones: sesiones };
      var $boton = $(this).find('[type="submit"]');
      bloquear($boton, true);
      $error.empty();
      guardarHorario(nuevo).done(function () {
        modalDia.hide();
      }).fail(function (mensaje) {
        $error.html(alerta(mensaje));
      }).always(function () {
        bloquear($boton, false);
      });
    });

    // Quitar un día borra sus sesiones, así que pide confirmación; el modal del día se cierra antes de abrir la confirmación
    $('#ofa-dia-quitar').on('click', function () {
      var n = estado.diaEditado, fila = filaSeleccionada();
      var nuevo = $.extend({}, estado.borrador);
      delete nuevo[n];
      var ultimo = !ORDEN_DIAS.some(function (x) { return nuevo[x]; });

      $('#ofa-modal-dia').one('hidden.bs.modal', function () {
        confirmar('Quitar la clase del ' + DIAS[n],
          'Se borrarán las sesiones no impartidas del ' + DIAS[n] + ' de ' + fila.idAsignatura + ' (' + fila.grupo + ') dentro del periodo. Esta acción no se puede deshacer.',
          '<span>' + (ultimo ? 'Era el único día con clase' : 'Se conserva') + '</span><span style="font-weight:500">'
            + (ultimo ? 'La materia se queda sin horario; las sesiones impartidas se conservan' : 'Las demás clases, las sesiones impartidas y las que estén fuera del periodo') + '</span>',
          function () { return guardarHorario(nuevo); });
      });
      modalDia.hide();
    });

    pintarTabla();
    pintarDetalle();
  })();
  </script>
