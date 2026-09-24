import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Form, Head } from '@inertiajs/react';

function ActiveCheckbox({ defaultChecked }) {
    return (
        <label className="flex items-center gap-2 text-sm text-stone-700">
            <Checkbox name="is_active" value="1" defaultChecked={defaultChecked} />
            Open
        </label>
    );
}

function Saved({ show }) {
    return show ? <span className="text-sm text-brand-700">Saved.</span> : null;
}

function ServiceFields({ service }) {
    return (
        <>
            <div className="flex-1">
                <TextInput
                    name="name"
                    defaultValue={service?.name}
                    placeholder="Service name, e.g. Registrar"
                    className="w-full"
                    required
                />
            </div>
            <div className="w-28">
                <TextInput
                    name="prefix"
                    defaultValue={service?.prefix}
                    placeholder="Prefix"
                    maxLength={5}
                    className="w-full uppercase"
                    required
                />
            </div>
        </>
    );
}

function CounterRow({ counter }) {
    return (
        <Form action={route('admin.counters.update', counter.id)} method="put">
            {({ errors, processing, recentlySuccessful }) => (
                <div className="py-2">
                    <div className="flex items-center gap-3">
                        <TextInput
                            name="name"
                            defaultValue={counter.name}
                            className="flex-1 text-sm"
                            required
                        />
                        <ActiveCheckbox defaultChecked={counter.isActive} />
                        <SecondaryButton type="submit" disabled={processing}>
                            Save
                        </SecondaryButton>
                        <Saved show={recentlySuccessful} />
                    </div>
                    <InputError message={errors.name} className="mt-1" />
                </div>
            )}
        </Form>
    );
}

function ServiceCard({ service }) {
    return (
        <div
            className={`bg-white p-6 shadow-sm shadow-brand-900/5 sm:rounded-xl ${
                service.isActive ? '' : 'opacity-75'
            }`}
        >
            <Form action={route('admin.services.update', service.id)} method="put">
                {({ errors, processing, recentlySuccessful }) => (
                    <>
                        <div className="flex items-center gap-3">
                            <ServiceFields service={service} />
                            <ActiveCheckbox defaultChecked={service.isActive} />
                            <PrimaryButton disabled={processing}>Save</PrimaryButton>
                            <Saved show={recentlySuccessful} />
                        </div>
                        <InputError message={errors.name} className="mt-1" />
                        <InputError message={errors.prefix} className="mt-1" />
                    </>
                )}
            </Form>

            <h4 className="mt-6 font-display text-base font-semibold tracking-tight text-stone-900">
                Counters
            </h4>
            <div className="mt-2 divide-y divide-stone-100">
                {service.counters.length === 0 && (
                    <p className="py-2 text-sm text-stone-500">No counters yet.</p>
                )}
                {service.counters.map((counter) => (
                    <CounterRow key={counter.id} counter={counter} />
                ))}
            </div>

            <Form
                action={route('admin.counters.store', service.id)}
                method="post"
                resetOnSuccess
                className="mt-3"
            >
                {({ errors, processing }) => (
                    <>
                        <div className="flex items-center gap-3">
                            <TextInput
                                name="name"
                                placeholder="New counter, e.g. Window 4"
                                className="flex-1 text-sm"
                                required
                            />
                            <input type="hidden" name="is_active" value="1" />
                            <SecondaryButton type="submit" disabled={processing}>
                                Add counter
                            </SecondaryButton>
                        </div>
                        <InputError message={errors.name} className="mt-1" />
                    </>
                )}
            </Form>
        </div>
    );
}

export default function Services({ services }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="font-display text-xl font-semibold leading-tight tracking-tight text-stone-800">
                    Services &amp; counters
                </h2>
            }
        >
            <Head title="Services" />

            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                    <p className="text-sm text-stone-600">
                        Untick "Open" to stop a service taking tickets or to close a
                        counter. Closed items are kept so past tickets still make sense.
                    </p>

                    {services.map((service) => (
                        <ServiceCard key={service.id} service={service} />
                    ))}

                    <div className="bg-white p-6 shadow-sm shadow-brand-900/5 sm:rounded-xl">
                        <h3 className="mb-3 text-lg font-semibold text-stone-900">
                            Add a service
                        </h3>
                        <Form
                            action={route('admin.services.store')}
                            method="post"
                            resetOnSuccess
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="flex items-center gap-3">
                                        <ServiceFields />
                                        <input type="hidden" name="is_active" value="1" />
                                        <PrimaryButton disabled={processing}>
                                            Add
                                        </PrimaryButton>
                                    </div>
                                    <InputError message={errors.name} className="mt-1" />
                                    <InputError message={errors.prefix} className="mt-1" />
                                </>
                            )}
                        </Form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
