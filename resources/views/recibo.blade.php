<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <style>
    @page {
      size: letter portrait;
      margin: 5mm 5mm 5mm 5mm;
    }

    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      font-size: 9pt;
    }

    .hoja {
      width: 100%;
      height: 100%;
      box-sizing: border-box;
      position: relative;
    }

    .mitad {
      position: relative;
      padding: 10px;
      height: 48%;
      box-sizing: border-box;
      overflow: hidden;
    }

    .contenido-recibo {
      position: relative;
      z-index: 10;
      background: transparent;
    }

    .recuadro {
      pointer-events: none;
      position: absolute;
      top: 10px;
      left: 50%;
      width: 98%;
      height: 97%;
      transform: translateX(-50%);
      border: 1px solid rgba(0, 0, 0, 0.7);
      border-radius: 10px;
      background-color: rgba(255, 255, 255, 0.1);
      box-sizing: border-box;
      z-index: 20;

      /* Logo como fondo abajo derecha dentro del marco */
      background-image: url('{{ public_path("images/logo.png") }}');
      background-repeat: no-repeat;
      background-position: right bottom 10px; /* 10px desde abajo */
      background-size: 120px auto;
      opacity: 1; /* no opaca el contenido */
    }

    /* Para controlar opacidad solo del logo sin afectar el marco */
    .recuadro::after {
      content: "";
      position: absolute;
      bottom: 10px;
      right: 10px;
      width: 110px;
      height: auto;
      max-height: 110px;
      background-image: url('{{ public_path("images/logo.png") }}');
      background-repeat: no-repeat;
      background-position: center center;
      background-size: contain;
      opacity: 0.1; /* logo tenue */
      pointer-events: none;
      z-index: 21;
    }

    .linea-punteada {
      position: absolute;
      top: 50%;
      left: 0;
      width: 100%;
      border-top: 1px dashed black;
      z-index: 30;
    }

    .marca-agua {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) rotate(-45deg);
      font-size: 90px;
      color: rgba(180, 180, 180, 0.2);
      font-weight: bold;
      z-index: 5;
      white-space: nowrap;
      pointer-events: none;
    }

    /* Encabezado */
    .encabezado {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      height: 110px;
      box-sizing: border-box;
      padding-left: 10px;
      padding-right: 10px;
    }

    .col-center {
      flex: 1;
      text-align: center;
      box-sizing: border-box;
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      padding-top: 0;
      margin: 0;
    }

    .col-right {
      flex: 0 0 110px;
      display: flex;
      align-items: flex-start;
      justify-content: flex-end;
      height: 100%;
      box-sizing: border-box;
      font-weight: bold;
      font-size: 16pt;
    }

    .titulos h2,
    .titulos{
      margin-top: 10;
      line-height: 1.0;
    }
.subtitulo {
  font-size: 10pt;
  margin-top: 4px;
  line-height: 1.3;
}

    .datos-recibo {
      margin-top: 8px;
      font-size: 10pt;
      line-height: 1.6;
      position: relative;
      z-index: 10;
    }

    .datos-recibo p {
      margin: 10px 0;
      margin-left: 30px; 
    }

    .datos-recibo p strong {
      display: inline-block;
      width: 140px;
    }
  </style>
</head>
<body>
  <div class="hoja">
    <div class="linea-punteada"></div>

    <!-- ORIGINAL -->
    <div class="mitad">
      <div class="contenido-recibo">
        <div class="encabezado">
          <div class="col-center">
            <div class="titulos">
              <h1>Universidad Alva Edison, A.C</h1>
              <h3><em>"El Lado Humano de la Educación"</em></h3>
              <div class="subtitulo">                
                Incorporado a la S.E.P. 21MSU1022U<br/>
                REG. FED. DE CONT. UAE 900901 1D5<br/>
                Av. Reforma No. 725 Centro Histórico Tel.: 22 22 32 37 93<br/>
                Puebla, Pue. C.P. 72000
              </div>
            </div>
          </div>
          <div class="col-right">
          </div>
        </div>
<br><br><br><br><br><br>
        <div class="datos-recibo">
          <p><strong>Recibo de:</strong> {{ $nombre ?? '_______________________' }}</p>
          <p><strong>La cantidad de $:</strong> {{ $cantidad ?? '_______________________' }}</p>
          <p><strong>Por concepto de:</strong> {{ $concepto ?? '_______________________' }}</p>
          <p><strong>Carrera:</strong> {{ $carrera ?? '_______________________' }}</p>
          <p><strong>Puebla, Pue. a:</strong> {{ $fecha ?? '_______________________' }}</p>
        </div>

        <div class="marca-agua">ORIGINAL</div>
      </div>
      <div class="recuadro"></div>
    </div>

    <!-- COPIA -->
    <div class="mitad">
      <div class="contenido-recibo">
        <div class="encabezado">
          <div class="col-center">
            <div class="titulos">
              <h1>Universidad Alva Edison, A.C</h1>
              <h3><em>"El Lado Humano de la Educación"</em></h3>
              <div class="subtitulo">                
                Incorporado a la S.E.P. 21MSU1022U<br/>
                REG. FED. DE CONT. UAE 900901 1D5<br/>
                Av. Reforma No. 725 Centro Histórico Tel.: 22 22 32 37 93<br/>
                Puebla, Pue. C.P. 72000
              </div>
            </div>
          </div>
          <div class="col-right">
          </div>
        </div>
<br><br><br><br><br><br>
        <div class="datos-recibo">
          <p><strong>Recibo de:</strong> {{ $nombre ?? '_______________________' }}</p>
          <p><strong>La cantidad de $:</strong> {{ $cantidad ?? '_______________________' }}</p>
          <p><strong>Por concepto de:</strong> {{ $concepto ?? '_______________________' }}</p>
          <p><strong>Carrera:</strong> {{ $carrera ?? '_______________________' }}</p>
          <p><strong>Puebla, Pue. a:</strong> {{ $fecha ?? '_______________________' }}</p>
        </div>

        <div class="marca-agua">COPIA</div>
      </div>
      <div class="recuadro"></div>
    </div>

    </div>
  </div>
</body>
</html>
