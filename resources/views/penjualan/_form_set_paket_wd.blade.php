<div class="row">
    <div class="col-sm-12">
        <div class="card card-info card-outline">
            <div class="card-body">
                <div class="row">
                    <div class="form-group col-md-9">
                        <input type="hidden" name="harga_wd_awal" id="harga_wd_awal" value="{{ $harga_wd }}">
                        {!! Form::label('id_paket_wd_p', 'Pilih Paket') !!}
                        <select id="id_paket_wd_p" name="id_paket_wd_p" class="form-control required input_select">
                            <option value="">--Pilih Paket--</option>
                            <?php $no = 0; ?>
                            @foreach( $pakets as $paket )
                                <?php $no = $no+1; ?>
                                <option value="{{ $paket->id }}" data-nama="{{ $paket->nama }}" data-harga="{{ $paket->harga }}" {!!( $paket->id == $id_paket_wd ? 'selected' : '')!!}>{{ $paket->nama }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="nama_paket" id="nama_paket" value="">
                    </div>
                    <div class="form-group col-md-3">
                        {!! Form::label('harga_wd_p', 'Harga Paket') !!}
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            {!! Form::hidden('harga_total_value', $harga_total, array('class' => 'form-control required', 'id' => 'harga_total_value')) !!}
                            {!! Form::text('harga_wd_p', $harga_wd, array('id' => 'harga_wd_p', 'class' => 'form-control', 'placeholder'=>'Harga Paket', 'readonly' => 'readonly')) !!}
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
</div>

<script type="text/javascript">
	$(document).ready(function(){
		$("#table_obat").DataTable();

        $('#id_paket_wd_p').on('select2:select', function (e) {
            harga = $(this).find(':selected').data('harga');
            nama_paket = $(this).find(':selected').data('nama');
            $("#harga_wd_p").val(harga);
            $("#nama_paket").val(nama_paket);
        });

        $('.input_select').select2();
	})

	function set_data(obj){
		id_paket_wd = $("#id_paket_wd_p").val();
	    harga_wd = parseFloat($("#harga_wd_p").val());
	    harga_total_awal = $("#harga_total_value").val();
        nama_paket = $("#nama_paket").val();

        var harga_wd_awal = $("#harga_wd_awal").val();
        if(harga_wd_awal == "") {
            harga_wd_awal = 0;
        }
	    
	    $("#id_paket_wd").val(id_paket_wd);
	    $("#id_paket_wd_input").html("Paket WD : "+nama_paket);
        var harga_wd_rp = hitung_rp(harga_wd);
	    $("#harga_wd_input").html(harga_wd);
	    $("#harga_wd").val(harga_wd);

        total_byr = parseFloat(harga_total_awal);
        if(harga_wd_awal != 0) {
            total_byr = parseFloat(total_byr) - parseFloat(harga_wd_awal);
        } 
        total_byr = parseFloat(total_byr) + parseFloat(harga_wd); 
        var total_byr_rp = hitung_rp(total_byr);

        $("#total_pembayaran").html(total_byr);
        $("#total_pembayaran_input").val(total_byr);
        $("#total_pembayaran_display").html("Rp "+ total_byr_rp +", -");
        $("#count_total_belanja").val(total_byr);
	    $('#modal-xl').modal("hide");
	}
</script>