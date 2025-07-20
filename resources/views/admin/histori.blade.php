@extends('layouts.app')

@section('sidebar')
@include('layouts.sidebar')
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light"></span> Histori Chat</h4>

    <!-- Ajax Sourced Server-side -->
    <div class="card">
        <h5 class="card-header">Informasi History Chat</h5>
        <div class="card-datatable text-nowrap">
            <div id="DataTables_Table_0_wrapper" class="dataTables_wrapper dt-bootstrap5 no-footer">
                <div class="table-responsive">
                    <table class="datatables-ajax table dataTable no-footer" id="tbl_chat_histori" aria-describedby="DataTables_Table_0_info">
                        <thead>
                            <tr>
                                <th class="sorting sorting_asc" tabindex="0" aria-controls="DataTables_Table_0" rowspan="1" colspan="1" style="width: 143.9px;" aria-sort="ascending" aria-label="Full name: activate to sort column descending">No</th>
                                <th class="sorting" tabindex="0" aria-controls="DataTables_Table_0" rowspan="1" colspan="1" style="width: 87.8333px;">Nama</th>
                                <th class="sorting" tabindex="0" aria-controls="DataTables_Table_0" rowspan="1" colspan="1" style="width: 123.8px;">Pesan User</th>
                                <th class="sorting" tabindex="0" aria-controls="DataTables_Table_0" rowspan="1" colspan="1" style="width: 98.0333px;">Response</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="odd">
                                <td valign="top" colspan="6" class="dataTables_empty">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!--/ Ajax Sourced Server-side -->

</div>
@endsection
@section('js')
<script>
$(document).ready(function () {
    $('#tbl_chat_histori').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("chat.history") }}',
        columns: [
            {
                data: null,
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                },
                orderable: false,
                searchable: false
            },
            { data: 'nama_user'},
            { data: 'message'},
            { data: 'response'}
        ]
    });
});
</script>
@endsection