<?php

declare(strict_types=1);

return [
    'actions' => [
        'approve' => 'Approve',
        'confirmReject' => 'Confirm rejection',
        'geocode' => 'Geocode',
        'reject' => 'Reject',
        'resumeFromRejection' => 'Resume from rejection',
    ],
    'entities' => [
        'categories' => [
            'plural' => 'Categories',
            'singular' => 'Category',
        ],
        'comments' => [
            'plural' => 'Comments',
            'singular' => 'Comment',
        ],
        'contents' => [
            'plural' => 'Contents',
            'singular' => 'Content',
        ],
        'contributors' => [
            'plural' => 'Contributors',
            'singular' => 'Contributor',
        ],
        'entities' => [
            'plural' => 'Entities',
            'singular' => 'Entity',
        ],
        'locations' => [
            'plural' => 'Locations',
            'singular' => 'Location',
        ],
        'presets' => [
            'plural' => 'Presets',
            'singular' => 'Preset',
        ],
        'tags' => [
            'plural' => 'Tags',
            'singular' => 'Tag',
        ],
    ],
    'fields' => [
        'address' => 'Address',
        'approvers_remaining' => 'Votes remaining',
        'category' => 'Category',
        'city' => 'City',
        'contributor' => 'Contributor',
        'country' => 'Country',
        'location' => 'Location',
        'parent' => 'Parent',
        'postcode' => 'Postcode',
        'province' => 'Province',
        'queued_at' => 'Queued since',
        'slug' => 'Slug',
        'status' => 'Status',
        'tag' => 'Tag',
        'title' => 'Title',
        'type' => 'Type',
    ],
    'messages' => [
        'approveFailed' => 'Approval failed.',
        'approved' => 'Revision approved.',
        'geocodeFailed' => 'Geocoding failed.',
        'geocodeFilled' => 'Geographic fields resolved.',
        'geocodeNeedQuery' => 'Enter an address or name first (min 3 chars].',
        'geocodeNoResult' => 'No match found for this query.',
        'rejectFailed' => 'Rejection failed.',
        'rejectReasonRequired' => 'Provide a reason to reject.',
        'rejected' => 'Revision rejected.',
        'relationsSaved' => 'Relations updated.',
        'sentForReview' => 'Changes submitted for review.',
    ],
    'navigation' => [
        'main' => 'CMS',
    ],
    'status' => [
        'expired' => 'Expired',
        'live' => 'Live',
        'locked' => 'Locked',
        'pendingApprove' => 'To approve',
        'pendingWait' => 'In review',
        'scheduled' => 'Scheduled',
        'unpublished' => 'Unpublished',
    ],
];
