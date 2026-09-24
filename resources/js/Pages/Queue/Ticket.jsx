import QueueLayout from '@/Layouts/QueueLayout';
import { Head, Link, router, usePoll } from '@inertiajs/react';
import { useEchoPublic } from '@laravel/echo-react';

function StatusMessage({ ticket, peopleAhead }) {
    switch (ticket.status) {
        case 'waiting':
            return (
                <p className="text-gray-700">
                    {peopleAhead === 0
                        ? "You're next!"
                        : peopleAhead === 1
                          ? '1 person ahead of you'
                          : `${peopleAhead} people ahead of you`}
                </p>
            );
        case 'serving':
            return (
                <p className="text-xl font-semibold text-green-700">
                    Please proceed to {ticket.counter}
                </p>
            );
        case 'done':
            return <p className="text-gray-700">You've been served. Thank you!</p>;
        case 'skipped':
            return (
                <p className="text-amber-700">
                    Your number was skipped. You can take a new ticket.
                </p>
            );
        default:
            return null;
    }
}

export default function Ticket({ ticket, peopleAhead, nowServing }) {
    // Reload the page data whenever this service's queue changes.
    useEchoPublic(
        `queue.${ticket.serviceId}`,
        ['.ticket.called', '.queue.updated'],
        () => router.reload(),
    );

    // Safety net in case the WebSocket connection drops.
    usePoll(30000);

    const isActive = ticket.status === 'waiting' || ticket.status === 'serving';

    return (
        <QueueLayout title={ticket.service}>
            <Head title={`Ticket ${ticket.code}`} />

            <div
                className={`rounded-lg p-8 text-center shadow-sm ${
                    ticket.status === 'serving'
                        ? 'bg-green-50 ring-2 ring-green-500'
                        : 'bg-white'
                }`}
            >
                <p className="text-sm uppercase tracking-widest text-gray-500">
                    Your number
                </p>
                <p className="my-3 text-6xl font-bold tabular-nums text-gray-900">
                    {ticket.code}
                </p>
                <StatusMessage ticket={ticket} peopleAhead={peopleAhead} />
            </div>

            <div className="rounded-lg bg-white p-5 shadow-sm">
                <h2 className="text-sm font-semibold uppercase tracking-widest text-gray-500">
                    Now serving
                </h2>
                {nowServing.length === 0 ? (
                    <p className="mt-2 text-gray-600">No one yet.</p>
                ) : (
                    <ul className="mt-2 divide-y divide-gray-100">
                        {nowServing.map((serving) => (
                            <li
                                key={serving.code}
                                className="flex justify-between py-2"
                            >
                                <span className="font-semibold tabular-nums text-gray-900">
                                    {serving.code}
                                </span>
                                <span className="text-gray-600">
                                    {serving.counter}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            {!isActive && (
                <Link
                    href={route('tickets.index')}
                    className="block text-center text-sm text-gray-600 underline hover:text-gray-900"
                >
                    Take another ticket
                </Link>
            )}
        </QueueLayout>
    );
}
