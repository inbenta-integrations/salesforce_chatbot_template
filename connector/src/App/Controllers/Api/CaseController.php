<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

namespace App\Controllers\Api;

use App\Model\Config;

class CaseController extends Controller
{

    protected $requester;
    protected $api_endpoint;

    /**
     * Create case action
     * @throws \Exception
     */
    public function createCaseAction()
    {
        $response = new \App\Model\Request\Response\Curl();
        $this->requester = new \App\Model\Request\Curl($response);
        $this->api_endpoint = Config::get('sfla.api_endpoint');

        $accessToken = $this->getAccessToken();
        if ($accessToken === '') {
            throw $this->error('An error ocurred');
        }

        $idContact = $this->getContactId($accessToken);
        if ($idContact === '') {
            throw $this->error('Error: contact information');
        }

        $case = $this->createCase($accessToken, $idContact);

        $this->getResponse()
            ->setSuccess(true)
            ->setData('case', $case);
    }

    /**
     * Create a new token, empty string on error
     * @return string
     */
    protected function getAccessToken(): string
    {
        $oauth = new \App\Model\Api\Sfla\Transaction\OAuth($this->requester);
        $oauthResponse = $oauth->process($this->api_endpoint);

        $accessToken = $oauthResponse->getData('access_token');
        if (!is_null($accessToken) && $accessToken !== '') {
            return $accessToken;
        }
        return '';
    }

    /**
     * Get the contact ID
     * If is not found then is created
     * @param string $accessToken
     * @return string
     */
    protected function getContactId(string $accessToken): string
    {
        $idContact = $this->searchContact($accessToken);
        if ($idContact === '') {
            $idContact = $this->insertContact($accessToken);
        }
        return $idContact;
    }

    /**
     * Search for a contact by its email
     * @param string $accessToken
     * @return string
     */
    protected function searchContact(string $accessToken): string
    {
        $contactSearch = new \App\Model\Api\Sfla\Transaction\ContactSearch($this->requester);
        $contactSearch->setAccessToken($accessToken);

        $bodyRequest = $this->getRequest()->getBody()->getData();

        if (!isset($bodyRequest['EMAIL_ADDRESS'])) {
            throw $this->error('Error: email is mandatory');
        }
        $contactSearch->setEmail($bodyRequest['EMAIL_ADDRESS']);

        $contact = $contactSearch->process($this->api_endpoint);

        $records = $contact->getData('records');
        if (isset($records[0]) && isset($records[0]['Id'])) {
            return $records[0]['Id'];
        }
        return '';
    }

    /**
     * Insert a new contact
     * @param string $accessToken
     * @return string
     */
    protected function insertContact(string $accessToken): string
    {
        $contactSave = new \App\Model\Api\Sfla\Transaction\ContactSave($this->requester);
        $contactSave->setAccessToken($accessToken);

        $bodyRequest = $this->getRequest()->getBody()->getData();
        $contactSave->setBodyRequest($bodyRequest);

        $contact = $contactSave->process($this->api_endpoint);

        $id = $contact->getData('id');
        $success = $contact->getData('success');
        if (!is_null($id) && $id !== '' && $success) {
            return $id;
        }
        return '';
    }

    /**
     * Create a new case
     */
    protected function createCase(string $accessToken, string $idContact): string
    {
        $case = new \App\Model\Api\Sfla\Transaction\CaseCreate($this->requester);
        $case->setAccessToken($accessToken);

        $bodyRequest = $this->getRequest()->getBody()->getData();
        $case->setBodyRequest($bodyRequest);

        $case->setContactId($idContact);
        $caseResponse = $case->process($this->api_endpoint);

        $idCase = $caseResponse->getData('id');
        $success = $caseResponse->getData('success');
        if (is_null($idCase) || $idCase === '' || !$success) {
            throw $this->error('Error: case not created');
        }

        return $this->getCaseDetails($accessToken, $idCase);
    }


    /**
     * Get the case details (case number) after the creation
     */
    protected function getCaseDetails(string $accessToken, string $idCase): string
    {
        $case = new \App\Model\Api\Sfla\Transaction\CaseGet($this->requester);
        $case->setAccessToken($accessToken);

        $case->setCaseId($idCase);
        $caseResponse = $case->process($this->api_endpoint);

        $caseNumber = $caseResponse->getData('CaseNumber');

        if (is_null($caseNumber) || $caseNumber === '') {
            throw $this->error('Error: case not created');
        }
        return $caseNumber;
    }
}
