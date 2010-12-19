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
        $this->addRequiredOption('mondongo');
        $this->addRequiredOption('document_class');
        $this->addOption('document_field');
        $this->addOption('find_options', array());
        $this->addOption('empty_choice');

        $mondongo = $this->getOption('mondongo');
        if (!$mondongo instanceof \Mondongo\Mondongo) {
            throw new \InvalidArgumentException('The "mondongo" option must be an instance of \Mondongo\Mondongo.');
        }

        if (!is_array($this->getOption('find_options'))) {
            throw new \InvalidArgumentException('The "find_options" option must be an array.');
        }

        // choices
        $choices = array();

        // empty choice
        if ($this->getOption('empty_choice') && !$this->getOption('multiple') && !$this->getOption('expanded')) {
            $choices[''] = '';
        }

        // query
        foreach ($mondongo->getRepository($this->getOption('document_class'))->find($this->getOption('find_options')) as $document) {
            if ($field = $this->getOption('document_field')) {
                $value = $document->get($field);
            } else {
                $value = $document->getId()->__toString();
            }
            $choices[$document->getId()->__toString()] = $value;
        }
        $this->addOption('choices', $choices);

        parent::configure();

        // \MongoId value transformer
        $this->setValueTransformer(new ValueTransformer\MongoIdTransformer());
    }
}
