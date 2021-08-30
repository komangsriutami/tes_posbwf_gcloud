<!-- ============================================================== -->
<!-- All Jquery -->
<!-- ============================================================== -->
{!! Html::script('assets/plugins/jquery/jquery.min.js') !!}
<!-- jQuery UI 1.11.4 -->
<!-- {!! Html::script('assets/plugins/jquery-ui/jquery-ui.min.js') !!} -->
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<!-- jquery-ui -->
{!! Html::script('assets/plugins/jquery-ui-1.12.1/jquery-ui.min.js') !!}
<script>
    $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Bootstrap 4 -->
{!! Html::script('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') !!}
<!-- Select2 -->
{!! Html::script('assets/plugins/select2/js/select2.full.min.js') !!}
<!-- jquery-validation -->
{!! Html::script('assets/plugins/jquery-validation/jquery.validate.min.js') !!}
{!! Html::script('assets/plugins/jquery-validation/additional-methods.min.js') !!}
<!-- ChartJS -->
{!! Html::script('assets/plugins/chart.js/Chart.min.js') !!}
<!-- Sparkline -->
<!-- {!! Html::script('assets/plugins/sparklines/sparkline.j') !!} -->
<!-- JQVMap -->
<!-- {!! Html::script('assets/plugins/jqvmap/jquery.vmap.min.js') !!}
{!! Html::script('assets/plugins/jqvmap/maps/jquery.vmap.usa.js') !!} -->
<!-- jQuery Knob Chart --><!-- 
{!! Html::script('assets/plugins/jquery-knob/jquery.knob.min.js') !!} -->
<!-- daterangepicker -->
{!! Html::script('assets/plugins/moment/moment.min.js') !!}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.js" integrity="sha512-0XDfGxFliYJPFrideYOoxdgNIvrwGTLnmK20xZbCAvPfLGQMzHUsaqZK8ZoH+luXGRxTrS46+Aq400nCnAT0/w==" crossorigin="anonymous"></script>
{!! Html::script('assets/plugins/daterangepicker/daterangepicker.js') !!}
<!-- Tempusdominus Bootstrap 4 -->
{!! Html::script('assets/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') !!}
<!-- Summernote -->
{!! Html::script('assets/plugins/summernote/summernote-bs4.min.js') !!}
<!-- overlayScrollbars -->
{!! Html::script('assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') !!}
<!-- pace-progress -->
{!! Html::script('assets/plugins/pace-progress/pace.min.js') !!}
<!-- dataTables js -->
{!! Html::script('assets/plugins/datatables2/jquery.dataTables.min.js') !!}
{!! Html::script('assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') !!}
{!! Html::script('assets/plugins/datatables-responsive/js/dataTables.responsive.min.js') !!}
{!! Html::script('assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') !!}
{!! Html::script('assets/plugins/datatables_editor/js/dataTables.editor.min.js') !!}
{!! Html::script('assets/plugins/datatables_editor/js/editor.bootstrap4.min.js') !!}
{!! Html::script('assets/plugins/datatables_editor/js/editor.bootstrap4.min.js') !!}

<!-- AdminLTE App -->
{!! Html::script('assets/dist/js/adminlte.js') !!}
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
{!! Html::script('assets/dist/js/pages/dashboard.js') !!}
<!-- AdminLTE for demo purposes -->
{!! Html::script('assets/dist/js/demo.js') !!}

<!-- Nested Sorting -->
{!! Html::script('assets/plugins/sorting/jquery.mjs.nestedSortable.js') !!}

{!! Html::script('assets/plugins/sweetalert/sweetalert.min.js') !!}
{!! Html::script('assets/plugins/fullcalendar/fullcalendar.min.js') !!}

{!! Html::script('assets/plugins/datepicker/bootstrap-datepicker.js') !!}

{!! Html::script('assets/qz-tray/dependencies/rsvp-3.1.0.min.js') !!}
{!! Html::script('assets/qz-tray/dependencies/sha-256.min.js') !!}
{!! Html::script('assets/qz-tray/qz-tray.js') !!}
{!! Html::script('assets/qz-tray/qz_print_script.js') !!}

<script>
     /** add active class and stay opened when selected */
    var url = window.location.href.split('#')[0];
    var spinner = $('#loader');

    // for sidebar menu entirely but not cover treeview
	$('ul.nav-sidebar a').filter(function() {
	    return this.href == url;
	}).addClass('active');

	// for treeview
	$('ul.nav-treeview a').filter(function() {
	    return this.href == url;
	}).parentsUntil(".nav-sidebar > .nav-treeview").addClass('menu-open').prev('a').addClass('active');


	$('.checkAlltogle').click(function(event) {
        if(this.checked) {
            $('#'+$(this).parents("table").attr('id')+' :checkbox').each(function() {
                this.checked = true;
            });
        }
        else{
            $('#'+$(this).parents("table").attr('id')+' :checkbox').each(function(){
                this.checked = false;
            });
        }
    });

    function set_active_apotek(id, nama){
        swal({
            title: 'Apotek '+nama,
            text: "Apakah anda yakin akan melakukan perubahan apotek menjadi Apotek "+nama+"? Yang sedang anda kerjakan akan tidak disimpan.",
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
                url: '{{url("set_active_apotek")}}/'+id,
                async:true,
                beforeSend: function(data){
                    // replace dengan fungsi loading
                },
                success:  function(data){
                    swal("Success!", "Apotek "+nama+" telah aktif.", "success");
                    location.reload();
                },
                complete: function(data){
                    //tb_dokter.fnDraw(false);
                },
                error: function(data) {
                    swal("Error!", "Ajax occured.", "error");
                }
            });
        });
    }

    function set_active_role(id, nama){
        swal({
            title: 'Role '+nama,
            text: "Apakah anda yakin akan melakukan perubahan role menjadi "+nama+"? Yang sedang anda kerjakan akan tidak disimpan.",
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
                url: '{{url("set_active_role")}}/'+id,
                async:true,
                beforeSend: function(data){
                    // replace dengan fungsi loading
                },
                success:  function(data){
                    swal("Success!", "Role "+nama+" telah aktif.", "success");
                    location.reload();
                },
                complete: function(data){
                    //tb_dokter.fnDraw(false);
                },
                error: function(data) {
                    swal("Error!", "Ajax occured.", "error");
                }
            });
        });
    }

    function set_active_tahun(id){
        swal({
            title: 'Change Tahun',
            text: "Apakah anda yakin akan melakukan perubahan tahun menjadi "+id+"? Yang sedang anda kerjakan akan tidak disimpan.",
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
                url: '{{url("set_active_tahun")}}/'+id,
                async:true,
                beforeSend: function(data){
                    // replace dengan fungsi loading
                },
                success:  function(data){
                    swal("Success!", "Tahun "+id+" telah aktif.", "success");
                    location.reload();
                },
                complete: function(data){
                    //tb_dokter.fnDraw(false);
                },
                error: function(data) {
                    swal("Error!", "Ajax occured.", "error");
                }
            });
        });
    }

    /*$(function() {
      $('form').submit(function(e) {
        e.preventDefault();
        spinner.show();
        $.ajax({
          url: 't2228.php',
          data: $(this).serialize(),
          method: 'post',
          dataType: 'JSON'
        }).done(function(resp) {
          spinner.hide();
          alert(resp.status);
        });
      });
    });*/
</script>


