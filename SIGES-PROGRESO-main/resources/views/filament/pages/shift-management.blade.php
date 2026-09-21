<x-filament-panels::page>
    <div class="space-y-8">
        {{-- Título principal --}}
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Gestión de Turnos</h1>

        {{-- Alertas --}}
        @if(session('shift_required'))
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6 flex items-center space-x-3">
                <svg class="h-5 w-5 text-yellow-500 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <div class="text-yellow-800">
                    <p class="font-semibold">Turno Requerido</p>
                    <p class="text-sm">{{ session('shift_required') }}</p>
                </div>
            </div>
        @endif

        @if(session('no_shifts_available'))
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 flex items-center space-x-3">
                <svg class="h-5 w-5 text-red-500 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <div class="text-red-800">
                    <p class="font-semibold">Sin Turnos Disponibles</p>
                    <p class="text-sm">{{ session('no_shifts_available') }}</p>
                </div>
            </div>
        @endif

        {{-- Turnos Abiertos: Tarjetas --}}
        <section class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Turnos Abiertos</h2>

            @php $openShifts = $this->getOpenShifts(); @endphp

            @if($openShifts->count())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($openShifts as $shift)
                        <article class="border border-green-300 bg-green-50 rounded-lg p-5 flex flex-col justify-between hover:shadow-lg transition-shadow duration-300">
                            <div>
                                <h3 class="text-lg font-semibold text-green-900 mb-1">
                                    {{ $shift->clinic_name }} - {{ ucfirst($shift->shift->value) }}
                                </h3>
                                <p class="text-sm text-green-700 mb-1 flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-600" width="16" height="16" style="width:16px;height:16px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 12v4m0 0v4m0-4h4m-4 0H8" />
                                    </svg>
                                    <span>{{ $shift->service->name }} | Dr. {{ $shift->user->name }}</span>
                                </p>
                                <p class="text-xs text-green-700 flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-green-600" width="12" height="12" style="width:12px;height:12px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M5 11h14v10H5z" />
                                    </svg>
                                    <span>Fecha: {{ \Carbon\Carbon::parse($shift->date)->format('d/m/Y') }}</span>
                                </p>
                                <p class="text-xs text-green-600 mt-2">
                                    Abierto por <strong>{{ $shift->openedBy->name }}</strong> el {{ $shift->shift_opened_at->format('d/m/Y H:i') }}
                                </p>
                                @if($shift->opening_notes)
                                    <p class="text-xs text-green-600 mt-1 italic">
                                        <strong>Notas:</strong> {{ $shift->opening_notes }}
                                    </p>
                                @endif
                            </div>

                            <div class="mt-4 flex items-center justify-between">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                    <span class="w-2 h-2 mr-2 rounded-full bg-green-600"></span>
                                    Abierto
                                </span>
                                {{ ($this->closeShiftAction)(['class' => 'bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition flex items-center space-x-2']) }}
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-center italic">No hay turnos abiertos actualmente.</p>
            @endif
        </section>

        {{-- Turnos Disponibles para Abrir: Tarjetas --}}
        <section class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Turnos Disponibles para Abrir</h2>

            @php $availableShifts = $this->getAvailableShifts(); @endphp

            @if($availableShifts->count())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($availableShifts as $shift)
                        <article class="border border-gray-300 bg-gray-50 rounded-lg p-5 flex flex-col justify-between hover:shadow-lg transition-shadow duration-300">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-1">
                                    {{ $shift->clinic_name }} - {{ ucfirst($shift->shift->value) }}
                                </h3>
                                <p class="text-sm text-gray-700 mb-1 flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-600" width="16" height="16" style="width:16px;height:16px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 12v4m0 0v4m0-4h4m-4 0H8" />
                                    </svg>
                                    <span>{{ $shift->service->name }} | Dr. {{ $shift->user->name }}</span>
                                </p>
                                <p class="text-xs text-gray-600 flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-gray-600" width="12" height="12" style="width:12px;height:12px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M5 11h14v10H5z" />
                                    </svg>
                                    <span>Fecha: {{ \Carbon\Carbon::parse($shift->date)->format('d/m/Y') }}</span>
                                </p>
                            </div>

                            <div class="mt-4 flex items-center justify-between">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-800">
                                    Disponible
                                </span>
                                {{ ($this->openShiftAction)(['class' => 'bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition flex items-center space-x-2']) }}
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-center italic">No hay turnos disponibles para abrir.</p>
            @endif
        </section>

        {{-- Información Importante --}}
        <section class="bg-blue-50 border border-blue-300 rounded-lg p-6 flex items-start space-x-4">
            <svg class="h-5 w-5 text-blue-600 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" />
            </svg>
            <div>
                <h3 class="text-md font-semibold text-blue-900 mb-2">Información Importante</h3>
                <ul class="list-disc list-inside space-y-1 text-blue-800 text-sm leading-relaxed">
                    <li>Debe abrir un turno antes de acceder a los módulos de citas y horarios.</li>
                    <li>Solo puede haber un turno abierto por consultorio a la vez.</li>
                    <li>Recuerde cerrar el turno al finalizar sus actividades.</li>
                    <li>Las notas de apertura y cierre quedan registradas para auditoría.</li>
                </ul>
            </div>
        </section>
    </div>
</x-filament-panels::page>