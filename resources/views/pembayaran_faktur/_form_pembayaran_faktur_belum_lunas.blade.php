@extends('layout.app')

@section('title')
Data Pembayaran Faktur
@endsection

@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item"><a href="#">Keuangan</a></li>
    <li class="breadcrumb-item"><a href="#">Data Pembayaran Faktur</a></li>
    <li class="breadcrumb-item active" aria-current="page">Index</li>
</ol>
@endsection

@section('content')
	<style type="text/css">
		.select2 {
		  width: 100%!important; /* overrides computed width, 100px in your demo */
		}
	</style>
    <style type="text/css">
        #divfix {
           bottom: 0;
           right: 0;
           position: fixed;
           z-index: 3000;
            }
        .format_total {
            font-size: 18px;
            font-weight: bold;
            color:#D81B60;
        }
    </style>
    <div class="row" id="divfix">
        <div class="col-sm-12">
            <div class="callout callout-success">
                <div id="btn_set" style="display: inline-block;"></div>
            </div>
        </div>
    </div>

	<!-- <div class="card card-info card-outline mb-12 border-left-primary">
	    <div class="card-body">
	      	<h4><i class="fa fa-info"></i> Informasi</h4>
	      	<p>Untuk pencarian, isikan kata yang ingin dicari pada kolom search, lalu tekan enter.</p>
	    </div>
	</div> -->

	<div class="card card-info card-outline" id="main-box" style="">
  		<div class="card-header">
        	<h3 class="card-title">
          		<i class="fas fa-list"></i>
   				List Data Pembelian
        	</h3>
      	</div>
        <div class="card-body">
            <form role="form" id="searching_form">
            	<div class="row">
                    <div class="col-lg-6 form-group">
                        <label>Apotek</label>
                        <select id="id_apotek" name="id_apotek" class="form-control input_select">
                            <option value="">------Pilih Apotek-----</option>
                            <?php $no = 0; ?>
                            @foreach( $apoteks as $apotek )
                                <?php $no = $no+1; ?>
                                <option value="{{ $apotek->id }}">{{ $apotek->nama_panjang }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-6 form-group">
                        <label>Suplier</label>
                        <select id="id_suplier" name="id_suplier" class="form-control input_select">
                            <option value="">------Pilih Suplier-----</option>
                            <?php $no = 0; ?>
                            @foreach( $supliers as $suplier )
                                <?php $no = $no+1; ?>
                                <option value="{{ $suplier->id }}">{{ $suplier->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group  col-md-6">
                        <label>Dari Tanggal</label>
                        <input type="text" name="tgl_awal"  id="tgl_awal" class="datepicker form-control">
                    </div>
                    <div class="form-group  col-md-6">
                        <label>Sampai Tanggal</label>
                        <input type="text" name="tgl_akhir" id="tgl_akhir" class="datepicker form-control">
                    </div>
                    <div class="col-lg-12" style="text-align: center;">
                        <button type="submit" class="btn btn-primary" id="datatable_filter"><i class="fa fa-search"></i> Cari</button> 
                        <span class="btn bg-maroon" onClick="export_pembayaran_faktur()"  data-toggle="modal" data-placement="top" title="Export Pembayaran Faktur"><i class="fa fa-print" aria-hidden="true"></i> Cetak</span>
                    </div>
            	</div>
            </form>
			<hr>
			<table  id="tb_faktur" class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th width="5%">No.</th>
                        <th width="6%">ID</th>
                        <th width="15%">No Faktur</th>
                        <th width="10%">Tgl Faktur</th>
                        <th width="10%">Jatuh Tempo</th>
                        <th width="20%">Apotek</th>
                        <th width="20%">Suplier</th>
                        <th width="10%">Jumlah</th>
                        <th width="8%">Jenis Pembelian</th>
                        <th width="8%">Status</th>
                        <th width="10%">Action</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
  	</div>
@endsection

@section('script')
<script type="text/javascript">
	var token = '{{csrf_token()}}';
 	

 	var tb_faktur = $('#tb_faktur').DataTable( {
		paging:true,
		destroy: true,
        autoWidth: false,
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{url("pembelian/list_pembayaran_faktur_belum_lunas")}}',
		        data:function(d){
		        	d.id_apotek = $('#id_apotek').val();
                    d.id_suplier = $('#id_suplier').val();
                    d.id_status_lunas = $('#id_status_lunas').val();
                    d.tgl_awal = $('#tgl_awal').val();
                    d.tgl_akhir = $('#tgl_akhir').val();
		         }
        },
        order: [],
        columns: [
        	{data: 'no', name: 'no',width:"2%"},
            {data: 'id', name: 'id'},
            {data: 'no_faktur', name: 'no_faktur'},
            {data: 'tgl_faktur', name: 'tgl_faktur'},
            {data: 'tgl_jatuh_tempo', name: 'tgl_jatuh_tempo'},
            {data: 'apotek', name: 'apotek'},
            {data: 'suplier', name: 'suplier'},
            {data: 'jumlah', name: 'jumlah'},
            {data: 'jenis_pembelian', name: 'jenis_pembelian'},
            {data: 'is_lunas', name: 'is_lunas'},
            {data: 'action', name: 'id',orderable: false, searchable: false, class:'text-center'}
        ],
        drawCallback: function(callback) {
            $("#btn_set").html(callback['jqXHR']['responseJSON']['btn_set']);
            //console.log(callback['jqXHR']['responseJSON']['btn_set'])
        }
	});


 	setTimeout(function(){
        $('#tb_faktur .checkAlltogle').prop('checked', false);
    }, 1);

	$(document).ready(function(){
		$("#searching_form").submit(function(e){
			e.preventDefault();
			tb_faktur.draw(false);
		});

        $('.input_select').select2({});

		$('#tgl_awal, #tgl_akhir').datepicker({
            autoclose:true,
            format:"yyyy-mm-dd",
            forceParse: false
        });
	})


    function lunas_pembayaran(id){
        swal({
            title: "Apakah anda yakin sudah melakukan pembayaran faktur ini?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Ya",
            cancelButtonText: "Tidak",
            closeOnConfirm: false
        },
        function(){
            $.ajax({
                type: "POST",
                url: '{{url("pembelian/lunas_pembayaran")}}',
                async:true,
                data: {
                    _token:token,
                    id:id
                },
                beforeSend: function(data){
                    // replace dengan fungsi loading
                },
                success:  function(data){
                    if(data==1){
                        swal("Success!", "Pembayaran Faktur ini berhasil diset menjadi lunas.", "success");
                    }else{
                        
                        swal("Failed!", "Gagal menyimpan data.", "error");
                    }
                },
                complete: function(data){
                    tb_faktur.draw(false);
                },
                error: function(data) {
                    swal("Error!", "Ajax occured.", "error");
                }
            });
        });
    }

    function lihat_detail_faktur(id){

        $.ajax({
            type: "POST",
            url: '{{url("pembelian/lihat_detail_faktur")}}',
            async:true,
            data: {
                _token:"{{csrf_token()}}",
                id:id,
            },
            beforeSend: function(data){
                // on_load();
                $('#modal-xl').find('.modal-xl').find(".modal-content").find(".modal-header").attr("class","modal-header bg-light-blue");
                $("#modal-xl .modal-title").html("Detail Faktur");
                $('#modal-xl').modal("show");
                $('#modal-xl').find('.modal-body-content').html('');
                $("#modal-xl").find(".overlay").fadeIn("200");
            },
                success:  function(data){
                $('#modal-xl').find('.modal-body-content').html(data);
            },
                complete: function(data){
                $("#modal-xl").find(".overlay").fadeOut("200");
            },
                error: function(data) {
                alert("error ajax occured!");
            }

        });
    }

    function export_pembayaran_faktur(){
        window.open("{{ url('pembelian/export') }}"+ "?id_apotek="+$('#id_apotek').val()+"&id_suplier="+$('#id_suplier').val()+"&id_status_lunas=0"+"&tgl_awal="+$('#tgl_awal').val()+"&tgl_akhir="+$('#tgl_akhir').val(),"_blank");
    }
</script>
@endsection