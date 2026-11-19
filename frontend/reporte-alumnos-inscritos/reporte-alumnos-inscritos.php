<?php
// Llamar al CRUD/UTILS
include 'crud/utils.php';
// Llamar al CRUD/GET
include 'crud/get.php';

// Dirección de apirest; en otro servidor se cambia con la variable de entorno SIAWEB_API_URL
$apiUrl = rtrim(getenv('SIAWEB_API_URL') ?: 'https://api.pruebas.siaweb.com.mx/api', '/');
$jsonOpciones = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
<body>
  <style>
    /* Sistema de diseño SIAWEB; todo vive bajo .rai para no tocar otras secciones */
    .rai, .rai-modal {
      --rai-surface: #ffffff; --rai-muted: #edf1ed; --rai-border: #dfe5df;
      --rai-text: #1b221c; --rai-text-muted: #5c665d;
      --rai-accent: #1c7a3d; --rai-accent-strong: #146130; --rai-accent-soft: #e2f4e9;
      --rai-bad-bg: #fbe7e4; --rai-bad-text: #a23b2e; --rai-on-accent: #ffffff; --rai-shadow: rgba(20, 30, 20, .10);
      color: var(--rai-text);
    }
    .dark .rai, .dark .rai-modal {
      --rai-surface: #1c211e; --rai-muted: #252b27; --rai-border: #343c36;
      --rai-text: #e8ece8; --rai-text-muted: #9aa79c;
      --rai-accent: #3fbf70; --rai-accent-strong: #57d184; --rai-accent-soft: #1c3226;
      --rai-bad-bg: #3a221d; --rai-bad-text: #f0a08f; --rai-on-accent: #0f1a12; --rai-shadow: rgba(0, 0, 0, .45);
    }
    .rai .rai-card { background: var(--rai-surface); border: 1px solid var(--rai-border); border-radius: 14px; box-shadow: 0 10px 30px var(--rai-shadow); padding: 20px; max-width: 760px; }
    .rai .rai-cabecera { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
    .rai .rai-icono, .rai-modal .rai-icono { width: 34px; height: 34px; border-radius: 999px; background: var(--rai-accent-soft); color: var(--rai-accent-strong); display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .rai .rai-titulo { font-size: 16px; font-weight: 700; }
    .rai .rai-sub { font-size: 13px; color: var(--rai-text-muted); }
    .rai .rai-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
    .rai .form-control { border: 1.5px solid var(--rai-border); border-radius: 10px; background-color: var(--rai-surface); color: var(--rai-text); }
    .rai .form-control:focus { border-color: var(--rai-accent); box-shadow: 0 0 0 3px var(--rai-accent-soft); }
    .rai .form-control.is-invalid { border-color: var(--rai-bad-text); }
    .rai .rai-acciones { display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap; margin-top: 18px; }
    .rai .rai-btn, .rai-modal .rai-btn { display: inline-flex; align-items: center; gap: 8px; padding: 9px 18px; border-radius: 10px; font-size: 14px; font-weight: 600; border: 1.5px solid transparent; text-decoration: none; }
    .rai .rai-btn:disabled { opacity: .5; cursor: not-allowed; }
    .rai .rai-btn.primario, .rai-modal .rai-btn.primario { background: var(--rai-accent); color: var(--rai-on-accent); }
    .rai .rai-btn.secundario, .rai-modal .rai-btn.secundario { background: var(--rai-surface); border-color: var(--rai-border); color: var(--rai-text); }
    .rai .rai-ayuda { font-size: 12.5px; color: var(--rai-text-muted); margin-top: 6px; }
    .rai .rai-alerta { display: flex; gap: 8px; align-items: flex-start; background: var(--rai-bad-bg); color: var(--rai-bad-text); border-radius: 10px; padding: 10px 12px; font-size: 13px; font-weight: 500; margin-top: 14px; }
    .rai-modal .modal-content { border-radius: 14px; border: 1px solid var(--rai-border); background: var(--rai-surface); color: var(--rai-text); }
    .rai-modal .modal-header { border-bottom: 0; padding: 16px 20px; }
    .rai-modal .modal-title { font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .rai-modal .modal-body { padding: 0 20px 16px; }
    .rai-modal .modal-footer { border-top: 1px solid var(--rai-muted); padding: 12px 20px; gap: 10px; }
    .rai-modal .rai-visor { width: 100%; height: 72vh; border: 1px solid var(--rai-border); border-radius: 10px; background: #ffffff; }
  </style>

  <div class="rai" id="rai">
    <div class="rai-card">
      <div class="rai-cabecera">
        <span class="rai-icono"><i class="bi bi-people"></i></span>
        <div>
          <div class="rai-titulo">Reporte de alumnos inscritos</div>
          <div class="rai-sub">Alumnos dados de alta entre las fechas elegidas, con UID, nombre, carrera y fecha de inscripción.</div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-12 col-sm-6">
          <label class="rai-label" for="rai-inicio">Fecha de inicio</label>
          <input class="form-control" id="rai-inicio" type="date" required>
        </div>
        <div class="col-12 col-sm-6">
          <label class="rai-label" for="rai-fin">Fecha de fin</label>
          <input class="form-control" id="rai-fin" type="date" required>
        </div>
      </div>
      <div class="rai-ayuda">Se toma la fecha de alta del alumno; el rango incluye los dos días elegidos completos.</div>

      <div id="rai-error" aria-live="polite"></div>

      <div class="rai-acciones">
        <button type="button" class="rai-btn secundario" id="rai-excel" data-tipo="excel"><i class="bi bi-file-earmark-excel"></i>Excel</button>
        <button type="button" class="rai-btn primario" id="rai-pdf" data-tipo="pdf"><i class="bi bi-file-earmark-pdf"></i>PDF</button>
      </div>
    </div>
  </div>

  <div class="modal fade rai-modal" id="rai-modal" tabindex="-1" aria-labelledby="rai-modal-titulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title" id="rai-modal-titulo"><span class="rai-icono"><i class="bi bi-people"></i></span><span id="rai-modal-texto">Alumnos inscritos</span></h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body" id="rai-modal-cuerpo"></div>
        <div class="modal-footer">
          <a class="rai-btn secundario" id="rai-descargar" href="#" target="_blank" rel="noopener"><i class="bi bi-download"></i>Descargar</a>
          <button type="button" class="rai-btn primario" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <script>
  (function () {
    'use strict';

    var API = <?= json_encode($apiUrl, $jsonOpciones) ?>;
    var RUTAS = { pdf: '/alumnos/inscritosFechas/', excel: '/alumnos/inscritosFechasExc/' };

    // El layout puede recortar los modales; van directo en body
    $('#rai-modal').appendTo('body');
    var modal = new bootstrap.Modal('#rai-modal');

    function escapar(texto) {
      return String(texto === null || texto === undefined ? '' : texto)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function alerta(mensaje) {
      return '<div class="rai-alerta" role="alert"><i class="bi bi-exclamation-circle"></i><span>' + escapar(mensaje) + '</span></div>';
    }

    function iso(fecha) {
      var m = fecha.getMonth() + 1, d = fecha.getDate();
      return fecha.getFullYear() + '-' + (m < 10 ? '0' : '') + m + '-' + (d < 10 ? '0' : '') + d;
    }

    function fechaCorta(valor) {
      var p = String(valor || '').split('-');
      return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : valor;
    }

    // Mensaje legible de los errores de apirest ({message, error}) y de Laravel ({message, errors})
    function mensajeError(xhr) {
      var r = xhr.responseJSON || {}, partes = [];
      var errores = r.error || r.errors;
      if (errores && typeof errores === 'object') {
        $.each(errores, function (_, lista) { partes = partes.concat(lista); });
      }
      if (partes.length) return partes.join(' ');
      if (xhr.status === 0) return 'No hay conexión con la API.';
      if (xhr.status >= 500) return 'Ocurrió un error en el servidor (' + xhr.status + '). Intenta de nuevo; si continúa, avisa a sistemas.';
      if (/^The route .* could not be found/i.test(r.message || '')) return 'La API no tiene este reporte; puede faltar subir la versión más reciente.';
      return r.message || 'No se pudo generar el reporte (' + xhr.status + ').';
    }

    /*
     * La fecha de inicio no puede ser posterior a la de fin: cada campo limita al otro con min/max
     * y, si se captura a mano algo fuera de rango, se marca el error y se bloquean los botones.
     */
    function validar() {
      var inicio = $('#rai-inicio').val(), fin = $('#rai-fin').val(), mensaje = '';

      $('#rai-fin').attr('min', inicio || null);
      $('#rai-inicio').attr('max', fin || null);

      if (!inicio || !fin) mensaje = 'Elige la fecha de inicio y la fecha de fin.';
      else if (inicio > fin) mensaje = 'La fecha de inicio no puede ser posterior a la fecha de fin.';

      $('#rai-inicio').toggleClass('is-invalid', !!mensaje && (!inicio || inicio > fin));
      $('#rai-fin').toggleClass('is-invalid', !!mensaje && (!fin || inicio > fin));
      $('#rai-error').html(mensaje ? alerta(mensaje) : '');
      $('#rai-excel, #rai-pdf').prop('disabled', !!mensaje);
      return !mensaje;
    }

    function generar(tipo, $boton) {
      if (!validar()) return;
      var inicio = $('#rai-inicio').val(), fin = $('#rai-fin').val();
      var original = $boton.html();

      $('#rai-excel, #rai-pdf').prop('disabled', true);
      $boton.html('<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>Generando…');
      $('#rai-error').empty();

      $.ajax({ url: API + RUTAS[tipo] + inicio + '/' + fin, type: 'GET', dataType: 'json' }).done(function (r) {
        var url = r && r.message;
        if (!url || !/^https?:\/\//.test(url)) {
          $('#rai-error').html(alerta((r && r.message) || 'No se pudo generar el reporte.'));
          return;
        }
        // El PDF se muestra directo; el Excel con el visor de Office (necesita que el archivo sea público)
        var visor = tipo === 'excel' ? 'https://view.officeapps.live.com/op/view.aspx?src=' + encodeURIComponent(url) : url;
        $('#rai-modal-texto').text('Alumnos inscritos del ' + fechaCorta(inicio) + ' al ' + fechaCorta(fin) + (tipo === 'excel' ? ' (Excel)' : ' (PDF)'));
        $('#rai-modal-cuerpo').html('<iframe class="rai-visor" title="Reporte de alumnos inscritos" src="' + escapar(visor) + '"></iframe>');
        $('#rai-descargar').attr('href', url);
        modal.show();
      }).fail(function (xhr) {
        $('#rai-error').html(alerta(mensajeError(xhr)));
      }).always(function () {
        // validar() reactiva los botones; si las fechas siguen bien, se conserva el error que mandó la API
        var errorApi = $('#rai-error').html();
        $boton.html(original);
        if (validar() && errorApi) $('#rai-error').html(errorApi);
      });
    }

    $('#rai-inicio, #rai-fin').on('input change', validar);
    $('#rai-excel, #rai-pdf').on('click', function () { generar($(this).data('tipo'), $(this)); });
    // Al cerrar se quita el visor para que no siga cargando
    $('#rai-modal').on('hidden.bs.modal', function () { $('#rai-modal-cuerpo').empty(); });

    // Por omisión: del primer día del mes a hoy
    var hoy = new Date();
    $('#rai-inicio').val(iso(new Date(hoy.getFullYear(), hoy.getMonth(), 1)));
    $('#rai-fin').val(iso(hoy));
    validar();
  })();
  </script>
</body>
