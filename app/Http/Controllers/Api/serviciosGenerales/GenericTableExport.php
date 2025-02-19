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
        if(empty($order))
            return DB::table($this->tableName)->orderBy($this->nameId);
        else return DB::table($this->tableName)->orderBy($this->order);
    }

    public function headings(): array {  
        return $this->headers;
    }  
}