<?php

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
    'contents' => [
      'plural' => 'Contenidos',
      'singular' => 'Contenido',
    ],
    'contributors' => [
      'plural' => 'Colaboradores',
      'singular' => 'Colaborador',
    ],
    'locations' => [
      'plural' => 'Ubicaciones',
      'singular' => 'Ubicación',
    ],
    'tags' => [
      'plural' => 'Etiquetas',
      'singular' => 'Etiqueta',
    ],
  ],
  'fields' => [
    'address' => 'Dirección',
    'approversRemaining' => 'Votos restantes',
    'category' => 'Categoría',
    'city' => 'Ciudad',
    'contributor' => 'Colaborador',
    'country' => 'País',
    'location' => 'Ubicación',
    'parent' => 'Padre',
    'postcode' => 'Código postal',
    'province' => 'Provincia',
    'queuedAt' => 'En cola desde',
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