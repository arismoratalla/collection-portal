<?php

return [
    'default_page_size' => 50,
    'page_sizes' => [25, 50, 100],
    'facet_initial_limit' => 5,
    'facet_load_increment' => 20,
    'initial_facets' => [
        'family', 'genus', 'specific_epithet', 'country', 'state', 'county', 'locality',
    ],
    'map_marker_limit' => 1000,
    // Client marker clustering is appropriate for bounded responses. Move to
    // server-side clusters before raising this beyond roughly 5,000 points.
    'server_clustering_threshold' => 5000,

    'collections' => [
        'fish' => [
            'columns' => [
                'catalog_number', 'field_number', 'family', 'genus',
                'specific_epithet', 'infraspecific_epithet', 'vernacular_name',
                'type_status', 'preparation', 'country', 'state', 'county', 'event_year',
            ],
            'facets' => [
                'family', 'genus', 'specific_epithet', 'infraspecific_epithet',
                'scientific_name', 'vernacular_name', 'type_status', 'continent',
                'country', 'state', 'county', 'locality', 'water_body', 'field_number',
                'event_year', 'event_month', 'event_day', 'verbatim_event_date',
                'sampling_protocol', 'preparation', 'identified_by', 'verbatim_depth',
            ],
        ],
    ],

    'default' => [
        'columns' => [
            'catalog_number', 'scientific_name', 'family', 'locality', 'state',
            'country', 'event_year', 'type_status',
        ],
        'facets' => [
            'family', 'genus', 'scientific_name', 'type_status', 'country', 'state',
            'county', 'locality', 'field_number', 'event_year', 'preparation',
        ],
    ],

    'facet_definitions' => [
        'family' => ['label' => 'Family', 'group' => 'Taxonomy', 'column' => 'taxa.family', 'relation' => 'taxon'],
        'genus' => ['label' => 'Genus', 'group' => 'Taxonomy', 'column' => 'taxa.genus', 'relation' => 'taxon'],
        'specific_epithet' => ['label' => 'Species / Specific Epithet', 'group' => 'Taxonomy', 'column' => 'taxa.specific_epithet', 'relation' => 'taxon'],
        'infraspecific_epithet' => ['label' => 'Subspecies', 'group' => 'Taxonomy', 'column' => 'taxa.infraspecific_epithet', 'relation' => 'taxon'],
        'scientific_name' => ['label' => 'Scientific Name', 'group' => 'Taxonomy', 'column' => 'taxa.scientific_name', 'relation' => 'taxon'],
        'vernacular_name' => ['label' => 'Common Name / Vernacular Name', 'group' => 'Taxonomy', 'column' => 'taxa.vernacular_name', 'relation' => 'taxon'],
        'type_status' => ['label' => 'Type Status', 'group' => 'Taxonomy', 'column' => 'specimens.type_status', 'relation' => 'specimen'],
        'continent' => ['label' => 'Continent / Ocean', 'group' => 'Geography', 'geography_type' => 'continent'],
        'country' => ['label' => 'Country', 'group' => 'Geography', 'geography_type' => 'country'],
        'state' => ['label' => 'State / Province', 'group' => 'Geography', 'geography_type' => 'state'],
        'county' => ['label' => 'County', 'group' => 'Geography', 'geography_type' => 'county'],
        'locality' => ['label' => 'Locality', 'group' => 'Geography', 'column' => 'localities.locality', 'relation' => 'locality'],
        'water_body' => ['label' => 'Water Body', 'group' => 'Geography', 'column' => 'localities.water_body', 'relation' => 'locality'],
        'verbatim_depth' => ['label' => 'Depth of Capture', 'group' => 'Geography', 'column' => 'localities.verbatim_depth', 'relation' => 'locality'],
        'field_number' => ['label' => 'Field Number', 'group' => 'Collecting Event', 'column' => 'collecting_events.field_number', 'relation' => 'event'],
        'event_year' => ['label' => 'Event Year', 'group' => 'Collecting Event', 'column' => 'collecting_events.event_year', 'relation' => 'event'],
        'event_month' => ['label' => 'Event Month', 'group' => 'Collecting Event', 'column' => 'collecting_events.event_month', 'relation' => 'event'],
        'event_day' => ['label' => 'Event Day', 'group' => 'Collecting Event', 'column' => 'collecting_events.event_day', 'relation' => 'event'],
        'verbatim_event_date' => ['label' => 'Verbatim Event Date', 'group' => 'Collecting Event', 'column' => 'collecting_events.verbatim_event_date', 'relation' => 'event'],
        'sampling_protocol' => ['label' => 'Sampling Protocol', 'group' => 'Collecting Event', 'column' => 'collecting_events.sampling_protocol', 'relation' => 'event'],
        'preparation' => ['label' => 'Preparations', 'group' => 'Preparation', 'column' => 'preparations.source_value', 'relation' => 'preparation'],
        'identified_by' => ['label' => 'Identified By', 'group' => 'Determination', 'column' => 'determinations.determiner_name', 'relation' => 'determination'],
    ],
];
