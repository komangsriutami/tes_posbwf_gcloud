{!! Form::model($defecta, ['method' => 'PUT', 'class'=>'validated_form', 'id'=>'form-edit-detail', 'route' => ['order.update_defecta', $defecta->id]]) !!}
    <div class="row">
        <div class="col-sm-12">
            <div class="card card-info card-outline">
                <div class="card-body">
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
                    <input type="hidden" name="id_defecta" id="id_defecta" value="{{ $defecta->id }}">
                    <input type="hidden" name="no" id="no" value="{{ $no }}">
                    <div class="row">
                        <div class="form-group col-md-4">
                            {!! Form::label('apotek', 'Apotek') !!}
                            {!! Form::text('apotek', $apotek->nama_panjang, array('class' => 'form-control', 'placeholder'=>'Nama Apotek', 'readonly' => 'readonly')) !!}
                        </div>
                        <div class="form-group col-md-8">
                            {!! Form::label('obat', 'Nama Obat') !!}
                            {!! Form::text('obat', $defecta->nama, array('class' => 'form-control', 'placeholder'=>'Nama Apotek', 'readonly' => 'readonly')) !!}
                        </div>
                        <div class="form-group col-md-1">
                            {!! Form::label('stok', 'Stok') !!}
                            {!! Form::text('stok', $defecta->total_stok, array('id' => 'stok','class' => 'form-control required', 'placeholder'=>'Stok', 'readonly' => 'readonly')) !!}
                        </div>
                        <div class="form-group col-md-1">
                            {!! Form::label('buffer', 'Buffer') !!}
                            {!! Form::text('buffer', $defecta->total_buffer, array('id' => 'buffer','class' => 'form-control required', 'placeholder'=>'Buffer', 'readonly' => 'readonly')) !!}
                        </div>
                        <div class="form-group col-md-1">
                            {!! Form::label('forcasting', 'Forcasting') !!}
                            {!! Form::text('forcasting', $defecta->forcasting, array('id' => 'forcasting','class' => 'form-control required', 'placeholder'=>'Forcasting', 'readonly' => 'readonly')) !!}
                        </div>
                        <div class="form-group col-md-2">
                            {!! Form::label('jumlah_diajukan', 'Jumlah  Diajukan(*)') !!}
                            {!! Form::text('jumlah_diajukan', $defecta->jumlah_diajukan, array('id' => 'jumlah_diajukan','class' => 'form-control required', 'placeholder'=>'Jumlah Diajukan', 'readonly' => 'readonly')) !!}
                        </div>
                        <div class="form-group col-md-2">
                            {!! Form::label('jumlah_order', 'Jumlah  Diorder(*)') !!}
                            {!! Form::text('jumlah_order', $defecta->jumlah_order, array('id' => 'jumlah_order','class' => 'form-control required', 'placeholder'=>'Jumlah Diajukan')) !!}
                        </div>
                        <div class="form-group col-md-12">
                            {!! Form::label('komentar', 'Komentar') !!}
                            {!! Form::text('komentar', $defecta->komentar, array('id' => 'komentar','class' => 'form-control', 'placeholder'=>'Komentar atau catatan')) !!}
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-success" type="button" onClick="set_detail_new(this, {{$no}})" data-toggle="tooltip" data-placement="top" title="Simpan"><i class="fa fa-save"></i> Simpan</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-undo"></i> Kembali</button>
                </div>
            </div>
         </div>
    </div>
<script type="text/javascript">
    $(document).ready(function(){
    })

    function set_detail_new(obj, no){
        jumlah_order = $("#jumlah_order").val();
        komentar = $("#komentar").val();
        id_defecta = $("#id_defecta").val();
        $("#jumlah_order_"+no).val(jumlah_order);
        $("#komentar_"+no).val(komentar);
        $("#jumlah_order_"+no).html(jumlah_order);
        $("#komentar_"+no).html(komentar);

        submit_valid(id_defecta);
    }
</script>
{!! Form::close() !!}


