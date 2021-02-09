<?php

namespace IvantageMail\Service;

use Laminas\Http\Client;

/**
 * Service for interacting with the SendGrid API
 *
 * @package IvantageMail
 * @copyright 2015 iVantage Health Analytics, Inc.
 */
class SendGrid {

    const API_V3_URL = 'https://api.sendgrid.com/v3';

    /** HTTP Client used to make requests to the SendGrid API */
    private $httpClient;
    /** API key required for authenticating SendGrid API requests */
    private $apiKey;

    public function __construct($httpClient, $apiKey) {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    /**
     * Get information about all SendGrid templates.
     *
     * @see https://sendgrid.com/docs/API_Reference/Web_API_v3/Template_Engine/templates.html#-GET
     *
     * @return {array} Information about the templates
     */
    public function getTemplates() {
        $data = $this->makeRequest('/templates');
        return $data['templates'];
    }

    /**
     * Get information about a particular SendGrid template.
     *
     * @see https://sendgrid.com/docs/API_Reference/Web_API_v3/Template_Engine/templates.html#-GET
     *
     * @param  {string} $templateId UUID of the template
     * @return {array} Information about the template
     */
    public function getTemplate($templateId) {
        return $this->makeRequest("/templates/$templateId");
    }

    private function makeRequest($url, $verb = 'GET') {
        $url = self::API_V3_URL . $url;
        $this->httpClient->setUri($url);
        $this->httpClient->setMethod($verb);
        // Set the sendgrid authorization headers
        $this->httpClient->setHeaders(array(
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json'
        ));

        $response = $this->httpClient->send();

        if($response->getStatusCode() !== 200) {
            $msg = "Error in sendgrid API request: " . $response->getStatusCode()
                    . " " . $response->getReasonPhrase();
            throw new \Exception($msg);
        }
        return json_decode($response->getBody(), true);
    }
}
