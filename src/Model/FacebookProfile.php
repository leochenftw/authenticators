<?php

namespace Leochenftw\Model;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use Leochenftw\Model\SocialProfile;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class FacebookProfile extends SocialProfile
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'FacebookProfile';
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'fb_id' =>  'Varchar(32)'
    ];

    private static $indexes = [
        'fb_id' =>  true
    ];

    public function create_member(&$data)
    {
        if ($member = Member::get()->filter(['Email' => $data->email])->first()) {
            $this->MemberID =   $member->ID;
            $this->write();
            return $member;
        }

        $member                 =   Member::create();
        $member->FirstName      =   $data->first_name;
        $member->Surname        =   $data->last_name;
        $member->Email          =   $data->email;

        // if ($member->hasField('Nickname')) {
        //     $member->Nickname   =   $data->short_name;
        // }

        $member->create_portrait($data->picture->data->url, $data->id . '-portrait.jpg');

        $this->MemberID =   $member->ID;
        $this->write();

        return $member;
    }
}
