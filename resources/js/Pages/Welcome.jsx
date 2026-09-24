import ApplicationLogo from '@/Components/ApplicationLogo';
import { branding } from '@/branding';
import { Head, Link } from '@inertiajs/react';

export default function Welcome({ auth }) {
    return (
        <div className="flex min-h-screen flex-col bg-brand-50">
            <Head title="Welcome" />

            <div className="border-b-4 border-gold-500 bg-brand-800 px-4 pb-10 pt-12 text-center text-white">
                <ApplicationLogo className="mx-auto h-28 w-28 rounded-full bg-white p-1 shadow-lg" />
                <h1 className="mt-5 text-2xl font-bold leading-tight">
                    {branding.schoolName}
                </h1>
                <p className="mt-1 text-gold-200">Online queue</p>
            </div>

            <main className="mx-auto w-full max-w-md flex-1 px-4 py-8">
                <p className="text-center text-gray-700">
                    Take a number from your phone and we'll let you know when it's
                    your turn.
                </p>

                <div className="mt-8 space-y-4">
                    <Link
                        href={route('tickets.index')}
                        className="block rounded-lg bg-brand-700 px-6 py-5 text-center text-lg font-semibold text-white shadow-sm transition hover:bg-brand-600"
                    >
                        Get a ticket
                    </Link>

                    <Link
                        href={route('display')}
                        className="block rounded-lg border border-brand-200 bg-white px-6 py-5 text-center text-lg font-semibold text-brand-800 shadow-sm transition hover:bg-brand-100"
                    >
                        Now Serving screen
                    </Link>
                </div>

                <p className="mt-8 text-center text-sm font-medium text-gold-700">
                    {branding.reminder} when you are called.
                </p>
            </main>

            <footer className="pb-8 text-center text-sm text-gray-500">
                {auth.user ? (
                    <Link
                        href={route('dashboard')}
                        className="underline hover:text-brand-800"
                    >
                        Go to your dashboard
                    </Link>
                ) : (
                    <Link
                        href={route('login')}
                        className="underline hover:text-brand-800"
                    >
                        Staff login
                    </Link>
                )}
            </footer>
        </div>
    );
}
