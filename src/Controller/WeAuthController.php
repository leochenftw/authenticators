<?php

namespace Leochenftw\Controller;

use PageController;

use GuzzleHttp\Client;
use Leochenftw\Debugger;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Core\Injector\Injector;
use GuzzleHttp\Exception\ClientException;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;
use Leochenftw\Model\WechatProfile;

class WeAuthController extends PageController
{
    public function index()
    {
        $app_id     =   Config::inst()->get('Authenticators', 'Wechat')['appid'];
        $re_url     =   Director::absoluteBaseURL() . 'auth/we';

        if (($code = $this->request->getVar('code')) && ($csrf = $this->request->getVar('state'))) {
            if (session_id() == $csrf) {

                $access_endpoint    =   Config::inst()->get('Authenticators', 'Wechat')['access_endpoint'];
                $access             =   Config::inst()->get('Authenticators', 'Wechat')['access'];
                $secret             =   Config::inst()->get('Authenticators', 'Wechat')['secret'];

                try {
                    $client     =   new Client(['base_uri' => $access_endpoint]);
                    $response   =   $client->request('GET', $access, [
                        'query' =>  [
                            'appid'         =>  $app_id,
                            'secret'        =>  $secret,
                            'code'          =>  $code,
                            'grant_type'    =>  'authorization_code'
                        ]
                    ]);

                    $response   =   json_decode($response->getBody()->getContents());

                    if (empty($response->errcode)) {
                        $access_token           =   $response->access_token;
                        $openid                 =   $response->openid;
                        $unionid                =   $response->unionid;

                        if ($profile = WechatProfile::get()->filter(['unionid' => $response->unionid])->first()) {
                            $member =   $profile->Member();

                            if (empty($member)) {
                                if ($data = $this->get_profile_details($access_token, $openid)) {
                                    $member =   $profile->create_member($data);
                                    Injector::inst()->get(IdentityStore::class)->logIn($member);
                                    return $this->redirect('/');
                                }
                            }
                        }

                        $profile                =   WechatProfile::create();
                        $profile->openid        =   $response->openid;
                        $profile->unionid       =   $response->unionid;

                        if ($data = $this->get_profile_details($access_token, $openid)) {
                            $profile->nickname  =   $data->nickname;
                            $member =   $profile->create_member($data);
                            Injector::inst()->get(IdentityStore::class)->logIn($member);
                            return $this->redirect('/');
                        }

                    }
                } catch (ClientException $e) {
                    print $e;
                }
            }

        }

        if (empty(session_id())) {
            session_start();
        }

        $oauth  =   Config::inst()->get('Authenticators', 'Wechat')['oauth'];
        $scopes =   Config::inst()->get('Authenticators', 'Wechat')['scopes'];

        $query  =   [
            'appid'         =>  $app_id,
            'redirect_uri'  =>  $re_url,
            'response_type' =>  'code',
            'scope'         =>  $scopes,
            'state'         =>  session_id(),
        ];

        $query  =   http_build_query($query, null, '&', PHP_QUERY_RFC3986);

        $url    =   $oauth . '?' . $query . '#wechat_redirect';

        return $this->redirect($url);
        // Debugger::inspect($url);
    }

    private function get_profile_details($access_token, $openid)
    {
        $profile_endpoint   =   Config::inst()->get('Authenticators', 'Wechat')['profile_endpoint'];
        $profile_api        =   Config::inst()->get('Authenticators', 'Wechat')['profile_api'];
        try {
            $client     =   new Client(['base_uri' => $profile_endpoint]);
            $response   =   $client->request('GET', $profile_api, [
                'query' =>  [
                    'access_token'  =>  $access_token,
                    'openid'        =>  $openid
                ]
            ]);

            return json_decode($response->getBody()->getContents());

        } catch (ClientException $e) {
            print $e;
        }

        return false;
    }
}
