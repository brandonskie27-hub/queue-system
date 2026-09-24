import { Head, Link } from '@inertiajs/react';

export default function Welcome({ auth }) {
    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-gray-100 px-4 py-12">
            <Head title="Welcome" />

            <div className="w-full max-w-md text-center">
                <h1 className="text-3xl font-bold text-gray-900">Queue System</h1>
                <p className="mt-2 text-gray-600">
                    Take a number from your phone and we'll let you know when it's your
                    turn.
                </p>

                <div className="mt-10 space-y-4">
                    <Link
                        href={route('tickets.index')}
                        className="block rounded-lg bg-gray-800 px-6 py-5 text-lg font-semibold text-white shadow-sm transition hover:bg-gray-700"
                    >
                        Get a ticket
                    </Link>

                    <Link
                        href={route('display')}
                        className="block rounded-lg bg-white px-6 py-5 text-lg font-semibold text-gray-800 shadow-sm transition hover:bg-gray-50"
                    >
                        Now Serving screen
                    </Link>
                </div>

                <p className="mt-10 text-sm text-gray-500">
                    {auth.user ? (
                        <Link
                            href={route('dashboard')}
                            className="underline hover:text-gray-800"
                        >
                            Go to your dashboard
                        </Link>
                    ) : (
                        <Link
                            href={route('login')}
                            className="underline hover:text-gray-800"
                        >
                            Staff login
                        </Link>
                    )}
                </p>
            </div>
        </div>
    );
}
