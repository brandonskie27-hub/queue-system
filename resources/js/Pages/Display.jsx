import BrandHeader from '@/Components/BrandHeader';
import { branding } from '@/branding';
import { playChime, unlockAudio } from '@/utils/chime';
import { queueUrl } from '@/utils/queueUrl';
import { Head, router, usePoll } from '@inertiajs/react';
import { useEchoPublic } from '@laravel/echo-react';
import { useEffect, useRef, useState } from 'react';
import QRCode from 'react-qr-code';

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
    const { url, phonesCanReach } = queueUrl();

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
        <div className="flex min-h-[100dvh] flex-col bg-brand-50 text-stone-900">
            <Head title="Now Serving" />

            {services.map((service) => (
                <ServiceListener
                    key={service.id}
                    serviceId={service.id}
                    onCalled={handleCalled}
                />
            ))}

            <BrandHeader subtitle="Now Serving" size="lg">
                <p className="text-4xl font-semibold text-white">
                    <Clock />
                </p>
            </BrandHeader>

            <main className="flex flex-1 flex-col gap-8 p-8">
                <section
                    className={`rounded-2xl border-l-8 p-10 text-center shadow-sm shadow-brand-900/5 transition-colors duration-700 ${
                        isFlashing
                            ? 'motion-safe:animate-pulse border-gold-600 bg-gold-200'
                            : 'border-brand-700 bg-white'
                    }`}
                >
                    {lastCalled ? (
                        <>
                            <p className="text-2xl font-semibold uppercase tracking-[0.12em] text-brand-600">
                                {lastCalled.service}
                            </p>
                            <p className="my-2 font-display text-[10rem] font-bold leading-none tracking-tight tabular-nums text-brand-900">
                                {lastCalled.code}
                            </p>
                            <p className="mt-4 font-display text-5xl font-semibold tracking-tight text-brand-800">
                                Please proceed to {lastCalled.counter}
                            </p>
                        </>
                    ) : (
                        <p className="py-12 text-4xl text-stone-500">
                            Please take a ticket. Numbers will be called here.
                        </p>
                    )}
                </section>

                <section className="grid flex-1 content-start gap-8 lg:grid-cols-2">
                    {services.map((service) => (
                        <div
                            key={service.id}
                            className="overflow-hidden rounded-2xl bg-white shadow-sm shadow-brand-900/5"
                        >
                            <div className="flex items-baseline justify-between bg-brand-700 px-6 py-4 text-white">
                                <h2 className="font-display text-3xl font-semibold tracking-tight">{service.name}</h2>
                                <p className="text-xl text-brand-100">
                                    {service.waitingCount} waiting
                                </p>
                            </div>

                            <div className="p-6">
                                {service.serving.length === 0 ? (
                                    <p className="py-4 text-2xl text-stone-500">
                                        No one is being served.
                                    </p>
                                ) : (
                                    <ul className="divide-y divide-brand-100">
                                        {service.serving.map((ticket) => (
                                            <li
                                                key={ticket.code}
                                                className="flex items-baseline justify-between py-3"
                                            >
                                                <span className="font-display text-6xl font-bold tabular-nums tracking-tight text-brand-900">
                                                    {ticket.code}
                                                </span>
                                                <span className="text-3xl text-stone-700">
                                                    {ticket.counter}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                )}

                                {service.nextUp.length > 0 && (
                                    <p className="mt-6 text-2xl text-stone-500">
                                        Next:{' '}
                                        <span className="font-semibold tabular-nums text-stone-800">
                                            {service.nextUp.join(', ')}
                                        </span>
                                    </p>
                                )}
                            </div>
                        </div>
                    ))}
                </section>
            </main>

            <footer className="flex items-center justify-between gap-8 border-t-4 border-gold-500 bg-gold-100 px-10 py-4 text-gold-900">
                <p className="font-display text-3xl font-semibold">
                    {branding.reminder}
                </p>
                {phonesCanReach && (
                    <div className="flex items-center gap-4">
                        <p className="text-right text-xl font-medium leading-snug">
                            Scan to get
                            <br />a ticket
                        </p>
                        <div className="rounded-lg bg-white p-2">
                            <QRCode value={url} size={96} fgColor="#2e3b1c" />
                        </div>
                    </div>
                )}
            </footer>

            {!soundOn && (
                <button
                    type="button"
                    onClick={enableSound}
                    className="fixed bottom-40 right-6 rounded-full bg-brand-700 px-5 py-3 text-lg text-white shadow-lg transition hover:bg-brand-600"
                >
                    Enable sound
                </button>
            )}
        </div>
    );
}
