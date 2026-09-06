import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { useState, useRef } from 'react';
import { UploadCloud, File, AlertCircle, Loader2, ArrowRight } from 'lucide-react';
import axios from 'axios';

interface PreviewStats {
    jumlah_row_asli: number;
    jumlah_row_valid: number;
    jumlah_duplicate: number;
    jumlah_conflict: number;
    jumlah_invalid: number;
    jumlah_peserta_baru: number;
    jumlah_peserta_diperbarui: number;
}

export default function BatchImport() {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: route('dashboard') },
        { title: 'Batch & Import', href: route('batches.index') },
        { title: 'Import Baru', href: route('batches.create') },
    ];

    const [file, setFile] = useState<File | null>(null);
    const [tanggalData, setTanggalData] = useState<string>(new Date().toISOString().split('T')[0]);
    const fileInputRef = useRef<HTMLInputElement>(null);
    
    // Status: 'idle' | 'previewing' | 'preview_ready' | 'importing' | 'error'
    const [status, setStatus] = useState<'idle' | 'previewing' | 'preview_ready' | 'importing' | 'error'>('idle');
    const [previewStats, setPreviewStats] = useState<PreviewStats | null>(null);
    const [previewData, setPreviewData] = useState<{file_path: string, original_name: string} | null>(null);
    const [errorMessage, setErrorMessage] = useState<string>('');

    const { post, processing: formProcessing } = useForm({});

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const selected = e.target.files?.[0];
        if (selected) {
            setFile(selected);
            setStatus('idle');
            setErrorMessage('');
        }
    };

    const handlePreview = async () => {
        if (!file || !tanggalData) return;

        setStatus('previewing');
        setErrorMessage('');

        const formData = new FormData();
        formData.append('file', file);
        formData.append('tanggal_data', tanggalData);

        try {
            const response = await axios.post(route('batches.preview'), formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });

            setPreviewStats(response.data.stats);
            setPreviewData({
                file_path: response.data.file_path,
                original_name: response.data.original_name
            });
            setStatus('preview_ready');
        } catch (error: any) {
            setStatus('error');
            if (error.response?.data?.error) {
                setErrorMessage(error.response.data.error);
            } else if (error.response?.data?.message) {
                setErrorMessage(error.response.data.message); // Validation errors
            } else {
                setErrorMessage('Terjadi kesalahan saat memproses file.');
            }
        }
    };

    const handleImport = () => {
        if (!previewData || status !== 'preview_ready') return;

        setStatus('importing');
        
        post(route('batches.import'), {
            data: {
                file_path: previewData.file_path,
                original_name: previewData.original_name,
                tanggal_data: tanggalData
            },
            onSuccess: () => {
                // Inertia will redirect to the show page
            },
            onError: (errors) => {
                setStatus('error');
                setErrorMessage(errors.error || 'Terjadi kesalahan saat import data.');
            }
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Import Data SIPP" />

            <div className="flex flex-col gap-6 p-6 max-w-3xl mx-auto mt-4">
                <div>
                    <h1 className="text-2xl font-semibold text-slate-900">Import Data SIPP</h1>
                    <p className="mt-1 text-sm text-slate-500">
                        Upload file Excel laporan SIPP untuk memperbarui data Peserta dan riwayat tunggakan.
                    </p>
                </div>

                <div className="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div className="p-6">
                        <div className="grid gap-6">
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">
                                    Tanggal Data (Cut-off) <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="date"
                                    className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-[#22577A] focus:outline-none focus:ring-1 focus:ring-[#22577A]"
                                    value={tanggalData}
                                    onChange={(e) => setTanggalData(e.target.value)}
                                    disabled={status === 'previewing' || status === 'preview_ready' || status === 'importing'}
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">
                                    File Excel SIPP (.xlsx, .xls) <span className="text-red-500">*</span>
                                </label>
                                
                                {status === 'idle' || status === 'error' ? (
                                    <div 
                                        className={`mt-1 flex justify-center rounded-lg border-2 border-dashed px-6 py-10 ${
                                            status === 'error' ? 'border-red-300 bg-red-50' : 'border-slate-300 hover:border-[#38A3A5] hover:bg-slate-50'
                                        } cursor-pointer transition-colors`}
                                        onClick={() => fileInputRef.current?.click()}
                                    >
                                        <div className="text-center">
                                            <UploadCloud className={`mx-auto h-12 w-12 ${status === 'error' ? 'text-red-400' : 'text-slate-400'}`} />
                                            <div className="mt-4 flex text-sm text-slate-600 justify-center">
                                                <span className="font-semibold text-[#22577A]">Pilih file</span>
                                                <span className="pl-1">atau drag and drop</span>
                                            </div>
                                            <p className="text-xs text-slate-500 mt-1">XLSX, XLS hingga 50MB</p>
                                        </div>
                                        <input
                                            type="file"
                                            className="hidden"
                                            ref={fileInputRef}
                                            accept=".xlsx, .xls, .csv"
                                            onChange={handleFileChange}
                                        />
                                    </div>
                                ) : (
                                    <div className="mt-1 flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 p-4">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-10 w-10 items-center justify-center rounded bg-blue-100 text-[#22577A]">
                                                <File className="h-5 w-5" />
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-slate-900">{file?.name}</p>
                                                <p className="text-xs text-slate-500">{(file?.size ? file.size / (1024 * 1024) : 0).toFixed(2)} MB</p>
                                            </div>
                                        </div>
                                        {status === 'preview_ready' && (
                                            <button 
                                                onClick={() => { setStatus('idle'); setFile(null); setPreviewStats(null); }}
                                                className="text-sm text-red-600 hover:underline font-medium"
                                                disabled={status === 'importing'}
                                            >
                                                Ganti File
                                            </button>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>

                        {status === 'error' && (
                            <div className="mt-4 flex items-start gap-3 rounded-md bg-red-50 p-4 border border-red-200">
                                <AlertCircle className="h-5 w-5 text-red-600 shrink-0 mt-0.5" />
                                <div className="text-sm text-red-800">
                                    <p className="font-semibold">Gagal memproses file</p>
                                    <p className="mt-1">{errorMessage}</p>
                                </div>
                            </div>
                        )}

                        {status === 'previewing' && (
                            <div className="mt-6 flex flex-col items-center justify-center py-8">
                                <Loader2 className="h-8 w-8 text-[#38A3A5] animate-spin" />
                                <p className="mt-4 text-sm font-medium text-slate-600">Menganalisis file Excel...</p>
                                <p className="text-xs text-slate-500 mt-1">Mohon tunggu, proses ini dapat memakan waktu untuk file besar.</p>
                            </div>
                        )}

                        {status === 'preview_ready' && previewStats && (
                            <div className="mt-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                                <h3 className="text-sm font-bold uppercase tracking-wider text-slate-500 mb-4">Hasil Preview (Belum Disimpan)</h3>
                                
                                <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                    <div className="rounded-lg border border-slate-200 p-4 text-center">
                                        <div className="text-2xl font-bold text-slate-900">{previewStats.jumlah_row_asli}</div>
                                        <div className="text-xs text-slate-500 font-medium mt-1">Total Baris Asli</div>
                                    </div>
                                    <div className="rounded-lg border border-green-200 bg-green-50 p-4 text-center">
                                        <div className="text-2xl font-bold text-green-700">{previewStats.jumlah_row_valid}</div>
                                        <div className="text-xs text-green-700 font-medium mt-1">Baris Valid</div>
                                    </div>
                                    <div className="col-span-2 rounded-lg border border-slate-200 p-4">
                                        <div className="flex justify-between items-center h-full">
                                            <div>
                                                <div className="text-xs text-slate-500 font-medium">Peserta Baru</div>
                                                <div className="text-xl font-bold text-[#57CC99]">+{previewStats.jumlah_peserta_baru}</div>
                                            </div>
                                            <div className="h-8 w-px bg-slate-200"></div>
                                            <div>
                                                <div className="text-xs text-slate-500 font-medium">Diperbarui</div>
                                                <div className="text-xl font-bold text-[#38A3A5]">+{previewStats.jumlah_peserta_diperbarui}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                { (previewStats.jumlah_duplicate > 0 || previewStats.jumlah_conflict > 0 || previewStats.jumlah_invalid > 0) && (
                                    <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4">
                                        <h4 className="text-sm font-semibold text-amber-800 mb-2">Peringatan: {previewStats.jumlah_duplicate + previewStats.jumlah_conflict + previewStats.jumlah_invalid} Baris akan diabaikan (Skip)</h4>
                                        <ul className="text-sm text-amber-700 list-disc pl-5 space-y-1">
                                            {previewStats.jumlah_duplicate > 0 && <li><strong>{previewStats.jumlah_duplicate}</strong> baris duplikat identik di dalam file.</li>}
                                            {previewStats.jumlah_conflict > 0 && <li><strong>{previewStats.jumlah_conflict}</strong> baris memiliki konflik data (NOKA sama, data penting berbeda).</li>}
                                            {previewStats.jumlah_invalid > 0 && <li><strong>{previewStats.jumlah_invalid}</strong> baris tidak valid (kekurangan data NOKA).</li>}
                                        </ul>
                                        <p className="mt-2 text-xs text-amber-600">Proses import tetap akan dilanjutkan untuk baris yang valid.</p>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                    
                    <div className="bg-slate-50 border-t border-slate-200 p-4 px-6 flex justify-end gap-3">
                        {status === 'idle' || status === 'error' ? (
                            <button
                                onClick={handlePreview}
                                disabled={!file || !tanggalData}
                                className="rounded-md bg-[#22577A] px-4 py-2 text-sm font-medium text-white hover:bg-[#1a4360] focus:outline-none focus:ring-2 focus:ring-[#22577A] focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Analisis File
                            </button>
                        ) : (status === 'preview_ready' || status === 'importing') ? (
                            <button
                                onClick={handleImport}
                                disabled={status === 'importing'}
                                className="flex items-center gap-2 rounded-md bg-[#57CC99] px-6 py-2 text-sm font-medium text-white hover:bg-[#48b586] focus:outline-none focus:ring-2 focus:ring-[#57CC99] focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {status === 'importing' ? (
                                    <>
                                        <Loader2 className="h-4 w-4 animate-spin" />
                                        Menyimpan Data...
                                    </>
                                ) : (
                                    <>
                                        Commit Import
                                        <ArrowRight className="h-4 w-4" />
                                    </>
                                )}
                            </button>
                        ) : null}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
