<?php  
  
namespace App\Http\Controllers\Api\serviciosGenerales;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenericTableExportEsp implements FromQuery, WithHeadings
{
    protected $tableName;
    protected $nameId;
    protected $filters;
    protected $order;
    protected $direction;
    protected $selectColumns;
    protected $joins;  
    protected $namesColumns;

    public function __construct($tableName, $nameId, $filters = [], $order = [], $direction = [], $selectColumns = ['*'], $joins = [],$namesColumns=[])
    {
        $this->tableName = $tableName;
        $this->nameId = $nameId;
        $this->filters = $filters;
        $this->order = $order;
        $this->direction = $direction;
        $this->selectColumns = $selectColumns;
        
        if(empty($namesColumns))
            $this->namesColumns = $selectColumns;
        else $this->namesColumns = $namesColumns;

        $this->joins = $joins;
    }

    public function query(){

    $query = DB::table($this->tableName);    
    foreach ($this->joins as $join) {
        if (!isset($join['table'], $join['conditions'])) {
            throw new \Exception('Join mal definido');
        }
        $type = $join['type'] ?? 'inner';
        $query->join($join['table'], function ($joinTable) use ($join) {
            foreach ($join['conditions'] as $cond) {
                if (!isset($cond['first'], $cond['second'])) {
                    throw new \Exception('Condición de JOIN mal definida');
                }
                $joinTable->on($cond['first'], '=', $cond['second']);
            }
        }, null, null, $type);
    }
    
    foreach ($this->filters as $column => $value) {
        $query->where($column, '=', $value);
    }

    $query->select($this->selectColumns);

    if (!empty($this->order) && !empty($this->direction)) {
        if (count($this->order) !== count($this->direction)) {
            throw new \Exception(
                'El número de columnas de ordenamiento no coincide con el número de direcciones.'
            );
        }

        for ($i = 0; $i < count($this->order); $i++) {
            $query->orderBy($this->order[$i], $this->direction[$i]);
        }

    } else {
        $query->orderBy($this->nameId, $this->direction);
    }
    return $query;
}

    public function headings(): array{
        return $this->namesColumns;
    }
}