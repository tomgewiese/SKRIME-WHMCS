<?php
/**
 * WHMCS SDK Skrime Registrar Module
 *
 * @see https://developers.whmcs.com/domain-registrars/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license https://www.whmcs.com/license/ WHMCS Eula
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;
use GuzzleHttp\Client;

/**
 * Define module related metadata
 *
 * @return array
 */
function skrime_MetaData()
{
    return array(
        'DisplayName' => 'Skrime Registrar Module',
        'APIVersion' => '1.1',
    );
}

/**
 * Define registrar configuration options.
 *
 * @return array
 */
function skrime_getConfigArray()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Skrime Registrar Module',
        ),
        'apiKey' => array(
            'FriendlyName' => 'API Key',
            'Type' => 'text',
            'Size' => '50',
            'Description' => 'Enter your Skrime API Key here',
        ),
    );
}

/**
 * Helper function to make API requests to SKRIME
 *
 * @param string $endpoint
 * @param array $postfields
 * @return array
 */
function skrime_makeApiRequest($endpoint, $postfields)
{
    $client = new Client();
    $response = $client->request('POST', 'https://skrime.eu/api/' . $endpoint, [
        'json' => $postfields,
    ]);

    return json_decode($response->getBody(), true);
}

/**
 * Register a domain.
 *
 * @param array $params common module parameters
 * @return array
 */
