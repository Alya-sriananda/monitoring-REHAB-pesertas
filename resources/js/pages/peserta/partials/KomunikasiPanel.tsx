import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { MessageCircle, Copy, ExternalLink, Save, History, CheckCircle2, AlertTriangle, XCircle } from 'lucide-react';
import { toast } from 'sonner';

export function KomunikasiPanel({ 
    rehabCase, 
    peserta, 
    selectedPeriod, 
    totalAmount, 
    templates 
}: { 
    rehabCase: any, 
    peserta: any, 
    selectedPeriod: string, 
    totalAmount: number, 
    templates: any[] 
}) {
    const [selectedTemplateId, setSelectedTemplateId] = useState<string>('');
    const [previewMessage, setPreviewMessage] = useState<string>('');
    const [isLoading, setIsLoading] = useState(false);
    const [isSaving, setIsSaving] = useState(false);
    const [catatan, setCatatan] = useState('');

    const formatDateMonth = (yyyyMm: string) => {
        if (!yyyyMm) return '';
        const [year, month] = yyyyMm.split('-');
        const date = new Date(parseInt(year), parseInt(month) - 1, 1);
        return date.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
    };

    const handleGeneratePreview = async () => {
        if (!selectedTemplateId || !selectedPeriod) {
            toast.error('Pilih template dan pastikan periode tersedia');
            return;
        }

        setIsLoading(true);
        try {
            // Find the active member/pendaftar (or just use the first member's ID to pass)
            const memberId = rehabCase.members?.[0]?.id;
            
            const response = await fetch('/komunikasi/preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    template_pesan_id: selectedTemplateId,
                    peserta_id: peserta.id,
                    periode_bulan: `${selectedPeriod}-01`,
                    nominal: totalAmount,
                }),
            });

            const data = await response.json();
            if (response.ok) {
                setPreviewMessage(data.pesan);
            } else {
                toast.error(data.message || 'Gagal generate preview');
            }
        } catch (error) {
            toast.error('Terjadi kesalahan jaringan');
        } finally {
            setIsLoading(false);
        }
    };

    const handleCopy = () => {
        if (!previewMessage) return;
        navigator.clipboard.writeText(previewMessage);
        toast.success('Pesan berhasil disalin ke clipboard');
    };

    const handleOpenWhatsApp = () => {
        if (!peserta.no_hp) {
            toast.error('Nomor WhatsApp belum tersedia');
            return;
        }
        if (!previewMessage) return;

        // Normalize phone number (very basic normalization, assumes Indonesian numbers)
        let phone = peserta.no_hp.replace(/\D/g, '');
        if (phone.startsWith('0')) {
            phone = '62' + phone.substring(1);
        }

        const url = `https://wa.me/${phone}?text=${encodeURIComponent(previewMessage)}`;
        window.open(url, '_blank');
    };

    const handleSaveRecord = async (status: string) => {
        if (!selectedTemplateId || !previewMessage) {
            toast.error('Generate pesan terlebih dahulu');
            return;
        }

        setIsSaving(true);
        try {
            const response = await fetch(`/rehab-cases/${rehabCase.id}/komunikasi`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    peserta_id: peserta.id,
                    template_pesan_id: selectedTemplateId,
                    periode_bulan: `${selectedPeriod}-01`,
                    pesan: previewMessage,
                    status: status,
                    catatan: catatan,
                }),
            });

            const data = await response.json();
            if (response.ok) {
                toast.success(data.message || 'Komunikasi berhasil dicatat');
                setCatatan('');
                router.reload({ only: ['peserta'] });
            } else {
                toast.error(data.message || 'Gagal mencatat komunikasi');
            }
        } catch (error) {
            toast.error('Terjadi kesalahan jaringan');
        } finally {
            setIsSaving(false);
        }
    };

    if (!templates || templates.length === 0) return null;

    const currentKomunikasis = rehabCase.komunikasis?.filter(
        (k: any) => k.periode_bulan.substring(0, 7) === selectedPeriod
    ) || [];

    return (
        <div className="rounded-xl border border-[#22577A]/20 bg-white shadow-sm overflow-hidden mt-6">
            <div className="flex items-center gap-2 border-b border-[#22577A]/10 bg-[#22577A]/5 px-6 py-4">
                <MessageCircle className="h-5 w-5 text-[#22577A]" />
                <h2 className="font-semibold text-[#22577A]">Komunikasi & Follow-up</h2>
            </div>
            
            <div className="p-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    {/* Panel Kiri: Pengaturan */}
                    <div className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-2">
                                Pilih Template Pesan
                            </label>
                            <select 
                                className="block w-full rounded-md border-slate-300 py-2 pl-3 pr-10 text-base focus:border-[#22577A] focus:outline-none focus:ring-[#22577A] sm:text-sm"
                                value={selectedTemplateId}
                                onChange={(e) => {
                                    setSelectedTemplateId(e.target.value);
                                    setPreviewMessage(''); // Reset preview on template change
                                }}
                            >
                                <option value="">-- Pilih Template --</option>
                                {templates.map(t => (
                                    <option key={t.id} value={t.id}>{t.nama_template}</option>
                                ))}
                            </select>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-medium text-slate-500 mb-1">Periode Target</label>
                                <div className="text-sm font-medium text-slate-900">{formatDateMonth(selectedPeriod)}</div>
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-slate-500 mb-1">Nominal Target</label>
                                <div className="text-sm font-medium text-slate-900">
                                    {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(totalAmount)}
                                </div>
                            </div>
                        </div>

                        <Button 
                            type="button" 
                            onClick={handleGeneratePreview}
                            disabled={!selectedTemplateId || !selectedPeriod || isLoading}
                            className="w-full"
                        >
                            {isLoading ? 'Generating...' : 'Generate Preview'}
                        </Button>

                        {currentKomunikasis.length > 0 && (
                            <div className="mt-6 pt-6 border-t border-slate-100 space-y-3">
                                <h4 className="text-sm font-semibold text-slate-800 flex items-center">
                                    <History className="h-4 w-4 mr-2" /> Riwayat Komunikasi (Periode Ini)
                                </h4>
                                <div className="space-y-3 max-h-[250px] overflow-y-auto pr-2">
                                    {currentKomunikasis.map((kom: any, idx: number) => (
                                        <div key={idx} className="bg-slate-50 border border-slate-200 rounded-md p-3 text-sm">
                                            <div className="flex items-center justify-between mb-2">
                                                <div className="flex items-center gap-1.5 font-medium">
                                                    {kom.status === 'sudah_dihubungi' && <CheckCircle2 className="h-4 w-4 text-green-600" />}
                                                    {kom.status === 'tidak_terdaftar_wa' && <AlertTriangle className="h-4 w-4 text-yellow-600" />}
                                                    {kom.status === 'gagal' && <XCircle className="h-4 w-4 text-red-600" />}
                                                    
                                                    <span className={
                                                        kom.status === 'sudah_dihubungi' ? 'text-green-700' :
                                                        kom.status === 'tidak_terdaftar_wa' ? 'text-yellow-700' : 'text-red-700'
                                                    }>
                                                        {kom.status === 'sudah_dihubungi' ? 'Sudah Dihubungi' :
                                                         kom.status === 'tidak_terdaftar_wa' ? 'Tidak Terdaftar WA' : 'Gagal'}
                                                    </span>
                                                </div>
                                                <span className="text-xs text-slate-500">
                                                    {new Date(kom.created_at).toLocaleDateString('id-ID', {
                                                        day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit'
                                                    })}
                                                </span>
                                            </div>
                                            {kom.template && (
                                                <div className="text-xs text-slate-600 mb-1">
                                                    <span className="font-medium">Template:</span> {kom.template}
                                                </div>
                                            )}
                                            {kom.catatan && (
                                                <div className="text-xs text-slate-600 italic border-l-2 border-slate-300 pl-2 mt-1">
                                                    "{kom.catatan}"
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Panel Kanan: Preview & Actions */}
                    <div className="space-y-4 border-l pl-8 border-slate-100">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-2">
                                Preview Pesan
                            </label>
                            <div className="bg-slate-50 border border-slate-200 rounded-md p-4 min-h-[150px] whitespace-pre-wrap text-sm text-slate-800">
                                {previewMessage || <span className="text-slate-400 italic">Preview pesan akan muncul di sini...</span>}
                            </div>
                        </div>

                        <div className="flex gap-2">
                            <Button 
                                type="button" 
                                variant="outline"
                                onClick={handleCopy}
                                disabled={!previewMessage}
                                className="flex-1"
                            >
                                <Copy className="w-4 h-4 mr-2" /> Copy
                            </Button>
                            <Button 
                                type="button"
                                variant="default"
                                onClick={handleOpenWhatsApp}
                                disabled={!previewMessage || !peserta.no_hp}
                                className="flex-1 bg-green-600 hover:bg-green-700 text-white border-green-600"
                            >
                                <ExternalLink className="w-4 h-4 mr-2" /> Buka WA
                            </Button>
                        </div>
                        {!peserta.no_hp && (
                            <p className="text-xs text-red-500 mt-1">* Nomor WhatsApp belum tersedia</p>
                        )}

                        {previewMessage && (
                            <div className="mt-6 pt-4 border-t border-slate-200 space-y-3">
                                <h4 className="text-sm font-medium text-slate-700">Catat Hasil Komunikasi</h4>
                                <input 
                                    type="text" 
                                    placeholder="Catatan tambahan (opsional)" 
                                    className="block w-full rounded-md border-slate-300 py-2 px-3 text-sm focus:border-[#22577A] focus:outline-none focus:ring-[#22577A]"
                                    value={catatan}
                                    onChange={(e) => setCatatan(e.target.value)}
                                />
                                <div className="flex flex-wrap gap-2 mt-2">
                                    <Button size="sm" variant="outline" disabled={isSaving} onClick={() => handleSaveRecord('sudah_dihubungi')} className="border-green-200 text-green-700 hover:bg-green-50">
                                        <Save className="w-3 h-3 mr-1" /> Sudah Dihubungi
                                    </Button>
                                    <Button size="sm" variant="outline" disabled={isSaving} onClick={() => handleSaveRecord('tidak_terdaftar_wa')} className="border-yellow-200 text-yellow-700 hover:bg-yellow-50">
                                        Tidak Terdaftar WA
                                    </Button>
                                    <Button size="sm" variant="outline" disabled={isSaving} onClick={() => handleSaveRecord('gagal')} className="border-red-200 text-red-700 hover:bg-red-50">
                                        Gagal
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
