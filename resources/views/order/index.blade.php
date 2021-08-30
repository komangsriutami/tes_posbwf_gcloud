@extends('layout.app')

@section('title')
Data Order
@endsection

@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item"><a href="#">Transaksi</a></li>
    <li class="breadcrumb-item"><a href="#">Data Order</a></li>
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

	<div class="card card-info card-outline mb-12 border-left-primary">
	    <div class="card-body">
	      	<h4><i class="fa fa-info"></i> Informasi</h4>
	      	<p>Untuk pencarian, isikan kata yang ingin dicari pada kolom search, lalu tekan enter.</p>
	    </div>
	</div>

	<div class="card card-info card-outline" id="main-box" style="">
  		<div class="card-header">
        	<h3 class="card-title">
          		<i class="fas fa-list"></i>
   				List Data Order
        	</h3>
      	</div>
        <div class="card-body">
        	<div class="row">
                <div class="form-group col-md-2">
                    <select id="id_proses" name="id_proses" class="form-control input_select">
                        <option value="0" {!!( "0" == $status_order_aktif ? 'selected' : '')!!}>Belum Diproses</option>
                        <option value="1" {!!( "1" == $status_order_aktif ? 'selected' : '')!!}>Proses</option>
                        <option value="2" {!!( "2" == $status_order_aktif ? 'selected' : '')!!}>Complete</option>
                    </select>
                </div>
				<div class="form-group col-md-2">
				    {!! Form::select('id_apotek', $apoteks, $apotek_order_aktif, ['id'=>'id_apotek', 'class' => 'form-control input_select']) !!}
				</div>
                <div class="form-group col-md-4">
                    {!! Form::select('id_suplier', $supliers, $suplier_order_aktif, ['id'=>'id_suplier', 'class' => 'form-control input_select']) !!}
                </div>
        	</div>
			<hr>
			<table  id="tb_data_order" class="table table-bordered table-striped table-hover">
		    	<thead>
			        <tr>
			        	<th width="1%"><input type="checkbox" class="checkAlltogle"></th>
			            <th width="3%">No.</th>
                        <th width="8%">Apotek</th>
			            <th width="40%">Nama Obat</th>
                        <th width="23%">Suplier</th>
			            <th width="8%">Total Stok</th>
			            <th width="8%">Total Buffer</th>
			            <th width="8%">Forcasting</th>
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
 	

 	var tb_data_order = $('#tb_data_order').DataTable( {
		paging:true,
		destroy: true,
        autoWidth: false,
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{url("order/list_order")}}',
		        data:function(d){
		        	d.id_apotek = $('#id_apotek').val();
                    d.id_suplier = $('#id_suplier').val();
		         }
        },
        order: [],
        columns: [
        	{data: 'checkList', name: 'checkList', orderable: false, searchable: false, width:'1%'},
         	{data: 'DT_RowIndex', name: 'DT_RowIndex',width:"2%"},
            {data: 'apotek', name: 'apotek', class:'text-center'},
            {data: 'nama', name: 'nama'},
            {data: 'id_suplier_order', name: 'id_suplier_order'},
            {data: 'total_stok', name: 'total_stok', class:'text-center'},
            {data: 'total_buffer', name: 'total_buffer', class:'text-center'},
            {data: 'forcasting', name: 'forcasting', class:'text-center'}
        ],
        drawCallback: function(callback) {
            $("#btn_set").html(callback['jqXHR']['responseJSON']['btn_set']);
            //console.log(callback['jqXHR']['responseJSON']['btn_set'])
        }
	});


 	setTimeout(function(){
        $('#tb_data_order .checkAlltogle').prop('checked', false);
    }, 1);

	$(document).ready(function(){
		$("#searching_form").submit(function(e){
			e.preventDefault();
			tb_data_order.draw(false);
		});

        $('.input_select').select2({});

		$('#id_apotek').change(function(){
            set_apotek_order_aktif(); 
        });

        $('#id_suplier').change(function(){
            set_suplier_order_aktif(); 
        });

        $('#id_proses').change(function(){
            set_status_order_aktif(); 
        });
	})

    function set_apotek_order_aktif() {
        $.ajax({
            url:'{{url("order/set_apotek_order_aktif")}}',
            type: 'POST',
            data: {
                _token      : "{{csrf_token()}}",
                id_apotek: $('#id_apotek').val()
            },
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success:function(data){
                tb_data_order.draw(false);
            }
        });
    }

    function set_suplier_order_aktif() {
        $.ajax({
            url:'{{url("order/set_suplier_order_aktif")}}',
            type: 'POST',
            data: {
                _token      : "{{csrf_token()}}",
                id_suplier: $('#id_suplier').val()
            },
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success:function(data){
                tb_data_order.draw(false);
            }
        });
    }

    function set_status_order_aktif() {
        $.ajax({
            url:'{{url("order/set_status_order_aktif")}}',
            type: 'POST',
            data: {
                _token      : "{{csrf_token()}}",
                id_status : $('#id_proses').val()
            },
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success:function(data){
                tb_data_order.draw(false);
            }
        });
    }

    function set_nota_order(){
        if ($("#tb_data_order input:checkbox[name=check_list]:checked").length > 0) {
            var arr_id_defecta = [];
            var arr_id_apotek = [];
            var arr_id_suplier = [];
            $("#tb_data_order input:checkbox[name=check_list]:checked").each(function(){
                arr_id_defecta.push($(this).data('id'));
                arr_id_apotek.push($(this).data('id_apotek'));
                arr_id_suplier.push($(this).data('id_suplier'));
            })
    
            var url = '{{url("order/set_nota_order")}}';
            var form = $('<form action="' + url + '" method="post" id="form_order">' +
                        '<input type="hidden" name="_token" id="_token" value="{{csrf_token()}}">' +
                        '<input type="hidden" name="id_defecta" value="'+ arr_id_defecta +'" />' +
                        '<input type="hidden" name="id_apotek" value="'+ arr_id_apotek +'" />' +
                        '<input type="hidden" name="id_suplier" value="'+ arr_id_suplier +'" />' +
              '</form>');
            $('body').append(form);
            form_order.submit();
        }
        else{
            swal({
                title: "Warning",
                text: "centang data terlebih dahulu!",
                type: "error",
                timer: 5000,
                showConfirmButton: false
            });
        }
    }
</script>
@endsection