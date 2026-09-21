<x-filament-panels::page>
    {{-- Contenedor principal con Alpine.js para la búsqueda --}}
    <div x-data="{ search: '' }" class="space-y-8">

        {{-- Barra de Búsqueda --}}
        <div class="relative">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <x-heroicon-o-magnifying-glass class="w-5 h-5 text-gray-400" style="width: 20px; height: 20px; color: #9ca3af;" />
            </div>
            <input 
                type="text" 
                x-model="search" 
                class="block w-full p-4 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" 
                placeholder="Buscar tutorial (ej. crear paciente, enfermería, médico...)" 
            >
        </div>

        {{-- SECCIÓN 1: GESTIÓN DE PACIENTES --}}
        <div x-show="!search || 'pacientes'.includes(search.toLowerCase()) || 'crear'.includes(search.toLowerCase()) || 'editar'.includes(search.toLowerCase())" class="space-y-4">
            <div class="flex items-center gap-2 pb-2 border-b border-gray-200 dark:border-gray-700">
                <x-heroicon-o-users class="w-6 h-6 text-primary-600" style="width: 24px; height: 24px; color: #d97706;" />
                <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                    Gestión de Pacientes
                </h2>
            </div>
            
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                
                {{-- Video 1: Crear Paciente --}}
                <div class="overflow-hidden bg-white rounded-lg shadow ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                        <h3 class="font-medium text-gray-950 dark:text-white">Registrar nuevo paciente</h3>
                    </div>
                    <div class="aspect-video bg-gray-100 dark:bg-gray-800 flex items-center justify-center relative group">
                        <video controls class="w-full h-full object-cover" preload="metadata">
                            <source src="{{ asset('storage/ayuda/pacientes/crear.mp4') }}" type="video/mp4">
                            Tu navegador no soporta video.
                        </video>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-800/50 h-full">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Alta de paciente, llenado de CURP y datos generales.
                        </p>
                    </div>
                </div>

               

        {{-- SECCIÓN 2: CITAS Y VISITAS --}}
        <div x-show="!search || 'citas'.includes(search.toLowerCase()) || 'visitas'.includes(search.toLowerCase()) || 'agendar'.includes(search.toLowerCase())" class="space-y-4">
            <div class="flex items-center gap-2 pb-2 border-b border-gray-200 dark:border-gray-700">
                <x-heroicon-o-calendar class="w-6 h-6 text-primary-600" style="width: 24px; height: 24px; color: #2563eb;" />
                <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                    Citas y Recepción
                </h2>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                
                {{-- Video 3: Crear Cita --}}
                <div class="overflow-hidden bg-white rounded-lg shadow ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                        <h3 class="font-medium text-gray-950 dark:text-white">Agendar visita (Recepcionista)</h3>
                    </div>
                    <div class="aspect-video bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        <video controls class="w-full h-full object-cover" preload="metadata">
                            <source src="{{ asset('storage/ayuda/citas/crear.mp4') }}" type="video/mp4">
                            Tu navegador no soporta video.
                        </video>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-800/50 h-full">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Búsqueda de paciente y creación de ticket de visita.
                        </p>
                    </div>
                </div>

            </div>
        </div>

        {{-- SECCIÓN 3: FLUJO CLÍNICO (Enfermería y Médicos) --}}
        <div x-show="!search || 'clinico'.includes(search.toLowerCase()) || 'enfermeria'.includes(search.toLowerCase()) || 'medico'.includes(search.toLowerCase())" class="space-y-4">
            <div class="flex items-center gap-2 pb-2 border-b border-gray-200 dark:border-gray-700">
                <x-heroicon-o-heart class="w-6 h-6 text-primary-600" style="width: 24px; height: 24px; color: #dc2626;" />
                <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                    Flujo Clínico
                </h2>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                
                {{-- Video 4: Enfermería --}}
                <div class="overflow-hidden bg-white rounded-lg shadow ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                        <h3 class="font-medium text-gray-950 dark:text-white">Hoja Inicial y Signos (Enfermería)</h3>
                    </div>
                    <div class="aspect-video bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        <video controls class="w-full h-full object-cover" preload="metadata">
                            <source src="{{ asset('storage/ayuda/citas/flujo_enfermeria.mp4') }}" type="video/mp4">
                            Tu navegador no soporta video.
                        </video>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-800/50 h-full">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Captura de signos vitales y hoja inicial de enfermería.
                        </p>
                    </div>
                </div>

                {{-- Video 5: Médico --}}
                <div class="overflow-hidden bg-white rounded-lg shadow ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                        <h3 class="font-medium text-gray-950 dark:text-white">Consulta y Receta (Médico)</h3>
                    </div>
                    <div class="aspect-video bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        <video controls class="w-full h-full object-cover" preload="metadata">
                            <source src="{{ asset('storage/ayuda/citas/flujo_medico.mp4') }}" type="video/mp4">
                            Tu navegador no soporta video.
                        </video>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-800/50 h-full">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Llenado de historia clínica, diagnóstico y expedición de receta.
                        </p>
                    </div>
                </div>

            </div>
        </div>

        {{-- SECCIÓN 4: CONFIGURACIÓN --}}
        <div x-show="!search || 'turnos'.includes(search.toLowerCase()) || 'consultorios'.includes(search.toLowerCase())" class="space-y-4">
            <div class="flex items-center gap-2 pb-2 border-b border-gray-200 dark:border-gray-700">
                <x-heroicon-o-cog-6-tooth class="w-6 h-6 text-primary-600" style="width: 24px; height: 24px; color: #6b7280;" />
                <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                    Configuración y Turnos
                </h2>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                
                {{-- Video 6: Turnos --}}
                <div class="overflow-hidden bg-white rounded-lg shadow ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                        <h3 class="font-medium text-gray-950 dark:text-white">Gestión de Turnos y Consultorios</h3>
                    </div>
                    <div class="aspect-video bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        <video controls class="w-full h-full object-cover" preload="metadata">
                            <source src="{{ asset('storage/ayuda/turnos/gestion.mp4') }}" type="video/mp4">
                            Tu navegador no soporta video.
                        </video>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-800/50 h-full">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Apertura de turnos diarios y asignación de médicos.
                        </p>
                    </div>
                </div>

            </div>
        </div>

        {{-- Mensaje si no hay resultados --}}
        <div x-show="search && $el.parentElement.querySelectorAll('div[x-show]:not([style*=\'display: none\'])').length === 0" class="text-center py-10 text-gray-500">
            No se encontraron tutoriales para tu búsqueda.
        </div>

    </div>
</x-filament-panels::page>