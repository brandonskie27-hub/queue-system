import { branding } from '@/branding';
import QueueLayout from '@/Layouts/QueueLayout';
import { playChime, vibrate } from '@/utils/chime';
import { Head, Link, router, usePoll } from '@inertiajs/react';
import { useEchoPublic } from '@laravel/echo-react';
import { useEffect, useRef } from 'react';

// Warn the student when this few people (or fewer) are ahead of them.
const ALMOST_UP_AT = 2;

function StatusMessage({ ticket, peopleAhead }) {
    switch (ticket.status) {
        case 'waiting':
            return (
                <>
                    <p className="text-gray-700">
                        {peopleAhead === 0
                            ? "You're next!"
                            : peopleAhead === 1
                              ? '1 person ahead of you'
                              : `${peopleAhead} people ahead of you`}
                    </p>
                    {peopleAhead <= ALMOST_UP_AT && (
                        <p className="mt-3 rounded-md bg-gold-100 px-3 py-2 font-semibold text-gold-800">
                            Almost your turn. Please stay nearby.
                        </p>
                    )}
                </>
            );
        case 'serving':
            return (
                <>
                    <p className="text-xl font-semibold text-brand-700">
                        Please proceed to {ticket.counter}
                    </p>
                    <p className="mt-2 text-sm font-medium text-gray-600">
                        {branding.reminder}.
                    </p>
                </>
            );
        case 'done':
            return <p className="text-gray-700">You've been served. Thank you!</p>;
        case 'skipped':
            return (
                <p className="text-gold-700">
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

    // Chime and vibrate when the student is called, and once when they're almost up.
    // Compared with the previous values so it only fires on the change, not on every reload.
    const previous = useRef({ status: ticket.status, peopleAhead });

    useEffect(() => {
        const before = previous.current;

        if (ticket.status === 'serving' && before.status !== 'serving') {
            playChime();
            vibrate([400, 200, 400]);
        } else if (
            ticket.status === 'waiting' &&
            peopleAhead <= ALMOST_UP_AT &&
            before.peopleAhead > ALMOST_UP_AT
        ) {
            playChime();
            vibrate(300);
        }

        previous.current = { status: ticket.status, peopleAhead };
    }, [ticket.status, peopleAhead]);

    const isActive = ticket.status === 'waiting' || ticket.status === 'serving';

    return (
        <QueueLayout title={ticket.service}>
            <Head
                title={
                    ticket.status === 'serving'
                        ? `Your turn! ${ticket.code}`
                        : `Ticket ${ticket.code}`
                }
            />

            <div
                className={`rounded-lg p-8 text-center shadow-sm ${
                    ticket.status === 'serving'
                        ? 'bg-brand-50 ring-2 ring-brand-600'
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
