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
                                <th>Nama Alat</th>
                                <th>Konsumsi BBM</th>
                                <th>Selisih Hour Meter</th>
                                <th>Total Box</th>
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
        order        : [[0,'asc']],
        ajax         : {
            url  : "{{Sideveloper::apiUrl('transaksi/summary-report')}}",
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
            { "data" : "nama_alat", "name" : "m_alat.nama" },
            { "data" : "konsumsi_bbm" },
            { "data" : "selisih_hourmeter" },
            { "data" : "total_box" },
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
        // columnDefs: [
        //     {
        //         targets : 0,
        //         orderable: false, 
        //         data: "id",
        //         render: function ( data, type, row, meta ) {
        //             return `
        //                     <div class="form-button-action">
        //                         <button class="aksi" onclick="openMap(${data})">
        //                             <span class="icon-holder"><i class="c-blue-500 ti-map"></i> </span>
        //                         </button>
        //                     </div>`;
        //         }
        //     },
        // ],
    });
    
    function refreshReport(){
        tableList.ajax.reload();
    }
</script>