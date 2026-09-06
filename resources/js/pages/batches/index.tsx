import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Upload } from 'lucide-react';

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

interface PaginationData {
    data: Batch[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

export default function BatchesIndex({ batches }: { batches: PaginationData }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: route('dashboard') },
        { title: 'Batch & Import', href: route('batches.index') },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Batch & Import" />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-slate-900">Batch & Import</h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Riwayat data SIPP yang telah diimport ke dalam sistem.
                        </p>
                    </div>
                    <Link
                        href={route('batches.create')}
                        className="flex items-center gap-2 rounded-md bg-[#22577A] px-4 py-2 text-sm font-medium text-white hover:bg-[#1a4360]"
                    >
                        <Upload className="h-4 w-4" />
                        Import Data Baru
                    </Link>
                </div>

                <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-slate-600">
                            <thead className="border-b border-slate-200 bg-slate-50 text-slate-700">
                                <tr>
                                    <th className="px-6 py-3 font-medium">Tanggal Data</th>
                                    <th className="px-6 py-3 font-medium">Nama File</th>
                                    <th className="px-6 py-3 font-medium text-right">Data Valid</th>
                                    <th className="px-6 py-3 font-medium text-right">Baru</th>
                                    <th className="px-6 py-3 font-medium text-right">Diperbarui</th>
                                    <th className="px-6 py-3 font-medium">Diimport Oleh</th>
                                    <th className="px-6 py-3 font-medium">Waktu Import</th>
                                    <th className="px-6 py-3 font-medium text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200">
                                {batches.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={8} className="px-6 py-8 text-center text-slate-500">
                                            Belum ada data yang diimport.
                                        </td>
                                    </tr>
                                ) : (
                                    batches.data.map((batch) => (
                                        <tr key={batch.id} className="hover:bg-slate-50">
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {batch.tanggal_data}
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="font-medium text-slate-900">{batch.nama_file}</div>
                                                {batch.status_proses !== 'selesai' && (
                                                    <span className="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800">
                                                        {batch.status_proses}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-right tabular-nums">
                                                {batch.jumlah_data}
                                            </td>
                                            <td className="px-6 py-4 text-right text-[#57CC99] font-medium tabular-nums">
                                                {batch.jumlah_peserta_baru > 0 ? `+${batch.jumlah_peserta_baru}` : '-'}
                                            </td>
                                            <td className="px-6 py-4 text-right text-[#38A3A5] font-medium tabular-nums">
                                                {batch.jumlah_peserta_diperbarui > 0 ? `+${batch.jumlah_peserta_diperbarui}` : '-'}
                                            </td>
                                            <td className="px-6 py-4">
                                                {batch.importer?.name || 'Sistem'}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {new Date(batch.created_at).toLocaleString('id-ID')}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <Link
                                                    href={route('batches.show', batch.id)}
                                                    className="text-[#22577A] hover:underline"
                                                >
                                                    Detail
                                                </Link>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                    {/* Pagination */}
                    {batches.last_page > 1 && (
                        <div className="flex items-center justify-between border-t border-slate-200 bg-white px-6 py-3">
                            <div className="text-sm text-slate-500">
                                Menampilkan {batches.data.length} dari {batches.total} hasil
                            </div>
                            <div className="flex gap-1">
                                {batches.links.map((link, i) => (
                                    link.url ? (
                                        <Link
                                            key={i}
                                            href={link.url}
                                            className={`rounded px-3 py-1 text-sm ${
                                                link.active
                                                    ? 'bg-[#22577A] text-white'
                                                    : 'text-slate-600 hover:bg-slate-100'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ) : (
                                        <span
                                            key={i}
                                            className="rounded px-3 py-1 text-sm text-slate-400"
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    )
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
