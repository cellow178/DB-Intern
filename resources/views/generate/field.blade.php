
return [
@foreach($fields as $key => $value)
    "{{$key}}" => "{{ $value }}",
@endforeach
];