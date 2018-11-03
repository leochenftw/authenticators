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
use Leochenftw\Model\LinkedinProfile;

class LinkedinAuthController extends PageController
{
    public function index()
    {
        $client_id  =   Config::inst()->get('Authenticators', 'Linkedin')['client_id'];
        $re_url     =   Director::absoluteBaseURL() . 'auth/in';
        $endpoint   =   Config::inst()->get('Authenticators', 'Linkedin')['endpoint'];

        if (($code = $this->request->getVar('code')) && ($csrf = $this->request->getVar('state'))) {
            if (session_id() == $csrf) {
                try {
                    $client     =   new Client(['base_uri' => $endpoint]);
                    $access     =   Config::inst()->get('Authenticators', 'Linkedin')['access'];
                    $secret     =   Config::inst()->get('Authenticators', 'Linkedin')['client_secret'];
                    $response   =   $client->request('GET', $access, [
                        'query' =>  [
                            'client_id'     =>  $client_id,
                            'client_secret' =>  $secret,
                            'code'          =>  $code,
                            'redirect_uri'  =>  $re_url,
                            'grant_type'    =>  'authorization_code'
                        ]
                    ]);

                    $response   =   json_decode($response->getBody()->getContents());

                    if (empty($response->error)) {
                        $access_token   =   $response->access_token;
                        $profile_api    =   Config::inst()->get('Authenticators', 'Linkedin')['profile_api'];
                        $fields         =   Config::inst()->get('Authenticators', 'Linkedin')['fields'];

                        $client         =   new Client();
                        $response       =   $client->request(
                                                'GET',
                                                $profile_api . $fields . '?format=json',
                                                [
                                                    'headers'   =>  [
                                                        'Authorization' => "Bearer {$access_token}"
                                                    ]
                                                ]
                                            );

                        $response       =   json_decode($response->getBody()->getContents());


                        if ($profile = LinkedinProfile::get()->filter(['LinkedinID' => $response->id])->first()) {
                            $member =   $profile->Member();

                            if (empty($member)) {
                                $member =   $profile->create_member($response);
                            }

                            Injector::inst()->get(IdentityStore::class)->logIn($member);
                            return $this->redirect('/');
                        }

                        $profile                =   LinkedinProfile::create();
                        $profile->LinkedinID    =   $response->id;
                        $member                 =   $profile->create_member($response);

                        Injector::inst()->get(IdentityStore::class)->logIn($member);
                        return $this->redirect('/');
                    }
                } catch (ClientException $e) {
                    print $e;
                }
            }

        }

        if (empty(session_id())) {
            session_start();
        }

        $oauth      =   Config::inst()->get('Authenticators', 'Linkedin')['oauth'];
        $scopes     =   Config::inst()->get('Authenticators', 'Linkedin')['scopes'];

        $query      =   [
            'response_type' =>  'code',
            'client_id'     =>  $client_id,
            'redirect_uri'  =>  $re_url,
            'state'         =>  session_id(),
            'scope'         =>  $scopes
        ];

        $query      =   http_build_query($query, null, '&', PHP_QUERY_RFC3986);


        $url        =   $endpoint . $oauth . '?' . $query;

        return $this->redirect($url);

    }
}
