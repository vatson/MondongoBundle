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

namespace Bundle\Mondongo\MondongoBundle\Form;

use Symfony\Component\Form\ChoiceField;

/**
 * MondongoChoiceForm.
 *
 * @package MondongoBundle
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class MondongoChoiceField extends ChoiceField
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->addRequiredOption('class');
        $this->addOption('method', '__toString');
        $this->addOption('query', array());
        $this->addOption('find_options', array());
        $this->addOption('add_empty');

        if (!is_array($this->getOption('query'))) {
            throw new \InvalidArgumentException('The "query" options must be an array.');
        }

        if (!is_array($this->getOption('find_options'))) {
            throw new \InvalidArgumentException('The "find_options" option must be an array.');
        }

        // choices
        $choices = array();

        // add empty
        if ($this->getOption('add_empty') && !$this->getOption('multiple') && !$this->getOption('expanded')) {
            $choices[''] = '';
        }

        // query
        $documents = \Mondongo\Container::get()
            ->getRepository($this->getOption('class'))
            ->find($this->getOption('query'), $this->getOption('find_options'))
        ;
        foreach ($documents as $document) {
            $choices[$document->getId()->__toString()] = $document->{$this->getOption('method')}();
        }
        $this->addOption('choices', $choices);

        parent::configure();

        // \MongoId value transformer
        $this->setValueTransformer(new ValueTransformer\MongoIdTransformer());
    }
}
