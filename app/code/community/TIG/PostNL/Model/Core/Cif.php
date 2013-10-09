<?php
/**
 *                  ___________       __            __   
 *                  \__    ___/____ _/  |_ _____   |  |  
 *                    |    |  /  _ \\   __\\__  \  |  |
 *                    |    | |  |_| ||  |   / __ \_|  |__
 *                    |____|  \____/ |__|  (____  /|____/
 *                                              \/       
 *          ___          __                                   __   
 *         |   |  ____ _/  |_   ____ _______   ____    ____ _/  |_ 
 *         |   | /    \\   __\_/ __ \\_  __ \ /    \ _/ __ \\   __\
 *         |   ||   |  \|  |  \  ___/ |  | \/|   |  \\  ___/ |  |  
 *         |___||___|  /|__|   \_____>|__|   |___|  / \_____>|__|  
 *                  \/                           \/               
 *                  ________       
 *                 /  _____/_______   ____   __ __ ______  
 *                /   \  ___\_  __ \ /  _ \ |  |  \\____ \ 
 *                \    \_\  \|  | \/|  |_| ||  |  /|  |_| |
 *                 \______  /|__|    \____/ |____/ |   __/ 
 *                        \/                       |__|    
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL: 
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@totalinternetgroup.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@totalinternetgroup.nl for more information.
 *
 * @copyright   Copyright (c) 2013 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
 
/**
 * Class containing all default methods used for CIF communication by this extension.
 * 
 * If you wish to add new methods you can etxend this class or create a new class that extends TIG_PostNL_Model_Core_Cif_Abstract
 */
class TIG_PostNL_Model_Core_Cif extends TIG_PostNL_Model_Core_Cif_Abstract
{
    /**
     * array containing various barcode types.
     * 
     * Types are as follows:
     * NL: dutch addresses
     * EU: european addresses
     * CD: global addresses
     * 
     * @var array
     */
    protected $_barcodeTypes = array(
        //dutch address
        'NL' => array(
                    'type'  => '3S', 
                    'range' => '', 
                    'serie' => '000000000-999999999',
                ),
        // european address
        'EU' => array( 
                    'type'  => '3S', 
                    'range' => '', 
                    'serie' => '0000000-9999999',
                ),
        //global address
        'CD' => array(
                    'type'  => 'CD', 
                    'range' => '', 
                    'serie' => '0000-9999',
                ),
    );
    
    /**
     * array containing possible address types
     * 
     * @var array
     */
    protected $_addressTypes = array(
        'Receiver'    => '01',
        'Sender'      => '02',
        'Return'      => '03',
        'Collection'  => '04',
        'Alternative' => '08', // alternative sender
        'Delivery'    => '09', // for use with PakjeGemak
    );
    
    /**
     * array containing all available printer types. These are used to determine the output type of shipping labels
     * currently only GraphicFile|PDF is supported
     * 
     * printer type syntax is: <printer family>|<printer type>
     * 
     * @var array
     */
    protected $_printerTypes = array(
        //graphic files
        'GraphicFile|GIF 200 dpi',
        'GraphicFile|GIF 400 dpi',
        'GraphicFile|GIF 600 dpi',
        'GraphicFile|JPG 200 dpi',
        'GraphicFile|JPG 400 dpi',
        'GraphicFile|JPG 600 dpi',
        'GraphicFile|PDF',
        'GraphicFile|PS',
        
        //Intermec FingerPrint
        'IntermecEasyCoder PF4i',
        
        //Intermec IDP
        'Intermec|EasyCoder E4',
        
        //Intermec IPL
        'Intermec|EasyCoder PF4i IPL',
        
        //Sato
        'Sato|GL408e',
        
        //Tec TCPL
        'TEC|B472',
        
        //TECISQ
        'Meto|SP 40',
        'TEC|B-SV4D',
        
        //Zebra EPS2
        'Zebra|LP 2844',
        'Intermec|Easycoder C4',
        'Eltron|EPL 2 Printers',
        'Zebra|EPL 2 Printers',
        'Eltron|Orion',
        'Intermec|PF8d',
        
        //Zebra ZPL II
        'Zebra|LP 2844-Z',
        'Zebra|Stripe S600',
        'Zebra|Z4Mplus',
        'Zebra|Generic ZPL || 200 dpi',
        'Zebra|Generic ZPL || 400 dpi',
        'Zebra|DA 402',
        'Zebra|105Se',
        'Zebra|105SL',
        'Zebra|Stripe S300',
        'Zebra|Stripe S400',
        'Zebra|Stripe S500',
        'Zebra|A300',
        'Zebra|S4M',
        'Zebra|GK420d',
    );
    
