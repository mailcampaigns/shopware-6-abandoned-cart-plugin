<?php

declare(strict_types=1);

namespace MailCampaigns\AbandonedCart\Exception;

use InvalidArgumentException;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
class InvalidCartDataException extends InvalidArgumentException
{
    public function __construct(string $key, string $expectedValue, $actualValue)
    {
        $actualValueType = gettype($actualValue);

        if ($actualValueType === 'object') {
            $actualValueType = get_class($actualValue);
        }

        parent::__construct(
            "Unexpected value for '$key': expecting '$expectedValue', got '$actualValueType'."
        );
    }
}
