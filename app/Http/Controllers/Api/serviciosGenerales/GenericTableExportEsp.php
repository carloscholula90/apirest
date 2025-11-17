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

    public function query() {
        $query = DB::table($this->tableName);
    
       
            if(!isset($join['conditions'])) {  
            foreach ($this->joins as $join) {
                $type = $join['type'] ?? 'inner'; // Default es INNER JOIN
                $query->join($join['table'], function ($joinTable) use ($join) {
                    foreach ($join['conditions'] as $cond) {
                    $joinTable->on($cond['first'], '=', $cond['second']);  }
                }, $type);
            }
            }
            else{
                foreach ($this->joins as $join) {
                    $type = $join['type'] ?? 'inner'; // Default es INNER JOIN
                    $query->join($join['table'], $join['first'], '=', $join['second'], $join['type'] ?? 'inner');
             }
        }
    
        // Aplicar filtros
        foreach ($this->filters as $column => $value) {
            $query->where($column, '=', $value);
        }
        
        $query->select($this->selectColumns);
    
        if (!empty($this->order) && !empty($this->direction)) {
        // Asegurarse de que ambos arreglos tengan el mismo número de elementos
            $orderCount = count($this->order);
            $directionCount = count($this->direction);

            if ($orderCount !== $directionCount) {
                throw new \Exception('El número de columnas de ordenamiento no coincide con el número de direcciones.');
            }

            // Recorrer ambos arreglos usando los mismos índices
            for ($i = 0; $i < $orderCount; $i++) {
                $orderColumn = $this->order[$i];
                $orderDirection = $this->direction[$i];

                // Aplicar el ordenamiento para cada par columna-dirección
                $query->orderBy($orderColumn, $orderDirection);
            }   
        } else {
            $query->orderBy($this->nameId, $this->direction);
        }
        
    Log::info('Query executed', ['query' => $query->toSql()]);
        return $query;
    }

    public function headings(): array{
        return $this->namesColumns;
    }
}