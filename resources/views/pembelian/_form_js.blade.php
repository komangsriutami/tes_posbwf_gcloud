<script type="text/javascript">
	var token = "";

    var tb_pembelian_revisi = $('#tb_pembelian_revisi').dataTable( {
            processing: true,
            serverSide: true,
            stateSave: true,
            ajax:{
                    url: '{{url("pembelian/list_pembelian_revisi")}}',
                    data:function(d){
                        d.id = $("#id").val();
                    }
                 },
            columns: [
                {data: 'no', name: 'no',width:"2%", class:'text-center'},
                {data: 'tanggal', name: 'tanggal', class:'text-center', orderable: true, searchable: true},
                {data: 'detail_obat', name: 'detail_obat', orderable: false, searchable: false},
                {data: 'kasir', name: 'kasir', class:'text-center', orderable: false, searchable: false},
                {data: 'jumlah_awal', name: 'jumlah_awal', class:'text-center', orderable: true, searchable: false},
                {data: 'jumlah', name: 'jumlah',class:'text-center', orderable: true, searchable: false},
                {data: 'harga_beli_awal', name: 'harga_beli_awal', class:'text-center', orderable: true, searchable: false},
                {data: 'harga_beli', name: 'harga_beli', class:'text-center', orderable: true, searchable: false}
            ],
            rowCallback: function( row, data, iDisplayIndex ) {
                var api = this.api();
                var info = api.page.info();
                var page = info.page;
                var length = info.length;
                var index = (page * length + (iDisplayIndex +1));
                $('td:eq(0)', row).html(index);
            },
            stateSaveCallback: function(settings,data) {
                localStorage.setItem( 'DataTables_' + settings.sInstance, JSON.stringify(data) )
            },
            stateLoadCallback: function(settings) {
                return JSON.parse( localStorage.getItem( 'DataTables_' + settings.sInstance ) )
            },
            drawCallback: function( settings ) {
                var api = this.api();
            }
        });

	$(document).ready(function(){
		token = $('input[name="_token"]').val();

		$('.input_select').select2();
		$("#suplier").focus();
		$("#suplier").keypress(function(event){
		    if (event.which == '10' || event.which == '13') {
		    	cari_suplier();
		        event.preventDefault();
		    }
		});

		$('#id_jenis_pembelian').on('select2:select', function (e) {
            $("#no_faktur").focus();
        });


		$("#no_faktur").keypress(function(event){
            if (event.which == '10' || event.which == '13') {
                $("#tgl_faktur").focus();
                event.preventDefault();
            }
        });
        

		$('#tgl_nota, #tgl_faktur, #tgl_jatuh_tempo, #tgl_batch').datepicker({
            autoclose:true,
            format:"yyyy-mm-dd",
            forceParse: false
        });

        $('#tgl_faktur').change(function(){
		    $("#tgl_jatuh_tempo").focus();
		});


		$('#tgl_jatuh_tempo').change(function(){
		    $("#barcode").focus();
		});

		$("#barcode").keypress(function(event){
		    if (event.which == '10' || event.which == '13') {
		    	cari_obat();
		        event.preventDefault();
		    }
		});

		$("#jumlah").keypress(function(event){
		    if (event.which == '10' || event.which == '13') {
		    	$("#total_harga").focus();
		    }
		});

		$("#total_harga").keypress(function(event){
		    if (event.which == '10' || event.which == '13') {
		    	$("#diskon").focus();
		    }
		});

		$("#diskon").keypress(function(event){
		    if (event.which == '10' || event.which == '13') {
		    	$("#diskon_persen").focus();
		    }
		});

		$("#diskon_persen").keypress(function(event){
		    if (event.which == '10' || event.which == '13') {
		    	$("#tgl_batch").focus()
		    }
		});

		$('#tgl_batch').change(function(event){
			$("#id_batch").focus();
		});

		$("#id_batch").keypress(function(event){
		    if (event.which == '10' || event.which == '13') {
		    	tambah_item_obat();
		    	event.preventDefault();
		    }
		});

		$("#diskon2").keypress(function(event){
		    if (event.which == '10' || event.which == '13') {
		    	$("#ppn").focus();
		    }
		});

		$("#ppn").keypress(function(event){
		    if (event.which == '10' || event.which == '13') {
		    	save_data();
		    }
		});
		
		$(document).on("keyup", function(e){
		  	var x = e.keyCode || e.which;
		    if (x == 16) {  
		    	// fungsi shift 
		        $("#barcode").focus();
		    } else if (x == 27) {  
		    	// fungsi  buka data suplier
		        open_data_suplier();
		    } else if(x==113){
		    	// fungsi F2 
		    	//alert("save data");
		    	save_data(); // belum dibuat
		    } else if(x==115){
		    	// fungsi F4
		    	$("#id_batch").focus();
		    } else if(x==118){
		    	// fungsi F7
		    	// tidak bisa digunakan
		    } else if(x==119){
		    	// fungsi F8
		    	$("#suplier").focus();
		    } else if(x==120){
		    	// fungsi F9
		    	$("#diskon2").focus();
		    } else if(x==121){
		    	// fungsi F10
		    	find_ketentuan_keyboard();
		    } else if(x == 17) {
		    	open_data_obat();
		    }
		})

        $('body').addClass('sidebar-collapse');

        $("#total_harga, #jumlah, #diskon_persen, #diskon").keyup(function() {
            cek_perubahan_harga_beli();
        });

        $("#diskon2, #ppn").keyup(function(){
        	var total1 = $("#total1").val();
        	var diskon1 = $("#diskon1").val();
        	var diskon2 = $("#diskon2").val();
        	var ppn = $("#ppn").val();

        	if(total1 == '') {
        		total1 = 0;
        	}

        	if(diskon1 == '') {
        		diskon1 = 0;
        	}

        	if(diskon2 == '') {
        		diskon2 = 0;
        	}

        	if(ppn == '') {
        		ppn = 0;
        	}
            var total2 = parseFloat(total1) - (parseFloat(diskon1) + parseFloat(diskon2));
            var total_pembelian = parseFloat(total2) + parseFloat(ppn/100 * parseFloat(total2));
            var total_pembelian_rp = hitung_rp(total_pembelian);
            $("#total_pembayaran_display").html("Rp "+ total_pembelian_rp +", -");
            $("#total_pembelian").val(total_pembelian);
        });

        hitung_total();
	})

	function goBack() {
	    window.history.back();
	}

	function cari_suplier() {
		var suplier = $("#suplier").val();
		open_data_suplier(suplier);
	}

	function add_suplier_dialog(id) {
		$.ajax({
            url:'{{url("pembelian/cari_suplier_dialog")}}',
            type: 'POST',
            data: {
                _token      : "{{csrf_token()}}",
                id: id
            },
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success:function(data){
        		$("#suplier").val(data.nama);
        		$("#id_suplier").val(data.id);
        		$("#id_jenis_pembelian").select2({});
        		$('#id_jenis_pembelian').select2('open');
		        $('#modal-xl').modal('toggle');
            }
        });
	}


	function cari_obat() {
		var barcode = $("#barcode").val();
		var inisial = $("#inisial").val();
		if(Number.isInteger(barcode)) {
			$.ajax({
	            url:'{{url("penjualan/cari_obat")}}',
	            type: 'POST',
	            data: {
	                _token      : "{{csrf_token()}}",
	                barcode: barcode,
	                inisial: inisial
	            },
	            dataType: 'json',
	            headers: {
	                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
	            },
	            success:function(data){
	            	if(data.is_data == 1) {
	            		$("#barcode").val(data.obat.barcode);
	            		$("#id_obat").val(data.obat.id);
		            	$("#nama_obat").val(data.obat.nama);
		                $("#harga_beli_sebelumnya").val(data.harga_stok.harga_beli);
				        $("#jumlah").focus();
	            	} else {
	            		show_error("Obat dengan barcode tersebut tidak dapat ditemukan!");
	            		kosongkan_form();
	            	}
	            	
	            }
	        });
		} else {
			open_data_obat(barcode);
		}		
	}

	function add_item_dialog(id_obat, harga_jual, harga_beli, stok_akhir, harga_beli_ppn) {
		var inisial = $("#inisial").val();
		$.ajax({
            url:'{{url("penjualan/cari_obat_dialog")}}',
            type: 'POST',
            data: {
                _token      : "{{csrf_token()}}",
                id_obat: id_obat,
                inisial: inisial
            },
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success:function(data){
        		$("#barcode").val(data.barcode);
        		$("#id_obat").val(data.id);
            	$("#nama_obat").val(data.nama);
                var angka = harga_beli;
                hasil = harga_beli.toFixed(2);
                $("#harga_beli_sebelumnya").val(hasil);
		        $("#jumlah").focus();
		        $('#modal-xl').modal('toggle');
            }
        });
	}

	function kosongkan_form(){
		$("#barcode").val('');
		$("#id_obat").val('');
    	$("#nama_obat").val('');
        $("#harga_beli_sebelumnya").val('');
        $("#harga_beli").val('');
        $("#jumlah").val('');
        $("#id_batch").val('');
        $("#tgl_batch").val('');
        $("#diskon").val('');
        $("#diskon_persen").val('');
        $("#total_harga").val('');
        $("#barcode").focus();
	}

	function open_data_obat(barcode) {
		$.ajax({
            type: "POST",
            url: '{{url("penjualan/open_data_obat")}}',
            async:true,
            data: {
                _token  : "{{csrf_token()}}",
                barcode : barcode,
            },
            beforeSend: function(data){
                // on_load();
                $('#modal-xl').find('.modal-xl').find(".modal-content").find(".modal-header").attr("class","modal-header bg-light-blue");
                $("#modal-xl .modal-title").html("Data Obat");
                $('#modal-xl').modal("show");
                $('#modal-xl').find('.modal-body-content').html('');
                $("#modal-xl").find(".overlay").fadeIn("200");
            },
            success:  function(data){
                $('#modal-xl').find('.modal-body-content').html(data);
            },
            complete: function(data){
                $("#modal-xl").find(".overlay").fadeOut("200");
            },
              error: function(data) {
                alert("error ajax occured!");
              }
        });
	}

	$("#add_row_pembelian").click(function(){
		var cek_ = cek_kelengkapan_form();
        if(cek_ == 1) {
			tambah_item_obat();
		} else {
			show_error("Data item penjualan tidak lengkap!");
		}
    });

    function cek_kelengkapan_form() {
    	var barcode = $("#barcode").val();
		var id_obat = $("#id_obat").val();
    	var nama_obat = $("#nama_obat").val();
        var harga_beli = $("#harga_beli").val();
        var total_harga = $("#total_harga").val();
        var id_batch = $("#id_batch").val();
        var tgl_batch = $("#tgl_batch").val();
        var diskon = $("#diskon").val();
        var jumlah = $("#jumlah").val();
        var diskon_persen = $("#diskon_persen").val();
        if(barcode != '' && id_obat != '' && nama_obat != '' && harga_beli != '' && total_harga != '' && id_batch != '' && tgl_batch != '' && diskon != '' && jumlah != '' && diskon_persen != '') {
        	return 1;
        } else {
        	return 2;
        }
    }

    function hitung_rp_khusus(nilai) {
        var nilai_str = nilai.toString();
        var res = nilai_str.split(".");
        var number_string = res[0],
            sisa    = number_string.length % 3,
            rupiah  = number_string.substr(0, sisa),
            ribuan  = number_string.substr(sisa).match(/\d{3}/g);
                
        if (ribuan) {
            separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        return rupiah;
    }

	function tambah_item_obat(){
		var counter = $("#counter").val();
        var id_obat = $("#id_obat").val();
	 	var nama_obat = $('#nama_obat').val();
        var harga_beli = $("#harga_beli").val();
        var harga_beli_rp = hitung_rp_khusus(harga_beli);
        var jumlah = $("#jumlah").val();
        var diskon = $("#diskon").val();
        var diskon_rp = hitung_rp(diskon);
        var diskon_persen = $("#diskon_persen").val();
        var id_batch = $("#id_batch").val();
        var tgl_batch = $("#tgl_batch").val();
        var total = $("#total_harga").val();
        var total_rp = hitung_rp(total);
        var dis_1 = $("#diskon_persen").val()/100 * $("#total_harga").val();
        //console.log(dis_1);
        var dis_2 = $("#diskon").val();
        var diskon1 = dis_1;
        var diskon2 = dis_2;
        var diskon1_rp = hitung_rp_khusus(diskon1);

        //console.log(diskon1);
        //console.log(diskon1_rp);
        var diskon2_rp = hitung_rp(diskon2);
        var total_diskon = parseFloat(dis_1) + parseFloat(dis_2); 
        var total_diskon_rp = hitung_rp(total_diskon);
        var total_2 = parseFloat(total) - parseFloat(total_diskon);
        //console.log(total_2);
        var total_2_rp = hitung_rp_khusus(total_2);

        var markup = "<tr>"+
                        "<td><input type='checkbox' name='record'>"+
        				"<input type='hidden' id='detail_pembelian["+counter+"][id]' name='detail_pembelian["+counter+"][id]'><span class='label label-primary btn-sm' onClick='deleteRow(this)' data-toggle='tooltip' data-placement='top' title='Hapus Data'><i class='fa fa-edit'></i> Hapus</span></td> "+
                        "<td style='display:none;'><input type='hidden' id='detail_pembelian["+counter+"][jumlah_revisi]' name='detail_pembelian["+counter+"][jumlah_revisi]' value='0'>0</td>"+
                        "<td style='display:none;'><input type='hidden' id='detail_pembelian["+counter+"][id_jenis_revisi]' name='detail_pembelian["+counter+"][id_jenis_revisi]' value='0'>0</td>"+
        				"<td style='display:none;'><input type='hidden' id='detail_pembelian["+counter+"][id_obat]' name='detail_pembelian["+counter+"][id_obat]' value='"+id_obat+"' data-id-obat='"+id_obat+"'>" + id_obat + "</td>"+
                        "<td><input type='hidden' id='detail_pembelian["+counter+"][nama_obat]' name='detail_pembelian["+counter+"][nama_obat]' value='"+nama_obat+"'>" + nama_obat + "</td>"+
                        "<td style='text-align:right;' id='detail_pembelian["+counter+"][total]'><input type='hidden' class='total' data-id-obat='"+id_obat+"' value='"+total+"'><span class='total_label'><b>" + total + "</b></span></td>"+
                         "<td style='text-align:right;'><input type='hidden' id='detail_pembelian["+counter+"][diskon]' name='detail_pembelian["+counter+"][diskon]' value='"+diskon+"' class='diskon' data-id-obat='"+id_obat+"'><span class='diskon_label'>" + diskon + "</span></td>"+
                        "<td style='text-align:center;'><input type='hidden' id='detail_pembelian["+counter+"][diskon_persen]' name='detail_pembelian["+counter+"][diskon_persen]' value='"+diskon_persen+"' class='diskon_persen' data-id-obat='"+id_obat+"'><span class='diskon_persen_label'>" + diskon_persen + " % (-"+ diskon1 +")</span></td>"+
                        "<td style='text-align:right;'><input type='hidden' id='detail_pembelian["+counter+"][total_2]' name='detail_pembelian["+counter+"][total_2]' value='"+total_2+"' class='total_2' data-id-obat='"+id_obat+"'><span class='total_2_label'>" + total_2 + "</span></td>"+
                        "<td style='text-align:center;'><input type='hidden' id='detail_pembelian["+counter+"][jumlah]' name='detail_pembelian["+counter+"][jumlah]' value='"+jumlah+"' class='jumlah' data-id-obat='"+id_obat+"'><span class='jumlah_label'>" + jumlah + "</span></td>"+
                         "<td style='text-align:right;'><input type='hidden' id='detail_pembelian["+counter+"][harga_beli]' name='detail_pembelian["+counter+"][harga_beli]' value='"+harga_beli+"' class='harga_beli' data-id-obat='"+id_obat+"'><span class='harga_beli_label'>" + harga_beli + "</span></td>"+
                        "<td style='display:none;'><input type='hidden' id='detail_pembelian["+counter+"][id_batch]' name='detail_pembelian["+counter+"][id_batch]' value='"+id_batch+"' class='id_batch' data-id-obat='"+id_obat+"'><span class='id_batch_label'>" + id_batch + "</span></td>"+
                        "<td style='display:none;'><input type='hidden' id='detail_pembelian["+counter+"][total_harga]' name='detail_pembelian["+counter+"][total_harga]' value='"+total+"' class='total_harga' data-id-obat='"+id_obat+"'><span class='total_harga_label'>" + total + "</span></td>"+
                        "<td style='display:none;'><input type='hidden' id='detail_pembelian["+counter+"][tgl_batch]' name='detail_pembelian["+counter+"][tgl_batch]' value='"+tgl_batch+"' class='tgl_batch' data-id-obat='"+id_obat+"'><span class='tgl_batch_label'>" + tgl_batch + "</span></td>"+
                        "<td style='display:none;' id='hitung_total_"+counter+"' class='hitung_total'>" + total + "</td>"+
                        "<td style='display:none;' id='hitung_diskon_"+counter+"' class='hitung_diskon'>" + diskon2 + "</td>"+
                        "<td style='display:none;' id='hitung_diskon_persen_"+counter+"' class='hitung_diskon_persen'>" + diskon1 + "</td>"+
                       
                    "</tr>";

        var harga_beli_label = $(".harga_beli_label");
        var jumlah_label = $(".jumlah_label");
        var total_2_label = $(".total_2_label");
        var id_batch_label = $(".id_batch_label");
        var tgl_batch_label = $(".tgl_batch_label");
        var diskon_label = $(".diskon_label");
        var diskon_persen_label = $(".diskon_persen_label");
        var total_label = $(".total_label");
        var status_append = true;

        $(".harga_beli").each(function(i,l){
            if($(l).data("id-obat")== id_obat){
                var nilai_harga_beli = parseInt($(l).val());
                if(isNaN(nilai_harga_beli)){
                    nilai_harga_beli = 0;
                }

                var harga_beli_var = parseInt( harga_beli );
                if(isNaN(harga_beli_var)){
                    harga_beli_var = 0;
                }
                
                //var new_harga_beli = harga_beli_var+nilai_harga_beli;
                var new_harga_beli = harga_beli_var;

                $(l).val(new_harga_beli);
                $(harga_beli_label[i]).html(new_harga_beli);

                status_append = false;
            }
        })

        $(".jumlah").each(function(i,l){
            if($(l).data("id-obat")== id_obat){
                var nilai_jumlah = parseInt($(l).val());
                if(isNaN(nilai_jumlah)){
                    nilai_jumlah = 0;
                }

                var jumlah_var = parseInt( jumlah );
                if(isNaN(jumlah_var)){
                    jumlah_var = 0;
                }
                
                //var new_jumlah = jumlah_var+nilai_jumlah;
                var new_jumlah = jumlah_var;

                $(l).val(new_jumlah);
                $(jumlah_label[i]).html(new_jumlah);

                status_append = false;
            }
        })

        $(".total_2_label").each(function(i,l){
            if($(l).data("id-obat")== id_obat){
                var nilai_total_2 = parseInt($(l).val());
                if(isNaN(nilai_total_2)){
                    nilai_total_2 = 0;
                }

                var total_2_var = parseInt( total_2 );
                if(isNaN(total_2_var)){
                    total_2_var = 0;
                }
                
                //var new_jumlah = jumlah_var+nilai_jumlah;
                var new_total_2 = total_2_var;

                $(l).val(new_total_2);
                $(total_2_label[i]).html(new_total_2);

                status_append = false;
            }
        })

        $(".id_batch").each(function(i,l){
            if($(l).data("id-obat")== id_obat){
                var nilai_id_batch = $(l).val();
                if(nilai_id_batch == ''){
                    nilai_id_batch = '';
                }

                var id_batch_var = id_batch;
                if(id_batch_var == ''){
                    id_batch_var = '';
                }
                
                //var new_id_batch = id_batch_var+nilai_id_batch;
                var new_id_batch = id_batch_var;

                $(l).val(new_id_batch);
                $(id_batch_label[i]).html(new_id_batch);

                status_append = false;
            }
        })

        $(".tgl_batch").each(function(i,l){
            if($(l).data("id-obat")== id_obat){
                var nilai_tgl_batch = $(l).val();
                if(nilai_tgl_batch == ''){
                    nilai_tgl_batch = '';
                }

                var tgl_batch_var = tgl_batch;
                if(tgl_batch_var == ''){
                    tgl_batch_var = '';
                }
                
                //var new_tgl_batch = tgl_batch_var+nilai_tgl_batch;
                var new_tgl_batch = tgl_batch_var;

                $(l).val(new_tgl_batch);
                $(tgl_batch_label[i]).html(new_tgl_batch);

                status_append = false;
            }
        })

        $(".diskon").each(function(i,l){
            if($(l).data("id-obat")== id_obat){
                var nilai_diskon = parseInt($(l).val());
                if(isNaN(nilai_diskon)){
                    nilai_diskon = 0;
                }

                var diskon_var = parseInt( diskon );
                if(isNaN(diskon_var)){
                    diskon_var = 0;
                }
                
                //var new_diskon = diskon_var+nilai_diskon;
                var new_diskon = diskon_var;

                $(l).val(new_diskon);
                $(diskon_label[i]).html(new_diskon);
                 $("#hitung_diskon_"+i).html(new_diskon);

                status_append = false;
            }
        })

        $(".diskon_persen").each(function(i,l){
            if($(l).data("id-obat")== id_obat){
                var nilai_diskon_persen = parseInt($(l).val());
                if(isNaN(nilai_diskon_persen)){
                    nilai_diskon_persen = 0;
                }

                var diskon_persen_var = parseInt( diskon_persen );
                if(isNaN(diskon_persen_var)){
                    diskon_persen_var = 0;
                }
                
                //var new_diskon_persen = diskon_persen_var+nilai_diskon_persen;
                var new_diskon_persen = diskon_persen_var;

                $(l).val(new_diskon_persen);
                $(diskon_persen_label[i]).html(new_diskon_persen);
                var dis_1 = new_diskon_persen/100 * $("#harga_beli").val();
                $("#hitung_diskon_persen_"+i).html(dis_1);

                status_append = false;
            }
        })

        $(".total").each(function(i,l){
            if($(l).data("id-obat")== id_obat){
                var nilai_total = parseInt($(l).val());
                if(isNaN(nilai_total)){
                    nilai_total = 0;
                }

                var total_var = parseInt( total );
                if(isNaN(total_var)){
                    total_var = 0;
                }
                
                //var new_total = total_var+nilai_total;
                var new_total = total_var;

                $(l).val(new_total);
                $(total_label[i]).html(new_total);
                $("#hitung_total_"+i).html(new_total);

                status_append = false;
            }
        })

        if(status_append == true){
            $("#tb_nota_pembelian tbody").append(markup);
            current_counter = parseInt($("#counter").val());
            if(isNaN(current_counter)){
                current_counter = 0;
            }
              
            $("#counter").val(current_counter+1);
        }      

        hitung_total();
        kosongkan_form();
	}

	function cek_perubahan_harga_beli() {
        var jumlah = $("#jumlah").val();
        var diskon = $("#diskon").val();
        var diskon_persen = $("#diskon_persen").val();
        var total_harga = $("#total_harga").val();


        if(jumlah == "") {
            jumlah = 1;
        } else {
            jumlah = jumlah;
        }

        if(diskon == "") {
            diskon = 0;
        } else {
            diskon = diskon;
        } 

        if(diskon_persen == "") {
            diskon_persen = 0;
        } else {
            diskon_persen = diskon_persen;
        }

        if(total_harga == "") {
            total_harga = 0;
        } else {
            total_harga = total_harga;
        }

 
        var total_diskon = parseFloat(diskon) + (parseFloat(diskon_persen)/100 * parseFloat(total_harga));
        hitung_1 = (parseFloat(total_harga))-parseFloat(total_diskon);
        harga_beli = parseFloat(hitung_1)/parseFloat(jumlah);
        $("#harga_beli").val(harga_beli);
    }

	function hitung_total(){
        var tes = $('.hitung_total');
        var diskon_v = $('.hitung_diskon');
        var diskon_persen_v = $('.hitung_diskon_persen');
        var total = 0;
        var diskon = 0;
        var diskon_persen = 0;
        
        $(tes).each(function(i,l){
            sub_total = parseFloat( $(l).html() );
            if(isNaN(sub_total)){
                sub_total = 0;
            }

            total = total+sub_total;
        })

        $(diskon_v).each(function(i,l){
            sub_total = parseFloat( $(l).html() );
            if(isNaN(sub_total)){
                sub_total = 0;
            }

            diskon = diskon+sub_total;
        })

        $(diskon_persen_v).each(function(i,l){
            sub_total = parseFloat( $(l).html() );
            if(isNaN(sub_total)){
                sub_total = 0;
            }

            diskon_persen = diskon_persen+sub_total;
        })
        //$("#harga_total").html(total);
        var num = parseFloat(diskon) + parseFloat(diskon_persen);
        var diskon_total = num.toFixed(2);
        var total2 = parseFloat(total) - parseFloat(diskon_total);
        var ppn = $("#ppn").val();
        var diskon2= $("#diskon2").val();
        if(ppn == '') {
        	ppn = 0;
        }

        if(diskon2 == '' ) {
        	diskon2 = 0;
        }

        var total2_2 = total2 - parseFloat(diskon2);
        var total_pembelian = total2_2 + (ppn/100 * total2_2);
        $("#total1").val(total);
        $("#diskon1").val(diskon_total);
        $("#total2").val(total2);
        var total_pembelian_rp = hitung_rp(total_pembelian);
        var total_pembelian_rpx = hitung_rp_khusus(total_pembelian);
        $("#total_pembayaran_display").html("Rp "+ total_pembelian_rpx +", -");
        $("#total_pembelian").val(total_pembelian);
    }

	function hitung_rp(nilai) {
		var	number_string = nilai.toString(),
			sisa 	= number_string.length % 3,
			rupiah 	= number_string.substr(0, sisa),
			ribuan 	= number_string.substr(sisa).match(/\d{3}/g);
				
		if (ribuan) {
			separator = sisa ? '.' : '';
			rupiah += separator + ribuan.join('.');
		}
		return rupiah;
	}

	function hapus_item_obat() {
		$("table tbody").find('input[name="record"]').each(function(){
        	if($(this).is(":checked")){
                $(this).parents("tr").remove();

                hitung_total();
            }
        });
	}

	function open_data_suplier(suplier) {
		$.ajax({
            type: "POST",
            url: '{{url("pembelian/open_data_suplier")}}',
            async:true,
            data: {
                _token  : "{{csrf_token()}}",
                suplier : suplier,
            },
            beforeSend: function(data){
                // on_load();
                $('#modal-xl').find('.modal-xl').find(".modal-content").find(".modal-header").attr("class","modal-header bg-light-blue");
                $("#modal-xl .modal-title").html("Data Suplier");
                $('#modal-xl').modal("show");
                $('#modal-xl').find('.modal-body-content').html('');
                $("#modal-xl").find(".overlay").fadeIn("200");
            },
            success:  function(data){
                $('#modal-xl').find('.modal-body-content').html(data);
            },
            complete: function(data){
                $("#modal-xl").find(".overlay").fadeOut("200");
            },
              error: function(data) {
                alert("error ajax occured!");
              }
        });
	}


	function set_jasa_dokter(){
	    $.ajax({
	        type: "POST",
	        url: '{{url("penjualan/set_jasa_dokter")}}',
	        async:true,
	        data: {
	        	_token:"{{csrf_token()}}",
	        	harga_total:$("#total_pembayaran_input").val(),
	        },
	        beforeSend: function(data){
	          	// on_load();
		        $('#modal-xl').find('.modal-xl').find(".modal-content").find(".modal-header").attr("class","modal-header bg-light-blue");
	            $("#modal-xl .modal-title").html("Jasa Dokter");
	            $('#modal-xl').modal("show");
	            $('#modal-xl').find('.modal-body-content').html('');
	            $("#modal-xl").find(".overlay").fadeIn("200");
	        },
	        success:  function(data){
	          	$('#modal-xl').find('.modal-body-content').html(data);
	        },
	        complete: function(data){
	            $("#modal-xl").find(".overlay").fadeOut("200");
	        },
	        error: function(data) {
	            alert("error ajax occured!");
	        }
	    });
	}

	function set_diskon_persen(){
	    $.ajax({
	        type: "POST",
	        url: '{{url("penjualan/set_diskon_persen")}}',
	        async:true,
	        data: {
	        	_token:"{{csrf_token()}}",
	        	harga_total:$("#harga_total_input").val(),
	        	diskon_total:$("#diskon_total_input").val(),
	          	total_biaya_dokter : $("#total_biaya_dokter").val(),
	        },
	        beforeSend: function(data){
	          // on_load();
	        	$('#modal-xl').find('.modal-xl').find(".modal-content").find(".modal-header").attr("class","modal-header bg-light-blue");
	            $("#modal-xl .modal-title").html("Diskon Karyawan");
	            $('#modal-xl').modal("show");
	            $('#modal-xl').find('.modal-body-content').html('');
	            $("#modal-xl").find(".overlay").fadeIn("200");
	        },
	        success:  function(data){
	          	$('#modal-xl').find('.modal-body-content').html(data);
	        },
	        complete: function(data){
	            $("#modal-xl").find(".overlay").fadeOut("200");
	        },
	        error: function(data) {
	            alert("error ajax occured!");
	        }
	    });
	}

	function open_pembayaran() {
		$.ajax({
	        type: "POST",
	        url: '{{url("penjualan/open_pembayaran")}}',
	        async:true,
	        data: {
	        	_token:"{{csrf_token()}}",
	        	harga_total:$("#total_pembayaran_input").val(),
	        },
	        beforeSend: function(data){
	          // on_load();
	        	$('#modal-lg').find('.modal-lg').find(".modal-content").find(".modal-header").attr("class","modal-header bg-light-blue");
	            $("#modal-lg .modal-title").html("Pembayaran");
	            $('#modal-lg').modal("show");
	            $('#modal-lg').find('.modal-body-content').html('');
	            $("#modal-lg").find(".overlay").fadeIn("200");
	        },
	        success:  function(data){
	          	$('#modal-lg').find('.modal-body-content').html(data);
	        },
	        complete: function(data){
	            $("#modal-lg").find(".overlay").fadeOut("200");
	        },
	        error: function(data) {
	            alert("error ajax occured!");
	        }
	    });
	}

	function save_data(){
		if($(".validated_form").valid()) {
			data = {};
			$("#form_pembelian").find("input[name], select").each(function (index, node) {
		        data[node.name] = node.value;
		    });

			//document.form_penjualan.submit() ;
			$("#form_pembelian").submit();
			/*$.ajax({
				type:"POST",
				url : '{{url("penjualan/")}}',
				dataType : "json",
				data : data,
				beforeSend: function(data){
					// replace dengan fungsi loading
				},
				success:  function(data){
					if(data.status ==1){
						show_info("Data penjualan berhasil disimpan!");
						$('#modal-lg').modal("hide");
					}else{
						show_error("Gagal menyimpan data penjualan ini!");
						return false;
					}
				},
				complete: function(data){
					// replace dengan fungsi mematikan loading
				},
				error: function(data) {
					show_error("error ajax occured!");
				}

			})*/
		} else {
			return false;
		}
	}

	function find_ketentuan_keyboard(){
	    $.ajax({
	        type: "POST",
	        url: '{{url("pembelian/find_ketentuan_keyboard")}}',
	        async:true,
	        data: {
	        	_token:"{{csrf_token()}}",
	        },
	        beforeSend: function(data){
	          // on_load();
	        $('#modal-lg').find('.modal-lg').find(".modal-content").find(".modal-header").attr("class","modal-header bg-info");
	        $("#modal-lg .modal-title").html("Ketentuan Kode Keyboard");
	        $('#modal-lg').modal("show");
	        $('#modal-lg').find('.modal-body-content').html('');
	        $("#modal-lg").find(".overlay").fadeIn("200");
	        },
	        success:  function(data){
	          $('#modal-lg').find('.modal-body-content').html(data);
	        },
	        complete: function(data){
	            $("#modal-lg").find(".overlay").fadeOut("200");
	        },
	          error: function(data) {
	            alert("error ajax occured!");
	          }
	    });
	}

	function edit_detail(no, id){
	    $.ajax({
	        type: "POST",
	        url: '{{url("pembelian/edit_detail")}}',
	        async:true,
	        data: {
	        	_token:"{{csrf_token()}}",
	        	no : no,
	        	id : id,
	        },
	        beforeSend: function(data){
	          // on_load();
	        $('#modal-xl').find('.modal-xl').find(".modal-content").find(".modal-header").attr("class","modal-header bg-light-blue");
	        $("#modal-xl .modal-title").html("Edit Data Pembelian");
	        $('#modal-xl').modal("show");
	        $('#modal-xl').find('.modal-body-content').html('');
	        $("#modal-xl").find(".overlay").fadeIn("200");
	        },
	        success:  function(data){
	          $('#modal-xl').find('.modal-body-content').html(data);
	        },
	        complete: function(data){
	            $("#modal-xl").find(".overlay").fadeOut("200");
	        },
	          error: function(data) {
	            alert("error ajax occured!");
	          }
	    });
	}

    function edit_detail_from_order(no, id_detail_order, id_obat){
        $.ajax({
            type: "POST",
            url: '{{url("pembelian/edit_detail_from_order")}}',
            async:true,
            data: {
                _token:"{{csrf_token()}}",
                no : no,
                id_detail_order : id_detail_order,
                id_obat : id_obat
            },
            beforeSend: function(data){
              // on_load();
            $('#modal-xl').find('.modal-xl').find(".modal-content").find(".modal-header").attr("class","modal-header bg-light-blue");
            $("#modal-xl .modal-title").html("Edit Data Pembelian");
            $('#modal-xl').modal("show");
            $('#modal-xl').find('.modal-body-content').html('');
            $("#modal-xl").find(".overlay").fadeIn("200");
            },
            success:  function(data){
              $('#modal-xl').find('.modal-body-content').html(data);
            },
            complete: function(data){
                $("#modal-xl").find(".overlay").fadeOut("200");
            },
              error: function(data) {
                alert("error ajax occured!");
              }
        });
    }


	function deleteRow(r) {
        var i = r.parentNode.parentNode.rowIndex;
        document.getElementById("tb_nota_pembelian").deleteRow(i);
        hitung_total();
    }

    function hapus_detail(r, id){
        swal({
            title: "Apakah anda yakin menghapus data ini?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Ya",
            cancelButtonText: "Tidak",
            closeOnConfirm: false
        },
        function(){
            $.ajax({
                type: "GET",
                url: '{{url("pembelian/hapus_detail/")}}/'+id,
                async:true,
                data: {
                    _token:token,
                    id:id
                },
                beforeSend: function(data){
                    // replace dengan fungsi loading
                },
                success:  function(data){
                    if(data==1){
                        var i = r.parentNode.parentNode.rowIndex;
                        document.getElementById("tb_nota_pembelian").deleteRow(i);
                        swal("Deleted!", "Item pembelian berhasil dihapus.", "success");
                    }else{
                        swal("Failed!", "Gagal menghapus item pembelian.", "error");
                    }
                },
                complete: function(data){
                    hitung_total();
                },
                error: function(data) {
                    swal("Error!", "Ajax occured.", "error");
                }
            });
        });
    }

    function change_obat(no, id_detail_pembelian) {
        $.ajax({
            type: "POST",
            url: '{{url("pembelian/change_obat")}}',
            async:true,
            data: {
                _token  : "{{csrf_token()}}",
                no : no,
                id_detail_pembelian : id_detail_pembelian,
            },
            beforeSend: function(data){
                // on_load();
                $('#modal-xl').find('.modal-xl').find(".modal-content").find(".modal-header").attr("class","modal-header bg-light-blue");
                $("#modal-xl .modal-title").html("Pembelian- Ganti Obat");
                $('#modal-xl').modal("show");
                $('#modal-xl').find('.modal-body-content').html('');
                $("#modal-xl").find(".overlay").fadeIn("200");
            },
            success:  function(data){
                $('#modal-xl').find('.modal-body-content').html(data);
            },
            complete: function(data){
                $("#modal-xl").find(".overlay").fadeOut("200");
            },
              error: function(data) {
                alert("error ajax occured!");
              }
        });
    }
</script>