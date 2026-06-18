import { useState } from 'react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { PinInput } from '@/components/auth/pin-input';
import AuthLayout from '@/layouts/auth-layout';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Head, router } from '@inertiajs/react';
import { Phone, Lock, ArrowLeft } from 'lucide-react';
import axios from 'axios';

type Props = {
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
};

type AuthMethods = {
    password: boolean;
    pin_code: boolean;
};

type Step = 'phone' | 'credentials';

export default function Login({
    status,
    canResetPassword,
    canRegister,
}: Props) {
    const [step, setStep] = useState<Step>('phone');
    const [phone, setPhone] = useState('');
    const [password, setPassword] = useState('');
    const [pinCode, setPinCode] = useState('');
    const [remember, setRemember] = useState(false);
    const [authMethods, setAuthMethods] = useState<AuthMethods | null>(null);
    const [isCheckingMethods, setIsCheckingMethods] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const handlePhoneSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setErrors({});

        if (!phone.trim()) {
            setErrors({ phone: 'Le numéro de téléphone est requis' });
            return;
        }

        setIsCheckingMethods(true);

        try {
            const response = await axios.post('/auth/check-methods', { phone });
            const data = response.data;

            if (!data.exists) {
                setErrors({ phone: 'Aucun compte trouvé avec ce numéro' });
                return;
            }

            setAuthMethods(data.methods);
            setStep('credentials');
        } catch (error: any) {
            if (error.response?.status === 429) {
                setErrors({ phone: 'Trop de tentatives. Veuillez réessayer plus tard.' });
            } else {
                setErrors({ phone: 'Une erreur est survenue. Veuillez réessayer.' });
            }
        } finally {
            setIsCheckingMethods(false);
        }
    };

    const handleCredentialsSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setErrors({});
        setIsSubmitting(true);

        const data: Record<string, any> = {
            phone,
            remember,
        };

        // Determine which credential to send
        if (authMethods?.pin_code && pinCode) {
            data.pin_code = pinCode;
        } else if (authMethods?.password && password) {
            data.password = password;
        } else {
            setErrors({ credentials: 'Veuillez entrer vos identifiants' });
            setIsSubmitting(false);
            return;
        }

        router.post(store(), data, {
            onError: (errors) => {
                setErrors(errors);
                setIsSubmitting(false);
            },
            onFinish: () => {
                setIsSubmitting(false);
            },
        });
    };

    const handleBack = () => {
        setStep('phone');
        setPassword('');
        setPinCode('');
        setAuthMethods(null);
        setErrors({});
    };

    const renderPhoneStep = () => (
        <form onSubmit={handlePhoneSubmit} className="flex flex-col gap-5">
            <div className="space-y-2">
                <Label htmlFor="phone" className="text-gray-700 font-medium">
                    Numéro de téléphone
                </Label>
                <div className="relative">
                    <Phone className="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                    <Input
                        id="phone"
                        type="tel"
                        value={phone}
                        onChange={(e) => setPhone(e.target.value)}
                        required
                        autoFocus
                        tabIndex={1}
                        autoComplete="tel"
                        placeholder="6XXXXXXXX"
                        className="pl-12 h-12 rounded-xl border-gray-200 bg-gray-50/50 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                    />
                </div>
                <InputError message={errors.phone} />
            </div>

            <Button
                type="submit"
                className="w-full h-12 rounded-xl bg-gradient-to-r from-indigo-500 to-violet-500 hover:from-indigo-600 hover:to-violet-600 text-white font-semibold shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/35 transition-all"
                tabIndex={2}
                disabled={isCheckingMethods}
            >
                {isCheckingMethods && <Spinner className="mr-2" />}
                Suivant
            </Button>

            {canRegister && (
                <div className="pt-4 text-center border-t border-gray-100">
                    <p className="text-sm text-gray-500">
                        Pas encore de compte ?{' '}
                        <TextLink
                            href={register()}
                            tabIndex={3}
                            className="font-semibold text-indigo-600 hover:text-indigo-700"
                        >
                            Créer un compte
                        </TextLink>
                    </p>
                </div>
            )}
        </form>
    );

    const renderCredentialsStep = () => (
        <form onSubmit={handleCredentialsSubmit} className="flex flex-col gap-5">
            {/* Phone display with back button */}
            <div className="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                <button
                    type="button"
                    onClick={handleBack}
                    className="p-2 rounded-lg hover:bg-gray-200 transition-colors"
                >
                    <ArrowLeft className="h-5 w-5 text-gray-600" />
                </button>
                <div className="flex-1">
                    <p className="text-xs text-gray-500">Numéro de téléphone</p>
                    <p className="font-medium text-gray-900">{phone}</p>
                </div>
            </div>

            {/* PIN Code Input */}
            {authMethods?.pin_code && (
                <div className="space-y-3">
                    <Label className="text-gray-700 font-medium text-center block">
                        Entrez votre code PIN
                    </Label>
                    <PinInput
                        value={pinCode}
                        onChange={setPinCode}
                        disabled={isSubmitting}
                        error={errors.pin_code}
                    />
                </div>
            )}

            {/* Password Input (if user has password and no PIN, or if PIN not yet set) */}
            {authMethods?.password && !authMethods?.pin_code && (
                <div className="space-y-2">
                    <div className="flex items-center justify-between">
                        <Label htmlFor="password" className="text-gray-700 font-medium">
                            Mot de passe
                        </Label>
                        {canResetPassword && (
                            <TextLink
                                href={request()}
                                className="text-sm text-indigo-600 hover:text-indigo-700"
                                tabIndex={4}
                            >
                                Mot de passe oublié ?
                            </TextLink>
                        )}
                    </div>
                    <div className="relative">
                        <Lock className="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                        <Input
                            id="password"
                            type="password"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            required
                            autoFocus
                            tabIndex={1}
                            autoComplete="current-password"
                            placeholder="••••••••"
                            className="pl-12 h-12 rounded-xl border-gray-200 bg-gray-50/50 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                        />
                    </div>
                    <InputError message={errors.password} />
                </div>
            )}

            <InputError message={errors.credentials} />

            <div className="flex items-center gap-3">
                <Checkbox
                    id="remember"
                    checked={remember}
                    onCheckedChange={(checked) => setRemember(checked === true)}
                    tabIndex={2}
                    className="rounded-md border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <Label htmlFor="remember" className="text-sm text-gray-600 cursor-pointer">
                    Se souvenir de moi
                </Label>
            </div>

            <Button
                type="submit"
                className="w-full h-12 rounded-xl bg-gradient-to-r from-indigo-500 to-violet-500 hover:from-indigo-600 hover:to-violet-600 text-white font-semibold shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/35 transition-all"
                tabIndex={3}
                disabled={isSubmitting}
                data-test="login-button"
            >
                {isSubmitting && <Spinner className="mr-2" />}
                Se connecter
            </Button>
        </form>
    );

    return (
        <AuthLayout
            title="Bon retour !"
            description={step === 'phone'
                ? "Connectez-vous pour accéder à votre espace"
                : "Entrez vos identifiants pour continuer"
            }
        >
            <Head title="Connexion" />

            {status && (
                <div className="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-center text-sm font-medium text-green-700">
                    {status}
                </div>
            )}

            {step === 'phone' ? renderPhoneStep() : renderCredentialsStep()}
        </AuthLayout>
    );
}
