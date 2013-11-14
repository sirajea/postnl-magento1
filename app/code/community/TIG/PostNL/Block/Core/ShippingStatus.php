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
class TIG_PostNL_Block_Core_ShippingStatus extends Mage_Core_Block_Template
{
    /**
     * Available status classes for the status bar html element
     */   
    const CLASS_UNCONFIRMED  = '';
    const CLASS_COLLECTION   = 'status-collection';
    const CLASS_DISTRIBUTION = 'status-distribution';
    const CLASS_TRANSIT      = 'status-transit';
    const CLASS_DELIVERED    = 'status-delivered';
    const CLASS_NOT_POSTNL   = 'hidden';
    
    /**
     * Get the current shipping status for a shipment
     * 
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * 
     * @return string
     */
    public function getShippingStatus($shipment)
    {
        $postnlShipment = Mage::getModel('postnl_core/shipment')->load($shipment->getId(), 'shipment_id');
        
        /**
         * Check if the postnl shipment exists. Otherwise it was probably not shipped using PostNL.
         * Even if it was, we would not be able to check the status of it anyway.
         */
        if (!$postnlShipment->getId()) {
            return self::CLASS_NOT_POSTNL;
        }
        
        switch ($postnlShipment->getShippingPhase()) {
            case '01': 
                $class = self::CLASS_COLLECTION;
                break;
            case '02': 
                $class = self::CLASS_DISTRIBUTION;
                break;
            case '03': 
                $class = self::CLASS_TRANSIT;
                break;
            case '04': 
                $class = self::CLASS_DELIVERED;
                break;
            default:
                $class = self::CLASS_UNCONFIRMED;
                break;
        }
        
        return $class;
    }
    
    /**
     * Check if the PostNL module is enabled. Otherwise return an empty string.
     * 
     * @return string | Mage_Core_Block_Template::_toHtml()
     */
    protected function _toHtml()
    {
        if (!Mage::helper('postnl')->isEnabled()) {
            return '';
        }
        
        return parent::_toHtml();
    }
}
