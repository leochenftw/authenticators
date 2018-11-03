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
use Leochenftw\Model\GithubProfile;

class GithubAuthController extends PageController
{
    public function index()
    {
        $app_id     =   Config::inst()->get('Authenticators', 'Github')['client_id'];
        $re_url     =   Director::absoluteBaseURL() . 'auth/github';
        $endpoint   =   Config::inst()->get('Authenticators', 'Github')['endpoint'];

        if (($code = $this->request->getVar('code')) && ($csrf = $this->request->getVar('state'))) {
            if (session_id() == $csrf) {

                $access     =   Config::inst()->get('Authenticators', 'Github')['access'];
                $secret     =   Config::inst()->get('Authenticators', 'Github')['client_secret'];

                try {
                    $client     =   new Client(['base_uri' => $endpoint]);
                    $response   =   $client->request('POST', $access, [
                        'headers'       =>  [
                            'Accept'    => 'application/json'
                        ],
                        'form_params'   =>  [
                            'client_id'     =>  $app_id,
                            'redirect_uri'  =>  $re_url,
                            'client_secret' =>  $secret,
                            'code'          =>  $code,
                            'state'         =>  session_id()
                        ]
                    ]);


                    $response           =   json_decode($response->getBody()->getContents());
                    if (empty($response->error)) {
                        $access_token       =   $response->access_token;
                        $profile_endpoint   =   Config::inst()->get('Authenticators', 'Github')['profile_endpoint'];
                        $profile_api        =   Config::inst()->get('Authenticators', 'Github')['profile_api'];

                        $client             =   new Client(['base_uri' => $profile_endpoint]);
                        $response           =   $client->request(
                                                    'GET',
                                                    $profile_api,
                                                    [
                                                        'headers'   =>  [
                                                            'Authorization' => "Bearer {$access_token}"
                                                        ]
                                                    ]
                                                );

                        $raw_data           =   $response->getBody()->getContents();
                        $response           =   json_decode($raw_data);

                        if ($profile = GithubProfile::get()->filter(['github_id' => $response->id])->first()) {
                            $member =   $profile->Member();

                            if (empty($member)) {
                                $member =   $profile->create_member($response);
                            }

                            Injector::inst()->get(IdentityStore::class)->logIn($member);
                            return $this->redirect('/');
                        }

                        $profile                    =   GithubProfile::create();
                        $profile->github_id         =   $response->id;
                        $profile->github_node_id    =   $response->node_id;
                        $profile->github_url        =   $response->html_url;
                        $profile->raw_data          =   $raw_data;
                        $member                     =   $profile->create_member($response);

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

        $oauth  =   Config::inst()->get('Authenticators', 'Github')['oauth'];
        $scopes =   Config::inst()->get('Authenticators', 'Github')['scopes'];

        $query  =   [
            'client_id'     =>  $app_id,
            'redirect_uri'  =>  $re_url,
            'state'         =>  session_id(),
            'scope'         =>  $scopes
        ];

        $query  =   http_build_query($query, null, '&', PHP_QUERY_RFC3986);

        $url    =   $endpoint . $oauth . '?' . $query;

        return $this->redirect($url);
    }
}
