<?php

namespace App\Http\Controllers\Api\escolar\Concerns;

trait ManejaHorarios
{
    // 1 es Domingo, 2 Lunes, ... 7 Sábado (mismo criterio que dtlTurno)
    private static $diasSemana = [
        1 => 'DOMINGO',
        2 => 'LUNES',
        3 => 'MARTES',
        4 => 'MIÉRCOLES',
        5 => 'JUEVES',
        6 => 'VIERNES',
        7 => 'SÁBADO',
    ];

    // Acepta el número (1-7) o el nombre del día, con o sin acento
    protected function diaNumero($dia)
    {
        $dia = trim((string) $dia);

        if (ctype_digit($dia))
            return isset(self::$diasSemana[(int) $dia]) ? (int) $dia : null;

        $dia = strtr(mb_strtoupper($dia, 'UTF-8'), ['É' => 'E', 'Á' => 'A']);

        foreach (self::$diasSemana as $numero => $nombre) {
            if (strtr($nombre, ['É' => 'E', 'Á' => 'A']) === $dia)
                return $numero;
        }

        return null;
    }

    protected function diaNombre($dia)
    {
        $numero = $this->diaNumero($dia);

        return $numero ? self::$diasSemana[$numero] : null;
    }

    // Compara horas 'H:i' o 'H:i:s'; los rangos que solo se tocan en un extremo no se empalman
    protected function seEmpalman($inicioA, $finA, $inicioB, $finB)
    {
        return substr($inicioA, 0, 5) < substr($finB, 0, 5)
            && substr($inicioB, 0, 5) < substr($finA, 0, 5);
    }

    protected function horaDentroDe($inicio, $fin, $rangoInicio, $rangoFin)
    {
        return substr($inicio, 0, 5) >= substr($rangoInicio, 0, 5)
            && substr($fin, 0, 5) <= substr($rangoFin, 0, 5);
    }
}
