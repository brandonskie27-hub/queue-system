import PrimaryButton from '@/Components/PrimaryButton';
import QueueLayout from '@/Layouts/QueueLayout';
import { Form, Head, Link } from '@inertiajs/react';

export default function Index({ services }) {
    return (
        <QueueLayout title="Get a ticket">
            <Head title="Get a ticket" />

            {services.length === 0 && (
                <p className="rounded-lg bg-white p-6 text-center text-gray-600 shadow-sm">
                    No services are open right now.
                </p>
            )}

            {services.map((service) => (
                <div
                    key={service.id}
                    className="flex items-center justify-between rounded-lg bg-white p-5 shadow-sm"
                >
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900">
                            {service.name}
                        </h2>
                        <p className="text-sm text-gray-500">
                            {service.waitingCount === 1
                                ? '1 person waiting'
                                : `${service.waitingCount} people waiting`}
                        </p>
                    </div>

                    {service.heldTicket ? (
                        <Link
                            href={route('tickets.show', service.heldTicket.id)}
                            className="rounded-md bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100"
                        >
                            Your ticket: {service.heldTicket.code}
                        </Link>
                    ) : (
                        <Form
                            action={route('tickets.store', service.id)}
                            method="post"
                        >
                            {({ processing }) => (
                                <PrimaryButton disabled={processing}>
                                    Get Ticket
                                </PrimaryButton>
                            )}
                        </Form>
                    )}
                </div>
            ))}
        </QueueLayout>
    );
}
