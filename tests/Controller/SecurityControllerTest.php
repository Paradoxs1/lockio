<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\BrowserKit\Cookie;

class SecurityControllerTest extends WebTestCase
{
    private $client = null;

    public function setUp()
    {
        $this->client = static::createClient([
            'environment' => 'test'
        ]);
    }

    const USER_FOR_LOGIN = 'php.js.16@gmail.com';

    const RIGHT_PASSWORD = '11111';

    const WRONG_PASSWORD = 'WRONG_PASSWORD';

    const HASH = '058a316c48353dbfbc66c90d97d90b52';

    const PAGES_TO_TEST = [
        '/',
        '/bucket',
        '/profile',
        '/invoices',
    ];

    const USERS_TO_TEST = [
        'ROLE_USER' => [
            'email' => self::USER_FOR_LOGIN,
            'allowedPages' => [
                '/',
                '/bucket',
                '/profile',
                '/invoices',
            ]
        ],
    ];

//    private function logIn()
//    {
//        $session = $this->client->getContainer()->get('session');
//
//        $firewallName = 'main';
//        $firewallContext = 'main';
//
//        $user = $this->client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => self::USER_FOR_LOGIN]);
//
//        $token = new UsernamePasswordToken($user, null, $firewallName, $user->getRoles());
//        $session->set('_security_' . $firewallContext, serialize($token));
//        $session->save();
//
//        $cookie = new Cookie ($session->getName(), $session->getId());
//        $this->client->getCookieJar()->set($cookie);
//    }

    /**
     * @dataProvider provideUrls
     */
    public function testPageIsSuccessful($url)
    {
        $client = static::createClient([
            'environment' => 'test'
        ]);
        $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function provideUrls()
    {
        return array(
            array('/'),
            array('/login'),
            array('/signup'),
            array('/password/reset')
        );
    }

    /**
     * Tests login and redirection after success.
     */
    public function testLoginSuccess()
    {
        $client = static::createClient([
            'environment' => 'test'
        ]);

        $client->followRedirects(true);

        if (self::USERS_TO_TEST) {
            foreach(self::USERS_TO_TEST as $role => $data) {
                $client->request(
                    'POST',
                    '/login',
                    [
                        '_username' => $data['email'],
                        '_password' => self::RIGHT_PASSWORD
                    ]
                );

                $this->assertEquals(200, $client->getResponse()->getStatusCode());

//                //Checking User redirection
//                if ('ROLE_USER' == $role ) {
//                    $this->assertEquals('/bucket', $client->getRequest()->getPathInfo());
//                }
            }
        }
    }

    /**
     * Tests login failure and redirection after that.
     */
    public function testLoginFailure()
    {
        $client = static::createClient([
            'environment' => 'test'
        ]);

        $client->followRedirects(true);

        if (self::USERS_TO_TEST) {
            foreach(self::USERS_TO_TEST as $role => $data) {
                $client->request(
                    'POST',
                    '/login',
                    [
                        '_username' => $data['email'],
                        '_password' => self::WRONG_PASSWORD
                    ]
                );

                $this->assertEquals(200, $client->getResponse()->getStatusCode());
                $this->assertEquals('/login', $client->getRequest()->getPathInfo());
            }
        }
    }

    /**
     * Tests pages access.
     */
    public function testLogout()
    {
        $client = static::createClient([
            'environment' => 'test'
        ]);

        $client->followRedirects(true);

        if (self::USERS_TO_TEST) {
            foreach(self::USERS_TO_TEST as $role => $data) {
                $client->request(
                    'POST',
                    '/',
                    [
                        '_username' => $data['email'],
                        '_password' => self::RIGHT_PASSWORD
                    ]
                );

                $this->assertEquals(200, $client->getResponse()->getStatusCode());

                //Logging out
                $client->request('GET','/logout');

                $this->assertEquals(200, $client->getResponse()->getStatusCode());
                $this->assertEquals('/', $client->getRequest()->getPathInfo());
            }
        }
    }

    /**
     * Tests pages access.
     */
    public function testPagesAcceccibilityByUsers()
    {
        $client = static::createClient([
            'environment' => 'test'
        ]);

        $client->followRedirects(true);

        if (self::USERS_TO_TEST) {
            foreach(self::USERS_TO_TEST as $role => $data) {

                //Log in
                $client->request(
                    'POST',
                    '/login',
                    [
                        '_username' => $data['email'],
                        '_password' => self::RIGHT_PASSWORD
                    ]
                );

                $this->assertEquals(200, $client->getResponse()->getStatusCode());

                //Test  pages
                if (self::PAGES_TO_TEST) {
                    foreach(self::PAGES_TO_TEST as $page) {

                        $client->request('GET', $page);

                        if (in_array($page, $data['allowedPages'])) {
                            $this->assertEquals(200, $client->getResponse()->getStatusCode());
                        }
                        else {
                            $this->assertEquals(403, $client->getResponse()->getStatusCode());
                        }
                    }
                }
            }
        }
    }

//    public function testRegistrationNotAllowedForLoggedUser()
//    {
//        $this->client->followRedirects(true);
//        $this->logIn();
//        $this->client->request('GET', '/signup');
//
//        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
//        $this->assertEquals('/', $this->client->getRequest()->getPathInfo());
//    }
//
//    public function testActivateAccountNotAllowedForLoggedUser()
//    {
//        $this->client->followRedirects(true);
//        $this->logIn();
//        $this->client->request('GET', '/signup/password/'.self::HASH);
//
//        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
//        $this->assertEquals('/', $this->client->getRequest()->getPathInfo());
//    }
//
//    public function testAccountActivatedNotAllowedForLoggedUser()
//    {
//        $this->client->followRedirects(true);
//        $this->logIn();
//        $this->client->request('GET', '/signup/activated');
//
//        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
//        $this->assertEquals('/', $this->client->getRequest()->getPathInfo());
//    }
//
//    public function testPasswordResetNotAllowedForLoggedUser()
//    {
//        $this->client->followRedirects(true);
//        $this->logIn();
//        $this->client->request('GET', '/password/reset');
//
//        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
//        $this->assertEquals('/', $this->client->getRequest()->getPathInfo());
//    }
//
//    public function testPasswordNewNotAllowedForLoggedUser()
//    {
//        $this->client->followRedirects(true);
//        $this->logIn();
//        $this->client->request('GET', '/password/new/'.self::HASH);
//
//        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
//        $this->assertEquals('/', $this->client->getRequest()->getPathInfo());
//    }
//
//    public function testPasswordSetNotAllowedForLoggedUser()
//    {
//        $this->client->followRedirects(true);
//        $this->logIn();
//        $this->client->request('GET', '/password/set');
//
//        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
//        $this->assertEquals('/', $this->client->getRequest()->getPathInfo());
//    }
}
