<div class="container-fluid">
    {!!Sideveloper::title($title)!!}
    {!!Sideveloper::breadcrumb($breadcrumbs)!!}
    <div class="row">
        <div class="col-md-12">
            <div class="bd bdrs-3 p-20 mB-20 form-bg" style="background-color: white">
                <div class="row">
                    <div class="col-md-6">
						{!!Sideveloper::formSelect2('Nama Alat', $alat, 'id_alat[]', 'all', 'multiple')!!}
						<div class="form-group row">
							<div class="col-sm-10">
								<div class="form-check">
									<label class="form-check-label">
										<input class="form-check-input" id="bbm" type="checkbox" checked> Konsumsi BBM
									</label>
								</div>
							</div>
						</div>
						<div class="form-group row">
							<div class="col-sm-10">
								<div class="form-check">
									<label class="form-check-label">
										<input class="form-check-input" id="hm" type="checkbox" checked> Selisih Hour Meter
									</label>
								</div>
							</div>
						</div>
						<div class="form-group row">
							<div class="col-sm-10">
								<div class="form-check">
									<label class="form-check-label">
										<input class="form-check-input" id="box" type="checkbox" checked> Total Box
									</label>
								</div>
							</div>
						</div>
                    </div>
                    <div class="col-md-6">
                        <div id="harian">
                            {!!Sideveloper::formInput('Start Date', 'text', 'start_date', date('Y-m-d',strtotime("-7 day")))!!}
                            {!!Sideveloper::formInput('End Date', 'text', 'end_date', date('Y-m-d'))!!}
                        </div>
                        <div class="pull-right">
                        <button type="button" onclick="refreshReport();" class="btn btn-info"><i class="fa fa-filter" aria-hidden="true"></i> Filter</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
	<div class="row">
        <div class="col-md-12">
            <div class="bd bdrs-3 p-20 mB-20 form-bg" style="background-color: white">
                <div class="row">
					<div class="col-md-12" style="height: 400px">
						<h6 class="c-grey-900">Chart Summary</h6>
						<canvas id="multipleBarChart"></canvas>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
	$('#id_alat').select2();
	var multipleBarChart = document.getElementById('multipleBarChart').getContext('2d');
	var myChart = new Chart(multipleBarChart, {
		type: 'bar',
		// data: {
		// 	labels: ["Head Truck 01","Head Truck 02","Head Truck 03"],
		// 	datasets : [{
		// 		label: "Konsumsi BBM",
		// 		backgroundColor: '#59d05d',
		// 		borderColor: '#59d05d',
		// 		data: [200, 300, 100],
		// 	},{
		// 		label: "Hour Meter",
		// 		backgroundColor: '#fdaf4b',
		// 		borderColor: '#fdaf4b',
		// 		data: [2000, 4000, 1500],
		// 	}, {
		// 		label: "Box",
		// 		backgroundColor: '#F25961',
		// 		borderColor: '#F25961',
		// 		data:[50, 40, 70],
		// 	}],
		// },
		options: {
			responsive: true, 
			maintainAspectRatio: false,
			legend: {
				position : 'bottom'
			},
			title: {
				display: true,
				text: 'Chart Summary'
			},
			tooltips: {
				mode: 'index',
				intersect: false
			},
			responsive: true,
			scaleShowValues: true,
			scales: {
				xAxes: [{
					stacked: false,
					ticks: {
						autoSkip: false
					}
				}],
				yAxes: [{
					stacked: false,
					scaleLabel: {
						display: true,
						labelString: 'Level'
					}
				}]
			}
		}
	});
	
	function UpdateChart(chart) {
		var data = null;

		$.ajax({
            method: "GET",
            url  : "{{Sideveloper::apiUrl('transaksi/grafik')}}",
            headers: {
                "Authorization": "Bearer " + localStorage.getItem('jwt_token')
            },
            data: { _token: "{{ csrf_token() }}", start_date :  $("#start_date").val(), end_date : $("#end_date").val(), alat: $("#id_alat").val() }
        })
        .done(function(res) {
			data = {
						labels: res.alat,
						datasets : [
							{
								label: "Konsumsi BBM",
								backgroundColor: '#59d05d',
								borderColor: '#59d05d',
								data: res.konsumsi_bbm,
							},
						{
							label: "Selisih Hour Meter",
							backgroundColor: '#fdaf4b',
							borderColor: '#fdaf4b',
							data: res.selisih_hourmeter,
						}, {
							label: "Total Box",
							backgroundColor: '#F25961',
							borderColor: '#F25961',
							data: res.total_box,
						}],
					}
					
		chart.data.labels = data.labels;
		splicebbm = 0;
		splicehm  = 0;
		splicebox = 0;
		if(!$('#bbm').is(":checked")){
			data.datasets.splice(0,1);
			splicebbm = 1;
		}
		if(!$('#hm').is(":checked")){
			data.datasets.splice(1 - splicebbm,1);
			splicehm = 1;
		}
		if(!$('#box').is(":checked")){
			data.datasets.splice(2 - splicebbm - splicehm,1);
			splicebox = 1;
		}
		// delete data.datasets[2];
		console.log(data)
		chart.data.datasets = data.datasets;
		chart.update();
		});
	}
	function refreshReport(){
		UpdateChart(myChart);
	}
	$(document).ready(function(){
		UpdateChart(myChart);
	})
</script>