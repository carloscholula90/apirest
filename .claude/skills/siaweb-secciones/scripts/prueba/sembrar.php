<?php
// Crea prueba.sqlite con datos ficticios para Oferta de asignaturas. Ajusta tablas y filas a la pantalla que pruebes.
$archivo = __DIR__.'/prueba.sqlite';
@unlink($archivo);
$db = new PDO('sqlite:'.$archivo);
$db->exec(<<<'SQL'
CREATE TABLE nivel (idNivel INT, descripcion TEXT, alias TEXT, imagen TEXT, activo INT, fechaAlta TEXT, fechaModificacion TEXT);
INSERT INTO nivel VALUES (5,'LICENCIATURA'||char(13),NULL,NULL,1,NULL,NULL),(6,'MAESTRIA',NULL,NULL,1,NULL,NULL);
CREATE TABLE periodo (idNivel INT, idPeriodo INT, descripcion TEXT, activo INT, inscripciones INT, cerrado INT, fechaInicio TEXT, fechaTermino TEXT, inmediato INT, logoGob TEXT, fechaAlta TEXT, fechaModificacion TEXT);
INSERT INTO periodo VALUES (5,109,'PRIMAVERA 2025',0,0,1,'2025-02-01','2025-06-30',1,NULL,NULL,NULL),(5,112,'OTOÑO 2026',1,1,0,'2026-08-03','2026-08-30',1,NULL,NULL,NULL);
CREATE TABLE carrera (idNivel INT, idCarrera INT, descripcion TEXT, diaInicioCargo INT, diaInicioRecargo TEXT, activo INT);
INSERT INTO carrera VALUES (5,6,'CIENCIAS DE LA COMUNICACION',1,'10',1),(5,4,'ARQUITECTURA',1,'10',1),(5,9,'CARRERA INACTIVA',1,'10',0),(6,3,'MAESTRIA EN EDUCACION',1,'10',1);
CREATE TABLE plan (idPlan INT, idNivel INT, idCarrera INT);
INSERT INTO plan VALUES (1,5,6),(2,5,4),(3,5,9),(4,6,3);
CREATE TABLE turno (idTurno INT, descripcion TEXT, letra TEXT, parciales INT);
INSERT INTO turno VALUES (1,'MATUTINO'||char(13),'M',3),(2,'VESPERTINO'||char(13),'V',3),(3,'SABATINO'||char(13),'S',3);
CREATE TABLE formato (idFormato INT, nombre TEXT, ruta TEXT, "default" INT);
INSERT INTO formato VALUES (1,'ACTA ORDINARIA',NULL,1),(2,'ACTA EXTRAORDINARIA',NULL,0);
CREATE TABLE duracionSesion (idNivel INT, idTurno INT, duracion INT);
INSERT INTO duracionSesion VALUES (5,1,50),(5,2,45);
CREATE TABLE configuracion (id_campo INT, valor TEXT, descripcion TEXT);
INSERT INTO configuracion VALUES (1,'1','OTRO'),(3,'secretario','SARA DIAZ'),(4,'presidente','NOMBRE QUE NO EXISTE'),(5,'supervisor','LUIS CANO VEGA');
CREATE TABLE persona (uid INT, nombre TEXT, primerApellido TEXT, segundoApellido TEXT);
INSERT INTO persona VALUES (101,'ANA','LOPEZ','RUIZ'),(102,'PEDRO','SOTO','MORA'),(103,'SARA','DIAZ','PEREZ'),(105,'LUIS','CANO','VEGA');
CREATE TABLE empleado (uid INT, secuencia INT, fechabaja TEXT);
INSERT INTO empleado VALUES (101,1,NULL),(102,1,NULL),(103,1,NULL),(105,1,NULL);
CREATE TABLE asignatura (idAsignatura TEXT, descripcion TEXT);
INSERT INTO asignatura VALUES ('LIAE05.I','INGLES I'),('LIAE41.II','PROBLEMAS SOCIOECONOMICOS DE MEXICO'),('LIAE18.III','TEORIA DE LA COMUNICACION'),('LIAE22.III','PRODUCCION AUDIOVISUAL'),('ADU-II6','INSTALACIONES'),('SAB1','ASIGNATURA SABATINA');
CREATE TABLE grupos (gruposec INTEGER PRIMARY KEY, idNivel INT, idPeriodo INT, idAsignatura TEXT, grupo TEXT, idTurno INT, uidProfesor INT, secuenciaProfesor INT, inscritos INT, capacidad INT, uidSecretario INT, uidPresidente INT, uidSupervisor INT, fechaIni TEXT, horaIni TEXT, horaFin TEXT, idFormato INT, logoSep TEXT, logoEscudo TEXT);
INSERT INTO grupos (gruposec,idNivel,idPeriodo,idAsignatura,grupo,idTurno,uidProfesor,secuenciaProfesor,inscritos,capacidad,uidSecretario,uidPresidente,uidSupervisor,fechaIni,horaIni,horaFin,idFormato) VALUES
 (1,5,112,'LIAE05.I','06M1A',1,101,1,28,40,102,103,105,'2026-12-14','09:00:00','11:00:00',1),
 (2,5,112,'LIAE41.II','06V1A',2,102,1,30,40,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
 (3,5,112,'LIAE18.III','06M3A',1,NULL,NULL,24,35,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
 (4,5,112,'LIAE22.III','06V3A',2,NULL,NULL,0,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
 (5,5,112,'SAB1','06S2A',3,NULL,NULL,5,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
 (6,5,112,'ADU-II6','04M2A',1,NULL,NULL,10,40,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
CREATE TABLE horarios (secHorarios INTEGER PRIMARY KEY AUTOINCREMENT, grupoSec INT, fechaIni TEXT, fechaFin TEXT, day INT, impartido INT);
INSERT INTO horarios (grupoSec,fechaIni,fechaFin,day,impartido) VALUES (1,'2026-08-03 07:00:00','2026-08-03 08:40:00',2,1),(1,'2026-08-10 07:00:00','2026-08-10 08:40:00',2,0);
CREATE TABLE diasFestivos (fechaFestivo TEXT, descripcion TEXT);
INSERT INTO diasFestivos VALUES ('2026-08-17','FESTIVO DE PRUEBA');
CREATE TABLE detasignatura (idAsignatura TEXT, idNivel INT, idCarrera INT);
SQL);
echo 'Base lista: '.$archivo.PHP_EOL;
