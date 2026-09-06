import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { ArrowLeft, FileText, AlertTriangle, Users, CheckCircle, Clock } from 'lucide-react';

interface Batch {
    id: number;
    tanggal_data: string;
    nama_file: string;
    jumlah_data: number;
    jumlah_row_asli: number;
    jumlah_row_valid: number;
    jumlah_duplicate: number;
    jumlah_conflict: number;
    jumlah_invalid: number;
    jumlah_peserta_baru: number;
    jumlah_peserta_diperbarui: number;
    status_proses: string;
    created_at: string;
    importer: {
        id: number;
        name: string;
    };
}

export default function BatchShow({ batch, import_errors }: { batch: Batch; import_errors: any[] | null }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: route('dashboard') },
        { title: 'Batch & Import', href: route('batches.index') },
        { title: 'Detail Batch', href: route('batches.show', batch.id) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Detail Batch - ${batch.nama_file}`} />

            <div className="flex flex-col gap-6 p-6 max-w-5xl">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link
                            href={route('batches.index')}
                            className="inline-flex h-8 w-8 items-center justify-center rounded-full hover:bg-slate-100"
                        >
                            <ArrowLeft className="h-5 w-5 text-slate-500" />
                        </Link>
                        <div>
                            <h1 className="text-2xl font-semibold text-slate-900">Detail Batch</h1>
                            <p className="mt-1 text-sm text-slate-500">
                                Ringkasan import data dari {batch.nama_file}
                            </p>
                        </div>
                    </div>
                    {/* Placeholder link for Peserta index filtered by batch */}
                    <Link
                        href={`/dashboard?batch_id=${batch.id}`}
                        className="flex items-center gap-2 rounded-md bg-white border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 shadow-sm"
                    >
                        <Users className="h-4 w-4" />
                        Lihat Peserta Batch Ini
                    </Link>
                </div>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div className="flex flex-col gap-1 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div className="flex items-center gap-2 text-slate-500 mb-2">
                            <FileText className="h-4 w-4" />
                            <span className="text-sm font-medium">Total Baris Asli</span>
                        </div>
                        <span className="text-3xl font-bold text-slate-900">{batch.jumlah_row_asli}</span>
                    </div>

                    <div className="flex flex-col gap-1 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div className="flex items-center gap-2 text-green-600 mb-2">
                            <CheckCircle className="h-4 w-4" />
                            <span className="text-sm font-medium">Baris Valid (Diimport)</span>
                        </div>
                        <span className="text-3xl font-bold text-slate-900">{batch.jumlah_row_valid}</span>
                        <div className="mt-1 flex gap-4 text-sm">
                            <div className="text-slate-600">
                                <span className="font-semibold text-emerald-600">+{batch.jumlah_peserta_baru}</span> Baru
                            </div>
                            <div className="text-slate-600">
                                <span className="font-semibold text-teal-600">+{batch.jumlah_peserta_diperbarui}</span> Diperbarui
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-col gap-1 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div className="flex items-center gap-2 text-amber-600 mb-2">
                            <AlertTriangle className="h-4 w-4" />
                            <span className="text-sm font-medium">Diabaikan (Skip)</span>
                        </div>
                        <span className="text-3xl font-bold text-slate-900">
                            {batch.jumlah_duplicate + batch.jumlah_conflict + batch.jumlah_invalid}
                        </span>
                        <div className="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                            <div className="text-slate-600">{batch.jumlah_duplicate} Duplicate</div>
                            <div className="text-red-500">{batch.jumlah_conflict} Conflict</div>
                            <div className="text-red-500">{batch.jumlah_invalid} Invalid</div>
                        </div>
                    </div>
                </div>

                <div className="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div className="border-b border-slate-200 bg-slate-50 px-6 py-4">
                        <h2 className="text-base font-semibold text-slate-900">Informasi Batch</h2>
                    </div>
                    <div className="px-6 py-4">
                        <dl className="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            <div>
                                <dt className="text-sm font-medium text-slate-500">Nama File</dt>
                                <dd className="mt-1 text-sm text-slate-900">{batch.nama_file}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-slate-500">Tanggal Data</dt>
                                <dd className="mt-1 text-sm text-slate-900">{batch.tanggal_data}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-slate-500">Diimport Oleh</dt>
                                <dd className="mt-1 text-sm text-slate-900">{batch.importer?.name}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-slate-500">Waktu Import</dt>
                                <dd className="mt-1 text-sm text-slate-900 flex items-center gap-1">
                                    <Clock className="h-3 w-3 text-slate-400" />
                                    {new Date(batch.created_at).toLocaleString('id-ID')}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {import_errors && import_errors.length > 0 && (
                    <div className="rounded-lg border border-red-200 bg-white shadow-sm overflow-hidden">
                        <div className="border-b border-red-200 bg-red-50 px-6 py-4 flex justify-between items-center">
                            <h2 className="text-base font-semibold text-red-900">Laporan Conflict & Invalid</h2>
                            <span className="text-sm text-red-600 font-medium">{import_errors.length} baris dicatat</span>
                        </div>
                        <div className="overflow-x-auto max-h-96">
                            <table className="w-full text-left text-sm text-slate-600">
                                <thead className="sticky top-0 bg-white border-b border-slate-200 shadow-sm">
                                    <tr>
                                        <th className="px-6 py-3 font-medium">Baris Excel</th>
                                        <th className="px-6 py-3 font-medium">NOKA</th>
                                        <th className="px-6 py-3 font-medium">Alasan Skip</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {import_errors.map((err, i) => (
                                        <tr key={i} className="hover:bg-slate-50">
                                            <td className="px-6 py-3 font-mono text-xs">{err.row_index || err.row || '-'}</td>
                                            <td className="px-6 py-3 font-medium">{err.noka || err.data?.noka || '-'}</td>
                                            <td className="px-6 py-3 text-red-600">{err.reason}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
