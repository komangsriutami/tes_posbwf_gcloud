<div class="row">
    <div class="col-sm-12">
        <div class="card card-info card-outline">
            <div class="card-body">
                <div class="row">
                	<input type="hidden" name="diskon_total_awal" id="diskon_total_awal" value="{{ $diskon_total }}">
                	<div class="form-group col-md-8">
					    {!! Form::label('id_karyawan_p', 'Pilih Karyawan') !!}
					    <select id="id_karyawan_p" name="id_karyawan_p" class="form-control required input_select">
                            <option value="">--Pilih Karyawan --</option>
                            <?php $no = 0; ?>
                            @foreach( $karyawans as $karyawan)
                                <?php $no = $no+1; ?>
                                <option value="{{ $karyawan->id }}" data-nama="{{ $karyawan->nama }}" {!!( $karyawan->id == $id_karyawan ? 'selected' : '')!!}>{{ $karyawan->nama }}</option>
                            @endforeach
                        </select>
					    <input type="hidden" name="nama_karyawan" id="nama_karyawan" value="">
					</div>
					<div class="form-group col-md-4">
					    {!! Form::label('diskon_p', 'Diskon Karyawan (%)') !!}
					    <div class="input-group"> 
					    	{!! Form::hidden('harga_total_value', $harga_total, array('class' => 'form-control', 'id' => 'harga_total_value')) !!}
					    	{!! Form::hidden('total_penjualan_value', $total_penjualan, array('class' => 'form-control', 'id' => 'total_penjualan_value')) !!}
					    	{!! Form::hidden('diskon_total_value', $diskon_total, array('class' => 'form-control', 'id' => 'diskon_total_value')) !!}
					        {!! Form::text('diskon_p', $diskon_persen, array('class' => 'form-control required', 'placeholder'=>'Masukan Diskon', 'autocomplete' => 'off')) !!}
					        <div class="input-group-prepend">
				                <span class="input-group-text">%</span>
				            </div>
					    </div>
					</div>
				</div>
			</div>
			<div class="card-footer">
				<div class="row">
					<div class="form-group col-md-12">
						<button class="btn btn-success btn-sm" type="button" onClick="set_data(this)" data-toggle="tooltip" data-placement="top" title="Simpan"><i class="fa fa-save"></i> Simpan</button>
                		<button type="button" class="btn btn-danger btn-sm" data-dismiss="modal"><i class="fa fa-undo"></i> Kembali</button>
					</div>
				</div>
            </div>
		</div>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function(){
		$('#id_karyawan_p').on('select2:select', function (e) {
            nama_karyawan = $(this).find(':selected').data('nama');
            $("#nama_karyawan").val(nama_karyawan);
            $("#diskon_p").focus();
        });

        $("#diskon_p").keypress(function(event){
            if (event.which == '10' || event.which == '13') {
                set_data(this);
                event.preventDefault();
            }
        });

        $('.input_select').select2();
	})

	function set_data(obj){
	    diskon_persen = $("#diskon_p").val();
	    id_karyawan = $("#id_karyawan_p").val();
	    harga_total_awal = $("#harga_total_value").val();
	    total_penjualan = $("#total_penjualan_value").val();
	 
	    nama_karyawan = $("#nama_karyawan").val();
	    var diskon_total_awal = $("#diskon_total_awal").val();
        if(diskon_total_awal == "") {
            diskon_total_awal = 0;
        }

	    hitung = ((parseFloat(diskon_persen))/100) * parseFloat(total_penjualan);
	    hitung_diskon = parseFloat(hitung);
	    $("#diskon_persen").val(diskon_persen);
	    $("#id_karyawan").val(id_karyawan);
	    $("#diskon_persen_input").html("Karyawan : "+nama_karyawan);
	    var diskon_total_rp = hitung_rp_khusus(hitung_diskon);
	    $("#diskon_total").html(hitung_diskon);
	    $("#diskon_total_input").val(hitung_diskon);

	    total_byr = parseFloat(harga_total_awal);
        if(diskon_total_awal != 0) {
            total_byr = parseFloat(total_byr) + parseFloat(diskon_total_awal);
        } 
        total_byr = parseFloat(total_byr) - parseFloat(hitung_diskon); 
	    var total_byr_rp = hitung_rp_khusus(total_byr);
        $("#total_pembayaran").html(total_byr);
        $("#total_pembayaran_input").val(total_byr);
        $("#total_pembayaran_display").html("Rp "+ total_byr_rp +", -");
        $("#count_total_belanja").val(total_byr);
	    $('#modal-xl').modal("hide");
	}
</script>