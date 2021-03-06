<?php

namespace AppBundle\Entity\Repository;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends \Doctrine\ORM\EntityRepository
{
    public function findAllIndexedById()
    {
        return $this->getEntityManager()
            ->createQuery("SELECT u
                           FROM AppBundle:User u
                           INDEX BY u.id
                           ORDER BY u.username")
            ->getResult();
    }
}
