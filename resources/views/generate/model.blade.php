
namespace App\Models;

use App\CoreService\CallService;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class {{ $studly_caps }} extends Model
{
    protected $table = '{{ $table_name }}';
    protected $dateFormat = 'c';
    const TABLE = "{{ $table_name }}";
    const FILEROOT = "{{ $fileRoot }}";
    const IS_LIST = {{ $list? "true" : "false" }};
    const IS_ADD = {{ $add? "true" : "false" }};
    const IS_EDIT = {{ $edit? "true" : "false" }};
    const IS_DELETE = {{ $delete? "true" : "false" }};
    const IS_VIEW = {{ $view? "true" : "false" }};
    const FIELD_LIST = {!! arrayToString($fieldList) !!};
    const FIELD_ADD = {!! arrayToString($fieldAdd) !!};
    const FIELD_EDIT = {!! arrayToString($fieldEdit) !!};
    const FIELD_VIEW = {!! arrayToString($fieldView) !!};
    const FIELD_READONLY = {!! arrayToString($fieldReadonly) !!};
    const FIELD_FILTERABLE = [
@foreach($fieldFilterable as $key => $value)
        "{{ $key }}" => [
            "operator" => "{{ $value["operator"] }}",
        ],
@endforeach
    ];
    const FIELD_SEARCHABLE = {!! arrayToString($fieldSearchable) !!};
    const FIELD_ARRAY = {!! arrayToString($fieldArray) !!};
    const FIELD_SORTABLE = {!! arrayToString($fieldSortable) !!};
    const FIELD_UNIQUE = {!! arrayToString($fieldUnique) !!};
    const FIELD_UPLOAD = {!! arrayToString($fieldUpload) !!};
    const FIELD_TYPE = [
@foreach($fieldType as $key => $type)
        "{{ $key }}" => "{{ $type }}",
@endforeach
    ];

    const FIELD_DEFAULT_VALUE = [
@foreach($fieldDefaultValue as $key => $value)
        "{{ $key }}" => "{{ $value }}",
@endforeach
    ];
    const FIELD_RELATION = [
@foreach($fieldRelation as $key => $relation)
        "{{ $key }}" => [
            "linkTable" => "{{ $relation["linkTable"] }}",
            "aliasTable" => "{{ $relation["aliasTable"] }}",
            "linkField" => "{{ $relation["linkField"] }}",
            "displayName" => "{{ $relation["displayName"] }}",
            "selectFields" => {!! arrayToString($relation["selectFields"]) !!},
            "selectValue" => "{{ $relation["selectValue"] }}"
        ],
@endforeach
    ];
    const CUSTOM_RELATION = [];
    const CUSTOM_SELECT = "{!! $customSelect !!}";
    const FIELD_VALIDATION = [
@foreach($fieldValidation as $key => $validation)
        "{{ $key }}" => "{{ $validation }}",
@endforeach
    ];
    const PARENT_CHILD = {!! arrayToString($parentChild) !!};
    // start custom{!! $customContent !!}// end custom
}
