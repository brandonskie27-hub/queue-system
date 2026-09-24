import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Form, Head } from '@inertiajs/react';

export default function SelectCounter({ services, currentCounterId }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="font-display text-xl font-semibold leading-tight tracking-tight text-stone-800">
                    Which counter are you working at?
                </h2>
            }
        >
            <Head title="Choose counter" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                    {services.length === 0 && (
                        <p className="bg-white p-6 text-stone-600 shadow-sm shadow-brand-900/5 sm:rounded-xl">
                            There are no open counters yet.
                        </p>
                    )}

                    {services.map((service) => (
                        <div
                            key={service.id}
                            className="bg-white p-6 shadow-sm shadow-brand-900/5 sm:rounded-xl"
                        >
                            <h3 className="text-lg font-semibold text-stone-900">
                                {service.name}
                            </h3>
                            <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                                {service.counters.map((counter) => (
                                    <Form
                                        key={counter.id}
                                        action={route('counter.update')}
                                        method="put"
                                    >
                                        {({ processing }) => (
                                            <>
                                                <input
                                                    type="hidden"
                                                    name="counter_id"
                                                    value={counter.id}
                                                />
                                                <button
                                                    type="submit"
                                                    disabled={processing}
                                                    className={`w-full rounded-lg border px-4 py-3 text-sm font-semibold transition duration-200 ease-out active:scale-[0.98] ${
                                                        counter.id ===
                                                        currentCounterId
                                                            ? 'border-brand-500 bg-brand-50 text-brand-700'
                                                            : 'border-stone-300 text-stone-700 hover:border-stone-400 hover:bg-stone-50'
                                                    }`}
                                                >
                                                    {counter.name}
                                                </button>
                                            </>
                                        )}
                                    </Form>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
