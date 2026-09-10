import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Settings', href: '/settings/templates' },
    { title: 'Template Pesan', href: '/settings/templates' },
];

export default function Templates({ templatePesans }: { templatePesans: any[] }) {
    const { data: dataPesan, setData: setDataPesan, post: postPesan, put: putPesan, reset: resetPesan, processing: processingPesan } = useForm({
        id: null as number | null,
        nama_template: '',
        isi_template: '',
        aktif: true,
    });

    const editPesan = (template: any) => {
        setDataPesan({
            id: template.id,
            nama_template: template.nama_template,
            isi_template: template.isi_template,
            aktif: !!template.aktif,
        });
    };

    const submitPesan = (e: React.FormEvent) => {
        e.preventDefault();
        if (dataPesan.id) {
            putPesan(`/settings/templates/pesan/${dataPesan.id}`, {
                onSuccess: () => {
                    toast.success('Template diperbarui');
                    resetPesan();
                }
            });
        } else {
            postPesan('/settings/templates/pesan', {
                onSuccess: () => {
                    toast.success('Template dibuat');
                    resetPesan();
                }
            });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pengaturan Template" />
            
            <div className="max-w-7xl mx-auto px-4 py-8">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-bold text-slate-800">Pengaturan Template Pesan</h1>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div className="md:col-span-1 space-y-4">
                        <h2 className="text-lg font-semibold">Form Template Pesan</h2>
                        <form onSubmit={submitPesan} className="bg-white p-4 rounded-lg shadow space-y-4">
                            <div>
                                <Label>Nama Template</Label>
                                <Input 
                                    value={dataPesan.nama_template} 
                                    onChange={e => setDataPesan('nama_template', e.target.value)} 
                                    required
                                />
                            </div>
                            <div>
                                <Label>Isi Pesan</Label>
                                <textarea 
                                    value={dataPesan.isi_template} 
                                    onChange={e => setDataPesan('isi_template', e.target.value)} 
                                    required
                                    className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 h-32"
                                />
                                <p className="text-xs text-slate-500 mt-1">
                                    Placeholder: <code>{`{nama}`}</code>, <code>{`{noka}`}</code>, <code>{`{periode}`}</code>, <code>{`{nominal}`}</code>
                                </p>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Checkbox 
                                    id="aktif_pesan" 
                                    checked={dataPesan.aktif}
                                    onCheckedChange={(checked) => setDataPesan('aktif', checked === true)}
                                />
                                <Label htmlFor="aktif_pesan">Aktif</Label>
                            </div>
                            <div className="flex gap-2">
                                <Button type="submit" disabled={processingPesan}>
                                    {dataPesan.id ? 'Perbarui' : 'Simpan Baru'}
                                </Button>
                                {dataPesan.id && (
                                    <Button type="button" variant="outline" onClick={() => resetPesan()}>Batal</Button>
                                )}
                            </div>
                        </form>
                    </div>
                    <div className="md:col-span-2">
                        <h2 className="text-lg font-semibold mb-4">Daftar Template Pesan</h2>
                        <div className="bg-white rounded-lg shadow overflow-hidden">
                            <table className="w-full text-sm text-left">
                                <thead className="bg-slate-50 text-slate-700">
                                    <tr>
                                        <th className="px-4 py-3">Nama Template</th>
                                        <th className="px-4 py-3">Isi (Preview)</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {templatePesans.map((t) => (
                                        <tr key={t.id} className="border-t">
                                            <td className="px-4 py-3 font-medium">{t.nama_template}</td>
                                            <td className="px-4 py-3 truncate max-w-xs">{t.isi_template}</td>
                                            <td className="px-4 py-3">
                                                <span className={`px-2 py-1 rounded text-xs ${t.aktif ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800'}`}>
                                                    {t.aktif ? 'Aktif' : 'Nonaktif'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Button variant="ghost" size="sm" onClick={() => editPesan(t)}>Edit</Button>
                                            </td>
                                        </tr>
                                    ))}
                                    {templatePesans.length === 0 && (
                                        <tr>
                                            <td colSpan={4} className="px-4 py-8 text-center text-slate-500">Belum ada template</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
