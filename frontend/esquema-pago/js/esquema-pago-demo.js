/*
 * Esquema de pago — modo demostración.
 * Reemplaza EP.api por una API simulada con datos de ejemplo, para probar las pantallas sin apirest.
 * Solo se activa con window.SIAWEB_DEMO = true o con ?demo=1 en la URL; los cambios se guardan en
 * el navegador (localStorage) y se pueden reiniciar.
 * Las reglas imitan a los controladores de apirest (validaciones, 404 en listas vacías, cálculo del pago).
 */
(function (window, $) {
  'use strict';

  var activo = window.SIAWEB_DEMO === true || /[?&]demo=1\b/.test(window.location.search);
  if (!activo || !window.EP) return;

  var CLAVE = 'siaweb-esquema-pago-demo-v2';
  var MINUTOS_POR_HORA = 50; // hora clase, igual que PagoController

  function datosIniciales() {
    var dias = function (desde, hasta, ini, fin, primerId, idTurno) {
      var r = [];
      for (var d = desde; d <= hasta; d++) r.push({ idDtlTurno: primerId + d, idTurno: idTurno, diaSemana: String(d), horaInicio: ini + ':00', horaFin: fin + ':00' });
      return r;
    };
    var horario = function (uid, sec, dia, tipo, ini, fin) {
      return { uid: uid, secuencia: sec, fechaInicio: '2026-08-24', fechaFin: '2026-12-18', diaSemana: dia, horaInicio: ini + ':00', horaFin: fin + ':00', idTipoBloque: tipo };
    };
    return {
      turnos: [
        { idTurno: 0, descripcion: 'TODOS', letra: null, parciales: null },
        { idTurno: 1, descripcion: 'MATUTINO', letra: 'M', parciales: 3 },
        { idTurno: 2, descripcion: 'VESPERTINO', letra: 'V', parciales: 3 },
        { idTurno: 3, descripcion: 'SABATINO', letra: 'S', parciales: 3 },
        { idTurno: 5, descripcion: 'EJECUTIVO', letra: 'E', parciales: 0 }
      ],
      // Turnos armados en bloques de 50 minutos con su receso
      detalles: [].concat(
        dias(2, 6, '07:00', '13:20', 0, 1),
        dias(2, 6, '13:30', '19:50', 10, 2),
        [{ idDtlTurno: 30, idTurno: 3, diaSemana: '7', horaInicio: '08:00:00', horaFin: '14:20:00' }],
        dias(3, 5, '18:00', '21:20', 50, 5)
      ),
      defaults: [
        { idTurno: 1, idTipoBloque: 2, horaInicio: '09:30:00', horaFin: '10:00:00', activo: 1 },
        { idTurno: 2, idTipoBloque: 2, horaInicio: '16:00:00', horaFin: '16:30:00', activo: 1 },
        { idTurno: 3, idTipoBloque: 2, horaInicio: '10:30:00', horaFin: '11:00:00', activo: 1 }
      ],
      tipos: [
        { idTipoBloque: 1, descripcion: 'CLASE', color: '#3B82F6', activo: 1, pagable: 1 },
        { idTipoBloque: 2, descripcion: 'RECESO', color: '#F59E0B', activo: 1, pagable: 0 },
        { idTipoBloque: 3, descripcion: 'NO DISPONIBLE', color: '#EF4444', activo: 1, pagable: 0 },
        { idTipoBloque: 4, descripcion: 'ASESORÍA', color: '#8B5CF6', activo: 1, pagable: 1 },
        { idTipoBloque: 5, descripcion: 'EVALUACIÓN', color: '#14B8A6', activo: 0, pagable: 1 }
      ],
      empleados: [
        { uid: 123, nombre: 'ELENA', primerApellido: 'RAMÍREZ', segundoApellido: 'SOTO', grado: 'MAESTRÍA', idRegimenFiscalNomina: 1 },
        { uid: 145, nombre: 'MARIO', primerApellido: 'GUTIÉRREZ', segundoApellido: 'PEÑA', grado: 'LICENCIATURA', idRegimenFiscalNomina: 2 },
        { uid: 162, nombre: 'SOFÍA', primerApellido: 'LÓPEZ', segundoApellido: 'IBARRA', grado: 'LICENCIATURA', idRegimenFiscalNomina: 2 },
        { uid: 178, nombre: 'ARTURO', primerApellido: 'MEDINA', segundoApellido: 'CRUZ', grado: 'DOCTORADO', idRegimenFiscalNomina: 1 },
        { uid: 190, nombre: 'LORENA', primerApellido: 'ACOSTA', segundoApellido: 'VEGA', grado: 'LICENCIATURA', idRegimenFiscalNomina: 1 },
        { uid: 204, nombre: 'IVÁN', primerApellido: 'BARRERA', segundoApellido: 'SALAS', grado: 'MAESTRÍA', idRegimenFiscalNomina: 2 },
        { uid: 215, nombre: 'DIEGO', primerApellido: 'CASTRO', segundoApellido: 'LUNA', grado: 'LICENCIATURA', idRegimenFiscalNomina: null },
        { uid: 231, nombre: 'PAULA', primerApellido: 'NAVARRO', segundoApellido: 'RÍOS', grado: 'MAESTRÍA', idRegimenFiscalNomina: null }
      ],
      // Bloques de 50 minutos. RAMÍREZ también da clase en EJECUTIVO el miércoles, que se cruza con VESPERTINO
      horarios: [
        horario(123, 1, 'LUNES', 1, '07:00', '08:40'), horario(123, 2, 'MARTES', 1, '07:00', '08:40'), horario(123, 3, 'MIÉRCOLES', 1, '10:00', '11:40'),
        horario(123, 4, 'JUEVES', 1, '07:00', '08:40'), horario(123, 5, 'VIERNES', 1, '10:00', '11:40'), horario(123, 6, 'LUNES', 4, '12:30', '13:20'),
        horario(123, 7, 'VIERNES', 3, '07:00', '07:50'), horario(123, 8, 'MIÉRCOLES', 1, '18:00', '19:40'),
        horario(145, 1, 'LUNES', 1, '10:00', '12:30'), horario(145, 2, 'MIÉRCOLES', 1, '07:00', '08:40'), horario(145, 3, 'JUEVES', 1, '10:00', '12:30'),
        horario(162, 1, 'MARTES', 1, '10:00', '12:30'), horario(162, 2, 'VIERNES', 1, '07:00', '08:40'),
        horario(178, 1, 'MIÉRCOLES', 1, '07:00', '08:40'), horario(178, 2, 'JUEVES', 4, '10:50', '11:40'),
        horario(190, 1, 'LUNES', 1, '13:30', '15:10'), horario(190, 2, 'MIÉRCOLES', 1, '16:30', '18:10'),
        horario(204, 1, 'MARTES', 1, '13:30', '16:00'), horario(204, 2, 'JUEVES', 1, '16:30', '19:00'),
        horario(215, 1, 'LUNES', 1, '07:00', '08:40'), horario(231, 1, 'MARTES', 1, '13:30', '15:10')
      ],
      impuestos: [
        { idImpuesto: 1, descripcion: 'IVA', porcentaje: 16, esRetencion: 0 },
        { idImpuesto: 2, descripcion: 'ISR RETENIDO', porcentaje: 10, esRetencion: 1 },
        { idImpuesto: 3, descripcion: 'IVA RETENIDO', porcentaje: 10.6667, esRetencion: 1 },
        { idImpuesto: 4, descripcion: 'ISR RETENIDO RESICO', porcentaje: 1.25, esRetencion: 1 }
      ],
      regimenes: [
        { idRegimenFiscal: 1, descripcion: 'RESICO', activo: 1 },
        { idRegimenFiscal: 2, descripcion: 'HONORARIOS', activo: 1 }
      ],
      costos: [
        { idCostoHora: 1, idRegimenFiscal: 1, costoHora: 75, vigenciaInicio: '2025-01-01', vigenciaFin: '2025-12-31' },
        { idCostoHora: 2, idRegimenFiscal: 1, costoHora: 80, vigenciaInicio: '2026-01-01', vigenciaFin: null },
        { idCostoHora: 3, idRegimenFiscal: 2, costoHora: 72, vigenciaInicio: '2025-01-01', vigenciaFin: '2025-12-31' },
        { idCostoHora: 4, idRegimenFiscal: 2, costoHora: 80, vigenciaInicio: '2026-01-01', vigenciaFin: null }
      ],
      regimenImpuestos: [
        { idRegimenFiscalImpuesto: 1, idRegimenFiscal: 1, idImpuesto: 1, activo: 1 },
        { idRegimenFiscalImpuesto: 2, idRegimenFiscal: 1, idImpuesto: 3, activo: 1 },
        { idRegimenFiscalImpuesto: 3, idRegimenFiscal: 1, idImpuesto: 4, activo: 1 },
        { idRegimenFiscalImpuesto: 4, idRegimenFiscal: 2, idImpuesto: 1, activo: 1 },
        { idRegimenFiscalImpuesto: 5, idRegimenFiscal: 2, idImpuesto: 2, activo: 1 },
        { idRegimenFiscalImpuesto: 6, idRegimenFiscal: 2, idImpuesto: 3, activo: 1 }
      ],
      calendarios: [
        { idCalendarioPago: 1, idTurno: 1, fechaInicio: '2026-10-16', fechaFin: '2026-10-31', procesado: 0, fechaCreacion: '2026-10-01 09:00:00' },
        { idCalendarioPago: 2, idTurno: 2, fechaInicio: '2026-10-16', fechaFin: '2026-10-31', procesado: 0, fechaCreacion: '2026-10-01 09:00:00' },
        { idCalendarioPago: 3, idTurno: 1, fechaInicio: '2026-11-01', fechaFin: '2026-11-15', procesado: 0, fechaCreacion: '2026-10-20 09:00:00' },
        { idCalendarioPago: 4, idTurno: 2, fechaInicio: '2026-11-01', fechaFin: '2026-11-15', procesado: 0, fechaCreacion: '2026-10-20 09:00:00' }
      ],
      pagos: [],
      sec: 1000
    };
  }

  var db;
  try { db = JSON.parse(window.localStorage.getItem(CLAVE)); } catch (e) { db = null; }
  if (!db || !db.turnos) db = datosIniciales();

  function guardar() {
    try { window.localStorage.setItem(CLAVE, JSON.stringify(db)); } catch (e) { /* sin almacenamiento: los cambios duran hasta recargar */ }
  }

  function nuevoId() { db.sec += 1; return db.sec; }
  function copia(x) { return JSON.parse(JSON.stringify(x)); }
  function hhmmss(h) { h = String(h || ''); return h.length === 5 ? h + ':00' : h; }
  function mayus(t) { return $.trim(String(t || '')).toUpperCase(); }

  function ok(clave, datos) { var r = { status: 200 }; if (clave) r[clave] = copia(datos); return r; }
  function error(status, mensaje, errores) {
    var r = { message: mensaje, status: status };
    if (errores) r.error = errores;
    throw { status: status, responseJSON: r };
  }
  function requerido(datos, campos) {
    var errores = {};
    campos.forEach(function (c) {
      if (datos[c] === undefined || datos[c] === null || datos[c] === '') errores[c] = ['El campo ' + c + ' es obligatorio.'];
    });
    if (Object.keys(errores).length) error(400, 'Error en la validacion de los datos', errores);
  }

  function tipo(id) { return db.tipos.find(function (t) { return Number(t.idTipoBloque) === Number(id); }); }
  function conTipo(x) { var c = copia(x); c.tipo_bloque = copia(tipo(x.idTipoBloque) || null); return c; }
  function turno(id) { return db.turnos.find(function (t) { return Number(t.idTurno) === Number(id); }); }
  function nombreCompleto(e) { return mayus([e.primerApellido, e.segundoApellido, e.nombre].join(' ')); }

  // ---------- Cálculo del pago (igual que PagoController) ----------

  function fechaUTC(iso) { var p = String(iso).substr(0, 10).split('-'); return Date.UTC(+p[0], +p[1] - 1, +p[2]); }
  function vecesDia(dia, desde, hasta) {
    if (desde > hasta) return 0;
    var d = desde;
    while (new Date(d).getUTCDay() + 1 !== dia) d += 86400000;
    return d > hasta ? 0 : Math.floor((hasta - d) / 86400000 / 7) + 1;
  }

  function calcular(cal) {
    var inicio = fechaUTC(cal.fechaInicio), fin = fechaUTC(cal.fechaFin);
    var rangos = {};
    db.detalles.filter(function (d) { return d.idTurno === cal.idTurno; }).forEach(function (d) {
      rangos[EP.diaNumero(d.diaSemana)] = [EP.hora(d.horaInicio), EP.hora(d.horaFin)];
    });
    var horas = {};
    db.horarios.forEach(function (h) {
      var t = tipo(h.idTipoBloque);
      var dia = EP.diaNumero(h.diaSemana);
      if (!t || Number(t.pagable) !== 1 || !rangos[dia]) return;
      if (EP.hora(h.horaInicio) < rangos[dia][0] || EP.hora(h.horaFin) > rangos[dia][1]) return;
      var veces = vecesDia(dia, Math.max(inicio, fechaUTC(h.fechaInicio)), Math.min(fin, fechaUTC(h.fechaFin)));
      if (!veces) return;
      horas[h.uid] = (horas[h.uid] || 0) + (EP.minutos(h.horaFin) - EP.minutos(h.horaInicio)) / MINUTOS_POR_HORA * veces;
    });

    var pagos = [], omitidos = [];
    Object.keys(horas).forEach(function (uidTexto) {
      var uid = Number(uidTexto);
      var emp = db.empleados.find(function (e) { return e.uid === uid; });
      if (!emp) return omitidos.push({ uid: uid, nombre: null, motivo: 'No es un empleado activo' });
      if (!emp.idRegimenFiscalNomina) return omitidos.push({ uid: uid, nombre: nombreCompleto(emp), motivo: 'No tiene regimen fiscal asignado' });
      var reg = db.regimenes.find(function (r) { return r.idRegimenFiscal === emp.idRegimenFiscalNomina; });
      var costo = db.costos.find(function (c) {
        return c.idRegimenFiscal === emp.idRegimenFiscalNomina && c.vigenciaInicio <= cal.fechaInicio && (!c.vigenciaFin || c.vigenciaFin >= cal.fechaInicio);
      });
      if (!costo) return omitidos.push({ uid: uid, nombre: nombreCompleto(emp), motivo: 'Su regimen fiscal no tiene costo por hora vigente al ' + EP.fecha(cal.fechaInicio) });
      var h = Math.round(horas[uid] * 100) / 100;
      var honorarios = Math.round(h * costo.costoHora * 100) / 100;
      var neto = honorarios, detalles = [];
      db.regimenImpuestos.filter(function (ri) { return ri.idRegimenFiscal === emp.idRegimenFiscalNomina && ri.activo; }).forEach(function (ri) {
        var imp = db.impuestos.find(function (i) { return i.idImpuesto === ri.idImpuesto; });
        if (!imp) return;
        var importe = Math.round(honorarios * imp.porcentaje) / 100;
        neto += imp.esRetencion ? -importe : importe;
        detalles.push({ idRegimenFiscalImpuesto: ri.idRegimenFiscalImpuesto, idImpuesto: imp.idImpuesto, descripcion: imp.descripcion, porcentaje: imp.porcentaje, esRetencion: imp.esRetencion, importe: importe });
      });
      pagos.push({ uid: uid, nombre: nombreCompleto(emp), grado: emp.grado, idRegimenFiscal: emp.idRegimenFiscalNomina, regimen: reg ? reg.descripcion : null,
        horasTrabajadas: h, horasDescontar: 0, costoHora: costo.costoHora, honorarios: honorarios, neto: Math.round(neto * 100) / 100, detalles: detalles });
    });
    pagos.sort(function (a, b) { return a.nombre.localeCompare(b.nombre); });
    return { pagos: pagos, omitidos: omitidos };
  }

  function filaConcentrado(p) {
    var iva = 0, retIsr = 0, pIsr = 0, retIva = 0;
    p.detalles.forEach(function (d) {
      if (!d.esRetencion) iva += d.importe;
      else if (/ISR/i.test(d.descripcion)) { retIsr += d.importe; pIsr += Number(d.porcentaje); }
      else retIva += d.importe;
    });
    return $.extend({}, p, { iva: iva, retencionIsr: retIsr, porcentajeIsr: pIsr, retencionIva: retIva });
  }

  // ---------- Rutas ----------

  var rutas = [
    ['GET', /^\/turnos$/, function () { return ok('turnos', db.turnos); }],
    ['GET', /^\/turnos\/detalles$/, function () {
      return ok('turnos', db.turnos.map(function (t) {
        var dias = db.detalles.filter(function (d) { return d.idTurno === t.idTurno; });
        return $.extend({}, t, { diasTurno: dias.map(function (d) { return { idDtlTurno: d.idDtlTurno, diaSemana: d.diaSemana, horaInicio: d.horaInicio, horaFin: d.horaFin }; }) });
      }));
    }],
    ['POST', /^\/turnos\/create$/, function (p, d) {
      requerido(d, ['descripcion']);
      var t = { idTurno: nuevoId(), descripcion: mayus(d.descripcion), letra: mayus(d.letra), parciales: Number(d.parciales || 0) };
      db.turnos.push(t);
      return ok('$turnos', t);
    }],

    ['GET', /^\/detallesturno\/(\d+)$/, function (p) {
      return ok('detallesTurno', db.detalles.filter(function (d) { return d.idTurno === +p[1]; })
        .sort(function (a, b) { return a.diaSemana - b.diaSemana; }));
    }],
    ['POST', /^\/detallesturno\/create$/, function (p, d) {
      requerido(d, ['idTurno', 'diaSemana', 'horaInicio', 'horaFin']);
      if (d.horaFin <= d.horaInicio) error(400, 'Error en la validacion de los datos', { horaFin: ['La hora fin debe ser posterior a la hora inicio.'] });
      var dia = EP.diaNumero(d.diaSemana);
      if (!dia) error(400, 'El dia de la semana no es valido, usa 1 (domingo) a 7 (sabado)');
      if (db.detalles.some(function (x) { return x.idTurno === +d.idTurno && EP.diaNumero(x.diaSemana) === dia; })) {
        error(400, 'El dia ya tiene horario en este turno, no se permite el empalme de dias');
      }
      var nuevo = { idDtlTurno: nuevoId(), idTurno: +d.idTurno, diaSemana: String(dia), horaInicio: hhmmss(d.horaInicio), horaFin: hhmmss(d.horaFin) };
      db.detalles.push(nuevo);
      return ok('detalleTurno', nuevo);
    }],
    ['DELETE', /^\/detallesturno\/(\d+)\/(\d+)$/, function (p) {
      var i = db.detalles.findIndex(function (x) { return x.idTurno === +p[1] && x.idDtlTurno === +p[2]; });
      if (i < 0) error(404, 'Detalle de turno no encontrado');
      db.detalles.splice(i, 1);
      return { message: 'Detalle de turno eliminado', status: 200 };
    }],

    ['GET', /^\/defaultturno\/?$/, function () {
      return ok('defaultTurnos', db.defaults.map(conTipo));
    }],
    ['GET', /^\/defaultturno\/(\d+)$/, function (p) {
      var lista = db.defaults.filter(function (x) { return x.idTurno === +p[1]; }).map(conTipo);
      if (!lista.length) error(404, 'Default de turno no encontrado');
      return ok('defaultTurnos', lista);
    }],
    ['POST', /^\/defaultturno\/create$/, function (p, d) {
      requerido(d, ['idTurno', 'horaInicio', 'idTipoBloque', 'horaFin']);
      validarDefault(+d.idTurno, +d.idTipoBloque, d.horaInicio, d.horaFin);
      if (db.defaults.some(function (x) { return x.idTurno === +d.idTurno && x.idTipoBloque === +d.idTipoBloque; })) {
        error(400, 'El default del turno ya existe o algun dato no es valido');
      }
      var nuevo = { idTurno: +d.idTurno, idTipoBloque: +d.idTipoBloque, horaInicio: hhmmss(d.horaInicio), horaFin: hhmmss(d.horaFin), activo: +d.activo };
      db.defaults.push(nuevo);
      return ok('defaultTurno', nuevo);
    }],
    ['PUT', /^\/defaultturno\/(\d+)\/(\d+)$/, function (p, d) {
      var x = db.defaults.find(function (y) { return y.idTurno === +p[1] && y.idTipoBloque === +p[2]; });
      if (!x) error(404, 'Default de turno no encontrado');
      validarDefault(+p[1], +p[2], d.horaInicio, d.horaFin);
      $.extend(x, { horaInicio: hhmmss(d.horaInicio), horaFin: hhmmss(d.horaFin), activo: +d.activo });
      return ok('defaultTurno', x);
    }],
    ['DELETE', /^\/defaultturno\/(\d+)\/(\d+)$/, function (p) {
      var i = db.defaults.findIndex(function (y) { return y.idTurno === +p[1] && y.idTipoBloque === +p[2]; });
      if (i < 0) error(404, 'Default de turno no encontrado');
      db.defaults.splice(i, 1);
      return { message: 'Default de turno eliminado', status: 200 };
    }],

    ['GET', /^\/tipobloque\/?$/, function () { return ok('tiposBloque', db.tipos); }],
    ['POST', /^\/tipobloque\/create$/, function (p, d) {
      requerido(d, ['descripcion', 'color']);
      var nuevo = { idTipoBloque: nuevoId(), descripcion: mayus(d.descripcion), color: mayus(d.color), activo: +d.activo, pagable: d.pagable === undefined ? 1 : +d.pagable };
      db.tipos.push(nuevo);
      return ok('tipoBloque', nuevo);
    }],
    ['PUT', /^\/tipobloque\/(\d+)$/, function (p, d) {
      var t = tipo(p[1]);
      if (!t) error(404, 'Tipo de bloque no encontrado');
      $.extend(t, { descripcion: mayus(d.descripcion), color: mayus(d.color), activo: +d.activo });
      if (d.pagable !== undefined) t.pagable = +d.pagable;
      return ok('tipoBloque', t);
    }],
    ['DELETE', /^\/tipobloque\/(\d+)$/, function (p) {
      var id = +p[1];
      if (db.defaults.some(function (x) { return x.idTipoBloque === id; }) || db.horarios.some(function (x) { return x.idTipoBloque === id; })) {
        error(400, 'No se puede eliminar el tipo de bloque, esta siendo utilizado');
      }
      db.tipos = db.tipos.filter(function (t) { return t.idTipoBloque !== id; });
      return { message: 'Tipo de bloque eliminado', status: 200 };
    }],

    ['GET', /^\/profesores\/nuevo\/(.+)$/, function (p) {
      // Igual que el LIKE de MySQL: sin distinguir mayúsculas ni acentos
      var sinAcentos = function (t) { return mayus(t).normalize('NFD').replace(/[̀-ͯ]/g, ''); };
      var texto = sinAcentos(decodeURIComponent(p[1]));
      var lista = db.empleados.filter(function (e) {
        return sinAcentos(nombreCompleto(e)).indexOf(texto) >= 0 || sinAcentos(e.nombre + ' ' + e.primerApellido).indexOf(texto) >= 0 || String(e.uid).indexOf(texto) >= 0;
      }).map(function (e) { return { uid: e.uid, nombre: e.nombre, primerApellido: e.primerApellido, segundoApellido: e.segundoApellido, secuencia: 1 }; });
      if (!lista.length) return { message: 'No se encontraron personas.', status: 200 };
      return ok('profesores', lista);
    }],

    ['GET', /^\/horarioprofesor\/(\d+)$/, function (p) {
      var lista = db.horarios.filter(function (h) { return h.uid === +p[1]; }).map(conTipo);
      if (!lista.length) error(404, 'Horario del profesor no encontrado');
      return ok('horariosProfesor', lista);
    }],
    ['POST', /^\/horarioprofesor\/create$/, function (p, d) {
      requerido(d, ['uid', 'fechaInicio', 'fechaFin', 'diaSemana', 'horaInicio', 'horaFin', 'idTipoBloque']);
      var dia = EP.diaNombre(d.diaSemana);
      validarHorario(+d.uid, dia, d, null);
      var max = db.horarios.filter(function (h) { return h.uid === +d.uid; }).reduce(function (m, h) { return Math.max(m, h.secuencia); }, 0);
      var nuevo = { uid: +d.uid, secuencia: max + 1, fechaInicio: d.fechaInicio, fechaFin: d.fechaFin, diaSemana: dia, horaInicio: hhmmss(d.horaInicio), horaFin: hhmmss(d.horaFin), idTipoBloque: +d.idTipoBloque };
      db.horarios.push(nuevo);
      return ok('horarioProfesor', conTipo(nuevo));
    }],
    ['PUT', /^\/horarioprofesor\/(\d+)\/(\d+)$/, function (p, d) {
      var h = db.horarios.find(function (x) { return x.uid === +p[1] && x.secuencia === +p[2]; });
      if (!h) error(404, 'Horario del profesor no encontrado');
      var dia = EP.diaNombre(d.diaSemana);
      validarHorario(+p[1], dia, d, +p[2]);
      $.extend(h, { fechaInicio: d.fechaInicio, fechaFin: d.fechaFin, diaSemana: dia, horaInicio: hhmmss(d.horaInicio), horaFin: hhmmss(d.horaFin), idTipoBloque: +d.idTipoBloque });
      return ok('horarioProfesor', conTipo(h));
    }],
    ['DELETE', /^\/horarioprofesor\/(\d+)\/(\d+)$/, function (p) {
      var i = db.horarios.findIndex(function (x) { return x.uid === +p[1] && x.secuencia === +p[2]; });
      if (i < 0) error(404, 'Horario del profesor no encontrado');
      db.horarios.splice(i, 1);
      return { message: 'Horario del profesor eliminado', status: 200 };
    }],

    ['GET', /^\/impuestos$/, function () { return ok('impuestos', db.impuestos); }],
    ['POST', /^\/impuestos\/create$/, function (p, d) {
      requerido(d, ['descripcion']);
      var nuevo = { idImpuesto: nuevoId(), descripcion: mayus(d.descripcion), porcentaje: +(d.porcentaje || 0), esRetencion: +(d.esRetencion || 0) };
      db.impuestos.push(nuevo);
      return ok('$impuestos', nuevo);
    }],
    ['PUT', /^\/impuestos\/(\d+)$/, function (p, d) {
      var i = db.impuestos.find(function (x) { return x.idImpuesto === +p[1]; });
      if (!i) error(404, 'Impuesto no encontrado');
      $.extend(i, { descripcion: mayus(d.descripcion), porcentaje: +(d.porcentaje || 0), esRetencion: +(d.esRetencion || 0) });
      return ok('Impuesto', i);
    }],
    ['DELETE', /^\/impuestos\/(\d+)$/, function (p) {
      if (db.regimenImpuestos.some(function (r) { return r.idImpuesto === +p[1] && r.activo; })) error(400, 'El impuesto esta asignado a un regimen fiscal');
      db.impuestos = db.impuestos.filter(function (x) { return x.idImpuesto !== +p[1]; });
      return { message: 'Impuesto eliminado', status: 200 };
    }],

    ['GET', /^\/regimenesfiscales$/, function () {
      return ok('regimenesFiscales', db.regimenes.map(function (r) {
        return $.extend({}, r, { empleados: db.empleados.filter(function (e) { return e.idRegimenFiscalNomina === r.idRegimenFiscal; }).length });
      }));
    }],
    ['GET', /^\/regimenesfiscales\/sinregimen$/, function () {
      return ok('empleados', db.empleados.filter(function (e) { return !e.idRegimenFiscalNomina; }));
    }],
    ['POST', /^\/regimenesfiscales\/create$/, function (p, d) {
      requerido(d, ['descripcion']);
      var desc = mayus(d.descripcion);
      if (db.regimenes.some(function (r) { return r.descripcion === desc; })) error(400, 'Ya existe el regimen fiscal ' + desc);
      var nuevo = { idRegimenFiscal: nuevoId(), descripcion: desc, activo: 1 };
      db.regimenes.push(nuevo);
      return ok('regimenFiscal', nuevo);
    }],
    ['GET', /^\/regimenesfiscales\/(\d+)\/costos$/, function (p) {
      return ok('costosHora', db.costos.filter(function (c) { return c.idRegimenFiscal === +p[1]; })
        .sort(function (a, b) { return b.vigenciaInicio.localeCompare(a.vigenciaInicio); }));
    }],
    ['POST', /^\/regimenesfiscales\/(\d+)\/costos$/, function (p, d) {
      requerido(d, ['costoHora', 'vigenciaInicio']);
      var vigente = db.costos.find(function (c) { return c.idRegimenFiscal === +p[1] && !c.vigenciaFin; });
      if (vigente && d.vigenciaInicio <= vigente.vigenciaInicio) error(400, 'La nueva vigencia debe iniciar despues del ' + EP.fecha(vigente.vigenciaInicio));
      if (vigente) {
        var antes = new Date(fechaUTC(d.vigenciaInicio) - 86400000);
        vigente.vigenciaFin = antes.toISOString().substr(0, 10);
      }
      var nuevo = { idCostoHora: nuevoId(), idRegimenFiscal: +p[1], costoHora: +d.costoHora, vigenciaInicio: d.vigenciaInicio, vigenciaFin: null };
      db.costos.push(nuevo);
      return ok('costoHora', nuevo);
    }],
    ['GET', /^\/regimenesfiscales\/(\d+)\/impuestos$/, function (p) {
      return ok('impuestos', db.regimenImpuestos.filter(function (r) { return r.idRegimenFiscal === +p[1] && r.activo; }).map(function (r) {
        var i = db.impuestos.find(function (x) { return x.idImpuesto === r.idImpuesto; }) || {};
        return { idRegimenFiscalImpuesto: r.idRegimenFiscalImpuesto, idImpuesto: r.idImpuesto, descripcion: i.descripcion, porcentaje: i.porcentaje, esRetencion: i.esRetencion };
      }));
    }],
    ['POST', /^\/regimenesfiscales\/(\d+)\/impuestos$/, function (p, d) {
      requerido(d, ['idImpuesto']);
      var r = db.regimenImpuestos.find(function (x) { return x.idRegimenFiscal === +p[1] && x.idImpuesto === +d.idImpuesto; });
      if (r && r.activo) error(400, 'El impuesto ya esta asignado a este regimen');
      if (r) r.activo = 1;
      else db.regimenImpuestos.push(r = { idRegimenFiscalImpuesto: nuevoId(), idRegimenFiscal: +p[1], idImpuesto: +d.idImpuesto, activo: 1 });
      return ok('regimenFiscalImpuesto', r);
    }],
    ['DELETE', /^\/regimenesfiscales\/(\d+)\/impuestos\/(\d+)$/, function (p) {
      var r = db.regimenImpuestos.find(function (x) { return x.idRegimenFiscal === +p[1] && x.idImpuesto === +p[2] && x.activo; });
      if (!r) error(404, 'El impuesto no esta asignado a este regimen');
      r.activo = 0;
      return { message: 'Impuesto quitado del regimen', status: 200 };
    }],

    ['GET', /^\/calendariopago(\?pendientes=1)?$/, function (p) {
      return ok('calendariosPago', db.calendarios.filter(function (c) { return !p[1] || !c.procesado; }).map(conTurno)
        .sort(function (a, b) { return a.fechaInicio.localeCompare(b.fechaInicio) || a.idTurno - b.idTurno; }));
    }],
    ['GET', /^\/calendariopago\/siguiente$/, function () {
      var c = db.calendarios.filter(function (x) { return !x.procesado; })
        .sort(function (a, b) { return a.fechaInicio.localeCompare(b.fechaInicio) || a.idTurno - b.idTurno; })[0];
      if (!c) error(404, 'No hay calendarios de pago por procesar');
      return ok('calendarioPago', conTurno(c));
    }],
    ['POST', /^\/calendariopago\/create$/, function (p, d) {
      requerido(d, ['idTurno', 'fechaInicio', 'fechaFin']);
      validarCalendario(+d.idTurno, d.fechaInicio, d.fechaFin, null);
      var nuevo = { idCalendarioPago: nuevoId(), idTurno: +d.idTurno, fechaInicio: d.fechaInicio, fechaFin: d.fechaFin, procesado: 0, fechaCreacion: new Date().toISOString() };
      db.calendarios.push(nuevo);
      return ok('calendarioPago', nuevo);
    }],
    ['PUT', /^\/calendariopago\/(\d+)$/, function (p, d) {
      var c = db.calendarios.find(function (x) { return x.idCalendarioPago === +p[1]; });
      if (!c) error(404, 'Calendario de pago no encontrado');
      if (c.procesado) error(400, 'El calendario de pago ya fue procesado y no se puede modificar');
      var idTurno = d.idTurno ? +d.idTurno : c.idTurno;
      validarCalendario(idTurno, d.fechaInicio, d.fechaFin, c.idCalendarioPago);
      $.extend(c, { idTurno: idTurno, fechaInicio: d.fechaInicio, fechaFin: d.fechaFin });
      return ok('calendarioPago', c);
    }],
    ['DELETE', /^\/calendariopago\/(\d+)$/, function (p) {
      var c = db.calendarios.find(function (x) { return x.idCalendarioPago === +p[1]; });
      if (!c) error(404, 'Calendario de pago no encontrado');
      if (c.procesado) error(400, 'El calendario de pago ya fue procesado y no se puede eliminar');
      db.calendarios = db.calendarios.filter(function (x) { return x !== c; });
      return { message: 'Calendario de pago eliminado', status: 200 };
    }],

    ['GET', /^\/pagos\/concentrado\/(\d+)$/, function (p) {
      var c = db.calendarios.find(function (x) { return x.idCalendarioPago === +p[1]; });
      if (!c) error(404, 'Calendario de pago no encontrado');
      if (c.procesado) {
        return ok('concentrado', db.pagos.filter(function (x) { return x.idCalendarioPago === c.idCalendarioPago; }).map(filaConcentrado));
      }
      var calculo = calcular(c);
      return { concentrado: copia(calculo.pagos.map(filaConcentrado)), omitidos: calculo.omitidos, status: 200 };
    }],
    ['POST', /^\/pagos\/procesar\/(\d+)$/, function (p) {
      var c = db.calendarios.find(function (x) { return x.idCalendarioPago === +p[1]; });
      if (!c) error(404, 'Calendario de pago no encontrado');
      if (c.procesado) error(400, 'El calendario de pago ya fue procesado');
      var calculo = calcular(c);
      if (!calculo.pagos.length) error(400, 'No hay empleados con horas pagables, regimen fiscal y costo por hora en este calendario');
      calculo.pagos.forEach(function (pago) { db.pagos.push($.extend({ idCalendarioPago: c.idCalendarioPago }, pago)); });
      c.procesado = 1;
      return { message: 'Calendario de pago procesado', pagos: calculo.pagos.length, omitidos: calculo.omitidos, status: 200 };
    }]
  ];

  function conTurno(c) { return $.extend(copia(c), { turno: copia(turno(c.idTurno) || null) }); }

  function validarDefault(idTurno, idTipo, inicio, fin) {
    if (!inicio || !fin || fin <= inicio) error(400, 'Error en la validacion de los datos', { horaFin: ['La hora fin debe ser posterior a la hora inicio.'] });
    var detalles = db.detalles.filter(function (d) { return d.idTurno === idTurno; });
    if (!detalles.length) error(400, 'El turno no tiene dias definidos en el detalle de turno');
    detalles.forEach(function (d) {
      if (EP.hora(inicio) < EP.hora(d.horaInicio) || EP.hora(fin) > EP.hora(d.horaFin)) {
        error(400, 'El bloque debe quedar dentro del horario del turno (' + EP.diaNombre(d.diaSemana) + ' ' + EP.hora(d.horaInicio) + ' - ' + EP.hora(d.horaFin) + ')');
      }
    });
    var choque = db.defaults.find(function (x) { return x.idTurno === idTurno && x.idTipoBloque !== idTipo && EP.seEmpalman(inicio, fin, x.horaInicio, x.horaFin); });
    if (choque) error(400, 'El bloque se empalma con otro default del turno (' + EP.hora(choque.horaInicio) + ' - ' + EP.hora(choque.horaFin) + ')');
  }

  function validarHorario(uid, dia, d, excluirSecuencia) {
    if (!dia) error(400, 'El dia de la semana no es valido');
    if (d.horaFin <= d.horaInicio) error(400, 'Error en la validacion de los datos', { horaFin: ['La hora fin debe ser posterior a la hora inicio.'] });
    var choque = db.horarios.find(function (h) {
      return h.uid === uid && h.secuencia !== excluirSecuencia && EP.diaNombre(h.diaSemana) === dia
        && h.fechaInicio <= d.fechaFin && h.fechaFin >= d.fechaInicio && EP.seEmpalman(d.horaInicio, d.horaFin, h.horaInicio, h.horaFin);
    });
    if (choque) {
      var t = tipo(choque.idTipoBloque);
      error(400, 'Se traslapa con ' + (t ? t.descripcion : 'otra actividad') + ' del ' + dia + ' (' + EP.hora(choque.horaInicio) + ' - ' + EP.hora(choque.horaFin) + ')');
    }
  }

  function validarCalendario(idTurno, inicio, fin, excluir) {
    if (!inicio || !fin || fin <= inicio) error(400, 'Error en la validacion de los datos', { fechaFin: ['La fecha fin debe ser posterior a la fecha inicio.'] });
    var choque = db.calendarios.find(function (c) { return c.idCalendarioPago !== excluir && c.idTurno === idTurno && c.fechaInicio <= fin && c.fechaFin >= inicio; });
    if (choque) error(400, 'Se empalma con el calendario del ' + EP.fecha(choque.fechaInicio) + ' al ' + EP.fecha(choque.fechaFin) + ' del mismo turno');
  }

  EP.api = function (metodo, ruta, datos, opciones) {
    opciones = opciones || {};
    var diferido = $.Deferred();
    setTimeout(function () {
      for (var i = 0; i < rutas.length; i++) {
        var r = rutas[i];
        var coincide = r[0] === metodo && ruta.match(r[1]);
        if (!coincide) continue;
        try {
          var respuesta = r[2](coincide, datos || {});
          guardar();
          diferido.resolve(copia(respuesta));
        } catch (xhr) {
          if (!xhr || !xhr.status) {
            window.console && console.error(xhr);
            xhr = { status: 500, responseJSON: { message: 'Error interno del demo: ' + (xhr && xhr.message ? xhr.message : xhr), status: 500 } };
          }
          if (opciones.vacioEn404 && xhr.status === 404) diferido.resolve(null);
          else diferido.reject(EP.mensajeError(xhr), xhr);
        }
        return;
      }
      var noExiste = { status: 404, responseJSON: { message: 'Ruta no simulada en el demo: ' + metodo + ' ' + ruta, status: 404 } };
      diferido.reject(EP.mensajeError(noExiste), noExiste);
    }, 120);
    return diferido.promise();
  };

  EP.demo = {
    reiniciar: function () {
      try { window.localStorage.removeItem(CLAVE); } catch (e) { /* nada */ }
      window.location.reload();
    }
  };

  // Aviso visible de que es una demostración
  $(function () {
    var $pantalla = $('.siaweb-ep').first();
    var $aviso = $('<div class="info-box neutral mb-4" role="note"></div>').html(EP.icono('info', 18)
      + '<span><strong>Modo demostración.</strong> Los datos son de ejemplo y los cambios solo se guardan en este navegador. '
      + '<a href="index.html">Volver al menú</a> · <a href="#" data-demo-reiniciar>Reiniciar datos</a></span>');
    $pantalla.prepend($aviso);
    $aviso.on('click', '[data-demo-reiniciar]', function (e) {
      e.preventDefault();
      if (window.confirm('¿Borrar los cambios y volver a los datos de ejemplo?')) EP.demo.reiniciar();
    });
  });
})(window, jQuery);
