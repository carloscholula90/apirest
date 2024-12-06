<?php  
  
namespace App\Http\Controllers\Api\serviciosGenerales;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;

class GenericTableExport implements FromQuery, WithHeadings
{   
    protected $tableName;
    protected $nameId;

    public function __construct($tableName,$nameId)
    {
        $this->tableName = $tableName;
        $this->nameId = $nameId;
    }

    public function query()
    {
        return DB::table($this->tableName)->orderBy($this->nameId);
    }

    public function headings(): array {  
        $firstRow = DB::table($this->tableName)->orderBy($this->nameId)->first();
        if ($firstRow === null) 
            return [];     
        return array_keys((array) $firstRow);
    }  
}