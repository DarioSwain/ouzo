<?php
/*
 * Copyright (c) Ouzo contributors, http://ouzoframework.org
 * This file is made available under the MIT License (view the LICENSE file for more information).
 */
namespace Ouzo;

use Ouzo\ExceptionHandling\Error;
use Ouzo\Utilities\Arrays;
use Ouzo\Utilities\Functions;
use Ouzo\Utilities\Strings;

class Validatable
{
    protected $_errors = [];
    protected $_errorFields = [];

    public function isValid()
    {
        $this->validate();
        $errors = $this->getErrors();
        return empty($errors);
    }

    public function getErrors()
    {
        return Arrays::map($this->_errors, Functions::extractField('message'));
    }

    public function getErrorObjects()
    {
        return $this->_errors;
    }

    public function getErrorFields()
    {
        return $this->_errorFields;
    }

    public function validate()
    {
        $this->_errors = [];
        $this->_errorFields = [];
    }

    public function validateAssociated(Validatable $validatable)
    {
        $validatable->validate();
        $this->_errors = array_merge($this->getErrorObjects(), $validatable->getErrorObjects());
        $this->_errorFields = array_merge($this->_errorFields, $validatable->getErrorFields());
    }

    /**
     * @param Validatable []
     */
    public function validateAssociatedCollection($validatables)
    {
        foreach ($validatables as $validatable) {
            $this->validateAssociated($validatable);
        }
    }

    public function validateNotBlank($value, $errorMessage, $errorField = null)
    {
        if (Strings::isBlank($value)) {
            $this->error($errorMessage);
            $this->_errorFields[] = $errorField;
        }
    }

    public function validateTrue($value, $errorMessage, $errorField = null)
    {
        if (!$value) {
            $this->error($errorMessage);
            $this->_errorFields[] = $errorField;
        }
    }

    public function validateUnique(array $values, $errorMessage, $errorField = null)
    {
        if (count($values) != count(array_unique($values))) {
            $this->error($errorMessage);
            $this->_errorFields[] = $errorField;
        }
    }

    public function validateDateTime($value, $errorMessage, $errorField = null)
    {
        if (!strtotime($value)) {
            $this->error($errorMessage);
            $this->_errorFields[] = $errorField;
        }
    }

    public function validateStringMaxLength($value, $maxLength, $errorMessage, $errorField = null)
    {
        if (strlen($value) > $maxLength) {
            $this->error($errorMessage);
            $this->_errorFields[] = $errorField;
        }
    }

    public function validateNotEmpty($value, $errorMessage, $errorField = null)
    {
        if (empty($value)) {
            $this->error($errorMessage);
            $this->_errorFields[] = $errorField;
        }
    }

    public function validateEmpty($value, $errorMessage, $errorField = null)
    {
        if (!empty($value)) {
            $this->error($errorMessage);
            $this->_errorFields[] = $errorField;
        }
    }

    public function error($error, $code = 0)
    {
        $this->_errors[] = $error instanceof Error ? $error : new Error($code, $error);
    }

    public function errors(array $errors, $code = 0)
    {
        foreach ($errors as $error) {
            $this->error($error, $code);
        }
    }
}
