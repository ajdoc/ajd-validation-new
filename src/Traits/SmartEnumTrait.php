<?php
namespace AjdVal\Traits;

use UnitEnum;
use BackedEnum;

trait SmartEnumTrait
{
    /**
     * @return array<string>
     */
    public static function names(): array
    {
        $array = [];
        $cases = self::cases();

        foreach ($cases as $case) {
            $array[] = $case->name;
        }

        return $array;
    }

    /**
     * @return array<string|int>
     */
    public static function values(): array
    {
        $array = [];
        $cases = self::cases();

        foreach ($cases as $case) {
            /** @var UnitEnum|BackedEnum $case */
            $array[] = match (true) {
                $case instanceof BackedEnum => $case->value,
                $case instanceof UnitEnum   => $case->name,
            };
        }

        return $array;
    }

    /**
     * @return array<string,string|int>
     */
    public static function array(): array
    {
        return array_combine(self::names(), self::values());
    }

    public function toScalar(): string|int
    {
        if ($this instanceof BackedEnum) {
            return $this->value;
        }

        return $this->name;
    }
}