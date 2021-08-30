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
    <div class="form-group col-sm-3">
        {!! Form::label('nama_singkatan', 'Nama Singkat (*)') !!}
        {!! Form::text('nama_singkatan', $menu->nama_singkatan, array('id' => 'nama_singkatan', 'class' => 'form-control required', 'placeholder'=>'Masukan Nama Singkat')) !!}
    </div>
    <div class="form-group col-sm-9">
        {!! Form::label('nama_panjang', 'Nama Panjang') !!}
        {!! Form::text('nama_panjang', $menu->nama_panjang, array('id' => 'nama_panjang', 'class' => 'form-control', 'placeholder'=>'Masukan Nama Panjang')) !!}
    </div>
    <div class="form-group col-sm-12">
        {!! Form::label('deskripsi', 'Deskripsi') !!}
        {!! Form::textarea('deskripsi', $menu->deskripsi, array('id' => 'deskripsi', 'class' => 'form-control textarea', 'placeholder'=>'Masukan Deskripsi', 'cols' => '50')) !!}
    </div>
    <div class="form-group col-sm-6">
        {!! Form::label('route_group', 'Route Group') !!}
        {!! Form::text('route_group', $menu->route_group, array('id' => 'route_group', 'class' => 'form-control', 'placeholder'=>'Masukan Route Group')) !!}
    </div>
    <div class="form-group col-sm-6">
        {!! Form::label('id_icon', 'Icon') !!}
        <select name="id_icon"  id="id_icon" class="form-control input_select">
            <option value="">--- Pilih Icon ---</option>
            @foreach($icons as $obj)
                <?php $name_icon = $obj->icon; ?>
                <option value="{{$name_icon}}" {!!( $name_icon == $menu->id_icon ? 'selected' : '')!!}><i class="fa fa-fw fa-bed"></i>{{$obj->icon}}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-sm-6">
        {!! Form::label('link', 'Link') !!}
        {!! Form::text('link', $menu->link, array('id' => 'link', 'class' => 'form-control required', 'placeholder'=>'Masukan Link')) !!}
    </div>
    <?php $nama_panjang = 'nama_panjang'; ?>
    <div class="form-group col-sm-6">
        {!! Form::label('parent', 'Parent') !!}
        <select name="parent"  id="parent" class="form-control parent">
            <option value="" data-nama_panjang="">--- Pilih Parent---</option>
            @foreach($parents as $obj)
                <option value="{{$obj->id}}" data-nama_panjang="{{$obj->$nama_panjang}}" {!!( $obj->id == $menu->parent ? 'selected' : '')!!}>{{$obj->$nama_panjang}}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-sm-6">
        {!! Form::label('sub_parent', 'Sub Parent') !!}
        <select name="sub_parent"  id="sub_parent" class="form-control sub_parent">
            <option value="" data-parent="">--- Pilih Sub Parent---</option>
            @foreach($sub_parents as $obj)
                <option value="{{$obj->id}}" data-parent="{{$obj->parent}}" {!!( $obj->id == $menu->sub_parent ? 'selected' : '')!!}>{{$obj->$nama_panjang}}</option>
            @endforeach
        </select>
    </div>

</div>




