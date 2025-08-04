@section('page-script')
    @vite(['resources/js/HS/standard-datatable.js', 'resources/js/HS/standard-offcanvas-right.js'])
@endsection

@foreach ($standardDataTableConfig['table'] as $column)
    <th>{{ ucfirst(str_replace('_', ' ', $column['headerName'])) }}</th>
@endforeach

