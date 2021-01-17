<div class="container-fluid">
    {!!Sideveloper::title($title)!!}
    {!!Sideveloper::breadcrumb($breadcrumbs)!!}
    <div class="row">
        <div class="col-md-12">
            <div class="bd bdrs-3 p-20 mB-20 form-bg" style="background-color: white">
                <div class="row">
                    <div class="col-md-6">
                        {!!Sideveloper::formSelect2('Nama Alat', $alat, 'id_alat')!!}
                        {!!Sideveloper::formSelect('Tipe Laporan', 
                            array(
                                array('name'=>'Harian', 'value'=>'1'),
                                array('name'=>'Bulanan', 'value'=>'2'),
                            ),
                            'tipe_laporan'
                        )!!}
                    </div>
                    <div class="col-md-6">
                        <div id="harian">
                            {!!Sideveloper::formInput('Start Date', 'text', 'start_date', date('Y-m-d',strtotime("-7 day")))!!}
                            {!!Sideveloper::formInput('End Date', 'text', 'end_date', date('Y-m-d'))!!}
                        </div>
                        <div id="bulanan">
                            {!!Sideveloper::formInput('Start Month', 'text', 'start_month', date('Y-m', strtotime("-1 month")))!!}
                            {!!Sideveloper::formInput('End Month', 'text', 'end_month', date('Y-m'))!!}
                        </div>
                        <div class="pull-right">
                        <button type="button" onclick="exportExcel();" class="btn btn-success"><i class="ti-save" aria-hidden="true"></i> Export Excel</button> &nbsp;&nbsp;
                        <button type="button" onclick="refreshReport();" class="btn btn-info"><i class="fa fa-filter" aria-hidden="true"></i> Filter</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="bgc-white bd bdrs-3 p-20 mB-20">
                <div class="table-responsive">
                    <table id="table-list" class="table table-striped table-bordered" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th width="20px">Map</th>
                                <th>Nama Alat</th>
                                <th>Tanggal</th>
                                <th>BBM Level</th>
                                <th width="100px">NMEA</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
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
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
        </div>
    </div>
</div>
<style>
    #table-list_filter{
        display: none;
    }
