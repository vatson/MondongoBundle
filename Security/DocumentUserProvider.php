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

namespace Bundle\Mondongo\MondongoBundle\Security;

use Symfony\Component\Security\User\AccountInterface;
use Symfony\Component\Security\User\UserProviderInterface;
use Symfony\Component\Security\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Exception\UnsupportedAccountException;
use Mondongo\Mondongo;

/**
 * DocumentUserProvider.
 *
 * @package MondongoBundle
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class DocumentUserProvider implements UserProviderInterface
{
    protected $mondongo;
    protected $class;
    protected $field;

    /**
     * Constructor.
     *
     * @param \Mondongo\Mondongo $mondongo A Mondongo.
     * @param string             $class    The class
     * @param string             $field    The field.
     */
    public function __construct(Mondongo $mondongo, $class, $field = null)
    {
        $this->mondongo = $mondongo;
        $this->class    = $class;
        $this->field    = $field;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        $repository = $this->mondongo->getRepository($this->class);

        if ($this->field) {
            $user = $repository->findOne(array($this->field => $username));
        } else {
            if (!$repository instanceof UserProviderInterface) {
                throw new \InvalidArgumentException(sprintf('The Mondongo repository "%s" must implement UserProviderInterface.', get_class($this->repository)));
            }

            $user = $repository->loadUserByUsername($username);
        }

        if (null === $user) {
            throw new UsernameNotFoundException(sprintf('User "%s" not found.', $username));
        }

        return $user;
    }

    /**
     * {@inheritDoc}
     */
    public function loadUserByAccount(AccountInterface $account)
    {
        if (!$account instanceof $this->class) {
            throw new UnsupportedAccountException(sprintf('Instances of "%s" are not supported.', get_class($account)));
        }

        return $this->loadUserByUsername((string) $account);
    }
}
