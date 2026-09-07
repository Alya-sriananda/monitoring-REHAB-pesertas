import { Head, useForm } from '@inertiajs/react';
import { useRef, FormEventHandler } from 'react';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/security';
import { CheckCircle2, Circle } from 'lucide-react';

type Props = {
    passwordRules: string;
};

export default function Security(props: Props) {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    const { data, setData, put, errors, processing, reset } = useForm({
        current_password: '',
        password: '',
    });

    const password = data.password || '';
    const requirements = [
        { label: 'Minimal 8 karakter', met: password.length >= 8 },
        { label: 'Satu huruf besar', met: /[A-Z]/.test(password) },
        { label: 'Satu angka', met: /[0-9]/.test(password) },
        { label: 'Satu simbol', met: /[^A-Za-z0-9]/.test(password) },
    ];

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        put(SecurityController.update.url(), {
            preserveScroll: true,
            onSuccess: () => reset('password', 'current_password'),
            onError: (errors) => {
                if (errors.password) {
                    passwordInput.current?.focus();
                }

                if (errors.current_password) {
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    return (
        <>
            <Head title="Profil" />

            <h1 className="sr-only">Profil</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Ubah Kata Sandi"
                    description="Gunakan kata sandi yang kuat untuk menjaga keamanan akun Anda."
                />

                <form onSubmit={submit} className="space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="current_password">
                            Kata Sandi Saat Ini
                        </Label>

                        <PasswordInput
                            id="current_password"
                            ref={currentPasswordInput}
                            name="current_password"
                            value={data.current_password}
                            onChange={(e) =>
                                setData('current_password', e.target.value)
                            }
                            className="mt-1 block w-full"
                            autoComplete="current-password"
                            placeholder="Kata sandi saat ini"
                        />

                        <InputError message={errors.current_password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">Kata Sandi Baru</Label>

                        <PasswordInput
                            id="password"
                            ref={passwordInput}
                            name="password"
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            className="mt-1 block w-full"
                            autoComplete="new-password"
                            placeholder="Kata sandi baru"
                            passwordrules={props.passwordRules}
                        />

                        <InputError message={errors.password} />

                        <div className="mt-2 space-y-2">
                            {requirements.map((req, i) => (
                                <div
                                    key={i}
                                    className="text-muted-foreground flex items-center space-x-2 text-sm"
                                >
                                    {req.met ? (
                                        <CheckCircle2 className="h-4 w-4 text-green-500" />
                                    ) : (
                                        <Circle className="h-4 w-4" />
                                    )}
                                    <span
                                        className={
                                            req.met
                                                ? 'font-medium text-green-600'
                                                : ''
                                        }
                                    >
                                        {req.label}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="flex items-center gap-4">
                        <Button
                            disabled={processing}
                            data-test="update-password-button"
                        >
                            Simpan
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

Security.layout = {
    breadcrumbs: [
        {
            title: 'Profil',
            href: edit(),
        },
    ],
};
