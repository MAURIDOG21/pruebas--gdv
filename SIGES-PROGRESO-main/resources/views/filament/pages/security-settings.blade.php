<x-filament::page>
    @if ($qrSvg)
        <div class="space-y-4">
            <div class="text-sm">Escanea este código QR en tu app TOTP y luego confirma con un código de 6 dígitos.</div>
            <div class="max-w-sm">{!! $qrSvg !!}</div>
            <div class="text-xs text-gray-500">Clave secreta: <span class="font-mono">{{ $secret }}</span></div>
        </div>
    @else
        <div class="text-sm text-gray-600">Usa las acciones del encabezado para habilitar o administrar tu 2FA.</div>
    @endif
</x-filament::page>