</style>
<script src='https://api.mapbox.com/mapbox-gl-js/v2.0.0/mapbox-gl.js'></script>
<link href='https://api.mapbox.com/mapbox-gl-js/v2.0.0/mapbox-gl.css' rel='stylesheet' />
<script>
    $(document).ready(function(){
        $("#bulanan").hide();
    });

    $("#tipe_laporan").change(function(){
        var value = $(this).val();
        if(value == '1'){
            $("#bulanan").hide();
            $("#harian").show();
        }else{
            $("#bulanan").show();
            $("#harian").hide();
        }
    });
    $('#id_alat').select2();
    
    $('#start_date, #end_date').datetimepicker({
        format: 'YYYY-MM-DD'
    });
    $('#start_month, #end_month').datetimepicker({
        format: 'YYYY-MM'
    });

    
    var tableList = $('#table-list').DataTable({
        processing   : true,
        serverSide   : true,
        bLengthChange: true,
        bFilter      : false,
        pageLength   : 10,
        order        : [[2,'desc']],
        ajax         : {
            url  : "{{Sideveloper::apiUrl('transaksi/report')}}",
            type : "get",   
            headers: {
                "Authorization": "Bearer " + localStorage.getItem('jwt_token')
            },
            data: function(d) {
                d.id_alat      = $("#id_alat").val();
                d.tipe_laporan = $("#tipe_laporan").val();
                d.start_date   = $("#start_date").val();
                d.end_date     = $("#end_date").val();
                d.start_month  = $("#start_month").val();
                d.end_month    = $("#end_month").val();
            },
            error: function(){ 
                $(".employee-grid-error").html("");
                $("#data-list").append('<tbody class="employee-grid-error"><tr><th colspan="4"><center>Internal Server Error</center></th></tr></tbody>');
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
        // initComplete: function () {
        //     this.api().columns().every(function () {
        //         var column = this;
        //         if(column[0] != 0){
        //             var input = '<input class="form-control">';
        //             if(column[0] == 2){
        //                 input = '<input type="date" class="form-control">';
        //             }else if(column[0] == 3){
        //                 input = '<input type="number" class="form-control">';
        //             }
        //             $(input).appendTo($(column.footer()).empty())
        //             .on('change', function () {
        //                 column.search($(this).val(), false, false, true).draw();
        //             });
        //         }
        //     });
        // },
        columnDefs: [
            {
                targets : 0,
                orderable: false, 
                data: "id",
                render: function ( data, type, row, meta ) {
                    return `
                            <div class="form-button-action">
                                <button class="aksi" onclick="openMap(${data})">
                                    <span class="icon-holder"><i class="c-blue-500 ti-map"></i> </span>
                                </button>
                            </div>`;
                }
            },
        ],
    });
    
    mapboxgl.accessToken = 'pk.eyJ1IjoiYXJpZmFrMjIiLCJhIjoiY2tqZWhwbDVuNXE1ODJ4cWo4dTF2MW1wbiJ9.tZk1uItNZtO-6dgydQxjfg';
    var map = new mapboxgl.Map({
        container: 'map',
        bearing: 90,
        style: 'mapbox://styles/mapbox/satellite-v9', // stylesheet location
        center: [110.42491207584106, -6.938581241192438], // starting position [lng, lat]
        zoom: 16.6 // starting zoom
    });
    let mapMarkers = [];
    function openMap(id){
        mapMarkers.forEach((marker) => marker.remove())
        mapMarkers = []
        $.ajax({
            method: "GET",
            url  : "{{Sideveloper::apiUrl('transaksi/lokasi-last')}}",
            headers: {
                "Authorization": "Bearer " + localStorage.getItem('jwt_token')
            },
            data: { _token: "{{ csrf_token() }}", id: id }
        })
        .done(function(res) {
            res.data.forEach(element => {
                var oImg = document.createElement("img");
                oImg.setAttribute('src', '{{url('assets/_custom/img/truck.png')}}');
                oImg.setAttribute('alt', 'na');
                oImg.setAttribute('height', '45px');
                oImg.setAttribute('width', '45px');
                oImg.setAttribute('style', 'margin-top:-5px');
                oImg.addEventListener('click', function () {
                    openTab(element);
                });
                var el = document.createElement("center");
                el.className = 'marker';
                el.innerHTML = '<b>'+element.kode_alat+'</b><br>';
                el.appendChild(oImg);
                const marker = new mapboxgl.Marker(el)
                    .setLngLat([element.lng, element.lat])
                    .addTo(map);
                mapMarkers.push(marker);
            });
        })
        $("#exampleModal").modal('show');
    }
    $('#exampleModal').on('shown.bs.modal', function() {
        map.resize();
    });
    
    function openTab(element){
        new mapboxgl.Popup({ closeOnClick: false })
            .setLngLat([element.lng, element.lat])
            .setHTML(`<b style="font-size:16px">${element.nama}</b>
                <ul style="padding-left:20px">
                    <li>
                        BBM Level ${element.bbm_level} <br>
                        <i>(${element.tanggal_bbm})</i>
                    </li>
                    <li>
                        Hour Meter ${element.hour_meter} <br>
                        <i>(${element.tanggal_hm})</i>
                    </li>
                    <li>
                        Box ${element.box ? element.box : 0} <br>
                        <i>(${element.tanggal_box})</i>
                    </li>
                </ul>
            `)
            .addTo(map);
    }
    function refreshReport(){
        tableList.ajax.reload();
    }

    

    function exportExcel(){
        var id_alat      = $("#id_alat").val();
        var tipe_laporan = $("#tipe_laporan").val();
        var start_date   = $("#start_date").val();
        var end_date     = $("#end_date").val();
        var start_month  = $("#start_month").val();
        var end_month    = $("#end_month").val();

        window.open("{{Sideveloper::selfUrl('export-report')}}?id_alat=" + id_alat + "&tipe_laporan=" + tipe_laporan + "&start_date=" + start_date + "&end_date=" + end_date + "&start_month=" + start_month+ "&end_month=" + end_month);
    }
</script>