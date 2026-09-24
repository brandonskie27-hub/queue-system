import PrimaryButton from '@/Components/PrimaryButton';
import QueueLayout from '@/Layouts/QueueLayout';
import { Form, Head, Link } from '@inertiajs/react';

export default function Index({ services }) {
    return (
        <QueueLayout title="Get a ticket">
            <Head title="Get a ticket" />

            <h1 className="px-1 font-display text-2xl font-semibold tracking-tight text-brand-900">
                Choose an office
            </h1>

            {services.length === 0 && (
                <p className="rounded-xl bg-white p-6 text-center text-stone-600 shadow-sm shadow-brand-900/5">
                    No offices are open right now. Please check back later.
                </p>
            )}

            {services.map((service) => (
                <section
                    key={service.id}
                    className="flex items-center justify-between gap-4 rounded-xl bg-white p-5 shadow-sm shadow-brand-900/5"
                >
                    <div className="min-w-0">
                        <h2 className="font-display text-xl font-semibold tracking-tight text-stone-900">
                            {service.name}
                        </h2>
                        <p className="mt-0.5 text-sm text-stone-500">
                            {service.waitingCount === 0
                                ? 'No one waiting'
                                : service.waitingCount === 1
                                  ? '1 person waiting'
                                  : `${service.waitingCount} people waiting`}
                        </p>
                    </div>

                    {service.heldTicket ? (
                        <Link
                            href={route('tickets.show', service.heldTicket.id)}
                            className="shrink-0 rounded-lg bg-brand-100 px-4 py-2.5 text-sm font-semibold text-brand-800 transition duration-200 ease-out hover:bg-brand-200 active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                        >
                            View{' '}
                            <span className="font-display tabular-nums">
                                {service.heldTicket.code}
                            </span>
                        </Link>
                    ) : (
                        <Form
                            action={route('tickets.store', service.id)}
                            method="post"
                            className="shrink-0"
                        >
                            {({ processing }) => (
                                <PrimaryButton disabled={processing}>
                                    {processing ? 'Getting ticket' : 'Get ticket'}
                                </PrimaryButton>
                            )}
                        </Form>
                    )}
                </section>
            ))}
        </QueueLayout>
    );
}
