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

    public function __construct($tableName, $nameId, $filters = [], $order = null, $direction = 'asc', $selectColumns = ['*'], $joins = [],$namesColumns=[])
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
    
        // Uniones
        foreach ($this->joins as $join) {
            $type = $join['type'] ?? 'inner'; // Default es INNER JOIN
            $query->join($join['table'], $join['first'], '=', $join['second'], $join['type'] ?? 'inner'); 
            Log::info('Este es un mensaje de información '.$join['table']);
        }
    
        // Aplicar filtros
        foreach ($this->filters as $column => $value) {
            $query->where($column, '=', $value);
        }
        $columnsToSelect = $this->selectColumns;

        // Verificar si 'activo' está en las columnas seleccionadas
        if (in_array('activo', $this->selectColumns)) 
            $columnsToSelect = array_merge($columnsToSelect, [DB::raw('CASE WHEN activo = 1 THEN "SI" ELSE "NO" END as activo')]);
 
        
        $query->select($columnsToSelect);
    
        // Ordenar los resultados
        if ($this->order) {
            $query->orderBy($this->order, $this->direction);
        } else {
            $query->orderBy($this->nameId, $this->direction);
        }
    
        return $query;
    }

    public function headings(): array{
        return $this->namesColumns;
    }
}