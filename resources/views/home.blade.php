@extends('layout.app')

@section('title')
Home
@endsection

@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item active" aria-current="page">Home</li>
</ol>
@endsection

@section('content')
<style type="text/css">
	hr.new2 {
	  border-top: 1px dashed red;
	}

</style>
	<input type="hidden" name="hak_akses" id="hak_akses" value="{{ $hak_akses }}">
	<div class="card mb-12 border-left-primary card-info">
	    <div class="card-body">
	      	<div class="row">
	      		<?php 
                    $nama_apotek_panjang_active = session('nama_apotek_panjang_active');
                    $id_apotek_active = session('id_apotek_active');
                    $id_role_active = session('id_role_active');
                    $date = date('d-m-Y H:i:s');
                ?>
                @if(empty($id_apotek_active))
                	<div class="col-md-12">
	      				<br>
                		<p class="text-red"><cite><b>Anda belum memilih apotek, silakan pilih apotek terlebih dahulu!</b></cite></p>
	      			</div>
                @else
                	<div class="col-lg-12 col-12">
				        <!-- small box -->
				        <div class="small-box bg-secondary">
				            <div class="inner text-center">
				                <h3>Apotek {{ $nama_apotek_panjang_active }}</h3>
				                <p>Taanggal Hari ini : {{ $date }}</p>
				            </div>
				            <div class="icon">
				                <i class="fa fa-hospital-user"></i>
				            </div>
				        </div>
				    </div>
				    @if($hak_akses == 1)
				    	<?php 
                            
                            $total_cash_kredit = $detail_penjualan_kredit->total - $penjualan_kredit->total_debet;
                            $total_cash_kredit_format = number_format($total_cash_kredit,0,',',',');

                            $total_diskon = $detail_penjualan->total_diskon_persen + $penjualan2->total_diskon_rp;
                            $total_3 = $detail_penjualan->total-$total_diskon;
                            $total_3_format = number_format($total_3,0,',',',');

                            $total_cash_kredit_terbayar = ($detail_penjualan_kredit_terbayar->total + $penjualan_kredit_terbayar->total_jasa_dokter + $penjualan_kredit_terbayar->total_jasa_resep) - $penjualan_kredit_terbayar->total_debet-$detail_penjualan_kredit_terbayar->total_diskon_vendor;
                            $total_penjualan_kredit_terbayar = $penjualan_kredit_terbayar->total_debet+$total_cash_kredit_terbayar;
                            $total_penjualan_kredit_terbayar_format = number_format($total_penjualan_kredit_terbayar,0,',',',');

                            $total_tf_masuk = number_format($total_penjualan_kredit_terbayar,0,',',',');
                            $total_tf_keluar = number_format($total_penjualan_kredit_terbayar,0,',',',');

                            $total_pembelian = number_format($detail_pembelian->total,0,',',',');
                            $total_pembelian_terbayar = number_format($detail_pembelian_terbayar->total,0,',',',');
                            $total_pembelian_blm_terbayar = number_format($detail_pembelian_blm_terbayar->total,0,',',',');
                            $total_pembelian_jatuh_tempo = number_format($detail_pembelian_jatuh_tempo->total,0,',',',');
                        ?>
						<div class="col-lg-12">
						    <!-- AREA CHART -->
						    <div class="card card-secondary">
						        <div class="card-header">
						            <h3 class="card-title"><i class="fa fa-chart-area"></i> Recap Report</h3>
						            <div class="card-tools">
						            	<a type="button" class="btn btn-info btn-sm" target="_blank" href="{{ url('recap_all') }}">Lihat Detail Perbulan</i>
						                </a>
						                <a type="button" class="btn btn-info btn-sm" target="_blank" href="{{ url('recap_perhari') }}">Lihat Detail Perhari</i>
						                </a>
						                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
						                </button>
						                <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
						            </div>
						        </div>
						        <div class="card-body">
						        	<div class="col-lg-12">
										<div class="row">
										    <div class="col-sm-4">
										        <div class="description-block border-right">
										            <!-- <span class="description-percentage text-success"><i class="fas fa-caret-up"></i> 17%</span> -->
										            <h5 class="description-header">Rp {{ $total_3_format }}</h5>
										            <span class="description-text">TOTAL PENJUALAN NON KREDIT</span>
										        </div>
										        <!-- /.description-block -->
										    </div>
										    <!-- /.col -->
										    <div class="col-sm-4">
										        <div class="description-block border-right">
										            <!-- <span class="description-percentage text-warning"><i class="fas fa-caret-left"></i> 0%</span> -->
										            <h5 class="description-header">Rp {{ $total_cash_kredit_format }}</h5>
										            <span class="description-text">TOTAL PENJUALAN KREDIT</span>
										        </div>
										        <!-- /.description-block -->
										    </div>
										    <!-- /.col -->
										    <div class="col-sm-4">
										        <div class="description-block">
										            <!-- <span class="description-percentage text-success"><i class="fas fa-caret-up"></i> 20%</span> -->
										            <h5 class="description-header">Rp {{ $total_penjualan_kredit_terbayar_format }}</h5>
										            <span class="description-text">TOTAL PEMBAYARAN PENJUALAN KREDIT</span>
										        </div>
										        <!-- /.description-block -->
										    </div>
										</div>
							    	</div>
							    	<hr>
							    	<div class="col-lg-12">
										<div class="row">
										    <div class="col-sm-3 col-6">
										        <div class="description-block border-right">
										            <!-- <span class="description-percentage text-success"><i class="fas fa-caret-up"></i> 17%</span> -->
										            <h5 class="description-header">Rp {{ $total_pembelian }}</h5>
										            <span class="description-text">TOTAL PEMBELIAN</span>
										        </div>
										        <!-- /.description-block -->
										    </div>
										    <!-- /.col -->
										    <div class="col-sm-3 col-6">
										        <div class="description-block border-right">
										            <!-- <span class="description-percentage text-warning"><i class="fas fa-caret-left"></i> 0%</span> -->
										            <h5 class="description-header">Rp {{ $total_pembelian_blm_terbayar }}</h5>
										            <span class="description-text">TOTAL PIUTANG PEMBELIAN</span>
										        </div>
										        <!-- /.description-block -->
										    </div>
										    <!-- /.col -->
										    <div class="col-sm-3 col-6">
										        <div class="description-block border-right">
										            <!-- <span class="description-percentage text-success"><i class="fas fa-caret-up"></i> 20%</span> -->
										            <h5 class="description-header">Rp {{ $total_pembelian_terbayar }}</h5>
										            <span class="description-text">TOTAL PEMBELIAN TERBAYAR</span>
										        </div>
										        <!-- /.description-block -->
										    </div>
										    <!-- /.col -->
										    <div class="col-sm-3 col-6">
										        <div class="description-block">
										            <!-- <span class="description-percentage text-danger"><i class="fas fa-caret-down"></i> 18%</span> -->
										            <h5 class="description-header">Rp {{ $total_pembelian_jatuh_tempo }}</h5>
										            <span class="description-text">TOTAL PEMBELIAN JATUH TEMPO</span>
										        </div>
										        <!-- /.description-block -->
										    </div>
										</div>
							    	</div>
							    	<hr>
						            <div class="col-lg-12">
										<div class="row">
										    <div class="col-sm-3 col-6">
										        <div class="description-block border-right">
										            <!-- <span class="description-percentage text-success"><i class="fas fa-caret-up"></i> 20%</span> -->
										            <h5 class="description-header">Rp {{ $total_tf_masuk }}</h5>
										            <span class="description-text">TOTAL TRANSFER MASUK</span>
										        </div>
										        <!-- /.description-block -->
										    </div>
										    <!-- /.col -->
										    <div class="col-sm-3 col-6">
										        <div class="description-block">
										            <!-- <span class="description-percentage text-danger"><i class="fas fa-caret-down"></i> 18%</span> -->
										            <h5 class="description-header text-info">-informasi belum tersedia-</h5>
										            <span class="description-text">TOTAL TRANSFER KELUAR TERBAYAR</span>
										        </div>
										        <!-- /.description-block -->
										    </div>
										    <!-- /.col -->
										    <div class="col-sm-3 col-6">
										        <div class="description-block border-right">
										            <!-- <span class="description-percentage text-success"><i class="fas fa-caret-up"></i> 20%</span> -->
										            <h5 class="description-header">Rp {{ $total_tf_keluar }}</h5>
										            <span class="description-text">TOTAL TRANSFER KELUAR</span>
										        </div>
										        <!-- /.description-block -->
										    </div>
										    <!-- /.col -->
										    <div class="col-sm-3 col-6">
										        <div class="description-block">
										            <!-- <span class="description-percentage text-danger"><i class="fas fa-caret-down"></i> 18%</span> -->
										            <h5 class="description-header text-info">-informasi belum tersedia-</h5>
										            <span class="description-text">TOTAL PIUTANG TRANSFER MASUK</span>
										        </div>
										        <!-- /.description-block -->
										    </div>
										</div>
							    	</div>
						        </div>
						        <!-- /.card-body -->
						    </div>
						    <!-- /.card -->
						</div>
						<div class="col-md-12">
							<div class="callout callout-info">
								<p class="text-red">Data penjualan pada grafik ditampilkan dari data closing kasir.</p>
			                </div>
						</div>
	                    <div class="col-lg-4">
						    <!-- AREA CHART -->
						    <div class="card card-info">
						        <div class="card-header">
						            <h3 class="card-title"><i class="fa fa-chart-area"></i> Penjualan</h3>
						            <div class="card-tools">
						                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
						                </button>
						                <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
						            </div>
						        </div>
						        <div class="card-body">
						            <div class="chart">
						                <canvas id="areaChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
						            </div>
						        </div>
						        <!-- /.card-body -->
						    </div>
						    <!-- /.card -->
						</div>
						<div class="col-lg-4">
						    <!-- AREA CHART -->
						    <div class="card card-info">
						        <div class="card-header">
						            <h3 class="card-title"><i class="fa fa-chart-line"></i> Penjualan</h3>
						            <div class="card-tools">
						                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
						                </button>
						                <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
						            </div>
						        </div>
						        <div class="card-body">
						            <div class="chart">
					                  <canvas id="lineChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
					                </div>
						        </div>
						        <!-- /.card-body -->
						    </div>
						    <!-- /.card -->
						</div>
						<div class="col-lg-4">
						    <!-- AREA CHART -->
						    <div class="card card-info">
						        <div class="card-header">
						            <h3 class="card-title"><i class="fa fa-chart-bar"></i> Penjualan</h3>
						            <div class="card-tools">
						                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
						                </button>
						                <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
						            </div>
						        </div>
						        <div class="card-body">
						            <div class="chart">
					                  <canvas id="barChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
					                </div>
						        </div>
						        <!-- /.card-body -->
						    </div>
						    <!-- /.card -->
						</div>
						<div class="col-lg-12">
						    <!-- AREA CHART -->
						    <div class="card card-secondary">
						        <div class="card-header">
						            <h3 class="card-title"><i class="fa fa-chart-bar"></i> Penjualan, Pembelian, Transfer Masuk, & Transfer Keluar</h3>
						            <div class="card-tools">
						                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
						                </button>
						                <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
						            </div>
						        </div>
						        <div class="card-body">
						            <div class="chart">
					                  <canvas id="barChartAll" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
					                </div>
						        </div>
						        <!-- /.card-body -->
						    </div>
						    <!-- /.card -->
						</div>
					@endif
                @endif
	      	</div>
	    </div>
	</div>
