<div class="container-fluid">
    {!!Sideveloper::title($title)!!}
    {!!Sideveloper::breadcrumb($breadcrumbs)!!}
    <div class="row">
        <div class="col-md-12">
            <div class="bgc-white bd bdrs-3 p-20 mB-20">
                <form id="formId">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-6">
                                {!!Sideveloper::formHidden('id')!!}
                                {!!Sideveloper::formHidden('tipe', 1)!!}
                                {!!Sideveloper::formSelect2('Nama Alat', $alat, 'id_alat')!!}
                                {!!Sideveloper::formInput('Waktu', 'text', 'tanggal', date('Y-m-d H:i:s'))!!}
                            </div>
                            <div class="col-md-6">
                                {!!Sideveloper::formInput('BBM Level', 'number', 'bbm_level')!!}
                                {!!Sideveloper::formText('GPS', 'gps')!!}
                            </div>
                        </div>
                        {!!Sideveloper::formSubmit('Simpan', 'submit')!!}
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="bgc-white bd bdrs-3 p-20 mB-20">
                <table id="table-list" class="table table-striped table-bordered" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>Aksi</th>
                            <th>Nama Alat</th>
                            <th>Tanggal</th>
                            <th>BBM Level</th>
                            <th>Lokasi</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>Filter</th>
                            <th>Nama Alat</th>
                            <th>Tanggal</th>
                            <th>BBM Level</th>
                            <th>Lokasi</th>
                        </tr>
                    </tfoot>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    $('#id_alat').select2();
    $('#tanggal').datetimepicker({
        format: 'YYYY-MM-DD HH:mm:ss'
    });
    $('#submit').click(function(e) {
        e.preventDefault();
        var btn = $(this);
        var form = $(this).closest('form');
        form.validate({
            rules: {
                id_alat: {
                    required: true,
                },
                tanggal: {
                    required: true,
                },
                bbm_level: {
                    required: true,
                    min     : 0,
                },
                gps: {
                    required: true,
                    maxlength: 500,
                },
            }
        });
        if (!form.valid()) {
            return;
        }
        apiLoading(true, btn);
        form.ajaxSubmit({
            url    : "{{Sideveloper::apiUrl('transaksi/insert')}}",
            data   : { _token: "{{ csrf_token() }}" },
            headers: {
                "Authorization": "Bearer " + localStorage.getItem('jwt_token')
            }, 
            type: 'POST',
            success: function(response) {
                apiLoading(false, btn);
                apiRespone(response,
                    false
                    ,
                    (res) => {
                        if(res.api_status == '1'){
                            $("#formId").get(0).reset();
                            $("#tipe").val(1);
                            $("#id_alat").val("{{$alat[0]['value']}}").trigger('change');
                            $("#label-submit").html('Simpan');
                            refreshTable();
                        }
                    }
                );
            },
            error: function(error){
                apiLoading(false, btn);
                swal(error.statusText);
            }
        });
    });
    function refreshTable(){
        tableList.ajax.reload();
    }
    var tableList = $('#table-list').DataTable({
        processing   : true,
        serverSide   : true,
        bLengthChange: true,
        bFilter      : true,
        pageLength   : 10,
        order        : [[2,'desc']],
        ajax         : {
            url  : "{{Sideveloper::apiUrl('transaksi/list')}}",
            type : "get",   
            headers: {
                "Authorization": "Bearer " + localStorage.getItem('jwt_token')
            },
            error: function(){ 
                $(".employee-grid-error").html("");
                $("#data-list").append('<tbody class="employee-grid-error"><tr><th colspan="5"><center>Internal Server Error</center></th></tr></tbody>');
                $("#data-list_processing").css("display","none");
            }
        },
        columns : [
            { "data" : "id" },
            { "data" : "nama_alat", "name" : "m_alat.nama" },
            { "data" : "tanggal" },
            { "data" : "bbm_level" },
            { "data" : "gps" },
        ],
        initComplete: function () {
            this.api().columns().every(function () {
                var column = this;
                if(column[0] != 0){
                    var input = document.createElement("input");
                    $(input).appendTo($(column.footer()).empty())
                    .on('change', function () {
                        column.search($(this).val(), false, false, true).draw();
                    });
                }
            });
        },
        columnDefs: [
            {
                targets : 0,
                orderable: false, 
                data: "id_transaksi",
                render: function ( data, type, row, meta ) {
                    return `
                            <div class="form-button-action">
                                <button class="aksi" onclick="ubah(${data})">
                                    <span class="icon-holder"><i class="c-blue-500 ti-pencil"></i> </span>
                                </button>
                                <button class="aksi" onclick="hapus(${data})">
                                    <span class="icon-holder"><i class="c-red-500 ti-trash"></i> </span>
                                </button>
                            </div>`;
                }
            },
        ],
    });
    function ubah(id){
        apiLoading(true);
        $.ajax({
            method: "GET",
            url  : "{{Sideveloper::apiUrl('transaksi/filter')}}",
            headers: {
                "Authorization": "Bearer " + localStorage.getItem('jwt_token')
            },
            data: { id: id, _token: "{{ csrf_token() }}" }
        })
        .done(function(res) {
            $("#id").val(res.data.id);
            $("#tipe").val(2);
            $("#id_alat").val(res.data.id_alat).trigger('change');
            $("#bbm_level").val(res.data.bbm_level);
            $("#gps").val(res.data.gps);
            $("#label-submit").html('Ubah');
            window.scrollTo(0,0);
        })
        .fail(function(err) {
            alert("error");
        })
        .always(function() {
            apiLoading(false);
        });
    }

    function hapus(id){
        swal({
            title: "Apakah anda yakin?",
            text: "Menghapus data ini!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {
            if (willDelete) {
                apiLoading(true);
                $.ajax({
                    method: "POST",
                    url: "{{Sideveloper::apiUrl('transaksi/delete')}}",
                    headers: {
                        "Authorization": "Bearer " + localStorage.getItem('jwt_token')
                    },
                    data: { id: id, _token: "{{ csrf_token() }}" }
                })
                .done(function(res) {
                    apiRespone(res,
						null,
						() => {
                            refreshTable();
						}
					);
                })
                .fail(function(err) {
                    alert("error");
                })
                .always(function() {
                    apiLoading(false);
                });
            }
        });
    }
</script>