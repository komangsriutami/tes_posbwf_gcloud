{!! Form::model($tips, ['method' => 'PUT', 'class'=>'validated_form', 'id'=>'form-edit', 'enctype'=>"multipart/form-data", 'route' => ['tips.update', $tips->id]]) !!}
    <div class="row">
        <div class="col-sm-12">
            <div class="card card-info card-outline">
                <div class="card-body">
                    @include('tips/_form', ['submit_text' => 'Update', 'tips'=>$tips])
                </div>
                <div class="card-footer">
                    <button class="btn btn-success" type="button" onClick="submit_valid({{$tips->id}})" data-toggle="tooltip" data-placement="top" title="Simpan"><i class="fa fa-save"></i> Simpan</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-undo"></i> Kembali</button>
                </div>
            </div>
         </div>
    </div>
{!! Form::close() !!}
@include('tips/_form_js')
