-- Aplica saldos anteriores negativos a los cargos pendientes por orden de nivel.
-- MySQL 8 / MariaDB 10.4+
-- Procesa todos los alumnos, exclusivamente en el periodo activo de su nivel.
-- No recibe parámetros:
-- CALL sp_aplicar_saldos_anteriores_negativos();

DELIMITER $$
DROP PROCEDURE IF EXISTS sp_aplicar_saldos_anteriores_negativos$$

CREATE PROCEDURE sp_aplicar_saldos_anteriores_negativos()
main: BEGIN
    DECLARE v_lock INT DEFAULT 0;
    DECLARE v_idFuente BIGINT;
    DECLARE v_idDestino BIGINT;
    DECLARE v_uid INT;
    DECLARE v_secuencia INT;
    DECLARE v_idPeriodo INT;
    DECLARE v_idNivel INT;
    DECLARE v_servicioFuente INT;
    DECLARE v_refFuente VARCHAR(255);
    DECLARE v_parcialidadFuente INT;
    DECLARE v_credito DECIMAL(15,2);
    DECLARE v_servicioDestino INT;
    DECLARE v_refDestino VARCHAR(255);
    DECLARE v_parcialidadDestino INT;
    DECLARE v_saldoDestino DECIMAL(15,2);
    DECLARE v_aplicado DECIMAL(15,2);
    DECLARE v_consecutivo INT;
    DECLARE v_identificador VARCHAR(100);
    DECLARE v_total DECIMAL(15,2) DEFAULT 0;
    DECLARE v_numAplicaciones INT DEFAULT 0;
    DECLARE v_numFuentes INT DEFAULT 0;
    DECLARE v_uidcajero INT DEFAULT 111111;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        IF v_lock = 1 THEN
            DO RELEASE_LOCK('sp_aplicar_saldos_anteriores_negativos');
        END IF;
        RESIGNAL;
    END;

    SELECT GET_LOCK('sp_aplicar_saldos_anteriores_negativos',10) INTO v_lock;
    IF COALESCE(v_lock,0) <> 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Ya existe otro proceso aplicando saldos anteriores';
    END IF;

    SET @origen = 'LARAVEL';
    SET @uidcajero = v_uidcajero;
    START TRANSACTION;

    DROP TEMPORARY TABLE IF EXISTS tmp_saldos_anteriores;
    CREATE TEMPORARY TABLE tmp_saldos_anteriores (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        uid INT NOT NULL,
        secuencia INT NOT NULL,
        idPeriodo INT NOT NULL,
        idNivel INT NOT NULL,
        servicioFuente INT NOT NULL,
        refFuente VARCHAR(255) NOT NULL,
        parcialidadFuente INT NULL,
        credito DECIMAL(15,2) NOT NULL
    ) ENGINE=InnoDB;

    INSERT INTO tmp_saldos_anteriores
        (uid,secuencia,idPeriodo,idNivel,servicioFuente,refFuente,
         parcialidadFuente,credito)
    SELECT edo.uid, edo.secuencia, edo.idPeriodo, al.idNivel,
           ct.idServicioTraspasoSaldos1, edo.referencia, edo.parcialidad,
           ROUND(ABS(SUM(CASE
               WHEN edo.tipomovto='C' THEN edo.importe
               WHEN edo.tipomovto='A' THEN -edo.importe ELSE 0 END)),2)
    FROM edocta edo
    INNER JOIN alumno al
       ON al.uid=edo.uid AND al.secuencia=edo.secuencia
    INNER JOIN periodo per
       ON per.idNivel=al.idNivel
      AND per.idPeriodo=edo.idPeriodo
      AND per.activo=1
    INNER JOIN configuracionTesoreria ct
       ON ct.idNivel=al.idNivel
      -- El saldo anterior se identifica por idServicio. Sus referencias
      -- históricas pueden iniciar con 000 y no representan el servicio.
      AND ct.idServicioTraspasoSaldos1=edo.idServicio
    WHERE edo.referencia IS NOT NULL
    GROUP BY edo.uid,edo.secuencia,edo.idPeriodo,al.idNivel,
             ct.idServicioTraspasoSaldos1,edo.referencia,edo.parcialidad
    HAVING ROUND(SUM(CASE
        WHEN edo.tipomovto='C' THEN edo.importe
        WHEN edo.tipomovto='A' THEN -edo.importe ELSE 0 END),2) < 0;

    DROP TEMPORARY TABLE IF EXISTS tmp_cargos_destino;
    CREATE TEMPORARY TABLE tmp_cargos_destino (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        idServicio INT NOT NULL,
        referencia VARCHAR(255) NOT NULL,
        parcialidad INT NULL,
        ordenCobro INT NOT NULL,
        saldo DECIMAL(15,2) NOT NULL
    ) ENGINE=InnoDB;

    WHILE EXISTS(SELECT 1 FROM tmp_saldos_anteriores) DO
        SELECT MIN(id) INTO v_idFuente FROM tmp_saldos_anteriores;
        SELECT uid,secuencia,idPeriodo,idNivel,servicioFuente,refFuente,
               parcialidadFuente,credito
          INTO v_uid,v_secuencia,v_idPeriodo,v_idNivel,v_servicioFuente,
               v_refFuente,v_parcialidadFuente,v_credito
        FROM tmp_saldos_anteriores WHERE id=v_idFuente;

        DELETE FROM tmp_cargos_destino;
        ALTER TABLE tmp_cargos_destino AUTO_INCREMENT=1;

        INSERT INTO tmp_cargos_destino
            (idServicio,referencia,parcialidad,ordenCobro,saldo)
        SELECT CAST(LEFT(TRIM(edo.referencia),3) AS UNSIGNED),
               edo.referencia,edo.parcialidad,
               COALESCE(ocs.orden,999999),
               ROUND(SUM(CASE
                   WHEN edo.tipomovto='C' THEN edo.importe
                   WHEN edo.tipomovto='A' THEN -edo.importe ELSE 0 END),2)
        FROM edocta edo
        LEFT JOIN ordenCobroServicio ocs
          ON ocs.idNivel=v_idNivel
         AND ocs.idServicio=CAST(LEFT(TRIM(edo.referencia),3) AS UNSIGNED)
        WHERE edo.uid=v_uid AND edo.secuencia=v_secuencia
          AND edo.idPeriodo=v_idPeriodo
          AND edo.referencia REGEXP '^[0-9]{3}'
          AND CAST(LEFT(TRIM(edo.referencia),3) AS UNSIGNED)<>v_servicioFuente
        GROUP BY edo.referencia,edo.parcialidad,ocs.orden
        HAVING ROUND(SUM(CASE
            WHEN edo.tipomovto='C' THEN edo.importe
            WHEN edo.tipomovto='A' THEN -edo.importe ELSE 0 END),2)>0
        ORDER BY COALESCE(ocs.orden,999999),
                 COALESCE(edo.parcialidad,999999),edo.referencia,
                 CAST(LEFT(TRIM(edo.referencia),3) AS UNSIGNED);

        SET v_identificador=CONCAT('CSA-',v_uid,'-',v_secuencia,'-',
                                    v_idPeriodo,'-',UUID());
        SET v_numFuentes=v_numFuentes+1;

        WHILE v_credito>0 AND EXISTS(SELECT 1 FROM tmp_cargos_destino) DO
            /*
             * El siguiente cargo se determina en cada aplicación mediante
             * ordenCobroServicio del nivel. Para órdenes iguales se utiliza
             * parcialidad y referencia. Los no configurados quedan al final.
             */
            SELECT id INTO v_idDestino
            FROM tmp_cargos_destino
            ORDER BY ordenCobro,
                     COALESCE(parcialidad,999999),
                     referencia,
                     idServicio
            LIMIT 1;
            SELECT idServicio,referencia,parcialidad,saldo
              INTO v_servicioDestino,v_refDestino,v_parcialidadDestino,
                   v_saldoDestino
            FROM tmp_cargos_destino WHERE id=v_idDestino;

            SET v_aplicado=ROUND(LEAST(v_credito,v_saldoDestino),2);
            IF v_aplicado>0 THEN
                SELECT COALESCE(MAX(consecutivo),0) INTO v_consecutivo
                FROM edocta
                WHERE uid=v_uid AND secuencia=v_secuencia
                  AND idPeriodo=v_idPeriodo FOR UPDATE;

                SET v_consecutivo=v_consecutivo+1;
                INSERT INTO edocta
                    (uid,secuencia,idServicio,consecutivo,importe,idPeriodo,
                     fechaMovto,FechaPago,idformaPago,cuatrodigitos,tipomovto,
                     folio,referencia,parcialidad,uidcajero,transaccion,
                     tipoOrigen,identificadorOrigen)
                VALUES
                    (v_uid,v_secuencia,v_servicioFuente,v_consecutivo,v_aplicado,
                     v_idPeriodo,NOW(),CURDATE(),NULL,NULL,'C',NULL,v_refFuente,
                     v_parcialidadFuente,v_uidcajero,v_identificador,
                     'COMPENSACION',v_identificador);

                SET v_consecutivo=v_consecutivo+1;
                INSERT INTO edocta
                    (uid,secuencia,idServicio,consecutivo,importe,idPeriodo,
                     fechaMovto,FechaPago,idformaPago,cuatrodigitos,tipomovto,
                     folio,referencia,parcialidad,uidcajero,transaccion,
                     tipoOrigen,identificadorOrigen)
                VALUES
                    (v_uid,v_secuencia,v_servicioDestino,v_consecutivo,v_aplicado,
                     v_idPeriodo,NOW(),CURDATE(),NULL,NULL,'A',NULL,v_refDestino,
                     v_parcialidadDestino,v_uidcajero,v_identificador,
                     'COMPENSACION',v_identificador);

                SET v_credito=ROUND(v_credito-v_aplicado,2);
                SET v_total=ROUND(v_total+v_aplicado,2);
                SET v_numAplicaciones=v_numAplicaciones+1;
            END IF;
            DELETE FROM tmp_cargos_destino WHERE id=v_idDestino;
        END WHILE;
        DELETE FROM tmp_saldos_anteriores WHERE id=v_idFuente;
    END WHILE;

    COMMIT;
    DO RELEASE_LOCK('sp_aplicar_saldos_anteriores_negativos');
    SET v_lock=0;

    SELECT v_numFuentes AS saldosAnterioresProcesados,
           v_numAplicaciones AS cargosImpactados,
           v_total AS totalAplicado;
END$$
DELIMITER ;
