<?php
/**
 * WHMCS SDK SKRIME Registrar Module
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
use WHMCS\Database\Capsule;

/**
 * Define module related metadata
 *
 * @return array
 */
function skrime_MetaData()
{
    return array(
        'DisplayName' => 'SKRIME',
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
            'Value' => 'skrime',
        ),
        'apiKey' => array(
            'FriendlyName' => 'API Key',
            'Type' => 'text',
            'Size' => '50',
            'Description' => 'Enter your skrime API Key here',
        ),
    );
}

/**
 * Helper function to make API requests to skrime
 *
 * @param string $endpoint
 * @param array $postfields
 * @param string $method
 * @param array $params
 * @return array
 */
function skrime_makeApiRequest($endpoint, $postfields = [], $method = 'POST', $params = [])
{
    $apiKey = $params['apiKey'];

    $client = new Client();
    $options = [
        'headers' => [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ],
        'json' => $postfields,
    ];

    $response = $client->request($method, 'https://skrime.eu/api/' . $endpoint, $options);

    return json_decode($response->getBody(), true);
}

function extractStreetAndNumber($address)
{
    // Regular expression to match the street name and house number
    // This assumes the house number is at the end of the string and contains numbers
    if (preg_match('/^(.+?)\s?(\d+.*)$/', $address, $matches)) {
        return [$matches[1], $matches[2]];
    }
    // If the regex fails, return the whole address as street and empty number
    return [$address, ''];
}

function cleanOrganizationName($name)
{
    // Remove unwanted characters
    return preg_replace('/[()ÄÖÜ]/', '', $name);
}


/**
 * Register a domain.
 *
 * @param array $params common module parameters
 * @return array
 */
