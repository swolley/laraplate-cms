<?php

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
    'contents' => [
      'plural' => 'Contents',
      'singular' => 'Content',
    ],
    'contributors' => [
      'plural' => 'Contributors',
      'singular' => 'Contributor',
    ],
    'locations' => [
      'plural' => 'Locations',
      'singular' => 'Location',
    ],
    'tags' => [
      'plural' => 'Tags',
      'singular' => 'Tag',
    ],
  ],
  'fields' => [
    'address' => 'Address',
    'approversRemaining' => 'Votes remaining',
    'category' => 'Category',
    'city' => 'City',
    'contributor' => 'Contributor',
    'country' => 'Country',
    'location' => 'Location',
    'parent' => 'Parent',
    'postcode' => 'Postcode',
    'province' => 'Province',
    'queuedAt' => 'Queued since',
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