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
class GithubProfile extends SocialProfile
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'GithubProfile';
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'github_id'         =>  'Varchar(32)',
        'github_node_id'    =>  'Varchar(32)',
        'github_url'        =>  'Varchar(256)'
    ];

    private static $indexes = [
        'github_id' =>  true
    ];

    public function create_member(&$data)
    {
        if ($member = Member::get()->filter(['Email' => (!empty($data->email) ? $data->email : $data->node_id)])->first()) {
            $this->MemberID =   $member->ID;
            $this->write();
            return $member;
        }

        $member                 =   Member::create();
        $member->FirstName      =   $this->get_first_name($data->name);
        $member->Surname        =   $this->get_last_name($data->name);
        $member->Email          =   !empty($data->email) ? $data->email : $data->node_id;

        $member->create_portrait($data->avatar_url, $data->id . '-portrait.jpg');

        $this->MemberID =   $member->ID;
        $this->write();

        return $member;
    }

    private function get_last_name($name)
    {
        if (!empty($name)) {
            $names  =   explode(' ', trim($name));
            if (count($names) > 1) {
                return $names[count($names) - 1];
            }
        }

        return '';
    }

    private function get_first_name($name)
    {
        if (!empty($name)) {
            $names  =   explode(' ', trim($name));

            return $names[0];
        }

        return '';
    }
}
