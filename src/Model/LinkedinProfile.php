<?php

namespace Leochenftw\Model;

use SilverStripe\ORM\DataObject;
use Leochenftw\Model\SocialProfile;
use SilverStripe\Security\Member;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class LinkedinProfile extends SocialProfile
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'LinkedinProfile';
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'LinkedinID'    =>  'Varchar(16)',
        'LinkedinURL'   =>  'Varchar(256)'
    ];

    private static $indexes = [
        'LinkedinID'  =>  true
    ];

    public function create_member(&$data)
    {
        $member                 =   Member::create();
        $member->FirstName      =   $data->firstName;
        $member->Surname        =   $data->lastName;
        $member->Email          =   $data->emailAddress;

        $member->create_portrait($data->pictureUrl, $data->id . '-portrait.jpg');

        $this->MemberID =   $member->ID;
        $this->write();

        return $member;
    }
}
