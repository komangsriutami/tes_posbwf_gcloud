@extends('layout.app')

@section('title')
List Data Order
@endsection

@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item"><a href="#">Transaksi</a></li>
    <li class="breadcrumb-item"><a href="#">List Data Order</a></li>
    <li class="breadcrumb-item active" aria-current="page">Index</li>
</ol>
@endsection

@section('content')
	<style type="text/css">
		.select2 {
		  width: 100%!important; /* overrides computed width, 100px in your demo */
		}
	</style>

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
				    {!! Form::select('id_apotek', $apoteks, null, ['id'=>'id_apotek', 'class' => 'form-control input_select']) !!}
				</div>
                <div class="form-group col-md-4">
                    {!! Form::select('id_suplier', $supliers, null, ['id'=>'id_suplier', 'class' => 'form-control input_select']) !!}
                </div>
        	</div>
			<hr>
			<table  id="tb_data_order" class="table table-bordered table-striped table-hover">
		    	<thead>
			        <tr>
			        	<th width="2%"><input type="checkbox" class="checkAlltogle"></th>
			            <th width="3%">No.</th>
                        <th width="10%">Tanggal</th>
			            <th width="35%">Apotek</th>
                        <th width="35%">Suplier</th>
			            <th width="15%">Action</th>
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
            url: '{{url("order/list_data_order")}}',
		        data:function(d){
		        	d.id_apotek = $('#id_apotek').val();
                    d.id_suplier = $('#id_suplier').val();
		         }
        },
        order: [],
        columns: [
        	{data: 'checkList', name: 'checkList', orderable: false, searchable: false, width:'1%'},
         	{data: 'DT_RowIndex', name: 'DT_RowIndex',width:"2%"},
            {data: 'tgl_nota', name: 'tgl_nota', class:'text-center'},
            {data: 'apotek', name: 'apotek'},
            {data: 'suplier', name: 'suplier'},
            {data: 'action', name: 'id', orderable: true, searchable: true, class:'text-center'}
        ],
        drawCallback: function(callback) {
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
	})

    function delete_order(id){
        swal({
            title: "Apakah anda yakin menghapus data ini?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Ya",
            cancelButtonText: "Tidak",
            closeOnConfirm: false
        },
        function(){
            $.ajax({
                type: "DELETE",
                url: '{{url("order")}}/'+id,
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
                        swal("Deleted!", "Data order berhasil dihapus.", "success");
                    }else{
                        swal("Failed!", "Gagal menghapus data order.", "error");
                    }
                },
                complete: function(data){
                    tb_data_order.draw(false);
                },
                error: function(data) {
                    swal("Error!", "Ajax occured.", "error");
                }
            });
        });
    }
</script>
@endsection