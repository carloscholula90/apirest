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

  var modalCalendario = new bootstrap.Modal('#modal-calendario');
  var $tabla = $('#tabla-calendarios');

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
      $('#tabla-concentrado').html(EP.filaVacia(15, 'documento', 'Sin calendario seleccionado', 'Elige un calendario para ver su concentrado.'));
      $('#pie-concentrado').empty();
      return;
    }
    $('#tabla-concentrado').html(EP.filaCargando(15));
    $('#pie-concentrado').empty();
    EP.limpiarAlerta($('#alerta-omitidos'));
    EP.api('GET', '/pagos/concentrado/' + c.idCalendarioPago).done(function (r) {
      estado.concentrado = r.concentrado || [];
      pintarConcentrado();
      pintarResumen();
      pintarOmitidos(r.omitidos || []);
    }).fail(function (mensaje, xhr) {
      var pendiente = xhr && xhr.status === 404;
      $('#tabla-concentrado').html(EP.filaVacia(15, pendiente ? 'info' : 'alerta',
        pendiente ? 'Cálculo del pago pendiente en apirest' : 'No se pudo cargar el concentrado',
        pendiente ? 'Se mostrará cuando exista la ruta /pagos/concentrado/{idCalendarioPago}.' : mensaje));
    });
  }

  // ---------- Pintado ----------

  function pintarOmitidos(omitidos) {
    if (!omitidos.length) return EP.limpiarAlerta($('#alerta-omitidos'));
    var detalle = omitidos.slice(0, 5).map(function (o) {
      return (o.nombre || 'UID ' + o.uid) + ': ' + o.motivo;
    }).join('; ') + (omitidos.length > 5 ? '; y ' + (omitidos.length - 5) + ' más' : '');
    EP.mostrarAlerta($('#alerta-omitidos'), omitidos.length + ' profesor(es) con horas en este calendario no se incluyen. ' + detalle + '.');
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
    $('#resumen-titulo').text(c ? tituloCalendario(c) : 'Sin calendario seleccionado');
    $('#reporte-titulo').text(c ? tituloCalendario(c) : 'sin calendario');
    $('#resumen-estado').text(c ? (c.procesado ? 'Procesado' : 'Por procesar') : '')
      .toggleClass('good', !!(c && c.procesado)).toggleClass('muted', !(c && c.procesado));
    $('#btn-procesar').prop('disabled', !c || c.procesado);
    $('#estado-procesado').html(c && c.procesado ? '<span class="saved">' + EP.icono('candado') + 'Procesado</span>' : '');

    var hay = estado.concentrado !== null;
    var t = totales();
    $('#kpi-empleados').text(hay ? EP.numero(t.empleados) : '—');
    $('#kpi-horas').text(hay ? EP.numero(t.horas) : '—');
    $('#kpi-descontar').text(hay ? EP.numero(t.descontar) : '—');
    $('#kpi-retenciones').text(hay ? EP.dinero(t.retIsr + t.retIva) : '—');
    $('#kpi-neto').text(hay ? EP.dinero(t.neto) : '—');
    $('#resumen-calculo').toggleClass('d-none', !hay || !t.empleados);
    $('#calc-horas').text(EP.numero(t.pagar));
    $('#calc-honorarios').text(EP.dinero(t.honorarios));
    $('#calc-iva').text('+' + EP.dinero(t.iva));
    $('#calc-retenciones').text('−' + EP.dinero(t.retIsr + t.retIva));
    $('#calc-neto').text(EP.dinero(t.neto));
  }

  function pintarConcentrado() {
    var filas = estado.concentrado || [];
    if (!filas.length) {
      $('#tabla-concentrado').html(EP.filaVacia(15, 'usuarios', 'Sin empleados en este calendario', 'No hay horas registradas para el periodo.'));
      $('#pie-concentrado').empty();
      return;
    }
    $('#tabla-concentrado').html(filas.map(function (r, i) {
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
    $('#pie-concentrado').html('<tr><td></td><td>Totales</td><td></td><td></td>'
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
    var $turno = $('#calendario-turno').empty();
    estado.turnos.forEach(function (t) { $turno.append($('<option>').val(t.idTurno).text($.trim(t.descripcion))); });
    if (c) $turno.val(c.idTurno);
    $('#calendario-inicio').val(c ? String(c.fechaInicio).substr(0, 10) : '');
    $('#calendario-fin').val(c ? String(c.fechaFin).substr(0, 10) : '');
    $('#form-calendario .ep-texto-titulo').text(c ? 'Editar calendario de pago' : 'Nuevo calendario de pago');
    EP.limpiarAlerta($('#form-calendario .ep-error'));
    modalCalendario.show();
  }

  $('#btn-nuevo-calendario').on('click', function () { abrirCalendario(null); });

  $tabla.on('click', '[data-editar]', function () {
    var id = Number($(this).data('editar'));
    abrirCalendario(estado.calendarios.find(function (c) { return c.idCalendarioPago === id; }));
  });

  $('#form-calendario').on('submit', function (e) {
    e.preventDefault();
    var $error = $(this).find('.ep-error');
    var c = estado.editando;
    var datos = {
      idTurno: Number($('#calendario-turno').val()),
      fechaInicio: $('#calendario-inicio').val(),
      fechaFin: $('#calendario-fin').val(),
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

  $('#btn-procesar').on('click', function () {
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
            EP.marcarGuardado($('#estado-procesado'), 'Procesado: ' + (r.pagos || 0) + ' pago(s)');
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
