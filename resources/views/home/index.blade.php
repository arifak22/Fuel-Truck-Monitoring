<div class="container-fluid">
    {!!Sideveloper::title($title)!!}
    {!!Sideveloper::breadcrumb($breadcrumbs)!!}
    <div class="row">
        <div class="col-md-12">
            <div class="bd bdrs-3 p-20 mB-20 form-bg" style="background-color: white">
                <div class="row">
                    <div class="col-md-6">
                        {!!Sideveloper::formSelect2('Nama Alat', $alat, 'id_alat')!!}
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
    <div class="masonry-item col-md-12">
        <div class="bgc-white p-20 bd">
          <h6 class="c-grey-900">Chart Summary</h6>
          <div class="mT-30">
            <canvas id="multipleBarChart" height="220"></canvas>
          </div>
        </div>
      </div>
</div>
<script>
$('#id_alat').select2();
var multipleBarChart = document.getElementById('multipleBarChart').getContext('2d');
	var myMultipleBarChart = new Chart(multipleBarChart, {
		type: 'bar',
		data: {
			labels: ["Head Truck 01","Head Truck 02","Head Truck 03"],
			datasets : [{
				label: "Konsumsi BBM",
				backgroundColor: '#59d05d',
				borderColor: '#59d05d',
				data: [200, 300, 100],
			},{
				label: "Hour Meter",
				backgroundColor: '#fdaf4b',
				borderColor: '#fdaf4b',
				data: [2000, 4000, 1500],
			}, {
				label: "Box",
				backgroundColor: '#F25961',
				borderColor: '#F25961',
				data:[50, 40, 70],
			}],
		},
		options: {
			responsive: true, 
			maintainAspectRatio: false,
			legend: {
				position : 'bottom'
			},
			title: {
				display: true,
				text: 'Stats'
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
</script>