import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';
import pesertaRoute from '@/routes/peserta';
import { useState } from 'react';
import { RehabRegistrationModal } from './partials/RehabRegistrationModal';
import { Button } from '@/components/ui/button';
import {
    User,
    MapPin,
    Phone,
    Mail,
    CreditCard,
    FileSpreadsheet,
    Activity,
    Calendar,
    ArrowLeft,
} from 'lucide-react';

interface PesertaDetail {
    id: number;
    noka: string;
    nama: string;
    no_hp: string;
    email: string;
    alamat: string;
    status_aktif: string;
    updated_at: string;
    daerah?: {
        nama: string;
    };
    rehab_case_members?: {
        id: number;
        tagihan_awal: string;
        sisa_tunggakan: string;
        jml_bulan_menunggak_awal: number;
        cicilan_bulanan: string;
        case?: {
            status_rehab: string;
            tanggal_pendaftaran: string;
            tanggal_akhir_cicilan: string;
        };
        installments?: {
            id: number;
            nomor_cicilan: number;
            periode_bulan: string;
            besaran_cicilan: string;
            tanggal_bayar: string | null;
        }[];
    }[];
    batches?: {
        id: number;
        batch?: {
            id: number;
            nama_file: string;
            tanggal_data: string;
            status_proses: string;
        };
    }[];
    sipp_verifications?: {
        id: number;
        tanggal_cek: string;
        terdaftar_rehab: boolean;
        catatan: string | null;
    }[];
}

