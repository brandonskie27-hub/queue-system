import ApplicationLogo from '@/Components/ApplicationLogo';
import { branding } from '@/branding';
import { queueUrl } from '@/utils/queueUrl';
import { Head } from '@inertiajs/react';
import QRCode from 'react-qr-code';

// Printable A4 poster with a QR code to the ticket page. Print it from the browser (Ctrl+P).
export default function Poster() {
    const { url, phonesCanReach } = queueUrl();

    return (
        <div className="min-h-[100dvh] bg-stone-200 py-8 print:bg-white print:py-0">
            <Head title="Queue poster" />
            {/* One A4 page with no browser margins, so the poster fills the sheet. */}
            <style>{'@page { size: A4; margin: 0; }'}</style>

            <div className="mx-auto mb-6 flex max-w-[210mm] items-center justify-between gap-4 px-4 print:hidden">
                <p className="text-sm text-stone-600">
                    Print this page on A4 and post it near the office.
                </p>
                <button
                    type="button"
                    onClick={() => window.print()}
                    disabled={!phonesCanReach}
                    className="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white transition duration-200 ease-out hover:bg-brand-600 active:scale-[0.98] disabled:opacity-40"
                >
                    Print poster
                </button>
            </div>

            {!phonesCanReach && (
                <p className="mx-auto mb-6 max-w-[210mm] rounded-lg bg-gold-100 px-4 py-3 text-sm text-gold-900 print:hidden">
                    This page is open at <strong>{window.location.host}</strong>, which
                    phones can't reach. Open it using this PC's network address instead,
                    for example <strong>http://192.168.x.x:8000/poster</strong>, so the QR
                    code works.
                </p>
            )}

            <article className="mx-auto flex min-h-[297mm] max-w-[210mm] flex-col bg-white shadow-lg shadow-brand-900/10 print:h-[297mm] print:min-h-0 print:overflow-hidden print:shadow-none">
                <header className="border-b-8 border-gold-500 bg-brand-800 px-12 py-10 text-center text-white print:py-7">
                    <ApplicationLogo className="mx-auto h-28 w-28 rounded-full bg-white p-1 print:h-24 print:w-24" />
                    <h1 className="mt-5 font-display text-4xl font-bold">
                        {branding.schoolName}
                    </h1>
                    <p className="mt-2 text-xl text-gold-200">
                        Registrar's Office and Finance
                    </p>
                </header>

                <main className="flex flex-1 flex-col items-center px-12 py-12 text-center print:py-8">
                    <h2 className="font-display text-5xl font-bold tracking-tight text-brand-900">
                        Get your queue number here
                    </h2>
                    <p className="mt-4 max-w-[40ch] text-xl leading-relaxed text-stone-600">
                        Scan with your phone camera. No app or account needed.
                    </p>

                    <p className="mt-8 rounded-xl bg-brand-50 px-8 py-4 print:mt-6 text-2xl text-brand-900 ring-2 ring-brand-200">
                        First, connect to the{' '}
                        <strong className="font-display font-bold">
                            {branding.wifiName}
                        </strong>{' '}
                        Wi-Fi
                    </p>

                    <div className="mt-10 rounded-2xl border-4 border-brand-700 p-6 print:mt-6 print:p-4">
                        {phonesCanReach ? (
                            <QRCode value={url} size={300} fgColor="#2e3b1c" />
                        ) : (
                            <div className="flex h-[300px] w-[300px] items-center justify-center text-stone-400">
                                QR code appears when opened via the network address
                            </div>
                        )}
                    </div>
                    <p className="mt-4 font-mono text-lg text-stone-700">{url}</p>

                    <ol className="mt-12 print:mt-8 grid w-full max-w-3xl grid-cols-4 gap-6 text-left">
                        <li>
                            <p className="font-display text-2xl font-semibold text-brand-800">
                                Connect
                            </p>
                            <p className="mt-1 text-stone-600">
                                Join the {branding.wifiName} Wi-Fi.
                            </p>
                        </li>
                        <li>
                            <p className="font-display text-2xl font-semibold text-brand-800">
                                Scan
                            </p>
                            <p className="mt-1 text-stone-600">
                                Open the link on your phone.
                            </p>
                        </li>
                        <li>
                            <p className="font-display text-2xl font-semibold text-brand-800">
                                Choose
                            </p>
                            <p className="mt-1 text-stone-600">
                                Pick the office and get your number.
                            </p>
                        </li>
                        <li>
                            <p className="font-display text-2xl font-semibold text-brand-800">
                                Wait
                            </p>
                            <p className="mt-1 text-stone-600">
                                Your phone chimes when it's your turn.
                            </p>
                        </li>
                    </ol>
                </main>

                <footer className="bg-gold-100 px-12 py-6 text-center font-display text-2xl font-semibold text-gold-900">
                    {branding.reminder}
                </footer>
            </article>
        </div>
    );
}
