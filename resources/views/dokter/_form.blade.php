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
        {!! Form::label('id_group_apotek', 'Group Apotek') !!}
        {!! Form::select('id_group_apotek', $group_apoteks, $dokter->id_group_apotek, ['class' => 'form-control required input_select']) !!}
    </div>
	<div class="form-group col-md-6">
	    {!! Form::label('nama', 'Nama') !!}
	    {!! Form::text('nama', $dokter->nama, array('class' => 'form-control required', 'placeholder'=>'Masukan Nama')) !!}
	</div>
	<div class="form-group col-md-6">
	    {!! Form::label('sib', 'SIB') !!}
	    {!! Form::text('sib', $dokter->sib, array('class' => 'form-control required', 'placeholder'=>'Masukan SIB')) !!}
	</div>
	<div class="form-group col-md-6">
	    {!! Form::label('alamat', 'Alamat') !!}
	    {!! Form::text('alamat', $dokter->alamat, array('class' => 'form-control required', 'placeholder'=>'Masukan Alamat')) !!}
	</div>
	<div class="form-group col-md4">
	    {!! Form::label('telepon', 'Telepon') !!}
	    {!! Form::text('telepon', $dokter->telepon, array('class' => 'form-control required number', 'placeholder'=>'Masukan Nomor Telepon')) !!}
	</div>
	<div class="form-group col-md-2">
	    {!! Form::label('fee', 'Fee %(*)') !!}
	    {!! Form::text('fee', $dokter->fee, array('class' => 'form-control required number', 'placeholder'=>'Masukan % Fee')) !!}
	</div>
</div>