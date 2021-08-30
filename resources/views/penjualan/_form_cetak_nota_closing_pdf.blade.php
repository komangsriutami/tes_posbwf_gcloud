<html lang="en">
    <head>
        
        <style type="text/css">
            h1, h2, h3, h4, h5{
                font-family: Times;
                font-size: 10pt;
            }

            body{
                font-family: Times;
                font-size: 10pt;

            }

            .header{
                text-align: center;
                border-bottom: double 4px black;
                margin-bottom: 15px; 
            }

            .header h1, .header h2, .header h3, .header h4, .header h5, .header p{
                margin: 0;
                padding: 0;
            }

            /*table tr td,table tr th{
                padding: 3px 7px 3px 7px;
            }*/

            table.table{
                border-collapse: collapse;
                table-layout: fixed;
                width: 100%;
            }

            table.table tr th, table.table tr td{
                border:1px black solid!important;
                font-weight: normal;
                font-size: 10pt;
            }
        </style>
    </head>

    <body style="word-wrap: break-word; text-align: justify; ">
        <?php
            $nama_apotek = strtoupper($apotek->nama_panjang);
            $total_jasa_dokter = number_format($penjualan_closing->total_jasa_dokter_a,0,',',',');
            $total_jasa_resep = number_format($penjualan_closing->total_jasa_resep_a,0,',',',');
            $total_paket_wd = number_format($penjualan_closing->total_paket_wd_a,0,',',',');
            $total_penjualan = number_format($penjualan_closing->total_penjualan_a,0,',',',');
            $total_debet = number_format($penjualan_closing->total_debet_a,0,',',',');
            $total_penjualan_cash = number_format($penjualan_closing->total_penjualan_cash_a,0,',',',');
            $total_penjualan_cn = number_format($penjualan_closing->total_penjualan_cn_a,0,',',',');
            $total_penjualan_kredit = number_format($penjualan_closing->total_penjualan_kredit_a,0,',',',');
            $total_penjualan_kredit_terbayar = number_format($penjualan_closing->total_penjualan_kredit_terbayar_a,0,',',',');
            $total_diskon = number_format($penjualan_closing->total_diskon_a,0,',',',');
            $uang_seharusnya = number_format($penjualan_closing->uang_seharusnya_a,0,',',',');
            $total_akhir = number_format($penjualan_closing->total_akhir_a,0,',',',');
            $jumlah_tt = number_format($penjualan_closing->jumlah_tt_a,0,',',',');
        ?>
        <div class="header" style="position: relative; padding-bottom: 10px;">
            <img  src="{{asset('assets/dist/img/logo.png')}}"  alt='image' style="position: absolute; left:0; top:0; width: 100;">
            <div style="position:absolute; top:20px; margin-left:250px;">
                <h5>APOTEK {{ $nama_apotek }}</h5>
                <h4 style="font-weight: bolder;">PT. BAKTI WIDYA FARMA</h4>
                <p>Alamat : {{ $apotek->alamat }}</p>
                <p>Telp : {{ $apotek->telepon }}</p>
                <p>Website : www.apotekbwf.com</p>
            </div>
        </div>

        <h4 style="text-align: center;font-weight: bolder; font-size: 10pt;margin: none!important;padding: none!important;"><b>LAPORAN PENJUALAN HARIAN</b></h4>
        <p style="text-align: center;margin-top: none!important;padding: none!important;">Tanggal : {{ $tanggal }}</p>
        
        <div style="margin-bottom: 20px;">
            <table width="100%" style="border: none;">
                <tbody>
                    <tr>
                        <td width="29%">Total Jasa Dokter</td>
                        <td width="1%"> : </td>
                        <td width="70%">Rp&nbsp;{{$total_jasa_dokter }}</td>
                    </tr>
                    <tr>
                        <td width="29%">Total Jasa Dokter</td>
                        <td width="1%"> : </td>
                        <td width="70%">Rp&nbsp;{{$total_jasa_dokter }}</td>
                    </tr>
                    <tr>
                        <td width="29%">Total Jasa Dokter</td>
                        <td width="1%"> : </td>
                        <td width="70%">Rp&nbsp;{{$total_jasa_dokter }}</td>
                    </tr>
                    <tr>
                        <td width="29%">Total Jasa Resep</td>
                        <td width="1%"> : </td>
                        <td width="70%">Rp&nbsp;{{ $total_jasa_resep }}</td>
                    </tr>
                    <tr>
                        <td width="29%">Total Paket WD</td>
                        <td width="1%"> : </td>
                        <td width="70%">Rp&nbsp;{{ $total_paket_wd }}</td>
                    </tr>
                    <tr>
                        <td width="29%">Total Penjualan</td>
                        <td width="1%"> : </td>
                        <td width="70%">Rp&nbsp;{{$total_penjualan }}</td>
                    </tr>
                    <tr>
                        <td width="29%">Total Debet/Credit</td>
                        <td width="1%"> : </td>
                        <td width="70%">Rp&nbsp;{{ $total_debet }}</td>
                    </tr>
                    <tr>
                        <td width="29%">Total Penjualan Cash</td>
                        <td width="1%"> : </td>
                        <td width="70%">Rp&nbsp;{{ $total_penjualan_cash }}</td>
                    </tr>
                    <tr>
                        <td width="29%">Total Retur</td>
                        <td width="1%"> : </td>
                        <td width="70%">Rp&nbsp;{{ $total_penjualan_cn }}</td>
                    </tr>
                    <tr>
                        <td width="29%">Total Retur</td>
                        <td width="1%"> : </td>
                        <td width="70%">Rp&nbsp;{{ $total_penjualan_cn }}</td>
                    </tr>
                    <tr>
                        <td width="29%">Total Penjualan K.</td>
                        <td width="1%"> : </td>
                        <td width="70%">Rp&nbsp;{{ $total_penjualan_kredit }}</td>
                    </tr>
                    <tr>
                        <td width="29%">Total K. Terbayar</td>
                        <td width="1%"> : </td>
                        <td width="70%">Rp&nbsp;{{ $total_penjualan_kredit_terbayar }}</td>
                    </tr>
                    <tr>
                        <td width="29%">Total Diskon Nota</td>
                        <td width="1%"> : </td>
                        <td width="70%">Rp&nbsp;{{ $total_diskon }}</td>
                    </tr>
                    <tr>
                        <td width="29%">Uang Seharusnya</td>
                        <td width="1%"> : </td>
                        <td width="70%">Rp&nbsp;{{ $uang_seharusnya }}</td>
                    </tr>
                    <tr>
                        <td width="29%">Total TT</td>
                        <td width="1%"> : </td>
                        <td width="70%">Rp&nbsp;{{ $jumlah_tt }}</td>
                    </tr>
                    <tr>
                        <td width="29%">Total Akhir</td>
                        <td width="1%"> : </td>
                        <td width="70%">Rp&nbsp;{{ $total_akhir }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" width="100%"><p>Catatan : K= Kredit, TT = Tidak Terdeteksi</p></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </body>
</html>