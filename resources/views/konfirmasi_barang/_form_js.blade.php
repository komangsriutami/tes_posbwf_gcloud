<script type="text/javascript">
	var token = "";

	var tb_data_obat = $('#tb_data_obat').DataTable( {
		paging:true,
		destroy: true,
        autoWidth: false,
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{url("pembelian/list_data_order")}}',
		        data:function(d){
		        	d.id_nota = $('#id_nota').val();
		         }
        },
        order: [],
        columns: [
        	{data: 'checkList', name: 'checkList', orderable: false, searchable: false, width:'1%'},
        	{data: 'no', name: 'no',width:"2%", class:'text-center'},
            {data: 'id_obat', name: 'id_obat'},
            {data: 'jumlah', name: 'jumlah', class:'text-center'},
            {data: 'action', name: 'id',orderable: true, searchable: true}
        ],
        drawCallback: function(callback) {
            $("#btn_set").html(callback['jqXHR']['responseJSON']['btn_set']);
        }
	});

	$(document).ready(function(){
		token = $('input[name="_token"]').val();

		$('#id_nota').on('select2:select', function (e) {
			tb_data_obat.draw(false);
			//var checkedStatus = this.checked;
		    //$("input:checkbox").prop("checked", true);
        });

		$('.input_select').select2();
	})

	function goBack() {
	    window.history.back();
	}
</script>