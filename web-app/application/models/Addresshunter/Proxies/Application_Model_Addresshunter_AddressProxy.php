<?php

namespace Addresshunter\Proxies;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ORM. DO NOT EDIT THIS FILE.
 */
class Application_Model_Addresshunter_AddressProxy extends \Application_Model_Addresshunter_Address implements \Doctrine\ORM\Proxy\Proxy
{
    private $_entityPersister;
    private $_identifier;
    public $__isInitialized__ = false;
    public function __construct($entityPersister, $identifier)
    {
        $this->_entityPersister = $entityPersister;
        $this->_identifier = $identifier;
    }
    /** @private */
    public function __load()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;
            if ($this->_entityPersister->load($this->_identifier, $this) === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            unset($this->_entityPersister, $this->_identifier);
        }
    }
    
    
    public function setCountry($country)
    {
        $this->__load();
        return parent::setCountry($country);
    }

    public function getCountry()
    {
        $this->__load();
        return parent::getCountry();
    }

    public function setCity($city)
    {
        $this->__load();
        return parent::setCity($city);
    }

    public function getCity()
    {
        $this->__load();
        return parent::getCity();
    }

    public function setPostcode($postcode)
    {
        $this->__load();
        return parent::setPostcode($postcode);
    }

    public function getPostcode()
    {
        $this->__load();
        return parent::getPostcode();
    }

    public function setStreet($street)
    {
        $this->__load();
        return parent::setStreet($street);
    }

    public function getStreet()
    {
        $this->__load();
        return parent::getStreet();
    }

    public function setHousenumber($housenumber)
    {
        $this->__load();
        return parent::setHousenumber($housenumber);
    }

    public function getHousenumber()
    {
        $this->__load();
        return parent::getHousenumber();
    }

    public function setAddressHash($addressHash)
    {
        $this->__load();
        return parent::setAddressHash($addressHash);
    }

    public function getAddressHash()
    {
        $this->__load();
        return parent::getAddressHash();
    }

    public function setApproxX($approxX)
    {
        $this->__load();
        return parent::setApproxX($approxX);
    }

    public function getApproxX()
    {
        $this->__load();
        return parent::getApproxX();
    }

    public function setApproxY($approxY)
    {
        $this->__load();
        return parent::setApproxY($approxY);
    }

    public function getApproxY()
    {
        $this->__load();
        return parent::getApproxY();
    }

    public function setIsAvailable($isAvailable)
    {
        $this->__load();
        return parent::setIsAvailable($isAvailable);
    }

    public function getIsAvailable()
    {
        $this->__load();
        return parent::getIsAvailable();
    }

    public function setFull($full)
    {
        $this->__load();
        return parent::setFull($full);
    }

    public function getFull()
    {
        $this->__load();
        return parent::getFull();
    }

    public function getId()
    {
        $this->__load();
        return parent::getId();
    }

    public function getName()
    {
        $this->__load();
        return parent::getName();
    }


    public function __sleep()
    {
        return array('__isInitialized__', 'country', 'city', 'postcode', 'street', 'housenumber', 'addressHash', 'approxX', 'approxY', 'isAvailable', 'full', 'id');
    }

    public function __clone()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;
            $class = $this->_entityPersister->getClassMetadata();
            $original = $this->_entityPersister->load($this->_identifier);
            if ($original === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            foreach ($class->reflFields AS $field => $reflProperty) {
                $reflProperty->setValue($this, $reflProperty->getValue($original));
            }
            unset($this->_entityPersister, $this->_identifier);
        }
        
    }
}