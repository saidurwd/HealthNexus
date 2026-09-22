<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-semibold text-gray-900">Appointment History</h1>
                        <a href="{{ route('admin.appointments.show', $appointment) }}" class="text-indigo-600 hover:text-indigo-900">
                            &larr; Back to Appointment
                        </a>
                    </div>

                    <div class="mb-6">
                        <h2 class="text-lg font-medium text-gray-900">Appointment #{{ $appointment->appointment_no }}</h2>
                        <p class="text-sm text-gray-600">Patient: {{ $appointment->patient->name ?? 'N/A' }}</p>
                        <p class="text-sm text-gray-600">Doctor: {{ $appointment->doctor->name ?? 'N/A' }}</p>
                        <p class="text-sm text-gray-600">Date: {{ $appointment->appointment_date->format('Y-m-d') }}</p>
                    </div>

                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Date/Time</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status Change</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Changed By</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Reason</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse($history as $record)
                                    <tr>
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-900 sm:pl-6">
                                            {{ $record->created_at->format('Y-m-d H:i') }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                                            <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                                {{ $record->old_status ?? 'N/A' }}
                                            </span>
                                            <span class="mx-2">&rarr;</span>
                                            <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
                                                {{ $record->new_status }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                                            {{ $record->changer->name ?? 'System' }}
                                        </td>
                                        <td class="px-3 py-4 text-sm text-gray-900">
                                            {{ $record->reason ?? '-' }}
                                        </td>
                                        <td class="px-3 py-4 text-sm text-gray-900">
                                            {{ $record->notes ?? '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-4 text-sm text-gray-500 text-center">
                                            No history records found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
