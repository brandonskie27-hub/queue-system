import { playChime, unlockAudio } from '@/utils/chime';
import { Head, router, usePoll } from '@inertiajs/react';
import { useEchoPublic } from '@laravel/echo-react';
import { useEffect, useRef, useState } from 'react';

// Listens to one service's channel. A component per service because hooks can't run in a loop.
function ServiceListener({ serviceId, onCalled }) {
    useEchoPublic(`queue.${serviceId}`, '.ticket.called', onCalled);
    useEchoPublic(`queue.${serviceId}`, '.queue.updated', () =>
        router.reload(),
    );

    return null;
}

function Clock() {
    const [now, setNow] = useState(new Date());

    useEffect(() => {
        const timer = setInterval(() => setNow(new Date()), 10000);

        return () => clearInterval(timer);
    }, []);

    return (
        <span className="tabular-nums">
            {now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}
        </span>
    );
}

export default function Display({ services, lastCalled }) {
    const [isFlashing, setIsFlashing] = useState(false);
    const [soundOn, setSoundOn] = useState(false);

    // The Echo listener keeps the first callback it's given, so it reads this ref
    // rather than the soundOn state (which it would only ever see as false).
    const soundOnRef = useRef(false);

    const enableSound = () => {
        unlockAudio().then(() => {
            soundOnRef.current = true;
            setSoundOn(true);
            playChime();
        });
    };

    // Safety net in case the WebSocket connection drops.
    usePoll(30000);

    useEffect(() => {
        if (!isFlashing) {
            return;
        }

        const timer = setTimeout(() => setIsFlashing(false), 8000);

        return () => clearTimeout(timer);
    }, [isFlashing]);

    const handleCalled = () => {
        if (soundOnRef.current) {
            playChime();
        }

        router.reload({ onSuccess: () => setIsFlashing(true) });
    };

    return (
        <div className="flex min-h-screen flex-col bg-gray-900 p-8 text-white">
            <Head title="Now Serving" />

            {services.map((service) => (
                <ServiceListener
                    key={service.id}
                    serviceId={service.id}
                    onCalled={handleCalled}
                />
            ))}

            <header className="mb-8 flex items-baseline justify-between">
                <h1 className="text-4xl font-bold tracking-tight">
                    Now Serving
                </h1>
                <p className="text-3xl text-gray-400">
                    <Clock />
                </p>
            </header>

            <section
                className={`mb-8 rounded-2xl p-8 text-center transition-colors duration-700 ${
                    isFlashing
                        ? 'animate-pulse bg-amber-400 text-gray-900'
                        : 'bg-gray-800'
                }`}
            >
                {lastCalled ? (
                    <>
                        <p className="text-2xl uppercase tracking-widest opacity-75">
                            {lastCalled.service}
                        </p>
                        <p className="my-2 text-9xl font-black tabular-nums">
                            {lastCalled.code}
                        </p>
                        <p className="text-5xl font-semibold">
                            Please proceed to {lastCalled.counter}
                        </p>
                    </>
                ) : (
                    <p className="py-12 text-4xl text-gray-400">
                        Please take a ticket. Numbers will be called here.
                    </p>
                )}
            </section>

            <section className="grid flex-1 gap-8 lg:grid-cols-2">
                {services.map((service) => (
                    <div key={service.id} className="rounded-2xl bg-gray-800 p-6">
                        <div className="mb-4 flex items-baseline justify-between">
                            <h2 className="text-3xl font-bold">{service.name}</h2>
                            <p className="text-xl text-gray-400">
                                {service.waitingCount} waiting
                            </p>
                        </div>

                        {service.serving.length === 0 ? (
                            <p className="py-4 text-2xl text-gray-500">
                                No one is being served.
                            </p>
                        ) : (
                            <ul className="divide-y divide-gray-700">
                                {service.serving.map((ticket) => (
                                    <li
                                        key={ticket.code}
                                        className="flex items-baseline justify-between py-3"
                                    >
                                        <span className="text-5xl font-bold tabular-nums">
                                            {ticket.code}
                                        </span>
                                        <span className="text-3xl text-gray-300">
                                            {ticket.counter}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}

                        {service.nextUp.length > 0 && (
                            <p className="mt-6 text-2xl text-gray-400">
                                Next:{' '}
                                <span className="tabular-nums text-gray-200">
                                    {service.nextUp.join(', ')}
                                </span>
                            </p>
                        )}
                    </div>
                ))}
            </section>

            {!soundOn && (
                <button
                    type="button"
                    onClick={enableSound}
                    className="fixed bottom-6 right-6 rounded-full bg-gray-700 px-5 py-3 text-lg text-gray-200 shadow-lg hover:bg-gray-600"
                >
                    Enable sound
                </button>
            )}
        </div>
    );
}
