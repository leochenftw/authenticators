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
use Leochenftw\Model\FacebookProfile;

class FbAuthController extends PageController
{
    // 169085497368018
    public function index()
    {
        $app_id     =   Config::inst()->get('Authenticators', 'Facebook')['app_id'];
        $re_url     =   Director::absoluteBaseURL() . 'auth/fb';

        if (($code = $this->request->getVar('code')) && ($csrf = $this->request->getVar('state'))) {
            if (session_id() == $csrf) {
                $access     =   Config::inst()->get('Authenticators', 'Facebook')['access'];
                $secret     =   Config::inst()->get('Authenticators', 'Facebook')['app_secret'];

                $client     =   new Client(['base_uri' => $access]);
                try {
                    $response   =   $client->request('GET', 'access_token', [
                        'query' =>  [
                            'client_id'     =>  $app_id,
                            'redirect_uri'  =>  $re_url,
                            'client_secret' =>  $secret,
                            'code'          =>  $code
                        ]
                    ]);

                    $response       =   json_decode($response->getBody()->getContents());

                    $access_token   =   $response->access_token;
                    $graph_api      =   Config::inst()->get('Authenticators', 'Facebook')['graph_api'];
                    $fields         =   Config::inst()->get('Authenticators', 'Facebook')['fields'];

                    $client         =   new Client(['base_uri' => $graph_api]);
                    $response       =   $client->request('GET', 'me?fields=' . $fields . '&access_token=' . $access_token);
                    $response       =   json_decode($response->getBody()->getContents());

                    if ($profile = FacebookProfile::get()->filter(['fb_id' => $response->id])->first()) {
                        $member =   $profile->Member();

                        if (empty($member)) {
                            $member =   $profile->create_member($response);
                        }

                        Injector::inst()->get(IdentityStore::class)->logIn($member);
                        return $this->redirect('/');
                    }

                    $profile        =   FacebookProfile::create();
                    $profile->fb_id =   $response->id;
                    $member         =   $profile->create_member($response);

                    Injector::inst()->get(IdentityStore::class)->logIn($member);
                    return $this->redirect('/');

                } catch (ClientException $e) {
                    print $e;
                }
            }

        }

        if (empty(session_id())) {
            session_start();
        }

        $oauth  =   Config::inst()->get('Authenticators', 'Facebook')['oauth'];
        $scopes =   Config::inst()->get('Authenticators', 'Facebook')['scopes'];

        $query  =   [
            'client_id'     =>  $app_id,
            'redirect_uri'  =>  $re_url,
            'state'         =>  session_id(),
            'scope'         =>  $scopes
        ];

        $query  =   http_build_query($query, null, '&', PHP_QUERY_RFC3986);

        $url    =   $oauth . '?' . $query;

        return $this->redirect($url);
    }
}
