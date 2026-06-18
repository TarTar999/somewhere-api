import { useState } from 'react';
import axios from 'axios';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { PinInput } from './pin-input';
import { Spinner } from '@/components/ui/spinner';
import { Lock, ArrowLeft } from 'lucide-react';

interface PinSetupModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

type Step = 'enter' | 'confirm';

export function PinSetupModal({ open, onOpenChange }: PinSetupModalProps) {
    const [pin, setPin] = useState('');
    const [confirmPin, setConfirmPin] = useState('');
    const [step, setStep] = useState<Step>('enter');
    const [error, setError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handlePinChange = (value: string) => {
        setError('');
        if (step === 'enter') {
            setPin(value);
            // Auto-advance to confirm step when PIN is complete
            if (value.length === 6) {
                setTimeout(() => setStep('confirm'), 300);
            }
        } else {
            setConfirmPin(value);
        }
    };

    const handleSubmit = async () => {
        if (step === 'enter') {
            if (pin.length !== 6) {
                setError('Le code PIN doit contenir 6 chiffres');
                return;
            }
            setStep('confirm');
            return;
        }

        // Confirm step
        if (confirmPin !== pin) {
            setError('Les codes PIN ne correspondent pas');
            setConfirmPin('');
            return;
        }

        setIsSubmitting(true);
        setError('');

        try {
            await axios.post('/auth/pin-code', {
                pin_code: pin,
                pin_code_confirmation: confirmPin,
            });
            onOpenChange(false);
            resetState();
        } catch (err: any) {
            const errors = err.response?.data?.errors;
            setError(errors?.pin_code?.[0] || err.response?.data?.message || 'Une erreur est survenue');
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleSkip = async () => {
        try {
            await axios.post('/auth/pin-code/skip');
            onOpenChange(false);
            resetState();
        } catch {
            // Ignore errors on skip, just close the modal
            onOpenChange(false);
            resetState();
        }
    };

    const handleBack = () => {
        setStep('enter');
        setConfirmPin('');
        setError('');
    };

    const resetState = () => {
        setPin('');
        setConfirmPin('');
        setStep('enter');
        setError('');
        setIsSubmitting(false);
    };

    const handleOpenChange = (newOpen: boolean) => {
        if (!newOpen) {
            resetState();
        }
        onOpenChange(newOpen);
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader className="text-center">
                    <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-500">
                        <Lock className="h-7 w-7 text-white" />
                    </div>
                    <DialogTitle className="text-xl">
                        {step === 'enter' ? 'Créer un code PIN' : 'Confirmer votre code PIN'}
                    </DialogTitle>
                    <DialogDescription className="text-center">
                        {step === 'enter'
                            ? 'Créez un code PIN à 6 chiffres pour une connexion plus rapide'
                            : 'Saisissez à nouveau votre code PIN pour confirmer'
                        }
                    </DialogDescription>
                </DialogHeader>

                <div className="py-6">
                    <PinInput
                        value={step === 'enter' ? pin : confirmPin}
                        onChange={handlePinChange}
                        disabled={isSubmitting}
                        error={error}
                        autoFocus
                    />
                </div>

                <DialogFooter className="flex-col sm:flex-row gap-2">
                    {step === 'confirm' && (
                        <Button
                            variant="ghost"
                            onClick={handleBack}
                            disabled={isSubmitting}
                            className="sm:mr-auto"
                        >
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Retour
                        </Button>
                    )}
                    {step === 'enter' && (
                        <Button
                            variant="ghost"
                            onClick={handleSkip}
                            disabled={isSubmitting}
                            className="text-gray-500 hover:text-gray-700"
                        >
                            Plus tard
                        </Button>
                    )}
                    <Button
                        onClick={handleSubmit}
                        disabled={isSubmitting || (step === 'enter' ? pin.length !== 6 : confirmPin.length !== 6)}
                        className="bg-gradient-to-r from-indigo-500 to-violet-500 hover:from-indigo-600 hover:to-violet-600"
                    >
                        {isSubmitting && <Spinner className="mr-2" />}
                        {step === 'enter' ? 'Continuer' : 'Confirmer'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default PinSetupModal;
