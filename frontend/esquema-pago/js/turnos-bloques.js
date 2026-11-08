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

  var $tablaDetalle = $('#tabla-detalle');
  var $tablaDefault = $('#tabla-default');
  var $tablaTipos = $('#tabla-tipos');
  var modalDetalle = new bootstrap.Modal('#modal-detalle');
  var modalDefault = new bootstrap.Modal('#modal-default');
  var modalTipo = new bootstrap.Modal('#modal-tipo');

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

      var $sel = $('#sel-turno').empty();
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
      EP.mostrarAlerta($('#alerta-general'), 'No se pudieron cargar los turnos. ' + mensaje);
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
    $('#turno-letra').text(turno && turno.letra ? turno.letra : '—');
    $('#turno-parciales').text(turno && turno.parciales !== null && turno.parciales !== undefined ? turno.parciales : '—');

    if (estado.detalle.length) {
      var inicio = estado.detalle.reduce(function (m, d) { return EP.hora(d.horaInicio) < m ? EP.hora(d.horaInicio) : m; }, '99:99');
      var fin = estado.detalle.reduce(function (m, d) { return EP.hora(d.horaFin) > m ? EP.hora(d.horaFin) : m; }, '00:00');
      $('#turno-horario').text(inicio + ' – ' + fin);
    } else {
      $('#turno-horario').text('Sin días definidos');
    }
    $('#turno-dias').html(EP.chipsDias(diasDelTurno()));
    $('#btn-nuevo-default').prop('disabled', !estado.detalle.length);
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

  $('#sel-turno').on('change', function () {
    estado.idTurno = Number($(this).val());
    cargarTurno();
  });

  // ---------- Detalle de turno ----------

  $('#btn-nuevo-detalle').on('click', function () {
    var turno = turnoActual();
    if (!turno) return;
    var usados = diasDelTurno();
    var $dia = $('#detalle-dia').empty();
    var primeroLibre = null;
    for (var d = 1; d <= 7; d++) {
      var usado = usados.indexOf(d) >= 0;
      if (!usado && primeroLibre === null) primeroLibre = d;
      $dia.append($('<option>').val(d).text(EP.DIAS[d] + (usado ? ' (ya registrado)' : '')).prop('disabled', usado));
    }
    if (primeroLibre === null) {
      return EP.mostrarAlerta($('#alerta-general'), 'El turno ' + turno.descripcion + ' ya tiene registrados los 7 días.');
    }
    $dia.val(primeroLibre);
    $('#detalle-inicio').val('08:00');
    $('#detalle-fin').val('14:00');
    $('#form-detalle .ep-texto-titulo').text('Nuevo día en ' + turno.descripcion);
    EP.limpiarAlerta($('#form-detalle .ep-error'));
    modalDetalle.show();
  });

  $('#form-detalle').on('submit', function (e) {
    e.preventDefault();
    var $error = $(this).find('.ep-error');
    var datos = {
      idTurno: estado.idTurno,
      diaSemana: Number($('#detalle-dia').val()),
      horaInicio: $('#detalle-inicio').val(),
      horaFin: $('#detalle-fin').val()
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
    var $tipo = $('#default-tipo').empty();
    var usados = estado.defaults.map(function (d) { return Number(d.idTipoBloque); });
    estado.tipos.forEach(function (t) {
      var esActual = registro && Number(registro.idTipoBloque) === Number(t.idTipoBloque);
      // La llave es (turno, tipo): un tipo solo puede estar una vez por turno
      if (!esActual && (Number(t.activo) !== 1 || usados.indexOf(Number(t.idTipoBloque)) >= 0)) return;
      $tipo.append($('<option>').val(t.idTipoBloque).text($.trim(t.descripcion)));
    });
    if (!registro && !$tipo.children().length) {
      return EP.mostrarAlerta($('#alerta-general'), 'Todos los tipos de bloque activos ya tienen un default en este turno.');
    }
    $tipo.prop('disabled', !!registro);
    $('#default-tipo-ayuda').toggleClass('d-none', !registro);
    if (registro) $tipo.val(registro.idTipoBloque);
    $('#default-inicio').val(registro ? EP.hora(registro.horaInicio) : '');
    $('#default-fin').val(registro ? EP.hora(registro.horaFin) : '');
    $('#default-activo').prop('checked', registro ? Number(registro.activo) === 1 : true);
    $('#default-rango').text(rangoPermitido());
    $('#form-default .ep-texto-titulo').text(registro ? 'Editar bloque por default' : 'Nuevo bloque por default');
    EP.limpiarAlerta($('#form-default .ep-error'));
    modalDefault.show();
  }

  $('#btn-nuevo-default').on('click', function () { abrirDefault(null); });

  $tablaDefault.on('click', '[data-editar-default]', function () {
    var id = Number($(this).data('editar-default'));
    abrirDefault(estado.defaults.find(function (d) { return Number(d.idTipoBloque) === id; }));
  });

  $('#form-default').on('submit', function (e) {
    e.preventDefault();
    var $error = $(this).find('.ep-error');
    var editando = estado.defaultEditando;
    var idTipo = Number($('#default-tipo').val());
    var inicio = $('#default-inicio').val();
    var fin = $('#default-fin').val();

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

    var datos = { horaInicio: inicio, horaFin: fin, activo: $('#default-activo').is(':checked') ? 1 : 0 };
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
    $('#tipo-descripcion').val(tipo ? $.trim(tipo.descripcion) : '');
    $('#tipo-color').val(tipo && /^#[0-9A-Fa-f]{6}$/.test(String(tipo.color).replace(';', '')) ? String(tipo.color).replace(';', '') : '#3B82F6');
    $('#tipo-activo').prop('checked', tipo ? Number(tipo.activo) === 1 : true);
    $('#tipo-pagable').prop('checked', tipo && tipo.pagable !== undefined ? Number(tipo.pagable) === 1 : true);
    $('#btn-eliminar-tipo').toggleClass('d-none', !tipo);
    $('#form-tipo .ep-texto-titulo').text(tipo ? 'Editar tipo de bloque' : 'Nuevo tipo de bloque');
    EP.limpiarAlerta($('#form-tipo .ep-error'));
    modalTipo.show();
  }

  $('#btn-nuevo-tipo').on('click', function () { abrirTipo(null); });

  $tablaTipos.on('click', '[data-editar-tipo]', function () {
    var id = Number($(this).data('editar-tipo'));
    abrirTipo(tipoPorId(id));
  });

  $('#form-tipo').on('submit', function (e) {
    e.preventDefault();
    var $error = $(this).find('.ep-error');
    var tipo = estado.tipoEditando;
    var descripcion = $.trim($('#tipo-descripcion').val()).toUpperCase();
    if (!descripcion) return EP.mostrarAlerta($error, 'Escribe la descripción del tipo de bloque.');
    var repetido = estado.tipos.find(function (t) {
      return $.trim(t.descripcion).toUpperCase() === descripcion && (!tipo || t.idTipoBloque !== tipo.idTipoBloque);
    });
    if (repetido) return EP.mostrarAlerta($error, 'Ya existe un tipo de bloque llamado ' + descripcion + '.');

    var datos = {
      descripcion: descripcion,
      color: $('#tipo-color').val().toUpperCase(),
      activo: $('#tipo-activo').is(':checked') ? 1 : 0,
      pagable: $('#tipo-pagable').is(':checked') ? 1 : 0
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

  $('#btn-eliminar-tipo').on('click', function () {
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
