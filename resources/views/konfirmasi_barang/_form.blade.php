@if (count( $errors) > 0 )
    <div class="alert alert-danger">
        @foreach ($errors->all() as $error)
            {{ $error }}<br>        
        @endforeach
    </div>
@endif
<style type="text/css">
    .select2 {
      width: 100%!important; /* overrides computed width, 100px in your demo */
    }
</style>
<div class="row">
	<div class="form-group col-md-6">
        {!! Form::label('id_nota', 'No. SP') !!}
        <select id="id_nota" name="id_nota" class="form-control input_select">
            <option value="">------ Pilih SP -----</option>
            <?php $no = 0; ?>
            @foreach( $orders as $order )
                <?php $no = $no+1; ?>
                <option value="{{ $order->id }}">SP - {{ $order->id }} - {{ $order->nama_suplier }} ({{ $order->tgl_nota }}) </option>
            @endforeach
        </select>
        {!! Form::hidden('is_from_order', 1, array('class' => 'form-control', 'id'=>'is_from_order')) !!}
    </div><!-- 
    <div class="col-lg-12">
        <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Proses</button> 
    </div> -->

    <div class="form-group col-md-12">
    	<div class="box box-success" id="detail_data_penjualan">
		    <div class="box-body">
		        <table  id="tb_data_obat" class="table table-bordered table-striped table-hover">
		            <thead>
		                <tr>
		                	<th width="1%"><input type="checkbox" class="checkAlltogle"></th>
		                    <th width="5%">No.</th>
		                    <th width="75%">ID Obat</th>
		                    <th width="10%">Jumlah</th>
		                    <th width="10%">Action</th>
		                </tr>
		            </thead>
		            <tbody>
		            </tbody>
		        </table>

		    </div>
		</div>
    </div>
</div>