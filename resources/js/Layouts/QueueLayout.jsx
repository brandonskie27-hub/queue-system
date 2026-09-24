import BrandHeader from '@/Components/BrandHeader';
import { Link } from '@inertiajs/react';

export default function QueueLayout({ title, children }) {
    return (
        <div className="min-h-[100dvh] bg-brand-50">
            <BrandHeader subtitle={title}>
                <Link
                    href={route('tickets.index')}
                    className="shrink-0 text-xs text-brand-100 underline hover:text-white"
                >
                    All offices
                </Link>
            </BrandHeader>

            <main className="mx-auto max-w-md space-y-4 px-4 py-6">
                {children}
            </main>
        </div>
    );
}
