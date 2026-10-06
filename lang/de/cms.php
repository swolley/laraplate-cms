<?php

return [
  'actions' => [
    'approve' => 'Genehmigen',
    'confirmReject' => 'Ablehnung bestätigen',
    'geocode' => 'Geokodieren',
    'reject' => 'Ablehnen',
    'resumeFromRejection' => 'Aus Ablehnung fortsetzen',
  ],
  'entities' => [
    'categories' => [
      'plural' => 'Kategorien',
      'singular' => 'Kategorie',
    ],
    'contents' => [
      'plural' => 'Inhalte',
      'singular' => 'Inhalt',
    ],
    'contributors' => [
      'plural' => 'Mitwirkende',
      'singular' => 'Mitwirkender',
    ],
    'locations' => [
      'plural' => 'Orte',
      'singular' => 'Ort',
    ],
    'tags' => [
      'plural' => 'Tags',
      'singular' => 'Tag',
    ],
  ],
  'fields' => [
    'address' => 'Adresse',
    'approversRemaining' => 'Verbleibende Stimmen',
    'category' => 'Kategorie',
    'city' => 'Stadt',
    'contributor' => 'Mitwirkender',
    'country' => 'Land',
    'location' => 'Ort',
    'parent' => 'Übergeordnet',
    'postcode' => 'PLZ',
    'province' => 'Provinz',
    'queuedAt' => 'In Warteschlange seit',
    'slug' => 'Slug',
    'status' => 'Status',
    'tag' => 'Tag',
    'title' => 'Titel',
    'type' => 'Typ',
  ],
  'messages' => [
    'approveFailed' => 'Genehmigung fehlgeschlagen.',
    'approved' => 'Revision genehmigt.',
    'geocodeFailed' => 'Geokodierung fehlgeschlagen.',
    'geocodeFilled' => 'Geografische Felder ermittelt.',
    'geocodeNeedQuery' => 'Gib zuerst eine Adresse oder einen Namen ein (mind. 3 Zeichen].',
    'geocodeNoResult' => 'Kein Treffer für diese Suche.',
    'rejectFailed' => 'Ablehnung fehlgeschlagen.',
    'rejectReasonRequired' => 'Gib einen Grund für die Ablehnung an.',
    'rejected' => 'Revision abgelehnt.',
    'relationsSaved' => 'Beziehungen aktualisiert.',
    'sentForReview' => 'Änderungen zur Prüfung eingereicht.',
  ],
  'status' => [
    'expired' => 'Abgelaufen',
    'live' => 'Live',
    'locked' => 'Gesperrt',
    'pendingApprove' => 'Zu genehmigen',
    'pendingWait' => 'In Prüfung',
    'scheduled' => 'Geplant',
    'unpublished' => 'Unveröffentlicht',
  ],
];