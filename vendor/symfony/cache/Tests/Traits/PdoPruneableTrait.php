<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace _PhpScoper5eddef0da618a\Symfony\Component\Cache\Tests\Traits;

trait PdoPruneableTrait
{
    protected function isPruned($cache, $name)
    {
        $o = new \ReflectionObject($cache);
        if (!$o->hasMethod('getConnection')) {
            self::fail('Cache does not have "getConnection()" method.');
        }
        $getPdoConn = $o->getMethod('getConnection');
        $getPdoConn->setAccessible(\true);
        /** @var \Doctrine\DBAL\Statement|\PDOStatement $select */
        $select = $getPdoConn->invoke($cache)->prepare('SELECT 1 FROM cache_items WHERE item_id LIKE :id');
        $select->bindValue(':id', \sprintf('%%%s', $name));
        $select->execute();
        return 1 !== (int) (\method_exists($select, 'fetchOne') ? $select->fetchOne() : $select->fetch(\PDO::FETCH_COLUMN));
    }
}
