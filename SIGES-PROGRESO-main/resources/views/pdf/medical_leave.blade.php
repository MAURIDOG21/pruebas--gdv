<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Incapacidad Médica - Folio {{ $medicalLeave->folio }}</title>
    <style>
        /* Estilos generales del documento */
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            margin: 20px auto;
            padding: 20px;
        }
        /* Encabezado con logos y títulos */
        .header {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .header img.logo {
            position: absolute;
            top: 0;
            left: 0;
            max-height: 80px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #007bff;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
            font-weight: normal;
        }
        /* Marca de agua */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: #000;
            opacity: 0.08;
            z-index: -1000;
            font-weight: bold;
            pointer-events: none;
        }
        /* Secciones de información */
        .info-section {
            margin-bottom: 25px;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
        }
        .info-section h3 {
            margin-top: 0;
            font-size: 16px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-grid .row {
            display: table-row;
        }
        .info-grid .cell {
            display: table-cell;
            padding: 5px;
            width: 50%;
        }
        .info-grid strong {
            display: inline-block;
            width: 150px;
        }
        /* Justificación médica */
        .reason-box {
            background-color: #f9f9f9;
        }
        /* Pie de página con firmas */
        .footer {
            margin-top: 50px;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 250px;
            margin: 40px auto 5px auto;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="watermark">{{ strtoupper($copyType === 'patient' ? 'Copia Asegurado' : 'Copia Institución') }}</div>

    <div class="container">
        <div class="header">
            {{-- Coloca aquí la ruta a tu logo. Debe ser una ruta absoluta. --}}
            <img src="{{ public_path('img/logo_ayuntamiento.png') }}" alt="Logo" class="logo" onerror="this.style.display='none'">
            <h1>H. Ayuntamiento de Progreso</h1>
            <h2>Licencia Médica por Incapacidad</h2>
        </div>

        <div class="info-section">
            <h3>Datos Generales</h3>
            <div class="info-grid">
                <div class="row">
                    <div class="cell"><strong>Folio:</strong> {{ $medicalLeave->folio }}</div>
                    <div class="cell"><strong>Fecha de Emisión:</strong> {{ $medicalLeave->issue_date->format('d/m/Y') }}</div>
                </div>
            </div>
        </div>

        <div class="info-section">
            <h3>Datos del Paciente</h3>
            <div class="info-grid">
                {{-- --- ¡CORRECCIONES APLICADAS AQUÍ! --- --}}
                <div class="row">
                    <div class="cell"><strong>Nombre:</strong> {{ $medicalLeave->medicalRecord->patient->full_name }}</div>
                    <div class="cell"><strong>N° Expediente:</strong> {{ $medicalLeave->medicalRecord->record_number }}</div>
                </div>
                <div class="row">
                    <div class="cell"><strong>CURP:</strong> {{ $medicalLeave->medicalRecord->patient->curp ?? 'N/A' }}</div>
                    <div class="cell"><strong>Tipo de Paciente:</strong> {{ $medicalLeave->medicalRecord->patient_type }}</div>
                </div>
            </div>
        </div>

        <div class="info-section reason-box">
            <h3>Detalles de la Incapacidad</h3>
            <div class="info-grid">
                <div class="row">
                    <div class="cell"><strong>Fecha de Inicio:</strong> {{ $medicalLeave->start_date->format('d/m/Y') }}</div>
                    <div class="cell"><strong>Fecha de Fin:</strong> {{ $medicalLeave->end_date->format('d/m/Y') }}</div>
                </div>
                <div class="row">
                    <div class="cell"><strong>Duración:</strong> {{ $medicalLeave->start_date->diffInDays($medicalLeave->end_date) + 1 }} días</div>
                    <div class="cell"><strong>Área Emisora:</strong> {{ $medicalLeave->issuing_department ?? 'Medicina General' }}</div>
                </div>
            </div>
            <p><strong>Justificación Médica:</strong></p>
            <p>{{ $medicalLeave->reason }}</p>
        </div>

        <div class="footer">
            <div class="signature-line">
                <strong>{{ $medicalLeave->doctor->name }}</strong><br>
                <span>Médico Tratante</span>
            </div>
        </div>
    </div>
</body>
</html>