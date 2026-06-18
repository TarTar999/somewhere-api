import * as React from 'react';

import { cn } from '@/lib/utils';

export interface TextareaProps extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {}

const Textarea = React.forwardRef<HTMLTextAreaElement, TextareaProps>(({ className, ...props }, ref) => {
    return (
        <textarea
            className={cn(
                'flex min-h-[80px] w-full rounded-xl border border-gray-100 bg-white px-4 py-3 text-sm shadow-sm placeholder:text-gray-400 focus-visible:outline-none focus-visible:border-indigo-500 focus-visible:ring-indigo-500/20 focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 transition-all',
                className
            )}
            ref={ref}
            {...props}
        />
    );
});
Textarea.displayName = 'Textarea';

export { Textarea };
