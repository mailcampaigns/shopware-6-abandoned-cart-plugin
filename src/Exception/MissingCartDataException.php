<?php

namespace MailCampaigns\AbandonedCart\Exception;

use LogicException;

/**
 * @author Twan Haverkamp <twan@mailcampaigns.nl>
 */
class MissingCartDataException extends LogicException
{
    public function __construct(string $requiredValue)
    {
        parent::__construct("Required value for '$requiredValue' is missing.");
    }
}
