<?php
/*
 * Copyright (c) Ouzo contributors, http://ouzoframework.org
 * This file is made available under the MIT License (view the LICENSE file for more information).
 */

namespace Ouzo\Injection\Annotation;

use Ouzo\Injection\InjectorException;
use Ouzo\Utilities\Strings;
use ReflectionClass;

class DocCommentExtractor implements AnnotationMetadataProvider
{
    public function getMetadata($instance)
    {
        $annotations = [];
        $class = new ReflectionClass($instance);
        $properties = $class->getProperties();
        foreach ($properties as $property) {
            $doc = $property->getDocComment();
            if (Strings::contains($doc, '@Inject')) {
                if (preg_match("#@var ([\\\\A-Za-z0-9]*)#s", $doc, $matched)) {
                    $className = $matched[1];
                    $name = $this->extractName($doc);
                    $annotations[$property->getName()] = ['name' => $name, 'className' => $className];
                } else {
                    throw new InjectorException('Cannot @Inject dependency. @var is not defined for property $' . $property->getName() . ' in class ' . $class->getName() . '.');
                }
            }
        }
        return $annotations;
    }

    public function getConstructorMetadata($className)
    {
        $annotations = [];
        $instance = new ReflectionClass($className);
        $constructor = $instance->getConstructor();
        if ($constructor) {
            $doc = $constructor->getDocComment();
            if (Strings::contains($doc, '@Inject')) {
                $parameters = $constructor->getParameters();
                foreach ($parameters as $parameter) {
                    if (!$parameter->getClass()) {
                        throw new InjectorException("Cannot @Inject by constructor for class $className. All arguments should have types defined.");
                    }
                    $annotations[] = ['name' => $parameter->getName(), 'className' => $parameter->getClass()->getName()];
                }
            }
        }
        return $annotations;
    }

    private function extractName($doc)
    {
        if (preg_match("#@Named\\(\"([A-Za-z0-9_]*)\"\\)#s", $doc, $matched)) {
            return $matched[1];
        }
        return '';
    }
}
