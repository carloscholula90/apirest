---
name: siaweb-secciones
description: Cómo crear o rediseñar pantallas de SIAWEB en /secciones (archivos PHP que incluye el layout, con jQuery, Bootstrap 5 y llamadas a apirest) siguiendo el sistema de diseño SIAWEB, y cómo probarlas antes de subirlas. Úsalo siempre que el usuario pida una pantalla, sección, prototipo o rediseño de siaweb.com.mx/secciones/..., quiera "conectar la pantalla a las APIs", ajustar su prototipo "a ese tipo de front" o te pase un archivo de /secciones, aunque no diga "front".
---

# Pantallas de /secciones (SIAWEB)

Cada pantalla de `https://siaweb.com.mx/secciones/<nombre>` es **un fragmento PHP** (`<nombre>.php`) que el layout de SIAWEB incluye. El layout ya pone menú lateral, barra superior, sesión, jQuery, Bootstrap 5 (bundle con `bootstrap.Modal`), Bootstrap Icons (`bi bi-...`) y la fuente Titillium Web. El usuario sube un solo archivo, así que la pantalla debe ser autosuficiente.

Ejemplo completo y probado: `frontend/oferta-asignaturas/oferta-asignaturas.php`. Léelo antes de escribir una pantalla nueva y copia su estructura.

## Estructura del archivo

Respeta este orden, que es el de las páginas reales:

1. **Includes del sistema**, tal cual:
   ```php
   <?php
   // Llamar al CRUD/UTILS
   include 'crud/utils.php';
   // Llamar al CRUD/GET
   include 'crud/get.php';
   ```
2. **Dirección de la API** en una sola variable, sobreescribible para pruebas:
   ```php
   $apiUrl = rtrim(getenv('SIAWEB_API_URL') ?: 'https://api.pruebas.siaweb.com.mx/api', '/');
   ```
3. **Catálogos fijos con cURL en PHP** (niveles, carreras, formatos…): una función que regrese `[]` si la API falla, para que la página cargue aunque falte un catálogo. Pásalos a JS con `json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)`.
4. Empieza el HTML con `<body>` y **no** pongas `<html>`, `<head>`, menú ni otra copia de jQuery o Bootstrap.
5. Si la página original tiene el botón Excel (`.list_btn_header`, `.button_reporte`, `<template id="excel">`, `reporte2()` y `js/crud/utils.js`), consérvalo igual. `reporte2()` genera el Excel en cada carga y la hace lenta; no lo quites sin preguntar.
6. **Estilos en un `<style>` dentro del archivo**, todo bajo un prefijo propio de la pantalla (por ejemplo `.ofa`). Usa también ids con ese prefijo, porque el layout puede tener ids genéricos como `#nivel`.
7. **JavaScript en un `<script>` al final**, dentro de una función autoejecutable, usando `$.ajax` para todo lo que no sea catálogo fijo.

## Consumo de la API

- Envuelve `$.ajax` en una función `api(metodo, ruta, datos)` que mande JSON (`contentType: 'application/json'`, `JSON.stringify`) y convierta errores en un mensaje legible. apirest responde errores como `{message, error, status}`, donde `error` puede ser un objeto de validación.
- Muestra los errores dentro de la pantalla con un componente de alerta, nunca con `alert()`.
- Particularidades conocidas de apirest: `GET /periodos/{idNivel}` responde con la clave `$periodos`; las descripciones de `nivel` y `turno` traen `\r` (usa `trim`); los catálogos pueden venir vacíos.
- Si un endpoint que necesitas no existe, usa el skill `siaweb-api` para crearlo en lugar de rodearlo desde el front. Si no puedes crearlo en ese momento, diseña la llamada con la forma de rutas de apirest (los datos que identifican el recurso en la ruta, como `GET /recurso/{anio}` o `DELETE /recurso/{fecha}`; los filtros opcionales en query string) y anota en la entrega la ruta exacta que falta, para que el back la cree igual.
- Antes de dar por hecha la pantalla, comprueba con `curl` qué rutas tiene hoy `api.pruebas.siaweb.com.mx`: suele tener una versión distinta a la del repositorio local. Contra ese servidor haz solo GET: un POST, PUT o DELETE cambia datos que usan otras personas, así que hazlo solo si el usuario lo autoriza en ese momento. Una ruta GET que escriba en la base (por ejemplo, `GET /diasfestivos/{year}` inserta festivos) no la llames para "probar".

