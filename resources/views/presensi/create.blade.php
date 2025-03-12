@extends('layouts.presensi')
@section('header')
<!-- App Header -->
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Halaman Absen</div>
    <div class="right"></div>
</div>
<!-- * App Header -->
 <style>
    .webcam-capture,
    .webcam-capture video{
        display: inline-block;
        width: 100% !important;
        margin: auto;
        height: auto !important;
        border-radius: 15px;
    }
    #map {
        height: 300px;
    }
 </style>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endsection
@section('content')
<!-- App Capsule -->
<div class="row" style="margin-top: 70px;">
    <div class="col">
        <!-- Bagian Lokasi User -->
        <input type="hidden" id="lokasi" disabled>
    </div>
</div>

<div class="row mt-2 mb-4">
    <div class="col">
        <div id="map"></div>
    </div>
</div>

<div class="row">
    <div class="col">
        @if ($cek > 0)
        <button id="takeabsen" class="btn btn-danger btn-block">
            <!-- <ion-icon name="camera-outline"></ion-icon> -->
            Absen Pulang
        </button>
        @else
        <button id="takeabsen" class="btn btn-primary btn-block" >
            <!-- <ion-icon name="camera-outline"></ion-icon> -->
            Absen Masuk
        </button>
        @endif
    </div>
</div>


<audio id="notifikasi_in">
    <source src="{{ asset('assets/sound/notifikasi_in.mp3') }}" type="audio/mpeg">
</audio>
<audio id="notifikasi_out">
    <source src="{{ asset('assets/sound/notifikasi_out.mp3') }}" type="audio/mpeg">
</audio>
<audio id="radius_sound">
    <source src="{{ asset('assets/sound/radius.mp3') }}" type="audio/mpeg">
</audio>
@endsection

@push('myscript')
<script>

    var notifikasi_in = document.getElementById('notifikasi_in');
    var notifikasi_out = document.getElementById('notifikasi_out');
    var radius_sound = document.getElementById('radius_sound');
    // Webcam.set({
    //     height:480,
    //     widht:640,
    //     image_format:'jpeg',
    //     jpeg_quality:80
    // });

    // Webcam.attach('.webcam-capture');

    var lokasi = document.getElementById('lokasi');
    if(navigator.geolocation){
        navigator.geolocation.getCurrentPosition(successCallback, errorCallback);
    }

    function successCallback(position) {
        // Lokasi Value Adalah Titik Lokasinya User
        //lokasi.value = position.coords.latitude + "," + position.coords.longitude;
        lokasi.value = -5.390264357938437 + "," + 105.24105702588515;
        //lokasi.value = lat_kantor + "," + long_kantor;
        
        var lokasi_kantor = "{{ $lok_kantor->lokasi_kantor }}";
        var lok = lokasi_kantor.split(",");
        var lat_kantor = lok[0];
        var long_kantor = lok[1];

        //var map = L.map('map').setView([position.coords.latitude, position.coords.longitude], 28);
        //var map = L.map('map').setView([lat_kantor, long_kantor], 28);
        //var map = L.map('map').setView([-5.390336, 105.2409856], 28);
        var map = L.map('map').setView([-5.390264357938437, 105.24105702588515], 28);

        var radius = "{{ $lok_kantor->radius }}"

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        //var marker = L.marker([position.coords.latitude, position.coords.longitude]).addTo(map);
        //var marker = L.marker([-5.390336, 105.2409856]).addTo(map);
        var marker = L.marker([-5.390264357938437, 105.24105702588515]).addTo(map);

        //var circle = L.circle([-5.390336, 105.59125199541869], {
        //var circle = L.circle([position.coords.latitude, position.coords.longitude], {
        var circle = L.circle([lat_kantor, long_kantor], {
            color: 'red',
            fillColor: '#f03',
            fillOpacity: 0.5,
            radius: radius
        }).addTo(map);
    }

    function errorCallback() {
        s
    }

    $("#takeabsen").click(function(e) {
        e.preventDefault(); // Hindari perilaku default
        
        const name = "{{ Auth::guard('murid')->user()->nama_lengkap }}";
        const nisn = "{{ Auth::guard('murid')->user()->nisn }}";
        const photo = "{{ Auth::guard('murid')->user()->foto }}";
        var lokasi = $('#lokasi').val();
        
        // Cek status absensi terlebih dahulu sebelum mengarahkan ke halaman absensi
        $.ajax({
            url: "{{ route('cek.absen') }}", // Ganti dengan route untuk mengecek absensi siswa
            type: "GET",
            data: { nisn: nisn },
            success: function(response) {
                let absen_masuk = false;
                let absen_pulang = false;

                if (response.absen_masuk && response.absen_pulang) {
                    // Sudah absen masuk, hanya perlu absen pulang
                    absen_masuk = true;
                    absen_pulang = false;
                } else if (!response.absen_masuk && !response.absen_pulang) {
                    // Belum absen masuk, maka hanya absen masuk
                    absen_masuk = false;
                    absen_pulang =false;
                } else {
                    // Jika sudah absen masuk dan pulang
                    absen_masuk = true;
                    absen_pulang = true;
                }
            
                // Arahkan ke halaman absensi dengan parameter yang sesuai
                window.location.href = `http://localhost:9000?name=${encodeURIComponent(name)}&nisn=${encodeURIComponent(nisn)}&photo=${photo}&lokasi=${lokasi}&absen_masuk=${absen_masuk}&absen_pulang=${absen_pulang}`;
            },
            error: function() {
                alert("Terjadi kesalahan saat mengambil data absensi.");
            }
        });
    });


    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        const nisn = urlParams.get('nisn');
        const absen = urlParams.get('absen'); // Bisa "masuk" atau "pulang"
        const lokasi = urlParams.get('lokasi');
        
        const trueNisn = "{{ Auth::guard('murid')->user()->nisn }}";
        
        console.log(trueNisn === nisn);
        console.log(absen === 'masuk' || absen === 'pulang');
        
        if ((absen === 'masuk' || absen === 'pulang') && nisn === trueNisn) {
            console.log('harusnya running ajax')
            let dataAbsen = {
                _token: "{{ csrf_token() }}",
                nisn: nisn,
                absen: absen, // Kirim status absen (masuk/pulang)
                lokasi: lokasi
            };
        
            $.ajax({
                type: 'POST',
                url: '/presensi/store',
                data: dataAbsen,
                cache: false,
                success: function(respond) {
                    var status = respond.split("|");
                    if (status[0] == "success") {
                        if (status[2] == "in") {
                            notifikasi_in.play();
                        } else {
                            notifikasi_out.play();
                        }
                        Swal.fire({
                            title: 'Berhasil !',
                            text: status[1],
                            icon: 'success'
                        });
                        setTimeout(() => location.href = '/dashboard', 10000);
                    } else {
                        if (status[2] == 'radius') {
                            radius_sound.play();
                        }
                        Swal.fire({
                            title: 'Error !',
                            text: status[1],
                            icon: 'error'
                        });
                        setTimeout(() => location.href = '/dashboard', 10000);
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Terjadi kesalahan saat mengirim data.',
                        icon: 'error'
                    });
                }
            });
        }
    });

</script>
@endpush
