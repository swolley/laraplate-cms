<?php

declare(strict_types=1);

namespace Modules\Cms\Casts;

/**
 * CMS dynamic field kinds (forms / dynamic content), distinct from Core search schema FieldType.
 */
enum FieldType: string
{
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case SWITCH = 'switch';
    case CHECKBOX = 'checkbox';
    case DATETIME = 'datetime';
    case NUMBER = 'number';
    case OBJECT = 'object';
    case EDITOR = 'editor';
    case ARRAY = 'array';
    case EMAIL = 'email';
    case URL = 'url';
    case SELECT = 'select';
    case RADIO = 'radio';
    case PHONE = 'phone';

    public function getRule(): string
    {
        return match ($this) {
            self::TEXT, self::TEXTAREA, self::PHONE => 'string',
            self::SWITCH => 'boolean',
            self::CHECKBOX, self::ARRAY => 'array',
            self::DATETIME => 'date',
            self::NUMBER => 'number',
            self::OBJECT, self::EDITOR => 'json',
            self::EMAIL => 'email',
            self::URL => 'url',
            self::SELECT, self::RADIO => '',
        };
    }

    public function isTextual(): bool
    {
        return match ($this) {
            self::TEXT, self::TEXTAREA, self::EDITOR => true,
            default => false,
        };
    }
}
