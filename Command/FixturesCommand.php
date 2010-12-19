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

namespace Bundle\Mondongo\MondongoBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Mondongo\DataLoader;

/**
 * FixturesCommand.
 *
 * @package MondongoBundle
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class FixturesCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mondongo:fixtures')
            ->setDescription('Load the fixtures.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('processing fixtures');

        $data = array();
        foreach ($this->container->get('kernel')->getBundles() as $bundle) {
            if (is_dir($dir = $bundle->getPath().'/Resources/fixtures/mondongo'))
            {
                $finder = new Finder();
                foreach ($finder->files()->name('*.yml')->followLinks()->in($dir) as $file) {
                    $data = \Bundle\Mondongo\MondongoBundle\MondongoBundle::arrayDeepMerge($data, (array) Yaml::load($file));
                }
            }
        }

        if (!$data) {
            $output->writeln('there are not fixtures');

            return;
        }

        $output->writeln('loading fixtures');

        $dataLoader = new DataLoader($this->container->get('mondongo'), $data);
        $dataLoader->load(true);
    }
}
