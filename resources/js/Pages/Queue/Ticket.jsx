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
                    <p className="text-stone-700">
                        {peopleAhead === 0
                            ? "You're next"
                            : peopleAhead === 1
                              ? '1 person ahead of you'
                              : `${peopleAhead} people ahead of you`}
                    </p>
                    {peopleAhead <= ALMOST_UP_AT && (
                        <p className="mt-3 rounded-lg bg-gold-100 px-3 py-2 font-semibold text-gold-800">
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
                    <p className="mt-2 text-sm font-medium text-stone-600">
                        {branding.reminder}.
                    </p>
                </>
            );
        case 'done':
            return <p className="text-stone-700">You've been served. Thank you.</p>;
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
                        ? `Your turn: ${ticket.code}`
                        : `Ticket ${ticket.code}`
                }
            />

            <section
                className={`rounded-xl p-8 text-center shadow-sm shadow-brand-900/5 transition-colors duration-300 ${
                    ticket.status === 'serving'
                        ? 'bg-brand-50 ring-2 ring-brand-600'
                        : 'bg-white'
                }`}
            >
                <p className="text-sm font-medium text-stone-500">Your number</p>
                <p className="my-2 font-display text-7xl font-bold tabular-nums tracking-tight text-brand-900">
                    {ticket.code}
                </p>
                <StatusMessage ticket={ticket} peopleAhead={peopleAhead} />
            </section>

            <section className="rounded-xl bg-white p-5 shadow-sm shadow-brand-900/5">
                <h2 className="font-display text-lg font-semibold tracking-tight text-stone-900">
                    Now serving
                </h2>
                {nowServing.length === 0 ? (
                    <p className="mt-2 text-stone-600">No one has been called yet.</p>
                ) : (
                    <ul className="mt-2 divide-y divide-stone-100">
                        {nowServing.map((serving) => (
                            <li
                                key={serving.code}
                                className="flex items-baseline justify-between py-2.5"
                            >
                                <span className="font-display text-lg font-semibold tabular-nums text-stone-900">
                                    {serving.code}
                                </span>
                                <span className="text-stone-600">
                                    {serving.counter}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            {!isActive && (
                <Link
                    href={route('tickets.index')}
                    className="block rounded-lg bg-brand-700 px-6 py-3.5 text-center font-semibold text-white shadow-sm shadow-brand-900/15 transition duration-200 ease-out hover:bg-brand-600 active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                >
                    Take another ticket
                </Link>
            )}
        </QueueLayout>
    );
}
