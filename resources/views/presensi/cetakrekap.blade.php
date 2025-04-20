<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>A4</title>

  <!-- Normalize or reset CSS with your favorite library -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/7.0.0/normalize.min.css">

  <!-- Load paper.css for happy printing -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/paper-css/0.4.1/paper.css">

  <!-- Set page size here: A5, A4 or A3 -->
  <!-- Set also "landscape" if you need -->
  <style>
    @page { 
        size: F4 
    }
    #title {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 18px;
        font-weight: bold;
    }
    .tabeldatamurid {
        margin-top: 40px;
    }
    .tabeldatamurid tr td {
        padding: 5px;
    }
    .tabelpresensi {
        width: 100%;
        margin-top: 20px;
        border-collapse: collapse;
    }
    .tabelpresensi tr th {
        border: 1px solid #131212;
        padding: 8px;
        background-color: #dbdbdb;
        font-size: 10px;
    }
    .tabelpresensi tr td {
        border: 1px solid #131212;
        padding: 5px;
        font-size: 12px;
    }
    .foto {
        width: 40px;
        height: 30px;
    }
  </style>
</head>

<!-- Set "A5", "A4" or "A3" for class name -->
<!-- Set also "landscape" if you need -->
<body class="legal landscape">
    <?php
    function selisih($jam_masuk, $jam_keluar)
    {
        list($h, $m, $s) = explode(":", $jam_masuk);
        $dtAwal = mktime($h, $m, $s, "1", "1", "1");
        list($h, $m, $s) = explode(":", $jam_keluar);
        $dtAkhir = mktime($h, $m, $s, "1", "1", "1");
        $dtSelisih = $dtAkhir - $dtAwal;
        $totalmenit = $dtSelisih / 60;
        $jam = explode(".", $totalmenit / 60);
        $sisamenit = ($totalmenit / 60) - $jam[0];
        $sisamenit2 = $sisamenit * 60;
        $jml_jam = $jam[0];
        return $jml_jam . ":" . round($sisamenit2);
    }
    ?>

  <!-- Each sheet element should have the class "sheet" -->
  <!-- "padding-**mm" is optional: you can set 10, 15, 20 or 25 -->
  <section class="sheet padding-10mm">

    <table style="width: 100%;">
        <tr>
            <td style="width: 30px;">
                <img src="{{ asset('assets/img/logo_smkn2.png') }}" width="75" height="80" alt="">
            </td>
            <td>
                <span id="title">
                    REKAP PRESENSI MURID<br>
                    BULAN {{ strtoupper($namabulan[$bulan]) }} {{ $tahun }}<br>
                    SMK NEGERI 2 KALIANDA<br>
                </span>
                <span><i>Jl. Soekarno-hatta Km.52 Kalianda, Kecamatan Kalianda, Kabupaten Lampung Selatan, Lampung.</i></span>
            </td>
        </tr>
    </table>
    
    <table class="tabelpresensi">
        <tr>
            <th rowspan="2">No.</th>
            <th rowspan="2">NISN</th>
            <th rowspan="2">Nama Murid</th>
            <th colspan="31">Tanggal</th>
            <th rowspan="2" style="vertical-align: top; background-color: green; color: white;">
                H<br>a<br>d<br>i<br>r
            </th>
            <th rowspan="2" style="vertical-align: top; background-color: saddlebrown; color: white;">
                T<br>e<br>r<br>l<br>a<br>m<br>b<br>a<br>t
            </th>
            <th rowspan="2" style="vertical-align: top; background-color: red; color: white;">
                A<br>l<br>f<br>a
            </th>
            <th rowspan="2" style="vertical-align: top; background-color: yellow; color: black;">
                I<br>z<br>i<br>n
            </th>
            <th rowspan="2" style="vertical-align: top; background-color: blue; color: white;">
                S<br>a<br>k<br>i<br>t
            </th>
            <th rowspan="2" style="vertical-align: top; background-color: purple; color: white;">
                B<br>o<br>l<br>o<br>s
            </th>
            <th rowspan="2" style="vertical-align: top;">T<br>o<br>t<br>a<br>l</th>
        </tr>
        <tr>
            <?php
            for($i=1; $i<=31; $i++){
            ?>
            <th>{{ $i }}</th>
            <?php
            }
            ?>
        </tr>
        @foreach ($rekap as $d)
        <tr>
            <td></td>    
            <td>{{ $d->nisn }}</td>
            <td>{{ $d->nama_lengkap }}</td>
            <?php
            $totalhadir = 0;
            $totalterlambat = 0;
            for($i=1; $i<=31; $i++){
                $tgl = "tgl_".$i;
                if(empty($d->$tgl)){
                    $hadir = ['',''];
                    $totalhadir += 0;
                } else {
                    $hadir = explode("-",$d->$tgl);
                    $totalhadir += 1;
                    if($hadir[0] > "07:00:00"){
                        $totalterlambat +=1;
                    }
                }
            ?>
            <td style="text-align: center;">
                @if(!empty($d->$tgl))
                    @php
                        $jam_masuk = $hadir[0];
                        $jam_pulang = $hadir[1];
                    @endphp
                        
                    @if($jam_masuk <= "07:00:00" && $jam_pulang >= "16:00:00")
                        {{-- Hadir tepat waktu --}}
                        <div style="width: 10px; height: 10px; background-color: green; margin: auto;"></div>
                    @elseif($jam_masuk > "07:00:00" && $jam_pulang >= "16:00:00")
                        {{-- Terlambat tapi tetap pulang --}}
                        <div style="width: 10px; height: 10px; background-color: green; margin: auto;"></div>
                        <div style="width: 10px; height: 10px; background-color: saddlebrown; margin: auto;"></div>
                    @else
                        {{-- Kondisi lainnya misal masuk tapi tidak pulang, atau tidak valid --}}
                        <div style="width: 10px; height: 10px; background-color: red; margin: auto;"></div>
                    @endif
                @else
                    {{-- Tidak ada data masuk dan pulang (alfa) --}}
                    <div style="width: 10px; height: 10px; background-color: red; margin: auto;"></div>
                @endif
            </td>
            <?php
            }
            ?>
            <td>{{ $totalhadir }}</td>
            <td>{{ $totalterlambat }}</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        @endforeach
    </table>

    <table width="100%" style="margin-top: 100px;">
        <tr>
            <td></td>
            <td style="text-align: center;">Kalianda, {{ date('d-m-Y') }}</td>
        </tr>
        <tr>
            <td style="text-align: center; vertical-align:bottom" height="100px">
                <u>Nama Wali Kelas</u><br>
                <i><b>Jabatan</b></i>
            </td>
            <td style="text-align: center; vertical-align:bottom">
                <u>Nama Kepala Sekolah</u><br>
                <i><b>Jabatan</b></i>
            </td>
        </tr>
    </table>
  </section>

</body>

</html>