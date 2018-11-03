<?php

namespace Leochenftw\Model;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use Leochenftw\Model\SocialProfile;
use Leochenftw\Debugger;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class WechatProfile extends SocialProfile
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'WechatProfile';
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'openid'    =>  'Varchar(40)',
        'unionid'   =>  'Varchar(40)',
        'nickname'  =>  'Varchar(32)'
    ];

    private static $indexes = [
        'openid'    =>  true,
        'unionid'   =>  true
    ];

    public function create_member(&$data)
    {
        if ($member = Member::get()->filter(['Email' => $data->openid])->first()) {
            $this->MemberID =   $member->ID;
            $this->write();
            return $member;
        }

        $member                 =   Member::create();
        $member->FirstName      =   $data->nickname;
        $member->Email          =   $data->openid;

        if (!empty($data->headimgurl)) {
            $segments       =   parse_url($data->headimgurl);
            $paths          =   explode('/', $segments['path']);

            $paths[count($paths) - 1]    =   0;

            $src            =   'https://' . $segments['host'] . implode('/', $paths);

            $member->create_portrait($src, $data->unionid . '-portrait.jpg');
        } else {
            $member->write();
        }

        $this->MemberID =   $member->ID;
        $this->write();

        return $member;
    }
}