## Sistema de diseño

La guía completa está en `C:\Users\hdzpi\Downloads\sistema-de-diseno-siaweb.md` y su checklist en `checklist-consistencia-modales.md`, en la misma carpeta. Léelos si vas a diseñar algo que no esté en el ejemplo. Lo esencial:

- **Color:** tokens del sistema como variables CSS bajo el prefijo de la pantalla, con su variante en `.dark` (Hope UI pone `.dark` en el body). El verde `--accent` es el único color de controles activos; nunca azul. Lo destructivo usa rojo `--danger`.
- **Filtros:** cada uno con etiqueta visible (mayúscula inicial, sin dos puntos), en cascada y sin IDs crudos.
- **Tabla:** encabezados en mayúsculas grises, sin columnas que repitan lo que ya dice un filtro, estado vacío con título y texto de ayuda.
- **Selección compartida:** un radio por fila y fila activa resaltada; las pestañas de captura trabajan sobre esa selección.
- **Acciones por fila:** menú ⋮ propio (posición fija, que no se salga de la pantalla) con Editar y Eliminar.
- **Modales:** ícono circular + título, una sola X, botón secundario outline y uno primario a la derecha. Agrégalos al `body` con JS (`appendTo('body')`) para que el layout no los recorte.
- **Reglas de interacción:** Eliminar siempre pide confirmación en un modal y explica qué se pierde. Editar reutiliza el formulario de Nuevo. Si el formulario está integrado en la página, el éxito se indica con "✓ Guardado" junto al botón.
- **Datos de ejemplo:** en MAYÚSCULAS cuando representan datos de la base.

## Estado y robustez

Dos defectos reales aparecieron al probar; evítalos desde el inicio:

- **Guarda en el estado de JS cada valor que el usuario captura** (en el evento `input`), no solo al guardar. El panel se repinta al cambiar de pestaña o filtrar, y lo capturado se perdía.
- **Las respuestas asíncronas no deben depender de la pestaña visible.** Valida contra el objeto cargado (por ejemplo, que siga siendo la misma acta) y contra si el usuario ya cambió ese campo. Usa un contador de petición solo para descartar cargas de detalle viejas.
- Repintar la tabla no debe repintar el detalle si la selección no cambió.
- Escapa todo texto que venga de la API antes de insertarlo como HTML.

## Probar antes de entregar

Usa los scripts de `scripts/prueba/`; ver `scripts/prueba/LEEME.md` para los pasos. Resumen:

1. Siembra una base SQLite desechable (`sembrar.php`, ajústalo a las tablas de tu pantalla) para no escribir en `siaweb_pruebas`.
2. Agrega temporalmente a `.claude/launch.json` dos servidores: la API (`router-api.php`, puerto 8000) y el layout simulado (`router-pagina.php`, puerto 8001). Inícialos con `preview_start`.
3. Abre `http://127.0.0.1:8001/secciones/<nombre>` y corre una batería en `javascript_tool`. Espera a que la página termine de cargar (`reporte2()` la retrasa) y espera cada resultado con un sondeo, porque el servidor de prueba atiende una petición a la vez.
4. Cubre: filtros, búsqueda, selección, cada pestaña, validaciones, guardar, editar, eliminar con confirmación y estados vacíos.
5. Al terminar, detén los servidores y quita las entradas de `launch.json`.

Este entorno no prueba el layout real (Hope UI, `crud/utils.js`, el botón Excel). Dilo al entregar.

## Entrega

- Deja el archivo en `frontend/<nombre>/<nombre>.php` y envíalo al usuario.
- Indica qué rutas de la API necesita y cuáles faltan en `api.pruebas`, qué ids cambiaron respecto a la versión anterior y qué no se pudo probar.
