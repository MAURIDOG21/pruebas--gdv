<?php

namespace App\Enums;

enum MedicalLeaveStatus : string
{
    case DRAFT = 'borrador';
    case PENDING_APPROVAL = 'pendiente_aprobacion';
    case APPROVED = 'aprobada';
    case ARCHIVED = 'archivada';
    case REJECTED = 'rechazada'; // Añadimos un estado de rechazo
}
