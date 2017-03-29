<?php
/*
 * Copyright (c) Ouzo contributors, http://ouzoframework.org
 * This file is made available under the MIT License (view the LICENSE file for more information).
 */
namespace Ouzo\Helper;

use Ouzo\Csrf\CsrfProtector;
use Ouzo\I18n;
use Ouzo\Model;
use Ouzo\Utilities\Joiner;
use Ouzo\Utilities\Strings;

class ModelFormBuilder
{
    /** @var Model */
    private $_object;

    public function __construct($object)
    {
        $this->_object = $object;
    }

    private function _objectName()
    {
        return Strings::camelCaseToUnderscore($this->_object->getModelName());
    }

    private function _generateId($name)
    {
        return $this->_objectName() . '_' . $name;
    }

    public function generateName($name)
    {
        return $this->_objectName() . '[' . $name . ']';
    }

    private function _generatePredefinedAttributes($field)
    {
        $id = $this->_generateId($field);
        $attributes = ['id' => $id];

        if (in_array($field, $this->_object->getErrorFields())) {
            $attributes['class'] = 'error';
        }
        return $attributes;
    }

    private function _translate($field)
    {
        return I18n::t($this->_objectName() . '.' . $field);
    }

    public function label($field, array $options = [])
    {
        return labelTag($this->_generateId($field), $this->_translate($field), $options);
    }

    public function textField($field, array $options = [])
    {
        $attributes = $this->_generatePredefinedAttributes($field);
        $attributes = $this->_mergeAttributes($attributes, $options);
        return textFieldTag($this->generateName($field), $this->_object->$field, $attributes);
    }

    public function textArea($field, array $options = [])
    {
        $attributes = $this->_generatePredefinedAttributes($field);
        $attributes = $this->_mergeAttributes($attributes, $options);
        return textAreaTag($this->generateName($field), $this->_object->$field, $attributes);
    }

    public function selectField($field, array $items, $options = [], $promptOption = null)
    {
        $attributes = $this->_generatePredefinedAttributes($field);
        $attributes = $this->_mergeAttributes($attributes, $options);
        return selectTag($this->generateName($field), $items, [$this->_object->$field], $attributes, $promptOption);
    }

    public function hiddenField($field, $options = [])
    {
        $attributes = $this->_generatePredefinedAttributes($field);
        $attributes = $this->_mergeAttributes($attributes, $options);
        return hiddenTag($this->generateName($field), $this->_object->$field, $attributes);
    }

    public function passwordField($field, array $options = [])
    {
        $attributes = $this->_generatePredefinedAttributes($field);
        $attributes = $this->_mergeAttributes($attributes, $options);
        return passwordFieldTag($this->generateName($field), $this->_object->$field, $attributes);
    }

    public function checkboxField($field, array $options = [])
    {
        $attributes = $this->_generatePredefinedAttributes($field);
        $attributes = $this->_mergeAttributes($attributes, $options);
        $value = $this->_object->$field;
        $checked = !empty($value);
        return checkboxTag($this->generateName($field), '1', $checked, $attributes);
    }

    public function radioField($field, array $options = [])
    {
        $attributes = $this->_generatePredefinedAttributes($field);
        $attributes = $this->_mergeAttributes($attributes, $options);
        return radioButtonTag($this->generateName($field), $this->_object->$field, $attributes);
    }

    public function start($url, $method = 'post', $attributes = [])
    {
        return formTag($url, $method, $attributes) . hiddenTag('csrftoken', CsrfProtector::getCsrfToken());
    }

    public function end()
    {
        return endFormTag();
    }

    public function getObject()
    {
        return $this->_object;
    }

    private function _mergeAttributes($attributes, $options)
    {
        if (isset($options['class']) && isset($attributes['class'])) {
            $options['class'] = Joiner::on(' ')->skipNulls()->join([$options['class'], $attributes['class']]);
        }
        return array_merge($attributes, $options);
    }
}