export default function PesertaShow({ peserta, candidates = [], latestBatch }: { peserta: PesertaDetail, candidates?: any[], latestBatch?: any }) {
    const [isRehabModalOpen, setIsRehabModalOpen] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard.url() },
        { title: 'Master Peserta', href: pesertaRoute.index.url() },
        {
            title: `Detail: ${peserta.nama}`,
            href: pesertaRoute.show.url(peserta.id),
        },
    ];

    const formatCurrency = (value: any) => {
        if (!value) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(value);
    };

    const formatDate = (dateString: string | null) => {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    };

    const activeCases = peserta.rehab_case_members?.filter(m => m.case?.status_rehab === 'AKTIF') || [];
    const historicalCases = peserta.rehab_case_members?.filter(m => m.case?.status_rehab !== 'AKTIF') || [];
    const activeCase = activeCases[0];

    const calculateTotalCicilanSampaiBulanIni = (installments: any[] = []) => {
        const currentDate = new Date();
        const currentYearMonth = currentDate.toISOString().substring(0, 7); // e.g., "2026-09"
        
        return installments
            .filter((inst) => inst.periode_bulan && inst.periode_bulan.substring(0, 7) <= currentYearMonth)
            .reduce((sum, inst) => sum + parseFloat(inst.besaran_cicilan || '0'), 0);
    };

    const renderCaseDetail = (member: any, isHistorical = false) => {
        // Check if there are different installment amounts (Custom Schedule)
        const isCustomSchedule = member.installments && new Set(member.installments.map((i: any) => parseFloat(i.besaran_cicilan))).size > 2;

        return (
            <div>
                {/* Header Status REHAB */}
                <div className={`mb-6 flex flex-wrap items-center justify-between gap-4 rounded-lg border border-slate-200 ${isHistorical ? 'bg-slate-100' : 'bg-slate-50'} p-4`}>
                    <div>
                        <div className="mb-1 text-xs font-medium text-slate-500">
                            Status Program
                        </div>
                        <div className="flex items-center gap-2">
                            <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${isHistorical ? 'bg-slate-200 text-slate-800' : 'bg-green-100 text-green-800'}`}>
                                {member.case?.status_rehab || 'Aktif'}
                            </span>
                        </div>
                    </div>
                    <div className="text-right">
                        <div className="mb-1 text-xs font-medium text-slate-500">
                            Periode Program
                        </div>
                        <div className="flex items-center justify-end gap-1 text-sm font-medium text-slate-900">
                            <Calendar className="h-3 w-3 text-slate-400" />
                            {formatDate(member.case?.tanggal_pendaftaran || null)} - {formatDate(member.case?.tanggal_akhir_cicilan || null)}
                        </div>
                    </div>
                </div>

                {/* Financial Summary */}
                <div className="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div className="rounded-lg border border-slate-200 bg-white p-4">
                        <div className="mb-1 text-xs font-medium text-slate-500">Lama Cicilan</div>
                        <div className="text-lg font-semibold text-slate-900">
                            {member.installments?.length || 0} <span className="text-sm font-normal text-slate-500">Bulan</span>
                        </div>
                    </div>
                    <div className="rounded-lg border border-slate-200 bg-white p-4">
                        <div className="mb-1 text-xs font-medium text-slate-500">Tagihan Awal</div>
                        <div className="text-lg font-semibold text-slate-900">
                            {formatCurrency(member.tagihan_awal)}
                        </div>
                    </div>
                    <div className="rounded-lg border border-slate-200 bg-white p-4">
                        <div className="mb-1 text-xs font-medium text-slate-500">
                            Total Cicilan hingga bulan ini
                        </div>
                        <div className="text-lg font-semibold text-[#22577A]">
                            {formatCurrency(calculateTotalCicilanSampaiBulanIni(member.installments))}
                        </div>
                    </div>
                    <div className="rounded-lg border border-red-100 bg-red-50 p-4">
                        <div className="mb-1 text-xs font-medium text-red-600">Sisa Tunggakan</div>
                        <div className="text-lg font-bold text-red-700">
                            {formatCurrency(member.sisa_tunggakan)}
                        </div>
                    </div>
                </div>

                {/* Riwayat Cicilan */}
                <h3 className="mb-3 border-b border-slate-200 pb-2 text-sm font-semibold text-slate-800">
                    Jadwal & Riwayat Pembayaran
                </h3>
                {member.installments && member.installments.length > 0 ? (
                    <div className="overflow-hidden rounded-lg border border-slate-200">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-slate-50 text-slate-600">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Bulan/Tahun</th>
                                    <th className="px-4 py-3 text-right font-medium">Tagihan</th>
                                    <th className="px-4 py-3 font-medium">Tanggal Bayar</th>
                                    <th className="px-4 py-3 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200 bg-white">
                                {member.installments.map((inst: any) => (
                                    <tr key={inst.id}>
                                        <td className="px-4 py-3 font-medium text-slate-900">
                                            Cicilan ke-{inst.nomor_cicilan}
                                            <div className="text-xs text-slate-500 font-normal">
                                                {new Date(inst.periode_bulan).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-right text-slate-900">
                                            {formatCurrency(inst.besaran_cicilan)}
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap text-slate-600">
                                            {formatDate(inst.tanggal_bayar)}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${inst.tanggal_bayar !== null ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'}`}>
                                                {inst.tanggal_bayar !== null ? 'Lunas' : 'Belum Lunas'}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <div className="rounded-lg border border-dashed border-slate-200 p-8 text-center">
                        <p className="text-sm text-slate-500">Tidak ada jadwal cicilan yang ditemukan.</p>
                    </div>
                )}
            </div>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Detail Peserta - ${peserta.nama}`} />

            <div className="mx-auto mt-4 flex max-w-5xl flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <Link
                            href={pesertaRoute.index.url()}
                            className="mb-2 inline-flex items-center gap-1 text-sm font-medium text-[#22577A] hover:underline"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Kembali ke Master Peserta
                        </Link>
                        <h1 className="text-2xl font-bold text-slate-900">
                            Detail Peserta
                        </h1>
                    </div>
                    {!activeCase && (
                        <Button onClick={() => setIsRehabModalOpen(true)}>
                            Verifikasi & Daftarkan REHAB
                        </Button>
                    )}
                </div>

                <RehabRegistrationModal
                    isOpen={isRehabModalOpen}
                    onClose={() => setIsRehabModalOpen(false)}
                    peserta={peserta}
                    candidates={candidates}
                    latestBatch={latestBatch}
                />

                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                    {/* Kolom Kiri: Profil Singkat */}
                    <div className="space-y-6 md:col-span-1">
                        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div className="flex items-center gap-2 border-b border-slate-200 bg-slate-50 px-6 py-4">
                                <User className="h-5 w-5 text-slate-500" />
                                <h2 className="font-medium text-slate-800">
                                    Identitas Diri
                                </h2>
                            </div>
                            <div className="flex flex-col gap-4 p-6">
                                <div>
                                    <div className="mb-1 text-xs font-medium tracking-wider text-slate-500 uppercase">
                                        NOKA
                                    </div>
                                    <div className="font-semibold text-slate-900">
                                        {peserta.noka}
                                    </div>
                                </div>
                                <div>
                                    <div className="mb-1 text-xs font-medium tracking-wider text-slate-500 uppercase">
                                        Nama Lengkap
                                    </div>
                                    <div className="font-semibold text-slate-900">
                                        {peserta.nama}
                                    </div>
                                </div>

                                <div className="mt-2 border-t border-slate-100 pt-4"></div>

                                <div className="flex items-start gap-3">
                                    <Phone className="mt-0.5 h-4 w-4 text-slate-400" />
                                    <div>
                                        <div className="text-sm font-medium text-slate-900">
                                            {peserta.no_hp || '-'}
                                        </div>
                                        <div className="text-xs text-slate-500">
                                            Nomor HP
                                        </div>
                                    </div>
                                </div>

                                <div className="flex items-start gap-3">
                                    <Mail className="mt-0.5 h-4 w-4 text-slate-400" />
                                    <div>
                                        <div className="text-sm font-medium text-slate-900">
                                            {peserta.email || '-'}
                                        </div>
                                        <div className="text-xs text-slate-500">
                                            Email
                                        </div>
                                    </div>
                                </div>

                                <div className="flex items-start gap-3">
                                    <MapPin className="mt-0.5 h-4 w-4 text-slate-400" />
                                    <div>
                                        <div className="text-sm font-medium text-slate-900">
                                            {peserta.alamat || '-'}
                                        </div>
                                        <div className="text-xs text-slate-500">
                                            Alamat{' '}
                                            {peserta.daerah
                                                ? `(${peserta.daerah.nama})`
                                                : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Histori Batch */}
                        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div className="flex items-center gap-2 border-b border-slate-200 bg-slate-50 px-6 py-4">
                                <FileSpreadsheet className="h-5 w-5 text-slate-500" />
                                <h2 className="font-medium text-slate-800">
                                    Histori Batch Import
                                </h2>
                            </div>
                            <div className="p-0">
                                {peserta.batches &&
                                peserta.batches.length > 0 ? (
                                    <div className="divide-y divide-slate-100">
                                        {peserta.batches.map((pb, idx) => (
                                            <div
                                                key={idx}
                                                className="flex flex-col gap-1 p-4"
                                            >
                                                <div className="flex items-start justify-between">
                                                    <div
                                                        className="truncate pr-4 text-sm font-medium text-slate-900"
                                                        title={
                                                            pb.batch?.nama_file
                                                        }
                                                    >
                                                        {pb.batch?.nama_file}
                                                    </div>
                                                    <span
                                                        className={`inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-xs font-medium ${
                                                            pb.batch
                                                                ?.status_proses ===
                                                            'selesai'
                                                                ? 'bg-green-100 text-green-800'
                                                                : 'bg-yellow-100 text-yellow-800'
                                                        }`}
                                                    >
                                                        {
                                                            pb.batch
                                                                ?.status_proses
                                                        }
                                                    </span>
                                                </div>
                                                <div className="text-xs text-slate-500">
                                                    Data Per:{' '}
                                                    {formatDate(
                                                        pb.batch
                                                            ?.tanggal_data ||
                                                            null,
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="p-6 text-center text-sm text-slate-500">
                                        Belum ada riwayat batch.
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Kolom Kanan: Data REHAB */}
                    <div className="space-y-6 md:col-span-2">
                        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div className="flex items-center gap-2 border-b border-slate-200 bg-slate-50 px-6 py-4">
                                <Activity className="h-5 w-5 text-[#38A3A5]" />
                                <h2 className="font-medium text-slate-800">
                                    Data Monitoring REHAB
                                </h2>
                            </div>

                            <div className="p-6">
                                {activeCases.length === 0 && historicalCases.length === 0 ? (
                                    <div className="flex flex-col items-center justify-center py-12 text-center">
                                        <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
                                            <CreditCard className="h-8 w-8 text-slate-300" />
                                        </div>
                                        <h3 className="mb-1 text-lg font-medium text-slate-900">
                                            Belum Terdaftar Program REHAB
                                        </h3>
                                        
                                        {peserta.sipp_verifications && peserta.sipp_verifications.length > 0 ? (
                                            <div className="mt-6 rounded-lg border border-slate-200 bg-white p-4 text-left shadow-sm max-w-md w-full">
                                                <h4 className="font-semibold text-slate-800 border-b pb-2 mb-3">Status Verifikasi SIPP Terakhir</h4>
                                                <div className="space-y-2 text-sm">
                                                    <div className="flex justify-between">
                                                        <span className="text-slate-500">Tanggal Cek:</span>
                                                        <span className="font-medium text-slate-900">
                                                            {new Date(peserta.sipp_verifications[0].tanggal_cek).toLocaleDateString('id-ID', {
                                                                day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
                                                            })}
                                                        </span>
                                                    </div>
                                                    <div className="flex justify-between">
                                                        <span className="text-slate-500">Status SIPP:</span>
                                                        <span className="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">
                                                            {peserta.sipp_verifications[0].terdaftar_rehab ? 'Terdaftar' : 'Tidak Terdaftar / Lunas'}
                                                        </span>
                                                    </div>
                                                    {peserta.sipp_verifications[0].catatan && (
                                                        <div className="flex justify-between border-t border-slate-100 pt-2 mt-2">
                                                            <span className="text-slate-500">Catatan:</span>
                                                            <span className="font-medium text-slate-900 text-right">
                                                                {peserta.sipp_verifications[0].catatan}
                                                            </span>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        ) : (
                                            <p className="max-w-sm text-sm text-slate-500 mt-2">
                                                Peserta ini belum memiliki data kepesertaan maupun riwayat cicilan program REHAB, serta belum pernah diverifikasi SIPP.
                                            </p>
                                        )}
                                    </div>
                                ) : (
                                    <div className="space-y-8">
                                        {activeCases.map((member, idx) => (
                                            <div key={member.id} className={idx > 0 ? 'border-t border-slate-200 pt-8' : ''}>
                                                <h3 className="text-lg font-bold text-[#22577A] mb-4">Program REHAB Aktif</h3>
                                                {renderCaseDetail(member)}
                                            </div>
                                        ))}
                                        
                                        {historicalCases.length > 0 && (
                                            <div className="mt-8 pt-8 border-t border-slate-200">
                                                <h3 className="text-lg font-bold text-slate-700 mb-4">Riwayat Historis REHAB</h3>
                                                {historicalCases.map((member, idx) => (
                                                    <div key={member.id} className={idx > 0 ? 'mt-8' : ''}>
                                                        {renderCaseDetail(member, true)}
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