function skrime_RegisterDomain($params)
{
    $postfields = [
        'domain' => $params['sld'] . '.' . $params['tld'],
        'authCode' => '',
        'contact' => [
            'company' => $params["companyname"],
            'firstname' => $params["firstname"],
            'lastname' => $params["lastname"],
            'street' => $params["address1"],
            'number' => '',
            'postcode' => $params["postcode"],
            'city' => $params["city"],
            'state' => $params["state"],
            'country' => $params["countrycode"],
            'email' => $params["email"],
            'phone' => $params["phonenumber"],
        ],
        'tos' => true,
        'cancellation' => false,
    ];

    try {
        $result = skrime_makeApiRequest('domain/order', $postfields);

        if ($result['state'] === 'success') {
            return ['success' => true];
        }

        return ['error' => $result['response']];
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Initiate domain transfer.
 *
 * @param array $params common module parameters
 * @return array
 */
function skrime_TransferDomain($params)
{
    $postfields = [
        'domain' => $params['sld'] . '.' . $params['tld'],
        'authCode' => $params['eppcode'],
        'contact' => [
            'company' => $params["companyname"],
            'firstname' => $params["firstname"],
            'lastname' => $params["lastname"],
            'street' => $params["address1"],
            'number' => '',
            'postcode' => $params["postcode"],
            'city' => $params["city"],
            'state' => $params["state"],
            'country' => $params["countrycode"],
            'email' => $params["email"],
            'phone' => $params["phonenumber"],
        ],
        'tos' => true,
        'cancellation' => false,
    ];

    try {
        $result = skrime_makeApiRequest('domain/order', $postfields);

        if ($result['state'] === 'success') {
            return ['success' => true];
        }

        return ['error' => $result['response']];
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Renew a domain.
 *
 * @param array $params common module parameters
 * @return array
 */
function skrime_RenewDomain($params)
{
    $postfields = [
        'domain' => $params['sld'] . '.' . $params['tld'],
    ];

    try {
        $result = skrime_makeApiRequest('domain/renew', $postfields);

        if ($result['state'] === 'success') {
            return ['success' => true];
        }

        return ['error' => $result['response']];
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Fetch current nameservers.
 *
 * @param array $params common module parameters
 * @return array
 */
function skrime_GetNameservers($params)
{
    $postfields = [
        'domain' => $params['sld'] . '.' . $params['tld'],
    ];

    try {
        $result = skrime_makeApiRequest('domain/nameserver', $postfields);

        if ($result['state'] === 'success') {
            return [
                'ns1' => isset($result['data']['nameserver'][0]) ? $result['data']['nameserver'][0] : '',
                'ns2' => isset($result['data']['nameserver'][1]) ? $result['data']['nameserver'][1] : '',
                'ns3' => isset($result['data']['nameserver'][2]) ? $result['data']['nameserver'][2] : '',
                'ns4' => isset($result['data']['nameserver'][3]) ? $result['data']['nameserver'][3] : '',
                'ns5' => isset($result['data']['nameserver'][4]) ? $result['data']['nameserver'][4] : '',
            ];
        }

        return ['error' => $result['response']];
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Save nameserver changes.
 *
 * @param array $params common module parameters
 * @return array
 */
function skrime_SaveNameservers($params)
{
    $postfields = [
        'domain' => $params['sld'] . '.' . $params['tld'],
        'nameserver' => array_filter([
            $params['ns1'],
            $params['ns2'],
            $params['ns3'],
            $params['ns4'],
            $params['ns5'],
        ]),
    ];

    try {
        $result = skrime_makeApiRequest('domain/nameserver', $postfields);

        if ($result['state'] === 'success') {
            return ['success' => true];
        }

        return ['error' => $result['response']];
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get the current WHOIS Contact Information.
 *
 * @param array $params common module parameters
 * @return array
 */
function skrime_GetContactDetails($params)
{
    $postfields = [
        'domain' => $params['sld'] . '.' . $params['tld'],
    ];

    try {
        $result = skrime_makeApiRequest('domain/single', $postfields);

        if ($result['state'] === 'success') {
            $contact = $result['data']['productInfo']['contact'];

            return [
                'Registrant' => [
                    'First Name' => isset($contact['firstname']) ? $contact['firstname'] : '',
                    'Last Name' => isset($contact['lastname']) ? $contact['lastname'] : '',
                    'Company Name' => isset($contact['company']) ? $contact['company'] : '',
                    'Email Address' => isset($contact['email']) ? $contact['email'] : '',
                    'Address 1' => isset($contact['street']) ? $contact['street'] : '',
                    'Address 2' => isset($contact['number']) ? $contact['number'] : '',
                    'City' => isset($contact['city']) ? $contact['city'] : '',
                    'State' => isset($contact['state']) ? $contact['state'] : '',
                    'Postcode' => isset($contact['postcode']) ? $contact['postcode'] : '',
                    'Country' => isset($contact['country']) ? $contact['country'] : '',
                    'Phone Number' => isset($contact['phone']) ? $contact['phone'] : '',
                ],
            ];
        }

        return ['error' => $result['response']];
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Update the WHOIS Contact Information for a given domain.
 *
 * @param array $params common module parameters
 * @return array
 */
function skrime_SaveContactDetails($params)
{
    $postfields = [
        'domain' => $params['sld'] . '.' . $params['tld'],
        'contact' => [
            'company' => $params["companyname"],
            'firstname' => $params["firstname"],
            'lastname' => $params["lastname"],
            'street' => $params["address1"],
            'number' => '',
            'postcode' => $params["postcode"],
            'city' => $params["city"],
            'state' => $params["state"],
            'country' => $params["countrycode"],
            'email' => $params["email"],
            'phone' => $params["phonenumber"],
        ],
    ];

    try {
        $result = skrime_makeApiRequest('domain/order', $postfields);

        if ($result['state'] === 'success') {
            return ['success' => true];
        }

        return ['error' => $result['response']];
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Check Domain Availability.
 *
 * @param array $params common module parameters
 * @return \WHMCS\Domains\DomainLookup\ResultsList
 */
function skrime_CheckAvailability($params)
{
    $postfields = [
        'domain' => $params['searchTerm'] . $params['tldsToInclude'][0], // Assuming single TLD check
    ];

    try {
        $result = skrime_makeApiRequest('domain/check', $postfields);

        $results = new ResultsList();
        $searchResult = new SearchResult($params['searchTerm'], $params['tldsToInclude'][0]);

        if ($result['state'] === 'success') {
            if ($result['data']['available']) {
                $searchResult->setStatus(SearchResult::STATUS_NOT_REGISTERED);
            } else {
                $searchResult->setStatus(SearchResult::STATUS_REGISTERED);
            }

            $results->append($searchResult);
            return $results;
        }

        return ['error' => $result['response']];
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get DNS Records for DNS Host Record Management.
 *
 * @param array $params common module parameters
 * @return array DNS Host Records
 */
function skrime_GetDNS($params)
{
    $postfields = [
        'domain' => $params['sld'] . '.' . $params['tld'],
    ];

    try {
        $result = skrime_makeApiRequest('domain/dns', $postfields);

        if ($result['state'] === 'success') {
            $records = [];
            foreach ($result['data']['records'] as $record) {
                $records[] = [
                    'hostname' => $record['name'],
                    'type' => $record['type'],
                    'address' => $record['data'],
                    'priority' => isset($record['priority']) ? $record['priority'] : '',
                ];
            }
            return $records;
        }

        return ['error' => $result['response']];
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Update DNS Host Records.
 *
 * @param array $params common module parameters
 * @return array
 */
function skrime_SaveDNS($params)
{
    $postfields = [
        'domain' => $params['sld'] . '.' . $params['tld'],
        'records' => $params['dnsrecords'],
    ];

    try {
        $result = skrime_makeApiRequest('domain/dns', $postfields);

        if ($result['state'] === 'success') {
            return ['success' => true];
        }

        return ['error' => $result['response']];
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}