    public function getBarcodeTypes()
    {
        return $this->_barcodeTypes;
    }
    
    public function getAddressTypes()
    {
        return $this->_addressTypes;
    }
    
    /**
     * Retrieves a barcode from CIF
     * 
     * @param $barcodeType Which kind of barcode to generate
     * 
     * @return string
     * 
     * @throws TIG_PostNL_Exception
     */
    public function generateBarcode($barcodeType = 'NL')
    {
        $availableBarcodeTypes = $this->getBarcodeTypes();
        if(!array_key_exists($barcodeType, $availableBarcodeTypes)) {
            throw Mage::exception('TIG_PostNL', 'Invalid barcode type requested: ' . $barcodeType);
        }
        
        $barcode = $availableBarcodeTypes[$barcodeType];
        
        $message = $this->_getMessage();
        $customer = $this->_getCustomer();
        $type = $barcode['type'];
        $range = $barcode['range'];
        $serie = $barcode['serie'];
        
        $soapParams = array(
            'Message'  => $message,
            'Customer' => $customer,
            'Barcode'  => array(
                'Type'  => $type,
                'Range' => $range,
                'Serie' => $serie,
            ),
        );
        
        $response = $this->call(
            'Barcode', 
            'GenerateBarcode',
            $soapParams
        );
        
        if (!is_object($response) || !isset($response->Barcode)) {
            throw Mage::exception('TIG_PostNL', 'Invalid barcode response: ' . "\n" . var_export($reponse, true));
        }
        
        return $response->Barcode;
    }
    
    /**
     * Retrieves the latest shipping status of a shipment from CIF
     * 
     * @param $barcode The barcode of the shipment
     * 
     * @return StdClass 
     * 
     * @throws TIG_PostNL_Exception
     */
    public function getShipmentStatus($barcode)
    {
        if (!$barcode) {
            throw Mage::exception('TIG_PostNL', 'No barcode supplied for ShippingStatus soap call');
        }
        
        $message = $this->_getMessage();
        $customer = $this_>_getCustomer();
        
        $soapParams = array(
            'Message'  => $message,
            'Customer' => $customer,
            'Shipment' => array(
                'Barcode' => $barcode,
            ),
        );
        
        $response = $this->call(
            'ShippingStatus', 
            'CompleteStatus', 
            $soapParams
        );
        
        if (!isset($response->Shipments) || !is_array($response->Shipments)) {
            throw Mage::exception('TIG_PostNL', 'Invalid shippingStatus response: ' . "\n" . var_export($reponse, true));
        }
        
        foreach($response->Shipments as $shipment) {
            if($shipment->Barcode === $barcode) { // we need the original shipment, not a related shipment (such as a return shipment)
                return $shipment;
            }
        }
        
        // no shipment could be matched to the supplied barcode
        throw Mage::exception('TIG_PostNL', 'Unable to match barcode to shippingStatus response: ' . "\n" . var_export($reponse, true));
    }
    
    /**
     * @TODO: implement this method
     */
    public function sendConfirmation($shipment)
    {
        throw new Exception("Error: PostNL Confirming method not implemented");
        /*
        $response = $this->_soapCall('Confirming', 'Confirming', array(
            'Message'   => $this->_getMessage(),
            'Customer'  => $this->_getCustomer(true),
            'Shipments' => array(
                'Shipment' => $this->_getShipment($shipment),
            ),
        ));
        throw new Exception("PostNL error: no confirmation success for shipment '" . $shipment->getBarcode() . "'");
        */
    }
    
    /**
     * Generates shipping labels for the chosen shipment
     * 
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @param string $printerType
     * 
     * @return array
     * 
     * @throws TIG_PostNL_Exception
     */
    public function generateLabels($shipment, $printerType = 'GraphicFile|PDF')
    {
        $availablePrinterTypes = $this->_printerTypes;
        if (!in_array($printerType, $availablePrinterTypes)) {
            throw Mage::exception('TIG_PostNL', 'Invalid printer type requested: ' . $printerType);
        }
        
        $message     = $this->_getMessage(array('Printertype' => $printerType));
        $customer    = $this->_getCustomer();
        $cifShipment = $this->_getShipment($shipment);
        
        $soapParams =  array(
            'Message'  => $message,
            'Customer' => $customer,
            'Shipment' => $cifShipment,
        );
        
        $response = $this->call(
            'Labelling', 
            'GenerateLabel', 
            $soapParams
        );
        
        if (!isset($response->Labels) || !is_object($response->Labels)) {
            throw Mage::exception('TIG_PostNL', 'Invalid generateLabels response: ' . "\n" . var_export($reponse, true));
        }
        
        $labels = $response->Labels->Label;
        if (!is_array($labels)) {
            $labels = array($labels);
        }
        
        return $labels;
    }

