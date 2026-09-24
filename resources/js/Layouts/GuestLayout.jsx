import ApplicationLogo from '@/Components/ApplicationLogo';
import { branding } from '@/branding';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="flex min-h-screen flex-col items-center bg-brand-50 pt-6 sm:justify-center sm:pt-0">
            <Link href="/" className="flex flex-col items-center text-center">
                <ApplicationLogo className="h-24 w-24" />
                <span className="mt-3 text-lg font-bold text-brand-800">
                    {branding.schoolName}
                </span>
                <span className="text-sm text-gray-500">Staff sign-in</span>
            </Link>

            <div className="mt-6 w-full overflow-hidden border-t-4 border-brand-700 bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg">
                {children}
            </div>
        </div>
    );
}
