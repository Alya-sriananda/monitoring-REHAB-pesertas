import { useState } from 'react';
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

interface RehabRegistrationModalProps {
    isOpen: boolean;
    onClose: () => void;
    peserta: any;
    candidates: any[];
}

export function RehabRegistrationModal({
    isOpen,
    onClose,
    peserta,
    candidates,
}: RehabRegistrationModalProps) {
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        // SIPP Data
        sipp_tanggal_cek: new Date().toISOString().split('T')[0],
        sipp_terdaftar_rehab: false,
        sipp_status_rehab: '',
        sipp_id_cicilan: '',
        sipp_noka_pendaftar: peserta.noka,
        sipp_npp_petugas: '',
        sipp_tanggal_daftar_rehab: '',
        sipp_tagihan_bulan_berjalan: '',
        sipp_tagihan_sebelum_bulan_berjalan: '',
        sipp_status_pembayaran_bulan_berjalan: '',
        sipp_tanggal_akhir_cicilan: '',
        sipp_jumlah_peserta_sipp: '',
        sipp_catatan: '',

        // Financial & Rehab Case setup
        tanggal_pendaftaran: '',
        jumlah_bulan_cicilan: '',

        // Members setup
        members: [
            {
                peserta_id: peserta.id,
                tagihan_awal: '',
                is_pendaftar: true,
            },
        ],
    });

    const [step, setStep] = useState(1); // 1: SIPP, 2: Candidates, 3: Financial

    const toggleCandidate = (candidate: any, checked: boolean) => {
        if (checked) {
            setData('members', [
                ...data.members,
                { peserta_id: candidate.id, tagihan_awal: '', is_pendaftar: false },
            ]);
        } else {
            setData(
                'members',
                data.members.filter((m: any) => m.peserta_id !== candidate.id)
            );
        }
    };

    const updateMemberTagihan = (pesertaId: number, tagihan: string) => {
        setData(
            'members',
            data.members.map((m: any) =>
                m.peserta_id === pesertaId ? { ...m, tagihan_awal: tagihan } : m
            )
        );
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (step < 3) {
            setStep(step + 1);
            return;
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
            <DialogContent className="sm:max-w-[600px] max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Verifikasi SIPP & Pendaftaran REHAB</DialogTitle>
                    <DialogDescription>
                        {step === 1 && 'Langkah 1: Masukkan data snapshot dari SIPP'}
                        {step === 2 && 'Langkah 2: Konfirmasi anggota keluarga yang ikut REHAB'}
                        {step === 3 && 'Langkah 3: Konfigurasi cicilan REHAB'}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4 py-4">
                    {step === 1 && (
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label>Tanggal Cek SIPP *</Label>
                                    <Input
                                        type="date"
                                        value={data.sipp_tanggal_cek}
                                        onChange={(e) => setData('sipp_tanggal_cek', e.target.value)}
                                        required
                                    />
                                    {errors.sipp_tanggal_cek && (
                                        <p className="text-sm text-red-500">{errors.sipp_tanggal_cek}</p>
                                    )}
                                </div>
                                <div className="space-y-2 flex flex-col justify-end">
                                    <div className="flex items-center space-x-2 h-10">
                                        <Checkbox
                                            id="terdaftar"
                                            checked={data.sipp_terdaftar_rehab}
                                            onCheckedChange={(c) => setData('sipp_terdaftar_rehab', !!c)}
                                        />
                                        <Label htmlFor="terdaftar">Peserta Terdaftar REHAB di SIPP</Label>
                                    </div>
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label>ID Cicilan</Label>
                                    <Input
                                        value={data.sipp_id_cicilan}
                                        onChange={(e) => setData('sipp_id_cicilan', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label>Status REHAB</Label>
                                    <Input
                                        value={data.sipp_status_rehab}
                                        onChange={(e) => setData('sipp_status_rehab', e.target.value)}
                                    />
                                </div>
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
                    )}

                    {step === 2 && (
                        <div className="space-y-4">
                            <div className="p-4 border rounded-lg bg-slate-50">
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
                                    <Label>Kandidat Anggota Keluarga (No. HP Sama)</Label>
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

                            <div className="space-y-4">
                                <Label>Tagihan Awal per Anggota *</Label>
                                {data.members.map((member: any, index: number) => {
                                    const mData = member.peserta_id === peserta.id 
                                        ? peserta 
                                        : candidates.find((c) => c.id === member.peserta_id);
                                    
                                    return (
                                        <div key={member.peserta_id} className="grid grid-cols-2 gap-4 items-center">
                                            <div className="text-sm font-medium">{mData?.nama}</div>
                                            <div>
                                                <Input
                                                    type="number"
                                                    min="0"
                                                    placeholder="Rp"
                                                    value={member.tagihan_awal}
                                                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => updateMemberTagihan(member.peserta_id, e.target.value)}
                                                    required
                                                />
                                                {errors[`members.${index}.tagihan_awal`] && (
                                                    <p className="text-sm text-red-500 mt-1">
                                                        {errors[`members.${index}.tagihan_awal`]}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    )}

                    <DialogFooter className="mt-6 flex justify-between w-full">
                        {step > 1 ? (
                            <Button type="button" variant="outline" onClick={() => setStep(step - 1)}>
                                Sebelumnya
                            </Button>
                        ) : (
                            <div />
                        )}
                        <Button type="submit" disabled={processing}>
                            {step < 3 ? 'Selanjutnya' : 'Simpan & Generate Jadwal'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