    protected function _getAddress($type, $object)
    {
        if(!isset($this->_addressTypes[$type]))
        {
            throw new Exception("Address type '$type' not available");
        }
        $addressType  = array('AddressType' => $this->_addressTypes[$type]);
        $addressArray = $this->_prepareAddressArray($type, $object);

        return array_merge($addressType, $addressArray);
    }

    protected function _prepareAddressArray($type, $object)
    {
        return array(
            'Area'             => $object->getArea(),
            'Buildingname'     => $object->getBuilding(),
            'City'             => $object->getCity(),
            'CompanyName'      => $object->getCompany(),
            'Countrycode'      => $object->getCountry(),
            'Department'       => $object->getDepartment(),
            'Doorcode'         => $object->getDoorcode(),
            'FirstName'        => $object->getFirstName(),
            'Floor'            => $object->getFloor(),
            'HouseNr'          => $object->getHouseNr(),
            'HouseNrExt'       => $object->getHouseNrExt(),
            'Name'             => $object->getName(),
            'Region'           => $object->getRegion(),
            'Remark'           => $object->getRemark(),
            'Street'           => $object->getStreet(),
            'Zipcode'          => $object->getZipcode(),
            'StreetHouseNrExt' => $object->getStreetHouseNrExt(),
        );
    }

    protected function _getAmount($shipment)
    {
        if($insuredAmount = $shipment->insuredAmount())
        {
            return array(
                'AccountName'       => '',
                'AccountNr'         => '',
                'AmountType'        => '02', // 01 = COD, 02 = Insured
                'Currency'          => 'EUR',
                'Reference'         => '',
                'TransactionNumber' => '',
                'Value'             => $insuredAmount,
            );
        }
        return array();
    }

    protected function _getCustoms($shipment)
    {
        $invoiceNumber = $shipment->customs_invoice;
        $res = array(
            'ShipmentType'           => $shipment->customs_shipment_type, // Gift / Documents / Commercial Goods / Commercial Sample / Returned Goods
            'HandleAsNonDeliverable' => 'False',
            'Invoice'                => empty($invoiceNumber) ? 'False' : 'True',
            'InvoiceNr'              => empty($invoiceNumber) ? '' : $invoiceNumber,
            'Certificate'            => 'False',
            'License'                => 'False',
            'Currency'               => 'EUR',
            'Content' => array(
                0 => array(
                    'Description'     => '...',
                    'Quantity'        => '...',
                    'Weight'          => '...',
                    'Value'           => $shipment->customs_value,
                    'HSTariffNr'      => '...',
                    'CountryOfOrigin' => '...',
                ),
            ),
        );
        return $res;
    }

    protected function _getContact($shipment)
    {
        $res = array(
            'ContactType' => '01', // Receiver
            'Email'       => $shipment->email,
            'SMSNr'       => '', // never sure if clean 06 number - TODO: check needed for PakjeGemak?
            'TelNr'       => $shipment->phone_number,
        );
        if(empty($res['Email']) && empty($res['SMSNr']) && empty($res['TelNr']))
        {
            // avoid empty contact errors
            $res['Email'] = $this->_customerEmail;
        }
        return $res;
    }

    protected function _getGroup($shipment)
    {
        // NOTE: extra fields can be used to group multi collo shipments (GroupType 03)
        return array(
            'GroupType' => '01',
        );
    }

    protected function _getCustomer($shipment = false)
    {
        $res = array(
            'CustomerCode'   => $this->_customerCode,
            'CustomerNumber' => $this->_customerNumber,
        );
        if($shipment)
        {
            $res += array(
                'Address'            => $this->_getAddress('Sender', $shipment),
                'CollectionLocation' => $this->_customerCollectionLocation,
                'ContactPerson'      => $this->_customerContractPerson,
                'Email'              => $this->_customerEmail,
                'Name'               => $this->_customerName,
            );
        }
        return $res;
    }

