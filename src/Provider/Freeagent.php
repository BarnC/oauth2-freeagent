<?php

namespace CloudManaged\OAuth2\Client\Provider;

use CloudManaged\OAuth2\Client\Entity\Company;
use Guzzle\Http\Exception\BadResponseException;
use League\OAuth2\Client\Exception\IDPException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;

class FreeAgent extends AbstractProvider
{
    public $responseType = 'string';
    public $baseURL = 'https://api.freeagent.com/v2/';

    public function __construct(array $options = array())
    {
        parent::__construct($options);
        if (isset($options['sandbox']) && $options['sandbox']) {
            $this->baseURL = 'https://api.sandbox.freeagent.com/v2/';
        }
    }

    public function urlBase()
    {
        return $this->baseURL;
    }

    public function urlAuthorize()
    {
        return $this->baseURL . 'approve_app';
    }

    public function urlAccessToken()
    {
        return $this->baseURL . 'token_endpoint';
    }

    public function urlUserDetails(AccessToken $token = null)
    {
        return $this->baseURL . 'company';
    }

    public function urlCreateContact()
    {
        return $this->baseURL . 'contacts';
    }

    public function userDetails($response, AccessToken $token)
    {
        $response = (array)($response->company);
        $company = new Company($response);
        return $company;
    }

    public function createContact(AccessToken $token, Array $data)
    {
        $url = $this->urlCreateContact($token);
        $headers = $this->getHeaders($token);
        $contact = (array)(json_decode($this->sendProviderData($url, $headers, $data))->contact);
        // Free agent doesn't return the id as its own field, so we parse it out of the URL which IS supplied.. Thanks Freeagent.
        $contact['id'] = explode('/contacts/', $contact['url'])[1];
        return $contact;
    }

    protected function sendProviderData($url, array $headers = [], $data)
    {
        try {
            $client = $this->getHttpClient();
            $request = $client->post($this->urlCreateContact(), $headers, $data);
            $response = $request->send()->getBody();
        } catch (BadResponseException $e) {
            // @codeCoverageIgnoreStart
            $response = $e->getResponse()->getBody();
            $result = $this->prepareResponse($response);
            throw new IDPException($result);
            // @codeCoverageIgnoreEnd
        }

        return $response;
    }
}
