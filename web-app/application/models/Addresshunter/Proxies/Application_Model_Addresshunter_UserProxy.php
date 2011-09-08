<?php

namespace Addresshunter\Proxies;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ORM. DO NOT EDIT THIS FILE.
 */
class Application_Model_Addresshunter_UserProxy extends \Application_Model_Addresshunter_User implements \Doctrine\ORM\Proxy\Proxy
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
    
    
    public function setNickname($nickname)
    {
        $this->__load();
        return parent::setNickname($nickname);
    }

    public function getNickname()
    {
        $this->__load();
        return parent::getNickname();
    }

    public function setOsmId($osmId)
    {
        $this->__load();
        return parent::setOsmId($osmId);
    }

    public function getOsmId()
    {
        $this->__load();
        return parent::getOsmId();
    }

    public function setAuthToken($authToken)
    {
        $this->__load();
        return parent::setAuthToken($authToken);
    }

    public function getAuthToken()
    {
        $this->__load();
        return parent::getAuthToken();
    }

    public function setDateCreated($dateCreated)
    {
        $this->__load();
        return parent::setDateCreated($dateCreated);
    }

    public function getDateCreated()
    {
        $this->__load();
        return parent::getDateCreated();
    }

    public function setDateLastAccess($dateLastAccess)
    {
        $this->__load();
        return parent::setDateLastAccess($dateLastAccess);
    }

    public function getDateLastAccess()
    {
        $this->__load();
        return parent::getDateLastAccess();
    }

    public function setTheme($theme)
    {
        $this->__load();
        return parent::setTheme($theme);
    }

    public function getTheme()
    {
        $this->__load();
        return parent::getTheme();
    }

    public function setTotalPoints($totalPoints)
    {
        $this->__load();
        return parent::setTotalPoints($totalPoints);
    }

    public function getTotalPoints()
    {
        $this->__load();
        return parent::getTotalPoints();
    }

    public function getId()
    {
        $this->__load();
        return parent::getId();
    }

    public function toArray()
    {
        $this->__load();
        return parent::toArray();
    }


    public function __sleep()
    {
        return array('__isInitialized__', 'nickname', 'osmId', 'authToken', 'dateCreated', 'dateLastAccess', 'theme', 'totalPoints', 'id');
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