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

  var modalImpuesto = new bootstrap.Modal('#modal-impuesto');
  var modalRegimen = new bootstrap.Modal('#modal-regimen');
  var modalCosto = new bootstrap.Modal('#modal-costo');
  var modalAsignar = new bootstrap.Modal('#modal-asignar');

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
    $('#tabla-catalogo').html(EP.filaCargando(5));
    return EP.api('GET', '/impuestos').done(function (r) {
      estado.catalogo = (r.impuestos || []).slice().sort(function (a, b) { return a.idImpuesto - b.idImpuesto; });
      pintarCatalogo();
    }).fail(function (mensaje) {
      $('#tabla-catalogo').html(EP.filaVacia(5, 'alerta', 'No se pudo cargar el catálogo', mensaje));
    });
  }

  function pintarCatalogo() {
    $('#tabla-catalogo').html(estado.catalogo.length ? estado.catalogo.map(function (i) {
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
      if (!lista.length) return EP.limpiarAlerta($('#alerta-sin-regimen'));
      var nombres = lista.slice(0, 5).map(function (p) {
        return $.trim([p.primerApellido, p.segundoApellido, p.nombre].filter(Boolean).join(' ')).toUpperCase();
      }).join(', ') + (lista.length > 5 ? ' y ' + (lista.length - 5) + ' más' : '');
      EP.mostrarAlerta($('#alerta-sin-regimen'), lista.length + ' empleado(s) activo(s) sin régimen fiscal (' + nombres
        + '). No se incluirán al procesar el pago.');
    });
  }

  function abrirImpuesto(impuesto) {
    estado.impuestoEditando = impuesto;
    $('#impuesto-descripcion').val(impuesto ? $.trim(impuesto.descripcion) : '');
    $('#impuesto-porcentaje').val(impuesto && impuesto.porcentaje !== undefined ? Number(impuesto.porcentaje) : '');
    $('#impuesto-retencion').prop('checked', impuesto ? Number(impuesto.esRetencion) === 1 : false);
    $('#btn-eliminar-impuesto').toggleClass('d-none', !impuesto);
    $('#form-impuesto .ep-texto-titulo').text(impuesto ? 'Editar impuesto' : 'Nuevo impuesto');
    EP.limpiarAlerta($('#form-impuesto .ep-error'));
    modalImpuesto.show();
  }

  $('#btn-nuevo-impuesto').on('click', function () { abrirImpuesto(null); });

  $('#tabla-catalogo').on('click', '[data-editar-impuesto]', function () {
    var id = Number($(this).data('editar-impuesto'));
    abrirImpuesto(estado.catalogo.find(function (i) { return Number(i.idImpuesto) === id; }));
  });

  $('#form-impuesto').on('submit', function (e) {
    e.preventDefault();
    var $error = $(this).find('.ep-error');
    var impuesto = estado.impuestoEditando;
    var descripcion = $.trim($('#impuesto-descripcion').val()).toUpperCase();
    if (!descripcion) return EP.mostrarAlerta($error, 'Escribe la descripción del impuesto.');
    var valor = $('#impuesto-porcentaje').val() === '' ? 0 : Number($('#impuesto-porcentaje').val());
    if (!(valor >= 0 && valor <= 100)) return EP.mostrarAlerta($error, 'El porcentaje debe estar entre 0 y 100.');

    var datos = { descripcion: descripcion, porcentaje: valor, esRetencion: $('#impuesto-retencion').is(':checked') ? 1 : 0 };
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

  $('#btn-eliminar-impuesto').on('click', function () {
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
    $('#aviso-pendiente').toggleClass('d-none', activar);
    $('#btn-nuevo-regimen, #sel-regimen').prop('disabled', !activar);
    $('#btn-nuevo-costo, #btn-asignar-impuesto').prop('disabled', !activar || estado.idRegimen === null);
  }

  function cargarRegimenes(seleccionar) {
    return EP.api('GET', '/regimenesfiscales').done(function (r) {
      estado.regimenes = (r.regimenesFiscales || []).map(function (x) {
        return $.extend({}, x, { idRegimenFiscal: Number(x.idRegimenFiscal), descripcion: $.trim(x.descripcion) });
      });
      var $sel = $('#sel-regimen').empty();
      estado.regimenes.forEach(function (x) { $sel.append($('<option>').val(x.idRegimenFiscal).text(x.descripcion)); });
      var existe = estado.regimenes.some(function (x) { return x.idRegimenFiscal === seleccionar; });
      estado.idRegimen = existe ? seleccionar : (estado.regimenes[0] ? estado.regimenes[0].idRegimenFiscal : null);
      if (estado.idRegimen !== null) $sel.val(estado.idRegimen);
      activarSeccionesRegimen(true);
      cargarRegimen();
    }).fail(function (mensaje, xhr) {
      activarSeccionesRegimen(false);
      var pendiente = EP.estadoVacio('info', 'Pendiente en apirest', 'Se activa cuando exista la API de régimen fiscal.');
      $('#tabla-costos').html('<tr><td colspan="4">' + pendiente + '</td></tr>');
      $('#tabla-impuestos-regimen').html('<tr><td colspan="4">' + pendiente + '</td></tr>');
      if (xhr && xhr.status !== 404) EP.mostrarAlerta($('#alerta-general'), 'No se pudieron cargar los regímenes fiscales. ' + mensaje);
    });
  }

  function cargarRegimen() {
    var regimen = regimenActual();
    $('#btn-nuevo-costo, #btn-asignar-impuesto').prop('disabled', !regimen);
    if (!regimen) {
      estado.costos = [];
      estado.impuestosRegimen = [];
      pintarRegimen();
      return;
    }
    $('#tabla-costos').html(EP.filaCargando(4));
    $('#tabla-impuestos-regimen').html(EP.filaCargando(4));
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
      EP.mostrarAlerta($('#alerta-general'), 'No se pudo cargar el régimen. ' + mensaje);
    });
  }

  function pintarRegimen() {
    var regimen = regimenActual();
    var vigente = costoVigente();
    $('#regimen-empleados').text(regimen && regimen.empleados !== undefined ? regimen.empleados : '—');
    $('#regimen-costo').text(vigente ? EP.dinero(vigente.costoHora) : 'Sin definir');
    $('#regimen-num-impuestos').text(regimen ? estado.impuestosRegimen.length : '—');
    $('#titulo-impuestos-regimen').text(regimen ? 'Impuestos de ' + regimen.descripcion : 'Impuestos del régimen');

    $('#tabla-costos').html(estado.costos.length ? estado.costos.map(function (c) {
      var esVigente = !c.vigenciaFin;
      return '<tr><td>' + EP.fecha(c.vigenciaInicio) + '</td><td>' + (c.vigenciaFin ? EP.fecha(c.vigenciaFin) : 'Sin fecha fin') + '</td>'
        + '<td class="num fw-bold">' + EP.dinero(c.costoHora) + '</td>'
        + '<td><span class="tag ' + (esVigente ? 'good' : 'muted') + '">' + (esVigente ? 'Vigente' : 'Histórico') + '</span></td></tr>';
    }).join('') : EP.filaVacia(4, 'dinero', 'Sin costo por hora', 'Registra el costo por hora y desde cuándo aplica.'));

    $('#tabla-impuestos-regimen').html(estado.impuestosRegimen.length ? estado.impuestosRegimen.map(function (i) {
      var retencion = Number(i.esRetencion) === 1;
      return '<tr><td class="fw-semibold">' + EP.escapar($.trim(i.descripcion)) + '</td>'
        + '<td class="num">' + (i.porcentaje !== undefined && i.porcentaje !== null ? EP.numero(i.porcentaje, 4).replace(/0+$/, '').replace(/[.,]$/, '') + '%' : '—') + '</td>'
        + '<td><span class="tag ' + (retencion ? 'bad' : 'info') + '">' + (retencion ? 'Retención' : 'Traslado') + '</span></td>'
        + '<td class="text-end"><button type="button" class="btn btn-icon del" data-quitar-impuesto="' + i.idImpuesto + '" aria-label="Quitar '
        + EP.escapar($.trim(i.descripcion)) + '">' + EP.icono('eliminar') + '</button></td></tr>';
    }).join('') : EP.filaVacia(4, 'porcentaje', 'Este régimen no tiene impuestos', 'Asigna los impuestos y retenciones que se calculan al pagarle a sus empleados.'));
  }

  $('#sel-regimen').on('change', function () {
    estado.idRegimen = Number($(this).val());
    cargarRegimen();
  });

  $('#btn-nuevo-regimen').on('click', function () {
    $('#regimen-descripcion').val('');
    EP.limpiarAlerta($('#form-regimen .ep-error'));
    modalRegimen.show();
  });

  $('#form-regimen').on('submit', function (e) {
    e.preventDefault();
    var $error = $(this).find('.ep-error');
    var descripcion = $.trim($('#regimen-descripcion').val()).toUpperCase();
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

  $('#btn-nuevo-costo').on('click', function () {
    var regimen = regimenActual();
    if (!regimen) return;
    $('#costo-regimen').text(regimen.descripcion);
    $('#costo-valor').val('');
    $('#costo-desde').val('');
    $('#costo-cierre').addClass('d-none');
    EP.limpiarAlerta($('#form-costo .ep-error'));
    modalCosto.show();
  });

  $('#costo-desde').on('change', function () {
    var vigente = costoVigente();
    var desde = $(this).val();
    $('#costo-cierre').toggleClass('d-none', !vigente || !desde).find('span').text(vigente && desde
      ? 'El costo de ' + EP.dinero(vigente.costoHora) + ' (desde ' + EP.fecha(vigente.vigenciaInicio) + ') quedará vigente hasta el ' + EP.fecha(diaAntes(desde)) + '.'
      : '');
  });

  $('#form-costo').on('submit', function (e) {
    e.preventDefault();
    var $error = $(this).find('.ep-error');
    var costo = Number($('#costo-valor').val());
    var desde = $('#costo-desde').val();
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

  $('#btn-asignar-impuesto').on('click', function () {
    var regimen = regimenActual();
    if (!regimen) return;
    var asignados = estado.impuestosRegimen.map(function (i) { return Number(i.idImpuesto); });
    var disponibles = estado.catalogo.filter(function (i) { return asignados.indexOf(Number(i.idImpuesto)) < 0; });
    if (!disponibles.length) {
      return EP.mostrarAlerta($('#alerta-general'), 'El régimen ' + regimen.descripcion + ' ya tiene todos los impuestos del catálogo.');
    }
    var $sel = $('#asignar-impuesto').empty();
    disponibles.forEach(function (i) { $sel.append($('<option>').val(i.idImpuesto).text($.trim(i.descripcion))); });
    $('#form-asignar .ep-texto-titulo').text('Asignar impuesto a ' + regimen.descripcion);
    EP.limpiarAlerta($('#form-asignar .ep-error'));
    modalAsignar.show();
  });

  $('#form-asignar').on('submit', function (e) {
    e.preventDefault();
    var $error = $(this).find('.ep-error');
    var $boton = $(this).find('[type=submit]');
    EP.bloquearBoton($boton, true);
    EP.api('POST', '/regimenesfiscales/' + estado.idRegimen + '/impuestos', { idImpuesto: Number($('#asignar-impuesto').val()) }).done(function () {
      modalAsignar.hide();
      cargarRegimen();
    }).fail(function (mensaje) {
      EP.mostrarAlerta($error, mensaje);
    }).always(function () {
      EP.bloquearBoton($boton, false);
    });
  });

  $('#tabla-impuestos-regimen').on('click', '[data-quitar-impuesto]', function () {
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
