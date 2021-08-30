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
<link href="http://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.4/summernote.css" rel="stylesheet">

<div class="row">
    <div class="form-group col-md-12">
        {!! Form::label('title', 'Judul') !!}
        {!! Form::text('title', $tips->title, array('class' => 'form-control required', 'placeholder'=>'Masukan Judul')) !!}
    </div>
    <div class="form-group col-md-12">
        {!! Form::label('content', 'Image') !!}
        <input type="file" name="image" class="form-control" placeholder="Masukan Image">
    </div>
    <div class="form-group col-md-12">
        {!! Form::label('content', 'Konten') !!}
        <textarea name="content" class="form-control content-tips" rows="5" placeholder="Masukan Konten">{{$tips->content}}</textarea>
    </div>
</div>
