import { useMemo, useState } from 'react';
import type { Shift } from '../types';

type ShiftsTabProps = {
    shifts: Shift[];
};

type ShiftFormData = {
    id: number | null;
    name: string;
    start_time: string;
    end_time: string;
};

const emptyForm: ShiftFormData = {
    id: null,
    name: '',
    start_time: '09:00',
    end_time: '18:00',
};

export default function ShiftsTab({ shifts }: ShiftsTabProps) {
    const [items, setItems] = useState<Shift[]>(shifts);
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [formData, setFormData] = useState<ShiftFormData>(emptyForm);

    const isEditing = useMemo(() => formData.id !== null, [formData.id]);

    const openCreateModal = () => {
        setFormData(emptyForm);
        setIsFormOpen(true);
    };

    const closeForm = () => {
        setIsFormOpen(false);
        setFormData(emptyForm);
    };

    const handleSaveShift = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!formData.name.trim()) {
            return;
        }

        if (isEditing) {
            setItems((current) =>
                current.map((shift) =>
                    shift.id === formData.id
                        ? {
                              ...shift,
                              name: formData.name.trim(),
                              start_time: formData.start_time,
                              end_time: formData.end_time,
                          }
                        : shift,
                ),
            );
        } else {
            setItems((current) => [
                ...current,
                {
                    id: Date.now(),
                    name: formData.name.trim(),
                    start_time: formData.start_time,
                    end_time: formData.end_time,
                },
            ]);
        }

        closeForm();
    };

    const handleEdit = (shift: Shift) => {
        setFormData({
            id: shift.id,
            name: shift.name,
            start_time: shift.start_time,
            end_time: shift.end_time,
        });
        setIsFormOpen(true);
    };

    const handleDelete = (id: number) => {
        setItems((current) => current.filter((shift) => shift.id !== id));
    };

    return (
        <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div className="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <h2 className="text-base font-semibold text-slate-900">
                        Shifts & Schedule
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Maintain working shift definitions for your teams.
                    </p>
                </div>

                <button
                    type="button"
                    onClick={openCreateModal}
                    className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
                >
                    Add Shift
                </button>
            </div>

            <div className="grid gap-4 p-6 md:grid-cols-2">
                {items.length === 0 && (
                    <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-500">
                        No shifts configured yet. Click Add Shift to create one.
                    </div>
                )}

                {items.map((shift) => (
                    <div
                        key={shift.id}
                        className="rounded-xl border border-slate-200 bg-slate-50 p-4"
                    >
                        <div className="flex items-start justify-between">
                            <div>
                                <h3 className="text-sm font-semibold text-slate-900">
                                    {shift.name}
                                </h3>
                                <p className="mt-1 text-sm text-slate-600">
                                    {shift.start_time} - {shift.end_time}
                                </p>
                            </div>

                            <div className="flex gap-2">
                                <button
                                    type="button"
                                    onClick={() => handleEdit(shift)}
                                    className="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-100"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handleDelete(shift.id)}
                                    className="rounded-md border border-red-200 bg-white px-2.5 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            {isFormOpen && (
                <div className="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 p-4">
                    <form
                        onSubmit={handleSaveShift}
                        className="w-full max-w-md rounded-2xl bg-white shadow-xl"
                    >
                        <div className="border-b border-slate-200 px-5 py-4">
                            <h3 className="text-base font-semibold text-slate-900">
                                {isEditing ? 'Edit Shift' : 'Create Shift'}
                            </h3>
                        </div>

                        <div className="space-y-4 px-5 py-4">
                            <div>
                                <label className="mb-2 block text-sm font-medium text-slate-700">
                                    Shift Name
                                </label>
                                <input
                                    type="text"
                                    value={formData.name}
                                    onChange={(event) =>
                                        setFormData((current) => ({
                                            ...current,
                                            name: event.target.value,
                                        }))
                                    }
                                    placeholder="Morning Shift"
                                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                                    required
                                />
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label className="mb-2 block text-sm font-medium text-slate-700">
                                        Start Time
                                    </label>
                                    <input
                                        type="time"
                                        value={formData.start_time}
                                        onChange={(event) =>
                                            setFormData((current) => ({
                                                ...current,
                                                start_time: event.target.value,
                                            }))
                                        }
                                        className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                                        required
                                    />
                                </div>

                                <div>
                                    <label className="mb-2 block text-sm font-medium text-slate-700">
                                        End Time
                                    </label>
                                    <input
                                        type="time"
                                        value={formData.end_time}
                                        onChange={(event) =>
                                            setFormData((current) => ({
                                                ...current,
                                                end_time: event.target.value,
                                            }))
                                        }
                                        className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                                        required
                                    />
                                </div>
                            </div>
                        </div>

                        <div className="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4">
                            <button
                                type="button"
                                onClick={closeForm}
                                className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
                            >
                                {isEditing ? 'Update Shift' : 'Save Shift'}
                            </button>
                        </div>
                    </form>
                </div>
            )}
        </div>
    );
}
