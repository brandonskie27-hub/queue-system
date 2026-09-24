import DangerButton from '@/Components/DangerButton';
import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Form, Head, Link, usePoll } from '@inertiajs/react';

function minutesSince(timestamp) {
    const minutes = Math.floor((Date.now() - new Date(timestamp)) / 60000);

    return minutes < 1 ? 'just now' : `${minutes} min`;
}

export default function Dashboard({ counter, service, current, waiting }) {
    // Pick up newly taken tickets. Replaced by live WebSocket updates (Reverb) later.
    usePoll(3000);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        {service.name} &middot; {counter.name}
                    </h2>
                    <Link
                        href={route('counter.edit')}
                        className="text-sm text-gray-600 underline hover:text-gray-900"
                    >
                        Change counter
                    </Link>
                </div>
            }
        >
            <Head title={`${counter.name} - ${service.name}`} />

            <div className="py-12">
                <div className="mx-auto grid max-w-5xl gap-6 sm:px-6 md:grid-cols-2 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="text-sm font-semibold uppercase tracking-widest text-gray-500">
                            Now serving
                        </h3>

                        {current ? (
                            <>
                                <p className="my-6 text-center text-6xl font-bold tabular-nums text-gray-900">
                                    {current.code}
                                </p>
                                <div className="flex justify-center gap-3">
                                    <Form
                                        action={route(
                                            'dashboard.tickets.done',
                                            current.id,
                                        )}
                                        method="post"
                                    >
                                        {({ processing }) => (
                                            <PrimaryButton disabled={processing}>
                                                Done
                                            </PrimaryButton>
                                        )}
                                    </Form>
                                    <Form
                                        action={route(
                                            'dashboard.tickets.skip',
                                            current.id,
                                        )}
                                        method="post"
                                    >
                                        {({ processing }) => (
                                            <DangerButton disabled={processing}>
                                                Skip (no-show)
                                            </DangerButton>
                                        )}
                                    </Form>
                                </div>
                            </>
                        ) : (
                            <div className="my-6 text-center">
                                <p className="mb-6 text-gray-600">
                                    {waiting.length > 0
                                        ? 'Ready for the next student.'
                                        : 'No one is waiting.'}
                                </p>
                                <Form
                                    action={route('dashboard.call-next')}
                                    method="post"
                                >
                                    {({ processing }) => (
                                        <PrimaryButton
                                            disabled={
                                                processing ||
                                                waiting.length === 0
                                            }
                                            className="px-8 py-4 text-base"
                                        >
                                            Call Next
                                        </PrimaryButton>
                                    )}
                                </Form>
                            </div>
                        )}
                    </div>

                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="text-sm font-semibold uppercase tracking-widest text-gray-500">
                            Waiting ({waiting.length})
                        </h3>

                        {waiting.length === 0 ? (
                            <p className="mt-4 text-gray-600">
                                The queue is empty.
                            </p>
                        ) : (
                            <ul className="mt-4 divide-y divide-gray-100">
                                {waiting.map((ticket, index) => (
                                    <li
                                        key={ticket.id}
                                        className="flex justify-between py-2"
                                    >
                                        <span
                                            className={`font-semibold tabular-nums ${
                                                index === 0
                                                    ? 'text-indigo-700'
                                                    : 'text-gray-900'
                                            }`}
                                        >
                                            {ticket.code}
                                        </span>
                                        <span className="text-sm text-gray-500">
                                            waiting{' '}
                                            {minutesSince(ticket.takenAt)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
