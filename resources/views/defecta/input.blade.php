@extends('layout.app')

@section('title')
Input Defecta
@endsection

@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item"><a href="#">Transaksi</a></li>
    <li class="breadcrumb-item"><a href="#">Input Defecta</a></li>
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
	      	<p>Untuk pencarian, isikan kata yang ingin dicari pada kolom seacrh, lalu tekan enter.</p>
	      	<p>Data Defacta dihitung terakhir tanggal : {{ $last_hitung }}</p>
	      	<a class="btn btn-info w-md m-b-5" href="{{url('defecta/hitung')}}"><i class="fa fa-calculator"></i> Hitung</a>
	    </div>
	</div>

	<div class="card card-info card-outline" id="main-box" style="">
  		<div class="card-header">
        	<h3 class="card-title">
          		<i class="fas fa-list"></i>
   				List Data Defecta
        	</h3>
      	</div>
        <div class="card-body">
			<table  id="tb_data_obat" class="table table-bordered table-striped table-hover">
		    	<thead>
			        <tr>
			            <th width="5%">No.</th>
			            <th width="10%">ID Obat</th>
			            <th width="45%">Nama Obat</th>
			            <th width="10%">Total Stok</th>
			            <th width="10%">Total Buffer</th>
			            <th width="10%">Forcasting</th>
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
 	

 	var tb_data_obat = $('#tb_data_obat').DataTable( {
		paging:true,
		destroy: true,
        autoWidth: false,
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{url("defecta/list_defecta_input")}}',
		        data:function(d){
		        	//d.s_sudah_diinput = $('#s_sudah_diinput').val();
		        	//d.nama = $('#nama').val();
		         }
        },
        order: [],
        columns: [
         	{data: 'DT_RowIndex', name: 'DT_RowIndex',width:"2%"},
            {data: 'id', name: 'id'},
            {data: 'nama', name: 'nama'},
            {data: 'total_stok', name: 'total_stok'},
            {data: 'total_buffer', name: 'total_buffer'},
            {data: 'forcasting', name: 'forcasting'},
            {data: 'action', name: 'id', orderable: true, searchable: true}
        ],
        drawCallback: function(callback) {
        }
	});

	$(document).ready(function(){
		$("#searching_form").submit(function(e){
			e.preventDefault();
			tb_data_obat.draw(false);
		})

		$('#s_sudah_diinput').change(function(){
            tb_data_obat.draw(false);
        });
	})

	// ini untuk versi yang baru
	function add_defecta(id, id_defecta){
	    $.ajax({
	        type: "POST",
	        url: '{{url("defecta/add_defecta")}}',
	        async:true,
	        data: {
	        	_token		: "{{csrf_token()}}",
	        	id_stok_harga:id,
	        	id_defecta:id_defecta,
	        	is_purchasing: 0
	        },
	        beforeSend: function(data){
		        // on_load();
		        $('#modal-xl').find('.modal-xl').find(".modal-content").find(".modal-header").attr("class","modal-header bg-light-blue");
		        $("#modal-xl .modal-title").html("Input Defecta");
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

	function submit_valid(id){
		if($(".validated_form").valid()) {
			data = {};
			$("#form-edit").find("input[name], select").each(function (index, node) {
		        data[node.name] = node.value;
    			
		    });

			$.ajax({
				type:"PUT",
				url : '{{url("defecta/")}}/'+id,
				dataType : "json",
				data : data,
				beforeSend: function(data){
					// replace dengan fungsi loading
				},
				success:  function(data){
					if(data.status ==1){
						show_info("Data defecta berhasil disimpan!");
						$('#modal-large').modal('toggle');
					}else{
						show_error("Gagal menyimpan data ini!");
						return false;
					}
				},
				complete: function(data){
					// replace dengan fungsi mematikan loading
					tb_data_obat.draw(false);
				},
				error: function(data) {
					show_error("error ajax occured!");
				}

			})
		} else {
			return false;
		}
	}

	function proses_generate_order(id){
		$.ajax({
		    type: "POST",
		    url: '{{url("keputusan_order/proses_generate_order")}}/'+id,
		    async:true,
		    data: {
		    	_token:"{{csrf_token()}}",
		    	id_apotek:$("#search_id_apotek").val()

		    },
		    beforeSend: function(data){
		      // on_load();
			    $('#modal-large').find('.modal-lg').find(".modal-content").find(".modal-header").attr("class","modal-header bg-light-blue");
			    $("#modal-large .modal-title").html("Generate Data");
			    $('#modal-large').modal("show");
			    $('#modal-large').find('.modal-body-content').html('');
			    $("#modal-large").find(".overlay").fadeIn("200");
		    },
		    success:  function(data){
		      	$('#modal-large').find('.modal-body-content').html(data);
		    },
		    complete: function(data){
		        $("#modal-large").find(".overlay").fadeOut("200");
		    },
		    error: function(data) {
		        alert("error ajax occured!");
		    }
		});
	}
</script>
@endsection