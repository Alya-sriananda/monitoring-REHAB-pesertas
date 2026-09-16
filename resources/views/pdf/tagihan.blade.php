<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tagihan REHAB - {{ $caseData->noka_pendaftar }}</title>
    <style>
        @page {
            margin: 100px 40px 100px 40px;
        }
        body {
            padding: 0px 40px 0px 40px ;
            font-family: Arial, sans-serif;
            font-size: 9pt; /* Approx 14.6px */
            color: #000;
            line-height: 1.4;
        }
        .logo-header {
            position: fixed;
            top: -90px;
            left: 0;
            width: 250px;
        }
        .footer-image {
            position: fixed;
            bottom: -100px;
            left: -40px;
            right: -40px;
            width: 115%;
            height: 30px;
            z-index: -1000;
        }
        .content {
            margin-top: 10px;
        }
        .info-table {
            width: 100%;
            margin-top: 15px;
            margin-bottom: 25px;
        }
        .info-table td {
            vertical-align: top;
            padding: 2px 0;
        }
        .info-table .label {
            width: 210px;
        }
        .info-table .colon {
            width: 15px;
        }
        .rincian-title {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 5px;
        }
        .table-tunggakan {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            page-break-inside: auto;
        }
        .table-tunggakan tr { 
            page-break-inside: avoid; 
            page-break-after: auto; 
        }
        .table-tunggakan th, .table-tunggakan td {
            border: 1px solid #cce3d1; /* Light green border as seen in the image */
            padding: 8px;
        }
        .table-tunggakan th {
            background-color: #128e29;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            border-color: #128e29;
        }
        .table-tunggakan .footer-row th {
            background-color: #002b70;
            color: #ffffff;
            font-weight: bold;
            border-color: #002b70;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        
        .member-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .member-item {
            display: block;
            width: 100%;
            clear: both;
        }
        .member-name {
            float: left;
        }
        .member-amount {
            float: right;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        .instructions {
            margin-top: 25px;
        }
        .instructions-list {
            margin: 5px 0 15px 0;
            padding-left: 20px;
        }
        .instructions-list li {
            margin-bottom: 3px;
        }
        .bullet-list {
            margin: 0 0 5px 0;
            padding-left: 15px;
            list-style-type: none;
        }
        .bullet-list li {
            position: relative;
            margin-bottom: 3px;
        }
        .bullet-list li:before {
            content: "•";
            position: absolute;
            left: -12px;
        }
        .salutation {
            margin-top: 25px;
        }
    </style>
</head>
<body>

    <img src="{{ base_path('excel/logo/BPJS_Kesehatan_logo.svg.webp') }}" class="logo-header">
    <img src="{{ base_path('excel/logo/Picture1.jpg') }}" class="footer-image">

    <div class="content">
        <p>Yth.<br>
        Bapak/Ibu {{ $caseData->peserta->nama }}<br>
        JL. {{ $caseData->peserta->alamat ?? '-' }}</p>

        @if($isLunas)
            <p style="text-align: justify; margin-top: 25px;">
                Kami dari <strong>BPJS Kesehatan Cabang Solok</strong> ingin mengucapkan terima kasih telah menjadi peserta BPJS Kesehatan dan telah mendaftar Program REHAB BPJS Kesehatan dalam upaya melunasi Tunggakan Iuran Mandiri BPJS Kesehatan.
            </p>
        @else
            <p style="text-align: justify; margin-top: 25px;">
                &nbsp;&nbsp;&nbsp;&nbsp;Pertama-tama kami ucapkan terima kasih telah menjadi peserta REHAB BPJS Kesehatan, bersama ini kami sampaikan informasi tagihan iuran BPJS Kesehatan Keluarga a.n {{ $caseData->peserta->nama }}, dengan rincian sebagai berikut:
            </p>
        @endif

        <table class="info-table">
            <tr>
                <td class="label">Nama</td>
                <td class="colon">:</td>
                <td>{{ $caseData->peserta->nama }}</td>
            </tr>
            <tr>
                <td class="label">Nomor Kartu Kepala Keluarga</td>
                <td class="colon">:</td>
                <td>{{ $caseData->noka_pendaftar }}</td>
            </tr>
            <tr>
                <td class="label">Jumlah Bulan Menunggak</td>
                <td class="colon">:</td>
                <td>{{ $jumlahBulanMenunggak }} bulan</td>
            </tr>
            <tr>
                <td class="label">Jumlah Anggota Keluarga</td>
                <td class="colon">:</td>
                <td>{{ $jumlahAnggota }} Anggota keluarga</td>
            </tr>
            <tr>
                <td class="label">Kekurangan Pembayaran</td>
                <td class="colon">:</td>
                <td>Rp {{ $isLunas ? '0' : number_format($totalTunggakan, 0, ',', '.') }}</td>
            </tr>
        </table>

        @if($isLunas)
            <p style="text-align: justify; margin-bottom: 25px;">
                Kami dari <strong>BPJS Kesehatan Cabang Solok</strong> ingin mengingatkan kembali mengenai tagihan iuran <strong>Program REHAB</strong> , terimakasih telah melakukan pembayaran iuran REHAB bulan {{ $processPeriod }}. Kami mengingatkan iuran REHAB kedepannya agar dapat dibayarkan sesuai jangka waktu yang sudah ditentukam sebelumnya.
            </p>
        @else
            <div class="rincian-title">Rincian Tagihan Belum Dibayar (Hingga {{ $processPeriod }})</div>
            <table class="table-tunggakan">
                <thead>
                    <tr>
                        <th width="8%" class="text-left">No.</th>
                        <th width="26%" class="text-left">Periode Bulan</th>
                        <th width="42%" class="text-left">Rincian Anggota Keluarga</th>
                        <th width="24%" class="text-left">Total Cicilan Bulanan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rincianTagihan as $idx => $tagihan)
                        <tr>
                            <td class="text-left"><strong>{{ $idx + 1 }}.</strong></td>
                            <td class="text-left">{{ $tagihan['periode_label'] }}</td>
                            <td class="text-left">
                                <ul class="member-list">
                                    @foreach($tagihan['member_breakdown'] as $nama => $nominal)
                                        <li class="member-item clearfix">
                                            <span class="member-name">{{ $nama }}</span>
                                            <span class="member-amount">Rp {{ number_format($nominal, 0, ',', '.') }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="text-right">Rp {{ number_format($tagihan['monthly_total'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="footer-row">
                        <th colspan="3" class="text-center">TOTAL KEKURANGAN PEMBAYARAN</th>
                        <th class="text-right">Rp {{ number_format($totalTunggakan, 0, ',', '.') }}</th>
                    </tr>
                </tfoot>
            </table>
        @endif

        <div style="page-break-inside: avoid;">
            <div class="instructions">
                <div>Untuk pembayarannya bisa melalui Mobile Banking dgn cara:</div>
                <ol class="instructions-list" style="margin-left: 15px;">
                    <li>Pilih Menu Pembayaran/Tagihan</li>
                    <li>Lalu pilih BPJS Kesehatan</li>
                    <li>Lalu isikan Nomor Pembayaran</li>
                </ol>
                
                <div style="margin-bottom: 5px;">Berikut nomor pembayaran:</div>
                <ul class="bullet-list">
                    <li>Bank Mandiri : Nomor Kartu BPJS Kesehatan, kurangkan 0 dua didepan</li>
                    <li>BNI : 88888(Kurangkan dua 0 di depan Nomor BPJS)</li>
                    <li>BRI : Kurangkan dua 0 di depan Nomor Kartu BPJS Kesehatan</li>
                    <li>Bank Nagari (Ollin): 000+Nomor Kartu+01</li>
                    <li>BCA,Indomaret, Tokopedia, DANA, Alfamart, PT Pos dan lain lain dengan menggunakan VA pembayaran BPJS*88888+kurangkan 0 dua didepan nomor kartu Kesehatan atau *Memakai Nomor Kartu BPJS.</li>
                </ul>

                <ol start="4" class="instructions-list" style="margin-left: 15px; margin-top: 10px;">
                    <li>Pilih bulan 1 Saja</li>
                    <li>Lalu Bayarkan.</li>
                </ol>
            </div>

            <p style="text-align: justify; margin-top: 25px;">
                Abaikan pesan ini jika Anda sudah melakukan pembayaran bulan ini. Terima kasih atas kepatuhan dan kerja sama Anda.
            </p>

            <div class="salutation">
                Salam sehat,<br>
                <strong>BPJS Kesehatan Cabang Solok</strong>
            </div>
        </div>
    </div>
</body>
</html>
