<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tagihan REHAB - {{ $caseData->noka_pendaftar }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #000;
        }
        .header h2 {
            margin: 5px 0 0 0;
            font-size: 14px;
            font-weight: normal;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 3px;
            vertical-align: top;
        }
        .info-table .label {
            width: 150px;
            font-weight: bold;
        }
        .info-table .colon {
            width: 10px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th, .table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        .table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }
        .text-right {
            text-align: right !important;
        }
        .text-center {
            text-align: center !important;
        }
        .footer {
            margin-top: 30px;
            font-size: 11px;
        }
        .message-box {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>SURAT PEMBERITAHUAN TUNGGAKAN PROGRAM REHAB</h1>
        <h2>BPJS Kesehatan</h2>
    </div>

    <div style="margin-bottom: 20px;">
        Yth. Bapak/Ibu <strong>{{ $caseData->peserta->nama }}</strong><br>
        di Tempat
    </div>

    <div class="info-section">
        <table class="info-table">
            <tr>
                <td class="label">Nama Kepala Keluarga</td>
                <td class="colon">:</td>
                <td>{{ $caseData->peserta->nama }}</td>
                <td class="label">Status REHAB</td>
                <td class="colon">:</td>
                <td>{{ $caseData->status_rehab }}</td>
            </tr>
            <tr>
                <td class="label">No. Kartu (NOKA)</td>
                <td class="colon">:</td>
                <td>{{ $caseData->noka_pendaftar }}</td>
                <td class="label">Tanggal Pendaftaran</td>
                <td class="colon">:</td>
                <td>{{ \Carbon\Carbon::parse($caseData->tanggal_pendaftaran)->translatedFormat('d F Y') }}</td>
            </tr>
            <tr>
                <td class="label">Alamat</td>
                <td class="colon">:</td>
                <td colspan="4">{{ $caseData->peserta->alamat ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="message-box">
        <p>{{ $statusMessage }}</p>
        <p><strong>{{ $reminderMessage }}</strong></p>
    </div>

    @if(!$isLunas)
        <h3 style="margin-bottom: 10px;">Rincian Tagihan Belum Dibayar (Hingga {{ $processPeriod }})</h3>
        
        <table class="table">
            <thead>
                <tr>
                    <th width="5%">No.</th>
                    <th width="25%">Periode Bulan</th>
                    <th width="45%">Rincian Anggota Keluarga</th>
                    <th width="25%">Total Cicilan Bulan Ini</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rincianTagihan as $idx => $tagihan)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>{{ $tagihan['periode_label'] }}</td>
                        <td>
                            <ul style="margin: 0; padding-left: 15px;">
                                @foreach($tagihan['member_breakdown'] as $nama => $nominal)
                                    <li>{{ $nama }} - Rp {{ number_format($nominal, 0, ',', '.') }}</li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="text-right">Rp {{ number_format($tagihan['monthly_total'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-right">TOTAL KEKURANGAN PEMBAYARAN</th>
                    <th class="text-right">Rp {{ number_format($totalTunggakan, 0, ',', '.') }}</th>
                </tr>
            </tfoot>
        </table>
    @endif

    <div class="footer">
        <p>
            Demikian surat pemberitahuan ini kami sampaikan. Atas perhatian dan kerjasamanya kami ucapkan terima kasih.
        </p>
        <p>
            Salam Sehat,<br>
            <strong>BPJS Kesehatan</strong>
        </p>
    </div>

</body>
</html>
