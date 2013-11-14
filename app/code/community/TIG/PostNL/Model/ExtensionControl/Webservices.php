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
class TIG_PostNL_Model_ExtensionControl_Webservices extends TIG_PostNL_Model_ExtensionControl_Webservices_Abstract
{    
    /**
     * XML paths for security keys
     */
    const XML_PATH_EXTENSIONCONTROL_UNIQUE_KEY  = 'postnl/general/unique_key';
    const XML_PATH_EXTENSIONCONTROL_PRIVATE_KEY = 'postnl/general/private_key';
    
    /**
     * XML paths for webshop activation settings
     */
    const XML_PATH_GENERAL_EMAIL     = 'postnl/general/email';
    const XML_PATH_UNSECURE_BASE_URL = 'web/unsecure/base_url';
    
    /**
     * XML paths for setting statistics
     */
    const XML_PATH_SUPPORTED_PRODUCT_OPTIONS = 'postnl/cif_product_options/supported_product_options';
    const XML_PATH_SPLI_STREET               = 'postnl/cif_address/split_street';
    
    /**
     * XML path to extension activation setting
     */
    const XML_PATH_ACTIVE = 'postnl/general/active';
    
    /**
     * Expected success response
     */
    const SUCCESS_MESSAGE = 'success';
    
    /**
     * Encryption method used for extension control communication
     */
    const ENCRYPTION_METHOD = 'bf-cbc';
    
    /**
     * Shipping method used by PostNL
     */
    const POSTNL_SHIPPING_METHOD = 'postnl_postnl';
    
    /**
     * Activates the webshop. This will trigger a private key and a unique key to be sent to the specified e-mail, which must be
     * entered into system config by the merchant in order to finish the activation process.
     * 
     * @return TIG_PostNL_Model_ExtensionControl_Webservices
     * 
     * @throws TIG_PostNL_Exception
     */
    public function activateWebshop()
    {
        $soapParams = array(
            'email'    => $this->_getEmail(),
            'hostName' => $this->_getHostName(),
        );
        
        $result = $this->call('activateWebshop', $soapParams);
        
        if (!is_array($result)
            || !isset($result['status'])
            || $result['status'] != self::SUCCESS_MESSAGE
        ) {
            throw Mage::exception('TIG_PostNL', 'Invalid activateWebshop response: ' . var_export($result, true));
        }
        
        return $this;
    }
    
    /**
     * Updates the ExtensionControl server with updated statistics
     * 
     * @return TIG_PostNL_Model_ExtensionControl_Webservices
     */
    public function updateStatistics()
    {
        $canSendStatictics = Mage::helper('postnl/webservices')->canSendStatistics();
        if (!$canSendStatictics) {
            throw Mage::exception('TIG_PostNL', 'Unable to update statistics. This feature has been disabled.');
        }   
             
        /**
         * Get the security keys used to encrypt the message
         */
        $uniqueKey  = $this->_getUniqueKey(1);
        $privateKey = $this->_getPrivateKey(1);
        
        /**
         * get version statistics of Magento and the PostNL extension
         */
        $versionData = $this->_getVersionData();
        
        /**
         * Get statistics of the websites using the extension
         */
        $websiteData = $this->_getWebsites();
        
        /**
         * Merge the website and version data
         */
        $data = array_merge($versionData, $websiteData);
        
        /**
         * Serialize and encrypt the data using the private and unique keys
         */
        $serializedData = serialize($data);
        $encryptedData = openssl_encrypt(
            $serializedData, 
            self::ENCRYPTION_METHOD, 
            $privateKey, 
            0, 
            substr($uniqueKey, 0, 8)
        );
        
        /**
         * Build the SOAP parameter array
         */
        $soapParams = array(
            'uniqueKey'    => $uniqueKey,
            'integrityKey' => sha1($serializedData . $privateKey),
            'data'         => $encryptedData,
        );
        
        /**
         * Send the request
         */
        $result = $this->call('updateStatistic', $soapParams);
        
        /**
         * Check if the request was succesfull
         */
        if (!is_array($result)
            || !isset($result['status'])
            || $result['status'] != self::SUCCESS_MESSAGE
        ) {
            throw Mage::exception('TIG_PostNL', 'Invalid updateStatistics response: ' . var_export($result, true));
        }
        
        return $result;
    }
    
    /**
     * Gets information about the Magento vrsion and edition as well as the vrsion of the currently installed PosTNL extension.
     * 
     * @return array
     */
    protected function _getVersionData()
    {
        /**
         * Get Magento and PosTNL extension version numbers
         */
        $magentoVersion = Mage::getVersion();
        $moduleVersion = (string) Mage::getConfig()->getModuleConfig('TIG_PostNL')->version;
        
        /**
         * Get the edition of the current Magento install. Possible options: Enterprise, Community
         * 
         * N.B. Professional and Go editions are not supported at this time
         */
        $isEnterprise = Mage::helper('postnl')->isEnterprise();
        if ($isEnterprise === true) {
            $magentoEdition = 'Enterprise';
        } else {
            $magentoEdition = 'Community';
        }
        
        $versionData = array(
            'magentoVersion' => $magentoVersion,
            'moduleVersion'  => $moduleVersion,
            'magentoEdition' => $magentoEdition,
        );
        
        return $versionData;
    }

