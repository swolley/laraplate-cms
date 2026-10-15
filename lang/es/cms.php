<?php

declare(strict_types=1);

return [
    'actions' => [
        'approve' => 'Aprobar',
        'confirmReject' => 'Confirmar rechazo',
        'geocode' => 'Geocodificar',
        'reject' => 'Rechazar',
        'resumeFromRejection' => 'Retomar desde el rechazo',
    ],
    'entities' => [
        'categories' => [
            'plural' => 'Categorías',
            'singular' => 'Categoría',
        ],
        'comments' => [
            'plural' => 'Comentarios',
            'singular' => 'Comentario',
        ],
        'contents' => [
            'plural' => 'Contenidos',
            'singular' => 'Contenido',
        ],
        'contributors' => [
            'plural' => 'Colaboradores',
            'singular' => 'Colaborador',
        ],
        'entities' => [
            'plural' => 'Entidades',
            'singular' => 'Entidad',
        ],
        'locations' => [
            'plural' => 'Ubicaciones',
            'singular' => 'Ubicación',
        ],
        'presets' => [
            'plural' => 'Preajustes',
            'singular' => 'Preajuste',
        ],
        'tags' => [
            'plural' => 'Etiquetas',
            'singular' => 'Etiqueta',
        ],
    ],
    'fields' => [
        'address' => 'Dirección',
        'approvers_remaining' => 'Votos restantes',
        'category' => 'Categoría',
        'city' => 'Ciudad',
        'contributor' => 'Colaborador',
        'country' => 'País',
        'location' => 'Ubicación',
        'parent' => 'Padre',
        'postcode' => 'Código postal',
        'province' => 'Provincia',
        'queued_at' => 'En cola desde',
        'slug' => 'Slug',
        'status' => 'Estado',
        'tag' => 'Etiqueta',
        'title' => 'Título',
        'type' => 'Tipo',
    ],
    'messages' => [
        'approveFailed' => 'La aprobación ha fallado.',
        'approved' => 'Revisión aprobada.',
        'geocodeFailed' => 'La geocodificación ha fallado.',
        'geocodeFilled' => 'Campos geográficos resueltos.',
        'geocodeNeedQuery' => 'Introduce primero una dirección o un nombre (mín. 3 caracteres].',
        'geocodeNoResult' => 'No se encontró ninguna coincidencia para esta búsqueda.',
        'rejectFailed' => 'El rechazo ha fallado.',
        'rejectReasonRequired' => 'Indica un motivo para rechazar.',
        'rejected' => 'Revisión rechazada.',
        'relationsSaved' => 'Relaciones actualizadas.',
        'sentForReview' => 'Cambios enviados a revisión.',
    ],
    'navigation' => [
        'main' => 'CMS',
    ],
    'status' => [
        'expired' => 'Caducado',
        'live' => 'Publicado',
        'locked' => 'Bloqueado',
        'pendingApprove' => 'Por aprobar',
        'pendingWait' => 'En revisión',
        'scheduled' => 'Programado',
        'unpublished' => 'No publicado',
    ],
];
