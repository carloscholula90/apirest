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

  var modalBloque = new bootstrap.Modal('#modal-bloque');
  var $zona = $('#zona-horario');

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
    return { inicio: $('#vigencia-inicio').val(), fin: $('#vigencia-fin').val() };
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

      var $sel = $('#sel-turno').empty();
      estado.turnos.forEach(function (t) {
        $sel.append($('<option>').val(t.idTurno).text(t.descripcion + ' (' + t.inicio + ' – ' + t.fin + ')'));
      });
      if (estado.turnos.length) {
        $sel.append($('<option>').val(TODOS).text('TODOS (consulta, sin recesos)'));
        estado.idTurno = estado.turnos[0].idTurno;
        $sel.val(estado.idTurno);
      } else {
        EP.mostrarAlerta($('#alerta-general'), 'No hay turnos con días configurados. Defínelos primero en Configuración de turnos y bloques.');
      }
    });

    var tipos = EP.api('GET', '/tipobloque').done(function (r) {
      estado.tipos = r.tiposBloque || [];
    });

    var defaults = EP.api('GET', '/defaultturno', null, { vacioEn404: true }).done(function (r) {
      estado.defaults = (r && r.defaultTurnos) || [];
    });

    return $.when(turnos, tipos, defaults).fail(function (mensaje) {
      EP.mostrarAlerta($('#alerta-general'), 'No se pudieron cargar los catálogos. ' + mensaje);
    });
  }

  function cargarHorario() {
    if (!estado.profesor) return $.Deferred().resolve().promise();
    $zona.html('<div class="table-empty"><span class="spinner-border spinner-border-sm" role="status"></span><span>Cargando horario…</span></div>');
    return EP.api('GET', '/horarioprofesor/' + estado.profesor.uid, null, { vacioEn404: true }).done(function (r) {
      estado.horario = (r && r.horariosProfesor) || [];
    }).fail(function (mensaje) {
      estado.horario = [];
      EP.mostrarAlerta($('#alerta-general'), 'No se pudo cargar el horario del profesor. ' + mensaje);
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
    $('#leyenda').html('<span class="rlabel me-1">Tipos de bloque</span>' + items.join(''));
  }

  function pintarTodo() {
    var turno = turnoActual();
    var vista = vistaActual();
    pintarLeyenda();

    $('#tag-turno').text(turno ? turno.descripcion : 'TODOS');
    $('#turno-horario').text(turno ? turno.inicio + ' – ' + turno.fin : 'Todos los turnos');
    $('#turno-dias').html(EP.chipsDias(vista.dias.map(function (d) { return d.numero; })));
    $('#aviso-todos').toggleClass('d-none', !!turno);
    $('#btn-imprimir').prop('disabled', !estado.profesor);

    var $aviso = $('#aviso-ocultos');
    if (!estado.profesor) {
      $zona.html(EP.estadoVacio('buscar', 'Selecciona un profesor', 'Búscalo por nombre o UID para ver y capturar su horario.'));
      $aviso.addClass('d-none');
      $('#resumen-horas').empty();
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
    $('#resumen-horas').html(detalle.length
      ? '<div class="leyenda"><span class="rlabel me-1">Horas por semana' + (turno ? ' en ' + EP.escapar(turno.descripcion) : '') + '</span>' + detalle.join('') + '</div>'
      : '<p class="ep-ayuda mb-0">Sin actividades capturadas' + (turno ? ' en este turno' : '') + '.</p>');
    $('#total-horas').text(textoHoras(total));
  }

  // ---------- Búsqueda de profesor ----------

  var temporizador = null;
  var $buscar = $('#buscar-profesor');
  var $resultados = $('#resultados-profesor');

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
    $('#profesor-uid').text(estado.profesor.uid);
    $('#profesor-nombre').text(estado.profesor.nombre);
    $('#datos-profesor').removeClass('d-none');
    EP.limpiarAlerta($('#alerta-general'));
    cargarHorario().done(function () {
      // Propone la vigencia más reciente del profesor; si no tiene horario, el semestre a partir de hoy
      var ultima = estado.horario.slice().sort(function (a, b) { return String(b.fechaFin).localeCompare(String(a.fechaFin)); })[0];
      $('#vigencia-inicio').val(ultima ? String(ultima.fechaInicio).substr(0, 10) : EP.hoyIso());
      $('#vigencia-fin').val(ultima ? String(ultima.fechaFin).substr(0, 10) : EP.hoyIso(180));
    }).always(pintarTodo);
  });

  $(document).on('click', function (e) {
    if (!$(e.target).closest('.search-wrap').length) cerrarResultados();
  });

  // ---------- Filtros ----------

  $('#sel-turno').on('change', function () {
    var valor = $(this).val();
    estado.idTurno = valor === TODOS ? TODOS : Number(valor);
    pintarTodo();
  });

  $('#vigencia-inicio, #vigencia-fin').on('change', pintarTodo);

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
    var $ini = $('#bloque-inicio').empty();
    var opciones = modulos.map(function (m) { return m.ini; });
    if (valor !== undefined && opciones.indexOf(valor) < 0) opciones.push(valor);
    opciones.sort(function (a, b) { return a - b; }).forEach(function (m) {
      $ini.append($('<option>').val(m).text(EP.horaDesdeMinutos(m)));
    });
    $ini.val(valor);
  }

  // La hora fin avanza de bloque en bloque y se detiene en el siguiente receso
  function llenarFin(modulos, inicio, valor) {
    var $fin = $('#bloque-fin').empty();
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
    var minutos = Number($('#bloque-fin').val()) - Number($('#bloque-inicio').val());
    $('#bloque-duracion').text(minutos > 0
      ? minutos + ' minutos · cuenta como ' + textoHoras(minutos).replace(' h', minutos === MODULO ? ' hora' : ' horas')
      : '—');
  }

  function abrirBloque(numeroDia, registro, indiceFila) {
    var turno = turnoActual();
    var v = vigencia();
    if (!turno) return;
    if (!registro && (!v.inicio || !v.fin || v.fin < v.inicio)) {
      return EP.mostrarAlerta($('#alerta-general'), 'Captura una vigencia válida (desde / hasta) antes de agregar bloques.');
    }

    estado.bloque = { dia: numeroDia, registro: registro };
    var excluidos = tiposDefault();
    var $tipo = $('#bloque-tipo').empty();
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

    $('#bloque-profesor').text(estado.profesor.nombre);
    $('#bloque-dia').text(EP.DIAS[numeroDia]);
    $('#bloque-vigencia').text(registro
      ? EP.fecha(registro.fechaInicio) + ' – ' + EP.fecha(registro.fechaFin)
      : EP.fecha(v.inicio) + ' – ' + EP.fecha(v.fin));
    $('#btn-eliminar-bloque').toggleClass('d-none', !registro);
    $('#form-bloque .ep-texto-titulo').text(registro ? 'Editar bloque' : 'Nuevo bloque');
    EP.limpiarAlerta($('#form-bloque .ep-error'));
    modalBloque.show();
  }

  $('#bloque-inicio').on('change', function () {
    var turno = turnoActual();
    var b = estado.bloque;
    if (!turno || !b) return;
    var inicio = Number($(this).val());
    llenarFin(modulosDelDia(turno, b.dia), inicio, inicio + MODULO);
  });

  $('#bloque-fin').on('change', pintarDuracion);

  $zona.on('click', 'button.hcell', function () {
    abrirBloque(Number($(this).data('dia')), null, Number($(this).data('fila')));
  });

  $zona.on('click', 'button.hblk', function () {
    var secuencia = Number($(this).data('secuencia'));
    var registro = estado.horario.find(function (h) { return Number(h.secuencia) === secuencia; });
    if (registro) abrirBloque(EP.diaNumero(registro.diaSemana), registro);
  });

  $('#form-bloque').on('submit', function (e) {
    e.preventDefault();
    var $error = $(this).find('.ep-error');
    var b = estado.bloque;
    var turno = turnoActual();
    var rango = rangoDia(turno, b.dia);
    var inicio = EP.horaDesdeMinutos(Number($('#bloque-inicio').val()));
    var fin = EP.horaDesdeMinutos(Number($('#bloque-fin').val()));
    var v = vigencia();
    var datos = {
      fechaInicio: b.registro ? String(b.registro.fechaInicio).substr(0, 10) : v.inicio,
      fechaFin: b.registro ? String(b.registro.fechaFin).substr(0, 10) : v.fin,
      diaSemana: EP.DIAS[b.dia],
      horaInicio: inicio,
      horaFin: fin,
      idTipoBloque: Number($('#bloque-tipo').val())
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
        EP.marcarGuardado($('#estado-guardado'));
      });
    }).fail(function (mensaje) {
      EP.mostrarAlerta($error, mensaje);
    }).always(function () {
      EP.bloquearBoton($boton, false);
    });
  });

  $('#btn-eliminar-bloque').on('click', function () {
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

  $('#btn-imprimir').on('click', function () {
    if (!estado.profesor) return;
    var v = vigencia();
    var res = construirCuadricula(vistaGlobal(), { editable: false });
    var total = 0;
    var horas = Object.keys(res.minutosPorTipo).map(function (id) {
      var t = tipoPorId(id) || { descripcion: 'TIPO ' + id };
      if (Number(t.pagable) !== 0) total += res.minutosPorTipo[id];
      return EP.escapar($.trim(t.descripcion)) + ': ' + textoHoras(res.minutosPorTipo[id]);
    });
    $('#impresion').html(
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
  $('#vigencia-inicio').val(EP.hoyIso());
  $('#vigencia-fin').val(EP.hoyIso(180));
  cargarCatalogos().always(pintarTodo);
});
