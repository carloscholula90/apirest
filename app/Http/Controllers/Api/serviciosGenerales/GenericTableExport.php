<?php  
  
namespace App\Http\Controllers\Api\serviciosGenerales;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GenericTableExport implements FromQuery, WithHeadings, WithStrictNullComparison
{   
    protected $tableName;
    protected $nameId;
    protected $headers;
    protected $order;

    public function __construct($tableName,$nameId,$headers = [],$order=null)
    {
        $this->tableName = $tableName;
        $this->nameId = $nameId;
        $this->headers = $headers;
        $this->order = $order;
    }

    public function query()
    {
        $query = DB::table($this->tableName);

        // Exportar solo las columnas que tienen encabezado
        if (!empty($this->headers))
            $query->select(array_slice(Schema::getColumnListing($this->tableName), 0, count($this->headers)));

        if(empty($this->order))
            return $query->orderBy($this->nameId);
        else return $query->orderBy($this->order);
    }

    public function headings(): array {  
        return $this->headers;
    }  
}