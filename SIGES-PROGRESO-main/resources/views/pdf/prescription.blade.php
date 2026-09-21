<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receta Médica - {{ $prescription->folio }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #1f2937; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .title { font-size: 18px; font-weight: 700; }
        .copy { font-size: 12px; color: #6b7280; }
        .section { margin-bottom: 12px; }
        .label { font-weight: 600; color: #374151; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
        th { background: #f9fafb; font-weight: 600; }
        .footer { margin-top: 24px; font-size: 11px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="title">Receta Médica</div>
            <div class="copy">Copia: {{ $copyType === 'patient' ? 'Paciente' : 'Institución' }}</div>
        </div>
        <div>
            <div class="label">Folio:</div>
            <div>{{ $prescription->folio }}</div>
        </div>
    </div>

    <div class="section">
        <div><span class="label">Paciente:</span> {{ $prescription->medicalRecord?->patient?->full_name ?? 'N/A' }}</div>
        <div><span class="label">Expediente:</span> {{ $prescription->medicalRecord?->record_number ?? 'N/A' }}</div>
        <div><span class="label">Fecha de emisión:</span> {{ optional($prescription->issue_date)->format('d/m/Y') }}</div>
        <div><span class="label">Médico:</span> {{ $prescription->doctor?->name ?? 'N/A' }}</div>
    </div>

    @if(!empty($prescription->diagnosis))
    <div class="section">
        <div class="label">Diagnóstico</div>
        <div>{{ $prescription->diagnosis }}</div>
    </div>
    @endif

    <div class="section">
        <div class="label">Indicaciones / Medicamentos</div>
        <table>
            <thead>
                <tr>
                    <th>Medicamento</th>
                    <th>Dosis</th>
                    <th>Frecuencia</th>
                    <th>Duración</th>
                    <th>Vía</th>
                    <th>Notas</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($prescription->items ?? []) as $item)
                    <tr>
                        <td>{{ $item['medication'] ?? ($item['drug'] ?? '') }}</td>
                        <td>{{ $item['dose'] ?? '' }}</td>
                        <td>{{ $item['frequency'] ?? '' }}</td>
                        <td>{{ $item['duration'] ?? '' }}</td>
                        <td>{{ $item['route'] ?? '' }}</td>
                        <td>{{ $item['notes'] ?? ($item['instructions'] ?? '') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Sin medicamentos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(!empty($prescription->notes))
    <div class="section">
        <div class="label">Notas generales</div>
        <div>{{ $prescription->notes }}</div>
    </div>
    @endif

    <div class="footer">
        <div>Firma y cédula del médico: ________________________________</div>
    </div>
</body>
</html>