    protected function _getMessage($extra = array())
    {
        $res = array(
            'MessageID'        => time(), // TODO: improve to something unique
            'MessageTimeStamp' => date('d-m-Y H:i:s'),
        );
        return array_merge($res, $extra);
    }

    protected function _getShipment($shipment)
    {
        $res = array(
            'Addresses' => array(
                'Address' => $this->_getAddress('Receiver', $shipment),
            ),
            'Amounts' => array(
                'Amount' => $this->_getAmount($shipment),
            ),
            'Barcode' => $shipment->getBarcode(),
            'CollectionTimeStampEnd'   => '',
            'CollectionTimeStampStart' => '',
            'Contacts' => array(
                'Contact' => $this->_getContact($shipment),
            ),
            'Dimension' => array(
                'Weight' => $shipment->getWeight(),
            ),
            'DownPartnerBarcode' => '',
            'DownPartnerID'      => '',
            'Groups' => array(
                'Group' => $this->_getGroup($shipment),
            ),
            'ProductCodeDelivery' => $shipment->getProductCode(),
            'Reference'           => $shipment->getReference(),
        );
        if($shipment->isPakjeGemak())
        {
            // we do not save a separate PakjeGemak address, so duplicate and filter it
            $res['Addresses']['Address'] = array(
                0 => $this->_getAddress('Receiver', $shipment),
                1 => $this->_getAddress('Delivery', $shipment),
            );
            $res['Addresses']['Address'][0]['CompanyName'] = '';
            $res['Addresses']['Address'][1]['Name'] = '';

            $res['Contacts']['Contact']['SMSNr'] = $shipment->phone_number;
        }
        if($shipment->isCD())
        {
            $res['Customs'] = $this->_getCustoms($shipment);
        }
        return $res;
    }

    /**
     * The SOAP functions
     */

    protected function _formatXML($xml)
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        return $dom->saveXML();
    }

    protected function _getSoapHeaders()
    {
        $headers = array();

        // http://stackoverflow.com/questions/13465168/php-namespaces-in-soapheader-child-nodes
        $namespace = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
        $node1     = new SoapVar($this->_username,      XSD_STRING,      null, null, 'Username',      $namespace);
        $node2     = new SoapVar($this->_password,      XSD_STRING,      null, null, 'Password',      $namespace);
        $token     = new SoapVar(array($node1, $node2), SOAP_ENC_OBJECT, null, null, 'UsernameToken', $namespace);
        $security  = new SoapVar(array($token),         SOAP_ENC_OBJECT, null, null, 'Security',      $namespace);
        $headers[] = new SOAPHeader($namespace, 'Security', $security, false);

        return $headers;
    }

    protected function _soapCall($wsdl, $function, $soapParams)
    {
        if(!isset($this->_wsdlFiles[$wsdl]))
        {
            throw new Exception(".wsdl file for function '$wsdl' not specified");
        }
        try
        {
            $wsdlUrl = $this->_wsdlPrefix . $this->_wsdlFiles[$wsdl];
            $client  = new SoapClient($wsdlUrl, array('trace' => 1, 'cache_wsdl' => WSDL_CACHE_NONE));
            $headers = $this->_getSoapHeaders();

            $client->__setSoapHeaders($headers);

//$cif_start = microtime(true);

            $response = $client->__soapCall(
                $function,
                array(
                    $function => $soapParams,
                )
            );

//$cif_end = microtime(true) - $cif_start;
//mail('martin.boer@totalinternetgroup.nl', 'CIF response for function ' . $function . ' took ' . round($cif_end, 2) . ' seconds', '');

            return $response;
        }
        catch(Exception $e)
        {
            $requestXML  = $this->_formatXML($client->__getLastRequest());
            $responseXML = $this->_formatXML($client->__getLastResponse());

            if(APPLICATION_ENV != 'live')
            {
                ini_set('xdebug.var_display_max_depth', 9);
                echo '<h2>Request XML</h2>';
                echo '<pre>' . htmlentities($requestXML) . '</pre>';
                echo '<h2>Response XML</h2>';
                echo '<pre>' . htmlentities($responseXML) . '</pre>';
                die;
            }
            else
            {
                // TODO: convert to error logging in DB
                mail('martin.boer@totalinternetgroup.nl', 'MyParcel CIF error', $requestXML . "\n\n" . $responseXML);
            }
            preg_match('/ErrorMsg>(.*)</', $responseXML, $matches);
            throw new Exception("PostNL error: '" . $matches[1] . "'");
        }
    }
}
