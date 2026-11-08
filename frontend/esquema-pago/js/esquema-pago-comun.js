/*
 * Esquema de pago — utilerías compartidas por las 4 pantallas.
 * Requiere jQuery 3 y Bootstrap 5 (incluido en libs.min.js de Hope UI).
 *
 * La URL de apirest se puede cambiar sin tocar este archivo:
 *   <script>window.SIAWEB_API_URL = 'https://api.siaweb.com.mx/api';</script>
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
