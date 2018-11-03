<?php

namespace Leochenftw\Model;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class SocialProfile extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'SocialProfile';
    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Member'    =>  Member::class
    ];
}
