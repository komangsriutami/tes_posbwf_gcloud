<div class="row">
    <div class="col-sm-12">
        <div class="card card-info card-outline">
            <div class="card-body">
                <div class="row">
                	<div class="form-group col-md-6">
                		<div id="qz-connection" class="panel panel-default">
                            <div class="panel-heading">
                                <button class="close tip" data-toggle="tooltip" title="Launch QZ" id="launch" href="#" onclick="launchQZ();" style="display: none;">
                                    <i class="fa fa-external-link"></i>
                                </button>
                                <h5 class="panel-title">
                                    Connection: <span id="status_qz" class="text-muted" style="font-weight: bold;">Unknown</span>
                                </h5>
                            </div>

                            <div class="panel-body">
                                <div class="btn-toolbar">
                                    <div class="btn-group" role="group">
                                        <a  href="{{ url('/penjualan/') }}" class="hidden-print btn btn-sm btn-info" style="text-decoration:none;margin:0;color: #fff;background-color: #dc3545;border-color: #dc3545;box-shadow: none; font-size:10pt;">Back | F2</a>
                                        <button type="button" class="btn btn-success btn-sm" onclick="startConnection();">Connect</button>
                                        <button type="button" class="btn btn-warning btn-sm" onclick="endConnection();">Disconnect</button>
                                    </div>
                                    <!-- <button type="button" class="btn btn-info" onclick="listNetworkInfo();">List Network Info</button> -->
                                </div>
                            </div>
                        </div>
                        <hr />
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <h5 class="panel-title">Printer</h5>
                            </div>

                            <div class="panel-body">
                               <!--  <div class="form-group">
                                    <label for="printerSearch">Pencarian :</label>
                                    <select id="list_printer" value="zebra" class="form-control"></select>
                                </div>
                                <hr /> -->
                                <div class="form-group">
                                    <label>Current printer:</label>
                                    <div id="configPrinter">NONE</div>
                                </div>
                                <div class="btn-toolbar">
                                    <div class="form-group">
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-danger btn-sm" onclick="print_nota_obat_operasional();">Printer | Shift</button>
                                        </div>
                                       
                                    </div>
                                    </div>
                            </div>
                        </div>
					</div>
					<div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <input type="hidden" name="id" id="id" value="{{ $obat_operasional->id }}">
                                <input type="hidden" name="token" id="token" value="{{csrf_token()}}">
                                <?php
                                    $nama_apotek = strtoupper($obat_operasional->apotek_nota->nama_panjang);
                                    $nama_apotek_singkat = strtoupper($obat_operasional->apotek_nota->nama_singkat);
                                ?>
                                <p align="center">APOTEK BWF-{{ $nama_apotek }}</p>
                                <p align="center">{{ $obat_operasional->apotek_nota->alamat }}</p>
                                <p align="center">Telp. {{ $obat_operasional->apotek_nota->telepon }}</p>
                                <hr>
                                <p style="margin-left: 10px;">No Nota   : {{$nama_apotek_singkat}}-{{ $obat_operasional->id }}</p>
                                <p style="margin-left: 10px;">Tanggal   : {{ $obat_operasional->created_at }}</p>
                                <p style="margin-left: 10px;">Kasir     : {{ $obat_operasional->created_oleh->nama }}</p>
                                <p style="margin-left: 10px;">Keterangan: {{ $obat_operasional->keterangan }}</p>
                                <hr>
                              
                                <table class="table">
										<tr>
											<td>No.</td>
											<td>ID</td>
											<td>Nama Obat</td>
											<td>Jumlah</td>
											<td>Harga</td>
											<td>Total</td>
										</tr>
									<?php $no = 0; ?>
									@foreach( $detail_obat_operasionals as $obj )
										<?php $no = $no+1; ?>
							          	<tr>
							          		<td>{{ $no }}</td>
								    		<td>{{ $obj->id_obat }}</td>
								    		<td>{{ $obj->nama }}</td>
								    		<td>{{ $obj->jumlah }}</td>
											<td>{{ $obj->harga_jual }}</td>
											<td>{{ $obj->total }}</td>
							          	</tr>
						          	@endforeach
                                    <tr>
                                        <td colspan="5"><b>Total</b></td>
                                        <td><b>{{ $obat_operasional->grand_total }}</b></td>
                                    </tr>
					        	</table>
                            </div>
                        </div>
                    </div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
    $(document).ready(function(){

        $(document).on("keyup", function(e){
            var x = e.keyCode || e.which;
            if (x == 16) {  
                // fungsi shift 
                print_nota_transfer_internal();
            } else if(x==113){
                // fungsi F2 
                window.location.href = "{{ url('/transfer_outlet/') }}";
            }
        })
    })
</script>