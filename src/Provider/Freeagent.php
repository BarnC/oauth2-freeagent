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

    public function urlContacts()
    {
        return $this->baseURL . 'contacts';
    }

    public function urlInvoices()
    {
        return $this->baseURL . 'invoices';
    }

    public function emailInvoice(AccessToken $token, $invoiceId, Array $data)
    {
        $url = implode('/', [
            $this->urlInvoices(),
            $invoiceId,
            'send_email'
            ]);

        $headers = $this->getHeaders($token);
        $this->sendProviderData($url, $headers, $data);
    }

    public function userDetails($response, AccessToken $token)
    {
        $response = (array)($response->company);
        $company = new Company($response);
        return $company;
    }

    public function createContact(AccessToken $token, Array $data)
    {
        $url = $this->urlContacts($token);
        $headers = $this->getHeaders($token);

        $response = $this->sendProviderData($url, $headers, json_encode($data));
        $contact = (array)(json_decode($response)->contact);
        return $contact;
    }

    public function createInvoice(AccessToken $token, Array $data)
    {
        $url = $this->urlInvoices();
        $headers = $this->getHeaders($token);
        $headers['content-type'] =  'application/json';
        $response = $this->sendProviderData($url, $headers, json_encode($data));
        $invoice = (array)(json_decode($response)->invoice);
        return $invoice;
    }

    protected function sendProviderData($url, array $headers = [], $data)
    {
        try {
            $client = $this->getHttpClient();
            $request = $client->post($url, $headers, $data);
            $response = $request->send()->getBody();
        } catch (\Exception $e) {
            die((new \Symfony\Component\Debug\ExceptionHandler(true))->createResponse($e));
            // @codeCoverageIgnoreStart
            $response = $e->getResponse()->getBody();
            $result = $this->prepareResponse($response);
            throw new IDPException($result);
            // @codeCoverageIgnoreEnd
        }

        return $response;
    }
}
