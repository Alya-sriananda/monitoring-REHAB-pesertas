import { useState, useMemo, useEffect } from 'react';
import { useForm, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { CreditCard, Calendar, CheckCircle2, XCircle, AlertTriangle } from 'lucide-react';
import { toast } from 'sonner';

interface Installment {
    id: number;
    nomor_cicilan: number;
    periode_bulan: string;
    besaran_cicilan: string;
    tanggal_bayar: string | null;
}

interface Member {
    id: number;
    peserta: {
        nama: string;
    };
    installments: Installment[];
}

interface RehabCase {
    id: number;
    status_rehab: string;
    members: Member[];
}

export function PaymentMonitoring({ rehabCase }: { rehabCase: RehabCase }) {
    const [selectedPeriod, setSelectedPeriod] = useState<string>('');

    // Extract unique periods from all installments across all members
    const periods = useMemo(() => {
        if (!rehabCase.members) return [];
        const allPeriods = new Set<string>();
        rehabCase.members.forEach((member) => {
            member.installments?.forEach((inst) => {
                if (inst.periode_bulan) {
                    allPeriods.add(inst.periode_bulan.substring(0, 7)); // YYYY-MM
                }
            });
        });
        const sortedPeriods = Array.from(allPeriods).sort();
        // Auto-select the first unpaid period or current month
        if (!selectedPeriod && sortedPeriods.length > 0) {
            // Find first unpaid period
            let firstUnpaid = '';
            for (const period of sortedPeriods) {
                const isPaid = rehabCase.members.every(m => {
                    const inst = m.installments?.find(i => i.periode_bulan.substring(0, 7) === period);
                    return !inst || inst.tanggal_bayar !== null;
                });
                if (!isPaid) {
                    firstUnpaid = period;
                    break;
                }
            }
            // If all paid or none found, fallback to current month or first
            const currentMonth = new Date().toISOString().substring(0, 7);
            if (firstUnpaid) {
                setTimeout(() => setSelectedPeriod(firstUnpaid), 0);
            } else if (sortedPeriods.includes(currentMonth)) {
                setTimeout(() => setSelectedPeriod(currentMonth), 0);
            } else {
                setTimeout(() => setSelectedPeriod(sortedPeriods[0]), 0);
            }
        }
        return sortedPeriods;
    }, [rehabCase, selectedPeriod]);

    const { data, setData, post, processing, reset, clearErrors } = useForm({
        periode_bulan: '',
        tanggal_bayar: '',
    });

    // Update form when selected period changes
    useEffect(() => {
        if (selectedPeriod) {
            // We need the full YYYY-MM-DD for backend. Assuming normalized to YYYY-MM-01
            setData('periode_bulan', `${selectedPeriod}-01`);
        }
    }, [selectedPeriod, setData]);

    // Compute period data
    const periodData = useMemo(() => {
        if (!selectedPeriod || !rehabCase.members) return null;

        const breakdown: { memberName: string; amount: number; tanggalBayar: string | null }[] = [];
        let totalAmount = 0;
        let paidCount = 0;
        let unpaidCount = 0;
        let commonTanggalBayar: string | null = null;

        rehabCase.members.forEach((member) => {
            const inst = member.installments?.find((i) => i.periode_bulan.substring(0, 7) === selectedPeriod);
            if (inst) {
                const amount = parseFloat(inst.besaran_cicilan);
                totalAmount += amount;
                breakdown.push({
                    memberName: member.peserta?.nama || 'Unknown',
                    amount,
                    tanggalBayar: inst.tanggal_bayar,
                });

                if (inst.tanggal_bayar) {
                    paidCount++;
                    commonTanggalBayar = inst.tanggal_bayar;
                } else {
                    unpaidCount++;
                }
            }
        });

        let status: 'SUDAH BAYAR' | 'BELUM BAYAR' | 'DATA TIDAK KONSISTEN' = 'BELUM BAYAR';
        if (paidCount > 0 && unpaidCount === 0) {
            status = 'SUDAH BAYAR';
        } else if (paidCount > 0 && unpaidCount > 0) {
            status = 'DATA TIDAK KONSISTEN';
        }

        return {
            breakdown,
            totalAmount,
            status,
            commonTanggalBayar,
        };
    }, [selectedPeriod, rehabCase]);

    // Update form's tanggal_bayar based on existing data
    useEffect(() => {
        if (periodData && periodData.status === 'SUDAH BAYAR' && periodData.commonTanggalBayar) {
            setData('tanggal_bayar', periodData.commonTanggalBayar);
        } else {
            setData('tanggal_bayar', '');
        }
    }, [periodData, setData]);

    const formatCurrency = (value: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(value);
    };

    const formatDateMonth = (yyyyMm: string) => {
        const [year, month] = yyyyMm.split('-');
        const date = new Date(parseInt(year), parseInt(month) - 1, 1);
        return date.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
    };

    const handleSavePayment = (e: React.FormEvent) => {
        e.preventDefault();
        
        post(`/rehab-cases/${rehabCase.id}/payments`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Pembayaran berhasil diperbarui');
            },
            onError: () => {
                toast.error('Gagal memperbarui pembayaran');
            }
        });
    };

    const handleClearPayment = () => {
        if (!confirm('Apakah Anda yakin ingin membatalkan pembayaran ini? Status akan kembali menjadi Belum Bayar.')) {
            return;
        }

        const clearForm = {
            periode_bulan: `${selectedPeriod}-01`,
            tanggal_bayar: '',
        };

        router.post(`/rehab-cases/${rehabCase.id}/payments`, clearForm, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Pembayaran berhasil dibatalkan');
                setData('tanggal_bayar', '');
            },
            onError: () => {
                toast.error('Gagal membatalkan pembayaran');
            }
        });
    };

    if (periods.length === 0) {
        return null;
    }

    return (
        <div className="rounded-xl border border-[#22577A]/20 bg-white shadow-sm overflow-hidden mt-6">
            <div className="flex items-center gap-2 border-b border-[#22577A]/10 bg-[#22577A]/5 px-6 py-4">
                <CreditCard className="h-5 w-5 text-[#22577A]" />
                <h2 className="font-semibold text-[#22577A]">Payment Monitoring Keluarga</h2>
            </div>
            
            <div className="p-6">
                <div className="flex flex-col md:flex-row gap-8">
                    {/* Left Col: Selector & Status */}
                    <div className="md:w-1/3 flex flex-col gap-6">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-2">
                                Pilih Periode Bulan
                            </label>
                            <div className="relative">
                                <select 
                                    className="block w-full rounded-md border-slate-300 py-2 pl-3 pr-10 text-base focus:border-[#22577A] focus:outline-none focus:ring-[#22577A] sm:text-sm"
                                    value={selectedPeriod}
                                    onChange={(e) => setSelectedPeriod(e.target.value)}
                                >
                                    {periods.map(p => (
                                        <option key={p} value={p}>{formatDateMonth(p)}</option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        {periodData && (
                            <div className={`rounded-lg p-4 border ${
                                periodData.status === 'SUDAH BAYAR' ? 'bg-green-50 border-green-200' :
                                periodData.status === 'BELUM BAYAR' ? 'bg-slate-50 border-slate-200' :
                                'bg-red-50 border-red-200'
                            }`}>
                                <div className="text-xs font-medium text-slate-500 mb-1 uppercase tracking-wider">Status Pembayaran</div>
                                <div className="flex items-center gap-2">
                                    {periodData.status === 'SUDAH BAYAR' && <CheckCircle2 className="h-5 w-5 text-green-600" />}
                                    {periodData.status === 'BELUM BAYAR' && <XCircle className="h-5 w-5 text-slate-400" />}
                                    {periodData.status === 'DATA TIDAK KONSISTEN' && <AlertTriangle className="h-5 w-5 text-red-600" />}
                                    
                                    <span className={`font-bold ${
                                        periodData.status === 'SUDAH BAYAR' ? 'text-green-700' :
                                        periodData.status === 'BELUM BAYAR' ? 'text-slate-700' :
                                        'text-red-700'
                                    }`}>
                                        {periodData.status}
                                    </span>
                                </div>
                                {periodData.status === 'DATA TIDAK KONSISTEN' && (
                                    <p className="text-xs text-red-600 mt-2">
                                        Data pembayaran tidak konsisten (sebagian anggota sudah bayar, sebagian belum). Silakan sinkronkan.
                                    </p>
                                )}
                            </div>
                        )}
                        
                        {periodData && (
                            <form onSubmit={handleSavePayment} className="flex flex-col gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-2">
                                        Tanggal Bayar
                                    </label>
                                    <input 
                                        type="date"
                                        className="block w-full rounded-md border-slate-300 py-2 px-3 text-base focus:border-[#22577A] focus:outline-none focus:ring-[#22577A] sm:text-sm"
                                        value={data.tanggal_bayar || ''}
                                        onChange={(e) => setData('tanggal_bayar', e.target.value)}
                                        required
                                    />
                                </div>
                                
                                <div className="flex flex-col items-start gap-3 mt-2">
                                    <Button 
                                        type="submit" 
                                        disabled={processing} 
                                        className="whitespace-nowrap px-4 py-2 bg-[#22577A] hover:bg-[#22577A]/90 w-full justify-center"
                                    >
                                        {processing ? 'Menyimpan...' : 'Simpan Pembayaran'}
                                    </Button>
                                    
                                    {periodData.status === 'SUDAH BAYAR' && (
                                        <Button 
                                            type="button" 
                                            variant="outline" 
                                            onClick={handleClearPayment}
                                            disabled={processing}
                                            className="whitespace-nowrap px-4 py-2 border-red-200 text-red-600 hover:bg-red-50 w-full justify-center"
                                        >
                                            Batalkan
                                        </Button>
                                    )}
                                </div>
                            </form>
                        )}
                    </div>

                    {/* Right Col: Details */}
                    <div className="md:w-2/3">
                        {periodData && (
                            <div>
                                <h3 className="text-lg font-semibold text-slate-800 mb-4">
                                    Detail Tagihan Keluarga: {formatDateMonth(selectedPeriod)}
                                </h3>
                                
                                <div className="bg-slate-50 rounded-lg border border-slate-200 overflow-hidden">
                                    <table className="w-full text-left text-sm">
                                        <thead className="bg-slate-100 text-slate-600 border-b border-slate-200">
                                            <tr>
                                                <th className="px-4 py-3 font-medium">Anggota Keluarga</th>
                                                <th className="px-4 py-3 text-right font-medium">Besaran Cicilan</th>
                                                <th className="px-4 py-3 text-center font-medium">Tanggal Bayar</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-200">
                                            {periodData.breakdown.map((item, idx) => (
                                                <tr key={idx}>
                                                    <td className="px-4 py-3 font-medium text-slate-900">{item.memberName}</td>
                                                    <td className="px-4 py-3 text-right text-slate-700">{formatCurrency(item.amount)}</td>
                                                    <td className="px-4 py-3 text-center text-slate-500">
                                                        {item.tanggalBayar ? new Date(item.tanggalBayar).toLocaleDateString('id-ID') : '-'}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                        <tfoot className="bg-slate-100 font-semibold border-t-2 border-slate-200">
                                            <tr>
                                                <td className="px-4 py-4 text-slate-900">TOTAL TAGIHAN</td>
                                                <td className="px-4 py-4 text-right text-lg text-[#22577A]">{formatCurrency(periodData.totalAmount)}</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
