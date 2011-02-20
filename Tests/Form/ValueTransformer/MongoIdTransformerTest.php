<?php

/*
 * Copyright 2010 Pablo Díez Pascual <pablodip@gmail.com>
 *
 * This file is part of MondongoBundle.
 *
 * MondongoBundle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * MondongoBundle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with MondongoBundle. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Mondongo\MondongoBundle\Tests\Form\ValueTransformer;

use Mondongo\MondongoBundle\Form\ValueTransformer\MongoIdTransformer;

class MongoIdTransformerTest extends \PHPUnit_Framework_TestCase
{
    public function testTransform()
    {
        $transformer = new MongoIdTransformer();

        $this->assertSame('', $transformer->transform(null));

        $string = '123456789012345678901234';
        $this->assertSame($string, $transformer->transform(new \MongoId($string)));

        try {
            $transformer->transform($string);
            $this->fail();
        } catch (\Exception $e) {
            $this->assertInstanceOf('\InvalidArgumentException', $e);
        }

        $strings = array(
            '12345678901234567890123a',
            '12345678901234567890123b',
            '12345678901234567890123c',
        );
        $this->assertSame($strings, $transformer->transform(array(
            new \MongoId($strings[0]),
            new \MongoId($strings[1]),
            new \MongoId($strings[2]),
        )));

        try {
            $transformer->transform(array(new \MongoId($strings[0]), $strings[1]));
            $this->fail();
        } catch (\Exception $e) {
            $this->assertInstanceOf('\InvalidArgumentException', $e);
        }
    }

    public function testReverseTransform()
    {
        $transformer = new MongoIdTransformer();

        $this->assertNull($transformer->reverseTransform(null, null));
        $this->assertNull($transformer->reverseTransform('', null));

        $string = '123456789012345678901234';
        $this->assertEquals(new \MongoId($string), $transformer->reverseTransform($string, null));

        foreach (array(true, '1234567890') as $value) {
            try {
                $transformer->reverseTransform($value, null);
                $this->fail();
            } catch (\Exception $e) {
                $this->assertInstanceOf('\InvalidArgumentException', $e);
            }
        }

        $strings = array(
            '12345678901234567890123a',
            '12345678901234567890123b',
            '12345678901234567890123c',
        );
        $this->assertEquals(array(
            new \MongoId($strings[0]),
            new \MongoId($strings[1]),
            new \MongoId($strings[2]),
        ), $transformer->reverseTransform($strings, null));

        foreach (array(true, '1234567890') as $value) {
            try {
                $transformer->reverseTransform(array($strings[0], $value), null);
                $this->fail();
            } catch (\Exception $e) {
                $this->assertInstanceOf('\InvalidArgumentException', $e);
            }
        }
    }
}
