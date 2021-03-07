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
                                {!!Sideveloper::formInput('Username', 'text', 'username', null)!!}
                                {!!Sideveloper::formInput('Password', 'text', 'password', null)!!}
                            
                                {!!Sideveloper::formSelect('Privilege', $privilege, 'privilege')!!}
                            </div>
                            <div class="col-md-6">
                                {!!Sideveloper::formInput('Nama', 'text', 'nama', null)!!}
                                {!!Sideveloper::formInput('E-mail', 'email', 'email', null)!!}
                                {!!Sideveloper::formSelect('Status', array(
                                    array('name'=>'Aktif', 'value'=>'Y'),
                                    array('name'=>'Tidak Aktif', 'value'=>'N'),
                                ), 'status')!!}
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
                            <th>Privilege</th>
                            <th>Username</th>
                            <th>Nama</th>
                            <th>E-Mail</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>Aksi</th>
                            <th>Privilege</th>
                            <th>Username</th>
                            <th>Nama</th>
                            <th>E-Mail</th>
                            <th>Status</th>
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
    $('#submit').click(function(e) {
        e.preventDefault();
        var btn = $(this);
        var form = $(this).closest('form');
        form.validate({
            rules: {
                username: {
                    required: true,
                    maxlength: 50,
                },
                nama: {
                    required: true,
                    maxlength: 150,
                },
                password: {
                    required: true,
                    maxlength: 150,
                },
                email: {
                    required: true,
                    maxlength: 150,
                },
                privilege: {
                    required: true,
                },
                status: {
                    required: true,
                },
            }
        });
        if (!form.valid()) {
            return;
        }
        apiLoading(true, btn);
        form.ajaxSubmit({
            url    : "{{Sideveloper::apiUrl('master/user-insert')}}",
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
            url  : "{{Sideveloper::apiUrl('master/truk-list')}}",
            type : "get",   
            headers: {
                "Authorization": "Bearer " + localStorage.getItem('jwt_token')
            },
            error: function(){ 
                $(".employee-grid-error").html("");
                $("#data-list").append('<tbody class="employee-grid-error"><tr><th colspan="4"><center>Internal Server Error</center></th></tr></tbody>');
                $("#data-list_processing").css("display","none");
            }
        },
        columns : [
            { "data" : "id_alat" },
            { "data" : "kode_alat"},
            { "data" : "nama" },
            { "data" : "status" },
        ],
        initComplete: function () {
            this.api().columns().every(function () {
                var column = this;
                if(column[0] != 0){
                    var input = '<input class="form-control">';
                    if(column[0] == 3){
                        input = `<select class="form-control">
                                    <option value=""> -- Semua -- </option>
                                    <option value="Y"> Aktif </option>
                                    <option value="N"> Tidak Aktif </option>
                                </select>
                                `;
                    }
                    // console.log(column[0]);
                    $(input).appendTo($(column.footer()).empty())
                    .on('change', function () {
                        column.search($(this).val(), false, false, true).draw();
                    });
                }
            });
        },
        createdRow: function( row, data, dataIndex){
            if( data.status ==  `N`){
                $(row).addClass('redClass');
            }
            console.log(data);
        },
        columnDefs: [
            {
                targets : 0,
                orderable: false, 
                data: "id_alat",
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
            {
                targets : 3,
                orderable: false, 
                data: "status",
                render: function ( data, type, row, meta ) {
                    // $(row).addClass('redClass');
                    if(data == 'N'){
                        return 'Tidak Aktif';
                    }else{
                        return 'Aktif';
                    }
                }
            },
        ],
    });
    function ubah(id){
        apiLoading(true);
        $.ajax({
            method: "GET",
            url  : "{{Sideveloper::apiUrl('master/truk-filter')}}",
            headers: {
                "Authorization": "Bearer " + localStorage.getItem('jwt_token')
            },
            data: { id: id, _token: "{{ csrf_token() }}" }
        })
        .done(function(res) {
            $("#id").val(res.data.id_alat);
            $("#tipe").val(2);
            $("#kode_alat").val(res.data.kode_alat);
            $("#nama").val(res.data.nama);
            $("#status").val(res.data.status);
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
                    url: "{{Sideveloper::apiUrl('master/truk-delete')}}",
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