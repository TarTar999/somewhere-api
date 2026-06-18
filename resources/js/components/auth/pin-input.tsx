import * as React from 'react';
import { REGEXP_ONLY_DIGITS, OTPInputContext } from 'input-otp';
import { InputOTP, InputOTPGroup } from '@/components/ui/input-otp';
import { cn } from '@/lib/utils';

interface PinInputProps {
    value: string;
    onChange: (value: string) => void;
    disabled?: boolean;
    error?: string;
    autoFocus?: boolean;
    className?: string;
}

// Custom slot that hides the PIN digits
const PinSlot = React.forwardRef<
    React.ElementRef<'div'>,
    React.ComponentPropsWithoutRef<'div'> & { index: number; hasError?: boolean }
>(({ index, className, hasError, ...props }, ref) => {
    const inputOTPContext = React.useContext(OTPInputContext);
    const { char, hasFakeCaret, isActive } = inputOTPContext.slots[index];

    return (
        <div
            ref={ref}
            className={cn(
                'relative flex h-14 w-12 items-center justify-center rounded-xl border-2 text-xl font-semibold transition-all',
                'border-gray-200 bg-gray-50/50',
                isActive && 'z-10 bg-white border-indigo-500 ring-2 ring-indigo-500/20',
                hasError && 'border-red-500',
                hasError && isActive && 'border-red-500 ring-red-500/20',
                className
            )}
            {...props}
        >
            {/* Show dot instead of actual character */}
            {char && (
                <span className="text-gray-900">●</span>
            )}
            {hasFakeCaret && (
                <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <div className="h-6 w-0.5 animate-caret-blink bg-indigo-500 duration-1000" />
                </div>
            )}
        </div>
    );
});
PinSlot.displayName = 'PinSlot';

export function PinInput({
    value,
    onChange,
    disabled = false,
    error,
    autoFocus = true,
    className,
}: PinInputProps) {
    return (
        <div className={cn('space-y-2', className)}>
            <InputOTP
                maxLength={6}
                value={value}
                onChange={onChange}
                disabled={disabled}
                pattern={REGEXP_ONLY_DIGITS}
                autoFocus={autoFocus}
                containerClassName="justify-center gap-3"
                textAlign="center"
            >
                <InputOTPGroup className="gap-2">
                    {[0, 1, 2, 3, 4, 5].map((index) => (
                        <PinSlot
                            key={index}
                            index={index}
                            hasError={!!error}
                        />
                    ))}
                </InputOTPGroup>
            </InputOTP>
            {error && (
                <p className="text-sm text-red-500 text-center">{error}</p>
            )}
        </div>
    );
}

export default PinInput;
