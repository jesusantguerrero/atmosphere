<?php

namespace App\Domains\Housing\Contracts;

/**
 * Fixed household areas a chore can live in (Tody-style). Chores without an
 * area, or with a key that is no longer listed here, count as General.
 */
enum ChoreArea: string
{
    case Kitchen = 'kitchen';
    case Bathroom = 'bathroom';
    case Bedroom = 'bedroom';
    case Living = 'living';
    case Laundry = 'laundry';
    case Kids = 'kids';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::Kitchen => 'Cocina',
            self::Bathroom => 'Baño',
            self::Bedroom => 'Dormitorio',
            self::Living => 'Sala',
            self::Laundry => 'Lavadero',
            self::Kids => 'Niños',
            self::General => 'General',
        };
    }

    public static function fromStored(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::General;
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_map(fn (self $area) => $area->value, self::cases());
    }
}
