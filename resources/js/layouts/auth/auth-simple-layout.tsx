import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';
import { Link } from '@inertiajs/react';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="min-h-svh flex items-center justify-center bg-[#F8FAFF] p-4 md:p-8">
            {/* Subtle background pattern */}
            <div className="absolute inset-0 bg-[radial-gradient(#e0e7ff_1px,transparent_1px)] [background-size:20px_20px] opacity-40" />

            <div className="relative w-full max-w-md">
                {/* Card */}
                <div className="bg-white rounded-2xl shadow-xl shadow-indigo-500/5 border border-gray-100 p-8 md:p-10">
                    {/* Logo & Header */}
                    <div className="flex flex-col items-center mb-8">
                        <Link
                            href={home()}
                            className="flex items-center gap-3 mb-6 group"
                        >
                            <AppLogoIcon className="h-12 w-12" />
                            <span className="text-xl font-bold text-gray-900">SomeWhere App</span>
                        </Link>

                        <div className="text-center">
                            <h1 className="text-2xl font-bold text-gray-900 mb-2">{title}</h1>
                            <p className="text-gray-500 text-sm">
                                {description}
                            </p>
                        </div>
                    </div>

                    {/* Form Content */}
                    {children}
                </div>

                {/* Footer */}
                <p className="text-center text-xs text-gray-400 mt-6">
                    © {new Date().getFullYear()} SomeWhere App. Tous droits réservés.
                </p>
            </div>
        </div>
    );
}
