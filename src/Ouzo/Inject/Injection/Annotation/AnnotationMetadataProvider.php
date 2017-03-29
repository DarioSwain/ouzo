<?php
/*
 * Copyright (c) Ouzo contributors, http://ouzoframework.org
 * This file is made available under the MIT License (view the LICENSE file for more information).
 */
namespace Ouzo\Injection\Annotation;

interface AnnotationMetadataProvider
{
    /**
     * @param $instance
     * @return mixed array that contains properties metadata: 'property_name' => ['className' => '\Class', 'name' => 'Name']
     */
    public function getMetadata($instance);

    /**
     * @param $className string
     * @return mixed array that contains ordered constructor arguments metadata: '0' => ['className' => '\Class', 'name' => 'Name']
     */
    public function getConstructorMetadata($className);
}
