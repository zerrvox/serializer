<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\Serializer\Tests\Metadata\Driver;

use JMS\Serializer\Metadata\Driver\AnnotationDriver;
use JMS\Serializer\Metadata\Driver\DoctrineTypeDriver;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\PHPCR\Configuration;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver as DoctrineDriver;
use Jackalope\Session;

class DoctrinePHPCRDriverTest extends \PHPUnit_Framework_TestCase
{
    public function getMetadata()
    {
        $refClass = new \ReflectionClass('JMS\Serializer\Tests\Fixtures\Doctrine\PHPCR\BlogPost');
        $metadata = $this->getDoctrineDriver()->loadMetadataForClass($refClass);

        return $metadata;
    }

    public function testTypelessPropertyIsGivenTypeFromDoctrineMetadata()
    {
        $metadata = $this->getMetadata();

        $this->assertEquals(
            array('name'=> 'DateTime', 'params' => array()),
            $metadata->propertyMetadata['createdAt']->type
        );
    }

    public function testSingleValuedAssociationIsProperlyHinted()
    {
        $metadata = $this->getMetadata();

        $this->assertEquals(
            array('name'=> 'JMS\Serializer\Tests\Fixtures\Doctrine\PHPCR\Author', 'params' => array()),
            $metadata->propertyMetadata['author']->type
        );
    }

    public function testMultiValuedAssociationIsProperlyHinted()
    {
        $metadata = $this->getMetadata();

        $this->assertEquals(
            array('name'=> 'ArrayCollection', 'params' => array(
                array('name' => 'JMS\Serializer\Tests\Fixtures\Doctrine\PHPCR\Comment', 'params' => array()))
            ),
            $metadata->propertyMetadata['comments']->type
        );
    }

    public function testTypeGuessByDoctrineIsOverwrittenByDelegateDriver()
    {
        $metadata = $this->getMetadata();

        // This would be guessed as boolean but we've overriden it to integer
        $this->assertEquals(
            array('name'=> 'integer', 'params' => array()),
            $metadata->propertyMetadata['published']->type
        );
    }

    public function testNodenameIsConvertedToString()
    {
        $metadata = $this->getMetadata();

        $this->assertEquals(
            array('name'=> 'string', 'params' => array()),
            $metadata->propertyMetadata['name']->type
        );
    }

    public function testNodeIsConvertedToString()
    {
        $metadata = $this->getMetadata();

        $this->assertEquals(
            array('name'=> 'string', 'params' => array()),
            $metadata->propertyMetadata['node']->type
        );
    }

    public function testLocaleIsConvertedToString()
    {
        $metadata = $this->getMetadata();

        $this->assertEquals(
            array('name'=> 'string', 'params' => array()),
            $metadata->propertyMetadata['locale']->type
        );
    }

    public function testUuidIsConvertedToString()
    {
        $metadata = $this->getMetadata();

        $this->assertEquals(
            array('name'=> 'string', 'params' => array()),
            $metadata->propertyMetadata['uuid']->type
        );
    }

    public function testParentDocumentIsConvertedToString()
    {
        $metadata = $this->getMetadata();

        $this->assertEquals(
            array('name'=> 'string', 'params' => array()),
            $metadata->propertyMetadata['parent']->type
        );
    }

    public function testChildIsConvertedToString()
    {
        $metadata = $this->getMetadata();

        $this->assertEquals(
            array('name'=> 'string', 'params' => array()),
            $metadata->propertyMetadata['child']->type
        );
    }

    public function testChildrenIsConvertedToString()
    {
        $metadata = $this->getMetadata();

        $this->assertEquals(
            array('name'=> 'string', 'params' => array()),
            $metadata->propertyMetadata['children']->type
        );
    }

    public function testReferrersIsProperlyHinted()
    {
        $metadata = $this->getMetadata();
        $this->assertEquals(
            array('name'=> 'ArrayCollection', 'params' => array(
                array('name'=> 'JMS\Serializer\Tests\Fixtures\Doctrine\PHPCR\Author', 'params' => array()))
            ),
            $metadata->propertyMetadata['referrers']->type
        );
    }

    public function testReferenceOneWithoutTargetDocumentIsConvertedToString()
    {
        $metadata = $this->getMetadata();

        $this->assertEquals(
            array('name'=> 'string', 'params' => array()),
            $metadata->propertyMetadata['publishedIn']->type
        );
    }

    // Can't come up with an equevalent test like the one for ORM
    // public function testUnknownDoctrineTypeDoesNotResultInAGuess()
    // {
    //     $metadata = $this->getMetadata();
    //     $this->assertNull($metadata->propertyMetadata['slug']->type);
    // }

    public function testNonDoctrineEntityClassIsNotModified()
    {
        // Note: Using regular BlogPost fixture here instead of Doctrine fixture
        // because it has no Doctrine metadata.
        $refClass = new \ReflectionClass('JMS\Serializer\Tests\Fixtures\BlogPost');

        $plainMetadata = $this->getAnnotationDriver()->loadMetadataForClass($refClass);
        $doctrineMetadata = $this->getDoctrineDriver()->loadMetadataForClass($refClass);

        $this->assertEquals($plainMetadata, $doctrineMetadata);
    }

    protected function getEntityManager()
    {
        $config = new Configuration();
        $config->setProxyDir(sys_get_temp_dir() . '/JMSDoctrineTestProxies');
        $config->setProxyNamespace('JMS\Tests\Proxies');
        $config->setMetadataDriverImpl(
            new DoctrineDriver(new AnnotationReader(), __DIR__.'/../../Fixtures/Doctrine/PHPCR')
        );

        $session = $this->getMockBuilder('PHPCR\SessionInterface')
            ->disableOriginalConstructor()
            ->getMock();
        return DocumentManager::create($session, $config);
    }

    public function getAnnotationDriver()
    {
        return new AnnotationDriver(new AnnotationReader());
    }

    protected function getDoctrineDriver()
    {
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->atLeastOnce())
             ->method('getManagerForClass')
             ->will($this->returnValue($this->getEntityManager()));

        return new DoctrineTypeDriver(
            $this->getAnnotationDriver(),
            $registry
        );
    }
}