    /**
     * Creates the website array for the updateStatistics method
     * 
     * @return array
     */
    protected function _getWebsites()
    {
        $websites = array();
        foreach (Mage::app()->getWebsites() as $website) {
            $extensionEnabled = $website->getConfig(self::XML_PATH_ACTIVE);
            if (!$extensionEnabled) {
                continue;
            }
            
            $websites[] = array(
                'websiteId'         => $website->getId(),
                'hostName'          => $this->_getHostName($website),
                'amountOfShipments' => $this->_getAmountOfShipments($website),
                'settings'          => array(
                    'globalShipping' => $this->_getUsesGlobalShipping($website),
                    'splitAddress'   => $this->_getUsesSplitAddress($website),
                ),
            );
        }
        
        $websiteData = array('websites' => $websites);
        return $websiteData;
    }
    
    /**
     * Get the email contact to which the unique- and privatekeys will be sent after activation
     * 
     * @return string
     */
    protected function _getEmail()
    {
        $email = Mage::getStoreConfig(self::XML_PATH_GENERAL_EMAIL, Mage_Core_Model_App::ADMIN_STORE_ID);
        
        return $email;
    }
    
    /**
     * Get thje hostname of the admin area to use in the module activation procedure or the hostname of a specified website to
     * use with the updateStatistics method
     * 
     * @param Mage_Core_Model_Website $website
     * 
     * @return string
     */
    protected function _getHostName($website = null)
    {
        /**
         * If no website ID is provided, get the current hostname. In most cases this will be the hostname of the admin 
         * environment.
         */
        if ($website === null) {
            $hostName = Mage::helper('core/http')->getHttpHost();
            return $hostName;
        }
        
        /**
         * Get the website's base URL
         */
        $baseUrl = $website->getConfig(self::XML_PATH_UNSECURE_BASE_URL, $website->getId());
        
        /**
         * Parse the URL and get the host name
         */
        $urlParts = parse_url($baseUrl);
        $hostName = $urlParts['host'];
        
        return $hostName;
    }
    
    /**
     * Gets the unique key from system/config. Keys will be decrypted using Magento's encryption key.
     * 
     * @return string
     */
    protected function _getUniqueKey()
    {
        $uniqueKey = Mage::getStoreConfig(self::XML_PATH_EXTENSIONCONTROL_UNIQUE_KEY, Mage_Core_Model_App::ADMIN_STORE_ID);
        $uniqueKey = Mage::helper('core')->decrypt($uniqueKey);
        
        return $uniqueKey;
    }
    
    /**
     * Gets the unique key from system/config. Keys will be decrypted using Magento's encryption key.
     * 
     * @return string
     */
    protected function _getPrivateKey()
    {
        $privateKey = Mage::getStoreConfig(self::XML_PATH_EXTENSIONCONTROL_PRIVATE_KEY, Mage_Core_Model_App::ADMIN_STORE_ID);
        $privateKey = Mage::helper('core')->decrypt($privateKey);
        
        return $privateKey;
    }
    
    /**
     * Get the number of PostNL shipments a specified website has sent
     * 
     * @param Mage_Core_Model_Website $website
     * 
     * @return int
     */
    protected function _getAmountOfShipments($website)
    {
        /**
         * Get a list of all storeIds associated with this website
         */
        $storeIds = array();
        foreach ($website->getGroups() as $group) {
            $stores = $group->getStores();
            foreach ($stores as $store) {
                $storeIds[] = $store->getId();
            }
        }
        
        /**
         * Implode the list to use in the collection select
         */
        $storeIds = implode(',', $storeIds);
        
        $resource = Mage::getSingleton('core/resource');
        
        /**
         * Get he shipment collection
         */
        $shipmentCollection = Mage::getResourceModel('sales/order_shipment_collection');
        $shipmentCollection->addFieldToSelect('entity_id');
        
        $select = $shipmentCollection->getSelect();
        
        /**
         * Join sales_flat_order table
         */
        $select->joinInner(
            array('order' => $resource->getTableName('sales/order')),
            '`main_table`.`order_id`=`order`.`entity_id`',
            array(
                'shipping_method'      => 'order.shipping_method',
            )
        );
        
        $shipmentCollection->addFieldToFilter('`shipping_method`', array('eq' => self::POSTNL_SHIPPING_METHOD))
                           ->addFieldToFilter('`main_table`.`store_id`', array('in' => $storeIds));
        
        $amountOfShipments = $shipmentCollection->getSize();
        return $amountOfShipments;
    }
    
    /**
     * Get whether a specified website uses global shipping
     * 
     * @param Mage_Core_Model_Website $website
     * 
     * @return boolean
     */
    protected function _getUsesGlobalShipping($website)
    {
        /**
         * Get a list of supported product options and a list of global product options
         */
        $supportedProductOptions = $website->getConfig(self::XML_PATH_SUPPORTED_PRODUCT_OPTIONS);
        $supportedProductOptions = explode(',', $supportedProductOptions);
        
        $globalProductOptions = Mage::helper('postnl/cif')->getGlobalProductCodes();
        
        /**
         * Check each global product option if it's supported.
         */
        foreach ($globalProductOptions as $productOption) {
            if (in_array($productOption, $supportedProductOptions)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get the split_street setting for a specified website
     * 
     * @param Mage_Core_Model_Website $website
     * 
     * @return boolean
     */
    protected function _getUsesSplitAddress($website)
    {
        $splitStreet = (bool) $website->getConfig(self::XML_PATH_SPLI_STREET);
        
        return $splitStreet;
    }
}