function skrime_RegisterDomain($params)
{
    list($street, $number) = extractStreetAndNumber($params["address1"]);

    $postfields = [
        'domain' => $params['sld'] . '.' . $params['tld'],
        'authCode' => '',
        'contact' => [
            'company' => cleanOrganizationName($params["companyname"]),
            'firstname' => $params["firstname"],
            'lastname' => $params["lastname"],
            'street' => $street,
            'number' => $number,
            'postcode' => $params["postcode"],
            'city' => $params["city"],
            'state' => $params["state"],
            'country' => $params["countrycode"],
            'email' => $params["email"],
            'phone' => $params["fullphonenumber"],
            'nameserver' => array_filter([
                $params['ns1'],
                $params['ns2'],
                $params['ns3'],
                $params['ns4'],
                $params['ns5'],
            ]),
        ],
        'tos' => true,
        'cancellation' => true,
    ];

    try {
        $result = skrime_makeApiRequest('domain/order', $postfields, 'POST', $params);

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
    list($street, $number) = extractStreetAndNumber($params["address1"]);

    $postfields = [
        'domain' => $params['sld'] . '.' . $params['tld'],
        'authCode' => $params['eppcode'],
        'contact' => [
            'company' => cleanOrganizationName($params["companyname"]),
            'firstname' => $params["firstname"],
            'lastname' => $params["lastname"],
            'street' => $street,
            'number' => $number,
            'postcode' => $params["postcode"],
            'city' => $params["city"],
            'state' => $params["state"],
            'country' => $params["countrycode"],
            'email' => $params["email"],
            'phone' => $params["fullphonenumber"],
            'nameserver' => array_filter([
                $params['ns1'],
                $params['ns2'],
                $params['ns3'],
                $params['ns4'],
                $params['ns5'],
            ]),
        ],
        'tos' => true,
        'cancellation' => true,
    ];

    try {
        $result = skrime_makeApiRequest('domain/order', $postfields, 'POST', $params);

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
        $result = skrime_makeApiRequest('domain/renew', $postfields, 'POST', $params);

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
        $result = skrime_makeApiRequest('domain/nameserver', $postfields, 'GET', $params);

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
        $result = skrime_makeApiRequest('domain/nameserver', $postfields, 'POST', $params);

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
        $result = skrime_makeApiRequest('domain/contact', $postfields, 'GET', $params);

        if ($result['state'] === 'success') {
            $contact = $result['data']['contact'];

            return [
                'Registrant' => [
                    'First Name' => $contact['firstname'],
                    'Last Name' => $contact['lastname'],
                    'Company Name' => $contact['company'],
                    'Email Address' => $contact['email'],
                    'Address 1' => $contact['street'],
                    'Address 2' => $contact['number'],
                    'City' => $contact['city'],
                    'State' => $contact['state'],
                    'Postcode' => $contact['postcode'],
                    'Country' => $contact['country'],
                    'Phone Number' => $contact['phone'],
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
    list($street, $number) = extractStreetAndNumber($params["address1"]);

    $postfields = [
        'domain' => $params['sld'] . '.' . $params['tld'],
        'contact' => [
            'company' => $params["companyname"],
            'firstname' => $params["firstname"],
            'lastname' => $params["lastname"],
            'street' => $street,
            'number' => $number,
            'postcode' => $params["postcode"],
            'city' => $params["city"],
            'state' => $params["state"],
            'country' => $params["countrycode"],
            'email' => $params["email"],
            'phone' => $params["phonenumber"],
        ],
    ];

    try {
        $result = skrime_makeApiRequest('domain/contact', $postfields, 'POST', $params);

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
        $result = skrime_makeApiRequest('domain/check', $postfields, 'POST', $params);

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
        $result = skrime_makeApiRequest('domain/dns', $postfields, 'GET', $params);

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
    // Transform the DNS records into the required format
    $dnsRecords = [];
    foreach ($params['dnsrecords'] as $record) {
        $dnsRecords[] = [
            'name' => $record['hostname'],
            'type' => $record['type'],
            'data' => $record['address'],
        ];
    }

    $postfields = [
        'domain' => $params['sld'] . '.' . $params['tld'],
        'records' => $dnsRecords,
    ];

    try {
        $result = skrime_makeApiRequest('domain/dns', $postfields, 'POST', $params);

        if ($result['state'] === 'success') {
            return ['success' => true];
        }

        return ['error' => $result['response']];
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}


/**
 * Fetch all domains.
 *
 * @param array $params common module parameters
 * @return array
 */
function skrime_GetAllDomains($params)
{
    try {
        $result = skrime_makeApiRequest('domain/all', [], 'GET', $params);

        if ($result['state'] === 'success') {
            return $result['data'];
        }

        return ['error' => $result['response']];
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Fetch domain pricelist.
 *
 * @param array $params common module parameters
 * @return array
 */
function skrime_GetDomainPricelist($params)
{
    try {
        $result = skrime_makeApiRequest('domain/pricelist', [], 'GET', $params);

        if ($result['state'] === 'success') {
            return $result['data'];
        }

        return ['error' => $result['response']];
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get EPP code (AuthInfo) for a domain.
 *
 * @param array $params common module parameters
 * @return array
 */
function skrime_GetEPPCode($params)
{
    $postfields = [
        'domain' => $params['sld'] . '.' . $params['tld'],
    ];

    try {
        $result = skrime_makeApiRequest('domain/authcode', $postfields, 'GET', $params);

        if ($result['state'] === 'success') {
            return ['eppcode' => $result['data']['authcode']];
        }

        return ['error' => $result['response']];
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Synchronize domain information.
 *
 * @param array $params common module parameters
 * @return array
 */
function skrime_Sync($params)
{
    $postfields = [
        'domain' => $params['sld'] . '.' . $params['tld'],
    ];

    try {
        $result = skrime_makeApiRequest('domain/single', $postfields, 'GET', $params);

        if ($result['state'] === 'success') {
            $expirydate = $result['data']['expireAt'];
            $active = $result['data']['state'] === 'active';
            $transferredAway = $result['data']['state'] === 'transferredaway';

            return [
                'expirydate' => $expirydate,
                'active' => $active,
                'cancelled' => !$active,
                'transferredAway' => $transferredAway,
            ];
        }

        return ['error' => $result['response']];
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get TLD Pricing
 *
 * @param array $params common module parameters
 * @return \WHMCS\Results\ResultsList
 */
function skrime_GetTldPricing(array $params)
{
    $pricelist = skrime_GetDomainPricelist($params);

    if (isset($pricelist['error'])) {
        return ['error' => $pricelist['error']];
    }

    $results = new ResultsList();

    foreach ($pricelist as $extension) {
        $item = (new ImportItem)
            ->setExtension('.' . $extension['tld'])
            ->setMinYears(1)
            ->setMaxYears(10)
            ->setRegisterPrice((float) $extension['create'])
            ->setRenewPrice((float) $extension['renew'])
            ->setTransferPrice((float) $extension['transfer'])
            ->setRedemptionFeeDays(30)  // Adjust as needed
            ->setRedemptionFeePrice((float) $extension['restore'])
            ->setCurrency('EUR')
            ->setEppRequired(true);

        $results[] = $item;
    }

    return $results;
}