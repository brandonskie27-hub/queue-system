import ApplicationLogo from '@/Components/ApplicationLogo';
import { branding } from '@/branding';
import { Head, Link } from '@inertiajs/react';

const linkButton =
    'block rounded-lg px-6 py-4 text-center text-lg font-semibold transition duration-200 ease-out active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2';

export default function Welcome({ auth }) {
    return (
        <div className="flex min-h-[100dvh] flex-col bg-brand-50">
            <Head title="Welcome" />

            <header className="border-b-4 border-gold-500 bg-brand-800 px-4 pb-10 pt-12 text-center text-white">
                <ApplicationLogo className="mx-auto h-28 w-28 rounded-full bg-white p-1 shadow-lg shadow-brand-950/30" />
                <h1 className="mx-auto mt-5 max-w-xs text-balance font-display text-3xl font-bold leading-tight tracking-tight">
                    {branding.schoolName}
                </h1>
                <p className="mt-2 text-gold-200">Online queue</p>
            </header>

            <main className="mx-auto w-full max-w-md flex-1 px-4 pb-8 pt-10">
                <p className="text-pretty text-center leading-relaxed text-stone-700">
                    Take a number from your phone. We'll let you know when it's your
                    turn.
                </p>

                <div className="mt-8 space-y-3">
                    <Link
                        href={route('tickets.index')}
                        className={`${linkButton} bg-brand-700 text-white shadow-md shadow-brand-900/15 hover:bg-brand-600`}
                    >
                        Get a ticket
                    </Link>

                    <Link
                        href={route('display')}
                        className={`${linkButton} bg-white text-brand-800 shadow-sm shadow-brand-900/5 ring-1 ring-brand-200 hover:bg-brand-100`}
                    >
                        Now serving screen
                    </Link>
                </div>

                <p className="mt-8 text-center text-sm font-medium text-gold-700">
                    {branding.reminder} when you are called.
                </p>
            </main>

            <footer className="pb-10 pt-2 text-center text-sm text-stone-500">
                <Link
                    href={auth.user ? route('dashboard') : route('login')}
                    className="rounded underline underline-offset-4 transition hover:text-brand-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                >
                    {auth.user ? 'Go to your dashboard' : 'Staff login'}
                </Link>
            </footer>
        </div>
    );
}
