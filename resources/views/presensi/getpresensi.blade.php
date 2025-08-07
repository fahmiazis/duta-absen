<?php
function selisih($jam_batas, $jam_masuk)
{
    if (!$jam_batas || !$jam_masuk) return "-";
    if (!str_contains($jam_batas, ':') || !str_contains($jam_masuk, ':')) return "-";

    list($h, $m, $s) = explode(":", $jam_batas);
    $dtBatas = mktime((int)$h, (int)$m, (int)$s, 1, 1, 1);
    
    list($h, $m, $s) = explode(":", $jam_masuk);
    $dtMasuk = mktime((int)$h, (int)$m, (int)$s, 1, 1, 1);
    
    $dtSelisih = $dtMasuk - $dtBatas;

    if ($dtSelisih <= 0) {
        return "Tepat Waktu";
    }

    $jam = floor($dtSelisih / 3600);
    $menit = floor(($dtSelisih % 3600) / 60);

    return $jam . " Jam : " . $menit . " Menit";
}
?>
@foreach ($presensi as $d)
    <tr>
        <td style='text-align: center;'>{{ $loop->iteration }}</td>
        <td>{{ $d->nisn }}</td>
        <td>{{ $d->nama_lengkap }}</td>
        <td style='text-align: center;'>{{ $d->kelas }}</td>
        <td>{{ $d->nama_jurusan }}</td>
        <td style='text-align: center;'>
            {{ $d->jam_in ?? 'Kosong' }}
        </td>
        <td style='text-align: center;'>{!! $d->jam_out != null ? $d->jam_out : '<span class="badge bg-danger">Belum Absen</span>' !!}</td>
        <td style='text-align: center;'>
            @if($d->jam_in && $d->jam_in >= $jamMasuk)
                @php $jamterlambat = selisih($jamMasuk, $d->jam_in); @endphp
                <span class="badge bg-danger">Terlambat<br>{{ $jamterlambat }}</span>
            @elseif($d->jam_in)
                <span class="badge bg-success">Tepat Waktu</span>
            @else
                <span class="badge bg-warning">Belum Absen</span>
            @endif
        </td>
        <td>
            <a href="#" class="btn btn-primary peta_jam_masuk" id="{{ $d->id }}" style="font-size:8pt; padding:2px 6px; height:auto; line-height:1;">
                <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-map-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 18.5l-3 -1.5l-6 3v-13l6 -3l6 3l6 -3v7.5" /><path d="M9 4v13" /><path d="M15 7v5.5" /><path d="M21.121 20.121a3 3 0 1 0 -4.242 0c.418 .419 1.125 1.045 2.121 1.879c1.051 -.89 1.759 -1.516 2.121 -1.879z" /><path d="M19 18v.01" /></svg>
                Masuk
            </a>
            <a href="#" class="btn btn-primary peta_jam_pulang" id="{{ $d->id }}" style="font-size:8pt; padding:2px 6px; height:auto; line-height:1;">
                <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-map-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 18.5l-3 -1.5l-6 3v-13l6 -3l6 3l6 -3v7.5" /><path d="M9 4v13" /><path d="M15 7v5.5" /><path d="M21.121 20.121a3 3 0 1 0 -4.242 0c.418 .419 1.125 1.045 2.121 1.879c1.051 -.89 1.759 -1.516 2.121 -1.879z" /><path d="M19 18v.01" /></svg>
                Pulang
            </a>
        </td>
    </tr>
@endforeach
<script>
    $(function(){
        $(".peta_jam_masuk").click(function(e){
            var id = $(this).attr("id");
            $.ajax({
                type:'POST',
                url:'/peta_jam_masuk',
                data:{
                    _token:"{{ csrf_token() }}",
                    id: id
                },
                cache:false,
                success:function(respond){
                    $("#loadmap_jam_masuk").html(respond);
                }
            });
            $("#modal-peta_jam_masuk").modal("show");
        });
        $(".peta_jam_pulang").click(function(e){
            var id = $(this).attr("id");
            $.ajax({
                type:'POST',
                url:'/peta_jam_pulang',
                data:{
                    _token:"{{ csrf_token() }}",
                    id: id
                },
                cache:false,
                success:function(respond){
                    $("#loadmap_jam_pulang").html(respond);
                }
            });
            $("#modal-peta_jam_pulang").modal("show");
        });

        $('#modal-peta_jam_masuk').on('hidden.bs.modal', function () {
            $('#loadmap_jam_masuk').html('');
        });
        $('#modal-peta_jam_pulang').on('hidden.bs.modal', function () {
            $('#loadmap_jam_pulang').html('');
        });
    });
</script>