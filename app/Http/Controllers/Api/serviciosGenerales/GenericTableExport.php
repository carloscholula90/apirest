<?php  
  
namespace App\Http\Controllers\Api\serviciosGenerales;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;

class GenericTableExport implements FromQuery, WithHeadings
{   
    protected $tableName;
    protected $nameId;
    protected $headers;

    public function __construct($tableName,$nameId,$headers = [])
    {
        $this->tableName = $tableName;
        $this->nameId = $nameId;
        $this->headers = $headers;
    }

    public function query()
    {
        return DB::table($this->tableName)->orderBy($this->nameId);
    }

    public function headings(): array {  
        return $this->headers;
    }  
}