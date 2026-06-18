import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { login } from '@/routes';
import { store } from '@/routes/register';
import { Form, Head } from '@inertiajs/react';
import { User, Phone, Lock, CheckCircle } from 'lucide-react';

export default function Register() {
    return (
        <AuthLayout
            title="Créer un compte"
            description="Rejoignez SomeWhere App en quelques étapes"
        >
            <Head title="Inscription" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-5"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="name" className="text-gray-700 font-medium">
                                    Nom complet
                                </Label>
                                <div className="relative">
                                    <User className="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <Input
                                        id="name"
                                        type="text"
                                        required
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="name"
                                        name="name"
                                        placeholder="Jean Dupont"
                                        className="pl-12 h-12 rounded-xl border-gray-200 bg-gray-50/50 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                                    />
                                </div>
                                <InputError message={errors.name} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="phone" className="text-gray-700 font-medium">
                                    Numéro de téléphone
                                </Label>
                                <div className="relative">
                                    <Phone className="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <Input
                                        id="phone"
                                        type="tel"
                                        required
                                        tabIndex={2}
                                        autoComplete="tel"
                                        name="phone"
                                        placeholder="6XXXXXXXX"
                                        className="pl-12 h-12 rounded-xl border-gray-200 bg-gray-50/50 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                                    />
                                </div>
                                <InputError message={errors.phone} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="password" className="text-gray-700 font-medium">
                                    Mot de passe
                                </Label>
                                <div className="relative">
                                    <Lock className="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <Input
                                        id="password"
                                        type="password"
                                        required
                                        tabIndex={3}
                                        autoComplete="new-password"
                                        name="password"
                                        placeholder="••••••••"
                                        className="pl-12 h-12 rounded-xl border-gray-200 bg-gray-50/50 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                                    />
                                </div>
                                <InputError message={errors.password} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="password_confirmation" className="text-gray-700 font-medium">
                                    Confirmer le mot de passe
                                </Label>
                                <div className="relative">
                                    <CheckCircle className="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <Input
                                        id="password_confirmation"
                                        type="password"
                                        required
                                        tabIndex={4}
                                        autoComplete="new-password"
                                        name="password_confirmation"
                                        placeholder="••••••••"
                                        className="pl-12 h-12 rounded-xl border-gray-200 bg-gray-50/50 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                                    />
                                </div>
                                <InputError message={errors.password_confirmation} />
                            </div>

                            <Button
                                type="submit"
                                className="w-full h-12 rounded-xl bg-gradient-to-r from-indigo-500 to-violet-500 hover:from-indigo-600 hover:to-violet-600 text-white font-semibold shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/35 transition-all mt-2"
                                tabIndex={5}
                                data-test="register-user-button"
                            >
                                {processing && <Spinner className="mr-2" />}
                                Créer mon compte
                            </Button>
                        </div>

                        <div className="pt-4 text-center border-t border-gray-100">
                            <p className="text-sm text-gray-500">
                                Déjà un compte ?{' '}
                                <TextLink
                                    href={login()}
                                    tabIndex={6}
                                    className="font-semibold text-indigo-600 hover:text-indigo-700"
                                >
                                    Se connecter
                                </TextLink>
                            </p>
                        </div>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
