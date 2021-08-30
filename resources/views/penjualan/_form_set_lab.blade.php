<div class="row">
    <div class="col-sm-12">
        <div class="card card-info card-outline">
            <div class="card-body">
                <div class="row">
                    <input type="hidden" name="biaya_lab_awal" id="biaya_lab_awal" value="{{ $biaya_lab }}">
                    <div class="form-group col-md-3">
                        {!! Form::label('nama_lab_p', 'Lab (*)') !!}
                        {!! Form::text('nama_lab_p', $nama_lab, array('id' => 'nama_lab_p', 'class' => 'form-control', 'placeholder'=>'Nama Lab', 'autocomplete' => 'off')) !!}
                    </div>
                    <div class="form-group col-md-3">
                        {!! Form::label('biaya_lab_p', 'Biaya Lab  (*)') !!}
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            {!! Form::hidden('harga_total_value', $harga_total, array('class' => 'form-control required', 'id' => 'harga_total_value')) !!}
                            {!! Form::text('biaya_lab_p', $biaya_lab, array('id' => 'biaya_lab_p', 'class' => 'form-control', 'placeholder'=>'Biaya Lab', 'autocomplete' => 'off')) !!}
                        </div>
                    </div>
                     <div class="form-group col-md-6">
                        {!! Form::label('keterangan_lab_p', 'Keterangan/Catatan  (*)') !!}
                        {!! Form::text('keterangan_lab_p', $keterangan_lab, array('id' => 'keterangan_lab_p', 'class' => 'form-control', 'placeholder'=>'Keterangan/Catatan', 'autocomplete' => 'off')) !!}
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

        $('.input_select').select2();

        $("#nama_lab_p").keypress(function(event){
            if (event.which == '10' || event.which == '13') {
                $("#biaya_lab_p").focus();
                event.preventDefault();
            }
        });

        $("#biaya_lab_p").keypress(function(event){
            if (event.which == '10' || event.which == '13') {
                $("#keterangan_lab_p").focus();
                event.preventDefault();
            }
        });

        $("#keterangan_lab_p").keypress(function(event){
            if (event.which == '10' || event.which == '13') {
                set_data(this);
            }
        });
	})

	function set_data(obj){
		nama_lab = $("#nama_lab_p").val();
	    biaya_lab = parseFloat($("#biaya_lab_p").val());
        keterangan_lab = $("#keterangan_lab_p").val();
        harga_total_awal = $("#harga_total_value").val();

        var biaya_lab_awal = $("#biaya_lab_awal").val();
        if(biaya_lab_awal == "") {
            biaya_lab_awal = 0;
        }
	    
	    $("#nama_lab_input").val(nama_lab);
	    $("#nama_lab_input").html("Laboratorium : "+nama_lab);
        var biaya_lab_rp = hitung_rp(biaya_lab);
	    $("#biaya_lab_input").html(biaya_lab);
	    $("#biaya_lab").val(biaya_lab);
        $("#nama_lab").val(nama_lab);
        $("#keterangan_lab").val(keterangan_lab);
        
        total_byr = parseFloat(harga_total_awal);
        if(biaya_lab_awal != 0) {
            total_byr = parseFloat(total_byr) - parseFloat(biaya_lab_awal);
        } 
        total_byr = parseFloat(total_byr) + parseFloat(biaya_lab); 
        var total_byr_rp = hitung_rp(total_byr);

        $("#total_pembayaran").html(total_byr);
        $("#total_pembayaran_input").val(total_byr);
        $("#total_pembayaran_display").html("Rp "+ total_byr_rp +", -");
        $("#count_total_belanja").val(total_byr);
	    $('#modal-xl').modal("hide");
	}
</script>