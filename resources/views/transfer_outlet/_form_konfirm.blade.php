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
    <div class="col-sm-4">
        <address>
            <strong>BWF POS</strong><br>
            {{ $apotek->nama_singkat }} - Apotek {{ $apotek->nama_panjang }}<br>
            {{ $apotek->alamat }}<br>
            Phone : {{ $apotek->telepon }}
        </address>
    </div>
    <div class="col-sm-4">
        <address>
            <strong>Transfer Dari</strong><br>
            {{ $apotek_asal->nama_singkat }} - Apotek {{ $apotek_asal->nama_panjang }}<br>
            {{ $apotek_asal->alamat }}<br>
            Phone : {{ $apotek_asal->telepon }}
        </address>
    </div>
    <div class="col-sm-4">
        <div class="card bg-info">
          <div class="card-body box-profile">
            <div class="text-center">
                <h1 id="total_to_display">Rp 0, -</h1>
            </div>

          </div>
        </div>
    </div>
</div>
<hr style="border: 1px solid #004d40; padding: 0px; margin-top: 0px; margin-bottom: 10px;">
<div class="row">
    {!! Form::hidden('is_from_order', 0, array('class' => 'form-control', 'id'=>'is_from_order')) !!}
    <div class="form-group col-md-2">
        {!! Form::label('apotek', 'Apotek Tujuan') !!}
        @if($var == 1)
            {!! Form::select('id_apotek_tujuan', $apoteks, $transfer_outlet->id_apotek_tujuan, ['id' => 'id_apotek_tujuan', 'class' => 'form-control']) !!}
        @else
            {!! Form::select('id_apotek_tujuan', $apoteks, $transfer_outlet->id_apotek_tujuan, ['id' => 'id_apotek_tujuan', 'class' => 'form-control', 'disabled'=>'disabled']) !!}
        @endif
    </div>
    <div class="form-group col-md-8">
        {!! Form::label('keterangan', 'Keterangan') !!}
        {!! Form::text('keterangan', $transfer_outlet->keterangan, array('id'=> 'keterangan', 'class' => 'form-control', 'placeholder'=>'Masukan Keterangan', 'autocomplete' => 'off')) !!}
    </div>
</div>
<?php $no = 0; ?>
<?php 
    if ($var==1) {
        $jum = 0;
    } else {
        $detail_transfer_outlets = $transfer_outlet->detail_transfer_outlet;
        $jum = count($detail_transfer_outlets);
    }
    

    $detail_transfer_outlet = new App\TransaksiTODetail;
?>
<hr style="border: 1px solid #004d40; padding: 0px; margin-top: 0px; margin-bottom: 10px;">
<p>Note : centang data yang akan dikonfirmasi dan tekan tombol Simpan untuk menyimpan data.</p>
<hr style="border: 1px solid #004d40; padding: 0px; margin-top: 0px; margin-bottom: 10px;">
<div class="row">
    <div class="form-group col-md-12">
        <div class="box box-success" id="detail_data_penjualan">
            <div class="box-body">
                <div class="table-responsive">
                    <table id="tb_nota_transfer_outlet" class="table table-bordered table-striped table-hover table-head-fixed text-nowrap mb-0">
                        <thead>
                            <tr class="bg-gray color-palette">
                                <td width="5%" class="text-center"><input type="checkbox" class="checkAlltogle"> Check All</td>
                                <td width="55%" class="text-center"><strong>Nama Obat</strong></td>
                                <td width="10%" class="text-center"><strong>Harga Beli</strong></td>
                                <td width="10%" class="text-center"><strong>Jumlah</strong></td>
                                <td width="10%" class="text-center"><strong>Total</strong></td>
                            </tr>
                        </thead>
                        <tbody>
                            @if($jum == 0)
                                @else
                                    <?php $no = 0; ?>
                                    @foreach($detail_transfer_outlets as $detail_transfer_outlet)
                                        <?php 
                                            $no++; 
                                            $total = $detail_transfer_outlet->total;
                                            $total = 'Rp '.number_format($total,0,',','.');
                                            $status = '<span class="text-info">[Belum dikonfirmasi]</span>';
                                            if($detail_transfer_outlet->is_status == 1) {
                                                $status = '<span class="text-success">[Konfirmasi : Barang telah diterima]</span>';
                                            } else if($detail_transfer_outlet->is_status == 2) {
                                                $status = '<span class="text-danger">[Konfirmasi : Barang tidak diterima]</span>';
                                            }
                                        ?>

                                        <tr>
                                            <td style="text-align: center;">
                                                @if($detail_transfer_outlet->is_status != 1)
                                                <input type="checkbox" name="detail_transfer_outlet[{{ $no }}][record]" id="record_{{$no}}">
                                                @endif
                                                <!-- <span class="label label-primary" onClick="edit_detail({!! $no !!}, {!! $detail_transfer_outlet->id !!})" data-toggle="tooltip" data-placement="top" title="Edit Data"><i class="fa fa-edit"></i> Edit</span> -->
                                                {!! Form::hidden('detail_transfer_outlet['.$no.'][id]', $detail_transfer_outlet->id, array('id' => 'id_'.$no, 'class' => 'form-control', 'placeholder'=>'ID', 'readonly' => 'readonly')) !!}
                                            </td>
                                            <td style="display: none;">
                                                {!! Form::hidden('detail_transfer_outlet['.$no.'][id_obat]', $detail_transfer_outlet->id_obat, array('id' => 'id_obat_'.$no, 'class' => 'form-control', 'placeholder'=>'ID Obat', 'readonly' => 'readonly')) !!}
                                            </td>
                                            <td>
                                                {!! Form::hidden('detail_transfer_outlet['.$no.'][nama_obat]', $detail_transfer_outlet->obat->nama, array('id' => 'nama_obat_'.$no, 'class' => 'form-control', 'placeholder'=>'Nama Obat', 'readonly' => 'readonly')) !!}
                                                {{ $detail_transfer_outlet->obat->nama }} | {!! $status !!}
                                            </td>
                                            <td style='text-align:right;'>
                                                {!! Form::hidden('detail_transfer_outlet['.$no.'][harga_outlet]', $detail_transfer_outlet->harga_outlet, array('id' => 'harga_outlet_'.$no, 'class' => 'form-control', 'placeholder'=>'Masukan Harga', 'readonly' => 'readonly')) !!}

                                                {{ $detail_transfer_outlet->harga_outlet }}
                                            </td>
                                            <td style='text-align:center;'>
                                                {!! Form::hidden('detail_transfer_outlet['.$no.'][jumlah]', $detail_transfer_outlet->jumlah, array('id' => 'jumlah_'.$no, 'class' => 'form-control', 'placeholder'=>'Masukan Jumlah', 'readonly' => 'readonly')) !!}

                                                {{ $detail_transfer_outlet->jumlah }}
                                            </td>
                                            <td style='text-align:right;' id="hitung_total_{{ $no }}" class="hitung_total" data-total="{{$detail_transfer_outlet->total}}">{{ $detail_transfer_outlet->total }}
                                            </td>
                                        </tr>
                                @endforeach
                            @endif
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4">Total</td>
                                <td id="harga_total" style="text-align: right;"></td>
                            </tr>
                        </tfoot>
                    </table>
                    <br>
                </div>
            </div>
        </div>
    </div>
</div>