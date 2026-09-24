import { Link } from '@inertiajs/react';

export default function QueueLayout({ title, children }) {
    return (
        <div className="min-h-screen bg-gray-100">
            <header className="bg-white shadow-sm">
                <div className="mx-auto flex max-w-md items-center justify-between px-4 py-4">
                    <h1 className="text-lg font-semibold text-gray-800">
                        {title}
                    </h1>
                    <Link
                        href={route('tickets.index')}
                        className="text-sm text-gray-600 underline hover:text-gray-900"
                    >
                        All services
                    </Link>
                </div>
            </header>

            <main className="mx-auto max-w-md space-y-4 px-4 py-6">
                {children}
            </main>
        </div>
    );
}
