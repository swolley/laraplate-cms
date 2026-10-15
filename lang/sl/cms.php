<?php

declare(strict_types=1);

return [
    'actions' => [
        'approve' => 'Odobri',
        'confirmReject' => 'Potrdi zavrnitev',
        'geocode' => 'Geokodiraj',
        'reject' => 'Zavrni',
        'resumeFromRejection' => 'Nadaljuj iz zavrnitve',
    ],
    'entities' => [
        'categories' => [
            'plural' => 'Kategorije',
            'singular' => 'Kategorija',
        ],
        'comments' => [
            'singular' => 'Komentar',
            'plural' => 'Komentarji',
        ],
        'contents' => [
            'plural' => 'Vsebine',
            'singular' => 'Vsebina',
        ],
        'contributors' => [
            'plural' => 'Sodelavci',
            'singular' => 'Sodelavec',
        ],
        'entities' => [
            'singular' => 'Entiteta',
            'plural' => 'Entitete',
        ],
        'locations' => [
            'plural' => 'Lokacije',
            'singular' => 'Lokacija',
        ],
        'presets' => [
            'singular' => 'Prednastavitev',
            'plural' => 'Prednastavitve',
        ],
        'tags' => [
            'plural' => 'Oznake',
            'singular' => 'Oznaka',
        ],
    ],
    'fields' => [
        'address' => 'Naslov',
        'approvers_remaining' => 'Preostali glasovi',
        'category' => 'Kategorija',
        'city' => 'Mesto',
        'contributor' => 'Sodelavec',
        'country' => 'Država',
        'location' => 'Lokacija',
        'parent' => 'Nadrejeno',
        'postcode' => 'Poštna številka',
        'province' => 'Pokrajina',
        'queued_at' => 'V čakalni vrsti od',
        'slug' => 'Slug',
        'status' => 'Stanje',
        'tag' => 'Oznaka',
        'title' => 'Naslov',
        'type' => 'Vrsta',
    ],
    'messages' => [
        'approveFailed' => 'Odobritev ni uspela.',
        'approved' => 'Revizija odobrena.',
        'geocodeFailed' => 'Geokodiranje ni uspelo.',
        'geocodeFilled' => 'Geografska polja so izpolnjena.',
        'geocodeNeedQuery' => 'Najprej vnesite naslov ali ime (vsaj 3 znaki].',
        'geocodeNoResult' => 'Za to iskanje ni zadetkov.',
        'rejectFailed' => 'Zavrnitev ni uspela.',
        'rejectReasonRequired' => 'Navedite razlog za zavrnitev.',
        'rejected' => 'Revizija zavrnjena.',
        'relationsSaved' => 'Povezave posodobljene.',
        'sentForReview' => 'Spremembe poslane v pregled.',
    ],
    'navigation' => [
        'main' => 'CMS',
    ],
    'status' => [
        'expired' => 'Poteklo',
        'live' => 'Objavljeno',
        'locked' => 'Zaklenjeno',
        'pendingApprove' => 'Za odobritev',
        'pendingWait' => 'V pregledu',
        'scheduled' => 'Načrtovano',
        'unpublished' => 'Neobjavljeno',
    ],
];