@endsection

@section('script')
<script type="text/javascript">
	var token = '{{csrf_token()}}';

	$(document).ready(function(){
		var hak_akses = $("#hak_akses").val();
		if(hak_akses == 1) {
			load_garfik();
		}
	})


	function load_garfik() {
		$.ajax({
            type: "GET",
            url: '{{url("home/load_grafik")}}',
            // dataType:'json',
            data: { 
            },
            beforeSend: function(data){
                // replace dengan fungsi loading
                spinner.show();
            },
            success:  function(data){
                
            },
            complete: function(data){
                // replace dengan fungsi mematikan loading
                var areaChartCanvas = $('#areaChart').get(0).getContext('2d')
				var areaChartData = {
					labels  : data.responseJSON.penjualan.label,
					datasets: [
						{
						label               : 'Penjualan Outlet',
						backgroundColor     : 'rgba(0, 151, 167,0.9)',
						borderColor         : 'rgba(0, 151, 167,0.8)',
						pointRadius          : false,
						pointColor          : '#006064',
						pointStrokeColor    : 'rgba(0, 151, 167,1)',
						pointHighlightFill  : '#fff',
						pointHighlightStroke: 'rgba(0, 151, 167,1)',
						data                : data.responseJSON.penjualan.values,
						},
						{
						label               : 'Rata-rata Group',
						backgroundColor     : 'rgba(251, 140, 0, 1)',
						borderColor         : 'rgba(251, 140, 0, 1)',
						pointRadius         : false,
						pointColor          : 'rgba(251, 140, 0, 1)',
						pointStrokeColor    : '#e65100',
						pointHighlightFill  : '#fff',
						pointHighlightStroke: 'rgba(251, 140, 0, 1)',
						data                : data.responseJSON.penjualan.all
						},
					]
				}
				
				var areaChartOptions = {
						maintainAspectRatio : false,
						responsive : true,
						legend: {
						display: false
					},
					scales: {
					xAxes: [{
						gridLines : {
						display : false,
						}
					}],
					yAxes: [{
						gridLines : {
						display : false,
						}
					}]
					}
				}
				
				// This will get the first returned node in the jQuery collection.
				var areaChart       = new Chart(areaChartCanvas, { 
					type: 'line',
					data: areaChartData, 
					options: areaChartOptions
				})

				var lineChartCanvas = $('#lineChart').get(0).getContext('2d')
				var lineChartOptions = jQuery.extend(true, {}, areaChartOptions)
				var lineChartData = jQuery.extend(true, {}, areaChartData)
				lineChartData.datasets[0].fill = false;
				lineChartData.datasets[1].fill = false;
				lineChartOptions.datasetFill = false
				
				var lineChart = new Chart(lineChartCanvas, { 
					type: 'line',
					data: lineChartData, 
					options: lineChartOptions
				})

			    var barChartCanvas = $('#barChart').get(0).getContext('2d')
			    var barChartData = jQuery.extend(true, {}, areaChartData)
			    var temp0 = areaChartData.datasets[0]
			    var temp1 = areaChartData.datasets[1]
			    barChartData.datasets[0] = temp1
			    barChartData.datasets[1] = temp0

			    var barChartOptions = {
			      responsive              : true,
			      maintainAspectRatio     : false,
			      datasetFill             : false
			    }

			    var barChart = new Chart(barChartCanvas, {
			      type: 'bar', 
			      data: barChartData,
			      options: barChartOptions
			    })


			    var areaChartDataAll = {
					labels  : data.responseJSON.penjualan.label,
					datasets: [
						{
							label               : 'Penjualan',
							backgroundColor     : 'rgba(251, 140, 0, 1)',
							borderColor         : 'rgba(251, 140, 0, 1)',
							pointRadius         : false,
							pointColor          : 'rgba(251, 140, 0, 1)',
							pointStrokeColor    : '#e65100',
							pointHighlightFill  : '#fff',
							pointHighlightStroke: 'rgba(251, 140, 0, 1)',
							data                : data.responseJSON.penjualan.values
						},	
						{
							label               : 'Pembelian',
							backgroundColor     : 'rgba(0, 151, 167,0.9)',
							borderColor         : 'rgba(0, 151, 167,0.8)',
							pointRadius          : false,
							pointColor          : '#006064',
							pointStrokeColor    : 'rgba(0, 151, 167,1)',
							pointHighlightFill  : '#fff',
							pointHighlightStroke: 'rgba(0, 151, 167,1)',
							data                : data.responseJSON.pembelian.values,
						},
						{
							label               : 'Transfer Keluar',
							backgroundColor     : 'rgba(0, 121, 107,0.9)',
							borderColor         : 'rgba(0, 121, 107,0.8)',
							pointRadius          : false,
							pointColor          : '#d81b60',
							pointStrokeColor    : 'rgba(0, 121, 107,1)',
							pointHighlightFill  : '#fff',
							pointHighlightStroke: 'rgba(0, 121, 107,1)',
							data                : data.responseJSON.transfer_keluar.values,
						},
						{
							label               : 'Transfer Masuk',
							backgroundColor     : 'rgba(2, 136, 209, 1)',
							borderColor         : 'rgba(2, 136, 209, 1)',
							pointRadius         : false,
							pointColor          : 'rgba(2, 136, 209, 1)',
							pointStrokeColor    : '#0277bd',
							pointHighlightFill  : '#fff',
							pointHighlightStroke: 'rgba(2, 136, 209, 1)',
							data                : data.responseJSON.transfer_masuk.values
						},
						/*{
						label               : 'Transfer Keluar',
						backgroundColor     : 'rgba(216, 27, 96,0.9)',
						borderColor         : 'rgba(216, 27, 96,0.8)',
						pointRadius          : false,
						pointColor          : '#d81b60',
						pointStrokeColor    : 'rgba(216, 27, 96,1)',
						pointHighlightFill  : '#fff',
						pointHighlightStroke: 'rgba(216, 27, 96,1)',
						data                : data.responseJSON.values,
						},*/
						
					]
				}


			    var barChartCanvasAll = $('#barChartAll').get(0).getContext('2d')
			    var barChartDataAll = jQuery.extend(true, {}, areaChartDataAll)
			    var temp0All = areaChartDataAll.datasets[0]
			    var temp1All = areaChartDataAll.datasets[1]
			    var temp2All = areaChartDataAll.datasets[2]
			    var temp3All = areaChartDataAll.datasets[3]
			    barChartDataAll.datasets[0] = temp0All
			    barChartDataAll.datasets[1] = temp1All
			    barChartDataAll.datasets[2] = temp2All
			    barChartDataAll.datasets[3] = temp3All

			    var barChartOptionsAll = {
			      responsive              : true,
			      maintainAspectRatio     : false,
			      datasetFill             : false
			    }

			    var barChartAll = new Chart(barChartCanvasAll, {
			      type: 'bar', 
			      data: barChartDataAll,
			      options: barChartOptionsAll
			    })

                spinner.hide();
            },
            error: function(data) {
                alert("error ajax occured!");
                // done_load();
            }
        });
	}
</script>
@endsection
