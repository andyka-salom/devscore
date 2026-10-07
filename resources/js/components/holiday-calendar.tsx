import { router, useForm } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Plus, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';

import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import { Input, InputError, Label } from '@/components/ui/input';
import { ConfirmDialog } from '@/components/confirm-dialog';
import type { Holiday } from '@/types/settings';

const DAYS_OF_WEEK = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
const MONTHS = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

interface HolidayCalendarProps {
    holidays: Holiday[];
}

export function HolidayCalendar({ holidays }: HolidayCalendarProps) {
    const [currentDate, setCurrentDate] = useState(new Date());
    const [selectedDate, setSelectedDate] = useState<string | null>(null);

    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    // Calculate calendar grid
    const firstDayOfMonth = new Date(year, month, 1);
    const lastDayOfMonth = new Date(year, month + 1, 0);
    const daysInMonth = lastDayOfMonth.getDate();
    
    // JS getDay(): 0 = Sunday, 1 = Monday. We want Monday = 0, Sunday = 6
    const startOffset = (firstDayOfMonth.getDay() + 6) % 7;
    
    const prevMonth = () => setCurrentDate(new Date(year, month - 1, 1));
    const nextMonth = () => setCurrentDate(new Date(year, month + 1, 1));

    // Pad empty cells before the 1st
    const blanks = Array.from({ length: startOffset }).map((_, i) => (
        <div key={`blank-${i}`} className="p-2 border border-slate-100 bg-slate-50/50 min-h-24"></div>
    ));

    const dayCells = Array.from({ length: daysInMonth }).map((_, i) => {
        const day = i + 1;
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dayHolidays = holidays.filter(h => h.date === dateStr);
        const isToday = dateStr === new Date().toISOString().split('T')[0];

        return (
            <div 
                key={day} 
                className={`p-2 border border-slate-100 min-h-24 relative group hover:bg-slate-50 transition-colors flex flex-col gap-1 ${isToday ? 'bg-blue-50/30' : ''}`}
            >
                <div className="flex justify-between items-start">
                    <span className={`text-sm font-medium ${isToday ? 'bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center' : 'text-slate-600'}`}>
                        {day}
                    </span>
                    <Button 
                        variant="ghost" 
                        size="icon" 
                        className="h-6 w-6 opacity-0 group-hover:opacity-100" 
                        onClick={() => setSelectedDate(dateStr)}
                    >
                        <Plus className="h-4 w-4 text-slate-400" />
                    </Button>
                </div>
                
                <div className="flex flex-col gap-1 mt-1">
                    {dayHolidays.map(holiday => (
                        <div key={holiday.id} className="text-xs bg-red-100 text-red-700 px-1.5 py-1 rounded truncate flex justify-between items-center group/holiday">
                            <span className="truncate" title={holiday.name}>{holiday.name}</span>
                            <ConfirmDialog
                                title="Hapus hari libur?"
                                description={`${holiday.name} akan dihitung sebagai hari kerja.`}
                                confirmLabel="Hapus"
                                variant="destructive"
                                onConfirm={() =>
                                    router.delete(`/settings/holidays/${holiday.id}`, { preserveScroll: true })
                                }
                                trigger={(open) => (
                                    <button
                                        type="button"
                                        onClick={(e) => { e.stopPropagation(); open(); }}
                                        className="opacity-0 group-hover/holiday:opacity-100 hover:text-red-900 shrink-0 ml-1"
                                    >
                                        <Trash2 className="h-3 w-3" />
                                    </button>
                                )}
                            />
                        </div>
                    ))}
                </div>
            </div>
        );
    });

    const cells = [...blanks, ...dayCells];
    
    // Pad end
    const remaining = 42 - cells.length; // 6 rows of 7
    if (remaining > 0 && remaining < 7) {
        cells.push(...Array.from({ length: remaining }).map((_, i) => (
            <div key={`blank-end-${i}`} className="p-2 border border-slate-100 bg-slate-50/50 min-h-24"></div>
        )));
    }

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h3 className="text-lg font-medium">Kalender Libur</h3>
                <div className="flex items-center gap-4">
                    <Button variant="outline" size="icon" onClick={prevMonth}>
                        <ChevronLeft className="h-4 w-4" />
                    </Button>
                    <div className="w-32 text-center font-semibold">
                        {MONTHS[month]} {year}
                    </div>
                    <Button variant="outline" size="icon" onClick={nextMonth}>
                        <ChevronRight className="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <div className="border rounded-xl overflow-hidden bg-white">
                <div className="grid grid-cols-7 bg-slate-100 border-b">
                    {DAYS_OF_WEEK.map(day => (
                        <div key={day} className="py-2 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            {day}
                        </div>
                    ))}
                </div>
                <div className="grid grid-cols-7">
                    {cells}
                </div>
            </div>

            <AddHolidayDialog 
                open={selectedDate !== null} 
                onClose={() => setSelectedDate(null)} 
                initialDate={selectedDate || ''} 
            />
        </div>
    );
}

function AddHolidayDialog({ open, onClose, initialDate }: { open: boolean, onClose: () => void, initialDate: string }) {
    const form = useForm({ date: initialDate, name: '' });

    // Update form date when initialDate changes
    if (form.data.date !== initialDate && open) {
        form.setData('date', initialDate);
    }

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/settings/holidays', { 
            preserveScroll: true, 
            onSuccess: () => {
                form.reset();
                onClose();
            } 
        });
    };

    return (
        <Dialog open={open} onClose={onClose} title="Tambah Hari Libur">
            <form onSubmit={submit} className="space-y-4">
                <div className="space-y-2">
                    <Label htmlFor="date">Tanggal</Label>
                    <Input
                        id="date"
                        type="date"
                        value={form.data.date}
                        onChange={(event) => form.setData('date', event.target.value)}
                    />
                    <InputError message={form.errors.date} />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="name">Nama Libur</Label>
                    <Input
                        id="name"
                        autoFocus
                        value={form.data.name}
                        onChange={(event) => form.setData('name', event.target.value)}
                    />
                    <InputError message={form.errors.name} />
                </div>
                <div className="flex justify-end gap-2 pt-2">
                    <Button type="button" variant="outline" onClick={onClose}>
                        Batal
                    </Button>
                    <Button type="submit" disabled={form.processing}>
                        Simpan
                    </Button>
                </div>
            </form>
        </Dialog>
    );
}
