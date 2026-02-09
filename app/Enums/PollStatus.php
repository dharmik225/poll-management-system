<?php

namespace App\Enums;

enum PollStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    /**
     * Get the label for the enum value
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::ARCHIVED => 'Archived',
        };
    }

    /**
     * Get the color for UI representation
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PUBLISHED => 'green',
            self::ARCHIVED => 'yellow',
        };
    }

    /**
     * Get the icon for UI representation
     */
    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'heroicon-o-pencil',
            self::PUBLISHED => 'heroicon-o-check-circle',
            self::ARCHIVED => 'heroicon-o-archive-box',
        };
    }

    /**
     * Get all enum values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all labels as associative array
     */
    public static function labels(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }

    /**
     * Get options for select dropdown
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'color' => $case->color(),
                'icon' => $case->icon(),
            ])
            ->toArray();
    }

    /**
     * Check if the enum is in a specific state
     */
    public function is(self $status): bool
    {
        return $this === $status;
    }

    /**
     * Check if the enum is in any of the given states
     */
    public function isAny(self ...$statuses): bool
    {
        return in_array($this, $statuses, true);
    }

    /**
     * Check if poll can be edited
     */
    public function canEdit(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if poll can receive responses
     */
    public function canReceiveResponses(): bool
    {
        return $this === self::PUBLISHED;
    }

    /**
     * Check if poll can be published
     */
    public function canPublish(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if poll can be archived
     */
    public function canArchive(): bool
    {
        return $this->isAny(self::DRAFT, self::PUBLISHED);
    }

    /**
     * Safely get enum from string value
     */
    public static function fromValue(?string $value): ?self
    {
        return self::tryFrom($value);
    }
}
