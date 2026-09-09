import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { PlusCircle, Trash2 } from 'lucide-react';

interface RehabRegistrationModalProps {
    isOpen: boolean;
    onClose: () => void;
    peserta: any;
    candidates: any[];
    latestBatch?: any;
}

export function RehabRegistrationModal({
    isOpen,
    onClose,
    peserta,
    candidates,
    latestBatch,
}: RehabRegistrationModalProps) {
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        // SIPP Data
        batch_id: latestBatch?.batch_id || '',
        sipp_terdaftar_rehab: false,
        sipp_id_cicilan: latestBatch?.idcicilan || '',
        sipp_noka_pendaftar: peserta.noka,
        sipp_tanggal_daftar_rehab: '',
        sipp_tanggal_akhir_cicilan: '',
        sipp_jumlah_peserta_sipp: '',
        sipp_catatan: '',

        // Financial & Rehab Case setup
        tanggal_pendaftaran: '',
        jumlah_bulan_cicilan: latestBatch?.jmlbulancicilawal || '',

        // Members setup
        members: [
            {
                temp_id: 'master',
                peserta_id: peserta.id,
                nama: peserta.nama,
                noka: peserta.noka,
                tagihan_awal: '',
                is_pendaftar: true,
                is_custom_schedule: false,
                custom_installments: [],
            },
        ],
    });

    const [step, setStep] = useState(1); // 1: SIPP, 2: Candidates, 3: Financial

    // Handle initial load logic if modal is reopened
    React.useEffect(() => {
        if (isOpen) {
            setData((prev: any) => ({
                ...prev,
                batch_id: latestBatch?.batch_id || '',
                sipp_id_cicilan: latestBatch?.idcicilan || '',
                jumlah_bulan_cicilan: latestBatch?.jmlbulancicilawal || '',
                members: [
                    {
                        temp_id: 'master',
                        peserta_id: peserta.id,
                        nama: peserta.nama,
                        noka: peserta.noka,
                        tagihan_awal: '',
                        is_pendaftar: true,
                        is_custom_schedule: false,
                        custom_installments: [],
                    },
                ],
            }));
            setStep(1);
            clearErrors();
        }
    }, [isOpen]);

    const toggleCandidate = (candidate: any, checked: boolean) => {
        if (checked) {
            setData('members', [
                ...data.members,
                {
                    temp_id: `cand-${candidate.id}`,
                    peserta_id: candidate.id,
                    nama: candidate.nama,
                    noka: candidate.noka,
                    tagihan_awal: '',
                    is_pendaftar: false,
                    is_custom_schedule: false,
                    custom_installments: [],
                },
            ]);
        } else {
            setData(
                'members',
                data.members.filter((m: any) => m.peserta_id !== candidate.id)
            );
        }
    };

    const addManualMember = () => {
        setData('members', [
            ...data.members,
            {
                temp_id: `manual-${Date.now()}`,
                peserta_id: null,
                nama: '',
                noka: '',
                tagihan_awal: '',
                is_pendaftar: false,
                is_custom_schedule: false,
                custom_installments: [],
            },
        ]);
    };

    const removeManualMember = (tempId: string) => {
        setData(
            'members',
            data.members.filter((m: any) => m.temp_id !== tempId)
        );
    };

    const updateMemberField = (tempId: string, field: string, value: any) => {
        setData(
            'members',
            data.members.map((m: any) =>
                m.temp_id === tempId ? { ...m, [field]: value } : m
            )
        );
    };

    const generateInitialCustomSchedule = (startDate: string, months: number, tagihanAwal: number) => {
        const schedule = [];
        const baseAmount = Math.floor(tagihanAwal / months);
        let total = 0;
        
        for (let i = 0; i < months; i++) {
            const d = new Date(startDate);
            d.setMonth(d.getMonth() + i);
            const periode = d.toISOString().split('T')[0];
            const isLast = i === months - 1;
            const amount = isLast ? (tagihanAwal - total) : baseAmount;
            total += amount;
            
            schedule.push({ periode_bulan: periode, besaran_cicilan: amount });
        }
        return schedule;
    };

    const toggleCustomSchedule = (member: any, checked: boolean) => {
        if (checked && data.tanggal_pendaftaran && data.jumlah_bulan_cicilan && member.tagihan_awal) {
            const initialSchedule = generateInitialCustomSchedule(
                data.tanggal_pendaftaran,
                parseInt(data.jumlah_bulan_cicilan),
                parseFloat(member.tagihan_awal)
            );
            setData(
                'members',
                data.members.map((m: any) =>
                    m.temp_id === member.temp_id ? { ...m, is_custom_schedule: true, custom_installments: initialSchedule } : m
                )
            );
        } else {
            setData(
                'members',
                data.members.map((m: any) =>
                    m.temp_id === member.temp_id ? { ...m, is_custom_schedule: false, custom_installments: [] } : m
                )
            );
        }
    };

    const updateCustomInstallment = (memberTempId: string, index: number, value: string) => {
        setData(
            'members',
            data.members.map((m: any) => {
                if (m.temp_id === memberTempId) {
                    const newInstallments = [...m.custom_installments];
                    newInstallments[index].besaran_cicilan = value ? parseFloat(value) : 0;
                    return { ...m, custom_installments: newInstallments };
                }
                return m;
            })
        );
    };

    const applyCustomScheduleToOthers = (sourceMember: any) => {
        setData(
            'members',
            data.members.map((m: any) => {
                if (m.temp_id !== sourceMember.temp_id && m.tagihan_awal === sourceMember.tagihan_awal) {
                    const copiedInstallments = sourceMember.custom_installments.map((inst: any) => ({ ...inst }));
                    return { ...m, is_custom_schedule: true, custom_installments: copiedInstallments };
                }
                return m;
            })
        );
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        const maxStep = data.sipp_terdaftar_rehab ? 3 : 2;
        
        if (step < maxStep) {
            setStep(step + 1);
            return;
        }

        // Validate custom schedules
        if (data.sipp_terdaftar_rehab) {
            for (const member of data.members) {
                if (member.is_custom_schedule) {
                    const totalCustom = member.custom_installments.reduce((sum: number, inst: any) => sum + (Number(inst.besaran_cicilan) || 0), 0);
                    if (totalCustom !== parseFloat(member.tagihan_awal)) {
                        alert(`Total jadwal cicilan khusus untuk ${member.nama} (Rp ${totalCustom}) tidak sama dengan Tagihan Awal (Rp ${member.tagihan_awal}). Harap perbaiki sebelum menyimpan.`);
                        return;
                    }
                }
            }
        }

        post(`/peserta/${peserta.id}/rehab`, {
            onSuccess: () => {
                reset();
                setStep(1);
                onClose();
            },
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-[650px] max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Verifikasi SIPP & Pendaftaran REHAB</DialogTitle>
                    <DialogDescription>
                        Konfigurasi sinkronisasi REHAB dan snapshot SIPP
                    </DialogDescription>
                </DialogHeader>

                {/* Stepper */}
                <div className="flex items-center justify-between mb-4 mt-2 px-4 relative">
                    <div className="absolute left-10 right-10 top-1/2 h-0.5 bg-slate-200 -z-10" />
                    {[
                        { label: 'SIPP Verification', s: 1 },
                        { label: 'Anggota', s: 2 },
                        { label: 'Cicilan', s: 3 },
                    ].filter(st => data.sipp_terdaftar_rehab || st.s < 3).map((st) => (
                        <div key={st.s} className="flex flex-col items-center bg-white px-2">
                            <div
                                className={`w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm ${
                                    step === st.s
                                        ? 'bg-blue-600 text-white ring-4 ring-blue-100'
                                        : step > st.s
                                        ? 'bg-green-500 text-white'
                                        : 'bg-slate-100 text-slate-400'
                                }`}
                            >
                                {step > st.s ? '✓' : st.s}
                            </div>
                            <span
                                className={`text-xs mt-2 font-medium ${
                                    step === st.s ? 'text-blue-600' : 'text-slate-500'
                                }`}
                            >
                                {st.label}
                            </span>
                        </div>
                    ))}
                </div>

                <form onSubmit={handleSubmit} className="space-y-4 py-4">
                    {step === 1 && (
                        <div className="space-y-4">
                            <div className="bg-slate-50 p-4 rounded-lg border flex justify-between items-center">
                                <div>
                                    <p className="text-sm font-medium">Waktu pengecekan:</p>
                                    <p className="text-sm text-slate-500">{new Date().toLocaleString('id-ID')}</p>
                                </div>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="terdaftar"
                                    checked={data.sipp_terdaftar_rehab}
                                    onCheckedChange={(c) => setData('sipp_terdaftar_rehab', !!c)}
                                />
                                <Label htmlFor="terdaftar" className="font-medium cursor-pointer">
                                    Peserta Terdaftar REHAB di SIPP
                                </Label>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label>ID Cicilan</Label>
                                    <Input
                                        value={data.sipp_id_cicilan}
                                        onChange={(e) => setData('sipp_id_cicilan', e.target.value)}
                                        placeholder="Kosongkan jika tidak ada"
                                    />
                                    {errors.sipp_id_cicilan && (
                                        <p className="text-sm text-red-500">{errors.sipp_id_cicilan}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label>Catatan</Label>
                                    <textarea
                                        className="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        value={data.sipp_catatan}
                                        onChange={(e) => setData('sipp_catatan', e.target.value)}
                                    />
                                </div>
                            </div>
                        </div>
                    )}

                    {step === 2 && (
                        <div className="space-y-4">
                            <div className="p-4 border rounded-lg bg-blue-50 border-blue-200">
                                <div className="flex items-start space-x-3">
                                    <Checkbox checked disabled />
                                    <div>
                                        <p className="font-medium">{peserta.nama}</p>
                                        <p className="text-sm text-slate-500">NOKA: {peserta.noka}</p>
                                        <p className="text-xs text-blue-600 font-medium">Pendaftar Utama</p>
                                    </div>
                                </div>
                            </div>

                            {candidates.length > 0 && (
                                <div className="space-y-3 mt-4">
                                    <Label>Kandidat Anggota Keluarga dari Excel (No. HP Sama)</Label>
                                    {candidates.map((candidate) => (
                                        <div key={candidate.id} className="p-3 border rounded-lg flex items-start space-x-3">
                                            <Checkbox
                                                id={`cand-${candidate.id}`}
                                                checked={data.members.some((m: any) => m.peserta_id === candidate.id)}
                                                onCheckedChange={(c) => toggleCandidate(candidate, !!c)}
                                            />
                                            <Label htmlFor={`cand-${candidate.id}`} className="cursor-pointer">
                                                <p className="font-medium">{candidate.nama}</p>
                                                <p className="text-sm text-slate-500">NOKA: {candidate.noka}</p>
                                            </Label>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Manual additions */}
                            <div className="mt-6 pt-4 border-t">
                                <div className="flex justify-between items-center mb-4">
                                    <Label>Anggota Tambahan Manual (Dari SIPP)</Label>
                                    <Button type="button" variant="outline" size="sm" onClick={addManualMember}>
                                        <PlusCircle className="w-4 h-4 mr-2" /> Tambah Peserta Manual
                                    </Button>
                                </div>
                                {data.members
                                    .filter((m: any) => !m.peserta_id && m.temp_id !== 'master')
                                    .map((member: any, index: number) => (
                                        <div key={member.temp_id} className="p-3 border rounded-lg mb-3 bg-slate-50 flex items-start space-x-3">
                                            <div className="flex-1 grid grid-cols-2 gap-3">
                                                <div className="space-y-1">
                                                    <Label className="text-xs">Nama Lengkap</Label>
                                                    <Input
                                                        value={member.nama}
                                                        onChange={(e) => updateMemberField(member.temp_id, 'nama', e.target.value)}
                                                        required
                                                    />
                                                </div>
                                                <div className="space-y-1">
                                                    <Label className="text-xs">NOKA</Label>
                                                    <Input
                                                        value={member.noka}
                                                        onChange={(e) => updateMemberField(member.temp_id, 'noka', e.target.value)}
                                                        required
                                                    />
                                                </div>
                                            </div>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="text-red-500 mt-5"
                                                onClick={() => removeManualMember(member.temp_id)}
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </Button>
                                        </div>
                                    ))}
                            </div>

                            {errors.members && (
                                <p className="text-sm text-red-500">{errors.members}</p>
                            )}
                        </div>
                    )}

                    {step === 3 && (
                        <div className="space-y-6">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label>Tanggal Pendaftaran REHAB *</Label>
                                    <Input
                                        type="date"
                                        value={data.tanggal_pendaftaran}
                                        onChange={(e) => setData('tanggal_pendaftaran', e.target.value)}
                                        required
                                    />
                                    {errors.tanggal_pendaftaran && (
                                        <p className="text-sm text-red-500">{errors.tanggal_pendaftaran}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label>Jumlah Bulan Cicilan *</Label>
                                    <Input
                                        type="number"
                                        min="1"
                                        value={data.jumlah_bulan_cicilan}
                                        onChange={(e) => setData('jumlah_bulan_cicilan', e.target.value)}
                                        required
                                    />
                                    {errors.jumlah_bulan_cicilan && (
                                        <p className="text-sm text-red-500">{errors.jumlah_bulan_cicilan}</p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-4 pt-4 border-t">
                                <Label>Tagihan Awal per Anggota *</Label>
                                {data.members.map((member: any, index: number) => {
                                    return (
                                        <div key={member.temp_id} className="border border-slate-200 rounded-lg p-4 mb-4 bg-slate-50">
                                            <div className="grid grid-cols-2 gap-4 items-center mb-4">
                                                <div className="text-sm font-medium flex items-center gap-2">
                                                    {member.nama} 
                                                    {!member.peserta_id && <span className="text-[10px] bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full">Manual</span>}
                                                </div>
                                                <div>
                                                    <Input
                                                        type="number"
                                                        min="0"
                                                        placeholder="Rp"
                                                        value={member.tagihan_awal}
                                                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => updateMemberField(member.temp_id, 'tagihan_awal', e.target.value)}
                                                        required
                                                    />
                                                    {errors[`members.${index}.tagihan_awal`] && (
                                                        <p className="text-sm text-red-500 mt-1">
                                                            {errors[`members.${index}.tagihan_awal`]}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>

                                            <div className="flex items-center space-x-2 mt-4 pt-4 border-t border-slate-200">
                                                <Checkbox
                                                    id={`custom-${member.temp_id}`}
                                                    checked={member.is_custom_schedule}
                                                    onCheckedChange={(c) => toggleCustomSchedule(member, !!c)}
                                                    disabled={!data.tanggal_pendaftaran || !data.jumlah_bulan_cicilan || !member.tagihan_awal}
                                                />
                                                <Label htmlFor={`custom-${member.temp_id}`} className="font-medium cursor-pointer text-sm text-slate-700">
                                                    Gunakan Jadwal Khusus SIPP (Tidak Dibagi Rata)
                                                </Label>
                                            </div>
                                            {!data.tanggal_pendaftaran && !member.tagihan_awal && (
                                                <p className="text-xs text-slate-500 mt-1 ml-6">Isi Tanggal Daftar, Jumlah Bulan, dan Tagihan Awal terlebih dahulu.</p>
                                            )}

                                            {member.is_custom_schedule && member.custom_installments.length > 0 && (
                                                <div className="mt-4 ml-6 p-4 bg-white border border-slate-200 rounded-lg">
                                                    <div className="text-sm font-medium mb-3 text-slate-700">Penyesuaian Jadwal Manual</div>
                                                    <div className="grid grid-cols-2 gap-3">
                                                        {member.custom_installments.map((inst: any, i: number) => (
                                                            <div key={i} className="flex items-center gap-2">
                                                                <div className="w-8 h-8 flex-shrink-0 bg-slate-100 rounded flex items-center justify-center text-xs font-medium text-slate-500">
                                                                    {i + 1}
                                                                </div>
                                                                <Input
                                                                    type="number"
                                                                    className="h-8 text-sm"
                                                                    value={inst.besaran_cicilan}
                                                                    onChange={(e) => updateCustomInstallment(member.temp_id, i, e.target.value)}
                                                                />
                                                            </div>
                                                        ))}
                                                    </div>
                                                    <div className="mt-4 pt-3 border-t flex justify-between items-center text-sm">
                                                        <span className="font-medium text-slate-600">Total Nominal Manual:</span>
                                                        <span className={`font-bold ${member.custom_installments.reduce((sum: number, inst: any) => sum + (Number(inst.besaran_cicilan) || 0), 0) === parseFloat(member.tagihan_awal) ? 'text-green-600' : 'text-red-600'}`}>
                                                            Rp {new Intl.NumberFormat('id-ID').format(member.custom_installments.reduce((sum: number, inst: any) => sum + (Number(inst.besaran_cicilan) || 0), 0))}
                                                        </span>
                                                    </div>
                                                    {data.members.filter((m: any) => m.temp_id !== member.temp_id && m.tagihan_awal === member.tagihan_awal).length > 0 && (
                                                        <div className="mt-4 text-right">
                                                            <Button 
                                                                type="button" 
                                                                variant="outline" 
                                                                size="sm" 
                                                                onClick={() => applyCustomScheduleToOthers(member)}
                                                                className="text-xs text-blue-600 hover:text-blue-700 hover:bg-blue-50 border-blue-200"
                                                            >
                                                                Terapkan ke Peserta Lain (Tagihan Sama)
                                                            </Button>
                                                        </div>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    )}

                    <DialogFooter className="mt-8 pt-4 border-t flex justify-between w-full">
                        {step > 1 ? (
                            <Button type="button" variant="outline" onClick={() => setStep(step - 1)}>
                                Sebelumnya
                            </Button>
                        ) : (
                            <div />
                        )}
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Menyimpan...' : (
                                !data.sipp_terdaftar_rehab && step === 2 
                                    ? 'Simpan Verifikasi SIPP' 
                                    : step < 3 
                                        ? 'Selanjutnya' 
                                        : 'Simpan & Generate Jadwal'
                            )}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
