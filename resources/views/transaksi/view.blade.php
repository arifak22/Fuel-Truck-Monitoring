<div class="container-fluid">
    {!!Sideveloper::title($title)!!}
    {!!Sideveloper::breadcrumb($breadcrumbs)!!}
    <div class="row">
        <div class="col-md-12">
            <div class="bd bdrs-3 p-20 mB-20 form-bg" style="background-color: white">
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
                                {!!Sideveloper::formInput('Hour Meter', 'number', 'hour_meter')!!}
                                {!!Sideveloper::formInput('BBM Level', 'number', 'bbm_level')!!}
                                <div class="form-group m-form__group"> 
                                    <label><button id="getLocation" onclick="openMap()"type="button" class="btn btn-sm btn-primary">Get Lokasi</button></label> 
                                    <textarea class="form-control" name="gps" id="gps" ></textarea> 
                                </div>
                            </div>
                        </div>
                        {!!Sideveloper::formSubmit2('Simpan', 'submit')!!}
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
                            <th>Hour Meter</th>
                            <th width="100px">Lokasi</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>Filter</th>
                            <th>Nama Alat</th>
                            <th>Tanggal</th>
                            <th>BBM Level</th>
                            <th>Hour Meter</th>
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

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">MAP</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            <div id='map' style='width: 100%; height: 450px;'></div>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="submitLokasi()" class="btn btn-info">Submit</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
        </div>
    </div>
</div>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<script src='https://api.mapbox.com/mapbox-gl-js/v2.0.0/mapbox-gl.js'></script>
<link href='https://api.mapbox.com/mapbox-gl-js/v2.0.0/mapbox-gl.css' rel='stylesheet' />
<script>
    mapboxgl.accessToken = 'pk.eyJ1IjoiYXJpZmFrMjIiLCJhIjoiY2tqZWhwbDVuNXE1ODJ4cWo4dTF2MW1wbiJ9.tZk1uItNZtO-6dgydQxjfg';
    var map = new mapboxgl.Map({
        container: 'map',
        bearing: 90,
        style: 'mapbox://styles/mapbox/satellite-v9', // stylesheet location
        // center: [110.42491207584106, -6.938581241192438], // starting position [lng, lat]
        center: [110.42491207584106, -6.938581241192438], // starting position [lng, lat]
        zoom: 16.6 // starting zoom
    });
    function openMap(){
        $("#exampleModal").modal('show');
    }
    var marker = new mapboxgl.Marker();
    var position = null;
    map.on('click', function(e){
        marker.remove();
        marker = new mapboxgl.Marker()
            .setLngLat([e.lngLat.lng, e.lngLat.lat])
            .addTo(map);
        position = e.lngLat;
        console.log(position);
    });
    $('#exampleModal').on('shown.bs.modal', function() {
        map.resize();
    });
    function submitLokasi(){
        if(!position)
            swal('Set Lokasi dahulu');

        $.ajax({
            method: "POST",
            url  : "{{Sideveloper::apiUrl('transaksi/parse-nmea')}}",
            headers: {
                "Authorization": "Bearer " + localStorage.getItem('jwt_token')
            },
            data: { _token: "{{ csrf_token() }}", latitude: position.lat, longitude: position.lng }
        })
        .done(function(res) {
            $("#gps").val(res.parse);
            $("#exampleModal").modal('hide');
        });
    }


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
                hour_meter: {
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
                            resetForm();
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
    function resetForm(){
        $("#formId").get(0).reset();
        $("#tipe").val(1);
        $("#id_alat").val("{{$alat[0]['value']}}").trigger('change');
        $("#label-submit").html('Simpan');
        $(".form-bg").css({"background-color": "white"});
    }
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
            { "data" : "tanggal", "name" : "transaksi.tanggal" },
            { "data" : "bbm_level" },
            { "data" : "hour_meter" },
            { "data" : "gps" },
        ],
        initComplete: function () {
            this.api().columns().every(function () {
                var column = this;
                if(column[0] != 0){
                    var input = '<input class="form-control">';
                    if(column[0] == 2){
                        input = '<input type="date" class="form-control">';
                    }else if(column[0] == 3 || column[0] == 4){
                        input = '<input type="number" class="form-control">';
                    }
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
                data: "id",
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
    $("#cancel-submit").click(function(){
        resetForm();
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
            $(".form-bg").css({"background-color": "#d3eafd"});
            $("#id").val(res.data.id);
            $("#tipe").val(2);
            $("#id_alat").val(res.data.id_alat).trigger('change');
            $("#bbm_level").val(res.data.bbm_level);
            $("#hour_meter").val(res.data.hour_meter);
            $("#tanggal").val(res.data.tanggal);
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