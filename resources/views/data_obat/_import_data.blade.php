<div class="row">
	<div class="col-xs-12">
      	<div class="box box-primary" id="main-box" style="">
      		<div class="box-header">
			    <div class="callout callout-info">
			        <h4><i class="fa fa-info"></i> Informasi </h4>
			        <p><cite>Catatan : tanda (*) merupakan kolom yang wajib diisikan.</cite></p>
			    </div>
			</div>
            <div class="box-body">
            {!! Form::model(new App\MasterObat, ['route' => ['data_obat.import_obat_to_excel'], 'class'=>'validated_form', 'files'=> true]) !!}
            	<div class="form-group  col-md-12">
	            	<input type="file" name="import_file" />
	            </div>
	            <div class="form-group  col-md-12">
					<button class="btn btn-primary" onclick="move()">Import File </button>
					<span class="loader" style="display: none;color: #ef6c00;"> | Import data reviewers diproses......</span>
				</div>
			{!! Form::close() !!}
			</div>
      	</div>
     </div>
</div>

<style type="text/css">
	#tb_pertanyaan_filter{
		display: none;
	}
</style>
<script type="text/javascript">
	var token = "";

	$(document).ready(function(){
		token = $('input[name="_token"]').val();
	})

	function move() {
		$(".loader").show();
	    $(".validated_form").submit();
	}
</script>