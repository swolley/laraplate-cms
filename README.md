<p>&nbsp;</p>
<p align="center">
	<a href="https://github.com/swolley" target="_blank">
		<img src="https://raw.githubusercontent.com/swolley/images/refs/heads/master/logo_laraplate.png" width="400" alt="Laravel Logo" />
    </a>
</p>
<p>&nbsp;</p>

> **Caution**: This package is a **work in progress**. **Don't use this in production or use at your own risk**—no guarantees are provided... or better yet, collaborate with me to create the definitive Laravel boilerplate; that's the right place to instroduce your ideas. Let me know your ideas...

## Table of Contents

-   [Description](#description)
-   [Installation](#installation)
-   [Configuration](#configuration)
-   [Features](#features)
-   [Scripts](#scripts)
-   [Contributing](#contributing)
-   [License](#license)

## Description

The Cms Module contains all the necessary functionalities to build a new Cms system.

## Installation

If you want to add this module to your project, you can use the `joshbrw/laravel-module-installer` package.

Laraplate-cms is dependent on laraplate-core. Add both the repositories to your `composer.json` file:

```json
"repositories": [
    {
        "type": "composer",
        "url": "https://github.com/swolley/laraplate-core.git"
    },
    {
        "type": "composer",
        "url": "https://github.com/swolley/laraplate-cms.git"
    }
]
```

```bash
composer require joshbrw/laravel-module-installer swolley/laraplate-core swolley/laraplate-cms
```

Then, you can install the module by running the following command:

```bash
php artisan module:install Core
php artisan module:install Cms
```

## Configuration

```env
#cms
CMS_SLUGGER='\Illuminate\Support\Str::slug'	#common slugger function for entities and content types

#geocoding
GEOCODING_PROVIDER=nominatim					#geocoding provider (nominatim, google)
GEOCODING_API_KEY=								#geocoding api key

#media library
MEDIA_DISK=public								#disk name for media files
MEDIA_QUEUE=media-library						#queue name for media files
QUEUE_CONNECTION=redis							#queue connection for media files
QUEUE_CONVERSIONS_BY_DEFAULT=true				#queue conversions by default
QUEUE_CONVERSIONS_AFTER_DB_COMMIT=true			#queue conversions after database commit
MAX_FILE_SIZE=10485760							#max file size for media files (10MB)
IMAGE_DRIVER=gd									#image driver for media files (gd, imagick)
FFMPEG_PATH=/usr/bin/ffmpeg						#ffmpeg path for media files
FFPROBE_PATH=/usr/bin/ffprobe					#ffprobe path for media files
MEDIA_DOWNLOADER_SSL=true						#enable SSL for media downloads
ENABLE_MEDIA_LIBRARY_VAPOR_UPLOADS=false		#enable vapor uploads for media files
FORCE_MEDIA_LIBRARY_LAZY_LOADING=true			#force lazy loading for media files
MEDIA_PREFIX=									#media prefix for storage
```

## Features

### Requirements

-   PHP >= 8.4
-   Laravel 12.0+
-   **PHP Extensions:**

    -   `ext-gd`: Provides support for image processing.
    -   `ext-exif`: Provides support for EXIF data extraction from images.
    -   `ext-imagick` (optional): Provides support for additional image processing.

### Installed Packages

The Cms Module utilizes several packages to enhance its functionality. Below is a list of the key packages included in the `composer.json` file:

-   **Parent-Child Relationships:**

    -   [tightenco/parental](https://github.com/tightenco/parental): Provides a way to manage parent-child relationships in models.

-   **Media Management:**

    -   [spatie/laravel-medialibrary](https://github.com/spatie/laravel-medialibrary): Provides a way to manage media files in models.

-   **Sorting and Ordering:**

    -   [spatie/eloquent-sortable](https://github.com/spatie/eloquent-sortable): Provides a way to make Eloquent models sortable.

-   **Video Processing:**

    -   [php-ffmpeg/php-ffmpeg](https://github.com/PHP-FFMpeg/PHP-FFMpeg): Provides a way to process video files using FFmpeg.

-   **Development and Testing:**

    -   [pestphp/pest](https://github.com/pestphp/pest): A testing framework for PHP.
    -   [pestphp/pest-plugin-laravel](https://github.com/pestphp/pest-plugin-laravel): Adds Laravel-specific testing features to Pest.
    -   [pestphp/pest-plugin-stressless](https://github.com/pestphp/pest-plugin-stressless): Stress testing plugin for Pest.
    -   [pestphp/pest-plugin-type-coverage](https://github.com/pestphp/pest-plugin-type-coverage): Type coverage plugin for Pest.
    -   [laravel/pint](https://github.com/laravel/pint): Laravel's code style fixer.
    -   [nunomaduro/phpinsights](https://github.com/nunomaduro/phpinsights): PHP quality checker.
    -   [peckphp/peck](https://github.com/peckphp/peck): PHP typo checker.
    -   [rector/rector](https://github.com/rectorphp/rector): Automated PHP code refactoring tool.

### Additional Functionalities

The Cms Module includes built-in features such as:

-   Dynamic Content types management
-   Media library management with advanced image processing
-   Models tagging and categorization
-   Content versioning and history
-   Authors and signatures management
-   Export Templates management in blade format
-   AI integration for content generation
-   Geocoding services integration
-   Video processing and thumbnail generation
-   Responsive image generation
-   Image optimization and compression
-   Media file organization and sorting
-   Parent-child content relationships
-   Content approval workflows
-   Multi-language content support
-   SEO-friendly URL generation
-   Content scheduling and publishing
-   Media file conversions and transformations
-   Lazy loading for improved performance
-   Vapor uploads support for cloud deployments

## Scripts

The Cms Module provides several useful scripts for development and maintenance:

### Code Quality and Testing

```bash
# Run all tests and quality checks
composer test

# Run specific test suites
composer test:unit          # Run unit tests with coverage
composer test:type-coverage # Check type coverage (target: 100%)
composer test:typos         # Check for typos in code
composer test:lint          # Check code style
composer test:types         # Run PHPStan analysis
composer test:refactor      # Run Rector refactoring
```

### Code Quality Tools

```bash
# Code style and IDE helpers
composer lint               # Fix code style and generate IDE helpers

# Static analysis
composer check              # Run PHPStan analysis
composer fix                # Run PHPStan analysis with auto-fix
composer refactor           # Run Rector refactoring
```

### Version Management

```bash
# Version bumping
composer version:major      # Bump major version
composer version:minor      # Bump minor version
composer version:patch      # Bump patch version
```

### Development Setup

```bash
# Setup Git hooks
composer setup:hooks
```

### Other References

Cms Module takes inspiration from, but does not directly require, libraries such as:

-   [spatie/laravel-tags](https://github.com/spatie/laravel-tags): Provides a way to manage tags in models.

## Contributing

If you want to contribute to this project, follow these steps:

1. Fork the repository.
2. Create a new branch for your feature or correction.
3. Send a pull request.

## License

Cms Module is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## TODO and FIXME

This section tracks all pending tasks and issues that need to be addressed in the Cms Module.

### High Priority

- [ ] **Dynamic Content Embedding** - `Modules/Cms/app/Models/Content.php:314`
  - TODO: How to extract embedding for dynamic contents?
  - Need to implement proper embedding extraction for AI-powered content analysis
  - Related to vector search and content indexing functionality

### Medium Priority

- [ ] **Content Embedding Implementation**
  - Need to implement embedding generation for dynamic content components
  - Should integrate with Core Module's vector search capabilities
  - Consider implementing automatic embedding updates when content changes

### Low Priority

- [ ] **Content Type Optimization**
  - Review and optimize content type handling
  - Consider implementing caching for frequently accessed content types
  - Evaluate performance improvements for large content collections

### Notes

- Most TODO items are related to AI integration and content optimization
- The main focus is on implementing proper embedding extraction for dynamic contents
- Should coordinate with Core Module's vector search implementation
- Consider implementing automated content analysis and tagging features
