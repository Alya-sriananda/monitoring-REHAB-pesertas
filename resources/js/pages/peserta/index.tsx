import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { useState, useEffect } from 'react';
import { dashboard } from '@/routes';
import pesertaRoute from '@/routes/peserta';
import { Search, Eye } from 'lucide-react';
// import { debounce } from 'lodash'; // We can write a simple debounce or use lodash if available

// Simple debounce function
function useDebounce<T>(value: T, delay: number): T {
    const [debouncedValue, setDebouncedValue] = useState<T>(value);
    useEffect(() => {
        const handler = setTimeout(() => {
            setDebouncedValue(value);
        }, delay);
        return () => {
            clearTimeout(handler);
        };
    }, [value, delay]);
    return debouncedValue;
}

interface Peserta {
    id: number;
    noka: string;
    nama: string;
    no_hp: string;
    status_aktif: string;
    updated_at: string;
    status_proses?: string;
    daerah?: {
        nama: string;
    };
    rehab_case_members?: {
        sisa_tunggakan: string;
    }[];
    batches?: {
        batch?: {
            nama_file: string;
            tanggal_data: string;
        };
    }[];
}

interface PaginationData {
    data: Peserta[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

export default function PesertaIndex({
    pesertas,
    filters,
    daerahs,
    batches,
}: {
    pesertas: PaginationData;
    filters: any;
    daerahs: any[];
    batches: any[];
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard.url() },
        { title: 'Master Peserta', href: pesertaRoute.index.url() },
    ];

    const [search, setSearch] = useState(filters.search || '');
    const debouncedSearch = useDebounce(search, 500);

    const [daerahId, setDaerahId] = useState(filters.daerah_id || '');
    const [batchId, setBatchId] = useState(filters.batch_id || '');
    const [statusRehab, setStatusRehab] = useState(filters.status_rehab || '');
    const [statusProses, setStatusProses] = useState(filters.status_proses || '');

    useEffect(() => {
        // Skip first render if values are same as initial filters
        if (
            debouncedSearch === (filters.search || '') &&
            daerahId === (filters.daerah_id || '') &&
            batchId === (filters.batch_id || '') &&
            statusRehab === (filters.status_rehab || '') &&
            statusProses === (filters.status_proses || '')
        ) {
            return;
        }

        router.get(
            pesertaRoute.index.url(),
            {
                search: debouncedSearch,
                daerah_id: daerahId,
                batch_id: batchId,
                status_rehab: statusRehab,
                status_proses: statusProses,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, [debouncedSearch, daerahId, batchId, statusRehab, statusProses]);

    const formatCurrency = (value: any) => {
        if (!value) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(value);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Master Peserta" />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-slate-900">
                            Master Peserta
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Daftar seluruh peserta yang terdata dalam sistem
                            monitoring REHAB.
                        </p>
                    </div>
                </div>

                <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-200 bg-slate-50 p-4">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div className="relative flex-1">
                                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <Search className="h-4 w-4 text-slate-400" />
                                </div>
                                <input
                                    type="text"
                                    className="block w-full rounded-md border-slate-300 pl-10 text-sm focus:border-[#22577A] focus:ring-[#22577A]"
                                    placeholder="Cari NOKA, Nama, atau No HP..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                />
                            </div>

                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                                <select
                                    className="rounded-md border-slate-300 text-sm focus:border-[#22577A] focus:ring-[#22577A]"
                                    value={statusRehab}
                                    onChange={(e) =>
                                        setStatusRehab(e.target.value)
                                    }
                                >
                                    <option value="">Semua Status REHAB</option>
                                    <option value="ada">Ada REHAB</option>
                                    <option value="tidak">Tidak Ada</option>
                                </select>

                                <select
                                    className="rounded-md border-slate-300 text-sm focus:border-[#22577A] focus:ring-[#22577A]"
                                    value={daerahId}
                                    onChange={(e) =>
                                        setDaerahId(e.target.value)
                                    }
                                >
                                    <option value="">Semua Daerah</option>
                                    {daerahs.map((d: any) => (
                                        <option key={d.id} value={d.id}>
                                            {d.nama}
                                        </option>
                                    ))}
                                </select>

                                <select
                                    className="rounded-md border-slate-300 text-sm focus:border-[#22577A] focus:ring-[#22577A]"
                                    value={statusProses}
                                    onChange={(e) =>
                                        setStatusProses(e.target.value)
                                    }
                                >
                                    <option value="">Semua Status Proses</option>
                                    <option value="BELUM DIVERIFIKASI">BELUM DIVERIFIKASI</option>
                                    <option value="TERVERIFIKASI / REHAB">TERVERIFIKASI / REHAB</option>
                                    <option value="TERVERIFIKASI / NON-REHAB">TERVERIFIKASI / NON-REHAB</option>
                                    <option value="PERLU FOLLOW-UP">PERLU FOLLOW-UP</option>
                                    <option value="SUDAH DIHUBUNGI">SUDAH DIHUBUNGI</option>
                                </select>

                                <select
                                    className="max-w-[200px] truncate rounded-md border-slate-300 text-sm focus:border-[#22577A] focus:ring-[#22577A]"
                                    value={batchId}
                                    onChange={(e) => setBatchId(e.target.value)}
                                >
                                    {batches.length === 0 && <option value="">Belum ada batch</option>}
                                    {batches.map((b: any) => (
                                        <option key={b.id} value={b.id}>
                                            {b.tanggal_data} - {b.nama_file}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-slate-600">
                            <thead className="border-b border-slate-200 bg-slate-50 text-slate-700">
                                <tr>
                                    <th className="px-6 py-3 font-medium">
                                        NOKA
                                    </th>
                                    <th className="px-6 py-3 font-medium">
                                        Nama
                                    </th>
                                    <th className="px-6 py-3 font-medium">
                                        No HP
                                    </th>
                                    <th className="px-6 py-3 font-medium">
                                        Daerah
                                    </th>
                                    <th className="px-6 py-3 font-medium">
                                        Status Proses
                                    </th>
                                    <th className="px-6 py-3 font-medium">
                                        Status REHAB
                                    </th>
                                    <th className="px-6 py-3 font-medium">
                                        Sisa Tunggakan
                                    </th>
                                    <th className="px-6 py-3 font-medium">
                                        Terakhir Diperbarui
                                    </th>
                                    <th className="px-6 py-3 text-right font-medium">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200">
                                {pesertas.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={8}
                                            className="px-6 py-8 text-center text-slate-500"
                                        >
                                            Tidak ada data peserta ditemukan.
                                        </td>
                                    </tr>
                                ) : (
                                    pesertas.data.map((peserta) => {
                                        const hasRehab =
                                            peserta.rehab_case_members &&
                                            peserta.rehab_case_members.length >
                                                0;

                                        let totalSisa = 0;
                                        if (hasRehab) {
                                            totalSisa =
                                                peserta.rehab_case_members!.reduce(
                                                    (sum, member) =>
                                                        sum +
                                                        parseFloat(
                                                            member.sisa_tunggakan ||
                                                                '0',
                                                        ),
                                                    0,
                                                );
                                        }

                                        return (
                                            <tr
                                                key={peserta.id}
                                                className="hover:bg-slate-50"
                                            >
                                                <td className="px-6 py-4 font-medium whitespace-nowrap text-slate-900">
                                                    {peserta.noka}
                                                </td>
                                                <td className="px-6 py-4">
                                                    {peserta.nama}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {peserta.no_hp || '-'}
                                                </td>
                                                <td className="px-6 py-4">
                                                    {peserta.daerah?.nama ||
                                                        '-'}
                                                </td>
                                                <td className="px-6 py-4">
                                                    {peserta.status_proses ? (
                                                        <span
                                                            className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                                peserta.status_proses === 'BELUM DIVERIFIKASI'
                                                                    ? 'bg-slate-100 text-slate-800'
                                                                    : peserta.status_proses === 'PERLU FOLLOW-UP'
                                                                    ? 'bg-orange-100 text-orange-800'
                                                                    : peserta.status_proses === 'SUDAH DIHUBUNGI'
                                                                    ? 'bg-blue-100 text-blue-800'
                                                                    : peserta.status_proses?.startsWith('TERVERIFIKASI')
                                                                    ? 'bg-green-100 text-green-800'
                                                                    : 'bg-slate-100 text-slate-800'
                                                            }`}
                                                        >
                                                            {peserta.status_proses}
                                                        </span>
                                                    ) : (
                                                        '-'
                                                    )}
                                                </td>
                                                <td className="px-6 py-4">
                                                    {hasRehab ? (
                                                        <span className="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                                            Aktif REHAB
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-800">
                                                            Tidak Ada
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {hasRehab
                                                        ? formatCurrency(
                                                              totalSisa,
                                                          )
                                                        : '-'}
                                                </td>
                                                <td className="px-6 py-4 text-xs whitespace-nowrap text-slate-500">
                                                    {new Date(
                                                        peserta.updated_at,
                                                    ).toLocaleString('id-ID')}
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    <Link
                                                        href={pesertaRoute.show.url(
                                                            peserta.id,
                                                        )}
                                                        className="inline-flex items-center gap-1 text-[#22577A] hover:underline"
                                                    >
                                                        <Eye className="h-4 w-4" />
                                                        Detail
                                                    </Link>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {pesertas.last_page > 1 && (
                        <div className="flex items-center justify-between border-t border-slate-200 bg-white px-6 py-3">
                            <div className="text-sm text-slate-500">
                                Menampilkan {pesertas.data.length} dari{' '}
                                {pesertas.total} hasil
                            </div>
                            <div className="flex gap-1 overflow-x-auto">
                                {pesertas.links.map((link, i) =>
                                    link.url ? (
                                        <Link
                                            key={i}
                                            href={link.url}
                                            className={`rounded px-3 py-1 text-sm ${
                                                link.active
                                                    ? 'bg-[#22577A] text-white'
                                                    : 'text-slate-600 hover:bg-slate-100'
                                            }`}
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                        />
                                    ) : (
                                        <span
                                            key={i}
                                            className="rounded px-3 py-1 text-sm text-slate-400"
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                        />
                                    ),
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
