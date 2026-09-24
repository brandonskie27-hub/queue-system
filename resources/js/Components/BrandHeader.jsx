import ApplicationLogo from '@/Components/ApplicationLogo';
import { branding } from '@/branding';
import { Link } from '@inertiajs/react';

// Green school header used on the public pages (home, tickets, display).
// "size" is "sm" for phones and "lg" for the waiting-area screen.
export default function BrandHeader({ subtitle, size = 'sm', children }) {
    const large = size === 'lg';

    return (
        <header className="border-b-4 border-gold-500 bg-brand-800 text-white">
            <div
                className={`mx-auto flex items-center gap-4 ${
                    large ? 'px-10 py-5' : 'max-w-md px-4 py-3'
                }`}
            >
                <Link href="/" className="shrink-0">
                    <ApplicationLogo
                        className={`rounded-full bg-white ${
                            large ? 'h-20 w-20 p-1' : 'h-11 w-11 p-0.5'
                        }`}
                    />
                </Link>
                <div className="min-w-0 flex-1">
                    <p
                        className={`font-bold leading-tight ${
                            large ? 'text-3xl' : 'text-sm'
                        }`}
                    >
                        {branding.schoolName}
                    </p>
                    {subtitle && (
                        <p
                            className={`text-gold-200 ${
                                large ? 'text-xl' : 'truncate text-xs'
                            }`}
                        >
                            {subtitle}
                        </p>
                    )}
                </div>
                {children}
            </div>
        </header>
    );
}
