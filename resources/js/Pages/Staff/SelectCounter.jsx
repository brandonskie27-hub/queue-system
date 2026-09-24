import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Form, Head } from '@inertiajs/react';

export default function SelectCounter({ services, currentCounterId }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Which counter are you working at?
                </h2>
            }
        >
            <Head title="Choose counter" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                    {services.length === 0 && (
                        <p className="bg-white p-6 text-gray-600 shadow-sm sm:rounded-lg">
                            There are no open counters yet.
                        </p>
                    )}

                    {services.map((service) => (
                        <div
                            key={service.id}
                            className="bg-white p-6 shadow-sm sm:rounded-lg"
                        >
                            <h3 className="text-lg font-semibold text-gray-900">
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
                                                    className={`w-full rounded-md border px-4 py-3 text-sm font-semibold transition ${
                                                        counter.id ===
                                                        currentCounterId
                                                            ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                                            : 'border-gray-300 text-gray-700 hover:border-gray-400 hover:bg-gray-50'
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
